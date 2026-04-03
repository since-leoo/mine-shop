import { View, Text } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { isH5 } from '../../common/platform';
import './index.scss';

interface Props {
  title: string;
}

export default function CouponNav({ title }: Props) {
  const systemInfo = Taro.getSystemInfoSync();
  const h5 = isH5();
  if (h5) {
    return null;
  }
  const menuButton = h5 ? null : Taro.getMenuButtonBoundingClientRect?.();
  const statusBarHeight = systemInfo.statusBarHeight || 20;
  const navHeight = menuButton ? (menuButton.top - statusBarHeight) * 2 + menuButton.height : 44;
  const capsuleWidth = menuButton ? systemInfo.windowWidth - menuButton.left + 12 : 176;

  const handleBack = () => {
    const pages = Taro.getCurrentPages();
    if (pages.length > 1) {
      Taro.navigateBack().catch(() => {
        Taro.switchTab({ url: '/pages/usercenter/index' }).catch(() => {
          Taro.reLaunch({ url: '/pages/usercenter/index' });
        });
      });
      return;
    }
    Taro.switchTab({ url: '/pages/usercenter/index' }).catch(() => {
      Taro.reLaunch({ url: '/pages/usercenter/index' });
    });
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
