export interface GroupBuyPurchaseIntent {
  entry: 'origin' | 'group' | 'join';
  buyOriginalPrice: boolean;
  groupNo: string | null;
}

export interface BuildGroupBuyOrderConfirmItemInput {
  quantity: number;
  spuId: string;
  goodsName: string;
  skuId: string;
  available: boolean;
  price: number;
  specInfo?: any[];
  primaryImage: string;
  title: string;
  orderType: string;
  activityId?: string;
  sessionId?: string;
  groupBuyId?: string;
  intent?: GroupBuyPurchaseIntent | null;
}

export function buildGroupBuyOrderConfirmItem(input: BuildGroupBuyOrderConfirmItemInput) {
  const item: Record<string, any> = {
    quantity: input.quantity,
    storeId: '1',
    spuId: input.spuId,
    goodsName: input.goodsName,
    skuId: input.skuId,
    available: input.available,
    price: input.price,
    specInfo: input.specInfo || [],
    primaryImage: input.primaryImage,
    thumb: input.primaryImage,
    title: input.title,
    orderType: input.orderType || 'normal',
    activityId: input.activityId || undefined,
    sessionId: input.sessionId || undefined,
    groupBuyId: input.orderType === 'group_buy' ? input.groupBuyId || undefined : undefined,
  };

  if (input.orderType !== 'group_buy' || !input.intent) {
    return item;
  }

  if (input.intent.buyOriginalPrice) {
    item.buyOriginalPrice = true;
  }

  if (input.intent.groupNo) {
    item.groupNo = input.intent.groupNo;
  }

  return item;
}
