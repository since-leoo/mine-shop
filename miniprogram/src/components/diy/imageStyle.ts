import { DiyImageProps } from '../diy-renderer/types';

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
    style.marginLeft = '24px';
    style.marginRight = '24px';
  } else if (widthMode === 'custom') {
    style.width = widthUnit === 'percent' ? `${Math.min(Math.max(width, 1), 100)}%` : `${Math.min(Math.max(width, 1), 750)}px`;
    style.marginLeft = 'auto';
    style.marginRight = 'auto';
  }

  return style;
}

export function imageItemStyle(props?: DiyImageProps, fallbackHeight = 160): Record<string, string | number> {
  const height = Number(props?.height || fallbackHeight);
  const radius = Number(props?.radius ?? 12);

  return {
    height: `${height}px`,
    borderRadius: `${radius}px`,
  };
}
