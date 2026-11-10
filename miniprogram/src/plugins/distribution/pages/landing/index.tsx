import { Button } from '@tarojs/components';
import Taro, { useLoad } from '@tarojs/taro';
import { useEffect, useRef, useState } from 'react';
import { resolveDistributionLanding } from '../../landing';
import { captureDistributionInvite } from '../../share';
import { DistributionLayout, LoadState } from '../shared';

export default function DistributionLandingPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const active = useRef(true);
  const busy = useRef(false);
  const parameters = useRef<Record<string, unknown>>({});
  useEffect(() => { active.current = true; return () => { active.current = false; }; }, []);
  const load = async () => {
    if (busy.current) return;
    busy.current = true; setLoading(true); setError('');
    try {
      const target = await resolveDistributionLanding(parameters.current);
      if (target.shareToken) await captureDistributionInvite({ share_token: target.shareToken });
      if (!active.current) return;
      if (target.home) await Taro.switchTab({ url: target.path });
      else await Taro.redirectTo({ url: target.path });
      if (target.notice) void Taro.showToast({ title: target.notice, icon: 'none', duration: 3000 });
    } catch (err: any) {
      if (active.current) setError(err?.msg || err?.message || '分享打开失败，请重试');
    } finally {
      busy.current = false;
      if (active.current) setLoading(false);
    }
  };
  useLoad((options) => { parameters.current = options || {}; void load(); });
  return <DistributionLayout title="打开分享">
    <LoadState loading={loading} error={error} onRetry={load} />
    {error && <Button className="distribution-secondary" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>返回首页</Button>}
  </DistributionLayout>;
}
