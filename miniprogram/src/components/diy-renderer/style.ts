import { DiyComponent, DiyLink } from './types';
import { navigateDiyLink } from './link';
import { resolveDiyAsset } from './assets';

type StyleValue = string | number | undefined;
type StyleRecord = Record<string, any>;

function definedStyle(style: Record<string, StyleValue>): Record<string, StyleValue> {
  return Object.fromEntries(
    Object.entries(style).filter(([, value]) => value !== undefined),
  ) as Record<string, StyleValue>;
}

function cssValue(value: string): string {
  return value.replace(/(-?\d*\.?\d+)px\b/g, (_, amount: string) => `${Number(amount) * 2}rpx`);
}

function schemaSize(value: any): string | undefined {
  if (value === undefined || value === null || value === '') return undefined;
  const amount = Number(value);
  return Number.isFinite(amount) ? `${amount * 2}rpx` : undefined;
}

function schemaValue(value: any): string | undefined {
  if (value === undefined || value === null || value === '') return undefined;
  if (typeof value === 'number') return schemaSize(value);
  return cssValue(String(value));
}

function pickStyleValue(source: StyleRecord, keys: string[]): any {
  for (const key of keys) {
    if (source[key] !== undefined && source[key] !== null && source[key] !== '') {
      return source[key];
    }
  }

  return undefined;
}

const legacyUserProfileHeaderBackground = 'linear-gradient(180deg, #EF8D78 0%, #F3A896 48%, #F8D7BF 78%, #FFF4EA 100%)';
const themedUserProfileHeaderBackground = 'linear-gradient(180deg, var(--diy-primary-color, #E8836B) 0%, var(--diy-primary-color, #E8836B) 56%, var(--diy-page-background-color, #FAF3ED) 100%)';

function schemaBackgroundValue(component: DiyComponent, style: StyleRecord): string | undefined {
  const background = style.background || style.backgroundColor;
  if (component.type === 'user-profile-header' && background === legacyUserProfileHeaderBackground) {
    return themedUserProfileHeaderBackground;
  }

  return schemaValue(background);
}

function edgeStyle(style: StyleRecord, key: 'margin' | 'padding'): Record<string, string | undefined> {
  const value = style[key];
  const capitalized = key === 'margin' ? 'Margin' : 'Padding';

  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return {
      [`${key}Top`]: schemaSize(value.top),
      [`${key}Right`]: schemaSize(value.right),
      [`${key}Bottom`]: schemaSize(value.bottom),
      [`${key}Left`]: schemaSize(value.left),
    };
  }

  return {
    [key]: schemaValue(value),
    [`${key}Top`]: schemaSize(style[`${key}Top`] ?? style[`${capitalized}Top`]),
    [`${key}Right`]: schemaSize(style[`${key}Right`] ?? style[`${capitalized}Right`]),
    [`${key}Bottom`]: schemaSize(style[`${key}Bottom`] ?? style[`${capitalized}Bottom`]),
    [`${key}Left`]: schemaSize(style[`${key}Left`] ?? style[`${capitalized}Left`]),
  };
}

function shadowStyle(style: StyleRecord): string | undefined {
  if (typeof style.boxShadow === 'string') return cssValue(style.boxShadow);
  if (typeof style.shadow === 'string') return cssValue(style.shadow);
  if (style.shadow && typeof style.shadow === 'object') {
    const x = schemaSize(style.shadow.x ?? 0) || '0';
    const y = schemaSize(style.shadow.y ?? 2) || '4rpx';
    const blur = schemaSize(style.shadow.blur ?? 8) || '16rpx';
    const spread = schemaSize(style.shadow.spread);
    const color = style.shadow.color || 'rgba(200, 140, 110, 0.08)';
    return [x, y, blur, spread, color].filter(Boolean).join(' ');
  }
  return undefined;
}

function radiusStyle(style: StyleRecord): Record<string, string | undefined> {
  const value = pickStyleValue(style, ['borderRadius', 'border_radius', 'border-radius']);
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return {
      borderTopLeftRadius: schemaSize(pickStyleValue(value, ['topLeft', 'top_left', 'top-left'])),
      borderTopRightRadius: schemaSize(pickStyleValue(value, ['topRight', 'top_right', 'top-right'])),
      borderBottomRightRadius: schemaSize(pickStyleValue(value, ['bottomRight', 'bottom_right', 'bottom-right'])),
      borderBottomLeftRadius: schemaSize(pickStyleValue(value, ['bottomLeft', 'bottom_left', 'bottom-left'])),
    };
  }

  return {
    borderRadius: schemaSize(value),
    borderTopLeftRadius: schemaSize(pickStyleValue(style, ['borderTopLeftRadius', 'border_top_left_radius', 'border-top-left-radius'])),
    borderTopRightRadius: schemaSize(pickStyleValue(style, ['borderTopRightRadius', 'border_top_right_radius', 'border-top-right-radius'])),
    borderBottomRightRadius: schemaSize(pickStyleValue(style, ['borderBottomRightRadius', 'border_bottom_right_radius', 'border-bottom-right-radius'])),
    borderBottomLeftRadius: schemaSize(pickStyleValue(style, ['borderBottomLeftRadius', 'border_bottom_left_radius', 'border-bottom-left-radius'])),
  };
}

