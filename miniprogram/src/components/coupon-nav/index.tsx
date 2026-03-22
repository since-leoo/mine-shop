import { View, Text } from '@tarojs/components';
import Taro from '@tarojs/taro';
import './index.scss';

interface Props {
  title: string;
}

export default function CouponNav({ title }: Props) {
  const systemInfo = Taro.getSystemInfoSync();
  const menuButton = Taro.getMenuButtonBoundingClientRect?.();
  const statusBarHeight = systemInfo.statusBarHeight || 20;
  const navHeight = menuButton ? (menuButton.top - statusBarHeight) * 2 + menuButton.height : 44;
  const capsuleWidth = menuButton ? systemInfo.windowWidth - menuButton.left + 12 : 176;

  const handleBack = () => {
    if (getCurrentPages().length > 1) {
      Taro.navigateBack();
      return;
    }
    Taro.switchTab({ url: '/pages/usercenter/index' });
  };

  return (
    <View className="coupon-nav" style={{ paddingTop: `${statusBarHeight}px` }}>
      <View className="coupon-nav__bar" style={{ height: `${navHeight}px` }}>
        <View className="coupon-nav__back" onClick={handleBack}>
          <Text className="coupon-nav__back-icon">‹</Text>
        </View>
        <Text className="coupon-nav__title">{title}</Text>
        <View className="coupon-nav__capsule-space" style={{ width: `${capsuleWidth}px` }} />
      </View>
    </View>
  );
}
