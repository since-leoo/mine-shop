import { View, Text, Image } from '@tarojs/components';
import orderPayIcon from '../../assets/usercenter/order-pay.svg';
import orderDeliverIcon from '../../assets/usercenter/order-deliver.svg';
import orderReceiveIcon from '../../assets/usercenter/order-receive.svg';
import orderReviewIcon from '../../assets/usercenter/order-review.svg';
import orderServiceIcon from '../../assets/usercenter/order-service.svg';
import menuAddressIcon from '../../assets/usercenter/menu-address.svg';
import menuCouponIcon from '../../assets/usercenter/menu-coupon.svg';
import menuWalletIcon from '../../assets/usercenter/menu-wallet.svg';
import menuHelpIcon from '../../assets/usercenter/menu-help.svg';
import menuSettingsIcon from '../../assets/usercenter/menu-settings.svg';
import profileQrcodeIcon from '../../assets/usercenter/profile-qrcode.svg';
import Taro, { useDidShow, usePullDownRefresh } from '@tarojs/taro';
import { useRef, useState } from 'react';
import { isLoggedIn } from '../../common/auth';
import { redirectToLogin } from '../../common/auth-guard';
import { isH5, isMiniProgram } from '../../common/platform';
import { fetchUserCenter } from '../../services/usercenter/fetchUsercenter';
import './index.scss';

interface UserInfo {
  avatarUrl: string;
  nickName: string;
  phoneNumber: string;
  inviteCode?: string;
  balance?: number;
}

interface OrderTagInfo {
  title: string;
  icon: string;
  orderNum: number;
  tabType: number;
}

interface CountsData {
  num: number;
  type: string;
}

interface MenuItem {
  title: string;
  icon: string;
  note: string;
  type: string;
}

interface WalletItem {
  num: string;
  label: string;
  type: string;
}

const defaultOrderTags: OrderTagInfo[] = [
  { title: '待付款', icon: '付', orderNum: 0, tabType: 5 },
  { title: '待发货', icon: '发', orderNum: 0, tabType: 10 },
  { title: '待收货', icon: '收', orderNum: 0, tabType: 40 },
  { title: '待评价', icon: '评', orderNum: 0, tabType: 60 },
  { title: '退款/售后', icon: '退', orderNum: 0, tabType: 0 },
];

const defaultMenuGroups: MenuItem[][] = [
  [
    { title: '收货地址', icon: '址', note: '', type: 'address' },
    { title: '优惠券', icon: '券', note: '', type: 'coupon' },
    { title: '我的钱包', icon: '包', note: '', type: 'wallet' },
  ],
  [
    { title: '联系客服', icon: '服', note: '', type: 'help' },
    { title: '资料设置', icon: '设', note: '', type: 'settings' },
  ],
];

