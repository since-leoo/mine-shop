import { config } from '../../config/index';

/** 获取商品详情页评论数 */
function mockFetchGoodDetailsCommentsCount(spuId = 0) {
  const { delay } = require('../_utils/delay');
  const {
    getGoodsDetailsCommentsCount,
  } = require('../../model/detailsComments');
  return delay().then(() => getGoodsDetailsCommentsCount(spuId));
}

/** 获取商品详情页评论数 */
export function getGoodsDetailsCommentsCount(spuId = 0) {
  if (config.useMock) {
    return mockFetchGoodDetailsCommentsCount(spuId);
  }
  return Promise.resolve({
    badCount: 0,
    commentCount: 0,
    goodCount: 0,
    goodRate: 0,
    hasImageCount: 0,
    middleCount: 0,
  });
}

/** 获取商品详情页评论 */
function mockFetchGoodDetailsCommentList(spuId = 0) {
  const { delay } = require('../_utils/delay');
  const { getGoodsDetailsComments } = require('../../model/detailsComments');
  return delay().then(() => getGoodsDetailsComments(spuId));
}

/** 获取商品详情页评论 */
export function getGoodsDetailsCommentList(spuId = 0) {
  if (config.useMock) {
    return mockFetchGoodDetailsCommentList(spuId);
  }
  return Promise.resolve({
    homePageComments: [],
  });
}
