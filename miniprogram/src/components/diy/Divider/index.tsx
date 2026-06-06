import { View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, any>, { color?: string; margin?: number }>;
}

export default function Divider({ component }: Props) {
  const color = component.props?.color || component.style?.color || '#e8ecef';
  const margin = Number(component.props?.margin || 24);

  return (
    <View className="diy-divider" style={{ paddingLeft: `${margin}px`, paddingRight: `${margin}px` }}>
      <View className="diy-divider__line" style={{ background: color }} />
    </View>
  );
}
