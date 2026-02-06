import Toast from 'tdesign-miniprogram/toast/index';
import { fetchGood } from '../../../services/good/fetchGood';
import { fetchActivityList } from '../../../services/activity/fetchActivityList';
import { fetchAvailableCoupons } from '../../../services/coupon/index';
import {
  getGoodsDetailsCommentList,
  getGoodsDetailsCommentsCount,
} from '../../../services/good/fetchGoodsDetailsComments';
import { ensureMiniProgramLogin, getStoredMemberProfile } from '../../../common/auth';

import { cdnBase } from '../../../config/index';

const imgPrefix = `${cdnBase}/`;

const recLeftImg = `${imgPrefix}common/rec-left.png`;
const recRightImg = `${imgPrefix}common/rec-right.png`;
const obj2Params = (obj = {}, encode = false) => {
  const result = [];
  Object.keys(obj).forEach((key) => result.push(`${key}=${encode ? encodeURIComponent(obj[key]) : obj[key]}`));

  return result.join('&');
};

const toCent = (price) => {
  const num = Number(price);
  if (!Number.isFinite(num)) return 0;
  return Math.round(num * 100);
};

const hashString = (input = '') => {
  let hash = 0;
  for (let i = 0; i < input.length; i++) {
    hash = (hash << 5) - hash + input.charCodeAt(i);
    hash |= 0;
  }
  return `00000000${(hash >>> 0).toString(16)}`.slice(-8);
};

const normalizeGalleryImages = (product = {}) => {
  let images = [];
  if (Array.isArray(product.gallery_images) && product.gallery_images.length > 0) {
    images = product.gallery_images.slice();
  } else if (Array.isArray(product.gallery)) {
    images = product.gallery
      .map((item) => item && (item.image_url || item.url || item.imageUrl))
      .filter((url) => typeof url === 'string' && url);
  }

  const primary = product.main_image || '';
  if (primary && !images.includes(primary)) {
    images.unshift(primary);
  }

  return images.filter((url) => typeof url === 'string' && url);
};

const extractImagesFromDetail = (detailContent) => {
  if (!detailContent || typeof detailContent !== 'string') return [];
  const regex = /<img[^>]+src=["']([^"']+)["']/gi;
  const urls = [];
  let match;
  while ((match = regex.exec(detailContent)) !== null) {
    if (match[1]) urls.push(match[1]);
  }
  return Array.from(new Set(urls));
};

const normalizeDescriptionImages = (product = {}) => {
  if (Array.isArray(product.gallery_images) && product.gallery_images.length > 0) {
    return product.gallery_images.slice();
  }
  const fromDetail = extractImagesFromDetail(product.detail_content);
  if (fromDetail.length > 0) return fromDetail;
  if (Array.isArray(product.gallery)) {
    return product.gallery
      .map((item) => item && (item.image_url || item.url || item.imageUrl))
      .filter((url) => typeof url === 'string' && url);
  }
  return [];
};

const normalizeSpecValues = (values) => {
  let list = values;
  if (!Array.isArray(list)) {
    if (typeof list === 'string' && list) {
      list = [list];
    } else {
      return [];
    }
  }

  return list.map((item, index) => {
    let title = `Spec ${index + 1}`;
    let realValue = item;
    let image = null;

    if (item && typeof item === 'object') {
      title = item.name || item.title || item.spec_title || title;
      realValue = item.value || item.spec_value || item.value_name || item.specValue || '';
      image = item.image || item.img || null;
    } else if (typeof item === 'string') {
      const parts = item.split(/[:：]/, 2);
      if (parts.length === 2) {
        title = parts[0].trim() || title;
        realValue = parts[1].trim();
      }
    }

    realValue = realValue == null ? '' : String(realValue);
    const specId = `spec_${index + 1}`;
    const valueId = `${specId}_${hashString(realValue)}`;

    return {
      specId,
      title: title || `Spec ${index + 1}`,
      valueId,
      value: realValue,
      image,
    };
  });
};

const buildSpecList = (skus = []) => {
  const specMap = {};
  skus.forEach((sku) => {
    const values = normalizeSpecValues(sku && sku.spec_values);
    values.forEach((value) => {
      const specId = value.specId;
      if (!specMap[specId]) {
        specMap[specId] = {
          specId,
          title: value.title,
          specValueList: {},
        };
      }
      specMap[specId].specValueList[value.valueId] = {
        specId,
        specTitle: specMap[specId].title,
        specValueId: value.valueId,
        specValue: value.value,
        image: value.image || null,
      };
    });
  });

  return Object.values(specMap).map((spec) => ({
    ...spec,
    specValueList: Object.values(spec.specValueList),
  }));
};

const buildSkuList = (skus = []) =>
  skus.map((sku) => {
    const values = normalizeSpecValues(sku && sku.spec_values);
    return {
      skuId: String((sku && (sku.id ?? sku.sku_id)) || ''),
      skuImage: sku && sku.image ? sku.image : null,
      specInfo: values.map((spec) => ({
        specId: spec.specId,
        specTitle: spec.title,
        specValueId: spec.valueId,
        specValue: spec.value,
      })),
      priceInfo: [
        { priceType: 1, price: toCent(sku && sku.sale_price) },
        { priceType: 2, price: toCent((sku && sku.market_price) || (sku && sku.sale_price)) },
      ],
      stockInfo: {
        stockQuantity: Number((sku && sku.stock) || 0),
        safeStockQuantity: Number((sku && sku.warning_stock) || 0),
        soldQuantity: Number((sku && sku.sold_quantity) || 0),
      },
      weight: {
        value: Number((sku && sku.weight) || 0),
        unit: 'KG',
      },
    };
  });

const sumSkuStock = (skus = []) =>
  skus.reduce((sum, sku) => sum + Number((sku && sku.stock) || 0), 0);

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
    const { isAllSelectedSku } = this.data;
    Toast({
      context: this,
      selector: '#t-toast',
      message: isAllSelectedSku ? '点击加入购物车' : '请选择规格',
      icon: '',
      duration: 1000,
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
    const resolvedStoreId = Number(product.store_id || product.storeId || 0);
    const quantity = Number(buyNum || 0);
    const resolvedSpuId = Number(spuId || product.id || product.spu_id || 0);
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
      storeName: product.store_name || product.storeName || '',
      spuId: resolvedSpuId || 0,
      goodsName: product.name || '',
      skuId: resolvedSkuId,
      available,
      price: selectItem.price || minSalePrice,
      specInfo: specInfoPayload,
      primaryImage: coverImage,
      thumb: coverImage,
      title: product.name || '',
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
      const skus = Array.isArray(product.skus) ? product.skus : [];
      const skuList = buildSkuList(skus);
      const specList = buildSpecList(skus);
      const images = normalizeGalleryImages(product);
      const desc = normalizeDescriptionImages(product);
      const limitInfo = [];
      const coverImage = product.main_image || images[0] || '';
      const stockQuantity = sumSkuStock(skus);
      const available = product.status === 'active' && stockQuantity > 0 ? 1 : 0;
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
        maxSalePrice: toCent(product.max_price),
        maxLinePrice: toCent(product.max_price),
        minSalePrice: toCent(product.min_price),
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
        soldout: product.status !== 'active',
        soldNum: Number(product.real_sales || 0) + Number(product.virtual_sales || 0),
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
        const nextState = {
          commentsList: homePageComments.map((item) => {
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
      title: (product.name || '') + shareSubTitle,
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
