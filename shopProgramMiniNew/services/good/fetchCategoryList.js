import { config } from '../../config/index';
import { request } from '../request';

/** 获取分类列表 (mock) */
function mockFetchGoodCategory() {
  const { delay } = require('../_utils/delay');
  const { getCategoryList } = require('../../model/category');
  return delay().then(() => getCategoryList());
}

/** 获取分类列表 */
export function getCategoryList() {
  if (config.useMock) {
    return mockFetchGoodCategory();
  }
  return request({
    url: '/api/v1/categories',
    method: 'GET',
  }).then((data = {}) => {
    return Array.isArray(data) ? data : data.list || [];
  });
}
