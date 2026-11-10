import { Image, Text, View } from '@tarojs/components';
import Taro, { useRouter } from '@tarojs/taro';
import { useState } from 'react';
import defaultAvatar from '../../../../assets/usercenter/avatar-default.svg';
import { fetchDistributionTeam, type TeamScope } from '../../services';
import { money, shortTime } from '../../services/format';
import { DistributionLayout, ListFooter, ScopeTabs, useDistributionList } from '../shared';

export default function DistributionTeam() {
  const router = useRouter();
  const [scope, setScope] = useState<TeamScope>(router.params.scope === 'all' ? 'all' : 'direct');
  const data = useDistributionList(scope, page => fetchDistributionTeam({ scope, page, pageSize: 20 }));
  return <DistributionLayout title="我的团队">
    <ScopeTabs value={scope} onChange={setScope} />
    <Text className="distribution-note">{scope === 'direct' ? '直属粉丝为您直接邀请注册的用户。' : '全部团队包含所有后代成员，不包含您本人。'} 共 {data.total} 人</Text>
    {data.list.map(member => <View key={member.memberId} className="distribution-card">
      <View className="distribution-person"><Image className="distribution-person__avatar" src={member.avatar || defaultAvatar} mode="aspectFill" /><View className="distribution-person__body"><Text className="distribution-person__name">{member.nickname || '商城会员'}</Text><Text className="distribution-muted">{shortTime(member.joinedAt)} 加入</Text></View><Text className="distribution-tag">{member.depth === 1 ? '直属' : `第 ${member.depth} 层`}</Text></View>
      <View className="distribution-grid">
        <View className="distribution-stat"><Text className="distribution-stat__value">¥{money(member.netAmount)}</Text><Text className="distribution-stat__label">净消费额</Text></View>
        <View className="distribution-stat distribution-link" onClick={() => Taro.navigateTo({ url: `/plugins/distribution/pages/orders/index?scope=${scope}&member_id=${member.memberId}` })}><Text className="distribution-stat__value">{member.paidOrderCount} ›</Text><Text className="distribution-stat__label">本人支付订单 · 有效 {member.validOrderCount} 笔</Text></View>
      </View>
    </View>)}
    <ListFooter loading={data.loading} error={data.error} total={data.total} count={data.list.length} onRetry={data.reload} onMore={data.loadMore} />
  </DistributionLayout>;
}
