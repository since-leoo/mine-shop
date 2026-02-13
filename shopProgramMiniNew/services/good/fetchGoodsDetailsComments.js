import { request } from '../request';

/**
 * 获取商品详情页评论统计 — 调用真实 API
 * GET /api/v1/review/product/{productId}/stats
 *
 * @param {number|string} spuId - 商品ID
 * @returns {Promise<{commentCount, badCount, middleCount, goodCount, hasImageCount, goodRate}>}
 */
export function getGoodsDetailsCommentsCount(spuId = 0) {
  return request({
    url: `/api/v1/review/product/${spuId}/stats`,
    method: 'GET',
  }).then((data) => {
    const { total = 0, good = 0, medium = 0, bad = 0, withImages = 0 } = data || {};
    const goodRate = total > 0 ? Math.round(((good / total) * 100) * 10) / 10 : 100;

    return {
      commentCount: String(total),
      badCount: String(bad),
      middleCount: String(medium),
      goodCount: String(good),
      hasImageCount: String(withImages),
      goodRate,
      uidCount: '0',
    };
  });
}

/**
 * 获取商品详情页评论列表 — 调用真实 API
 * GET /api/v1/review/product/{productId}/summary
 *
 * @param {number|string} spuId - 商品ID
 * @returns {Promise<{homePageComments: Array}>}
 */
export function getGoodsDetailsCommentList(spuId = 0) {
  return request({
    url: `/api/v1/review/product/${spuId}/summary`,
    method: 'GET',
  }).then((data) => {
    const { list = [] } = data || {};

    const homePageComments = list.map((item) => ({
      spuId: String(spuId),
      skuId: null,
      specInfo: null,
      commentContent: item.content || '',
      commentScore: item.rating || 5,
      uid: String(item.id || ''),
      userName: item.nickname || '匿名用户',
      userHeadUrl: item.avatar || '',
    }));

    return { homePageComments };
  });
}
