import { config } from '../../config';
import { request } from '../request';

/** 获取商品列表 (mock) */
function mockFetchGoodsList(pageIndex = 1, pageSize = 20) {
  const { delay } = require('../_utils/delay');
  const { getGoodsList } = require('../../model/goods');
  return delay().then(() =>
    getGoodsList(pageIndex, pageSize).map((item: any) => {
      return {
        spuId: item.spuId,
        thumb: item.thumb ?? item.primaryImage ?? '',
        title: item.title,
        price: item.price ?? item.minSalePrice ?? 0,
        originPrice: item.originPrice ?? item.maxLinePrice ?? 0,
        tags: item.tags ?? item.spuTagList.map((tag: any) => tag.title),
      };
    }),
  );
}

/** 获取商品列表 */
export function fetchGoodsList(pageIndex = 1, pageSize = 20) {
  if (config.useMock) {
    return mockFetchGoodsList(pageIndex, pageSize);
  }
  const page = pageIndex && pageIndex > 0 ? pageIndex : 1;
  const size = pageSize && pageSize > 0 ? pageSize : 20;
  return request({
    url: '/api/v1/products',
    method: 'GET',
    data: { page, page_size: size, status: 'active' },
  }).then((res: any = {}) => res.list || []);
}

/** 获取热门商品 */
export function fetchHotGoods(pageSize = 6) {
  if (config.useMock) {
    return mockFetchGoodsList(1, pageSize);
  }
  return request({
    url: '/api/v1/products',
    method: 'GET',
    data: { page: 1, page_size: pageSize, status: 'active', is_hot: 1 },
  }).then((res: any = {}) => res.list || []);
}

/** 获取推荐商品 */
export function fetchRecommendGoods(pageSize = 6) {
  if (config.useMock) {
    return mockFetchGoodsList(1, pageSize);
  }
  return request({
    url: '/api/v1/products',
    method: 'GET',
    data: { page: 1, page_size: pageSize, status: 'active', is_recommend: 1 },
  }).then((res: any = {}) => res.list || []);
}
