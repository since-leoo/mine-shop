import { isH5 } from './platform';

/** 装修尺寸使用 375px 设计稿单位；JSX 动态值不会经过 Taro 的 CSS 编译。 */
export function pixelSize(value: number): string {
  return isH5() ? `${value}PX` : `${value * 2}rpx`;
}

export function cssLength(value: string): string {
  return value.replace(/(-?\d*\.?\d+)(rpx|px)\b/gi, (_, amount: string, unit: string) => (
    unit.toLowerCase() === 'rpx'
      ? isH5() ? `${Number(amount) / 2}PX` : `${Number(amount)}rpx`
      : pixelSize(Number(amount))
  ));
}
