import { View, Text } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { isH5 } from '../../common/platform';
import './index.scss';

interface Props {
  title?: string;
  showBack?: boolean;
  showTitle?: boolean;
  light?: boolean;
  background?: 'default' | 'transparent';
}

export default function PageNav({
  title = '',
  showBack = true,
  showTitle = true,
  light = false,
  background = 'default',
}: Props) {
  const systemInfo = Taro.getSystemInfoSync();
  const h5 = isH5();
  const statusBarHeight = systemInfo.statusBarHeight || 20;

  const handleBack = () => {
    const pages = Taro.getCurrentPages();
    if (pages.length > 1) {
      Taro.navigateBack().catch(() => {
        Taro.switchTab({ url: '/pages/home/index' }).catch(() => {
          Taro.reLaunch({ url: '/pages/home/index' });
        });
      });
      return;
    }
    Taro.switchTab({ url: '/pages/home/index' }).catch(() => {
      Taro.reLaunch({ url: '/pages/home/index' });
    });
  };

  if (h5) {
    if (!showBack && !showTitle) {
      return <View className={`page-nav page-nav--h5-placeholder ${background === 'transparent' ? 'page-nav--transparent' : ''}`} />;
    }

    return (
      <View
        className={`page-nav page-nav--h5 ${background === 'transparent' ? 'page-nav--transparent' : ''}`}
        style={{ paddingTop: `${statusBarHeight}px` }}
      >
        <View className="page-nav__bar">
          {showBack ? (
            <View className="page-nav__back" onClick={handleBack}>
              <Text className={`page-nav__back-icon ${light ? 'page-nav__back-icon--light' : ''}`}>‹</Text>
            </View>
          ) : (
            <View className="page-nav__back page-nav__back--placeholder" />
          )}
          {showTitle ? <Text className={`page-nav__title ${light ? 'page-nav__title--light' : ''}`}>{title}</Text> : null}
          <View className="page-nav__capsule-space" />
        </View>
      </View>
    );
  }
  const menuButton = h5 ? null : Taro.getMenuButtonBoundingClientRect?.();
  const navHeight = menuButton ? (menuButton.top - statusBarHeight) * 2 + menuButton.height : 44;
  const capsuleWidth = menuButton ? systemInfo.windowWidth - menuButton.left + 12 : 176;

  return (
    <View className={`page-nav ${background === 'transparent' ? 'page-nav--transparent' : ''}`} style={{ paddingTop: `${statusBarHeight}px` }}>
      <View className="page-nav__bar" style={{ height: `${navHeight}px` }}>
        {showBack ? (
          <View className="page-nav__back" onClick={handleBack}>
            <Text className={`page-nav__back-icon ${light ? 'page-nav__back-icon--light' : ''}`}>‹</Text>
          </View>
        ) : (
          <View className="page-nav__back page-nav__back--placeholder" />
        )}
        {showTitle ? <Text className={`page-nav__title ${light ? 'page-nav__title--light' : ''}`}>{title}</Text> : null}
        <View className="page-nav__capsule-space" style={{ width: `${capsuleWidth}px` }} />
      </View>
    </View>
  );
}
