import { Text, View } from '@tarojs/components';
import { DiyComponent, DiyMarketingEntryItem } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick } from '../../diy-renderer/style';
import './index.scss';

interface Props {
  component: DiyComponent<
    { items?: DiyMarketingEntryItem[] },
    { title?: string; layout?: string; itemGap?: number; cardRadius?: number; needLogin?: boolean }
  >;
}

export default function MarketingEntry({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  const title = component.props?.title || '今日活动直达';
  const itemGap = Number(component.props?.itemGap ?? 10);
  const cardRadius = Number(component.props?.cardRadius ?? 10);

  return (
    <View className="diy-marketing-entry" style={diyComponentStyle(component)}>
      <View className="diy-marketing-entry__head">
        <View className="diy-marketing-entry__mark" />
        <Text className="diy-marketing-entry__title">{title}</Text>
      </View>
      <View className="diy-marketing-entry__grid" style={{ gap: `${itemGap * 2}rpx` }}>
        {items.slice(0, 4).map((item, index) => (
          <View
            key={`${item.title || 'entry'}-${index}`}
            className="diy-marketing-entry__card"
            style={{
              background: item.background || (index % 2 === 0 ? '#F0A18E' : '#86BFA9'),
              borderRadius: `${cardRadius * 2}rpx`,
              color: item.color || '#FFFFFF',
            }}
            onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
          >
            <Text className="diy-marketing-entry__card-title">{item.title || '活动入口'}</Text>
            <Text className="diy-marketing-entry__subtitle">{item.subtitle || '点击进入专题页'}</Text>
            <Text className="diy-marketing-entry__badge">{item.badge || '立即查看'}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}
