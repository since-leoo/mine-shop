import { View, Text } from '@tarojs/components';
import { useMemo } from 'react';
import './index.scss';

interface PriceProps {
  price: number | string;
  symbol?: string;
  type?: string;
  fill?: boolean;
  decimalSmaller?: boolean;
  priceUnit?: 'fen' | 'yuan';
  className?: string;
}

export default function Price({
  price,
  symbol = '¥',
  type = '',
  fill = false,
  decimalSmaller = false,
  priceUnit = 'fen',
  className = '',
}: PriceProps) {
  const pArr = useMemo(() => {
    let p = parseFloat(`${price}`);
    if (isNaN(p)) return ['', ''];
    const isMinus = p < 0;
    if (isMinus) p = -p;
    let integer: string, decimal: string;
    if (priceUnit === 'yuan') {
      const parts = p.toString().split('.');
      integer = parts[0];
      decimal = !parts[1]
        ? '00'
        : parts[1].length === 1
          ? `${parts[1]}0`
          : parts[1];
    } else {
      p = Math.round(p * 10 ** 8) / 10 ** 8;
      p = Math.ceil(p);
      integer = p >= 100 ? `${p}`.slice(0, -2) : '0';
      decimal = `${p + 100}`.slice(-2);
    }
    if (!fill) {
      if (decimal === '00') decimal = '';
      else if (decimal[1] === '0') decimal = decimal[0];
    }
    if (isMinus) integer = `-${integer}`;
    return [integer, decimal];
  }, [price, fill, priceUnit]);

  return (
    <View className={`price ${type} ${className}`}>
      {type === 'delthrough' && <View className="price__line" />}
      <Text className="price__symbol">{symbol}</Text>
      <Text className="price__integer">{pArr[0]}</Text>
      {pArr[1] && (
        <Text
          className={`price__decimal ${decimalSmaller ? 'price__decimal--smaller' : ''}`}
        >
          .{pArr[1]}
        </Text>
      )}
    </View>
  );
}
