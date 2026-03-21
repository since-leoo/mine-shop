import { request } from '../request';

/**
 * 获取商品详情页评论统计
 * GET /api/v1/review/product/{productId}/stats
 */
export function getGoodsDetailsCommentsCount(spuId: number | string = 0) {
  return request({
    url: `/api/v1/review/product/${spuId}/stats`,
    method: 'GET',
  }).then((data: any) => {
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
 * 获取商品详情页评论列表
 * GET /api/v1/review/product/{productId}/summary
 */
export function getGoodsDetailsCommentList(spuId: number | string = 0) {
  return request({
    url: `/api/v1/review/product/${spuId}/summary`,
    method: 'GET',
  }).then((data: any) => {
    const { list = [] } = data || {};

    const homePageComments = list.map((item: any) => ({
      spuId: String(spuId),
      skuId: null,
      specInfo: item.skuName || '',
      commentContent: item.content || '',
      commentScore: item.rating || 5,
      uid: String(item.id || ''),
      userName: item.nickname || '匿名用户',
      userHeadUrl: item.avatar || '',
      sellerReply: item.adminReply || '',
      commentTime: item.createdAt
        ? String(new Date(item.createdAt).getTime())
        : String(Date.now()),
      commentResources: (item.images || []).map((src: string) => ({ src, type: 'image' })),
    }));

    return { homePageComments };
  });
}
