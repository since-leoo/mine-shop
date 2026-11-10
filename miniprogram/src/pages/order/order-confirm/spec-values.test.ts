import { describe, expect, it } from 'vitest';
import { formatOrderSpecValues } from './spec-values';

describe('订单确认页规格显示', () => {
  it.each([
    ['具名规格', [{ name: '颜色', value: '红色' }, { name: '尺码', value: 'XL' }]],
    ['历史 valueName 规格', [{ valueName: '红色' }, { valueName: 'XL' }]],
    ['字符串数组', ['红色', 'XL']],
    ['对象映射', { 颜色: '红色', 尺码: 'XL' }],
    ['历史单字符串', '红色 XL'],
  ])('兼容%s', (_label, values) => {
    expect(formatOrderSpecValues(values)).toBe('红色 XL');
  });

  it('优先使用当前 value，保留数值零，忽略空值和损坏对象', () => {
    expect(formatOrderSpecValues([
      { name: '颜色', value: '红色', valueName: '旧颜色' },
      { name: '编号', value: 0 },
      { name: '损坏规格', value: { invalid: true } },
      { name: '空规格' },
      null,
      '',
    ])).toBe('红色 0');
  });

  it.each([undefined, null, [], {}, false])('无规格 %j 时显示为空', (values) => {
    expect(formatOrderSpecValues(values)).toBe('');
  });
});
