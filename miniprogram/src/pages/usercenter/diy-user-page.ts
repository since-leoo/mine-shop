import { DiyPagePayload } from '../../components/diy-renderer/types';
import { getMiniProgramNavMetrics } from '../../utils/system-info';

export interface UserProfileDiyInfo {
  avatarUrl?: string;
  nickName?: string;
  inviteCode?: string;
}

export interface UserOrderTagDiyInfo {
  title?: string;
  tabType?: number;
  orderNum?: number;
}

function orderTabTypeOf(item: Record<string, any>): number | undefined {
  const tabType = item.link?.params?.tabType ?? item.link?.params?.status;
  const value = Number(tabType);
  return Number.isFinite(value) ? value : undefined;
}

function orderCountFor(item: Record<string, any>, index: number, orderTags: UserOrderTagDiyInfo[]): number {
  const tabType = orderTabTypeOf(item);
  const matched = tabType !== undefined
    ? orderTags.find((tag) => Number(tag.tabType) === tabType)
    : undefined;
  const fallback = matched ?? orderTags[index];
  const count = Number(fallback?.orderNum ?? 0);
  return Number.isFinite(count) && count > 0 ? count : 0;
}

export function userDiyPage(page: DiyPagePayload, userInfo: UserProfileDiyInfo, orderTags: UserOrderTagDiyInfo[] = []): DiyPagePayload {
  const shouldExtendTop = page.page?.style?.topTransparent === true;
  const navMetrics = shouldExtendTop ? getMiniProgramNavMetrics() : null;
  const topInset = navMetrics ? navMetrics.statusBarHeight + navMetrics.navHeight : 0;

  return {
    ...page,
    components: page.components.map((component) => {
      if (component.type === 'user-order-panel') {
        const items = Array.isArray(component.data?.items) ? component.data.items : [];
        return {
          ...component,
          data: {
            ...(component.data || {}),
            items: items.map((item, index) => ({
              ...item,
              orderNum: orderCountFor(item, index, orderTags),
            })),
          },
        };
      }

      if (component.type !== 'user-profile-header') return component;

      const style = { ...(component.style || {}) };
      if (topInset > 0) {
        const padding = style.padding && typeof style.padding === 'object' && !Array.isArray(style.padding)
          ? { ...style.padding }
          : { top: 0, right: 28, bottom: 34, left: 28 };
        padding.top = Math.max(Number(padding.top || 0), Math.round(topInset + 24));
        style.padding = padding;
      }

      return {
        ...component,
        style,
        props: {
          ...(component.props || {}),
          avatar: userInfo.avatarUrl || component.props?.avatar || '',
          nickname: userInfo.nickName || component.props?.nickname || '温馨用户',
          inviteCode: userInfo.inviteCode || component.props?.inviteCode || 'WARM2026',
          qrcodeIcon: component.props?.qrcodeIcon || 'assets/usercenter/profile-qrcode.svg',
        },
      };
    }),
  };
}
