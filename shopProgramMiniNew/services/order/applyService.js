/** 获取售后单数据 — 后端暂无此接口，使用mock */
export function fetchRightsPreview(params) {
  const { delay } = require('../_utils/delay');
  const { genRightsPreview } = require('../../model/order/applyService');
  return delay().then(() => genRightsPreview(params));
}

/** 确认收货 — 后端暂无售后确认接口，使用mock */
export function dispatchConfirmReceived() {
  const { delay } = require('../_utils/delay');
  return delay();
}

/** 获取可选的售后原因列表 — 后端暂无此接口，使用mock */
export function fetchApplyReasonList(params) {
  const { delay } = require('../_utils/delay');
  const { genApplyReasonList } = require('../../model/order/applyService');
  return delay().then(() => genApplyReasonList(params));
}

/** 发起售后申请 — 后端暂无此接口，使用mock */
export function dispatchApplyService(params) {
  const { delay } = require('../_utils/delay');
  const { applyService } = require('../../model/order/applyService');
  return delay().then(() => applyService(params));
}
