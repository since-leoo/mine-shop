import { View, Text, ScrollView, Image } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useState, useEffect } from 'react';
import { getCategoryList } from '../../services/good/fetchCategoryList';
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

/** Flatten nested children into a flat list of leaf items */
function getLeafItems(category: CategoryGroup): CategoryChild[] {
  if (!category.children || category.children.length === 0) {
    return [];
  }

  const leaves: CategoryChild[] = [];

  function walk(items: any[]) {
    for (const item of items) {
      if (item.children && item.children.length > 0) {
        walk(item.children);
      } else {
        leaves.push({
          groupId: item.groupId || item.categoryId || '',
          name: item.name || item.title || '',
          thumbnail: item.thumbnail || '',
        });
      }
    }
  }

  walk(category.children);
  return leaves;
}

export default function Category() {
  const [categories, setCategories] = useState<CategoryGroup[]>([]);
  const [activeIndex, setActiveIndex] = useState(0);
  const [loading, setLoading] = useState(true);

  const fetchCategories = async () => {
    try {
      setLoading(true);
      const result = await getCategoryList();
      const normalizeTree = (nodes: any[] = []): CategoryGroup[] => {
        return nodes.map((node: any) => ({
          ...node,
          groupId: node.groupId || node.categoryId || '',
          name: node.name || node.title || '',
          thumbnail: node.thumbnail || node.image || '',
          children: Array.isArray(node.children) ? normalizeTree(node.children) : [],
        }));
      };
      const list: CategoryGroup[] = normalizeTree(Array.isArray(result) ? result : []);
      setCategories(list);
    } catch (error) {
      console.error('Failed to load categories:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  useDidShow(() => {
    // TabBar sync can be handled here if needed
  });

  const handleCategoryTap = (index: number) => {
    setActiveIndex(index);
  };

  const handleItemTap = (item: CategoryChild) => {
    const categoryId = item.groupId;
    const categoryName = encodeURIComponent(item.name || '');
    Taro.navigateTo({
      url: `/pages/goods/list/index?categoryId=${categoryId}&categoryName=${categoryName}`,
    });
  };

  const activeCategory = categories[activeIndex];
  const items = activeCategory ? getLeafItems(activeCategory) : [];

  if (loading) {
    return (
      <View className="category-page">
        <View className="category-loading">
          <Text className="category-loading__text">Loading...</Text>
        </View>
      </View>
    );
  }

  return (
    <View className="category-page">
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
            <Text className="category-content__empty-text">暂无商品</Text>
          </View>
        )}
      </ScrollView>
    </View>
  );
}
