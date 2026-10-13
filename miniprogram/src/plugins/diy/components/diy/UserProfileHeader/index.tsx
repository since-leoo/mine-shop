import { Image, Text, View } from '@tarojs/components';
import { useState } from 'react';
import { DiyComponent } from '../../diy-renderer/types';
import { diyComponentStyle } from '../../diy-renderer/style';
import { resolveDiyAsset } from '../../diy-renderer/assets';
import profileQrcodeIcon from '../../../../../assets/usercenter/profile-qrcode.svg';
import defaultAvatar from '../../../../../assets/usercenter/avatar-default.svg';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, any>, { avatar?: string; nickname?: string; inviteCode?: string; qrcodeIcon?: string }>;
}

function ProfileAvatar({ avatar }: { avatar: string }) {
  const [failedUrl, setFailedUrl] = useState<string | null>(null);
  return <Image className="diy-user-profile-header__avatar-img" src={avatar && avatar !== failedUrl ? avatar : defaultAvatar} mode="aspectFill" onError={() => setFailedUrl(avatar)} />;
}

export default function UserProfileHeader({ component }: Props) {
  const avatar = resolveDiyAsset(component.props?.avatar || '');
  const nickname = component.props?.nickname || '商城会员';
  const inviteCode = component.props?.inviteCode || '';
  const subtitle = /^(邀请码|手机号)[:：]/.test(inviteCode) ? inviteCode : `邀请码: ${inviteCode}`;
  const qrcodeIcon = resolveDiyAsset(component.props?.qrcodeIcon || '') || profileQrcodeIcon;

  return (
    <View className="diy-user-profile-header" style={diyComponentStyle(component)}>
      <View className="diy-user-profile-header__avatar">
        <ProfileAvatar avatar={avatar} />
      </View>
      <View className="diy-user-profile-header__body">
        <Text className="diy-user-profile-header__name">{nickname}</Text>
        {inviteCode ? <Text className="diy-user-profile-header__code">{subtitle}</Text> : null}
      </View>
      {inviteCode ? <View className="diy-user-profile-header__qrcode">
        <Image className="diy-user-profile-header__qrcode-icon" src={qrcodeIcon} mode="aspectFit" />
      </View> : null}
    </View>
  );
}
