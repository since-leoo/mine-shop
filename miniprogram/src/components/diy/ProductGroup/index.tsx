import Taro from '@tarojs/taro';
import { Text, View } from '@tarojs/components';
import GoodsList from '../../GoodsList';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
import './index.scss';

interface Props {
  component: DiyComponent<
    { products?: DiyProductItem[]; items?: DiyProductItem[]; mode?: string; source?: string },
    { title?: string; source?: string; sort?: string; categoryId?: string | number; activityId?: string | number; tagIds?: Array<string | number>; limit?: number }
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
    originPrice: Number(item.originPrice || item.linePrice || item.maxLinePrice || item.maxPrice || 0),
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
  const products = (component.data?.products || component.data?.items || []).map(normalizeProduct);

  return (
    <View className="diy-product-group">
      <View className="diy-product-group__head">
        <Text className="diy-product-group__title">{component.props?.title || '商品组'}</Text>
        <Text className="diy-product-group__source">{sourceText(component)}</Text>
      </View>
      {products.length > 0 ? (
        <GoodsList
          goodsList={products}
          onClickGoods={(goods) => {
            if (!goods.spuId) return;
            Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${goods.spuId}` });
          }}
        />
      ) : (
        <View className="diy-product-group__empty">{sourceText(component)}</View>
      )}
    </View>
  );
}
