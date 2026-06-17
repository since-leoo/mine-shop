import { Image, Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { imageOf, stopDiyEvent } from '../../diy-renderer/style';
import './index.scss';

interface GroupBuyItem {
  id?: number | string;
  title?: string;
  group_price?: number;
  groupPrice?: number;
  price?: number;
  min_people?: number;
  minPeople?: number;
  image?: string;
  thumb?: string;
}

interface Props {
  component: DiyComponent<{ activities?: GroupBuyItem[]; items?: GroupBuyItem[] }, { title?: string; limit?: number; layout?: string }>;
}

function money(value?: number) {
  return ((Number(value || 0)) / 100).toFixed(2);
}

export default function GroupBuyGroup({ component }: Props) {
  const activities = component.data?.activities || component.data?.items || [];
  if (activities.length === 0) return null;
  const limit = Number(component.props?.limit || 6);
  const layout = component.props?.layout || 'two-column';

  return (
    <View className={`diy-group-buy-group diy-group-buy-group--${layout}`}>
      <View className="diy-group-buy-group__title">{component.props?.title || '多人拼团'}</View>
      <View className="diy-group-buy-group__list">
        {activities.slice(0, limit).map((item, index) => {
          const image = imageOf(item);
          const price = item.group_price || item.groupPrice || item.price;

          return (
            <View
              key={`${item.id || index}`}
              className="diy-group-buy-group__item"
              onClick={(event) => {
                stopDiyEvent(event);
                navigateDiyLink({ type: 'group_buy', id: item.id }, { needLogin: component.props?.needLogin });
              }}
            >
              <View className="diy-group-buy-group__image">
                {image ? <Image className="diy-group-buy-group__thumb" src={image} mode="aspectFill" /> : null}
              </View>
              <Text className="diy-group-buy-group__name">{item.title || '拼团活动'}</Text>
              <Text className="diy-group-buy-group__price">¥{money(price)}</Text>
              <Text className="diy-group-buy-group__people">{item.min_people || item.minPeople || 2}人成团</Text>
            </View>
          );
        })}
      </View>
    </View>
  );
}
