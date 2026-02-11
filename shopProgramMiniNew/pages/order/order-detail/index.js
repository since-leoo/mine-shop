import { formatTime } from '../../../utils/util';
import { OrderStatus, LogisticsIconMap } from '../config';
import { fetchBusinessTime, fetchOrderDetail } from '../../../services/order/orderDetail';
import Toast from 'tdesign-miniprogram/toast/index';
import { getAddressPromise } from '../../../services/address/list';

Page({
  data: {
    pullDownRefreshing: false,
    pageLoading: true,
    order: {}, // 后台返回的原始数据
    _order: {}, // 内部使用和提供给 order-card 的数据
    storeDetail: {},
    countDownTime: null,
    addressEditable: false,
    backRefresh: false, // 用于接收其他页面back时的状态
    formatCreateTime: '', //格式化订单创建时间
    logisticsNodes: [],
    /** 订单评论状态 */
    orderHasCommented: true,
  },

  onLoad(query) {
    this.orderNo = query.orderNo;
    this.init();
    this.navbar = this.selectComponent('#navbar');
    this.pullDownRefresh = this.selectComponent('#wr-pull-down-refresh');
  },

  onShow() {
    // 当从其他页面返回，并且 backRefresh 被置为 true 时，刷新数据
    if (!this.data.backRefresh) return;
    this.onRefresh();
    this.setData({ backRefresh: false });
  },

  onPageScroll(e) {
    this.pullDownRefresh && this.pullDownRefresh.onPageScroll(e);
  },

  onImgError(e) {
    if (e.detail) {
      console.error('img 加载失败');
    }
  },

  // 页面初始化，会展示pageLoading
  init() {
    this.setData({ pageLoading: true });
    this.getStoreDetail();
    this.getDetail()
      .then(() => {
        this.setData({ pageLoading: false });
      })
      .catch((e) => {
        console.error(e);
      });
  },

  // 页面刷新，展示下拉刷新
  onRefresh() {
    this.init();
    // 如果上一页为订单列表，通知其刷新数据
    const pages = getCurrentPages();
    const lastPage = pages[pages.length - 2];
    if (lastPage) {
      lastPage.data.backRefresh = true;
    }
  },

  // 页面刷新，展示下拉刷新
  onPullDownRefresh_() {
    this.setData({ pullDownRefreshing: true }, () => {
      this.getDetail()
        .then(() => {
          this.setData({ pullDownRefreshing: false });
        })
        .catch(() => {
          this.setData({
            pullDownRefreshing: false,
          });
        });
    });
  },

  getDetail() {
    const params = { orderNo: this.orderNo };
    return fetchOrderDetail(params).then((res) => {
      // 后端返回的数据可能是 res.data 或直接 res
      const order = res?.data || res;
      if (!order || !order.orderNo) {
        console.error('order detail empty');
        return;
      }

      const addr = order.address || {};
      const receiverAddress = [
        addr.provinceName, addr.cityName, addr.districtName, addr.detailAddress,
      ].filter(Boolean).join('');

      // 构造 logisticsVO 供 WXML 模板使用
      order.logisticsVO = {
        receiverName: addr.receiverName || addr.name || '',
        receiverPhone: addr.receiverPhone || addr.phone || '',
        logisticsNo: '',
      };

      const _order = {
        id: order.id,
        orderNo: order.orderNo,
        parentOrderNo: order.orderNo,
        storeId: '',
        storeName: '',
        status: order.status,
        statusDesc: order.orderStatusName || order.statusDesc || '',
        amount: order.payAmount,
        totalAmount: order.totalAmount,
        logisticsNo: '',
        goodsList: (order.items || []).map((goods) => ({
          id: goods.id,
          thumb: goods.productImage,
          title: goods.productName,
          skuId: goods.skuId,
          spuId: goods.productId,
          specs: goods.skuName ? [goods.skuName] : [],
          price: goods.unitPrice,
          num: goods.quantity,
          titlePrefixTags: [],
          buttons: [],
        })),
        buttons: order.buttonVOs || [],
        createTime: order.createdAt,
        receiverAddress,
        groupInfoVo: null,
      };
      this.setData({
        order,
        _order,
        formatCreateTime: order.createdAt || '',
        countDownTime: null,
        addressEditable: false,
        isPaid: !!order.paidAt,
        logisticsNodes: [],
      });
    });
  },

  // 展开物流节点
  flattenNodes(nodes) {
    return (nodes || []).reduce((res, node) => {
      return (node.nodes || []).reduce((res1, subNode, index) => {
        res1.push({
          title: index === 0 ? node.title : '', // 子节点中仅第一个显示title
          desc: subNode.status,
          date: formatTime(+subNode.timestamp, 'YYYY-MM-DD HH:mm:ss'),
          icon: index === 0 ? LogisticsIconMap[node.code] || '' : '', // 子节点中仅第一个显示icon
        });
        return res1;
      }, res);
    }, []);
  },

  datermineInvoiceStatus(order) {
    // 1-已开票
    // 2-未开票（可补开）
    // 3-未开票
    // 4-门店不支持开票
    return order.invoiceStatus;
  },

  // 拼接省市区
  composeAddress(order) {
    const addr = order.address || {};
    return [addr.provinceName, addr.cityName, addr.districtName, addr.detailAddress]
      .filter(Boolean)
      .join(' ');
  },

  getStoreDetail() {
    fetchBusinessTime().then((res) => {
      const storeDetail = {
        storeTel: res.data.telphone,
        storeBusiness: res.data.businessTime.join('\n'),
      };
      this.setData({ storeDetail });
    });
  },

  // 仅对待支付状态计算付款倒计时
  // 返回时间若是大于2020.01.01，说明返回的是关闭时间，否则说明返回的直接就是剩余时间
  computeCountDownTime(order) {
    if (order.orderStatus !== OrderStatus.PENDING_PAYMENT) return null;
    return order.autoCancelTime > 1577808000000 ? order.autoCancelTime - Date.now() : order.autoCancelTime;
  },

  onCountDownFinish() {
    //this.setData({ countDownTime: -1 });
    const { countDownTime, order } = this.data;
    if (countDownTime > 0 || (order && order.groupInfoVo && order.groupInfoVo.residueTime > 0)) {
      this.onRefresh();
    }
  },

  onGoodsCardTap(e) {
    const { index } = e.currentTarget.dataset;
    const goods = (this.data.order.items || [])[index];
    if (goods) {
      wx.navigateTo({ url: `/pages/goods/details/index?spuId=${goods.productId}` });
    }
  },

  onPayOrder(e) {
    const { orderNo } = e.detail;
    wx.navigateTo({
      url: `/pages/order/order-confirm/index?orderNo=${orderNo}&isPay=1`,
    });
  },

  onEditAddressTap() {
    getAddressPromise()
      .then((address) => {
        this.setData({
          'order.logisticsVO.receiverName': address.name,
          'order.logisticsVO.receiverPhone': address.phone,
          '_order.receiverAddress': address.address,
        });
      })
      .catch(() => {});

    wx.navigateTo({
      url: `/pages/user/address/list/index?selectMode=1`,
    });
  },

  onOrderNumCopy() {
    wx.setClipboardData({
      data: this.data.order.orderNo,
    });
  },

  onDeliveryNumCopy() {
    wx.setClipboardData({
      data: this.data.order.logisticsVO.logisticsNo,
    });
  },

  onToInvoice() {
    wx.navigateTo({
      url: `/pages/order/invoice/index?orderNo=${this.data._order.orderNo}`,
    });
  },

  onSuppleMentInvoice() {
    wx.navigateTo({
      url: `/pages/order/receipt/index?orderNo=${this.data._order.orderNo}`,
    });
  },

  onDeliveryClick() {
    const logisticsData = {
      nodes: this.data.logisticsNodes,
      company: this.data.order.logisticsVO.logisticsCompanyName,
      logisticsNo: this.data.order.logisticsVO.logisticsNo,
      phoneNumber: this.data.order.logisticsVO.logisticsCompanyTel,
    };
    wx.navigateTo({
      url: `/pages/order/delivery-detail/index?data=${encodeURIComponent(JSON.stringify(logisticsData))}`,
    });
  },

  /** 跳转订单评价 */
  navToCommentCreate() {
    wx.navigateTo({
      url: `/pages/order/createComment/index?orderNo=${this.orderNo}`,
    });
  },

  /** 跳转拼团详情/分享页*/
  toGrouponDetail() {
    wx.showToast({ title: '点击了拼团' });
  },

  clickService() {
    Toast({
      context: this,
      selector: '#t-toast',
      message: '您点击了联系客服',
    });
  },

  onOrderInvoiceView() {
    wx.navigateTo({
      url: `/pages/order/invoice/index?orderNo=${this.orderNo}`,
    });
  },
});
