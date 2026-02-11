/** 获取评价商品 — 后端暂无此接口，使用mock */
export function getGoods(parameter) {
  const { delay } = require('../_utils/delay');
  const { getGoods: mockGetGoods } = require('../../model/submitComment');
  return delay().then(() => mockGetGoods(parameter));
}
