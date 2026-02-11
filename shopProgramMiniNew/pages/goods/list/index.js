import { fetchGoodsList } from '../../../services/good/fetchGoodsList';
import Toast from 'tdesign-miniprogram/toast/index';

const initFilters = { overall: 1, sorts: '', layout: 0 };

Page({
  data: {
    goodsList: [],
    layout: 0,
    sorts: '',
    overall: 1,
    show: false,
    minVal: '',
    maxVal: '',
    filter: initFilters,
    hasLoaded: false,
    loadMoreStatus: 0,
    loading: true,
    keywords: '',
    categoryId: '',
    categoryName: '',
  },

  pageNum: 1,
  pageSize: 30,
  total: 0,

  onLoad(query) {
    const { categoryId, categoryName, keywords } = query;
    if (categoryId) this.setData({ categoryId });
    if (categoryName) {
      const decoded = decodeURIComponent(categoryName);
      this.setData({ categoryName: decoded });
      wx.setNavigationBarTitle({ title: decoded });
    }
    if (keywords) this.setData({ keywords });
    this.init(true);
  },

  onReachBottom() {
    const { goodsList } = this.data;
    if (goodsList.length >= this.total && this.total > 0) {
      this.setData({ loadMoreStatus: 2 });
      return;
    }
    this.init(false);
  },

  handleFilterChange(e) {
    const { layout, overall, sorts } = e.detail;
    this.pageNum = 1;
    this.setData({ layout, sorts, overall, loadMoreStatus: 0 });
    this.init(true);
  },

  async init(reset = true) {
    const { loadMoreStatus, goodsList = [] } = this.data;
    if (loadMoreStatus !== 0 && !reset) return;
    this.setData({ loadMoreStatus: 1, loading: true });

    const params = {
      pageNum: reset ? 1 : this.pageNum + 1,
      pageSize: this.pageSize,
      keyword: this.data.keywords || undefined,
      categoryId: this.data.categoryId || undefined,
      sort: this.data.overall ? undefined : (this.data.sorts || undefined),
    };

    try {
      const { spuList, totalCount = 0 } = await fetchGoodsList(params);
      if (totalCount === 0 && reset) {
        this.total = 0;
        this.setData({ goodsList: [], hasLoaded: true, loadMoreStatus: 0, loading: false });
        return;
      }
      const _goodsList = reset ? spuList : goodsList.concat(spuList);
      this.pageNum = params.pageNum;
      this.total = totalCount;
      this.setData({
        goodsList: _goodsList,
        loadMoreStatus: _goodsList.length >= totalCount ? 2 : 0,
        hasLoaded: true,
        loading: false,
      });
    } catch (error) {
      this.setData({ loading: false, hasLoaded: true });
    }
  },

  gotoGoodsDetail(e) {
    const { index } = e.detail;
    const { spuId } = this.data.goodsList[index];
    wx.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  },

  handleAddCart() {
    Toast({ context: this, selector: '#t-toast', message: '点击加购' });
  },

  tagClickHandle() {
    Toast({ context: this, selector: '#t-toast', message: '点击标签' });
  },

  showFilterPopup() { this.setData({ show: true }); },
  showFilterPopupClose() { this.setData({ show: false }); },
  onMinValAction(e) { this.setData({ minVal: e.detail.value }); },
  onMaxValAction(e) { this.setData({ maxVal: e.detail.value }); },
  reset() { this.setData({ minVal: '', maxVal: '' }); },

  confirm() {
    this.pageNum = 1;
    this.setData({ show: false, goodsList: [], loadMoreStatus: 0 }, () => this.init());
  },
});
