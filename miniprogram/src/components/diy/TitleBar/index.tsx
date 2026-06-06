import { Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, any>, { title?: string; subtitle?: string }>;
}

export default function TitleBar({ component }: Props) {
  const title = component.props?.title || component.data?.title || component.name || '';
  const subtitle = component.props?.subtitle || component.data?.subtitle || '';
  if (!title && !subtitle) return null;

  return (
    <View className="diy-title-bar">
      {title ? <Text className="diy-title-bar__title">{title}</Text> : null}
      {subtitle ? <Text className="diy-title-bar__subtitle">{subtitle}</Text> : null}
    </View>
  );
}
