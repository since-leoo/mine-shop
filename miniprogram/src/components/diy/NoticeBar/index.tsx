import { Text, View } from '@tarojs/components';
import { DiyComponent, DiyLink } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import { diyComponentStyle, stopDiyEvent } from '../../diy-renderer/style';
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
      style={diyComponentStyle(component)}
      onClick={(event) => {
        stopDiyEvent(event);
        navigateDiyLink(item?.link, { needLogin: component.props?.needLogin });
      }}
    >
      {component.props?.showIcon === false ? null : <Text className="diy-notice-bar__icon">!</Text>}
      <Text className="diy-notice-bar__text">{text}</Text>
    </View>
  );
}
