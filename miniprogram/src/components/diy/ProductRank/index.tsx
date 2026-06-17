import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { imageOf, stopDiyEvent } from '../../diy-renderer/style';
import './index.scss';

interface Props {
  component: DiyComponent<{ products?: DiyProductItem[] }, { title?: string; rankType?: string; limit?: number }>;
}

export default function ProductRank({ component }: Props) {
  const products = component.data?.products || [];
  const fallback = products.length > 0 ? products : [{ name: '热销商品' }, { name: '精选商品' }, { name: '新品商品' }];
  const limit = Number(component.props?.limit || 10);

  return (
    <View className="diy-product-rank">
      <View className="diy-product-rank__title">{component.props?.title || '商品榜单'}</View>
      {fallback.slice(0, limit).map((item, index) => {
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
              {item.price !== undefined ? <Text className="diy-product-rank__price">¥{Number(item.price || 0).toFixed(2)}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}
