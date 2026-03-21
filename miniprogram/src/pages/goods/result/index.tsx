import { View, Text } from '@tarojs/components';
import Taro, { useReachBottom } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { SearchBar } from '@nutui/nutui-react-taro';
import { getSearchResult } from '../../../services/good/fetchSearchResult';
import { addSearchHistory } from '../../../services/good/fetchSearchHistory';
import GoodsList from '../../../components/GoodsList';
import LoadMore from '../../../components/LoadMore';
import './index.scss';

type SortMode = 'overall' | 'price-asc' | 'price-desc';

const PAGE_SIZE = 30;

export default function ResultPage() {
  const [keywords, setKeywords] = useState('');
  const [goodsList, setGoodsList] = useState<any[]>([]);
  const [hasLoaded, setHasLoaded] = useState(false);
  const [loadMoreStatus, setLoadMoreStatus] = useState<0 | 1 | 2 | 3>(0);
  const [sortMode, setSortMode] = useState<SortMode>('overall');

  const pageNumRef = useRef(1);
  const totalRef = useRef(0);

  const loadData = useCallback(async (reset = true, kw?: string) => {
    const keyword = kw ?? keywords;
    if (!keyword) return;
    if (loadMoreStatus === 1 && !reset) return;
    setLoadMoreStatus(1);

    const params: any = {
      pageNum: reset ? 1 : pageNumRef.current + 1,
      pageSize: PAGE_SIZE,
      keyword,
    };

    if (sortMode === 'price-asc') {
      params.sort = 1;
      params.sortType = 0;
    } else if (sortMode === 'price-desc') {
      params.sort = 1;
      params.sortType = 1;
    } else {
      params.sort = 0;
    }

    try {
      const data = await getSearchResult(params);
      const { spuList = [], totalCount = 0 } = data || {};

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
  }, [keywords, goodsList, sortMode, loadMoreStatus]);

  useEffect(() => {
    const instance = Taro.getCurrentInstance();
    const searchValue = instance.router?.params?.searchValue || '';
    if (searchValue) {
      const decoded = decodeURIComponent(searchValue);
      setKeywords(decoded);
      loadData(true, decoded);
    }
  }, []);

  useEffect(() => {
    if (keywords) {
      setGoodsList([]);
      pageNumRef.current = 1;
      setLoadMoreStatus(0);
      loadData(true);
    }
  }, [sortMode]);

  useReachBottom(() => {
    if (goodsList.length < totalRef.current) {
      loadData(false);
    } else if (goodsList.length > 0) {
      setLoadMoreStatus(2);
    }
  });

  const handleSubmit = useCallback(() => {
    const kw = keywords.trim();
    if (!kw) return;
    addSearchHistory(kw);
    setGoodsList([]);
    pageNumRef.current = 1;
    setLoadMoreStatus(0);
    loadData(true);
  }, [keywords, loadData]);

  const handleSortChange = useCallback((mode: SortMode) => {
    if (mode === sortMode) return;
    setSortMode(mode);
  }, [sortMode]);

  const handleGoodsClick = useCallback((goods: any) => {
    const spuId = goods.spuId || goods.id || '';
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  }, []);

  const handleRetry = useCallback(() => {
    loadData(false);
  }, [loadData]);

  return (
    <View className="result-page">
      {/* Search Bar */}
      <View className="result-page__bar">
        <SearchBar
          value={keywords}
          onChange={(val) => setKeywords(val)}
          onSearch={handleSubmit}
          placeholder="搜索商品"
          className="result-page__search-bar"
        />
      </View>

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
          <Text className="empty-state__text">抱歉，未找到相关商品</Text>
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
