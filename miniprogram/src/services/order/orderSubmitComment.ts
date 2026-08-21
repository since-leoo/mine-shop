import { request } from '../request';

/** 获取待评价商品：复用订单详情接口，避免使用本地 mock。 */
export function getGoods(parameter: any) {
  const orderNo = parameter?.orderNo || parameter?.tradeNo || parameter;
  if (!orderNo) return Promise.resolve([]);

  return request({
    url: `/api/v1/order/detail/${encodeURIComponent(String(orderNo))}`,
    method: 'GET',
    needAuth: true,
  }).then((data: any) => {
    const order = data?.order || data || {};
    const items = Array.isArray(order.items) ? order.items : [];
    return items.map((item: any) => ({
      orderId: order.id || order.orderId,
      orderItemId: item.id || item.orderItemId,
      productId: item.spuId || item.productId,
      productName: item.productName || item.title || '',
      productImage: item.productImage || item.thumb || item.image || '',
      skuName: item.skuName || item.specs || '',
    }));
  });
}
