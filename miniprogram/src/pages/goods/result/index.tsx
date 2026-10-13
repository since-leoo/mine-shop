import { View, Text } from '@tarojs/components';
import Taro, { useReachBottom } from '@tarojs/taro';
import { useState, useCallback } from 'react';
import { SearchBar } from '@nutui/nutui-react-taro';
import { getSearchResult } from '../../../services/good/fetchSearchResult';
import { addSearchHistory } from '../../../services/good/fetchSearchHistory';
import GoodsList from '../../../components/GoodsList';
import LoadMore from '../../../components/LoadMore';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import { useProductResults, type SortMode } from '../use-product-results';
import './index.scss';

export default function ResultPage() {
  const [keywords, setKeywords] = useState(() => {
    const value = Taro.getCurrentInstance().router?.params?.searchValue || '';
    try { return decodeURIComponent(value); } catch { return value; }
  });
  const [submittedKeyword, setSubmittedKeyword] = useState(keywords.trim());
  const [sortMode, setSortMode] = useState<SortMode>('overall');
  const { goodsList, hasLoaded, loadMoreStatus, loadNext, retry } = useProductResults(getSearchResult, {
    keyword: submittedKeyword, sortMode, enabled: !!submittedKeyword,
  });
  useReachBottom(loadNext);
  const handleSubmit = () => {
    const keyword = keywords.trim();
    if (!keyword) return;
    addSearchHistory(keyword);
    setSubmittedKeyword(keyword);
  };
  const handleSortChange = (mode: SortMode) => setSortMode(mode);
  const handleGoodsClick = useCallback((goods: any) => {
    const spuId = goods.spuId || goods.id || '';
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${spuId}` });
  }, []);

  return (
    <View className={`result-page ${isH5() ? 'result-page--h5' : ''}`}>
      <PageNav title="搜索结果" />
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
      {goodsList.length === 0 && hasLoaded && loadMoreStatus === 2 && (
        <View className="empty-state">
          <View className="empty-state__search-icon" />
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
            onAddCart={handleGoodsClick}
          />
        </View>
      )}

      <LoadMore
        status={loadMoreStatus}
        listIsEmpty={goodsList.length === 0}
        onRetry={retry}
      />
    </View>
  );
}
