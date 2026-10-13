import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyNavItem } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf, schemaSize } from '../../diy-renderer/style';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyNavItem[] }, { columns?: number; rows?: number; iconSize?: number; iconRadius?: number; imageSize?: number; itemGap?: number }>;
}

function hasSvgIcon(item: DiyNavItem, icon: string): boolean {
  const inlineSvg = item.svg || item.svgCode;
  if (typeof inlineSvg === 'string' && /^<svg[\s>]/i.test(inlineSvg.trim())) return true;

  const source = String(item.icon || item.image || icon || '').split('?')[0].split('#')[0];
  return source.endsWith('.svg') || icon.startsWith('data:image/svg+xml');
}

export default function QuickNav({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;
  const columns = Math.min(Math.max(Number(component.props?.columns || 5), 3), 5);
  const rows = Math.min(Math.max(Number(component.props?.rows || 2), 1), 4);
  const limit = columns * rows;
  const itemStyle = { width: `${100 / columns}%` };
  const iconSize = Number(component.props?.iconSize || 48);
  const iconRadius = Number(component.props?.iconRadius ?? 16);
  const imageSize = Number(component.props?.imageSize || 30);
  const itemGap = Number(component.props?.itemGap ?? 6);

  return (
    <View className="diy-quick-nav" style={diyComponentStyle(component)}>
      {items.slice(0, limit).map((item, index) => {
        const icon = imageOf(item);
        const title = item.title || item.name || '';
        const visualImageSize = hasSvgIcon(item, icon) ? Math.round(imageSize * 0.8) : imageSize;

        return (
          <View
            key={`${title}-${index}`}
            className="diy-quick-nav__item"
            style={itemStyle}
            onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
          >
            <View
              className="diy-quick-nav__icon-wrap"
              style={{
                width: schemaSize(iconSize),
                height: schemaSize(iconSize),
                borderRadius: schemaSize(iconRadius),
                background: item.iconBg || undefined,
              }}
            >
              {icon ? <Image className="diy-quick-nav__icon" style={{ width: schemaSize(visualImageSize), height: schemaSize(visualImageSize) }} src={icon} mode="aspectFit" /> : <Text className="diy-quick-nav__icon-text">{item.iconText || title.slice(0, 1)}</Text>}
            </View>
            <Text className="diy-quick-nav__title" style={{ marginTop: schemaSize(itemGap) }}>{title}</Text>
          </View>
        );
      })}
    </View>
  );
}
