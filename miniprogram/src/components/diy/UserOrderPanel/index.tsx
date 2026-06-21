import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyLink } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import './index.scss';

interface UserOrderItem {
  label?: string;
  icon?: string;
  image?: string;
  svg?: string;
  badge?: boolean;
  orderNum?: number;
  link?: DiyLink;
}

interface Props {
  component: DiyComponent<{ items?: UserOrderItem[] }, { title?: string; moreText?: string; moreLink?: DiyLink }>;
}

export default function UserOrderPanel({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-user-order-panel" style={diyComponentStyle(component)}>
      <View className="diy-user-order-panel__head">
        <Text className="diy-user-order-panel__title">{component.props?.title || '我的订单'}</Text>
        <Text
          className="diy-user-order-panel__more"
          onClick={(event) => handleDiyLinkClick(event, component.props?.moreLink, component.props?.needLogin)}
        >
          {component.props?.moreText || '全部订单'} ›
        </Text>
      </View>
      <View className="diy-user-order-panel__grid">
        {items.slice(0, 5).map((item, index) => {
          const icon = imageOf(item);
          const orderNum = Number(item.orderNum || 0);
          const badgeText = orderNum > 99 ? '99+' : String(orderNum);
          return (
            <View
              key={`${item.label || 'order'}-${index}`}
              className="diy-user-order-panel__item"
              onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
            >
              <View className="diy-user-order-panel__icon-wrap">
                {orderNum > 0 ? (
                  <View className="diy-user-order-panel__badge diy-user-order-panel__badge--count">
                    <Text className="diy-user-order-panel__badge-text">{badgeText}</Text>
                  </View>
                ) : item.badge ? <View className="diy-user-order-panel__badge" /> : null}
                {icon ? <Image className="diy-user-order-panel__icon" src={icon} mode="aspectFit" /> : <Text className="diy-user-order-panel__fallback">□</Text>}
              </View>
              <Text className="diy-user-order-panel__label">{item.label || '订单'}</Text>
            </View>
          );
        })}
      </View>
    </View>
  );
}
