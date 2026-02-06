import { getCategoryList } from '../../services/good/fetchCategoryList';
Page({
  data: {
    list: [],
  },
  async init() {
    try {
      const result = await getCategoryList();
      this.setData({
        list: result,
      });
    } catch (error) {
      console.error('err:', error);
    }
  },

  onShow() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar && typeof tabBar.init === 'function') {
      tabBar.init();
    }
  },
  onChange(event) {
    const { item } = event?.detail || {};
    if (!item || !item.groupId) {
      return;
    }
    const categoryId = item.groupId;
    const categoryName = encodeURIComponent(item.name || '');
    wx.navigateTo({
      url: `/pages/goods/list/index?categoryId=${categoryId}&categoryName=${categoryName}`,
    });
  },
  onLoad() {
    this.init(true);
  },
});
