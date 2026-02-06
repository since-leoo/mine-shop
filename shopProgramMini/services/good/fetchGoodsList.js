/* eslint-disable no-param-reassign */
import { config } from '../../config/index';
import { request } from '../request';

/** 获取商品列表 */
function mockFetchGoodsList(params) {
  const { delay } = require('../_utils/delay');
  const { getSearchResult } = require('../../model/search');

  const data = getSearchResult(params);

  if (data.spuList.length) {
    data.spuList.forEach((item) => {
      item.spuId = item.spuId;
      item.thumb = item.primaryImage;
      item.title = item.title;
      item.price = item.minSalePrice;
      item.originPrice = item.maxLinePrice;
      item.desc = '';
      if (item.spuTagList) {
        item.tags = item.spuTagList.map((tag) => tag.title);
      } else {
        item.tags = [];
      }
    });
  }
  return delay().then(() => {
    return data;
  });
}

/** 获取商品列表 */
export function fetchGoodsList(params = {}) {
  if (config.useMock) {
    return mockFetchGoodsList(params);
  }

  const query = {
    page: params.pageNum || 1,
    page_size: params.pageSize || 30,
    status: 'active',
  };

  if (params.keyword) {
    query.keyword = params.keyword;
  }
  if (params.categoryId) {
    query.category_id = Number(params.categoryId);
  }
  if (typeof params.rawMinPrice === 'number' && !Number.isNaN(params.rawMinPrice)) {
    query.min_price = params.rawMinPrice;
  }
  if (typeof params.rawMaxPrice === 'number' && !Number.isNaN(params.rawMaxPrice)) {
    query.max_price = params.rawMaxPrice;
  }

  return request({
    url: '/api/v1/products',
    method: 'GET',
    data: query,
  }).then((res = {}) => {
    const list = Array.isArray(res.list) ? res.list : [];
    return {
      spuList: list,
      totalCount: res.total || 0,
    };
  });
}
