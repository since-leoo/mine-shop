import { Image, ScrollView, Text, View } from '@tarojs/components';
import { useEffect, useMemo, useState } from 'react';
import { getCategoryList } from '../../../../../services/good/fetchCategoryList';
import { DiyCategoryPanelItem, DiyComponent } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import { categoryLeaves } from './categories';
import './index.scss';

interface Props {
  component: DiyComponent<
    { items?: DiyCategoryPanelItem[] },
    { activeIndex?: number; columns?: number; title?: string; itemGap?: number; needLogin?: boolean }
  >;
}

export function normalizeCategoryPanelItems(nodes: any[] = []): DiyCategoryPanelItem[] {
  return nodes.map((node) => {
    const name = node.name || node.title || '';

    return {
      title: name,
      name,
      children: categoryLeaves(node).map((item) => {
        const id = item.groupId || item.categoryId || item.id || '';
        const title = item.name || item.title || '';
        const image = item.thumbnail || item.image || item.icon || '';

        return {
          title,
          name: title,
          image,
          thumbnail: image,
          link: item.link || { type: 'category', id },
        };
      }),
    };
  });
}

export default function CategoryPanel({ component }: Props) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [retry, setRetry] = useState(0);
  const [items, setItems] = useState<DiyCategoryPanelItem[]>([]);
  const [activeIndex, setActiveIndex] = useState(component.props?.activeIndex || 0);
  const columns = Math.min(Math.max(Number(component.props?.columns || 3), 2), 4);
  const safeActiveIndex = Math.max(0, Math.min(activeIndex, Math.max(items.length - 1, 0)));
  const activeCategory = items[safeActiveIndex];
  const children = useMemo(() => activeCategory?.children || [], [activeCategory]);

  useEffect(() => {
    let mounted = true;
    if (component.data?.items?.length) {
      setItems(normalizeCategoryPanelItems(component.data.items));
      setLoading(false);
      setError(false);
      return;
    }
    setLoading(true);
    setError(false);
    getCategoryList()
      .then((result: any) => {
        if (!mounted) return;
        setItems(normalizeCategoryPanelItems(Array.isArray(result) ? result : []));
      })
      .catch((error: unknown) => {
        if (mounted) setError(true);
      }).finally(() => { if (mounted) setLoading(false); });

    return () => {
      mounted = false;
    };
  }, [component.data?.items, retry]);

  if (items.length === 0) return (
    <View className="diy-category-panel__state">
      <Text>{loading ? '分类加载中…' : error ? '分类加载失败' : '暂无分类'}</Text>
      {error ? <View className="diy-category-panel__retry" onClick={() => setRetry((value) => value + 1)}>点击重试</View> : null}
    </View>
  );

  return (
    <View className="diy-category-panel" style={diyComponentStyle(component)}>
      <ScrollView className="diy-category-panel__sidebar" scrollY enhanced showScrollbar={false}>
        {items.map((item, index) => (
          <View
            key={`${item.title || item.name || 'category'}-${index}`}
            className={`diy-category-panel__tab ${index === safeActiveIndex ? 'diy-category-panel__tab--active' : ''}`}
            onClick={() => setActiveIndex(index)}
          >
            <Text className="diy-category-panel__tab-text">{item.title || item.name || '分类'}</Text>
          </View>
        ))}
      </ScrollView>
      <ScrollView className="diy-category-panel__content" scrollY enhanced showScrollbar={false}>
        <View className="diy-category-panel__head">
          <View className="diy-category-panel__mark" />
          <Text className="diy-category-panel__title">{activeCategory?.title || activeCategory?.name || component.props?.title || '商品分类'}</Text>
        </View>
        <View className="diy-category-panel__grid" style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}>
          {children.map((item, index) => {
            const title = item.title || item.name || '分类';
            const image = imageOf(item);

            return (
              <View
                key={`${title}-${index}`}
                className="diy-category-panel__card"
                onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
              >
                <View className="diy-category-panel__image-wrap">
                  {image ? <Image className="diy-category-panel__image" src={image} mode="aspectFill" lazyLoad /> : null}
                </View>
                <Text className="diy-category-panel__name">{title}</Text>
              </View>
            );
          })}
        </View>
        {children.length === 0 ? (
          <View className="diy-category-panel__empty">
            <Text className="diy-category-panel__empty-text">暂无分类</Text>
          </View>
        ) : null}
      </ScrollView>
    </View>
  );
}
