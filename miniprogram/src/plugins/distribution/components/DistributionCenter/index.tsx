import { Text, View } from '@tarojs/components';
import Taro, { useDidShow } from '@tarojs/taro';
import { useEffect, useRef, useState } from 'react';
import { getStoredToken, isLoggedIn } from '../../../../common/auth';
import { isMiniProgram } from '../../../../common/platform';
import { fetchDistributionOverview, fetchDistributionStatus, type DistributionOverview, type DistributionStatus } from '../../services';
import { distributionVisible, money } from '../../services/format';
import type { DiyComponent } from '../../../diy/components/diy-renderer/types';
import { diyComponentStyle } from '../../../diy/components/diy-renderer/style';
import './index.scss';

interface Props { component: DiyComponent<Record<string, any>, { title?: string; showInvite?: boolean; showTeam?: boolean; showOrders?: boolean; showCommission?: boolean }> }

export function DistributionCenterView({ component, status, overview, miniProgram }: Props & { status: DistributionStatus | null; overview: DistributionOverview | null; miniProgram: boolean }) {
  if (!distributionVisible(status, miniProgram) || !overview) return null;
  const props = component.props || {};
  const open = (page: string, event: { stopPropagation: () => void }) => {
    event.stopPropagation();
    Taro.navigateTo({ url: `/plugins/distribution/pages/${page ? `${page}/` : ''}index` });
  };
  return <View className="diy-distribution-center" style={diyComponentStyle(component)}>
    <View className="diy-distribution-center__header" onClick={event => open('', event)}><Text>{props.title || '分销中心'}</Text><Text className="diy-distribution-center__more">查看 ›</Text></View>
    <View className="diy-distribution-center__grid">
      {props.showTeam !== false && <View className="diy-distribution-center__stat" onClick={event => open('team', event)}><Text className="diy-distribution-center__value">{overview.teamCount}</Text><Text className="diy-distribution-center__label">团队成员</Text></View>}
      {props.showOrders !== false && <View className="diy-distribution-center__stat" onClick={event => open('orders', event)}><Text className="diy-distribution-center__value">{overview.paidOrderCount}</Text><Text className="diy-distribution-center__label">消费订单</Text></View>}
      {props.showCommission !== false && <View className="diy-distribution-center__stat" onClick={event => open('commissions', event)}><Text className="diy-distribution-center__value">¥{money(overview.availableCommission)}</Text><Text className="diy-distribution-center__label">可提现佣金</Text></View>}
    </View>
    {props.showInvite !== false && <View className="diy-distribution-center__invite" onClick={event => open('invite', event)}><Text>邀请好友</Text><Text>剩余 {status?.remainingSlots ?? 0} 个直属名额 ›</Text></View>}
  </View>;
}

export default function DistributionCenter({ component }: Props) {
  const [status, setStatus] = useState<DistributionStatus | null>(null);
  const [overview, setOverview] = useState<DistributionOverview | null>(null);
  const sequence = useRef(0);
  const token = getStoredToken();
  const refresh = async () => {
    const id = ++sequence.current;
    const ownerToken = getStoredToken();
    setStatus(null); setOverview(null);
    if (!isLoggedIn()) return;
    try {
      const nextStatus = await fetchDistributionStatus();
      if (id !== sequence.current || ownerToken !== getStoredToken()) return;
      if (!distributionVisible(nextStatus, isMiniProgram())) return;
      const nextOverview = await fetchDistributionOverview();
      if (id !== sequence.current || ownerToken !== getStoredToken()) return;
      setStatus(nextStatus); setOverview(nextOverview);
    } catch { /* Optional business module is hidden when unavailable. */ }
  };
  useEffect(() => { void refresh(); return () => { ++sequence.current; }; }, [token]);
  useDidShow(() => { void refresh(); });
  return <DistributionCenterView component={component} status={status} overview={overview} miniProgram={isMiniProgram()} />;
}
