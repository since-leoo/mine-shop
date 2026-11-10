import { Button, Text, View } from '@tarojs/components';
import Taro, { useDidShow, usePullDownRefresh } from '@tarojs/taro';
import { useRef, useState } from 'react';
import { fetchDistributionOverview, fetchDistributionStatus, type DistributionOverview, type DistributionStatus } from '../services';
import { distributionVisible, money } from '../services/format';
import { isMiniProgram } from '../../../common/platform';
import { DistributionLayout, goDistribution, LoadState } from './shared';

export default function DistributionCenter() {
  const [status, setStatus] = useState<DistributionStatus | null>(null);
  const [overview, setOverview] = useState<DistributionOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const sequence = useRef(0);
  const load = async () => {
    const requestId = ++sequence.current;
    setLoading(true); setError('');
    try {
      const [nextStatus, nextOverview] = await Promise.all([fetchDistributionStatus(), fetchDistributionOverview()]);
      if (sequence.current !== requestId) return;
      setStatus(nextStatus); setOverview(nextOverview);
    } catch (err: any) { if (sequence.current === requestId) setError(err?.msg || '分销中心加载失败'); }
    finally { if (sequence.current === requestId) setLoading(false); }
  };
  useDidShow(() => { void load(); });
  usePullDownRefresh(() => { void load().finally(() => Taro.stopPullDownRefresh()); });
  const visible = distributionVisible(status, isMiniProgram());
  return <DistributionLayout title="分销中心">
    <LoadState loading={loading && !overview} error={error} onRetry={load} />
    {overview && <>
      {!visible && <View className="distribution-notice">推广服务暂未开放，历史佣金和提现记录仍可查询。</View>}
      <View className="distribution-card distribution-hero">
        <Text>可提现佣金（元）</Text><Text className="distribution-amount">{money(overview.availableCommission)}</Text>
        <View className="distribution-grid">
          <View className="distribution-stat"><Text className="distribution-stat__value">{money(overview.pendingCommission)}</Text><Text className="distribution-stat__label">待结算</Text></View>
          <View className="distribution-stat"><Text className="distribution-stat__value">{money(overview.frozenCommission)}</Text><Text className="distribution-stat__label">提现冻结</Text></View>
          <View className="distribution-stat"><Text className="distribution-stat__value">{money(overview.withdrawnCommission)}</Text><Text className="distribution-stat__label">累计已提现</Text></View>
          <View className="distribution-stat"><Text className="distribution-stat__value">{money(overview.debtAmount)}</Text><Text className="distribution-stat__label">待追回</Text></View>
        </View>
        <Button className="distribution-secondary" onClick={() => goDistribution('withdraw')}>申请提现</Button>
      </View>
      {visible && <View className="distribution-card">
        <Text className="distribution-title">我的团队</Text>
        <View className="distribution-grid">
          <View className="distribution-stat distribution-link" onClick={() => goDistribution('team')}><Text className="distribution-stat__value">{overview.directCount} / {status?.directLimit ?? 3}</Text><Text className="distribution-stat__label">直属粉丝 ›</Text></View>
          <View className="distribution-stat distribution-link" onClick={() => Taro.navigateTo({ url: '/plugins/distribution/pages/team/index?scope=all' })}><Text className="distribution-stat__value">{overview.teamCount}</Text><Text className="distribution-stat__label">全部团队 ›</Text></View>
          <View className="distribution-stat"><Text className="distribution-stat__value">{overview.consumerCount}</Text><Text className="distribution-stat__label">消费人数</Text></View>
          <View className="distribution-stat distribution-link" onClick={() => goDistribution('orders')}><Text className="distribution-stat__value">{overview.paidOrderCount}</Text><Text className="distribution-stat__label">支付订单数 ›</Text></View>
          <View className="distribution-stat"><Text className="distribution-stat__value">{overview.validOrderCount}</Text><Text className="distribution-stat__label">有效消费订单数</Text></View>
          <View className="distribution-stat"><Text className="distribution-stat__value">¥{money(overview.netAmount)}</Text><Text className="distribution-stat__label">团队净消费额</Text></View>
        </View>
        <Text className="distribution-note">净消费额为实付金额扣除已成功退款；全额退款订单不计入有效消费订单数。团队统计不代表多级返佣。</Text>
        <Button className="distribution-primary" onClick={() => goDistribution('invite')}>我的邀请码 · 剩余 {status?.remainingSlots ?? 0} 个直属名额</Button>
      </View>}
      <View className="distribution-card">
        <View className="distribution-row" onClick={() => goDistribution('commissions')}><Text>佣金明细</Text><Text className="distribution-link">查看 ›</Text></View>
        <View className="distribution-row" onClick={() => goDistribution('withdrawals')}><Text>提现记录</Text><Text className="distribution-link">查看 ›</Text></View>
      </View>
      <Text className="distribution-note">佣金以有效销售订单结算，退款会冲回对应佣金。待追回款项按平台规则处理，具体余额以账务记录为准。</Text>
    </>}
  </DistributionLayout>;
}
