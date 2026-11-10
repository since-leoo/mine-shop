import { Text, View } from '@tarojs/components';
import { fetchDistributionWithdrawals } from '../../services';
import { money, shortTime, withdrawalLabels } from '../../services/format';
import { DistributionLayout, ListFooter, useDistributionList } from '../shared';

export default function DistributionWithdrawals() {
  const data = useDistributionList('withdrawals', page => fetchDistributionWithdrawals({ page, pageSize: 20 }));
  return <DistributionLayout title="提现记录">
    <Text className="distribution-note">申请提交后金额将冻结，审核拒绝或付款失败时按账务结果释放。</Text>
    {data.list.map(item => <View key={item.id} className="distribution-card">
      <View className="distribution-row"><Text className="distribution-order-title">{item.withdrawalNo}</Text><Text className="distribution-tag">{withdrawalLabels[item.status] || item.status}</Text></View>
      <View className="distribution-row"><Text>申请金额</Text><Text>¥{money(item.amount)}</Text></View>
      <View className="distribution-row"><Text className="distribution-muted">手续费</Text><Text>¥{money(item.feeAmount)}</Text></View>
      <View className="distribution-row"><Text>预计 / 实际到账金额</Text><Text className="distribution-link">¥{money(item.netAmount)}</Text></View>
      <Text className="distribution-note">申请时间：{shortTime(item.createdAt)}{item.paidAt ? `\n付款时间：${shortTime(item.paidAt)}` : ''}</Text>
      {item.remark && <Text className="distribution-note">处理说明：{item.remark}</Text>}
      {item.paymentNo && <Text className="distribution-note">付款流水：{item.paymentNo}</Text>}
    </View>)}
    <ListFooter loading={data.loading} error={data.error} total={data.total} count={data.list.length} onRetry={data.reload} onMore={data.loadMore} />
  </DistributionLayout>;
}
