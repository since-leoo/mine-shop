import { Image, Text, View } from '@tarojs/components';
import { DiyComponent, DiyLink } from '../../diy-renderer/types';
import { diyComponentStyle, handleDiyLinkClick, imageOf } from '../../diy-renderer/style';
import './index.scss';

interface UserMenuItem {
  label?: string;
  value?: string;
  icon?: string;
  image?: string;
  svg?: string;
  link?: DiyLink;
}

interface Props {
  component: DiyComponent<{ items?: UserMenuItem[] }>;
}

export default function UserMenuList({ component }: Props) {
  const items = component.data?.items || [];
  if (items.length === 0) return null;

  return (
    <View className="diy-user-menu-list" style={diyComponentStyle(component)}>
      {items.slice(0, 10).map((item, index) => {
        const icon = imageOf(item);
        return (
          <View
            key={`${item.label || 'menu'}-${index}`}
            className="diy-user-menu-list__item"
            onClick={(event) => handleDiyLinkClick(event, item.link, component.props?.needLogin)}
          >
            <View className="diy-user-menu-list__left">
              {icon ? <Image className="diy-user-menu-list__icon" src={icon} mode="aspectFit" /> : <Text className="diy-user-menu-list__fallback">□</Text>}
              <Text className="diy-user-menu-list__label">{item.label || '菜单'}</Text>
            </View>
            <View className="diy-user-menu-list__right">
              {item.value ? <Text className="diy-user-menu-list__value">{item.value}</Text> : null}
              <Text className="diy-user-menu-list__arrow">›</Text>
            </View>
          </View>
        );
      })}
    </View>
  );
}
