import { config } from '../../config/index';

/** 获取商品详情页评论数 (mock) */
function mockFetchGoodDetailsCommentsCount(spuId = 0) {
  const { delay } = require('../_utils/delay');
  const { getGoodsDetailsCommentsCount } = require('../../model/detailsComments');
  return delay().then(() => getGoodsDetailsCommentsCount(spuId));
}

/** 获取商品详情页评论数 — 后端暂无此接口，使用mock */
export function getGoodsDetailsCommentsCount(spuId = 0) {
  return mockFetchGoodDetailsCommentsCount(spuId);
}

/** 获取商品详情页评论 (mock) */
function mockFetchGoodDetailsCommentList(spuId = 0) {
  const { delay } = require('../_utils/delay');
  const { getGoodsDetailsComments } = require('../../model/detailsComments');
  return delay().then(() => getGoodsDetailsComments(spuId));
}

/** 获取商品详情页评论 — 后端暂无此接口，使用mock */
export function getGoodsDetailsCommentList(spuId = 0) {
  return mockFetchGoodDetailsCommentList(spuId);
}
