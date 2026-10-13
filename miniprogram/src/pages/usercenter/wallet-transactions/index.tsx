import { View, Text } from '@tarojs/components';
import Taro, { useRouter, usePullDownRefresh, useReachBottom } from '@tarojs/taro';
import { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { fetchWalletTransactions } from '../../../services/usercenter/fetchWalletTransactions';
import PageNav from '../../../components/page-nav';
import { isMiniProgram } from '../../../common/platform';
import { getMiniProgramNavMetrics } from '../../../utils/system-info';
import './index.scss';

const TYPE_LABELS: Record<string, string> = {
  income: '收入',
  expense: '支出',
  recharge: '充值',
  withdraw: '提现',
  refund: '退款',
  reward: '奖励',
  deduct: '扣除',
  adjust: '调整',
  adjustIn: '调增',
  adjustOut: '调减',
  adjust_in: '调增',
  adjust_out: '调减',
};

const INCOME_TYPES = new Set(['income', 'recharge', 'refund', 'reward', 'adjustIn', 'adjust_in']);
const EXPENSE_TYPES = new Set(['expense', 'withdraw', 'deduct', 'adjustOut', 'adjust_out']);

interface TransactionItem {
  id: string;
  typeLabel: string;
  flowType: 'income' | 'expense';
  amount: number;
  amountText: string;
  source: string;
  createdAt: string;
}

interface TransactionGroup {
  key: string;
  title: string;
  items: TransactionItem[];
}

function formatAmount(amount: number, type: string): string {
  if (type === 'balance') {
    const yuan = (Math.abs(amount) / 100).toFixed(2);
    return amount >= 0 ? '+' + yuan : '-' + yuan;
  }
  return amount > 0 ? '+' + amount : '' + amount;
}

function resolveFlowType(type: string, amount: number): 'income' | 'expense' {
  if (INCOME_TYPES.has(type)) return 'income';
  if (EXPENSE_TYPES.has(type)) return 'expense';
  return amount >= 0 ? 'income' : 'expense';
}

function formatTime(timeStr: string): string {
  if (!timeStr) return '';
  const str = String(timeStr).replace('T', ' ').replace(/\.\d+Z?$/, '');
  return str.substring(0, 16);
}

function parseTimeToMs(timeStr: string): number {
  if (!timeStr) return 0;
  const ms = new Date(timeStr.replace(/\./g, '/').replace('T', ' ')).getTime();
  return Number.isFinite(ms) ? ms : 0;
}

export default function WalletTransactions() {
  const router = useRouter();
  const initialType = router.params.type === 'points' ? 'points' : 'balance';
  const weapp = isMiniProgram();
  const navMetrics = weapp ? getMiniProgramNavMetrics() : null;

  const [walletType, setWalletType] = useState(initialType);
  const [list, setList] = useState<TransactionItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [error, setError] = useState('');
  const requestRef = useRef(0);
  const busyRef = useRef(false);
  const walletTypeRef = useRef(walletType);
  walletTypeRef.current = walletType;

  const pageRef = useRef(1);
  const PAGE_SIZE = 20;
  const summary = useMemo(() => {
    const income = list
      .filter((item) => item.flowType === 'income')
      .reduce((sum, item) => sum + Math.abs(item.amount), 0);
    const expense = list
      .filter((item) => item.flowType === 'expense')
      .reduce((sum, item) => sum + Math.abs(item.amount), 0);

    return {
      incomeText:
        walletType === 'balance'
          ? (income / 100).toFixed(2)
          : String(income),
      expenseText:
        walletType === 'balance'
          ? (expense / 100).toFixed(2)
          : String(expense),
      countText: String(list.length),
    };
  }, [list, walletType]);

  const groupedList = useMemo<TransactionGroup[]>(() => {
    if (list.length === 0) return [];

    const now = new Date();
    const todayKey = `${now.getFullYear()}-${now.getMonth() + 1}-${now.getDate()}`;
    const currentMonthKey = `${now.getFullYear()}-${now.getMonth() + 1}`;
    const map: Record<string, TransactionGroup> = {};

    list.forEach((item) => {
      const ts = parseTimeToMs(item.createdAt);
      const d = ts ? new Date(ts) : null;
      let title = '更早';
      let key = 'earlier';

      if (d) {
        const itemDayKey = `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
        const itemMonthKey = `${d.getFullYear()}-${d.getMonth() + 1}`;
        if (itemDayKey === todayKey) {
          title = '今天';
          key = 'today';
        } else if (itemMonthKey === currentMonthKey) {
          title = '本月';
          key = 'month';
        } else {
          title = `${d.getFullYear()}年${String(d.getMonth() + 1).padStart(2, '0')}月`;
          key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        }
      }

      if (!map[key]) {
        map[key] = { key, title, items: [] };
      }
      map[key].items.push(item);
    });

    const order = ['today', 'month'];
    return Object.values(map).sort((a, b) => {
      const ai = order.indexOf(a.key);
      const bi = order.indexOf(b.key);
      if (ai >= 0 || bi >= 0) return (ai < 0 ? 99 : ai) - (bi < 0 ? 99 : bi);
      return b.key.localeCompare(a.key);
    });
  }, [list]);

  useEffect(() => {
    const title = walletType === 'points' ? '积分明细' : '余额明细';
    Taro.setNavigationBarTitle({ title });
  }, [walletType]);

  const fetchList = useCallback(async (reset = false) => {
    if (busyRef.current && !reset) return;
    const sequence = ++requestRef.current;
    const type = walletTypeRef.current;
    const page = reset ? 1 : pageRef.current;
    busyRef.current = true;
    setLoading(true);
    setError('');
    try {
      const res: any = await fetchWalletTransactions({ walletType: type, page, pageSize: PAGE_SIZE });
      // 快速切换余额/积分时，旧请求不能覆盖新标签的数据和分页状态。
      if (sequence !== requestRef.current || type !== walletTypeRef.current) return;
      const records = (res.list || []).map((item: any) => {
        const amount = Number(item.amount) || 0;
        const flowType = resolveFlowType(String(item.type || ''), amount);
        return {
          id: item.id, flowType, amount,
          typeLabel: TYPE_LABELS[item.type] || item.type || '其他',
          amountText: formatAmount(flowType === 'expense' ? -Math.abs(amount) : Math.abs(amount), type),
          source: item.description || item.source || '', createdAt: formatTime(item.createdAt),
        };
      });
      setList(previous => reset ? records : [...previous, ...records]);
      setHasMore(page * PAGE_SIZE < Number(res.total || 0));
      pageRef.current = page + 1;
    } catch (err: any) {
      if (sequence === requestRef.current) setError(err?.msg || '明细加载失败，请重试');
    } finally {
      if (sequence === requestRef.current) { busyRef.current = false; setLoading(false); }
    }
  }, []);

  useEffect(() => {
    void fetchList(true);
    return () => { ++requestRef.current; busyRef.current = false; };
  }, [walletType, fetchList]);

  usePullDownRefresh(() => {
    void fetchList(true).finally(() => Taro.stopPullDownRefresh());
  });
  useReachBottom(() => { if (hasMore) void fetchList(); });

  const handleTabChange = useCallback((type: 'balance' | 'points') => {
    if (walletTypeRef.current === type) return;
    walletTypeRef.current = type;
    setWalletType(type);
    setList([]);
    setHasMore(true);
    pageRef.current = 1;
  }, []);

  return (
    <View className={`wallet-page${weapp ? ' wallet-page--weapp' : ''}`}>
      {weapp ? (
        <View className="wallet-page__nav"><PageNav title={walletType === 'points' ? '积分明细' : '余额明细'} /></View>
      ) : <PageNav title={walletType === 'points' ? '积分明细' : '余额明细'} />}
      {/* Tabs */}
      <View className="wallet-page__tabs" style={navMetrics ? { top: `calc(${navMetrics.statusBarHeight + navMetrics.navHeight}px + 20rpx)` } : undefined}>
        <View className="wallet-tabs">
          <View
            className={`wallet-tabs__item ${walletType === 'balance' ? 'wallet-tabs__item--active' : ''}`}
            onClick={() => handleTabChange('balance')}
          >
            <Text className={`wallet-tabs__text ${walletType === 'balance' ? 'wallet-tabs__text--active' : ''}`}>余额</Text>
          </View>
          <View
            className={`wallet-tabs__item ${walletType === 'points' ? 'wallet-tabs__item--active' : ''}`}
            onClick={() => handleTabChange('points')}
          >
            <Text className={`wallet-tabs__text ${walletType === 'points' ? 'wallet-tabs__text--active' : ''}`}>积分</Text>
          </View>
        </View>
      </View>

      <View className="wallet-page__summary">
        <View className="wallet-page__summary-head">
          <Text className="wallet-page__summary-title">
            {walletType === 'balance' ? '余额明细' : '积分明细'}
          </Text>
          <Text className="wallet-page__summary-count">共{summary.countText}笔</Text>
        </View>
        <View className="wallet-page__summary-grid">
          <View className="wallet-page__summary-cell">
            <Text className="wallet-page__summary-label">已加载收入</Text>
            <Text className="wallet-page__summary-value wallet-page__summary-value--income">
              {walletType === 'balance' ? `¥${summary.incomeText}` : summary.incomeText}
            </Text>
          </View>
          <View className="wallet-page__summary-cell">
            <Text className="wallet-page__summary-label">已加载支出</Text>
            <Text className="wallet-page__summary-value wallet-page__summary-value--expense">
              {walletType === 'balance' ? `¥${summary.expenseText}` : summary.expenseText}
            </Text>
          </View>
        </View>
      </View>

      {/* Transaction list */}
      {list.length > 0 && (
        <View className="wallet-page__list">
          {groupedList.map((group) => (
            <View key={group.key} className="wallet-page__group">
              <View className="wallet-page__group-head">
                <Text className="wallet-page__group-title">{group.title}</Text>
                <Text className="wallet-page__group-count">{group.items.length}笔</Text>
              </View>
              {group.items.map((item) => (
                <View key={item.id} className="wallet-page__item">
                  <View className="wallet-page__item-info">
                    <View className="wallet-page__item-header">
                      <Text className="wallet-page__type-label">{item.typeLabel}</Text>
                      {item.source && (
                        <Text className="wallet-page__source">{item.source}</Text>
                      )}
                    </View>
                    <Text className="wallet-page__time">{item.createdAt}</Text>
                  </View>
                  <Text
                    className={`wallet-page__amount ${
                      item.flowType === 'income' ? 'wallet-page__amount--positive' : 'wallet-page__amount--negative'
                    }`}
                  >
                    {item.amountText}
                  </Text>
                </View>
              ))}
            </View>
          ))}
        </View>
      )}

      {/* Empty */}
      {!loading && !error && list.length === 0 && (
        <View className="wallet-page__state">
          <Text className="wallet-page__state-icon">📄</Text>
          <Text className="wallet-page__state-text">暂无记录</Text>
        </View>
      )}

      {!!error && !loading && <View className="wallet-page__state" onClick={() => void fetchList(list.length === 0)}>
        <Text className="wallet-page__state-text">{error}</Text>
        <Text className="wallet-page__retry">点击重新加载</Text>
      </View>}
      {/* Loading */}
      {loading && (
        <View className="wallet-page__state">
          <Text className="wallet-page__state-text">加载中...</Text>
        </View>
      )}

      {/* No more */}
      {!hasMore && list.length > 0 && (
        <View className="wallet-page__no-more">
          <Text className="wallet-page__no-more-text">没有更多了</Text>
        </View>
      )}
    </View>
  );
}
