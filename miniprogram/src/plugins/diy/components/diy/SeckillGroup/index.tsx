import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyProductItem } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { diyComponentStyle, imageOf, stopDiyEvent } from '../../diy-renderer/style';
import './index.scss';

interface SessionItem {
  id?: number | string;
  activity_id?: number | string;
  activityId?: number | string;
  title?: string;
  start_time?: string;
  end_time?: string;
}

interface Props {
  component: DiyComponent<{ session?: SessionItem; products?: DiyProductItem[]; items?: DiyProductItem[] }, { title?: string; limit?: number; layout?: string }>;
}

export default function SeckillGroup({ component }: Props) {
  const session = component.data?.session;
  // request 会将后端下划线字段转换为 camelCase，也兼容旧装修 schema。
  const activityId = session?.activityId ?? session?.activity_id;
  if (!session || (!session.id && !activityId)) return null;
  const limit = Math.max(Number(component.props?.limit || 3), 1);
  const layout = component.props?.layout || 'scroll';
  const products = (component.data?.products || component.data?.items || []).slice(0, limit);

  return (
    <View
      className={`diy-seckill-group diy-seckill-group--${layout}`}
      style={diyComponentStyle(component)}
      onClick={(event) => {
        stopDiyEvent(event);
        navigateDiyLink({ type: 'page', path: '/pages/promotion/detail/index', params: { activityId, sessionId: session.id } }, { needLogin: component.props?.needLogin });
      }}
    >
      <View className="diy-seckill-group__head">
        <Text className="diy-seckill-group__title">{component.props?.title || '限时秒杀'}</Text>
        <Text className="diy-seckill-group__session">{session.title || '秒杀场次'}</Text>
      </View>
      <View className="diy-seckill-group__list">
        {products.map((item, index) => {
          const image = imageOf(item);
          const productId = item.spuId || item.spu_id || item.productId || item.id;

          return (
            <View
              key={`${item.id || index}`}
              className="diy-seckill-group__item"
              onClick={(event) => {
                stopDiyEvent(event);
                if (productId && !String(productId).startsWith('placeholder-')) {
                  navigateDiyLink({ type: 'page', path: '/pages/goods/details/index', params: { spuId: productId, orderType: 'seckill', activityId, sessionId: session.id } }, { needLogin: component.props?.needLogin });
                }
              }}
            >
              <View className="diy-seckill-group__image">
                {image ? <Image className="diy-seckill-group__thumb" src={image} mode="aspectFill" /> : null}
              </View>
              <Text className="diy-seckill-group__name">{item.title || item.name || '秒杀商品'}</Text>
              <Text className="diy-seckill-group__price">¥{(Number(item.price ?? item.salePrice ?? 0) / 100).toFixed(2)}</Text>
            </View>
          );
        })}
      </View>
    </View>
  );
}
