import { Text, View } from '@tarojs/components';
import GoodsList from '../../GoodsList';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { diyComponentStyle } from '../../diy-renderer/style';
import { getGoodsList } from '../../../model/goods';
import './index.scss';

interface Props {
  component: DiyComponent<
    { products?: DiyProductItem[]; items?: DiyProductItem[]; mode?: string; source?: string },
    {
      title?: string;
      source?: string;
      sort?: string;
      categoryId?: string | number;
      activityId?: string | number;
      tagIds?: Array<string | number>;
      limit?: number;
      layout?: string;
      variant?: string;
      listPadding?: string | number | { top?: number; right?: number; bottom?: number; left?: number };
      gap?: number;
      showHeader?: boolean;
      showSource?: boolean;
    }
  >;
}

function normalizeProduct(item: DiyProductItem, index: number) {
  const spuId = item.spuId || item.spu_id || item.id || item.productId || index;

  return {
    ...item,
    id: item.id || spuId,
    spuId,
    skuId: item.skuId || item.sku_id || item.defaultSkuId || item.id || spuId,
    thumb: item.thumb || item.image || item.primaryImage || item.mainImage || item.main_image || '',
    title: item.title || item.name || item.goodsName || '',
    price: Number(item.price || item.salePrice || item.minSalePrice || item.minPrice || 0),
    originPrice: Number(item.originPrice || item.linePrice || item.minLinePrice || item.maxLinePrice || item.maxPrice || 0),
    tags: item.tags || item.spuTagList?.map((tag) => tag.title).filter(Boolean) || [],
  };
}

function sourceText(component: Props['component']): string {
  const source = component.props?.source || component.data?.source || component.data?.mode || 'recommend';
  const map: Record<string, string> = {
    manual: '手动商品',
    recommend: '推荐商品',
    hot: '热卖商品',
    new: '新品商品',
    category: `分类 ${component.props?.categoryId || '未选择'}`,
    tag: '标签商品',
    activity: `活动 ${component.props?.activityId || '未选择'}`,
  };
  return map[source] || '推荐商品';
}

export default function ProductGroup({ component }: Props) {
  const limit = Math.max(Number(component.props?.limit || 6), 1);
  const layout = component.props?.layout || 'two-column';
  const variant = component.props?.variant || '';
  const sourceProducts = component.data?.products || component.data?.items || [];
  const fallbackProducts = sourceProducts.length > 0 ? [] : getGoodsList(0, limit);
  const products = (sourceProducts.length > 0 ? sourceProducts : fallbackProducts).map(normalizeProduct).slice(0, limit);
  const showHeader = component.props?.showHeader === true || Boolean(component.props?.title);
  const showSource = component.props?.showSource === true;

  return (
    <View className={`diy-product-group diy-product-group--${layout} ${variant ? `diy-product-group--${variant}` : ''}`} style={diyComponentStyle(component)}>
      {showHeader ? (
        <View className="diy-product-group__head">
          <Text className="diy-product-group__title">{component.props?.title}</Text>
          {showSource ? <Text className="diy-product-group__source">{sourceText(component)}</Text> : null}
        </View>
      ) : null}
      {products.length > 0 ? (
        <GoodsList
          goodsList={products}
          layout={layout}
          gap={component.props?.gap}
          padding={component.props?.listPadding}
          onClickGoods={(goods) => {
            if (!goods.spuId) return;
            navigateDiyLink({ type: 'product', id: goods.spuId }, { needLogin: component.props?.needLogin });
          }}
        />
      ) : (
        <View className="diy-product-group__empty">{sourceText(component)}</View>
      )}
    </View>
  );
}
