import detailCartLineIcon from '../../assets/detail-bottom/cart-line.svg';
import detailHomeLineIcon from '../../assets/detail-bottom/home-line.svg';
import homeQuickFireIcon from '../../assets/home-quick/fire.svg';
import homeQuickGiftIcon from '../../assets/home-quick/gift.svg';
import homeQuickGridIcon from '../../assets/home-quick/grid.svg';
import homeQuickGroupIcon from '../../assets/home-quick/group.svg';
import homeQuickLeafIcon from '../../assets/home-quick/leaf.svg';
import homeQuickTagIcon from '../../assets/home-quick/tag.svg';
import homeSearchLineIcon from '../../assets/home-top/search-line.svg';
import tabCartIcon from '../../assets/tab/cart.png';
import tabCartActiveIcon from '../../assets/tab/cart-active.png';
import tabCategoryIcon from '../../assets/tab/category.png';
import tabCategoryActiveIcon from '../../assets/tab/category-active.png';
import tabHomeIcon from '../../assets/tab/home.png';
import tabHomeActiveIcon from '../../assets/tab/home-active.png';
import tabUserIcon from '../../assets/tab/user.png';
import tabUserActiveIcon from '../../assets/tab/user-active.png';
import userMenuAddressIcon from '../../assets/usercenter/menu-address.svg';
import userMenuCouponIcon from '../../assets/usercenter/menu-coupon.svg';
import userMenuHelpIcon from '../../assets/usercenter/menu-help.svg';
import userMenuSettingsIcon from '../../assets/usercenter/menu-settings.svg';
import userMenuWalletIcon from '../../assets/usercenter/menu-wallet.svg';
import userOrderDeliverIcon from '../../assets/usercenter/order-deliver.svg';
import userOrderPayIcon from '../../assets/usercenter/order-pay.svg';
import userOrderReceiveIcon from '../../assets/usercenter/order-receive.svg';
import userOrderReviewIcon from '../../assets/usercenter/order-review.svg';
import userOrderServiceIcon from '../../assets/usercenter/order-service.svg';
import userProfileQrcodeIcon from '../../assets/usercenter/profile-qrcode.svg';

const diyAssetMap: Record<string, string> = {
  'assets/detail-bottom/cart-line.svg': detailCartLineIcon,
  'assets/detail-bottom/home-line.svg': detailHomeLineIcon,
  'assets/home-quick/fire.svg': homeQuickFireIcon,
  'assets/home-quick/gift.svg': homeQuickGiftIcon,
  'assets/home-quick/grid.svg': homeQuickGridIcon,
  'assets/home-quick/group.svg': homeQuickGroupIcon,
  'assets/home-quick/leaf.svg': homeQuickLeafIcon,
  'assets/home-quick/tag.svg': homeQuickTagIcon,
  'assets/home-top/search-line.svg': homeSearchLineIcon,
  'assets/tab/cart.png': tabCartIcon,
  'assets/tab/cart-active.png': tabCartActiveIcon,
  'assets/tab/category.png': tabCategoryIcon,
  'assets/tab/category-active.png': tabCategoryActiveIcon,
  'assets/tab/home.png': tabHomeIcon,
  'assets/tab/home-active.png': tabHomeActiveIcon,
  'assets/tab/user.png': tabUserIcon,
  'assets/tab/user-active.png': tabUserActiveIcon,
  'assets/usercenter/menu-address.svg': userMenuAddressIcon,
  'assets/usercenter/menu-coupon.svg': userMenuCouponIcon,
  'assets/usercenter/menu-help.svg': userMenuHelpIcon,
  'assets/usercenter/menu-settings.svg': userMenuSettingsIcon,
  'assets/usercenter/menu-wallet.svg': userMenuWalletIcon,
  'assets/usercenter/order-deliver.svg': userOrderDeliverIcon,
  'assets/usercenter/order-pay.svg': userOrderPayIcon,
  'assets/usercenter/order-receive.svg': userOrderReceiveIcon,
  'assets/usercenter/order-review.svg': userOrderReviewIcon,
  'assets/usercenter/order-service.svg': userOrderServiceIcon,
  'assets/usercenter/profile-qrcode.svg': userProfileQrcodeIcon,
};

export function resolveDiyAsset(value?: string): string {
  if (!value) return '';
  const normalized = value.replace(/^\/+/, '');
  if (diyAssetMap[normalized]) {
    return diyAssetMap[normalized];
  }

  return normalized.startsWith('assets/') ? `/${normalized}` : value;
}
