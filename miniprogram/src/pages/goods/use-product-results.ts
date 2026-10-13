import { useCallback, useEffect, useRef, useState } from 'react';

export type SortMode = 'overall' | 'price-asc' | 'price-desc';

export function useProductResults(fetcher: (params: any) => Promise<any>, filters: {
  categoryId?: string; keyword?: string; sortMode: SortMode; enabled?: boolean;
}) {
  const { categoryId, keyword, sortMode, enabled = true } = filters;
  const [goodsList, setGoodsList] = useState<any[]>([]);
  const [hasLoaded, setHasLoaded] = useState(false);
  const [loadMoreStatus, setLoadMoreStatus] = useState<0 | 1 | 2 | 3>(0);
  const page = useRef(0);
  const total = useRef(0);
  const loadedCount = useRef(0);
  const busy = useRef(false);
  const generation = useRef(0);

  const loadData = useCallback(async (reset = false) => {
    if (!enabled || (busy.current && !reset)) return;
    if (reset) {
      generation.current += 1;
      page.current = 0;
      loadedCount.current = 0;
      total.current = 0;
      setGoodsList([]);
      setHasLoaded(false);
    }
    const currentGeneration = generation.current;
    const nextPage = page.current + 1;
    busy.current = true;
    setLoadMoreStatus(1);
    try {
      const { spuList = [], totalCount = 0 } = await fetcher({
        pageNum: nextPage, pageSize: 30, categoryId, keyword,
        sort: sortMode === 'overall' ? 0 : 1,
        sortType: sortMode === 'price-desc' ? 1 : 0,
      });
      // 筛选切换后，迟到的旧响应不能覆盖新的列表。
      if (currentGeneration !== generation.current) return;
      page.current = nextPage;
      total.current = Number(totalCount);
      loadedCount.current += spuList.length;
      setGoodsList(previous => nextPage === 1 ? spuList : [...previous, ...spuList]);
      setLoadMoreStatus(spuList.length === 0 || loadedCount.current >= total.current ? 2 : 0);
    } catch {
      if (currentGeneration !== generation.current) return;
      setLoadMoreStatus(3);
    } finally {
      if (currentGeneration === generation.current) {
        busy.current = false;
        setHasLoaded(true);
      }
    }
  }, [fetcher, categoryId, keyword, sortMode, enabled]);

  useEffect(() => {
    void loadData(true);
    return () => { generation.current += 1; busy.current = false; };
  }, [loadData]);

  const loadNext = () => {
    if (loadedCount.current < total.current && !busy.current) void loadData();
  };
  return { goodsList, hasLoaded, loadMoreStatus, loadNext, retry: () => loadData() };
}
