import Toast from 'tdesign-miniprogram/toast/index';
import { fetchGood } from '../../../services/good/fetchGood';
import { addCartItem } from '../../../services/cart/cart';
import {
  getGoodsDetailsCommentList,
  getGoodsDetailsCommentsCount,
} from '../../../services/good/fetchGoodsDetailsComments';
import { cdnBase } from '../../../config/index';

const imgPrefix = `${cdnBase}/`;
const recLeftImg = `${imgPrefix}common/rec-left.png`;
const recRightImg = `${imgPrefix}common/rec-right.png`;

const obj2Params = (obj = {}, encode = false) => {
  const result = [];
  Object.keys(obj).forEach((key) => result.push(`${key}=${encode ? encodeURIComponent(obj[key]) : obj[key]}`));
  return result.join('&');
};

Page({
  data: {
    commentsList: [],
    commentsStatistics: { badCount: 0, commentCount: 0, goodCount: 0, goodRate: 0, hasImageCount: 0, middleCount: 0 },
    isShowPromotionPop: false,
    activityList: [],
    recLeftImg,
    recRightImg,
    details: {},
    storeName: '云mall标准版旗舰店',
    jumpArray: [
      { title: '首页', url: '/pages/home/home', iconName: 'home' },
      { title: '购物车', url: '/pages/cart/index', iconName: 'cart', showCartNum: true },
    ],
    isStock: true,
    cartNum: 0,
    soldout: false,
    buttonType: 1,
    buyNum: 1,
    selectedAttrStr: '',
    skuArray: [],
    primaryImage: '',
    specImg: '',
    isSpuSelectPopupShow: false,
    isAllSelectedSku: false,
    buyType: 0,
    outOperateStatus: false,
    selectSkuSellsPrice: 0,
    maxLinePrice: 0,
    minSalePrice: 0,
    maxSalePrice: 0,
    list: [],
    spuId: '',
    navigation: { type: 'fraction' },
    current: 0,
    autoplay: true,
    duration: 500,
    interval: 5000,
    soldNum: 0,
    // 秒杀/拼团相关
    orderType: '', // 'seckill' | 'group_buy' | ''
    activityId: '',
    sessionId: '',
    activityInfo: null,
  },

  selectItem: null,

  handlePopupHide() { this.setData({ isSpuSelectPopupShow: false }); },

  showSkuSelectPopup(type) {
    this.setData({ buyType: type || 0, outOperateStatus: type >= 1, isSpuSelectPopupShow: true });
  },

  buyItNow() { this.showSkuSelectPopup(1); },
  toAddCart() { this.showSkuSelectPopup(2); },

  toNav(e) { wx.switchTab({ url: e.detail.url }); },

  showCurImg(e) {
    const { index } = e.detail;
    const { images } = this.data.details;
    wx.previewImage({ current: images[index], urls: images });
  },

  chooseSpecItem(e) {
    const { specList } = this.data.details;
    const { selectedSku, isAllSelectedSku } = e.detail;
    if (!isAllSelectedSku) this.setData({ selectSkuSellsPrice: 0 });
    this.setData({ isAllSelectedSku });
    this.getSkuItem(specList, selectedSku);
  },

  getSkuItem(specList, selectedSku) {
    const { skuArray, primaryImage } = this.data;
    const selectedSkuValues = this.getSelectedSkuValues(specList, selectedSku);
    let selectedAttrStr = ` 件  `;
    selectedSkuValues.forEach((item) => { selectedAttrStr += `，${item.specValue}  `; });

    const skuItem = skuArray.filter((item) => {
      let status = true;
      (item.specInfo || []).forEach((subItem) => {
        if (!selectedSku[subItem.specId] || selectedSku[subItem.specId] !== subItem.specValueId) status = false;
      });
      if (status) return item;
    });

    this.selectSpecsName(selectedSkuValues.length > 0 ? selectedAttrStr : '');
    if (skuItem && skuItem.length > 0) {
      this.selectItem = skuItem[0];
      this.setData({ selectSkuSellsPrice: skuItem[0].price || 0 });
    } else {
      this.selectItem = null;
      this.setData({ selectSkuSellsPrice: 0 });
    }
    this.setData({ specImg: (skuItem && skuItem[0] && skuItem[0].skuImage) ? skuItem[0].skuImage : primaryImage });
  },

  getSelectedSkuValues(skuTree, selectedSku) {
    const normalizedTree = {};
    skuTree.forEach((treeItem) => { normalizedTree[treeItem.specId] = treeItem.specValueList; });
    return Object.keys(selectedSku).reduce((selectedValues, skuKeyStr) => {
      const skuValues = normalizedTree[skuKeyStr];
      const skuValueId = selectedSku[skuKeyStr];
      if (skuValueId !== '') {
        const skuValue = skuValues.filter((v) => v.specValueId === skuValueId)[0];
        skuValue && selectedValues.push(skuValue);
      }
      return selectedValues;
    }, []);
  },

  selectSpecsName(name) { this.setData({ selectedAttrStr: name || '' }); },

  addCart() {
    const { isAllSelectedSku } = this.data;
    if (!isAllSelectedSku) {
      Toast({ context: this, selector: '#t-toast', message: '请选择规格', duration: 1000 });
      return;
    }
    const skuId = this.selectItem?.skuId;
    if (!skuId) {
      Toast({ context: this, selector: '#t-toast', message: '请选择规格', duration: 1000 });
      return;
    }
    addCartItem({ skuId: Number(skuId), quantity: this.data.buyNum })
      .then(() => {
        Toast({ context: this, selector: '#t-toast', message: '已加入购物车', icon: 'check-circle', duration: 1000 });
        this.handlePopupHide();
      })
      .catch((err) => {
        Toast({ context: this, selector: '#t-toast', message: err.msg || '加入购物车失败', duration: 1000 });
      });
  },

  gotoBuy() {
    const { isAllSelectedSku, buyNum, details, orderType, activityId, sessionId } = this.data;
    if (!isAllSelectedSku) {
      Toast({ context: this, selector: '#t-toast', message: '请选择规格', duration: 1000 });
      return;
    }
    this.handlePopupHide();

    const skuId = this.selectItem?.skuId || (details.skuList && details.skuList[0]?.skuId);
    const query = {
      quantity: buyNum,
      storeId: '1',
      spuId: details.spuId,
      goodsName: details.title,
      skuId: skuId,
      available: details.available,
      price: this.data.selectSkuSellsPrice || details.minSalePrice,
      specInfo: this.selectItem?.specInfo || [],
      primaryImage: details.primaryImage,
      thumb: details.primaryImage,
      title: details.title,
    };

    // 秒杀/拼团订单附加参数
    if (orderType === 'seckill') {
      query.orderType = 'seckill';
      query.activityId = activityId;
      query.sessionId = sessionId;
    } else if (orderType === 'group_buy') {
      query.orderType = 'group_buy';
      query.activityId = activityId;
    }

    const urlQueryStr = obj2Params({ goodsRequestList: JSON.stringify([query]) });
    wx.navigateTo({ url: `/pages/order/order-confirm/index?${urlQueryStr}` });
  },

  specsConfirm() {
    if (this.data.buyType === 1) this.gotoBuy();
    else this.addCart();
  },

  changeNum(e) { this.setData({ buyNum: e.detail.buyNum }); },

  closePromotionPopup() { this.setData({ isShowPromotionPop: false }); },
  showPromotionPopup() { this.setData({ isShowPromotionPop: true }); },

  promotionChange(e) {
    const { index } = e.detail;
    wx.navigateTo({ url: `/pages/promotion/promotion-detail/index?promotion_id=${index}` });
  },

  getDetail(spuId) {
    const { orderType, activityId, sessionId } = this.data;
    let fetchPromise;

    if (orderType === 'seckill' && sessionId) {
      // 秒杀商品详情
      const { request } = require('../../../services/request');
      fetchPromise = request({ url: `/api/v1/seckill/products/${sessionId}/${spuId}`, method: 'GET' });
    } else if (orderType === 'group_buy' && activityId) {
      // 拼团商品详情
      const { request } = require('../../../services/request');
      fetchPromise = request({ url: `/api/v1/group-buy/products/${activityId}/${spuId}`, method: 'GET' });
    } else {
      fetchPromise = fetchGood(spuId);
    }

    fetchPromise.then((details) => {
      const skuArray = [];
      const { skuList, primaryImage, isPutOnSale, minSalePrice, maxSalePrice, maxLinePrice, soldNum, activityInfo } = details;
      (skuList || []).forEach((item) => {
        skuArray.push({
          skuId: item.skuId,
          quantity: item.stockInfo ? item.stockInfo.stockQuantity : 0,
          specInfo: item.specInfo,
          price: item.priceInfo?.[0]?.price || item.price || 0,
          skuImage: item.skuImage || '',
        });
      });

      this.setData({
        details,
        isStock: details.spuStockQuantity > 0,
        maxSalePrice: maxSalePrice ? parseInt(maxSalePrice) : 0,
        maxLinePrice: maxLinePrice ? parseInt(maxLinePrice) : 0,
        minSalePrice: minSalePrice ? parseInt(minSalePrice) : 0,
        skuArray,
        primaryImage,
        soldout: isPutOnSale === 0,
        soldNum: soldNum || 0,
        activityInfo: activityInfo || null,
        list: [],
      });
    }).catch((err) => {
      console.error('getDetail error:', err);
      Toast({ context: this, selector: '#t-toast', message: '商品加载失败', duration: 2000 });
    });
  },

  async getCommentsList() {
    try {
      const data = await getGoodsDetailsCommentList();
      const { homePageComments } = data;
      this.setData({
        commentsList: (homePageComments || []).map((item) => ({
          goodsSpu: item.spuId,
          userName: item.userName || '',
          commentScore: item.commentScore,
          commentContent: item.commentContent || '用户未填写评价',
          userHeadUrl: item.userHeadUrl || '',
        })),
      });
    } catch (error) { console.error('comments error:', error); }
  },

  async getCommentsStatistics() {
    try {
      const data = await getGoodsDetailsCommentsCount();
      const { badCount, commentCount, goodCount, goodRate, hasImageCount, middleCount } = data;
      this.setData({
        commentsStatistics: {
          badCount: parseInt(`${badCount}`), commentCount: parseInt(`${commentCount}`),
          goodCount: parseInt(`${goodCount}`), goodRate: Math.floor(goodRate * 10) / 10,
          hasImageCount: parseInt(`${hasImageCount}`), middleCount: parseInt(`${middleCount}`),
        },
      });
    } catch (error) { console.error('comments statistics error:', error); }
  },

  navToCommentsListPage() {
    wx.navigateTo({ url: `/pages/goods/comments/index?spuId=${this.data.spuId}` });
  },

  onShareAppMessage() {
    return {
      imageUrl: this.data.details.primaryImage,
      title: this.data.details.title,
      path: `/pages/goods/details/index?spuId=${this.data.spuId}`,
    };
  },

  onLoad(query) {
    const { spuId, orderType, activityId, sessionId } = query;
    this.setData({
      spuId,
      orderType: orderType || '',
      activityId: activityId || '',
      sessionId: sessionId || '',
    });
    this.getDetail(spuId);
    this.getCommentsList(spuId);
    this.getCommentsStatistics(spuId);
  },
});
