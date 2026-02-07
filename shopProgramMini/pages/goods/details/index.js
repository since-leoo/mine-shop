import Toast from 'tdesign-miniprogram/toast/index';
import { fetchGood } from '../../../services/good/fetchGood';
import { fetchActivityList } from '../../../services/activity/fetchActivityList';
import { fetchAvailableCoupons, receiveCoupon } from '../../../services/coupon/index';
import {
  getGoodsDetailsCommentList,
  getGoodsDetailsCommentsCount,
} from '../../../services/good/fetchGoodsDetailsComments';
import { ensureMiniProgramLogin, getStoredMemberProfile } from '../../../common/auth';
import { addCartItem } from '../../../services/cart/cart';

import { cdnBase } from '../../../config/index';

const imgPrefix = `${cdnBase}/`;

const recLeftImg = `${imgPrefix}common/rec-left.png`;
const recRightImg = `${imgPrefix}common/rec-right.png`;
const obj2Params = (obj = {}, encode = false) => {
  const result = [];
  Object.keys(obj).forEach((key) => result.push(`${key}=${encode ? encodeURIComponent(obj[key]) : obj[key]}`));

  return result.join('&');
};

const buildActivityPromotions = (activities = []) =>
  activities.map((item = {}) => ({
    tag: item.tag || (item.promotionSubCode === 'MYJ' ? '满减' : '满折'),
    label: item.label || '满100元减99.9元',
    promotion_id: item.promotionId || item.id || '',
    coupon_id: '',
  }));

