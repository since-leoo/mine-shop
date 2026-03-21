import { View, Text } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { useState, useEffect, useCallback } from 'react';
import { SearchBar } from '@nutui/nutui-react-taro';
import {
  getSearchHistory,
  getSearchPopular,
  addSearchHistory,
  clearSearchHistory,
} from '../../../services/good/fetchSearchHistory';
import './index.scss';

export default function SearchPage() {
  const [searchValue, setSearchValue] = useState('');
  const [historyWords, setHistoryWords] = useState<string[]>([]);
  const [popularWords, setPopularWords] = useState<string[]>([]);

  const queryHistory = useCallback(async () => {
    try {
      const data = await getSearchHistory();
      setHistoryWords(data.historyWords || []);
    } catch (error) {
      console.error('history error:', error);
    }
  }, []);

  const queryPopular = useCallback(async () => {
    try {
      const data = await getSearchPopular();
      setPopularWords(data.popularWords || []);
    } catch (error) {
      console.error('popular error:', error);
    }
  }, []);

  useEffect(() => {
    queryHistory();
    queryPopular();
  }, [queryHistory, queryPopular]);

  const doSearch = useCallback((keyword: string) => {
    if (!keyword.trim()) return;
    addSearchHistory(keyword);
    Taro.navigateTo({
      url: `/pages/goods/result/index?searchValue=${encodeURIComponent(keyword)}`,
    });
  }, []);

  const handleSubmit = useCallback(() => {
    doSearch(searchValue);
  }, [searchValue, doSearch]);

  const handleHistoryTap = useCallback((word: string) => {
    doSearch(word);
  }, [doSearch]);

  const handlePopularTap = useCallback((word: string) => {
    doSearch(word);
  }, [doSearch]);

  const handleClearHistory = useCallback(() => {
    Taro.showModal({
      title: '提示',
      content: '确认清除所有搜索历史？',
      success: (res) => {
        if (res.confirm) {
          clearSearchHistory();
          setHistoryWords([]);
        }
      },
    });
  }, []);

  return (
    <View className="search-page">
      {/* Search Bar */}
      <View className="search-page__bar">
        <SearchBar
          placeholder="搜索商品"
          value={searchValue}
          onChange={(val) => setSearchValue(val)}
          onSearch={handleSubmit}
          autoFocus
          className="search-page__search-bar"
        />
      </View>

      <View className="search-page__body">
        {/* History */}
        {historyWords.length > 0 && (
          <View className="search-section">
            <View className="search-section__header">
              <Text className="search-section__title">历史搜索</Text>
              <Text className="search-section__clear" onClick={handleClearHistory}>清除</Text>
            </View>
            <View className="search-section__tags">
              {historyWords.map((word, idx) => (
                <View
                  key={idx}
                  className="search-tag"
                  onClick={() => handleHistoryTap(word)}
                >
                  <Text className="search-tag__text">{word}</Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* Popular */}
        {popularWords.length > 0 && (
          <View className="search-section">
            <View className="search-section__header">
              <Text className="search-section__title">热门搜索</Text>
            </View>
            <View className="search-section__tags">
              {popularWords.map((word, idx) => (
                <View
                  key={idx}
                  className="search-tag search-tag--hot"
                  onClick={() => handlePopularTap(word)}
                >
                  <Text className="search-tag__text">{word}</Text>
                </View>
              ))}
            </View>
          </View>
        )}
      </View>
    </View>
  );
}
