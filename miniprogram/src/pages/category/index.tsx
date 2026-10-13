import { View, Text, ScrollView, Image } from '@tarojs/components';
import Taro, { useDidShow, usePullDownRefresh } from '@tarojs/taro';
import { useCallback, useMemo, useRef, useState } from 'react';
import { isH5 } from '../../common/platform';
import { currentDiyPageType, fetchDiyPage } from '../../services/diy/page';
import { getCategoryList } from '../../services/good/fetchCategoryList';
import PageNav from '../../components/page-nav';
import H5TabBar from '../../components/h5-tab-bar';
import DiyRenderer from '../../components/diy-renderer';
import { DiyPagePayload } from '../../components/diy-renderer/types';
import { diyStyle } from '../../components/diy-renderer/style';
import { categoryLeaves, normalizeCategories } from '../../plugins/diy/components/diy/CategoryPanel/categories';
import './index.scss';

interface CategoryChild {
  groupId: string;
  name: string;
  thumbnail: string;
}

interface CategoryGroup {
  groupId: string;
  name: string;
  thumbnail: string;
  children?: CategoryGroup[] | CategoryChild[];
}

function renderLoading() {
  return (
    <View className={`category-page ${isH5() ? 'category-page--h5' : ''}`}>
      <PageNav title="商品分类" showBack={false} />
      <View className="category-page__body">
        <View className="category-loading">
          <Text className="category-loading__text">加载中…</Text>
        </View>
      </View>
      {isH5() ? <H5TabBar current="/pages/category/index" /> : null}
    </View>
  );
}

export default function Category() {
  const [categories, setCategories] = useState<CategoryGroup[]>([]);
  const [activeIndex, setActiveIndex] = useState(0);
  const [loading, setLoading] = useState(true);
  const [diyPage, setDiyPage] = useState<DiyPagePayload | null>(null);

  const [error, setError] = useState(false);
  const fetchingRef = useRef(false);
  const fetchCategories = useCallback(async () => {
    if (fetchingRef.current) return;
    fetchingRef.current = true;
    try {
      setLoading(true);
      setError(false);
      const publishedDiyPage = await fetchDiyPage('category', currentDiyPageType()).catch(() => null);
      if (publishedDiyPage?.page && publishedDiyPage.components.length > 0) {
        setDiyPage(publishedDiyPage);
        return;
      }

      setDiyPage(null);
      const result = await getCategoryList();
      const list: CategoryGroup[] = normalizeCategories(Array.isArray(result) ? result : []);
      setActiveIndex((current) => Math.min(current, Math.max(0, list.length - 1)));
      setCategories(list);
    } catch (error) {
      setError(true);
    } finally {
      fetchingRef.current = false;
      setLoading(false);
      Taro.stopPullDownRefresh();
    }
  }, []);

  useDidShow(() => {
    fetchCategories();
  });

  usePullDownRefresh(() => {
    fetchCategories();
  });

  const handleCategoryTap = (index: number) => {
    setActiveIndex(index);
  };

  const handleItemTap = (item: CategoryChild) => {
    const categoryId = item.groupId;
    if (!categoryId) return;
    const categoryName = encodeURIComponent(item.name || '');
    Taro.navigateTo({
      url: `/pages/goods/list/index?categoryId=${encodeURIComponent(categoryId)}&categoryName=${categoryName}`,
    });
  };

  const activeCategory = categories[activeIndex];
  const items = activeCategory ? categoryLeaves(activeCategory) : [];
  const pageStyle = useMemo(() => diyPage?.page ? diyStyle(diyPage.page.style || {}) : {}, [diyPage]);

  if (loading && !diyPage) {
    return renderLoading();
  }

  if (diyPage?.page && diyPage.components.length > 0) {
    return (
      <View className={`category-page category-page--diy ${isH5() ? 'category-page--h5' : ''}`}>
        <PageNav title={diyPage.page.title || '商品分类'} showBack={false} />
        <View className="category-page__body category-page__body--diy" style={pageStyle}>
          <DiyRenderer page={diyPage} transparent />
        </View>
        {isH5() ? <H5TabBar current="/pages/category/index" /> : null}
      </View>
    );
  }

  return (
    <View className={`category-page ${isH5() ? 'category-page--h5' : ''}`}>
      <PageNav title="商品分类" showBack={false} />
      <View className="category-page__body">
        {/* Left Sidebar */}
        <ScrollView
          className="category-sidebar"
          scrollY
          enhanced
          showScrollbar={false}
        >
          {categories.map((cat, index) => (
            <View
              key={`cat-${index}`}
              className={`category-sidebar__item ${index === activeIndex ? 'category-sidebar__item--active' : ''}`}
              onClick={() => handleCategoryTap(index)}
            >
              <Text className="category-sidebar__text">{cat.name || '分类'}</Text>
            </View>
          ))}
        </ScrollView>

        {/* Right Content */}
        <ScrollView
          className="category-content"
          scrollY
          enhanced
          showScrollbar={false}
        >
          {activeCategory && (
            <View className="category-content__header">
              <View className="category-content__title-bar" />
              <Text className="category-content__title">{activeCategory.name || '商品分类'}</Text>
            </View>
          )}

          <View className="category-content__grid">
            {items.map((item, index) => (
              <View
                key={`item-${index}`}
                className="category-card"
                onClick={() => handleItemTap(item)}
              >
                <View className="category-card__img-wrap">
                  <Image
                    className="category-card__img"
                    src={item.thumbnail}
                    mode="aspectFill"
                    lazyLoad
                  />
                </View>
                <Text className="category-card__name">{item.name}</Text>
              </View>
            ))}
          </View>

          {items.length === 0 && !loading && (
            <View className="category-content__empty">
              <Text className="category-content__empty-text">{error ? '分类加载失败' : '暂无分类'}</Text>
              {error ? <View className="category-content__retry" onClick={fetchCategories}>点击重试</View> : null}
            </View>
          )}
        </ScrollView>
      </View>
      {isH5() ? <H5TabBar current="/pages/category/index" /> : null}
    </View>
  );
}
