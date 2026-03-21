import { View, Text, Image } from '@tarojs/components';
import { useMemo } from 'react';
import Price from '../Price';
import './index.scss';

export interface GoodsData {
  id?: string | number;
  thumb?: string;
  title?: string;
  price?: number;
  originPrice?: number;
  tags?: string[];
  [key: string]: any;
}

interface GoodsCardProps {
  data: GoodsData;
  currency?: string;
  onClick?: (goods: GoodsData) => void;
  onAddCart?: (goods: GoodsData) => void;
}

export default function GoodsCard({
  data,
  currency = '¥',
  onClick,
  onAddCart,
}: GoodsCardProps) {
  const isValidityLinePrice = useMemo(() => {
    if (data.originPrice && data.price && data.originPrice < data.price) {
      return false;
    }
    return true;
  }, [data.originPrice, data.price]);

  const handleClick = () => {
    onClick?.(data);
  };

  const handleAddCart = (e) => {
    e.stopPropagation();
    onAddCart?.(data);
  };

  return (
    <View className="goods-card" onClick={handleClick}>
      <View className="goods-card__main">
        <View className="goods-card__thumb">
          {data.thumb && (
            <Image
              className="goods-card__img"
              src={data.thumb}
              mode="aspectFill"
              lazyLoad
            />
          )}
        </View>
        <View className="goods-card__body">
          <View className="goods-card__upper">
            {data.title && (
              <View className="goods-card__title">{data.title}</View>
            )}
            {data.tags && data.tags.length > 0 && (
              <View className="goods-card__tags">
                {data.tags.map((tag, index) => (
                  <Text key={index} className="goods-card__tag">
                    {tag}
                  </Text>
                ))}
              </View>
            )}
          </View>
          <View className="goods-card__down">
            {data.price != null && (
              <Price
                price={data.price}
                symbol={currency}
                className="goods-card__price"
              />
            )}
            {data.originPrice != null && isValidityLinePrice && (
              <Price
                price={data.originPrice}
                symbol={currency}
                type="delthrough"
                className="goods-card__origin-price"
              />
            )}
            <View className="goods-card__add-cart" onClick={handleAddCart}>
              <Text className="goods-card__add-cart-icon">+</Text>
            </View>
          </View>
        </View>
      </View>
    </View>
  );
}
