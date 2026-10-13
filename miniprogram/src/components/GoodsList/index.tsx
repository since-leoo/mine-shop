import { View } from '@tarojs/components';
import GoodsCard, { GoodsData } from '../GoodsCard';
import { cssLength, pixelSize } from '../../common/style-units';
import './index.scss';

interface GoodsListProps {
  goodsList: GoodsData[];
  layout?: 'two-column' | 'single' | 'scroll' | string;
  gap?: number;
  padding?: string | number | { top?: number; right?: number; bottom?: number; left?: number };
  onClickGoods?: (goods: GoodsData) => void;
  onAddCart?: (goods: GoodsData) => void;
}

function size(value?: number): string | undefined {
  return value === undefined ? undefined : pixelSize(Number(value));
}

function edgeValue(value: GoodsListProps['padding']): string | undefined {
  if (value === undefined || value === null || value === '') return undefined;
  if (typeof value === 'number') return size(value);
  if (typeof value === 'string') return cssLength(value);
  const top = size(value.top ?? 0) || '0';
  const right = size(value.right ?? 16) || pixelSize(16);
  const bottom = size(value.bottom ?? 12) || pixelSize(12);
  const left = size(value.left ?? 16) || pixelSize(16);
  return `${top} ${right} ${bottom} ${left}`;
}

export default function GoodsList({
  goodsList,
  layout = 'two-column',
  gap,
  padding,
  onClickGoods,
  onAddCart,
}: GoodsListProps) {
  return (
    <View className={`goods-list goods-list--${layout}`} style={{ gap: size(gap), padding: edgeValue(padding) }}>
      {goodsList.map((item, index) => (
        <View key={item.id ?? index} className="goods-list__item" onClick={(event) => event.stopPropagation()}>
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
