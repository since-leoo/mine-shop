import { View, Text, Button } from '@tarojs/components';
import Taro, { usePullDownRefresh, useReachBottom } from '@tarojs/taro';
import { useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import PageNav from '../../../components/page-nav';
import type { PageResult, TeamScope } from '../services';
import './shared.scss';

export const goDistribution = (page = '') => Taro.navigateTo({ url: `/plugins/distribution/pages/${page ? `${page}/` : ''}index` });
export function DistributionLayout({ title, children }: { title: string; children: ReactNode }) {
  return <View className="distribution-page"><PageNav title={title} /><View className="distribution-page__body">{children}</View></View>;
}
export function LoadState({ loading, error, empty = false, onRetry }: { loading: boolean; error: string; empty?: boolean; onRetry: () => void }) {
  if (error) return <View className="distribution-state"><Text>{error}</Text><Button size="mini" onClick={onRetry}>重新加载</Button></View>;
  if (loading) return <View className="distribution-state">加载中…</View>;
  if (empty) return <View className="distribution-state">暂无记录</View>;
  return null;
}
export function ScopeTabs({ value, onChange }: { value: TeamScope; onChange: (value: TeamScope) => void }) {
  return <View className="distribution-tabs">{([{ key: 'direct', title: '直属粉丝' }, { key: 'all', title: '全部团队' }] as const).map(item => <View key={item.key} className={`distribution-tabs__item${value === item.key ? ' is-active' : ''}`} onClick={() => onChange(item.key)}>{item.title}</View>)}</View>;
}

/** A new query supersedes prior requests, including a slow response from the other tab. */
export function useDistributionList<T>(queryKey: string, loader: (page: number) => Promise<PageResult<T>>) {
  const [list, setList] = useState<T[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const loaderRef = useRef(loader);
  loaderRef.current = loader;
  const nextPage = useRef(1);
  const requestId = useRef(0);
  const busy = useRef(false);
  const hasMore = useRef(true);
  const load = useCallback(async (reset = false) => {
    if (!reset && (busy.current || !hasMore.current)) return;
    const id = ++requestId.current;
    const page = reset ? 1 : nextPage.current;
    busy.current = true;
    setLoading(true);
    setError('');
    if (reset) { setList([]); setTotal(0); }
    try {
      const result = await loaderRef.current(page);
      if (id !== requestId.current) return;
      const rows = Array.isArray(result.list) ? result.list : [];
      const count = Number(result.total || 0);
      setList(old => reset ? rows : [...old, ...rows]);
      setTotal(count);
      nextPage.current = page + 1;
      hasMore.current = rows.length > 0 && page * 20 < count;
    } catch (err: any) {
      if (id === requestId.current) setError(err?.msg || err?.message || '加载失败，请重试');
    } finally {
      if (id === requestId.current) { busy.current = false; setLoading(false); }
    }
  }, []);
  useEffect(() => {
    hasMore.current = true;
    void load(true);
    return () => { ++requestId.current; busy.current = false; };
  }, [queryKey, load]);
  usePullDownRefresh(() => { void load(true).finally(() => Taro.stopPullDownRefresh()); });
  useReachBottom(() => { void load(); });
  return { list, total, loading, error, hasMore: hasMore.current, reload: () => load(true), loadMore: () => load() };
}
export function ListFooter({ loading, error, total, count, onRetry, onMore }: { loading: boolean; error: string; total: number; count: number; onRetry: () => void; onMore: () => void }) {
  return <><LoadState loading={loading} error={error} empty={count === 0} onRetry={onRetry} />{!loading && !error && count > 0 ? count < total ? <Button className="distribution-more" onClick={onMore}>加载更多</Button> : <Text className="distribution-end">已显示全部 {total} 条</Text> : null}</>;
}
