import { beforeEach, expect, it, vi } from 'vitest';
import { fetchOrderDetail } from './orderDetail';
import { request } from '../request';
vi.mock('../request', () => ({ request: vi.fn().mockResolvedValue({ orderNo: 'ORDER/7' }) }));
beforeEach(() => vi.clearAllMocks());
it('缺少编号时拒绝请求，不回退为模拟订单', async () => {
  for (const input of [undefined, {}, { orderNo: '' }, '   ']) await expect(fetchOrderDetail(input)).rejects.toThrow('缺少订单编号');
  expect(request).not.toHaveBeenCalled();
});
it('详情编号作为单一路径段编码并始终要求会员认证', async () => {
  await fetchOrderDetail({ orderNo: ' ORDER/7 ' });
  expect(request).toHaveBeenCalledWith({ url: '/api/v1/order/detail/ORDER%2F7', method: 'GET', needAuth: true });
});
