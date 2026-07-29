import { DiyImageProps } from '../diy-renderer/types';
import { isH5 } from '../../common/platform';

function platformSize(value: number): string {
  return isH5() ? `${value}PX` : `${value * 2}rpx`;
}

export function imageMode(objectFit?: string): 'aspectFill' | 'aspectFit' | 'scaleToFill' {
  if (objectFit === 'contain') return 'aspectFit';
  if (objectFit === 'fill') return 'scaleToFill';
  return 'aspectFill';
}

export function imageContainerStyle(props?: DiyImageProps, fallbackHeight = 160): Record<string, string | number> {
  return {
    ...imageOuterStyle(props),
    ...imageItemStyle(props, fallbackHeight),
  };
}

export function imageOuterStyle(props?: DiyImageProps): Record<string, string | number> {
  const widthMode = props?.widthMode || 'full';
  const widthUnit = props?.widthUnit || 'percent';
  const width = Number(props?.width || 100);
  const style: Record<string, string | number> = {};

  if (widthMode === 'contained') {
    style.marginLeft = '32rpx';
    style.marginRight = '32rpx';
  } else if (widthMode === 'custom') {
    const customWidth = Math.min(Math.max(width, 1), 750);
    style.width = widthUnit === 'percent'
      ? `${Math.min(Math.max(width, 1), 100)}%`
      : isH5() ? `${customWidth / 2}PX` : `${customWidth}rpx`;
    style.marginLeft = 'auto';
    style.marginRight = 'auto';
  }

  return style;
}

export function imageItemStyle(props?: DiyImageProps, fallbackHeight = 160): Record<string, string | number> {
  const height = Number(props?.height || fallbackHeight);
  const radius = Number(props?.radius ?? 12);

  return {
    height: platformSize(height),
    borderRadius: platformSize(radius),
  };
}
