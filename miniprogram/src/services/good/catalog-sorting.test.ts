import { beforeEach, describe, expect, it, vi } from 'vitest';
const { request } = vi.hoisted(() => ({ request: vi.fn() }));
vi.mock('../request', () => ({ request }));
vi.mock('../../config', () => ({ config: { useMock: false } }));
import { fetchGoodsList } from './fetchGoodsList';
import { getSearchResult } from './fetchSearchResult';
import { fetchSeckillGood } from './fetchGood';

describe('商品查询接口契约', () => {
  beforeEach(() => { request.mockReset(); request.mockResolvedValue({ list: [], pagination: { total: 0 } }); });
  it.each([fetchGoodsList, getSearchResult])('升降价格传不同枚举，默认排序保持未指定', async fetcher => {
    await fetcher({ keyword: '花瓶', pageNum: 2, sort: 1, sortType: 0 });
    expect(request.mock.calls[0][0].data).toMatchObject({ sort: 'price_asc', keyword: '花瓶', page: 2 });
    await fetcher({ sort: 1, sortType: 1 });
    expect(request.mock.calls[1][0].data.sort).toBe('price_desc');
    await fetcher({ sort: 0 });
    expect(request.mock.calls[2][0].data.sort).toBeUndefined();
  });
  it('秒杀查询带场次且保持服务端分值、库存和限购元数据', async () => {
    const detail = { minSalePrice: 599, activityInfo: { stock: 8, maxQuantityPerUser: 2 } };
    request.mockResolvedValueOnce(detail);
    expect(await fetchSeckillGood('8', '102')).toBe(detail);
    expect(request).toHaveBeenCalledWith({ url: '/api/v1/seckill/products/8/102', method: 'GET' });
  });
});
