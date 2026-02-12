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
  const history = wx.getStorageSync(SEARCH_HISTORY_KEY) || [];
  return Promise.resolve({ historyWords: history });
}

/** 保存搜索关键词到历史 */
export function addSearchHistory(keyword) {
  if (!keyword || !keyword.trim()) return;
  const word = keyword.trim();
  let history = wx.getStorageSync(SEARCH_HISTORY_KEY) || [];
  // 去重，最新的放最前面，最多保留20条
  history = history.filter((w) => w !== word);
  history.unshift(word);
  if (history.length > 20) history = history.slice(0, 20);
  wx.setStorageSync(SEARCH_HISTORY_KEY, history);
}

/** 清除搜索历史 */
export function clearSearchHistory() {
  wx.removeStorageSync(SEARCH_HISTORY_KEY);
}

/** 删除单条搜索历史 */
export function removeSearchHistoryItem(index) {
  const history = wx.getStorageSync(SEARCH_HISTORY_KEY) || [];
  history.splice(index, 1);
  wx.setStorageSync(SEARCH_HISTORY_KEY, history);
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
