import Dialog from 'tdesign-miniprogram/dialog/index';
import Toast from 'tdesign-miniprogram/toast/index';
import {
  fetchCartGroupData,
  updateCartItem,
  deleteCartItem,
  clearInvalidCartItems,
} from '../../services/cart/cart';

Page({
  data: {
    cartGroupData: null,
  },

  onShow() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar && typeof tabBar.init === 'function') {
      tabBar.init();
    }
    this.refreshData();
  },

  onPullDownRefresh() {
    this.refreshData().finally(() => wx.stopPullDownRefresh());
  },

  refreshData() {
    return fetchCartGroupData().then((res) => {
      const cartGroupData = res.data;
      this.processCartData(cartGroupData);
      this.setData({ cartGroupData });
    });
  },

  /** 从服务端返回数据重新渲染 */
  applyServerData(res) {
    if (res && res.data && res.data.storeGoods) {
      const cartGroupData = res.data;
      this.processCartData(cartGroupData);
      this.setData({ cartGroupData });
    } else {
      this.refreshData();
    }
  },

  processCartData(cartGroupData) {
    let isEmpty = true;
    for (const store of cartGroupData.storeGoods) {
      store.isSelected = true;
      store.storeStockShortage = false;
      if (!store.shortageGoodsList) {
        store.shortageGoodsList = [];
      }
      for (const activity of store.promotionGoodsList) {
        activity.goodsPromotionList = activity.goodsPromotionList.filter((goods) => {
          goods.originPrice = undefined;
          if (goods.quantity > goods.stockQuantity) {
            store.storeStockShortage = true;
          }
          if (!goods.isSelected) {
            store.isSelected = false;
          }
          if (goods.stockQuantity > 0) {
            return true;
          }
          store.shortageGoodsList.push(goods);
          return false;
        });
        if (activity.goodsPromotionList.length > 0) {
          isEmpty = false;
        }
      }
      if (store.shortageGoodsList.length > 0) {
        isEmpty = false;
      }
    }
    cartGroupData.invalidGoodItems = cartGroupData.invalidGoodItems.map((goods) => {
      goods.originPrice = undefined;
      return goods;
    });
    cartGroupData.isNotEmpty = !isEmpty;
  },

  findGoods(spuId, skuId) {
    let currentStore;
    let currentActivity;
    let currentGoods;
    const { storeGoods } = this.data.cartGroupData;
    for (const store of storeGoods) {
      for (const activity of store.promotionGoodsList) {
        for (const goods of activity.goodsPromotionList) {
          if (goods.spuId === spuId && goods.skuId === skuId) {
            currentStore = store;
            currentActivity = activity;
            currentGoods = goods;
            return { currentStore, currentActivity, currentGoods };
          }
        }
      }
    }
    return { currentStore, currentActivity, currentGoods };
  },

  // 选择单个商品
  onGoodsSelect(e) {
    const {
      goods: { spuId, skuId },
      isSelected,
    } = e.detail;
    const { currentGoods } = this.findGoods(spuId, skuId);
    if (!currentGoods) return;

    updateCartItem(skuId, { isSelected: !!isSelected }).then((res) => {
      this.applyServerData(res);
    }).catch(() => {
      Toast({ context: this, selector: '#t-toast', message: '操作失败', theme: 'error' });
    });
  },

  // 全选门店
  onStoreSelect(e) {
    const {
      store: { storeId },
      isSelected,
    } = e.detail;
    const currentStore = this.data.cartGroupData.storeGoods.find((s) => s.storeId === storeId);
    if (!currentStore) return;

    // 批量更新该门店下所有商品的选中状态
    const promises = [];
    currentStore.promotionGoodsList.forEach((activity) => {
      activity.goodsPromotionList.forEach((goods) => {
        promises.push(updateCartItem(goods.skuId, { isSelected: !!isSelected }));
      });
    });

    Promise.all(promises).then(() => {
      this.refreshData();
    }).catch(() => {
      Toast({ context: this, selector: '#t-toast', message: '操作失败', theme: 'error' });
    });
  },

  // 加购数量变更
  onQuantityChange(e) {
    const {
      goods: { spuId, skuId },
      quantity,
    } = e.detail;
    const { currentGoods } = this.findGoods(spuId, skuId);
    if (!currentGoods) return;

    const stockQuantity = currentGoods.stockQuantity > 0 ? currentGoods.stockQuantity : 0;

    if (quantity > stockQuantity) {
      if (currentGoods.quantity === stockQuantity && quantity - stockQuantity === 1) {
        Toast({ context: this, selector: '#t-toast', message: '当前商品库存不足' });
        return;
      }
      Dialog.confirm({
        title: '商品库存不足',
        content: `当前商品库存不足，最大可购买数量为${stockQuantity}件`,
        confirmBtn: '修改为最大可购买数量',
        cancelBtn: '取消',
      }).then(() => {
        updateCartItem(skuId, { quantity: stockQuantity }).then((res) => {
          this.applyServerData(res);
        });
      }).catch(() => {});
      return;
    }

    updateCartItem(skuId, { quantity }).then((res) => {
      this.applyServerData(res);
    }).catch(() => {
      Toast({ context: this, selector: '#t-toast', message: '更新失败', theme: 'error' });
    });
  },

  // 删除商品
  onGoodsDelete(e) {
    const {
      goods: { skuId },
    } = e.detail;
    Dialog.confirm({
      content: '确认删除该商品吗?',
      confirmBtn: '确定',
      cancelBtn: '取消',
    }).then(() => {
      deleteCartItem(skuId).then((res) => {
        Toast({ context: this, selector: '#t-toast', message: '商品删除成功' });
        this.applyServerData(res);
      }).catch(() => {
        Toast({ context: this, selector: '#t-toast', message: '删除失败', theme: 'error' });
      });
    });
  },

  // 清空失效商品
  clearInvalidGoods() {
    clearInvalidCartItems().then((res) => {
      this.applyServerData(res);
    }).catch(() => {
      Toast({ context: this, selector: '#t-toast', message: '清理失败', theme: 'error' });
    });
  },

  // 全选
  onSelectAll(event) {
    const { isAllSelected } = event?.detail ?? {};
    const targetSelected = !isAllSelected;

    const promises = [];
    this.data.cartGroupData.storeGoods.forEach((store) => {
      store.promotionGoodsList.forEach((activity) => {
        activity.goodsPromotionList.forEach((goods) => {
          promises.push(updateCartItem(goods.skuId, { isSelected: targetSelected }));
        });
      });
    });

    Promise.all(promises).then(() => {
      this.refreshData();
    }).catch(() => {
      Toast({ context: this, selector: '#t-toast', message: '操作失败', theme: 'error' });
    });
  },

  goCollect() {
    const promotionID = '123';
    wx.navigateTo({
      url: `/pages/promotion/promotion-detail/index?promotion_id=${promotionID}`,
    });
  },

  goGoodsDetail(e) {
    const { spuId, storeId } = e.detail.goods;
    wx.navigateTo({
      url: `/pages/goods/details/index?spuId=${spuId}&storeId=${storeId}`,
    });
  },

  onToSettle() {
    const goodsRequestList = [];
    this.data.cartGroupData.storeGoods.forEach((store) => {
      store.promotionGoodsList.forEach((promotion) => {
        promotion.goodsPromotionList.forEach((m) => {
          if (m.isSelected == 1) {
            goodsRequestList.push(m);
          }
        });
      });
    });
    wx.setStorageSync('order.goodsRequestList', JSON.stringify(goodsRequestList));
    wx.navigateTo({ url: '/pages/order/order-confirm/index?type=cart' });
  },

  onGotoHome() {
    wx.switchTab({ url: '/pages/home/home' });
  },
});
