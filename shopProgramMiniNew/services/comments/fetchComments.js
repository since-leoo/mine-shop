/** 获取商品评论 — 后端暂无此接口，使用mock */
export function fetchComments(params) {
  const { delay } = require('../_utils/delay');
  const { getGoodsAllComments } = require('../../model/comments');
  return delay().then(() => getGoodsAllComments(params));
}
