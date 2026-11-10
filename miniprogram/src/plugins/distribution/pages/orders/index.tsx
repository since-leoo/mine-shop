import { Text, View } from '@tarojs/components';
import { useRouter } from '@tarojs/taro';
import { useState } from 'react';
import { fetchDistributionOrders, type TeamScope } from '../../services';
import { money, orderLabels, shortTime } from '../../services/format';
import { DistributionLayout, ListFooter, ScopeTabs, useDistributionList } from '../shared';

export default function DistributionOrders() {
  const router = useRouter();
  const memberId = Number(router.params.member_id) > 0 ? Number(router.params.member_id) : undefined;
  const [scope, setScope] = useState<TeamScope>(router.params.scope === 'direct' ? 'direct' : 'all');
  const data = useDistributionList(`${scope}:${memberId || ''}`, page => fetchDistributionOrders({ scope: memberId ? 'self' : scope, memberId, page, pageSize: 20 }));
  return <DistributionLayout title={memberId ? '粉丝消费订单' : '团队消费订单'}>
    {!memberId && <ScopeTabs value={scope} onChange={setScope} />}
    <Text className="distribution-note">{memberId ? '展示该粉丝本人的消费订单。' : '展示所选范围内粉丝的消费订单。'} 共 {data.total} 笔，订单信息已脱敏。</Text>
    {data.list.map(order => <View key={order.id} className="distribution-card">
      <View className="distribution-row"><Text className="distribution-order-title">订单 {order.orderNo}</Text><Text className="distribution-tag">{orderLabels[order.status] || order.status}</Text></View>
      <View className="distribution-product">{order.productSummary || '商品订单'}</View>
      <View className="distribution-row"><Text className="distribution-muted">会员</Text><Text>{order.nickname || '商城会员'}</Text></View>
      <View className="distribution-row"><Text className="distribution-muted">实付 / 已退款</Text><Text>¥{money(order.paidAmount)} / ¥{money(order.refundAmount)}</Text></View>
      <View className="distribution-row"><Text>净消费额</Text><Text className="distribution-link">¥{money(order.netAmount)}</Text></View>
      <View className="distribution-row"><Text className="distribution-muted">我的佣金</Text><Text>¥{money(order.commissionAmount)}</Text></View>
      <Text className="distribution-note">支付时间：{shortTime(order.paidAt) || '—'}</Text>
    </View>)}
    <ListFooter loading={data.loading} error={data.error} total={data.total} count={data.list.length} onRetry={data.reload} onMore={data.loadMore} />
  </DistributionLayout>;
}
