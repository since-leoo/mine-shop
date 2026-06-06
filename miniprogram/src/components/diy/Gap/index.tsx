import { View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';

interface Props {
  component: DiyComponent<Record<string, any>, { height?: number; background?: string }>;
}

export default function Gap({ component }: Props) {
  const height = Number(component.props?.height || component.data?.height || 16);
  const background = component.props?.background || component.style?.background || 'transparent';

  return <View style={{ height: `${Math.max(0, height)}px`, background }} />;
}
