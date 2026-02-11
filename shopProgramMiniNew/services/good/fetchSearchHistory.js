import { config } from '../../config/index';

const SEARCH_HISTORY_KEY = 'searchHistory';

/** 获取搜索历史 (mock) */
function mockSearchHistory() {
  const { delay } = require('../_utils/delay');
  const { getSearchHistory } = require('../../model/search');
  return delay().then(() => getSearchHistory());
}

/** 获取搜索历史 */
export function getSearchHistory() {
  if (config.useMock) {
    return mockSearchHistory();
  }
  // 搜索历史存本地
  const history = wx.getStorageSync(SEARCH_HISTORY_KEY) || [];
  return Promise.resolve({ historyWords: history });
}

/** 获取热门搜索 (mock) */
function mockSearchPopular() {
  const { delay } = require('../_utils/delay');
  const { getSearchPopular } = require('../../model/search');
  return delay().then(() => getSearchPopular());
}

/** 获取热门搜索 */
export function getSearchPopular() {
  if (config.useMock) {
    return mockSearchPopular();
  }
  // 热门搜索可以从后端获取，暂用本地默认
  return Promise.resolve({
    popularWords: ['手机', '电脑', '耳机', '运动鞋', '连衣裙'],
  });
}