export function diyComponentStyle(component: DiyComponent): Record<string, StyleValue> {
  const style = component.style || {};
  return definedStyle({
    ...edgeStyle(style, 'margin'),
    ...edgeStyle(style, 'padding'),
    ...radiusStyle(style),
    width: schemaValue(style.width),
    minWidth: schemaValue(style.minWidth),
    maxWidth: schemaValue(style.maxWidth),
    height: schemaValue(style.height),
    minHeight: schemaValue(style.minHeight),
    maxHeight: schemaValue(style.maxHeight),
    background: schemaBackgroundValue(component, style),
    boxShadow: shadowStyle(style),
    color: style.color,
    textAlign: style.textAlign,
    fontSize: schemaSize(style.fontSize),
    fontFamily: style.fontFamily || undefined,
    fontWeight: style.fontWeight,
    position: style.position,
    zIndex: style.zIndex,
  });
}

export function diyStyle(style: StyleRecord = {}): Record<string, StyleValue> {
  return definedStyle({
    ...edgeStyle(style, 'margin'),
    ...edgeStyle(style, 'padding'),
    ...radiusStyle(style),
    width: schemaValue(style.width),
    minWidth: schemaValue(style.minWidth),
    maxWidth: schemaValue(style.maxWidth),
    height: schemaValue(style.height),
    minHeight: schemaValue(style.minHeight),
    maxHeight: schemaValue(style.maxHeight),
    background: schemaValue(style.background || style.backgroundColor),
    boxShadow: shadowStyle(style),
    color: style.color,
    textAlign: style.textAlign,
    fontSize: schemaSize(style.fontSize),
    lineHeight: schemaValue(style.lineHeight),
    letterSpacing: schemaSize(style.letterSpacing),
    fontFamily: style.fontFamily || undefined,
    fontWeight: style.fontWeight,
  });
}

export function diyTextStyle(component: DiyComponent): Record<string, StyleValue> {
  const style = component.style || {};
  return definedStyle({
    color: style.color,
    textAlign: style.textAlign,
    fontSize: schemaSize(style.fontSize),
    lineHeight: schemaValue(style.lineHeight),
    letterSpacing: schemaSize(style.letterSpacing),
    fontFamily: style.fontFamily || undefined,
    fontWeight: style.fontWeight,
  });
}

export function diyBackgroundStyle(component: DiyComponent): Record<string, StyleValue> {
  const style = component.style || {};
  return definedStyle({
    background: schemaValue(style.background || style.backgroundColor),
    ...radiusStyle(style),
  });
}

function svgToDataUri(value: any): string {
  if (typeof value !== 'string') return '';
  const svg = value.trim();
  if (!/^<svg[\s>]/i.test(svg)) return '';

  const sanitized = svg
    .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
    .replace(/\son[a-z]+\s*=\s*(['"]).*?\1/gi, '')
    .replace(/\s(href|xlink:href)\s*=\s*(['"])\s*javascript:.*?\2/gi, '');
  const normalized = /\sxmlns=/.test(sanitized)
    ? sanitized
    : sanitized.replace(/^<svg\b/i, '<svg xmlns="http://www.w3.org/2000/svg"');

  return `data:image/svg+xml,${encodeURIComponent(normalized)}`;
}

export function imageOf(item?: Record<string, any>): string {
  const svg = svgToDataUri(item?.svg || item?.svgCode);
  if (svg) return svg;

  return resolveDiyAsset(item?.image || item?.icon || item?.img || item?.url || item?.thumb || item?.mainImage || item?.main_image || item?.cover || '');
}

export function handleComponentClick(component: DiyComponent): void {
  const link = component.props?.link as DiyLink | undefined;
  if (!link || !link.type || link.type === 'none') return;
  navigateDiyLink(link, { needLogin: component.props?.needLogin });
}

export function stopDiyEvent(event?: { stopPropagation?: () => void }): void {
  event?.stopPropagation?.();
}

export function handleDiyLinkClick(
  event: { stopPropagation?: () => void } | undefined,
  link: DiyLink | undefined,
  needLogin?: boolean,
): void {
  stopDiyEvent(event);
  navigateDiyLink(link, { needLogin });
}
