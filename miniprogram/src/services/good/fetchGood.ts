import { config } from '../../config';
import { request } from '../request';

/** 获取商品详情 (mock) */
function mockFetchGood(ID: number | string = 0) {
  const { delay } = require('../_utils/delay');
  const { genGood } = require('../../model/good');
  return delay().then(() => genGood(ID));
}

/** 获取商品详情 */
export function fetchGood(ID: number | string = 0) {
  if (config.useMock) {
    return mockFetchGood(ID);
  }
  return request({
    url: `/api/v1/products/${ID}`,
    method: 'GET',
  });
}

/** 秒杀详情包含活动 SKU、限购、库存及有效时间，不能用普通售价替代。 */
export function fetchSeckillGood(sessionId: string, productId: string) {
  return request({ url: `/api/v1/seckill/products/${sessionId}/${productId}`, method: 'GET' });
}
