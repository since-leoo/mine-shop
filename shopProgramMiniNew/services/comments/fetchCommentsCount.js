import { request } from '../request';

/**
 * 获取商品评论统计 — 调用真实 API
 * GET /api/v1/review/product/{productId}/stats
 *
 * @param {object|number} params - 商品ID 或 { spuId }
 * @returns {Promise<{commentCount, badCount, middleCount, goodCount, hasImageCount, goodRate}>}
 */
export function fetchCommentsCount(params) {
  const spuId = typeof params === 'object' ? params.spuId : params;

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