function H5UserCenterView(props: {
  userInfo: UserInfo;
  walletItems: WalletItem[];
  orderTags: OrderTagInfo[];
  menuGroups: MenuItem[][];
  versionNo: string;
  onAvatarClick: () => void;
  onOrderTagClick: (tag: OrderTagInfo) => void;
  onMenuClick: (type: string) => void;
}) {
  const { userInfo, walletItems, orderTags, menuGroups, versionNo, onAvatarClick, onOrderTagClick, onMenuClick } = props;

  return (
    <View className="usercenter usercenter--h5">
      <View className="usercenter-h5__hero">
        <View className="usercenter-h5__hero-orb" />
        <View className="usercenter-h5__hero-card" onClick={onAvatarClick}>
          <View className="usercenter-h5__avatar-wrap">
            {userInfo.avatarUrl ? (
              <Image className="usercenter-h5__avatar" src={userInfo.avatarUrl} mode="aspectFill" />
            ) : (
              <Text className="usercenter-h5__avatar-text">我</Text>
            )}
          </View>
          <View className="usercenter-h5__info">
            <Text className="usercenter-h5__name">{userInfo.nickName}</Text>
            <Text className="usercenter-h5__sub">{userInfo.phoneNumber ? `手机号：${userInfo.phoneNumber}` : `邀请码：${userInfo.inviteCode || 'WARM2026'}`}</Text>
          </View>
          <Text className="usercenter-h5__arrow">›</Text>
        </View>
      </View>

      <View className="usercenter-h5__wallet">
        {walletItems.map((item) => (
          <View key={item.type} className="usercenter-h5__wallet-item">
            <Text className="usercenter-h5__wallet-num">{item.num}</Text>
            <Text className="usercenter-h5__wallet-label">{item.label}</Text>
          </View>
        ))}
      </View>

      <View className="usercenter-h5__section">
        <View className="usercenter-h5__section-head" onClick={() => Taro.navigateTo({ url: '/pages/order/order-list/index' })}>
          <Text className="usercenter-h5__section-title">我的订单</Text>
          <Text className="usercenter-h5__section-more">全部订单 ›</Text>
        </View>
        <View className="usercenter-h5__order-grid">
          {orderTags.map((tag) => (
            <View key={tag.tabType} className="usercenter-h5__order-item" onClick={() => onOrderTagClick(tag)}>
              <View className="usercenter-h5__order-icon-wrap">
                <Text className="usercenter-h5__order-icon">{tag.icon}</Text>
                {tag.orderNum > 0 ? <Text className="usercenter-h5__order-badge">{tag.orderNum > 99 ? '99+' : tag.orderNum}</Text> : null}
              </View>
              <Text className="usercenter-h5__order-text">{tag.title}</Text>
            </View>
          ))}
        </View>
      </View>

      {menuGroups.map((group, groupIndex) => (
        <View key={groupIndex} className="usercenter-h5__menu-card">
          {group.map((item) => (
            <View key={item.type} className="usercenter-h5__menu-item" onClick={() => onMenuClick(item.type)}>
              <Text className="usercenter-h5__menu-icon">{item.icon}</Text>
              <Text className="usercenter-h5__menu-text">{item.title}</Text>
              {item.note ? <Text className="usercenter-h5__menu-note">{item.note}</Text> : null}
              <Text className="usercenter-h5__menu-arrow">›</Text>
            </View>
          ))}
        </View>
      ))}

      <View className="usercenter-h5__tip">
        <Text className="usercenter-h5__tip-title">H5 资料入口说明</Text>
        <Text className="usercenter-h5__tip-desc">H5 端不展示微信授权入口，资料修改统一进入资料设置页处理。</Text>
      </View>

      <View className="usercenter__version">
        <Text className="usercenter__version-text">当前版本 {versionNo ? `v${versionNo}` : 'v1.0.0'}</Text>
      </View>
    </View>
  );
}

const orderIconMap: Record<number, string> = {
  5: orderPayIcon,
  10: orderDeliverIcon,
  40: orderReceiveIcon,
  60: orderReviewIcon,
  0: orderServiceIcon,
};

const menuIconMap: Record<string, string> = {
  address: menuAddressIcon,
  coupon: menuCouponIcon,
  wallet: menuWalletIcon,
  help: menuHelpIcon,
  settings: menuSettingsIcon,
};

