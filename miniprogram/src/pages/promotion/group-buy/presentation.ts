export interface GroupBuyHeroChip {
  title: string;
  subtitle: string;
}

export interface GroupBuyHeroPresentation {
  showStandaloneNav: boolean;
  showTopbarControls: boolean;
  overlayControls: boolean;
  showCapsuleActions: boolean;
  eyebrow: string;
  headline: string;
  subline: string;
  chips: GroupBuyHeroChip[];
}

const HERO_CHIPS: GroupBuyHeroChip[] = [
  { title: '2人快拼', subtitle: '低门槛化' },
  { title: '3人爆团', subtitle: '价格更狠' },
  { title: '品牌福利团', subtitle: '限时返场' },
  { title: '同城极速团', subtitle: '当日发货' },
];

export function getGroupBuyHeroPresentation(h5: boolean): GroupBuyHeroPresentation {
  return {
    showStandaloneNav: h5,
    showTopbarControls: true,
    overlayControls: true,
    showCapsuleActions: h5,
    eyebrow: 'GROUP BUY MARKET',
    headline: '一起拼更省',
    subline: '边逛边参团，把“社交氛围感”做出来',
    chips: HERO_CHIPS,
  };
}
