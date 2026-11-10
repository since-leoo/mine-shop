import { describe, expect, it, vi } from 'vitest';
vi.mock('@tarojs/components', () => ({ Text: 'text', View: 'view' }));
vi.mock('@tarojs/taro', () => ({ default: { navigateTo: vi.fn(), getEnv: () => 'WEB', ENV_TYPE: { WEB: 'WEB', WEAPP: 'WEAPP' } }, useDidShow: vi.fn() }));
vi.mock('../../../../common/auth', () => ({ getStoredToken: () => 'token', isLoggedIn: () => true }));
vi.mock('../../services', () => ({ fetchDistributionOverview: vi.fn(), fetchDistributionStatus: vi.fn() }));
import { DistributionCenterView } from './index';
import type { DistributionOverview, DistributionStatus } from '../../services';

const status: DistributionStatus = { enabled: true, h5Enabled: true, miniProgramEnabled: true, canInvite: true, inviteCode: 'ABC123', directCount: 1, directLimit: 3, remainingSlots: 2 };
const overview = { teamCount: 12, paidOrderCount: 20, availableCommission: 2990 } as DistributionOverview;
const component = { id: 'distribution', type: 'distribution-center', props: { title: '我的推广' } };
function content(node: any): string {
  if (node == null || typeof node === 'boolean') return '';
  if (typeof node === 'string' || typeof node === 'number') return String(node);
  if (Array.isArray(node)) return node.map(content).join('');
  return content(node.props?.children);
}

describe('DIY distribution component', () => {
  it('does not show financial or team data when the plugin/platform is off', () => {
    expect(DistributionCenterView({ component, status: { ...status, enabled: false }, overview, miniProgram: false })).toBeNull();
    expect(DistributionCenterView({ component, status: { ...status, h5Enabled: false }, overview, miniProgram: false })).toBeNull();
    expect(DistributionCenterView({ component, status: null, overview, miniProgram: false })).toBeNull();
  });
  it('renders real statistics in yuan and respects each published item switch', () => {
    const text = content(DistributionCenterView({ component, status, overview, miniProgram: false }));
    expect(text).toContain('我的推广'); expect(text).toContain('¥29.90'); expect(text).toContain('剩余 2 个直属名额');
    const hidden = content(DistributionCenterView({ component: { ...component, props: { showInvite: false, showTeam: false, showOrders: false, showCommission: false } }, status, overview, miniProgram: false }));
    expect(hidden).not.toContain('邀请好友'); expect(hidden).not.toContain('团队成员'); expect(hidden).not.toContain('消费订单'); expect(hidden).not.toContain('29.90');
  });
});
