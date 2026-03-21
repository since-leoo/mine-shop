import { View, Text } from '@tarojs/components';
import Taro, { useReachBottom } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchGoodsList } from '../../../services/good/fetchGoodsList';
import GoodsList from '../../../components/GoodsList';
import LoadMore from '../../../components/LoadMore';
import './index.scss';

type SortMode = 'overall' | 'price-asc' | 'price-desc';

const PAGE_SIZE = 30;

export default function GoodsListPage() {
  const [goodsList, setGoodsList] = useState<any[]>([]);
  const [hasLoaded, setHasLoaded] = useState(false);
  const [loadMoreStatus, setLoadMoreStatus] = useState<0 | 1 | 2 | 3>(0);
  const [sortMode, setSortMode] = useState<SortMode>('overall');
  const [categoryId, setCategoryId] = useState('');

  const pageNumRef = useRef(1);
  const totalRef = useRef(0);

  const loadData = useCallback(async (reset = true) => {
    if (loadMoreStatus === 1 && !reset) return;
    setLoadMoreStatus(1);

    const params: any = {
      pageNum: reset ? 1 : pageNumRef.current + 1,
      pageSize: PAGE_SIZE,
    };

    if (categoryId) params.categoryId = categoryId;
    if (sortMode === 'overall') {
      // default sort
    } else if (sortMode === 'price-asc') {
      params.sort = 1;
      params.sortType = 0;
    } else if (sortMode === 'price-desc') {
      params.sort = 1;
      params.sortType = 1;
    }

    try {
      const { spuList = [], totalCount = 0 } = await fetchGoodsList(params);
      if (totalCount === 0 && reset) {
        totalRef.current = 0;
        setGoodsList([]);
        setHasLoaded(true);
        setLoadMoreStatus(0);
        return;
      }
      const newList = reset ? spuList : [...goodsList, ...spuList];
      pageNumRef.current = params.pageNum;
      totalRef.current = totalCount;
      setGoodsList(newList);
      setLoadMoreStatus(newList.length >= totalCount ? 2 : 0);
      setHasLoaded(true);
    } catch {
      setLoadMoreStatus(3);
      setHasLoaded(true);
    }
  }, [goodsList, categoryId, sortMode, loadMoreStatus]);

  useEffect(() => {
    const instance = Taro.getCurrentInstance();
    const params = instance.router?.params || {};
    if (params.categoryId) setCategoryId(params.categoryId);
    if (params.categoryName) {
      const decoded = decodeURIComponent(params.categoryName);
      Taro.setNavigationBarTitle({ title: decoded });
    }
  }, []);

  useEffect(() => {
    loadData(true);
  }, [sortMode, categoryId]);

  useReachBottom(() => {
    if (goodsList.length < totalRef.current) {
      loadData(false);
    } else if (goodsList.length > 0) {
      setLoadMoreStatus(2);
    }
  });

  const handleSortChange = useCallback((mode: SortMode) => {
    if (mode === sortMode) return;
    setSortMode(mode);
    setGoodsList([]);
    pageNumRef.current = 1;
    setLoadMoreStatus(0);
  }, [sortMode]);

  const handleGoodsClick = useCallback((goods: any) => {
    const spuId = goods.spuId || goods.id || '';
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  }, []);

  const handleRetry = useCallback(() => {
    loadData(false);
  }, [loadData]);

  return (
    <View className="goods-list-page">
      {/* Sort Bar */}
      <View className="sort-bar">
        <View
          className={`sort-bar__item ${sortMode === 'overall' ? 'sort-bar__item--active' : ''}`}
          onClick={() => handleSortChange('overall')}
        >
          <Text className="sort-bar__text">综合</Text>
        </View>
        <View
          className={`sort-bar__item ${sortMode === 'price-asc' ? 'sort-bar__item--active' : ''}`}
          onClick={() => handleSortChange('price-asc')}
        >
          <Text className="sort-bar__text">价格升序</Text>
          <Text className="sort-bar__arrow">{'\u2191'}</Text>
        </View>
        <View
          className={`sort-bar__item ${sortMode === 'price-desc' ? 'sort-bar__item--active' : ''}`}
          onClick={() => handleSortChange('price-desc')}
        >
          <Text className="sort-bar__text">价格降序</Text>
          <Text className="sort-bar__arrow">{'\u2193'}</Text>
        </View>
      </View>

      {/* Empty State */}
      {goodsList.length === 0 && hasLoaded && (
        <View className="empty-state">
          <Text className="empty-state__icon">{'\u{1F50D}'}</Text>
          <Text className="empty-state__text">暂无相关商品</Text>
        </View>
      )}

      {/* Goods Grid */}
      {goodsList.length > 0 && (
        <View className="goods-list-wrap">
          <GoodsList
            goodsList={goodsList.map((item) => ({
              id: item.spuId || item.id,
              thumb: item.thumb ?? item.primaryImage ?? '',
              title: item.title,
              price: item.price ?? item.minSalePrice ?? 0,
              originPrice: item.originPrice ?? item.maxLinePrice ?? 0,
              tags: item.tags || [],
              spuId: item.spuId,
            }))}
            onClickGoods={handleGoodsClick}
          />
        </View>
      )}

      <LoadMore
        status={loadMoreStatus}
        listIsEmpty={goodsList.length === 0}
        onRetry={handleRetry}
      />
    </View>
  );
}
