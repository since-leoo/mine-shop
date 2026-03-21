export {
  fetchAfterSaleEligibility as fetchRightsPreview,
  createAfterSale as dispatchApplyService,
} from './afterSale';

export function fetchApplyReasonList() {
  return Promise.resolve([
    '商品质量问题',
    '商品与描述不符',
    '少件/漏发',
    '尺寸不合适',
    '不想要了',
    '其他',
  ]);
}

export function dispatchConfirmReceived() {
  return Promise.resolve(true);
}
