import { Text, View } from '@tarojs/components';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
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
      {fallback.slice(0, limit).map((item, index) => (
        <View key={`${item.id || index}`} className="diy-product-rank__item">
          <Text className="diy-product-rank__no">{index + 1}</Text>
          <View className="diy-product-rank__image" />
          <Text className="diy-product-rank__name">{item.name || item.title || '商品'}</Text>
        </View>
      ))}
    </View>
  );
}
