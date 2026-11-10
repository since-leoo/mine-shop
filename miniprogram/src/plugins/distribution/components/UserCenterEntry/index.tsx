import { Image, Text, View } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useEffect, useRef, useState } from 'react';
import { getStoredToken } from '../../../../common/auth';
import { isMiniProgram } from '../../../../common/platform';
import qrcodeIcon from '../../../../assets/usercenter/profile-qrcode.svg';
import { fetchDistributionStatus, type DistributionStatus } from '../../services';
import { distributionVisible } from '../../services/format';
import './index.scss';

function useStatus() {
  const [status, setStatus] = useState<DistributionStatus | null>(null);
  const sequence = useRef(0);
  const token = getStoredToken();
  const refresh = async () => {
    const id = ++sequence.current;
    const owner = getStoredToken();
    setStatus(null);
    if (!owner) return;
    try {
      const result = await fetchDistributionStatus();
      if (id === sequence.current && owner === getStoredToken()) setStatus(result);
    } catch { /* An unavailable optional plugin has no entry. */ }
  };
  useEffect(() => { void refresh(); return () => { ++sequence.current; }; }, [token]);
  useDidShow(() => { void refresh(); });
  return status;
}
export function DistributionProfileEntry({ options = {} }: { options?: Record<string, any> }) {
  const status = useStatus();
  if (!distributionVisible(status, isMiniProgram()) || (options.showInvite === false && options.showQrcode === false)) return null;
  return <View className="distribution-profile-entry" onClick={event => { event.stopPropagation(); Taro.navigateTo({ url: '/plugins/distribution/pages/invite/index' }); }}>
    {options.showQrcode !== false && <Image className="distribution-profile-entry__image" src={qrcodeIcon} mode="aspectFit" />}
    {options.showInvite !== false && <Text className="distribution-profile-entry__code">{status?.inviteCode}</Text>}
  </View>;
}
export function DistributionMenuEntry() {
  const status = useStatus();
  const visible = distributionVisible(status, isMiniProgram());
  if (!visible && !status?.hasHistory && !status?.hasAccount) return null;
  return <View className="distribution-menu-entry" onClick={() => Taro.navigateTo({ url: '/plugins/distribution/pages/index' })}>
    <Text>{visible ? '分销中心' : '我的佣金'}</Text><Text>›</Text>
  </View>;
}
