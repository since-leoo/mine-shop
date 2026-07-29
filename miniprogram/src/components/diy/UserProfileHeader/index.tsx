import { Image, Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { diyComponentStyle } from '../../diy-renderer/style';
import { resolveDiyAsset } from '../../diy-renderer/assets';
import profileQrcodeIcon from '../../../assets/usercenter/profile-qrcode.svg';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, never>, { avatar?: string; nickname?: string; inviteCode?: string; qrcodeIcon?: string }>;
}

export default function UserProfileHeader({ component }: Props) {
  const avatar = resolveDiyAsset(component.props?.avatar || '');
  const nickname = component.props?.nickname || '小花花';
  const inviteCode = component.props?.inviteCode || 'WARM2026';
  const subtitle = /^(邀请码|手机号)[:：]/.test(inviteCode) ? inviteCode : `邀请码: ${inviteCode}`;
  const qrcodeIcon = resolveDiyAsset(component.props?.qrcodeIcon || '') || profileQrcodeIcon;

  return (
    <View className="diy-user-profile-header" style={diyComponentStyle(component)}>
      <View className="diy-user-profile-header__avatar">
        {avatar ? <Image className="diy-user-profile-header__avatar-img" src={avatar} mode="aspectFill" /> : <Text className="diy-user-profile-header__avatar-icon">●</Text>}
      </View>
      <View className="diy-user-profile-header__body">
        <Text className="diy-user-profile-header__name">{nickname}</Text>
        <Text className="diy-user-profile-header__code">{subtitle}</Text>
      </View>
      <View className="diy-user-profile-header__qrcode">
        <Image className="diy-user-profile-header__qrcode-icon" src={qrcodeIcon} mode="aspectFit" />
      </View>
    </View>
  );
}
