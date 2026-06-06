import Taro from '@tarojs/taro';
import { View } from '@tarojs/components';
import GoodsList from '../../GoodsList';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
import './index.scss';

interface Props {
  component: DiyComponent<{ products?: DiyProductItem[]; items?: DiyProductItem[] }>;
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

export default function ProductGroup({ component }: Props) {
  const products = (component.data?.products || component.data?.items || []).map(normalizeProduct);
  if (products.length === 0) return null;

  return (
    <View className="diy-product-group">
      <GoodsList
        goodsList={products}
        onClickGoods={(goods) => {
          if (!goods.spuId) return;
          Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${goods.spuId}` });
        }}
      />
    </View>
  );
}
