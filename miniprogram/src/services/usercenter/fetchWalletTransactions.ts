import { request } from '../request';

/**
 * 获取钱包流水记录列表
 */
export function fetchWalletTransactions({
  walletType,
  page = 1,
  pageSize = 20,
}: {
  walletType: string;
  page?: number;
  pageSize?: number;
}) {
  return request({
    url: '/api/v1/member/wallet/transactions',
    method: 'GET',
    data: { walletType, page, pageSize },
    needAuth: true,
  });
}
