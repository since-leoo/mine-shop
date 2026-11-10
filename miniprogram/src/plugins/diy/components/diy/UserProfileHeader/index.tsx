import { Image, Text, View } from '@tarojs/components';
import { useState } from 'react';
import { renderPluginSlot } from '../../../../../common/plugin-hooks';
import { DiyComponent } from '../../diy-renderer/types';
import { diyComponentStyle } from '../../diy-renderer/style';
import { resolveDiyAsset } from '../../diy-renderer/assets';
import defaultAvatar from '../../../../../assets/usercenter/avatar-default.svg';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, any>, { avatar?: string; nickname?: string; inviteCode?: string; phoneNumber?: string; showInviteEntry?: boolean; showInvite?: boolean; showQrcode?: boolean; qrcodeIcon?: string }>;
}

function ProfileAvatar({ avatar }: { avatar: string }) {
  const [failedUrl, setFailedUrl] = useState<string | null>(null);
  return <Image className="diy-user-profile-header__avatar-img" src={avatar && avatar !== failedUrl ? avatar : defaultAvatar} mode="aspectFill" onError={() => setFailedUrl(avatar)} />;
}

export default function UserProfileHeader({ component }: Props) {
  const avatar = resolveDiyAsset(component.props?.avatar || '');
  const nickname = component.props?.nickname || '商城会员';
  const phoneNumber = component.props?.phoneNumber || '';
  const legacySubtitle = component.props?.inviteCode || '';
  const subtitle = phoneNumber ? `手机号：${phoneNumber}` : /^手机号[:：]/.test(legacySubtitle) ? legacySubtitle : '';

  return (
    <View className="diy-user-profile-header" style={diyComponentStyle(component)}>
      <View className="diy-user-profile-header__avatar">
        <ProfileAvatar avatar={avatar} />
      </View>
      <View className="diy-user-profile-header__body">
        <Text className="diy-user-profile-header__name">{nickname}</Text>
        {subtitle ? <Text className="diy-user-profile-header__code">{subtitle}</Text> : null}
      </View>
      {renderPluginSlot('user-profile-right', { options: component.props || {} })}
    </View>
  );
}
