import { config } from '../../config';
import { request } from '../request';

/** 获取商品列表 (mock) */
function mockFetchGoodsList(params: any) {
  const { delay } = require('../_utils/delay');
  const { getSearchResult } = require('../../model/search');

  const data = getSearchResult(params);
  if (data.spuList.length) {
    data.spuList.forEach((item: any) => {
      item.thumb = item.primaryImage;
      item.price = item.minSalePrice;
      item.originPrice = item.maxLinePrice;
      item.desc = '';
      item.tags = item.spuTagList ? item.spuTagList.map((tag: any) => tag.title) : [];
    });
  }
  return delay().then(() => data);
}

/** 获取商品列表 */
export function fetchGoodsList(params: any) {
  if (config.useMock) {
    return mockFetchGoodsList(params);
  }
  const { pageNum = 1, pageSize = 20, keyword, categoryId, sort } = params || {};
  return request({
    url: '/api/v1/products',
    method: 'GET',
    data: {
      page: pageNum,
      page_size: pageSize,
      keyword: keyword || undefined,
      category_id: categoryId || undefined,
      sort: sort || undefined,
      status: 'active',
    },
  }).then((res: any = {}) => {
    const list = (res.list || []).map((item: any) => ({
      ...item,
      thumb: item.thumb ?? item.primaryImage ?? '',
      price: item.price ?? item.minSalePrice ?? 0,
      originPrice: item.originPrice ?? item.maxLinePrice ?? 0,
      tags: item.tags || [],
    }));
    return {
      spuList: list,
      totalCount: res.pagination?.total || list.length,
    };
  });
}
