import { View, Text, Image, Swiper, SwiperItem, ScrollView } from '@tarojs/components';
import Taro, { useDidShow, usePullDownRefresh } from '@tarojs/taro';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { isH5 } from '../../common/platform';
import { addCartItem } from '../../services/cart/cart';
import { fetchHome } from '../../services/home/home';
import searchIcon from '../../assets/home-top/search-line.svg';
import tagIcon from '../../assets/home-quick/tag.svg';
import giftIcon from '../../assets/home-quick/gift.svg';
import fireIcon from '../../assets/home-quick/fire.svg';
import gridIcon from '../../assets/home-quick/grid.svg';
import groupIcon from '../../assets/home-quick/group.svg';
import './index.scss';

interface BannerItem {
  image: string;
  title?: string;
  link?: string;
}

interface QuickEntry {
  key: string;
  name: string;
  icon: string;
  bg: string;
  url?: string;
}

interface ProductCard {
  id: string | number;
  thumb: string;
  title: string;
  price: number;
  originPrice?: number;
  tags?: string[];
  spuId?: string | number;
  skuId?: string | number;
}

interface CountdownParts {
  days: string;
  hours: string;
  minutes: string;
  seconds: string;
  overDay: boolean;
}

function toPrice(value: any): number {
  const amount = Number(value ?? 0);
  if (!Number.isFinite(amount)) return 0;
  return amount > 999 ? amount / 100 : amount;
}

function toBannerItem(item: any, index: number): BannerItem {
  if (typeof item === 'string') {
    return { image: item, title: `banner-${index}` };
  }

  return {
    image: item?.image || item?.img || item?.thumb || item?.cover || '',
    title: item?.title || item?.name || `banner-${index}`,
    link: item?.link || item?.url || '',
  };
}

function toProductCard(item: any, index: number, tag?: string): ProductCard {
  const spuId = item?.spuId || item?.spu_id || item?.id || item?.productId || index;
  const skuId = item?.skuId || item?.sku_id || item?.defaultSkuId || item?.id || spuId;

  return {
    id: `${spuId}-${skuId}-${index}`,
    thumb: item?.thumb || item?.primaryImage || item?.mainImage || item?.main_image || item?.image || item?.cover || '',
    title: item?.title || item?.goodsName || item?.name || '商品',
    price: toPrice(item?.price ?? item?.salePrice ?? item?.minSalePrice ?? item?.minPrice ?? item?.min_price),
    originPrice: toPrice(item?.originPrice ?? item?.linePrice ?? item?.maxLinePrice ?? item?.maxPrice ?? item?.max_price),
    tags: Array.isArray(item?.tags) && item.tags.length > 0 ? item.tags : tag ? [tag] : [],
    spuId,
    skuId,
  };
}

function toTargetTimestamp(value: unknown): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value > 1_000_000_000_000 ? value : value * 1000;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Date.parse(value.replace(/-/g, '/'));
    return Number.isNaN(parsed) ? 0 : parsed;
  }

  return 0;
}

