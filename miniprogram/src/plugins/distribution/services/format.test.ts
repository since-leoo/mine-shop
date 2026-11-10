import { describe, expect, it } from 'vitest';
import { distributionVisible, hasDistributionHistory, money, yuanToFen } from './format';
import type { DistributionOverview, DistributionStatus } from './index';

const enabled: DistributionStatus = { enabled: true, canInvite: true, inviteCode: 'ABC123', directCount: 0, directLimit: 3, remainingSlots: 3, h5Enabled: true, miniProgramEnabled: true };

describe('distribution amounts', () => {
  it('converts integer fen without confusing API amounts with yuan', () => {
    expect(money(12345)).toBe('123.45');
    expect(money(-1)).toBe('-0.01');
    expect(money(undefined)).toBe('0.00');
    expect(yuanToFen('0.29')).toBe(29);
    expect(yuanToFen('123.45')).toBe(12345);
    expect(yuanToFen('10.1')).toBe(1010);
  });
  it.each(['-1', '0', '0.001', '1e3', 'NaN', '', '1.', '1,000', '9007199254740992'])('rejects invalid withdrawal input %s', value => {
    expect(yuanToFen(value)).toBeNull();
  });
});

describe('distribution visibility and retained accounts', () => {
  it('requires the global switch as well as the current platform switch', () => {
    expect(distributionVisible(null, false)).toBe(false);
    expect(distributionVisible({ ...enabled, enabled: false }, true)).toBe(false);
    expect(distributionVisible({ ...enabled, h5Enabled: false }, false)).toBe(false);
    expect(distributionVisible({ ...enabled, h5Enabled: false }, true)).toBe(true);
    expect(distributionVisible({ ...enabled, miniProgramEnabled: false }, true)).toBe(false);
  });
  it('keeps history available after promotion is disabled and balances are exhausted', () => {
    expect(hasDistributionHistory({ ...enabled, enabled: false, hasHistory: true }, null)).toBe(true);
    expect(hasDistributionHistory(null, { withdrawnCommission: 9900 } as DistributionOverview)).toBe(true);
    expect(hasDistributionHistory(null, null)).toBe(false);
  });
});
