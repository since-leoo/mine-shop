import { Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { stopDiyEvent } from '../../diy-renderer/style';
import './index.scss';

interface CouponItem {
  id?: number | string;
  name?: string;
  value?: number;
  min_amount?: number;
  minAmount?: number;
}

interface Props {
  component: DiyComponent<{ coupons?: CouponItem[] }, { title?: string; limit?: number; layout?: string }>;
}

function money(value?: number) {
  return ((Number(value || 0)) / 100).toFixed(0);
}

export default function CouponGroup({ component }: Props) {
  const coupons = component.data?.coupons || [];
  if (coupons.length === 0) return null;
  const limit = Number(component.props?.limit || 3);
  const layout = component.props?.layout || 'scroll';

  return (
    <View className={`diy-coupon-group diy-coupon-group--${layout}`}>
      <View className="diy-coupon-group__title">{component.props?.title || '领券中心'}</View>
      <View className="diy-coupon-group__list">
        {coupons.slice(0, limit).map((item, index) => (
          <View
            key={`${item.id || index}`}
            className="diy-coupon-group__item"
            onClick={(event) => {
              stopDiyEvent(event);
              navigateDiyLink({ type: 'coupon', id: item.id }, { needLogin: component.props?.needLogin });
            }}
          >
            <Text className="diy-coupon-group__amount">¥{money(item.value)}</Text>
            <Text className="diy-coupon-group__name">{item.name || '优惠券'}</Text>
            <Text className="diy-coupon-group__condition">满{money(item.min_amount || item.minAmount)}可用</Text>
          </View>
        ))}
      </View>
    </View>
  );
}
