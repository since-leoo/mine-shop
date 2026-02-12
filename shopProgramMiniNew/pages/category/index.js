import { getCategoryList } from '../../services/good/fetchCategoryList.js';

Page({
  data: {
    list: [],
  },

  async init() {
    try {
      const result = await getCategoryList();
      const list = this.normalizeCategoryTree(result);
      this.setData({ list });
    } catch (error) {
      console.error('err:', error);
    }
  },

  onShow() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar && typeof tabBar.init === 'function') tabBar.init();
  },

  onChange(event) {
    const { item } = event?.detail || {};
    const targetId = item?.groupId || item?.categoryId;
    if (!item || !targetId) {
      wx.navigateTo({ url: '/pages/goods/list/index' });
      return;
    }
    const categoryId = targetId;
    const categoryName = encodeURIComponent(item.title || '');
    wx.navigateTo({
      url: `/pages/goods/list/index?categoryId=${categoryId}&categoryName=${categoryName}`,
    });
  },

  onLoad() {
    this.init();
  },

  normalizeCategoryTree(categories = []) {
    if (!Array.isArray(categories)) return [];
    return categories.map((item) => {
      const children = this.normalizeCategoryTree(item.children || []);
      const title = item.title || '';
      const groupId = item.groupId || item.categoryId || '';
      return {
        ...item,
        title,
        groupId,
        children,
      };
    });
  },
});
