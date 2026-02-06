import { config } from '../../config/index';
import { request } from '../request';

const DEFAULT_ICON =
  'https://tdesign.gtimg.com/miniprogram/template/retail/category/category-default.png';

/** 获取商品列表 */
function mockFetchGoodCategory() {
  const { delay } = require('../_utils/delay');
  const { getCategoryList } = require('../../model/category');
  return delay().then(() => getCategoryList());
}

/** 获取商品列表 */
export function getCategoryList() {
  if (config.useMock) {
    return mockFetchGoodCategory();
  }

  return request({ url: '/api/v1/categories', method: 'GET' }).then((data = {}) => {
    const list = Array.isArray(data.list) ? data.list : [];
    return list.map(convertNode);
  });
}

function convertNode(node) {
  const children = Array.isArray(node.children) ? node.children.map(convertNode) : [];
  const thumbnail = node.thumbnail || node.icon || DEFAULT_ICON;
  return {
    groupId: node.categoryId ?? node.id ?? '',
    name: node.title ?? node.name ?? '',
    thumbnail,
    children,
  };
}