function MiniProgramUserCenterView(props: {
  userInfo: UserInfo;
  walletItems: WalletItem[];
  orderTags: OrderTagInfo[];
  menuGroups: MenuItem[][];
  versionNo: string;
  onAvatarClick: () => void;
  onOrderTagClick: (tag: OrderTagInfo) => void;
  onMenuClick: (type: string) => void;
}) {
  const { userInfo, walletItems, orderTags, menuGroups, versionNo, onAvatarClick, onOrderTagClick, onMenuClick } = props;

  return (
    <View className="usercenter usercenter--default">
      <View className="usercenter__header">
        <View className="usercenter__header-bg">
          <View className="usercenter__header-circle" />
          <View className="usercenter__header-content" onClick={onAvatarClick}>
            <View className="usercenter__avatar-wrap">
              {userInfo.avatarUrl ? (
                <Image className="usercenter__avatar" src={userInfo.avatarUrl} mode="aspectFill" />
              ) : (
                <Text className="usercenter__avatar-emoji">我</Text>
              )}
            </View>
            <View className="usercenter__info">
              <Text className="usercenter__nickname">{userInfo.nickName}</Text>
              <Text className="usercenter__phone">
                {userInfo.phoneNumber ? `手机号：${userInfo.phoneNumber}` : `邀请码：${userInfo.inviteCode || 'WARM2026'}`}
              </Text>
            </View>
            <View className="usercenter__qrcode">
              <Image className="usercenter__qrcode-icon" src={profileQrcodeIcon} mode="aspectFit" />
            </View>
          </View>
        </View>
      </View>

      <View className="usercenter__wallet-card">
        {walletItems.map((item) => (
          <View key={item.type} className="usercenter__wallet-item">
            <Text className="usercenter__wallet-num">{item.num}</Text>
            <Text className="usercenter__wallet-label">{item.label}</Text>
          </View>
        ))}
      </View>

      <View className="usercenter__orders-card">
        <View className="usercenter__orders-header" onClick={() => Taro.navigateTo({ url: '/pages/order/order-list/index' })}>
          <Text className="usercenter__orders-title">我的订单</Text>
          <View className="usercenter__orders-all">
            <Text className="usercenter__orders-all-text">全部订单 ›</Text>
          </View>
        </View>
        <View className="usercenter__orders-tags">
          {orderTags.map((tag) => (
            <View key={tag.tabType} className="usercenter__order-tag" onClick={() => onOrderTagClick(tag)}>
              <View className="usercenter__order-tag-icon-wrap">
                <Image className="usercenter__order-tag-icon" src={orderIconMap[tag.tabType] || orderServiceIcon} mode="aspectFit" />
                {tag.orderNum > 0 ? (
                  tag.orderNum > 1 ? (
                    <View className="usercenter__order-tag-badge">
                      <Text className="usercenter__order-tag-badge-text">{tag.orderNum > 99 ? '99+' : tag.orderNum}</Text>
                    </View>
                  ) : (
                    <View className="usercenter__order-tag-dot" />
                  )
                ) : null}
              </View>
              <Text className="usercenter__order-tag-text">{tag.title}</Text>
            </View>
          ))}
        </View>
      </View>

      {menuGroups.map((group, groupIndex) => (
        <View key={groupIndex} className="usercenter__menu-card">
          {group.map((item) => (
            <View key={item.type} className="usercenter__menu-item" onClick={() => onMenuClick(item.type)}>
              <Image className="usercenter__menu-icon" src={menuIconMap[item.type] || menuSettingsIcon} mode="aspectFit" />
              <Text className="usercenter__menu-text">{item.title}</Text>
              {item.note ? <Text className="usercenter__menu-note">{item.note}</Text> : null}
              <Text className="usercenter__menu-arrow">›</Text>
            </View>
          ))}
        </View>
      ))}

      <View className="usercenter__version">
        <Text className="usercenter__version-text">当前版本 {versionNo ? `v${versionNo}` : 'v1.0.0'}</Text>
      </View>
    </View>
  );
}

