import { View, Text, Image } from '@tarojs/components';
import Taro from '@tarojs/taro';
import homeIcon from '../../assets/tab/home.png';
import homeActiveIcon from '../../assets/tab/home-active.png';
import categoryIcon from '../../assets/tab/category.png';
import categoryActiveIcon from '../../assets/tab/category-active.png';
import cartIcon from '../../assets/tab/cart.png';
import cartActiveIcon from '../../assets/tab/cart-active.png';
import userIcon from '../../assets/tab/user.png';
import userActiveIcon from '../../assets/tab/user-active.png';
import './index.scss';

const TAB_LIST = [
  { pagePath: '/pages/home/index', text: '首页', icon: homeIcon, activeIcon: homeActiveIcon },
  { pagePath: '/pages/category/index', text: '分类', icon: categoryIcon, activeIcon: categoryActiveIcon },
  { pagePath: '/pages/cart/index', text: '购物车', icon: cartIcon, activeIcon: cartActiveIcon },
  { pagePath: '/pages/usercenter/index', text: '我的', icon: userIcon, activeIcon: userActiveIcon },
];

interface Props {
  current?: string;
}

export default function H5TabBar({ current = '' }: Props) {
  const route = current || `/${Taro.getCurrentInstance().router?.path || ''}`;

  return (
    <View className="h5-tab-bar">
      <View className="h5-tab-bar__inner">
        {TAB_LIST.map((item) => {
          const active = route === item.pagePath;
          return (
            <View
              key={item.pagePath}
              className={`h5-tab-bar__item ${active ? 'h5-tab-bar__item--active' : ''}`}
              onClick={() => {
                if (active) return;
                Taro.switchTab({ url: item.pagePath });
              }}
            >
              <View className="h5-tab-bar__icon-wrap">
                <Image className="h5-tab-bar__icon" src={active ? item.activeIcon : item.icon} mode="aspectFit" />
              </View>
              <Text className="h5-tab-bar__text">{item.text}</Text>
            </View>
          );
        })}
      </View>
    </View>
  );
}
