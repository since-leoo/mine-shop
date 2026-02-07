import { mockIp, mockReqId } from '../../utils/mock';

/** 生成结算数据（单店铺扁平结构，金额单位：分） */
export function genSettleDetail(params) {
  const { userAddressReq, couponList, goodsRequestList } = params;

  const items = Array.isArray(goodsRequestList)
    ? goodsRequestList.map((goods) => ({
        product_id: goods.spuId || goods.productId || 0,
        sku_id: goods.skuId || 0,
        product_name: goods.title || goods.productName || '',
        sku_name: goods.skuName || '',
        product_image: goods.primaryImage || goods.productImage || '',
        spec_values: goods.specInfo || goods.specValues || [],
        unit_price: Number(goods.price || goods.unitPrice || 0),
        quantity: Number(goods.quantity || 1),
        total_price: Number(goods.price || goods.unitPrice || 0) * Number(goods.quantity || 1),
        weight: goods.weight || 0,
      }))
    : [];

  // 计算总价（分）
  const goodsAmount = items.reduce((sum, item) => sum + item.total_price, 0);

  // 计算优惠券折扣（分）
  let couponAmount = 0;
  if (couponList && couponList.length > 0) {
    couponList.forEach((coupon) => {
      if (coupon.status === 'default') {
        if (coupon.type === 1) {
          couponAmount += Number(coupon.value || 0);
        } else if (coupon.type === 2) {
          couponAmount += Math.round(goodsAmount * Number(coupon.value || 0) / 10);
        }
      }
    });
  }

  const discountAmount = 0;
  const shippingFee = 0;
  const totalAmount = goodsAmount - discountAmount;
  const payAmount = totalAmount + shippingFee - couponAmount;

  const goodsCount = items.reduce((sum, item) => sum + item.quantity, 0);

  const resp = {
    data: {
      settle_type: userAddressReq ? 1 : 0,
      user_address: userAddressReq
        ? {
            name: userAddressReq.name || '',
            phone: userAddressReq.phone || '',
            province: userAddressReq.province || '',
            city: userAddressReq.city || '',
            district: userAddressReq.district || '',
            detail_address: userAddressReq.detail || '',
            full_address: [
              userAddressReq.province,
              userAddressReq.city,
              userAddressReq.district,
              userAddressReq.detail,
            ]
              .filter(Boolean)
              .join(''),
            checked: true,
          }
        : null,
      store_name: '云Mall深圳旗舰店',
      goods_count: goodsCount,
      items,
      price: {
        goods_amount: goodsAmount,
        discount_amount: discountAmount,
        shipping_fee: shippingFee,
        total_amount: totalAmount,
        pay_amount: payAmount,
      },
      coupon_amount: couponAmount,
      invoice_support: 1,
    },
    code: 'Success',
    msg: null,
    requestId: mockReqId(),
    clientIp: mockIp(),
    rt: 244,
    success: true,
  };

  return resp;
}