function formatCountdown(targetTime: unknown, now = Date.now()): CountdownParts {
  const target = toTargetTimestamp(targetTime);
  const remainMs = Math.max(0, target - now);
  const totalSeconds = Math.floor(remainMs / 1000);
  const days = Math.floor(totalSeconds / 86400);
  const hours = Math.floor((totalSeconds % 86400) / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;

  return {
    days: String(days),
    hours: String(hours).padStart(2, '0'),
    minutes: String(minutes).padStart(2, '0'),
    seconds: String(seconds).padStart(2, '0'),
    overDay: days > 0,
  };
}

const quickEntries: QuickEntry[] = [
  { key: 'coupon', name: '限时特惠', icon: tagIcon, bg: '#FFF1E8', url: '/pages/coupon/coupon-center/index' },
  { key: 'group', name: '拼团活动', icon: groupIcon, bg: '#E8F5E9', url: '/pages/promotion/group-buy/index' },
  { key: 'gift', name: '优惠券', icon: giftIcon, bg: '#FFF3E0', url: '/pages/coupon/coupon-center/index' },
  { key: 'hot', name: '热卖榜单', icon: fireIcon, bg: '#FCE4EC', url: '/pages/goods/result/index' },
  { key: 'all', name: '全部分类', icon: gridIcon, bg: '#F3E5F5', url: '/pages/category/index' },
];

function HomeH5View(props: {
  banners: BannerItem[];
  bannerIndex: number;
  setBannerIndex: (value: number) => void;
  loading: boolean;
  recommendList: ProductCard[];
  seckillCountdown: CountdownParts;
  openSeckillTopic: () => void;
  openGroupBuyTopic: () => void;
  openLink: (url?: string) => void;
  openGoods: (goods: ProductCard) => void;
  handleAddCart: (goods: ProductCard) => void;
}) {
  const {
    banners,
    bannerIndex,
    setBannerIndex,
    loading,
    recommendList,
    seckillCountdown,
    openSeckillTopic,
    openGroupBuyTopic,
    openLink,
    openGoods,
    handleAddCart,
  } = props;

  return (
    <View className="home home--h5">
      <View className="home-top-bg">
        <View className="home-search" onClick={() => Taro.navigateTo({ url: '/pages/goods/search/index' })}>
          <Image className="home-search__icon-img" src={searchIcon} mode="aspectFit" />
          <Text className="home-search__placeholder">搜索你需要的商品</Text>
        </View>

        {banners.length > 0 ? (
          <View className="home-swiper">
            <Swiper
              className="home-swiper__inner"
              autoplay
              circular
              current={bannerIndex}
              onChange={(e) => setBannerIndex(e.detail.current)}
            >
              {banners.map((item, index) => (
                <SwiperItem key={`${item.title}-${index}`}>
                  <View className="home-swiper__slide" onClick={() => openLink(item.link)}>
                    <Image className="home-swiper__img" src={item.image} mode="aspectFill" />
                  </View>
                </SwiperItem>
              ))}
            </Swiper>
            <View className="home-swiper__dots">
              {(banners.length > 0 ? banners : [1, 2, 3]).slice(0, 3).map((_, index) => (
                <View key={index} className={`home-swiper__dot ${index === Math.min(bannerIndex, 2) ? 'home-swiper__dot--active' : ''}`} />
              ))}
            </View>
          </View>
        ) : (
          <View className="home-banner-fallback">
            <Text className="home-banner-fallback__title">春日上新</Text>
            <Text className="home-banner-fallback__desc">精选好物，温暖每一天</Text>
            <Text className="home-banner-fallback__btn">立即查看</Text>
          </View>
        )}
      </View>

      <View className="home-quick-entries">
        {quickEntries.map((item) => (
          <View key={item.key} className="home-quick-entries__item" onClick={() => openLink(item.url)}>
            <View className="home-quick-entries__icon-wrap" style={{ background: item.bg }}>
              <Image className="home-quick-entries__icon-img" src={item.icon} mode="aspectFit" />
            </View>
            <Text className="home-quick-entries__name">{item.name}</Text>
          </View>
        ))}
      </View>

      <View className="home-promo-hub">
        <View className="home-promo-hub__head">
          <View className="home-promo-hub__head-top">
            <View className="home-promo-hub__head-left">
              <View className="home-promo-hub__bar" />
              <Text className="home-promo-hub__title">今日活动直达</Text>
            </View>
            <Text className="home-promo-hub__head-note">{loading ? '加载中...' : '精选会场'}</Text>
          </View>
        </View>
        <View className="home-promo-hub__ads">
          <View className="home-promo-hub__ad-card home-promo-hub__ad-card--seckill" onClick={openSeckillTopic}>
            <Text className="home-promo-hub__ad-title">限时秒杀</Text>
            <Text className="home-promo-hub__ad-sub">爆款低价专场 点击直达秒杀会场</Text>
            {seckillCountdown.overDay ? (
              <Text className="home-promo-hub__ad-long home-promo-hub__ad-long--light">
                {seckillCountdown.days}天{seckillCountdown.hours}小时{seckillCountdown.minutes}分{seckillCountdown.seconds}秒
              </Text>
            ) : (
              <View className="home-promo-hub__ad-cd home-promo-hub__ad-cd--light">
                <View className="home-promo-hub__ad-cd-box"><Text className="home-promo-hub__ad-cd-num">{seckillCountdown.hours}</Text></View>
                <Text className="home-promo-hub__ad-cd-sep">:</Text>
                <View className="home-promo-hub__ad-cd-box"><Text className="home-promo-hub__ad-cd-num">{seckillCountdown.minutes}</Text></View>
                <Text className="home-promo-hub__ad-cd-sep">:</Text>
                <View className="home-promo-hub__ad-cd-box"><Text className="home-promo-hub__ad-cd-num">{seckillCountdown.seconds}</Text></View>
              </View>
            )}
          </View>
          <View className="home-promo-hub__ad-card home-promo-hub__ad-card--group" onClick={openGroupBuyTopic}>
            <Text className="home-promo-hub__ad-title">拼团活动</Text>
            <Text className="home-promo-hub__ad-sub">多人拼团更划算 热门好物持续开团</Text>
            <View className="home-promo-hub__ad-chip">
              <Text className="home-promo-hub__ad-chip-text">立即开团</Text>
            </View>
          </View>
        </View>
      </View>

      <View className="home-mid-banner">
        <View className="home-mid-banner__content">
          <View>
            <Text className="home-mid-banner__title">满 199 减 30</Text>
            <Text className="home-mid-banner__desc">全场跨店可用 · 24小时限时</Text>
          </View>
          <View className="home-mid-banner__btn">
            <Text className="home-mid-banner__btn-text">立即领券</Text>
          </View>
        </View>
      </View>

      {recommendList.length > 0 ? (
        <View className="home-recommend">
          <View className="home-recommend__header">
            <View className="home-recommend__title-bar" />
            <Text className="home-recommend__title">人气推荐</Text>
          </View>
          <View className="home-recommend__grid">
            {recommendList.map((item, index) => (
              <View key={item.id} className="home-h5-card" onClick={() => openGoods(item)}>
                <View className={`home-h5-card__media home-h5-card__media--${index % 2 === 0 ? 'warm' : 'mint'}`}>
                  {index === 0 ? <Text className="home-h5-card__badge">新品</Text> : null}
                  <Image className="home-h5-card__img" src={item.thumb} mode="aspectFill" />
                </View>
                <View className="home-h5-card__body">
                  <Text className="home-h5-card__title">{item.title}</Text>
                  {item.tags?.[0] ? <Text className="home-h5-card__tag">{item.tags[0]}</Text> : null}
                  <View className="home-h5-card__foot">
                    <View className="home-h5-card__price-row">
                      <Text className="home-h5-card__currency">¥</Text>
                      <Text className="home-h5-card__price">{item.price.toFixed(0)}</Text>
                      {item.originPrice ? <Text className="home-h5-card__origin">¥{item.originPrice.toFixed(0)}</Text> : null}
                    </View>
                    <View className="home-h5-card__cart" onClick={(e) => { e.stopPropagation(); handleAddCart(item); }}>
                      <Text className="home-h5-card__cart-text">+</Text>
                    </View>
                  </View>
                </View>
              </View>
            ))}
          </View>
        </View>
      ) : null}
    </View>
  );
}

function HomeDefaultView(props: {
  loading: boolean;
  banners: BannerItem[];
  seckillList: ProductCard[];
  groupBuyList: ProductCard[];
  recommendList: ProductCard[];
  seckillCountdown: CountdownParts;
  openSeckillTopic: () => void;
  openGroupBuyTopic: () => void;
  openLink: (url?: string) => void;
  openGoods: (goods: ProductCard) => void;
  handleAddCart: (goods: ProductCard) => void;
}) {
  const {
    loading,
    banners,
    seckillList,
    groupBuyList,
    recommendList,
    seckillCountdown,
    openSeckillTopic,
    openGroupBuyTopic,
    openLink,
    openGoods,
    handleAddCart,
  } = props;

  const hotList = groupBuyList.length > 0 ? groupBuyList : recommendList.slice(0, 6);

  return (
    <View className="home home--default">
      <View className="home-top-bg">
        <View className="home-search" onClick={() => Taro.navigateTo({ url: '/pages/goods/search/index' })}>
          <Image className="home-search__icon-img" src={searchIcon} mode="aspectFit" />
          <Text className="home-search__placeholder">搜索商品名称、品牌或关键词</Text>
        </View>

        {banners.length > 0 ? (
          <View className="home-swiper">
            <Swiper className="home-swiper__inner" autoplay circular indicatorDots indicatorColor="rgba(255,255,255,0.35)" indicatorActiveColor="#ffffff">
              {banners.map((item, index) => (
                <SwiperItem key={`${item.title}-${index}`}>
                  <Image className="home-swiper__img" src={item.image} mode="aspectFill" onClick={() => openLink(item.link)} />
                </SwiperItem>
              ))}
            </Swiper>
          </View>
        ) : (
          <View className="home-banner-fallback">
            <Text className="home-banner-fallback__title">春日上新</Text>
            <Text className="home-banner-fallback__desc">精选好物，温暖每一天</Text>
            <Text className="home-banner-fallback__btn">立即查看</Text>
          </View>
        )}
      </View>

      <View className="home-quick-entries">
        {quickEntries.map((item) => (
          <View key={item.key} className="home-quick-entries__item" onClick={() => openLink(item.url)}>
            <View className="home-quick-entries__icon-wrap" style={{ background: item.bg }}>
              <Image className="home-quick-entries__icon-img" src={item.icon} mode="aspectFit" />
            </View>
            <Text className="home-quick-entries__name">{item.name}</Text>
          </View>
        ))}
      </View>

      <View className="home-activity-info">
        <View className="home-activity-info__head">
          <View className="home-activity-info__head-left">
            <View className="home-activity-info__bar" />
            <Text className="home-activity-info__title">今日活动直达</Text>
          </View>
          <Text className="home-activity-info__note">{loading ? '加载中...' : '精选会场'}</Text>
        </View>
        <View className="home-activity-info__cards">
          <View className="home-activity-info__card home-activity-info__card--seckill" onClick={openSeckillTopic}>
            <Text className="home-activity-info__card-title">限时秒杀</Text>
            <Text className="home-activity-info__card-sub">爆款低价专场 点击直达秒杀会场</Text>
            {seckillCountdown.overDay ? (
              <Text className="home-activity-info__long">
                {seckillCountdown.days}天{seckillCountdown.hours}小时{seckillCountdown.minutes}分{seckillCountdown.seconds}秒
              </Text>
            ) : (
              <View className="home-activity-info__countdown">
                <View className="home-activity-info__countdown-box"><Text>{seckillCountdown.hours}</Text></View>
                <Text className="home-activity-info__countdown-sep">:</Text>
                <View className="home-activity-info__countdown-box"><Text>{seckillCountdown.minutes}</Text></View>
                <Text className="home-activity-info__countdown-sep">:</Text>
                <View className="home-activity-info__countdown-box"><Text>{seckillCountdown.seconds}</Text></View>
              </View>
            )}
          </View>
          <View className="home-activity-info__card home-activity-info__card--group" onClick={openGroupBuyTopic}>
            <Text className="home-activity-info__card-title">拼团活动</Text>
            <Text className="home-activity-info__card-sub">多人拼团更划算 热门好物持续开团</Text>
            <View className="home-activity-info__chip">
              <Text className="home-activity-info__chip-text">立即开团</Text>
            </View>
          </View>
        </View>
      </View>

      {seckillList.length > 0 ? (
        <View className="home-seckill">
          <View className="home-seckill__header">
            <View className="home-seckill__title-row">
              <View className="home-seckill__title-left">
                <View className="home-seckill__title-bar" />
                <Text className="home-seckill__title">限时秒杀</Text>
              </View>
              <View className="home-seckill__countdown">
                {seckillCountdown.overDay ? (
                  <Text className="home-seckill__countdown-text">
                    {seckillCountdown.days}天{seckillCountdown.hours}:{seckillCountdown.minutes}:{seckillCountdown.seconds}
                  </Text>
                ) : (
                  <>
                    <View className="home-seckill__countdown-block"><Text>{seckillCountdown.hours}</Text></View>
                    <Text className="home-seckill__countdown-sep">:</Text>
                    <View className="home-seckill__countdown-block"><Text>{seckillCountdown.minutes}</Text></View>
                    <Text className="home-seckill__countdown-sep">:</Text>
                    <View className="home-seckill__countdown-block"><Text>{seckillCountdown.seconds}</Text></View>
                  </>
                )}
              </View>
            </View>
            <Text className="home-group-buy__more" onClick={openSeckillTopic}>{loading ? '加载中...' : '查看更多'}</Text>
          </View>
          <ScrollView className="home-seckill__scroll" scrollX enhanced showScrollbar={false}>
            <View className="home-seckill__list">
              {seckillList.map((item) => (
                <View key={item.id} className="home-seckill__card" onClick={() => openGoods(item)}>
                  <Image className="home-seckill__card-img" src={item.thumb} mode="aspectFill" />
                  <Text className="home-seckill__card-title">{item.title}</Text>
                  <View className="home-seckill__card-price-row">
                    <Text className="home-seckill__card-price">¥{item.price.toFixed(2)}</Text>
                    {item.originPrice ? <Text className="home-seckill__card-origin-price">¥{item.originPrice.toFixed(2)}</Text> : null}
                  </View>
                </View>
              ))}
            </View>
          </ScrollView>
        </View>
      ) : null}

      {hotList.length > 0 ? (
        <View className="home-hot">
          <View className="home-hot__header">
            <View className="home-hot__title-left">
              <View className="home-hot__title-bar" />
              <Text className="home-hot__title">{groupBuyList.length > 0 ? '热门拼团' : '热门推荐'}</Text>
            </View>
            <Text className="home-hot__more" onClick={groupBuyList.length > 0 ? openGroupBuyTopic : () => Taro.navigateTo({ url: '/pages/goods/result/index' })}>
              查看更多
            </Text>
          </View>
          <ScrollView className="home-hot__scroll" scrollX enhanced showScrollbar={false}>
            <View className="home-hot__list">
              {hotList.map((item) => (
                <View key={item.id} className="home-hot__card" onClick={() => openGoods(item)}>
                  <Image className="home-hot__card-img" src={item.thumb} mode="aspectFill" />
                  <Text className="home-hot__card-title">{item.title}</Text>
                  <Text className="home-hot__card-price">¥{item.price.toFixed(2)}</Text>
                </View>
              ))}
            </View>
          </ScrollView>
        </View>
      ) : null}

      <View className="home-recommend">
        <View className="home-recommend__header">
          <View className="home-recommend__title-bar" />
          <Text className="home-recommend__title">人气推荐</Text>
        </View>
        <View className="home-recommend__grid">
          {recommendList.map((item, index) => (
            <View key={item.id} className="home-default-card" onClick={() => openGoods(item)}>
              <View className={`home-default-card__media home-default-card__media--${index % 2 === 0 ? 'warm' : 'mint'}`}>
                {index === 0 ? <Text className="home-default-card__badge">新品</Text> : null}
                <Image className="home-default-card__img" src={item.thumb} mode="aspectFill" />
              </View>
              <View className="home-default-card__body">
                <Text className="home-default-card__title">{item.title}</Text>
                {item.tags?.[0] ? <Text className="home-default-card__tag">{item.tags[0]}</Text> : null}
                <View className="home-default-card__foot">
                  <View className="home-default-card__price-row">
                    <Text className="home-default-card__currency">¥</Text>
                    <Text className="home-default-card__price">{item.price.toFixed(0)}</Text>
                    {item.originPrice ? <Text className="home-default-card__origin">¥{item.originPrice.toFixed(0)}</Text> : null}
                  </View>
                  <View className="home-default-card__cart" onClick={(e) => { e.stopPropagation(); handleAddCart(item); }}>
                    <Text className="home-default-card__cart-text">+</Text>
                  </View>
                </View>
              </View>
            </View>
          ))}
        </View>
      </View>
    </View>
  );
}

export default function Home() {
  const [loading, setLoading] = useState(false);
  const [bannerIndex, setBannerIndex] = useState(0);
  const [banners, setBanners] = useState<BannerItem[]>([]);
  const [seckillList, setSeckillList] = useState<ProductCard[]>([]);
  const [groupBuyList, setGroupBuyList] = useState<ProductCard[]>([]);
  const [recommendList, setRecommendList] = useState<ProductCard[]>([]);
  const [seckillEndTime, setSeckillEndTime] = useState<string | number | null>(null);
  const [seckillActivityId, setSeckillActivityId] = useState<string | number | null>(null);
  const [seckillSessionId, setSeckillSessionId] = useState<string | number | null>(null);
  const [nowMs, setNowMs] = useState(Date.now());

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data: any = await fetchHome();
      const bannerList = Array.isArray(data?.swiper) ? data.swiper.map(toBannerItem) : [];
      const seckill = Array.isArray(data?.seckillList) ? data.seckillList.map((item: any, index: number) => toProductCard(item, index, '秒杀')) : [];
      const groupBuy = Array.isArray(data?.groupBuyList) ? data.groupBuyList.map((item: any, index: number) => toProductCard(item, index, '拼团')) : [];
      const recommendSource = Array.isArray(data?.recommendList) && data.recommendList.length > 0
        ? data.recommendList
        : Array.isArray(data?.hotList) && data.hotList.length > 0
          ? data.hotList
          : [...seckill, ...groupBuy];

      setBanners(bannerList);
      setSeckillList(seckill);
      setGroupBuyList(groupBuy);
      setRecommendList(recommendSource.map((item: any, index: number) => toProductCard(item, index)));
      setSeckillEndTime(data?.seckillEndTime ?? null);
      setSeckillActivityId(data?.seckillActivityId ?? null);
      setSeckillSessionId(data?.seckillSessionId ?? null);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '首页加载失败', icon: 'none' });
    } finally {
      setLoading(false);
      Taro.stopPullDownRefresh();
    }
  }, []);

  useDidShow(() => {
    refresh();
  });

  usePullDownRefresh(() => {
    refresh();
  });

  useEffect(() => {
    const target = toTargetTimestamp(seckillEndTime);
    if (!target) {
      setNowMs(Date.now());
      return;
    }

    setNowMs(Date.now());
    const timer = setInterval(() => {
      setNowMs(Date.now());
    }, 1000);

    return () => clearInterval(timer);
  }, [seckillEndTime]);

  const openGoods = (goods: ProductCard) => {
    if (!goods.spuId) return;
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${goods.spuId}` });
  };

  const openLink = (url?: string) => {
    if (!url) return;
    Taro.navigateTo({ url });
  };

  const openSeckillTopic = useCallback(() => {
    const query = [
      seckillActivityId ? `activityId=${seckillActivityId}` : '',
      seckillSessionId ? `sessionId=${seckillSessionId}` : '',
    ].filter(Boolean).join('&');
    Taro.navigateTo({ url: `/pages/promotion/detail/index${query ? `?${query}` : ''}` });
  }, [seckillActivityId, seckillSessionId]);

  const openGroupBuyTopic = useCallback(() => {
    Taro.navigateTo({ url: '/pages/promotion/group-buy/index' });
  }, []);

  const handleAddCart = async (goods: ProductCard) => {
    if (!goods.skuId) {
      openGoods(goods);
      return;
    }

    try {
      await addCartItem({ skuId: goods.skuId, quantity: 1 });
      Taro.showToast({ title: '已加入购物车', icon: 'success' });
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '加入购物车失败', icon: 'none' });
    }
  };

  const h5RecommendList = useMemo(() => recommendList, [recommendList]);
  const seckillCountdown = useMemo(() => formatCountdown(seckillEndTime, nowMs), [seckillEndTime, nowMs]);

  if (isH5()) {
    return (
      <HomeH5View
        banners={banners}
        bannerIndex={bannerIndex}
        setBannerIndex={setBannerIndex}
        loading={loading}
        recommendList={h5RecommendList}
        seckillCountdown={seckillCountdown}
        openSeckillTopic={openSeckillTopic}
        openGroupBuyTopic={openGroupBuyTopic}
        openLink={openLink}
        openGoods={openGoods}
        handleAddCart={handleAddCart}
      />
    );
  }

  return (
    <HomeDefaultView
      loading={loading}
      banners={banners}
      seckillList={seckillList}
      groupBuyList={groupBuyList}
      recommendList={recommendList}
      seckillCountdown={seckillCountdown}
      openSeckillTopic={openSeckillTopic}
      openGroupBuyTopic={openGroupBuyTopic}
      openLink={openLink}
      openGoods={openGoods}
      handleAddCart={handleAddCart}
    />
  );
}
