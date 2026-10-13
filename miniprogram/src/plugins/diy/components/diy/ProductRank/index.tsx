import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { diyComponentStyle, imageOf, stopDiyEvent } from '../../diy-renderer/style';
import './index.scss';

interface Props {
  component: DiyComponent<{ products?: DiyProductItem[] }, { title?: string; rankType?: string; limit?: number }>;
}

export default function ProductRank({ component }: Props) {
  const products = component.data?.products || [];
  if (products.length === 0) return null;
  const limit = Number(component.props?.limit || 10);

  return (
    <View className="diy-product-rank" style={diyComponentStyle(component)}>
      <View className="diy-product-rank__title">{component.props?.title || '商品榜单'}</View>
      {products.slice(0, limit).map((item, index) => {
        const image = imageOf(item);
        const productId = item.spuId || item.spu_id || item.productId || item.id;

        return (
          <View
            key={`${item.id || index}`}
            className="diy-product-rank__item"
            onClick={(event) => {
              stopDiyEvent(event);
              if (productId) {
                navigateDiyLink({ type: 'product', id: productId }, { needLogin: component.props?.needLogin });
              }
            }}
          >
            <Text className="diy-product-rank__no">{index + 1}</Text>
            <View className="diy-product-rank__image">
              {image ? <Image className="diy-product-rank__thumb" src={image} mode="aspectFill" /> : null}
            </View>
            <View className="diy-product-rank__body">
              <Text className="diy-product-rank__name">{item.name || item.title || '商品'}</Text>
              {(item.price ?? item.min_price ?? item.minPrice) !== undefined ? <Text className="diy-product-rank__price">¥{(Number(item.price ?? item.min_price ?? item.minPrice ?? 0) / 100).toFixed(2)}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}
