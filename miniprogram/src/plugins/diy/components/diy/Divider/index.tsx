import { View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { schemaSize } from '../../diy-renderer/style';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, any>, { color?: string; margin?: number }>;
}

export default function Divider({ component }: Props) {
  const color = component.props?.color || component.style?.color || '#e8ecef';
  const margin = Number(component.props?.margin ?? 24);

  return (
    <View className="diy-divider" style={{ paddingLeft: schemaSize(margin), paddingRight: schemaSize(margin) }}>
      <View className="diy-divider__line" style={{ background: color }} />
    </View>
  );
}
