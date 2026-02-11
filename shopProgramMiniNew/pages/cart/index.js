import Dialog from 'tdesign-miniprogram/dialog/index';
import Toast from 'tdesign-miniprogram/toast/index';
import { fetchCartGroupData, updateCartItem, deleteCartItem, clearInvalidCartItems } from '../../services/cart/cart';

Page({
  data: {
    cartGroupData: null,
  },

  onShow() {
    this.getTabBar().init();
    this.refreshData();
  },

  onLoad() {
    this.refreshData();
  },

  refreshData() {
    fetchCartGroupData().then((res) => {
      let isEmpty = true;
      const cartGroupData = res.data;
      for (const store of (cartGroupData.storeGoods || [])) {
        store.isSelected = true;
        store.storeStockShortage = false;
        if (!store.shortageGoodsList) store.shortageGoodsList = [];
        for (const activity of (store.promotionGoodsList || [])) {
          activity.goodsPromotionList = (activity.goodsPromotionList || []).filter((goods) => {
            goods.originPrice = undefined;
            if (goods.quantity > goods.stockQuantity) store.storeStockShortage = true;
            if (!goods.isSelected) store.isSelected = false;
            if (goods.stockQuantity > 0) return true;
            store.shortageGoodsList.push(goods);
            return false;
          });
          if (activity.goodsPromotionList.length > 0) isEmpty = false;
        }
        if (store.shortageGoodsList.length > 0) isEmpty = false;
      }
      (cartGroupData.invalidGoodItems || []).forEach((goods) => { goods.originPrice = undefined; });
      cartGroupData.isNotEmpty = !isEmpty;
      this.setData({ cartGroupData });
    });
  },

  findGoods(spuId, skuId) {
    let currentStore, currentActivity, currentGoods;
    const { storeGoods } = this.data.cartGroupData || {};
    for (const store of (storeGoods || [])) {
      for (const activity of (store.promotionGoodsList || [])) {
        for (const goods of (activity.goodsPromotionList || [])) {
          if (goods.spuId === spuId && goods.skuId === skuId) {
            return { currentStore: store, currentActivity: activity, currentGoods: goods };
          }
        }
      }
    }
    return { currentStore, currentActivity, currentGoods };
  },

  onGoodsSelect(e) {
    const { goods: { spuId, skuId }, isSelected } = e.detail;
    const { currentGoods } = this.findGoods(spuId, skuId);
    if (currentGoods) currentGoods.isSelected = isSelected;
    this.refreshData();
  },

  onStoreSelect(e) {
    const { store: { storeId }, isSelected } = e.detail;
    const currentStore = (this.data.cartGroupData?.storeGoods || []).find((s) => s.storeId === storeId);
    if (currentStore) {
      currentStore.isSelected = isSelected;
      (currentStore.promotionGoodsList || []).forEach((activity) => {
        (activity.goodsPromotionList || []).forEach((goods) => { goods.isSelected = isSelected; });
      });
    }
    this.refreshData();
  },

  onQuantityChange(e) {
    const { goods: { spuId, skuId }, quantity } = e.detail;
    const { currentGoods } = this.findGoods(spuId, skuId);
    if (!currentGoods) return;
    const stockQuantity = Math.max(0, currentGoods.stockQuantity || 0);
    if (quantity > stockQuantity) {
      if (currentGoods.quantity === stockQuantity) {
        Toast({ context: this, selector: '#t-toast', message: '当前商品库存不足' });
        return;
      }
      Dialog.confirm({
        title: '商品库存不足',
        content: `最大可购买数量为${stockQuantity}件`,
        confirmBtn: '修改为最大数量',
        cancelBtn: '取消',
      }).then(() => {
        updateCartItem(skuId, { quantity: stockQuantity }).then(() => this.refreshData());
      });
      return;
    }
    updateCartItem(skuId, { quantity }).then(() => this.refreshData());
  },

  goCollect() {
    wx.navigateTo({ url: '/pages/promotion/promotion-detail/index?promotion_id=0' });
  },

  goGoodsDetail(e) {
    const { spuId } = e.detail.goods;
    wx.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  },

  clearInvalidGoods() {
    clearInvalidCartItems().then(() => this.refreshData());
  },

  onGoodsDelete(e) {
    const { goods: { spuId, skuId } } = e.detail;
    Dialog.confirm({ content: '确认删除该商品吗?', confirmBtn: '确定', cancelBtn: '取消' }).then(() => {
      deleteCartItem(skuId).then(() => {
        Toast({ context: this, selector: '#t-toast', message: '商品删除成功' });
        this.refreshData();
      });
    });
  },

  onSelectAll(event) {
    const { isAllSelected } = event?.detail ?? {};
    Toast({ context: this, selector: '#t-toast', message: `${isAllSelected ? '取消' : '点击'}了全选按钮` });
  },

  onToSettle() {
    const goodsRequestList = [];
    (this.data.cartGroupData?.storeGoods || []).forEach((store) => {
      (store.promotionGoodsList || []).forEach((promotion) => {
        (promotion.goodsPromotionList || []).forEach((m) => {
          if (m.isSelected == 1) goodsRequestList.push(m);
        });
      });
    });
    if (goodsRequestList.length === 0) {
      Toast({ context: this, selector: '#t-toast', message: '请选择商品' });
      return;
    }
    wx.setStorageSync('order.goodsRequestList', JSON.stringify(goodsRequestList));
    wx.navigateTo({ url: '/pages/order/order-confirm/index?type=cart' });
  },

  onGotoHome() { wx.switchTab({ url: '/pages/home/home' }); },
});