Page({
  data: {
    commentsList: [],
    commentsStatistics: {
      badCount: 0,
      commentCount: 0,
      goodCount: 0,
      goodRate: 0,
      hasImageCount: 0,
      middleCount: 0,
    },
    isShowPromotionPop: false,
    activityList: [],
    recLeftImg,
    recRightImg,
    product: {},
    images: [],
    desc: [],
    specList: [],
    limitInfo: [],
    goodsTabArray: [
      {
        name: '商品',
        value: '', // 空字符串代表置顶
      },
      {
        name: '详情',
        value: 'goods-page',
      },
    ],
    storeLogo: `${imgPrefix}common/store-logo.png`,
    storeName: '云mall标准版旗舰店',
    jumpArray: [
      {
        title: '首页',
        url: '/pages/home/home',
        iconName: 'home',
      },
      {
        title: '购物车',
        url: '/pages/cart/index',
        iconName: 'cart',
        showCartNum: true,
      },
    ],
    isStock: true,
    cartNum: 0,
    soldout: false,
    buttonType: 1,
    buyNum: 1,
    selectedAttrStr: '',
    skuArray: [],
    skuList: [],
    primaryImage: '',
    specImg: '',
    selectItem: null,
    selectedSkuValues: [],
    isSpuSelectPopupShow: false,
    isAllSelectedSku: false,
    buyType: 0,
    outOperateStatus: false, // 是否外层加入购物车
    operateType: 0,
    selectSkuSellsPrice: 0,
    maxLinePrice: 0,
    minSalePrice: 0,
    maxSalePrice: 0,
    list: [],
    couponList: [],
    couponTotal: 0,
    spuId: '',
    navigation: { type: 'fraction' },
    current: 0,
    autoplay: true,
    duration: 500,
    interval: 5000,
    soldNum: 0, // 已售数量
  },

  handlePopupHide() {
    this.setData({
      isSpuSelectPopupShow: false,
    });
  },

  showSkuSelectPopup(type) {
    this.setData({
      buyType: type || 0,
      outOperateStatus: type >= 1,
      isSpuSelectPopupShow: true,
    });
  },

  buyItNow() {
    this.showSkuSelectPopup(1);
  },

  toAddCart() {
    this.showSkuSelectPopup(2);
  },

  toNav(e) {
    const { url } = e.detail;
    wx.switchTab({
      url: url,
    });
  },

  showCurImg(e) {
    const { index } = e.detail;
    const { images } = this.data;
    wx.previewImage({
      current: images[index],
      urls: images, // 需要预览的图片http链接列表
    });
  },

  onPageScroll({ scrollTop }) {
    const goodsTab = this.selectComponent('#goodsTab');
    goodsTab && goodsTab.onScroll(scrollTop);
  },

  chooseSpecItem(e) {
    const { specList } = this.data;
    const { selectedSku, isAllSelectedSku } = e.detail;
    if (!isAllSelectedSku) {
      this.setData({
        selectSkuSellsPrice: 0,
      });
    }
    this.setData({
      isAllSelectedSku,
    });
    this.getSkuItem(specList, selectedSku);
  },

  getSkuItem(specList, selectedSku) {
    const { skuArray, primaryImage } = this.data;
    const selectedSkuValues = this.getSelectedSkuValues(specList, selectedSku);
    let selectedAttrStr = '';
    if (selectedSkuValues.length > 0) {
      selectedAttrStr = ' 件  ';
      selectedSkuValues.forEach((item) => {
        selectedAttrStr += `，${item.specValue}  `;
      });
    }
    const skuItem =
      skuArray.find((item) => {
        const specInfo = item.specInfo || [];
        if (specInfo.length === 0) {
          return false;
        }
        return specInfo.every((subItem) => {
          const selectedValue = selectedSku[subItem.specId];
          return !!selectedValue && selectedValue === subItem.specValueId;
        });
      }) || null;
    this.selectSpecsName(selectedSkuValues.length > 0 ? selectedAttrStr : '');
    this.setData({
      selectItem: skuItem,
      selectSkuSellsPrice: skuItem ? skuItem.price || 0 : 0,
      specImg: skuItem && skuItem.skuImage ? skuItem.skuImage : primaryImage,
      selectedSkuValues,
    });
  },

  // 获取已选择的sku名称
  getSelectedSkuValues(skuTree, selectedSku) {
    const normalizedTree = this.normalizeSkuTree(skuTree);
    return Object.keys(selectedSku).reduce((selectedValues, skuKeyStr) => {
      const skuValues = normalizedTree[skuKeyStr];
      const skuValueId = selectedSku[skuKeyStr];
      if (skuValueId !== '') {
        const skuValue = skuValues.filter((value) => {
          return value.specValueId === skuValueId;
        })[0];
        skuValue && selectedValues.push(skuValue);
      }
      return selectedValues;
    }, []);
  },

  normalizeSkuTree(skuTree) {
    const normalizedTree = {};
    skuTree.forEach((treeItem) => {
      normalizedTree[treeItem.specId] = treeItem.specValueList;
    });
    return normalizedTree;
  },

  selectSpecsName(selectSpecsName) {
    if (selectSpecsName) {
      this.setData({
        selectedAttrStr: selectSpecsName,
      });
    } else {
      this.setData({
        selectedAttrStr: '',
      });
    }
  },

  addCart() {
    const { isAllSelectedSku, selectItem, buyNum } = this.data;
    if (!isAllSelectedSku || !selectItem) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '请选择规格',
        icon: '',
        duration: 1000,
      });
      return;
    }
    const skuId = Number(selectItem.skuId || 0);
    const quantity = Number(buyNum || 1);
    if (skuId <= 0) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '请选择规格',
        icon: '',
        duration: 1000,
      });
      return;
    }
    addCartItem({ skuId, quantity })
      .then(() => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '已加入购物车',
          icon: 'check-circle',
          duration: 1500,
        });
        this.handlePopupHide();
      })
      .catch((err) => {
        Toast({
          context: this,
          selector: '#t-toast',
          message: err?.msg || '加入购物车失败',
          theme: 'error',
          duration: 1500,
        });
      });
  },

  async gotoBuy(event) {
    const { buyNum, selectItem, selectedSkuValues, product, available, minSalePrice, primaryImage, spuId } = this.data;
    const shouldProceed =
      typeof event?.detail?.isAllSelectedSku === 'boolean' ? event.detail.isAllSelectedSku : this.data.isAllSelectedSku;
    const resolvedSkuId = Number(selectItem?.skuId || 0);
    if (!shouldProceed || !selectItem || resolvedSkuId <= 0) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '请选择规格',
        icon: '',
        duration: 1000,
      });
      return;
    }
    const loginReady = await this.ensureOrderAuth();
    if (!loginReady) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '请先完成登录',
        duration: 1500,
        icon: 'help-circle',
      });
      return;
    }

    this.handlePopupHide();
    const coverImage = selectItem.skuImage || primaryImage;
    const specInfoPayload =
      selectedSkuValues && selectedSkuValues.length > 0
        ? selectedSkuValues.map((item) => ({
            specId: item.specId,
            specTitle: item.specTitle || item.title || '',
            specValueId: item.specValueId,
            specValue: item.specValue,
          }))
        : [];
    const resolvedStoreId = Number(product.storeId || 0);
    const quantity = Number(buyNum || 0);
    const resolvedSpuId = Number(spuId || product.spuId || 0);
    if (quantity <= 0) {
      Toast({
        context: this,
        selector: '#t-toast',
        message: '请选择数量',
        icon: '',
        duration: 1000,
      });
      return;
    }
    const query = {
      quantity,
      storeId: resolvedStoreId || 0,
      storeName: product.storeName || '',
      spuId: resolvedSpuId || 0,
      goodsName: product.title || '',
      skuId: resolvedSkuId,
      available,
      price: selectItem.price || minSalePrice,
      specInfo: specInfoPayload,
      primaryImage: coverImage,
      thumb: coverImage,
      title: product.title || '',
    };
    let urlQueryStr = obj2Params(
      {
        goodsRequestList: JSON.stringify([query]),
      },
      true,
    );
    urlQueryStr = urlQueryStr ? `?${urlQueryStr}` : '';
    const path = `/pages/order/order-confirm/index${urlQueryStr}`;
    wx.navigateTo({
      url: path,
    });
  },

  specsConfirm() {
    const { buyType } = this.data;
    if (buyType === 1) {
      this.gotoBuy();
    } else {
      this.addCart();
    }
    // this.handlePopupHide();
  },

  changeNum(e) {
    this.setData({
      buyNum: e.detail.buyNum,
    });
  },

  async ensureOrderAuth() {
    try {
      const profile = getStoredMemberProfile();
      await ensureMiniProgramLogin({ openid: profile?.openid || '' });
      return true;
    } catch (error) {
      console.warn('ensure order auth failed', error);
      return false;
    }
  },

  closePromotionPopup() {
    this.setData({
      isShowPromotionPop: false,
    });
  },

  promotionChange(e) {
    const { couponId, promotionId, index } = e.detail || {};
    if (couponId) {
      wx.navigateTo({
        url: `/pages/coupon/coupon-detail/index?id=${couponId}`,
      });
      return;
    }
    const targetId = promotionId || index || '';
    wx.navigateTo({
      url: `/pages/promotion/promotion-detail/index?promotion_id=${targetId}`,
    });
  },

  showPromotionPopup() {
    this.setData({
      isShowPromotionPop: true,
    });
  },

  async receiveCoupon(e) {
    const { couponId, index } = e.detail;
    
    try {
      // 检查登录状态
      const loginReady = await this.ensureOrderAuth();
      if (!loginReady) {
        Toast({
          context: this,
          selector: '#t-toast',
          message: '请先完成登录',
          duration: 1500,
          icon: 'help-circle',
        });
        return;
      }

      // 调用领取优惠券 API
      await receiveCoupon(couponId);
      
      Toast({
        context: this,
        selector: '#t-toast',
        message: '领取成功',
        icon: 'check-circle',
        duration: 1500,
      });

      // 领取成功后更新列表状态
      const { list, activityList } = this.data;
      if (list[index]) {
        list[index].is_receivable = false;
        this.setData({ list });
      }
      if (activityList[index]) {
        activityList[index].is_receivable = false;
        this.setData({ activityList });
      }
    } catch (error) {
      console.error('领取优惠券失败:', error);
      Toast({
        context: this,
        selector: '#t-toast',
        message: error?.message || error?.msg || '领取失败',
        theme: 'error',
        duration: 1500,
      });
    }
  },

  async getDetail(spuId) {
    if (!spuId) {
      wx.showToast({
        title: '商品不存在',
        icon: 'none',
      });
      return;
    }

    try {
      const [detailsResponse, activityResponse, couponResponse] = await Promise.all([
        fetchGood(spuId),
        fetchActivityList().catch(() => []),
        fetchAvailableCoupons({ spuId, limit: 10 }).catch(() => ({ list: [], total: 0 })),
      ]);

      const product = detailsResponse || {};
      
      // 适配新的 API 返回格式（camelCase）
      const skuList = Array.isArray(product.skuList) ? product.skuList : [];
      const specList = Array.isArray(product.specList) ? product.specList : [];
      const images = Array.isArray(product.images) ? product.images : [];
      const desc = Array.isArray(product.desc) ? product.desc : [];
      const limitInfo = Array.isArray(product.limitInfo) ? product.limitInfo : [];
      const coverImage = product.primaryImage || images[0] || '';
      const stockQuantity = Number(product.spuStockQuantity || 0);
      const available = Number(product.available || 0);
      
      const couponList =
        couponResponse && Array.isArray(couponResponse.list) ? couponResponse.list : [];
      const couponTotal =
        couponResponse && typeof couponResponse.total === 'number'
          ? couponResponse.total
          : couponList.length;
      const rawActivityList = Array.isArray(activityResponse) ? activityResponse : [];
      const activityPromotions = buildActivityPromotions(rawActivityList);
      const promotionArray = [...couponList, ...activityPromotions];

      const skuArray = skuList.map((item) => ({
        skuId: item.skuId,
        quantity: item.stockInfo ? item.stockInfo.stockQuantity : 0,
        specInfo: item.specInfo || [],
        skuImage: item.skuImage || null,
        price: item.priceInfo && item.priceInfo[0] ? item.priceInfo[0].price : 0,
      }));

      this.setData({
        product,
        images,
        desc,
        specList,
        limitInfo,
        activityList: promotionArray,
        isStock: stockQuantity > 0,
        maxSalePrice: Number(product.maxSalePrice || 0),
        maxLinePrice: Number(product.maxLinePrice || 0),
        minSalePrice: Number(product.minSalePrice || 0),
        list: promotionArray,
        couponList,
        couponTotal,
        skuArray,
        skuList,
        primaryImage: coverImage,
        specImg: coverImage,
        selectItem: null,
        selectedSkuValues: [],
        selectedAttrStr: '',
        isAllSelectedSku: false,
        buyNum: 1,
        soldout: !available,
        soldNum: Number(product.soldNum || 0),
        available,
      });
    } catch (error) {
      console.error('get detail error:', error);
      wx.showToast({
        title: '商品详情加载失败',
        icon: 'none',
      });
    }
  },

  async getCommentsList() {
    try {
      const code = 'Success';
      const data = await getGoodsDetailsCommentList();
      const { homePageComments } = data;
      if (code.toUpperCase() === 'SUCCESS') {
        const comments = Array.isArray(homePageComments) ? homePageComments : [];
        const nextState = {
          commentsList: comments.map((item) => {
            return {
              goodsSpu: item.spuId,
              userName: item.userName || '',
              commentScore: item.commentScore,
              commentContent: item.commentContent || '用户未填写评价',
              userHeadUrl: item.isAnonymity ? this.anonymityAvatar : item.userHeadUrl || this.anonymityAvatar,
            };
          }),
        };
        this.setData(nextState);
      }
    } catch (error) {
      console.error('comments error:', error);
    }
  },

  onShareAppMessage() {
    // 自定义的返回信息
    const { selectedAttrStr, product } = this.data;
    let shareSubTitle = '';
    if (selectedAttrStr.indexOf('件') > -1) {
      const count = selectedAttrStr.indexOf('件');
      shareSubTitle = selectedAttrStr.slice(count + 1, selectedAttrStr.length);
    }
    const customInfo = {
      imageUrl: this.data.primaryImage,
      title: (product.title || '') + shareSubTitle,
      path: `/pages/goods/details/index?spuId=${this.data.spuId}`,
    };
    return customInfo;
  },

  /** 获取评价统计 */
  async getCommentsStatistics() {
    try {
      const code = 'Success';
      const data = await getGoodsDetailsCommentsCount();
      if (code.toUpperCase() === 'SUCCESS') {
        const { badCount, commentCount, goodCount, goodRate, hasImageCount, middleCount } = data;
        const nextState = {
          commentsStatistics: {
            badCount: parseInt(`${badCount}`),
            commentCount: parseInt(`${commentCount}`),
            goodCount: parseInt(`${goodCount}`),
            /** 后端返回百分比后数据但没有限制位数 */
            goodRate: Math.floor(goodRate * 10) / 10,
            hasImageCount: parseInt(`${hasImageCount}`),
            middleCount: parseInt(`${middleCount}`),
          },
        };
        this.setData(nextState);
      }
    } catch (error) {
      console.error('comments statiistics error:', error);
    }
  },

  /** 跳转到评价列表 */
  navToCommentsListPage() {
    wx.navigateTo({
      url: `/pages/goods/comments/index?spuId=${this.data.spuId}`,
    });
  },

  onLoad(query = {}) {
    const { spuId } = query;
    if (!spuId) {
      wx.showToast({
        title: '缺少商品ID',
        icon: 'none',
      });
      return;
    }
    this.setData({
      spuId,
    });
    this.getDetail(spuId);
    this.getCommentsList(spuId);
    this.getCommentsStatistics(spuId);
  },
});
