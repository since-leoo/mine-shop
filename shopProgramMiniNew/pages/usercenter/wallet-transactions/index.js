import { fetchWalletTransactions } from '../../../services/usercenter/fetchWalletTransactions';

/** 交易类型标签映射 */
const TYPE_LABELS = {
  income: '收入',
  expense: '支出',
  recharge: '充值',
  withdraw: '提现',
  refund: '退款',
  reward: '奖励',
  deduct: '扣除',
  adjust: '调整',
};

Page({
  data: {
    type: 'balance', // balance | points
    list: [],
    page: 1,
    pageSize: 20,
    hasMore: true,
    loading: false,
  },

  onLoad(options) {
    const type = options.type === 'points' ? 'points' : 'balance';
    const title = type === 'points' ? '积分明细' : '余额明细';

    this.setData({ type });
    wx.setNavigationBarTitle({ title });
    this.fetchList();
  },

  onPullDownRefresh() {
    this.setData({ list: [], page: 1, hasMore: true });
    this.fetchList().then(() => {
      wx.stopPullDownRefresh();
    }).catch(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.fetchList();
    }
  },

  fetchList() {
    if (this.data.loading) return Promise.resolve();

    this.setData({ loading: true });

    const { type, page, pageSize } = this.data;

    return fetchWalletTransactions({ walletType: type, page, pageSize })
      .then((res) => {
        const records = (res.list || []).map((item) => ({
          id: item.id,
          typeLabel: TYPE_LABELS[item.type] || item.type || '其他',
          amount: item.amount,
          amountText: this.formatAmount(item.amount, type),
          source: item.description || item.source || '',
          createdAt: this.formatTime(item.createdAt),
        }));

        const newList = this.data.list.concat(records);
        const total = res.total || 0;

        this.setData({
          list: newList,
          page: page + 1,
          loading: false,
          hasMore: newList.length < total,
        });
      })
      .catch(() => {
        this.setData({ loading: false });
      });
  },

  /**
   * 格式化金额显示
   * 正数加 "+" 前缀，负数保持 "-" 前缀
   * 余额类型从分转元显示
   */
  formatAmount(amount, type) {
    let display;
    if (type === 'balance') {
      const yuan = (Math.abs(amount) / 100).toFixed(2);
      display = amount >= 0 ? '+' + yuan : '-' + yuan;
    } else {
      display = amount > 0 ? '+' + amount : '' + amount;
    }
    return display;
  },

  /**
   * 格式化时间，截取到分钟
   */
  formatTime(timeStr) {
    if (!timeStr) return '';
    // 后端返回格式如 "2024-01-15T10:30:00.000000Z" 或 "2024-01-15 10:30:00"
    const str = String(timeStr).replace('T', ' ').replace(/\.\d+Z?$/, '');
    return str.substring(0, 16); // "2024-01-15 10:30"
  },
});
