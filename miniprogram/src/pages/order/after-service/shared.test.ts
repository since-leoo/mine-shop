import { expect, it } from 'vitest';
import { buildAfterSaleTimeline, formatAmount } from './shared';
it('金额按分转换，换货完成不生成退款成功记录', () => {
  expect(formatAmount(9999)).toBe('¥99.99');
  const timeline = buildAfterSaleTimeline({ status: 'completed', type: 'exchange', reshipLogisticsNo: 'SF123', reshipLogisticsCompany: '顺丰', createdAt: '', updatedAt: '', reason: '' } as any);
  expect(timeline.some(item => item.title.includes('退款'))).toBe(false);
  expect(timeline.some(item => item.title === '换货流程已完成')).toBe(true);
});
