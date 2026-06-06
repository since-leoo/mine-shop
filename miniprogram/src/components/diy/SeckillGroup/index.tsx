import { Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface SessionItem {
  id?: number | string;
  activity_id?: number | string;
  title?: string;
  start_time?: string;
  end_time?: string;
}

interface Props {
  component: DiyComponent<{ session?: SessionItem }, { title?: string; limit?: number }>;
}

export default function SeckillGroup({ component }: Props) {
  const session = component.data?.session;
  if (!session?.id && !session?.activity_id) return null;

  return (
    <View className="diy-seckill-group" onClick={() => navigateDiyLink({ type: 'seckill', id: session.activity_id || session.id })}>
      <View className="diy-seckill-group__head">
        <Text className="diy-seckill-group__title">{component.props?.title || '限时秒杀'}</Text>
        <Text className="diy-seckill-group__session">{session.title || '秒杀场次'}</Text>
      </View>
      <View className="diy-seckill-group__list">
        {Array.from({ length: Math.min(Number(component.props?.limit || 3), 3) }).map((_, index) => (
          <View key={index} className="diy-seckill-group__item">
            <View className="diy-seckill-group__image" />
            <Text className="diy-seckill-group__name">秒杀商品</Text>
            <Text className="diy-seckill-group__price">¥0.00</Text>
          </View>
        ))}
      </View>
    </View>
  );
}
