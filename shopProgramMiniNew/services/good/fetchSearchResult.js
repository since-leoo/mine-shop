/* eslint-disable no-param-reassign */
import { config } from '../../config/index';
import { request } from '../request';

/** 获取搜索结果 (mock) */
function mockSearchResult(params) {
  const { delay } = require('../_utils/delay');
  const { getSearchResult } = require('../../model/search');

  const data = getSearchResult(params);
  if (data.spuList.length) {
    data.spuList.forEach((item) => {
      item.thumb = item.primaryImage;
      item.price = item.minSalePrice;
      item.originPrice = item.maxLinePrice;
      item.tags = item.spuTagList ? item.spuTagList.map((tag) => ({ title: tag.title })) : [];
    });
  }
  return delay().then(() => data);
}

/** 获取搜索结果 */
export function getSearchResult(params) {
  if (config.useMock) {
    return mockSearchResult(params);
  }
  const { keyword, pageNum = 1, pageSize = 20, sort } = params || {};
  return request({
    url: '/api/v1/products',
    method: 'GET',
    data: {
      keyword: keyword || undefined,
      page: pageNum,
      page_size: pageSize,
      sort: sort || undefined,
      status: 'active',
    },
  }).then((res = {}) => {
    const list = (res.list || []).map((item) => ({
      ...item,
      thumb: item.thumb || item.primaryImage,
      price: item.price || item.minSalePrice,
      originPrice: item.originPrice || item.maxLinePrice,
      tags: item.tags || [],
    }));
    return {
      spuList: list,
      totalCount: res.pagination?.total || list.length,
    };
  });
}
