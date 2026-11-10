import { Text, View } from '@tarojs/components';
import { fetchDistributionCommissions } from '../../services';
import { commissionLabels, money, shortTime } from '../../services/format';
import { DistributionLayout, ListFooter, useDistributionList } from '../shared';

export default function DistributionCommissions() {
  const data = useDistributionList('commissions', page => fetchDistributionCommissions({ page, pageSize: 20 }));
  return <DistributionLayout title="佣金明细">
    <Text className="distribution-note">支付成功后佣金待结算，订单满足结算条件后转为可提现。退款按对应订单冲回。</Text>
    {data.list.map(item => <View key={item.id} className="distribution-card">
      <View className="distribution-row"><Text className="distribution-order-title">订单 {item.orderNo}</Text><Text className="distribution-tag">{commissionLabels[item.status] || item.status}</Text></View>
      <View className="distribution-row"><Text>当前佣金</Text><Text className="distribution-link">¥{money(item.netAmount)}</Text></View>
      <View className="distribution-row"><Text className="distribution-muted">原始佣金 / 已冲回</Text><Text>¥{money(item.amount)} / ¥{money(item.refundedAmount)}</Text></View>
      <Text className="distribution-note">计佣时间：{shortTime(item.createdAt)}{item.settledAt ? `\n结算时间：${shortTime(item.settledAt)}` : ''}</Text>
    </View>)}
    <ListFooter loading={data.loading} error={data.error} total={data.total} count={data.list.length} onRetry={data.reload} onMore={data.loadMore} />
  </DistributionLayout>;
}
