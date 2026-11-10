import { Button, Image, Text, View } from '@tarojs/components';
import Taro, { useShareAppMessage } from '@tarojs/taro';
import { useEffect, useRef, useState } from 'react';
import { isMiniProgram } from '../../../../common/platform';
import { fetchDistributionStatus, type DistributionStatus } from '../../services';
import { distributionVisible } from '../../services/format';
import { createDistributionShare } from '../../share';
import { DistributionLayout, LoadState } from '../shared';

export default function DistributionInvite() {
  const [status, setStatus] = useState<DistributionStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [sharePath, setSharePath] = useState('');
  const [h5Url, setH5Url] = useState('');
  const [imageUrl, setImageUrl] = useState('');
  const [qrError, setQrError] = useState('');
  const [linkBusy, setLinkBusy] = useState('');
  const [generatedUrl, setGeneratedUrl] = useState('');
  const active = useRef(true);
  const weapp = isMiniProgram();
  useShareAppMessage(() => ({ title: '邀请你逛逛我的商城', path: sharePath || '/pages/home/index' }));
  const qr = async () => {
    setQrError('');
    try {
      const result = await createDistributionShare({ target: 'home', channel: 'qrcode' });
      if (!active.current) return;
      if (result.imageUrl) setImageUrl(result.imageUrl);
      else setQrError('暂未生成邀请二维码，可使用下方邀请链接');
    } catch (err: any) { if (active.current) setQrError(err?.msg || '二维码生成失败，可使用下方邀请链接'); }
  };
  const load = async () => {
    setLoading(true); setError(''); setSharePath(''); setH5Url(''); setImageUrl(''); setGeneratedUrl('');
    try {
      const nextStatus = await fetchDistributionStatus();
      if (!active.current) return;
      setStatus(nextStatus);
      if (distributionVisible(nextStatus, weapp) && nextStatus.canInvite) {
        const result = await createDistributionShare({ target: 'home', channel: weapp ? 'card' : 'link' });
        if (!active.current) return;
        if (!result.shareToken) throw new Error('当前无法生成推广邀请，请刷新后重试');
        setSharePath(result.path || ''); setH5Url(result.h5Url || result.url || '');
        void qr();
      }
    } catch (err: any) { if (active.current) setError(err?.msg || err?.message || '邀请信息加载失败'); }
    finally { if (active.current) setLoading(false); }
  };
  useEffect(() => { active.current = true; void load(); return () => { active.current = false; }; }, []);
  const copy = (value: string) => { if (value) Taro.setClipboardData({ data: value }); };
  const generateLink = async (channel: 'short_link' | 'url_link') => {
    if (linkBusy) return;
    setLinkBusy(channel);
    try {
      const result = await createDistributionShare({ target: 'home', channel });
      const value = result.url;
      if (!value) throw new Error('当前无法生成此类链接，请使用普通邀请链接或微信转发');
      setGeneratedUrl(value); copy(value);
    } catch (err: any) { Taro.showToast({ title: err?.msg || err?.message || '生成失败，请使用普通邀请链接', icon: 'none', duration: 3000 }); }
    finally { setLinkBusy(''); }
  };
  const enabled = distributionVisible(status, weapp);
  return <DistributionLayout title="邀请好友">
    <LoadState loading={loading} error={error} onRetry={load} />
    {!loading && status && !enabled && <View className="distribution-notice">推广服务暂未开放，当前不能生成邀请链接。</View>}
    {!loading && !error && status && enabled && <>
      <View className="distribution-card distribution-invite">
        <Text className="distribution-title">我的邀请码</Text><Text className="distribution-invite__code">{status.inviteCode || '—'}</Text>
        {status.inviteCode && <Text className="distribution-link" onClick={() => copy(status.inviteCode)}>复制邀请码</Text>}
        <Text className="distribution-note">直属粉丝 {status.directCount} / {status.directLimit} · 剩余 {status.remainingSlots} 个名额</Text>
        {!status.canInvite ? <View className="distribution-notice">当前不能继续邀请新成员。已有关系和历史佣金不受影响。</View> : <>
          {imageUrl ? <Image className="distribution-invite__qr" src={imageUrl} mode="aspectFit" showMenuByLongpress onError={() => { setImageUrl(''); setQrError('二维码加载失败，请重试或复制邀请链接'); }} /> : <Text className="distribution-note">{qrError || '正在生成邀请二维码…'}</Text>}
          {qrError && <Text className="distribution-link" onClick={qr}>重新生成二维码</Text>}
          {weapp && <Button className="distribution-primary" openType="share" disabled={!sharePath}>分享给微信好友</Button>}
          <Button className="distribution-secondary" disabled={!h5Url} onClick={() => copy(h5Url)}>复制邀请链接</Button>
          <View className="distribution-grid"><Button className="distribution-secondary" loading={linkBusy === 'short_link'} disabled={!!linkBusy} onClick={() => generateLink('short_link')}>生成短链接</Button><Button className="distribution-secondary" loading={linkBusy === 'url_link'} disabled={!!linkBusy} onClick={() => generateLink('url_link')}>生成微信跳转链接</Button></View>
          {generatedUrl && <Text className="distribution-invite__url" selectable onClick={() => copy(generatedUrl)}>{generatedUrl}</Text>}
        </>}
      </View>
      <Text className="distribution-note">好友通过您的专属链接完成首次注册后建立直属关系，每人最多 {status.directLimit} 位直属粉丝。已注册用户不会更换邀请人，名额以注册时为准。</Text>
      <Text className="distribution-note">短链接及微信跳转链接受平台能力和有效期限制，生成失败时仍可使用普通邀请链接或微信转发。</Text>
    </>}
  </DistributionLayout>;
}
