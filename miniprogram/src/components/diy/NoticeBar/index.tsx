import { Text, View } from '@tarojs/components';
import { DiyComponent, DiyLink } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface NoticeItem {
  text?: string;
  link?: DiyLink;
}

interface Props {
  component: DiyComponent<{ items?: NoticeItem[] }, { showIcon?: boolean }>;
}

export default function NoticeBar({ component }: Props) {
  const item = component.data?.items?.[0];
  const text = item?.text || '';
  if (!text) return null;

  return (
    <View
      className="diy-notice-bar"
      style={{ background: component.style?.background || '#fff7ed', color: component.style?.color || '#c2410c' }}
      onClick={() => navigateDiyLink(item?.link)}
    >
      {component.props?.showIcon === false ? null : <Text className="diy-notice-bar__icon">!</Text>}
      <Text className="diy-notice-bar__text">{text}</Text>
    </View>
  );
}
