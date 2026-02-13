import { request } from '../request';

/**
 * 获取商品评论列表 — 调用真实 API
 * GET /api/v1/review/product/{productId}
 *
 * @param {object} params - 查询参数 { pageNum, pageSize, queryParameter: { spuId, commentLevel?, hasImage? } }
 * @returns {Promise<{pageNum, pageSize, totalCount, pageList}>}
 */
export function fetchComments(params) {
  const { pageNum = 1, pageSize = 10, queryParameter = {} } = params || {};
  const { spuId, commentLevel, hasImage } = queryParameter;

  const query = { page: pageNum, page_size: pageSize };

  // 评分等级映射：1=差评, 2=中评, 3=好评
  if (commentLevel === 1) {
    query.rating_level = 'bad';
  } else if (commentLevel === 2) {
    query.rating_level = 'medium';
  } else if (commentLevel === 3) {
    query.rating_level = 'good';
  }

  if (hasImage) {
    query.has_images = 1;
  }

  return request({
    url: `/api/v1/review/product/${spuId}`,
    method: 'GET',
    data: query,
  }).then((data) => {
    const { total = 0, list = [], page = 1, pageSize: ps = 10 } = data || {};

    const pageList = list.map((item) => ({
      spuId: String(spuId),
      skuId: '0',
      specInfo: '',
      commentContent: item.content || '',
      commentResources: (item.images || []).map((src) => ({ src, type: 'image' })),
      commentScore: item.rating || 5,
      uid: String(item.id || ''),
      userName: item.nickname || '匿名用户',
      userHeadUrl: item.avatar || '',
      isAnonymity: item.isAnonymous || false,
      commentTime: item.createdAt
        ? String(new Date(item.createdAt).getTime())
        : String(Date.now()),
      isAutoComment: false,
      sellerReply: item.adminReply || '',
      goodsDetailInfo: '',
    }));

    return {
      pageNum: page,
      pageSize: ps,
      totalCount: String(total),
      pageList,
    };
  });
}
