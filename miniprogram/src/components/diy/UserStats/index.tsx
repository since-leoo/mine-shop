import { Text, View } from '@tarojs/components';
import { DiyComponent, DiyLink } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick } from '../../diy-renderer/style';
import './index.scss';

interface UserStatItem {
  label?: string;
  value?: string | number;
  link?: DiyLink;
}

interface Props {
  component: DiyComponent<{ items?: UserStatItem[] }>;
}

export default function UserStats({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-user-stats" style={diyComponentStyle(component)}>
      {items.slice(0, 4).map((item, index) => (
        <View
          key={`${item.label || 'stat'}-${index}`}
          className="diy-user-stats__item"
          onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
        >
          <Text className="diy-user-stats__value">{item.value ?? '0'}</Text>
          <Text className="diy-user-stats__label">{item.label || '统计'}</Text>
        </View>
      ))}
    </View>
  );
}
