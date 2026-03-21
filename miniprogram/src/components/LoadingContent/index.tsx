import { View } from '@tarojs/components';
import { Loading } from '@nutui/nutui-react-taro';
import './index.scss';

interface LoadingContentProps {
  position?: 'static' | 'absolute' | 'fixed';
  noMask?: boolean;
  children?: React.ReactNode;
}

export default function LoadingContent({
  position = 'absolute',
  noMask = false,
  children,
}: LoadingContentProps) {
  return (
    <View
      className={`loading-content loading-content--${position} ${noMask ? 'loading-content--no-mask' : ''}`}
    >
      <View className="loading-content__spinner">
        <Loading className="loading-content__icon" />
      </View>
      {children && <View className="loading-content__extra">{children}</View>}
    </View>
  );
}
