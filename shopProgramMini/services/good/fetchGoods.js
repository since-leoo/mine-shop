import { config } from '../../config/index';
import { request } from '../request';

/** 获取商品列表 */
function mockFetchGoodsList(pageIndex = 1, pageSize = 20) {
  const { delay } = require('../_utils/delay');
  const { getGoodsList } = require('../../model/goods');
  return delay().then(() =>
    getGoodsList(pageIndex, pageSize).map((item) => {
      return {
        spuId: item.spuId,
        thumb: item.primaryImage,
        title: item.title,
        price: item.minSalePrice,
        originPrice: item.maxLinePrice,
        tags: item.spuTagList.map((tag) => tag.title),
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
    data: {
      page,
      page_size: size,
      status: 'active',
    },
  }).then((res = {}) => res.list || []);
}
