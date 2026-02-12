import {
  getSearchHistory,
  getSearchPopular,
  addSearchHistory,
  clearSearchHistory,
  removeSearchHistoryItem,
} from '../../../services/good/fetchSearchHistory';

Page({
  data: {
    historyWords: [],
    popularWords: [],
    searchValue: '',
    dialog: {
      title: '确认删除当前历史记录',
      showCancelButton: true,
      message: '',
    },
    dialogShow: false,
  },

  deleteType: 0,
  deleteIndex: '',

  onShow() {
    this.queryHistory();
    this.queryPopular();
  },

  async queryHistory() {
    try {
      const data = await getSearchHistory();
      this.setData({ historyWords: data.historyWords || [] });
    } catch (error) {
      console.error(error);
    }
  },

  async queryPopular() {
    try {
      const data = await getSearchPopular();
      this.setData({ popularWords: data.popularWords || [] });
    } catch (error) {
      console.error(error);
    }
  },

  confirm() {
    const { historyWords } = this.data;
    if (this.deleteType === 1) {
      clearSearchHistory();
      this.setData({ historyWords: [], dialogShow: false });
    } else {
      removeSearchHistoryItem(this.deleteIndex);
      historyWords.splice(this.deleteIndex, 1);
      this.setData({ historyWords, dialogShow: false });
    }
  },

  close() {
    this.setData({ dialogShow: false });
  },

  handleClearHistory() {
    this.deleteType = 1;
    this.setData({
      dialog: { ...this.data.dialog, message: '确认删除所有历史记录' },
      dialogShow: true,
    });
  },

  deleteCurr(e) {
    const { index } = e.currentTarget.dataset;
    this.deleteIndex = index;
    this.deleteType = 0;
    this.setData({
      dialog: { ...this.data.dialog, message: '确认删除当前历史记录' },
      dialogShow: true,
    });
  },

  handleHistoryTap(e) {
    const { index } = e.currentTarget.dataset;
    const word = this.data.historyWords[index] || '';
    if (word) this.doSearch(word);
  },

  handlePopularTap(e) {
    const { index } = e.currentTarget.dataset;
    const word = this.data.popularWords[index] || '';
    if (word) this.doSearch(word);
  },

  handleSubmit(e) {
    const value = (e.detail.value || '').trim();
    if (!value) return;
    this.doSearch(value);
  },

  doSearch(keyword) {
    addSearchHistory(keyword);
    wx.navigateTo({
      url: `/pages/goods/result/index?searchValue=${keyword}`,
    });
  },
});
