import { Button, Input, Text, View } from '@tarojs/components';
import Taro from '@tarojs/taro';
import { useEffect, useRef, useState } from 'react';
import { fetchDistributionOverview, fetchWithdrawalPolicy, submitWithdrawal, type DistributionOverview, type WithdrawalPolicy } from '../../services';
import { money, yuanToFen } from '../../services/format';
import { DistributionLayout, LoadState } from '../shared';

export default function DistributionWithdraw() {
  const [overview, setOverview] = useState<DistributionOverview | null>(null);
  const [policy, setPolicy] = useState<WithdrawalPolicy | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [amount, setAmount] = useState('');
  const [accountName, setAccountName] = useState('');
  const [accountNo, setAccountNo] = useState('');
  const [remark, setRemark] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const busy = useRef(false);
  const submission = useRef({ fingerprint: '', key: '' });
  const load = async () => {
    setLoading(true); setLoadError('');
    try {
      const [balance, rules] = await Promise.all([fetchDistributionOverview(), fetchWithdrawalPolicy()]);
      setOverview(balance); setPolicy(rules);
    } catch (err: any) { setLoadError(err?.msg || '提现信息加载失败'); }
    finally { setLoading(false); }
  };
  useEffect(() => { void load(); }, []);
  const submit = async () => {
    if (busy.current || submitted || !overview || !policy) return;
    const amountFen = yuanToFen(amount);
    if (amountFen === null) { setError('请输入有效金额，最多保留两位小数'); return; }
    if (amountFen < policy.minAmount) { setError(`最低提现金额为 ¥${money(policy.minAmount)}`); return; }
    if (amountFen > overview.availableCommission) { setError('提现金额超过可提现余额'); return; }
    if (!accountName.trim() || !accountNo.trim()) { setError('请填写收款人姓名和收款账号'); return; }
    if (!policy.channels.includes('manual')) { setError('当前暂无可用的提现渠道'); return; }
    const data = { amount: amountFen, channel: 'manual' as const, accountName: accountName.trim(), accountNo: accountNo.trim(), remark: remark.trim() };
    const fingerprint = JSON.stringify(data);
    if (submission.current.fingerprint !== fingerprint) submission.current = { fingerprint, key: `withdraw-${Date.now()}-${Math.random().toString(36).slice(2)}` };
    busy.current = true; setSubmitting(true); setError('');
    try {
      await submitWithdrawal({ ...data, requestKey: submission.current.key });
      setSubmitted(true);
      await Taro.showToast({ title: '提现申请已提交', icon: 'success' });
      Taro.redirectTo({ url: '/plugins/distribution/pages/withdrawals/index' });
    } catch (err: any) { setError(err?.msg || '提交失败，请核对提现记录后重试'); }
    finally { busy.current = false; setSubmitting(false); }
  };
  return <DistributionLayout title="申请提现">
    <LoadState loading={loading} error={loadError} onRetry={load} />
    {!loading && !loadError && overview && policy && <>
      <View className="distribution-card distribution-hero"><Text>可提现佣金（元）</Text><Text className="distribution-amount">{money(overview.availableCommission)}</Text><Text className="distribution-note">提现冻结 ¥{money(overview.frozenCommission)} · 待追回 ¥{money(overview.debtAmount)}</Text></View>
      <View className="distribution-card">
        <Text className="distribution-title">提现到收款账号</Text>
        <Text className="distribution-note">人工审核后付款。最低提现 ¥{money(policy.minAmount)}，手续费率 {(Number(policy.feeRateBps || 0) / 100).toFixed(2)}%。</Text>
        {!policy.channels.includes('manual') && <View className="distribution-notice">当前暂无可用的提现渠道。</View>}
        <Text className="distribution-form__label">提现金额（元）</Text><Input className="distribution-form__input" type="digit" value={amount} maxlength={12} placeholder="请输入提现金额" onInput={event => setAmount(event.detail.value)} />
        <View className="distribution-row" onClick={() => setAmount(money(overview.availableCommission))}><Text className="distribution-muted">申请金额包含手续费</Text><Text className="distribution-link">全部提现</Text></View>
        <Text className="distribution-form__label">收款人姓名</Text><Input className="distribution-form__input" value={accountName} maxlength={50} placeholder="请填写与收款账号一致的姓名" onInput={event => setAccountName(event.detail.value)} />
        <Text className="distribution-form__label">收款账号</Text><Input className="distribution-form__input" value={accountNo} maxlength={100} placeholder="银行卡或平台支持的收款账号" onInput={event => setAccountNo(event.detail.value)} />
        <Text className="distribution-form__label">备注（选填）</Text><Input className="distribution-form__input" value={remark} maxlength={200} placeholder="如开户银行等收款说明" onInput={event => setRemark(event.detail.value)} />
        {error && <Text className="distribution-form__error">{error}</Text>}
        <Button className="distribution-primary" loading={submitting} disabled={submitting || submitted || overview.availableCommission <= 0 || !policy.channels.includes('manual')} onClick={submit}>{submitted ? '申请已提交' : '提交提现申请'}</Button>
        <Text className="distribution-note">请核对收款信息。提交后申请金额将冻结，可在提现记录查看审核及付款状态。</Text>
      </View>
    </>}
  </DistributionLayout>;
}
