/** 获取商品评论数 — 后端暂无此接口，使用mock */
export function fetchCommentsCount(ID = 0) {
  const { delay } = require('../_utils/delay');
  const { getGoodsCommentsCount } = require('../../model/comments');
  return delay().then(() => getGoodsCommentsCount(ID));
}
