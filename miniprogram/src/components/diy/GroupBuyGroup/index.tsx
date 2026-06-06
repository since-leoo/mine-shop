import { Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface GroupBuyItem {
  id?: number | string;
  title?: string;
  group_price?: number;
  min_people?: number;
}

interface Props {
  component: DiyComponent<{ activities?: GroupBuyItem[] }, { title?: string; limit?: number }>;
}

function money(value?: number) {
  return ((Number(value || 0)) / 100).toFixed(2);
}

export default function GroupBuyGroup({ component }: Props) {
  const activities = component.data?.activities || [];
  if (activities.length === 0) return null;
  const limit = Number(component.props?.limit || 6);

  return (
    <View className="diy-group-buy-group">
      <View className="diy-group-buy-group__title">{component.props?.title || '多人拼团'}</View>
      <View className="diy-group-buy-group__list">
        {activities.slice(0, limit).map((item, index) => (
          <View key={`${item.id || index}`} className="diy-group-buy-group__item" onClick={() => navigateDiyLink({ type: 'group_buy', id: item.id })}>
            <View className="diy-group-buy-group__image" />
            <Text className="diy-group-buy-group__name">{item.title || '拼团活动'}</Text>
            <Text className="diy-group-buy-group__price">¥{money(item.group_price)}</Text>
            <Text className="diy-group-buy-group__people">{item.min_people || 2}人成团</Text>
          </View>
        ))}
      </View>
    </View>
  );
}
