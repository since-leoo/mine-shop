import { Image, ScrollView, Text, View } from '@tarojs/components';
import { useEffect, useMemo, useState } from 'react';
import { getCategoryList } from '../../../services/good/fetchCategoryList';
import { DiyCategoryPanelItem, DiyComponent } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import './index.scss';

interface Props {
  component: DiyComponent<
    { items?: DiyCategoryPanelItem[] },
    { columns?: number; title?: string; itemGap?: number; needLogin?: boolean }
  >;
}

function getLeafItems(node: any): any[] {
  const children = Array.isArray(node?.children) ? node.children : [];
  if (children.length === 0) return [];

  const leaves: any[] = [];
  const walk = (items: any[]) => {
    for (const item of items) {
      const childNodes = Array.isArray(item?.children) ? item.children : [];
      if (childNodes.length > 0) {
        walk(childNodes);
      } else {
        leaves.push(item);
      }
    }
  };

  walk(children);

  return leaves;
}

function normalizeCategoryPanelItems(nodes: any[] = []): DiyCategoryPanelItem[] {
  return nodes.map((node) => {
    const name = node.name || node.title || '';

    return {
      title: name,
      name,
      children: getLeafItems(node).map((item) => {
        const id = item.groupId || item.categoryId || item.id || '';
        const title = item.name || item.title || '';
        const image = item.thumbnail || item.image || item.icon || '';

        return {
          title,
          name: title,
          image,
          thumbnail: image,
          link: { type: 'category', id },
        };
      }),
    };
  });
}

export default function CategoryPanel({ component }: Props) {
  const [items, setItems] = useState<DiyCategoryPanelItem[]>([]);
  const [activeIndex, setActiveIndex] = useState(component.props?.activeIndex || 0);
  const columns = Math.min(Math.max(Number(component.props?.columns || 3), 2), 4);
  const safeActiveIndex = Math.min(activeIndex, Math.max(items.length - 1, 0));
  const activeCategory = items[safeActiveIndex];
  const children = useMemo(() => activeCategory?.children || [], [activeCategory]);

  useEffect(() => {
    let mounted = true;

    getCategoryList()
      .then((result: any) => {
        if (!mounted) return;
        setItems(normalizeCategoryPanelItems(Array.isArray(result) ? result : []));
      })
      .catch((error: unknown) => {
        console.error('Failed to load category panel:', error);
      });

    return () => {
      mounted = false;
    };
  }, []);

  if (items.length === 0) return null;

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
