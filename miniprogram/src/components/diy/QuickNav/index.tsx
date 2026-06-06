import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyNavItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface Props {
  component: DiyComponent<{ items?: DiyNavItem[] }>;
}

export default function QuickNav({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-quick-nav">
      {items.slice(0, 10).map((item, index) => {
        const icon = item.icon || item.image || '';
        const title = item.title || item.name || '';

        return (
          <View key={`${title}-${index}`} className="diy-quick-nav__item" onClick={() => navigateDiyLink(item.link)}>
            <View className="diy-quick-nav__icon-wrap">
              {icon ? <Image className="diy-quick-nav__icon" src={icon} mode="aspectFit" /> : null}
            </View>
            <Text className="diy-quick-nav__title">{title}</Text>
          </View>
        );
      })}
    </View>
  );
}