export default function UserCenter() {
  const [userInfo, setUserInfo] = useState<UserInfo>({
    avatarUrl: '',
    nickName: '加载中...',
    phoneNumber: '',
  });
  const [orderTags, setOrderTags] = useState<OrderTagInfo[]>(defaultOrderTags);
  const [menuGroups, setMenuGroups] = useState<MenuItem[][]>(defaultMenuGroups);
  const [walletItems, setWalletItems] = useState<WalletItem[]>([
    { num: '0', label: '优惠券', type: 'coupon' },
    { num: '0', label: '积分', type: 'points' },
    { num: '¥0.00', label: '余额', type: 'balance' },
    { num: '0', label: '收藏', type: 'collect' },
  ]);
  const [versionNo, setVersionNo] = useState('');
  const profileNavigatingRef = useRef(false);

  const fetchData = () => {
    fetchUserCenter()
      .then((res: any) => {
        const info = res?.userInfo || {};
        const countsData: CountsData[] = Array.isArray(res?.countsData) ? res.countsData : [];
        const orderTagInfos = Array.isArray(res?.orderTagInfos) ? res.orderTagInfos : [];

        setUserInfo({
          avatarUrl: info?.avatarUrl || info?.avatar || '',
          nickName: info?.nickName || info?.nickname || '温馨用户',
          phoneNumber: info?.phoneNumber || info?.phone || '',
          inviteCode: info?.inviteCode || '',
          balance: Number(info?.balance || 0),
        });

        const balanceText = `¥${(Number(info?.balance || 0) / 100).toFixed(2)}`;
        const countMap = new Map(countsData.map((item) => [item.type, item.num]));
        setWalletItems([
          { num: String(countMap.get('coupon') ?? 0), label: '优惠券', type: 'coupon' },
          { num: String(countMap.get('point') ?? countMap.get('points') ?? 0), label: '积分', type: 'points' },
          { num: balanceText, label: '余额', type: 'balance' },
          { num: String(countMap.get('collect') ?? 0), label: '收藏', type: 'collect' },
        ]);

        setOrderTags(defaultOrderTags.map((tag, index) => ({
          ...tag,
          orderNum: Number(orderTagInfos[index]?.orderNum ?? 0),
        })));

        setMenuGroups(defaultMenuGroups.map((group) => group.map((item) => {
          if (item.type === 'coupon') {
            return { ...item, note: `${countMap.get('coupon') ?? 0} 张可用` };
          }
          return item;
        })));

        Taro.stopPullDownRefresh();
      })
      .catch(() => {
        Taro.stopPullDownRefresh();
      });
  };

  useDidShow(() => {
    if (isH5() && !isLoggedIn()) {
      redirectToLogin('/pages/usercenter/index');
      return;
    }

    fetchData();

    if (!isMiniProgram()) {
      setVersionNo('h5');
      return;
    }

    try {
      const accountInfo = Taro.getAccountInfoSync();
      const miniProgram = accountInfo?.miniProgram || ({} as any);
      const envVersion = miniProgram.envVersion || '';
      setVersionNo(envVersion === 'release' ? miniProgram.version || '' : envVersion || '');
    } catch (error) {
      console.warn('getAccountInfoSync failed', error);
    }
  });

  usePullDownRefresh(() => {
    fetchData();
  });

  const handleOrderTagClick = (tag: OrderTagInfo) => {
    if (tag.tabType === 0) {
      Taro.navigateTo({ url: '/pages/order/after-service-list/index' });
      return;
    }

    Taro.navigateTo({ url: `/pages/order/order-list/index?status=${tag.tabType}` });
  };

  const handleMenuClick = (type: string) => {
    switch (type) {
      case 'address':
        Taro.navigateTo({ url: '/pages/user/address/list/index' });
        break;
      case 'coupon':
        Taro.navigateTo({ url: '/pages/coupon/coupon-list/index' });
        break;
      case 'wallet':
        Taro.navigateTo({ url: '/pages/usercenter/wallet-transactions/index' });
        break;
      case 'help':
        Taro.showToast({ title: '请在后台配置客服入口', icon: 'none' });
        break;
      case 'settings':
        Taro.navigateTo({ url: '/pages/user/person-info/index' });
        break;
      default:
        break;
    }
  };

  const handleAvatarClick = () => {
    if (profileNavigatingRef.current) return;
    profileNavigatingRef.current = true;
    Taro.navigateTo({
      url: '/pages/user/person-info/index',
      complete: () => {
        setTimeout(() => {
          profileNavigatingRef.current = false;
        }, 300);
      },
    });
  };

  if (isH5()) {
    return (
      <H5UserCenterView
        userInfo={userInfo}
        walletItems={walletItems}
        orderTags={orderTags}
        menuGroups={menuGroups}
        versionNo={versionNo}
        onAvatarClick={handleAvatarClick}
        onOrderTagClick={handleOrderTagClick}
        onMenuClick={handleMenuClick}
      />
    );
  }

  return (
    <MiniProgramUserCenterView
      userInfo={userInfo}
      walletItems={walletItems}
      orderTags={orderTags}
      menuGroups={menuGroups}
      versionNo={versionNo}
      onAvatarClick={handleAvatarClick}
      onOrderTagClick={handleOrderTagClick}
      onMenuClick={handleMenuClick}
    />
  );
}
