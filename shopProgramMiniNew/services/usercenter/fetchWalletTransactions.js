import { request } from '../request';

/**
 * 获取钱包流水记录列表
 * @param {Object} params
 * @param {string} params.walletType - 钱包类型（balance/points）
 * @param {number} [params.page=1] - 页码
 * @param {number} [params.pageSize=20] - 每页条数
 * @returns {Promise<{list: Array, total: number}>}
 */
export function fetchWalletTransactions({ walletType, page = 1, pageSize = 20 }) {
  return request({
    url: '/api/v1/member/wallet/transactions',
    method: 'GET',
    data: { walletType, page, pageSize },
    needAuth: true,
  });
}
