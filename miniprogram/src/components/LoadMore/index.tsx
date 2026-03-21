import { View, Text } from '@tarojs/components';
import { Loading } from '@nutui/nutui-react-taro';
import './index.scss';

/** 0=idle, 1=loading, 2=noMore, 3=failed */
type LoadMoreStatus = 0 | 1 | 2 | 3;

interface LoadMoreProps {
  status: LoadMoreStatus;
  onRetry?: () => void;
  listIsEmpty?: boolean;
  noMoreText?: string;
  children?: React.ReactNode;
}

export default function LoadMore({
  status,
  onRetry,
  listIsEmpty = false,
  noMoreText = '没有更多了',
  children,
}: LoadMoreProps) {
  // Hide when idle or noMore and list is empty (show empty slot instead)
  if (listIsEmpty && (status === 0 || status === 2)) {
    return <>{children}</>;
  }

  return (
    <View className="load-more">
      {/* Loading */}
      {status === 1 && (
        <View className="load-more__loading">
          <Loading className="load-more__spinner" />
          <Text className="load-more__loading-text">加载中...</Text>
        </View>
      )}

      {/* No more */}
      {status === 2 && (
        <View className="load-more__no-more">
          <View className="load-more__divider-line" />
          <Text className="load-more__no-more-text">{noMoreText}</Text>
          <View className="load-more__divider-line" />
        </View>
      )}

      {/* Failed */}
      {status === 3 && (
        <View className="load-more__error">
          <Text className="load-more__error-text">加载失败</Text>
          <Text className="load-more__refresh-btn" onClick={onRetry}>
            刷新
          </Text>
        </View>
      )}
    </View>
  );
}
