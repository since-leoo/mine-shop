import { View } from '@tarojs/components';
import GoodsCard, { GoodsData } from '../GoodsCard';
import './index.scss';

interface GoodsListProps {
  goodsList: GoodsData[];
  onClickGoods?: (goods: GoodsData) => void;
  onAddCart?: (goods: GoodsData) => void;
}

export default function GoodsList({
  goodsList,
  onClickGoods,
  onAddCart,
}: GoodsListProps) {
  return (
    <View className="goods-list">
      {goodsList.map((item, index) => (
        <View key={item.id ?? index} className="goods-list__item">
          <GoodsCard
            data={item}
            currency={item.currency || '¥'}
            onClick={onClickGoods}
            onAddCart={onAddCart}
          />
        </View>
      ))}
    </View>
  );
}
