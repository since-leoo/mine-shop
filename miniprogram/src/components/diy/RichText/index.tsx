import { RichText as TaroRichText, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import './index.scss';

interface Props {
  component: DiyComponent<{ content?: string }, { padding?: number }>;
}

export default function RichText({ component }: Props) {
  const content = component.data?.content || '';
  if (!content) return null;

  return (
    <View className="diy-rich-text" style={{ padding: `${Number(component.props?.padding ?? 12)}px` }}>
      <TaroRichText nodes={content} />
    </View>
  );
}
