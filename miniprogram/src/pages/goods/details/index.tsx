import { View, Text, Image, Swiper, SwiperItem, RootPortal } from '@tarojs/components';
import Taro, { useShareAppMessage } from '@tarojs/taro';
import { useState, useEffect, useCallback, useRef } from 'react';
import { fetchGood } from '../../../services/good/fetchGood';
import { addCartItem } from '../../../services/cart/cart';
import { fetchGroupBuyProductDetail, fetchOngoingGroups } from '../../../services/promotion/groupBuy';
import { trackEvent } from '../../../common/analytics';
import {
  getGoodsDetailsCommentList,
  getGoodsDetailsCommentsCount,
} from '../../../services/good/fetchGoodsDetailsComments';
import homeIcon from '../../../assets/detail-bottom/home-line.svg';
import cartIcon from '../../../assets/detail-bottom/cart-line.svg';
import PageNav from '../../../components/page-nav';
import { isH5 } from '../../../common/platform';
import './index.scss';

interface SkuItem {
  skuId: string;
  quantity: number;
  specInfo: any[];
  price: number;
  skuImage: string;
}

interface CommentsStatistics {
  badCount: number;
  commentCount: number;
  goodCount: number;
  goodRate: number;
  hasImageCount: number;
  middleCount: number;
}

interface GroupTeam {
  groupNo: string;
  leaderNickname: string;
  leaderAvatar: string;
  joinedCount: number;
  needCount: number;
  expireTime: string;
}

interface CountdownSegment {
  value: string;
  label: string;
}

function parseSeckillEndTime(input: any): number {
  if (input == null || input === '') return 0;
  const n = Number(input);
  if (Number.isFinite(n) && n > 0) {
    if (n >= 1e12) return n;
    if (n >= 1e9) return n * 1000;
    return Date.now() + (n >= 1e6 ? n : n * 1000);
  }
  const raw = String(input).trim();
  if (!raw) return 0;
  const candidates = [raw];
  if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(raw)) {
    candidates.push(raw.replace(' ', 'T'));
    candidates.push(raw.replace(/-/g, '/'));
  }
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    candidates.push(raw.replace(/-/g, '/'));
  }
  for (const text of candidates) {
    const ts = new Date(text).getTime();
    if (Number.isFinite(ts) && ts > 0) return ts;
  }
  return 0;
}

function parseCountdownSegments(input: string): CountdownSegment[] {
  if (!input) {
    return [
      { value: '00', label: '时' },
      { value: '00', label: '分' },
      { value: '00', label: '秒' },
    ];
  }

  const dayMatch = input.match(/(\d+)天(\d{2})小时(\d{2})分(\d{2})秒/);
  if (dayMatch) {
    return [
      { value: dayMatch[1], label: '天' },
      { value: dayMatch[2], label: '时' },
      { value: dayMatch[3], label: '分' },
      { value: dayMatch[4], label: '秒' },
    ];
  }

  const hmsMatch = input.match(/(\d{2}):(\d{2}):(\d{2})/);
  if (hmsMatch) {
    return [
      { value: hmsMatch[1], label: '时' },
      { value: hmsMatch[2], label: '分' },
      { value: hmsMatch[3], label: '秒' },
    ];
  }

  return [
    { value: '00', label: '时' },
    { value: '00', label: '分' },
    { value: '00', label: '秒' },
  ];
}

function formatPrice(price: number) {
  return (price / 100).toFixed(2);
}

export default function GoodsDetails() {
  const [details, setDetails] = useState<any>({});
  const [skuArray, setSkuArray] = useState<SkuItem[]>([]);
  const [primaryImage, setPrimaryImage] = useState('');
  const [specImg, setSpecImg] = useState('');
  const [minSalePrice, setMinSalePrice] = useState(0);
  const [maxLinePrice, setMaxLinePrice] = useState(0);
  const [soldNum, setSoldNum] = useState(0);
  const [swiperCurrent, setSwiperCurrent] = useState(0);
  const [showSpecPopup, setShowSpecPopup] = useState(false);
  const [specPopupVisible, setSpecPopupVisible] = useState(false);
  const [selectedSku, setSelectedSku] = useState<Record<string, string>>({});
  const [isAllSelectedSku, setIsAllSelectedSku] = useState(false);
  const [selectedAttrStr, setSelectedAttrStr] = useState('');
  const [selectSkuSellsPrice, setSelectSkuSellsPrice] = useState(0);
  const [buyNum, setBuyNum] = useState(1);
  const [buyType, setBuyType] = useState(0);
  const [commentsList, setCommentsList] = useState<any[]>([]);
  const [commentsStatistics, setCommentsStatistics] = useState<CommentsStatistics>({
    badCount: 0,
    commentCount: 0,
    goodCount: 0,
    goodRate: 0,
    hasImageCount: 0,
    middleCount: 0,
  });
  const [spuId, setSpuId] = useState('');
  const [orderType, setOrderType] = useState('');
  const [activityId, setActivityId] = useState('');
  const [sessionId, setSessionId] = useState('');
  const [groupBuyId, setGroupBuyId] = useState('');
  const [groupTeams, setGroupTeams] = useState<GroupTeam[]>([]);
  const [seckillCountdown, setSeckillCountdown] = useState('');

  const selectItemRef = useRef<SkuItem | null>(null);
  const popupTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const seckillTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const hasTrackViewRef = useRef(false);

  const getDetail = useCallback(async (id: string, mode: string, groupActivityId: string) => {
    try {
      const data = mode === 'group_buy' && groupActivityId
        ? await fetchGroupBuyProductDetail(groupActivityId, id)
        : await fetchGood(id);

      const skus: SkuItem[] = (data.skuList || []).map((item: any) => ({
        skuId: String(item.skuId || ''),
        quantity: item.stockInfo ? item.stockInfo.stockQuantity : 0,
        specInfo: item.specInfo || [],
        price: item.priceInfo?.[0]?.price || item.price || 0,
        skuImage: item.skuImage || '',
      }));

      setDetails(data);
      setSkuArray(skus);
      setPrimaryImage(data.primaryImage || '');
      setSoldNum(data.soldNum || 0);
      setMinSalePrice(data.minSalePrice ? parseInt(data.minSalePrice, 10) : 0);
      setMaxLinePrice(data.maxLinePrice ? parseInt(data.maxLinePrice, 10) : 0);

      if (mode === 'group_buy') {
        const resolvedActivityId = String(groupActivityId || data.activityInfo?.activityId || data.activityId || data.groupBuyId || '');
        if (resolvedActivityId && resolvedActivityId !== groupBuyId) {
          setGroupBuyId(resolvedActivityId);
        }
      }
    } catch (err) {
      console.error('getDetail error:', err);
      Taro.showToast({ title: '商品加载失败', icon: 'none' });
    }
  }, [groupBuyId]);
  const getCommentsList = useCallback(async (id: string) => {
    try {
      const data = await getGoodsDetailsCommentList(id);
      const list = (data.homePageComments || []).map((item: any) => ({
        goodsSpu: item.spuId,
        userName: item.userName || '',
        commentScore: item.commentScore,
        commentContent: item.commentContent || '用户未填写评价',
        userHeadUrl: item.userHeadUrl || '',
        specInfo: item.specInfo || '',
        sellerReply: item.sellerReply || '',
      }));
      setCommentsList(list);
    } catch (error) {
      console.error('comments error:', error);
      setCommentsList([]);
    }
  }, []);

  const getCommentsStatistics = useCallback(async (id: string) => {
    try {
      const data = await getGoodsDetailsCommentsCount(id);
      setCommentsStatistics({
        badCount: parseInt(`${data.badCount}`, 10),
        commentCount: parseInt(`${data.commentCount}`, 10),
        goodCount: parseInt(`${data.goodCount}`, 10),
        goodRate: Math.floor(data.goodRate * 10) / 10,
        hasImageCount: parseInt(`${data.hasImageCount}`, 10),
        middleCount: parseInt(`${data.middleCount}`, 10),
      });
    } catch (error) {
      console.error('comments statistics error:', error);
      setCommentsStatistics({
        badCount: 0,
        commentCount: 0,
        goodCount: 0,
        goodRate: 0,
        hasImageCount: 0,
        middleCount: 0,
      });
    }
  }, []);

  useEffect(() => {
    const instance = Taro.getCurrentInstance();
    const id = instance.router?.params?.spuId || '';
    const ot = instance.router?.params?.orderType || '';
    const actId = instance.router?.params?.activityId || '';
    const sId = instance.router?.params?.sessionId || '';
    const gbId = instance.router?.params?.groupBuyId || '';
    setSpuId(id);
    setOrderType(ot);
    setActivityId(actId);
    setSessionId(sId);
    setGroupBuyId(gbId);
    if (id) {
      getDetail(id, ot, gbId || actId);
      getCommentsList(id);
      getCommentsStatistics(id);
    }
  }, [getDetail, getCommentsList, getCommentsStatistics]);

  useEffect(() => {
    if (!spuId || hasTrackViewRef.current) return;
    hasTrackViewRef.current = true;
    trackEvent('goods_detail_view', {
      spuId,
      orderType: orderType || 'normal',
      activityId: activityId || groupBuyId || '',
      sessionId: sessionId || '',
    });
  }, [spuId, orderType, activityId, groupBuyId, sessionId]);

  useEffect(() => () => {
    if (popupTimerRef.current) clearTimeout(popupTimerRef.current);
    if (seckillTimerRef.current) clearInterval(seckillTimerRef.current);
  }, []);

  useEffect(() => {
    const currentActivityId = groupBuyId || activityId;
    if (orderType !== 'group_buy' || !currentActivityId) {
      setGroupTeams([]);
      return;
    }
    fetchOngoingGroups(currentActivityId, 6)
      .then((res: any) => setGroupTeams(Array.isArray(res) ? res : []))
      .catch(() => setGroupTeams([]));
  }, [orderType, groupBuyId, activityId]);

  useEffect(() => {
    if (orderType !== 'seckill') {
      setSeckillCountdown('');
      return;
    }
    const endTs = parseSeckillEndTime(
      details.seckillEndTime || details.endTime || details.activityEndTime || details.promotionEndTime,
    );

    const update = () => {
      const now = Date.now();
      let diff = endTs - now;
      if (!endTs) {
        const end = new Date();
        end.setHours(23, 59, 59, 0);
        diff = end.getTime() - now;
      }
      if (diff <= 0) {
        setSeckillCountdown('00:00:00');
        return;
      }
      const total = Math.floor(diff / 1000);
      const d = Math.floor(total / 86400);
      const h = String(Math.floor((total % 86400) / 3600)).padStart(2, '0');
      const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
      const s = String(total % 60).padStart(2, '0');
      setSeckillCountdown(d > 0 ? `${d}天${h}小时${m}分${s}秒` : `${h}:${m}:${s}`);
    };

    update();
    if (seckillTimerRef.current) clearInterval(seckillTimerRef.current);
    seckillTimerRef.current = setInterval(update, 1000);
    return () => {
      if (seckillTimerRef.current) clearInterval(seckillTimerRef.current);
    };
  }, [orderType, details]);

  const getSelectedSkuValues = useCallback((specList: any[], selected: Record<string, string>) => {
    const normalizedTree: Record<string, any[]> = {};
    (specList || []).forEach((treeItem: any) => {
      normalizedTree[String(treeItem.specId)] = treeItem.specValueList;
    });
    return Object.keys(selected).reduce((acc: any[], skuKeyStr) => {
      const skuValues = normalizedTree[skuKeyStr];
      const skuValueId = selected[skuKeyStr];
      if (skuValueId && skuValues) {
        const skuValue = skuValues.find((v: any) => String(v.specValueId) === String(skuValueId));
        if (skuValue) acc.push(skuValue);
      }
      return acc;
    }, []);
  }, []);

  const handleSpecSelect = useCallback((specId: string, specValueId: string) => {
    const specKey = String(specId);
    const valueKey = String(specValueId);
    setSelectedSku((prev) => {
      const next = { ...prev };
      if (next[specKey] === valueKey) delete next[specKey];
      else next[specKey] = valueKey;

      const currentSpecList = details.specList || [];
      const allSelected = currentSpecList.length > 0 && currentSpecList.every((spec: any) => !!next[String(spec.specId)]);
      setIsAllSelectedSku(allSelected);

      const selectedValues = getSelectedSkuValues(currentSpecList, next);
      setSelectedAttrStr(selectedValues.map((v: any) => v.specValue).join(', '));

      const matchedSku = skuArray.find((sku) => (sku.specInfo || []).every((si: any) => next[String(si.specId)] && String(next[String(si.specId)]) === String(si.specValueId)));
      if (matchedSku) {
        selectItemRef.current = matchedSku;
        setSelectSkuSellsPrice(matchedSku.price || 0);
        setSpecImg(matchedSku.skuImage || primaryImage);
      } else {
        selectItemRef.current = null;
        setSelectSkuSellsPrice(0);
        setSpecImg(primaryImage);
      }
      return next;
    });
  }, [details, skuArray, primaryImage, getSelectedSkuValues]);
  const closeSpecPopup = useCallback(() => {
    setSpecPopupVisible(false);
    if (popupTimerRef.current) clearTimeout(popupTimerRef.current);
    popupTimerRef.current = setTimeout(() => setShowSpecPopup(false), 220);
  }, []);

  const showSkuSelectPopup = useCallback((type: number) => {
    setBuyType(type);
    trackEvent('goods_sku_popup_open', {
      spuId,
      orderType: orderType || 'normal',
      actionType: type === 2 ? 'add_cart' : type === 1 ? 'buy_now' : 'select_spec',
    });
    if (!showSpecPopup) {
      setShowSpecPopup(true);
      if (popupTimerRef.current) clearTimeout(popupTimerRef.current);
      popupTimerRef.current = setTimeout(() => setSpecPopupVisible(true), 20);
      return;
    }
    setSpecPopupVisible(true);
  }, [showSpecPopup, spuId, orderType]);

  const handleBuyNumChange = useCallback((delta: number) => {
    setBuyNum((prev) => Math.max(1, Math.min(99, prev + delta)));
  }, []);

  const handleAddCart = useCallback(async () => {
    if (!isAllSelectedSku) {
      Taro.showToast({ title: '请选择规格', icon: 'none' });
      return;
    }
    const skuId = selectItemRef.current?.skuId;
    if (!skuId) {
      Taro.showToast({ title: '请选择规格', icon: 'none' });
      return;
    }
    try {
      await addCartItem({ skuId: Number(skuId), quantity: buyNum });
      trackEvent('goods_add_cart_success', { spuId, skuId, quantity: buyNum, orderType: orderType || 'normal' });
      Taro.showToast({ title: '已加入购物车', icon: 'success' });
      closeSpecPopup();
    } catch (err: any) {
      trackEvent('goods_add_cart_fail', { spuId, skuId, quantity: buyNum, orderType: orderType || 'normal' });
      Taro.showToast({ title: err.msg || '加入购物车失败', icon: 'none' });
    }
  }, [isAllSelectedSku, buyNum, closeSpecPopup, spuId, orderType]);

  const handleBuyNow = useCallback(() => {
    if (!isAllSelectedSku) {
      Taro.showToast({ title: '请选择规格', icon: 'none' });
      return;
    }
    closeSpecPopup();

    const skuId = selectItemRef.current?.skuId || (details.skuList && details.skuList[0]?.skuId);
    const query = {
      quantity: buyNum,
      storeId: '1',
      spuId: details.spuId,
      goodsName: details.title,
      skuId,
      available: details.available,
      price: selectSkuSellsPrice || details.minSalePrice,
      specInfo: selectItemRef.current?.specInfo || [],
      primaryImage: details.primaryImage,
      thumb: details.primaryImage,
      title: details.title,
      orderType: orderType || 'normal',
      activityId: activityId || undefined,
      sessionId: sessionId || undefined,
      groupBuyId: orderType === 'group_buy' ? groupBuyId || undefined : undefined,
    };

    const urlQueryStr = `goodsRequestList=${encodeURIComponent(JSON.stringify([query]))}`;
    trackEvent('goods_buy_now_click', {
      spuId,
      skuId: skuId || '',
      quantity: buyNum,
      orderType: orderType || 'normal',
      activityId: activityId || groupBuyId || '',
      sessionId: sessionId || '',
    });
    Taro.navigateTo({ url: `/pages/order/order-confirm/index?${urlQueryStr}` });
  }, [isAllSelectedSku, buyNum, details, selectSkuSellsPrice, closeSpecPopup, orderType, activityId, sessionId, groupBuyId]);

  const handleSpecConfirm = useCallback(() => {
    if (buyType === 1) handleBuyNow();
    else handleAddCart();
  }, [buyType, handleBuyNow, handleAddCart]);

  const navToComments = useCallback(() => {
    trackEvent('goods_comment_entry_click', { spuId, orderType: orderType || 'normal' });
    Taro.navigateTo({ url: `/pages/goods/comments/index?spuId=${spuId}` });
  }, [spuId, orderType]);

  const handlePreviewImage = useCallback((index: number) => {
    trackEvent('goods_image_preview', { spuId, index, orderType: orderType || 'normal' });
    const currentImages = details.images || [];
    if (currentImages.length > 0) {
      Taro.previewImage({ current: currentImages[index], urls: currentImages });
    }
  }, [details.images, spuId, orderType]);

  const handleShareClick = useCallback(() => {
    trackEvent('goods_share_click', { spuId, orderType: orderType || 'normal', activityId: activityId || groupBuyId || '' });
    Taro.showShareMenu({ withShareTicket: true })
      .catch(() => {})
      .finally(() => {
        Taro.showToast({ title: '请点击右上角进行分享', icon: 'none' });
      });
  }, [spuId, orderType, activityId, groupBuyId]);

  const handleGroupJoinClick = useCallback((team: GroupTeam) => {
    trackEvent('group_team_join_click', { spuId, groupNo: team.groupNo || '', joinedCount: team.joinedCount || 0, needCount: team.needCount || 0 });
    showSkuSelectPopup(1);
  }, [spuId, showSkuSelectPopup]);

  const handleGroupBottomClick = useCallback((entry: 'origin' | 'group') => {
    trackEvent('group_bottom_click', { spuId, entry, orderType: orderType || 'group_buy', activityId: activityId || groupBuyId || '' });
    showSkuSelectPopup(1);
  }, [spuId, orderType, activityId, groupBuyId, showSkuSelectPopup]);

  const formatGroupRemain = useCallback((expireTime: string) => {
    const end = new Date(expireTime).getTime();
    if (!end) return '剩余 2 小时';
    const diff = end - Date.now();
    if (diff <= 0) return '即将结束';
    const totalMins = Math.floor(diff / 60000);
    if (totalMins < 60) return `剩余 ${totalMins} 分钟`;
    const hours = Math.floor(totalMins / 60);
    return `剩余 ${hours} 小时`;
  }, []);

  const renderStars = (score: number) => Array.from({ length: 5 }, (_, i) => (
    <Text key={i} className={`star ${i < score ? 'star--active' : ''}`}>
      {i < score ? '★' : '☆'}
    </Text>
  ));

  const images = details.images || [];
  const specList = details.specList || [];
  const descImages = details.desc || [];
  const isGroupBuyMode = orderType === 'group_buy';
  const isSeckillMode = orderType === 'seckill';
  const mainTitle = details.title || details.goodsName || details.name || '';
  const subTitle = details.subTitle || details.subtitle || details.intro || details.sellingPoint || details.brief || '';
  const groupPriceCent = selectSkuSellsPrice || minSalePrice || 0;
  const originPriceCent = maxLinePrice || minSalePrice || 0;
  const seckillCountdownSegments = parseCountdownSegments(seckillCountdown || '00:00:00');
  const seckillLimitCount = Number(details.limitQuantity || details.buyLimit || details.seckillLimit || details.limitBuyNum || 2) || 2;

  useShareAppMessage(() => ({
    title: mainTitle || '商品详情',
    path: `/pages/goods/details/index?spuId=${spuId}`,
    imageUrl: primaryImage || images[0] || '',
  }));
  return (
    <View className={`goods-detail-page ${isH5() ? 'goods-detail-page--h5' : ''} ${isGroupBuyMode ? 'goods-detail-page--group' : ''} ${isSeckillMode ? 'goods-detail-page--seckill' : ''}`}>
      {!isH5() ? <PageNav title="商品详情" /> : null}
      {images.length > 0 && (
        <View className={`goods-swiper ${isGroupBuyMode ? 'goods-swiper--group' : ''}`}>
          <Swiper className="goods-swiper__inner" indicatorDots={!isGroupBuyMode} autoplay circular indicatorActiveColor="#E8836B" current={swiperCurrent} onChange={(e) => setSwiperCurrent(e.detail.current)}>
            {images.map((img: string, idx: number) => (
              <SwiperItem key={idx} onClick={() => handlePreviewImage(idx)}>
                <Image className="goods-swiper__img" src={img} mode="aspectFill" />
              </SwiperItem>
            ))}
          </Swiper>
        </View>
      )}

      <View className={`goods-info ${isGroupBuyMode ? 'goods-info--group' : ''} ${isSeckillMode ? 'goods-info--seckill' : ''}`}>
        {isSeckillMode ? (
          <>
            <View className="goods-info__market-row">
              <View className="goods-info__market-left">
                <Text className="goods-info__market-price">¥{formatPrice(originPriceCent)}</Text>
                <Text className="goods-info__market-label">市场价参考</Text>
              </View>
              <Text className="goods-info__sold">已售 {soldNum}</Text>
            </View>
            <View className="goods-info__title-row goods-info__title-row--seckill">
              <Text className="goods-info__title">{mainTitle}</Text>
            </View>
            {subTitle && <Text className="goods-info__intro">{subTitle}</Text>}
          </>
        ) : (
          <>
            <View className="goods-info__price-row">
              <View className="goods-info__price-left">
                <Text className="goods-info__currency">¥</Text>
                <Text className="goods-info__price">{formatPrice(selectSkuSellsPrice || minSalePrice)}</Text>
                {!isGroupBuyMode && <Text className="goods-info__price-up">起</Text>}
                {maxLinePrice > 0 && <Text className="goods-info__origin-price">¥{formatPrice(maxLinePrice)}</Text>}
              </View>
              <Text className="goods-info__sold">已售{soldNum}</Text>
            </View>
            {!isGroupBuyMode && (
              <View className="goods-info__tags-row">
                <Text className="goods-info__tag">满100减20</Text>
                <Text className="goods-info__tag goods-info__tag--accent">新人专享</Text>
                <Text className="goods-info__tag">领券中</Text>
              </View>
            )}
            <View className="goods-info__title-row">
              <Text className="goods-info__title">{mainTitle}</Text>
              {!isGroupBuyMode && (
                <View className="goods-info__share" onClick={handleShareClick}>
                  <Text className="goods-info__share-icon">↗</Text>
                  <Text>分享</Text>
                </View>
              )}
            </View>
            {subTitle && <Text className="goods-info__intro">{subTitle}</Text>}
          </>
        )}
      </View>

      {isSeckillMode && (
        <View className="goods-mode-card goods-mode-card--seckill">
          <View className="goods-mode-card__head">
            <View className="goods-mode-card__price-wrap">
              <Text className="goods-mode-card__tag">秒杀价</Text>
              <Text className="goods-mode-card__price"><Text className="goods-mode-card__price-currency">¥</Text>{formatPrice(groupPriceCent)}</Text>
              {originPriceCent > 0 && <Text className="goods-mode-card__origin">¥{formatPrice(originPriceCent)}</Text>}
            </View>
            <Text className="goods-mode-card__team">限时抢购</Text>
          </View>
          <View className="goods-mode-card__seckill-cd-row">
            <View className="goods-mode-card__seckill-countdown">
              <Text className="goods-mode-card__seckill-cd-label">距结束</Text>
              <View className="goods-mode-card__seckill-boxes">
                {seckillCountdownSegments.map((segment, index) => (
                  <View key={`${segment.label}-${index}`} className="goods-mode-card__seckill-box-group">
                    <Text className="goods-mode-card__seckill-box">{segment.value}</Text>
                    {index < seckillCountdownSegments.length - 1 && <Text className="goods-mode-card__seckill-sep">:</Text>}
                  </View>
                ))}
              </View>
            </View>
            <Text className="goods-mode-card__seckill-limit">每人限购 {seckillLimitCount} 件</Text>
          </View>
        </View>
      )}

      {isGroupBuyMode && (
        <>
          <View className="goods-mode-card goods-mode-card--group">
            <View className="goods-mode-card__head">
              <View className="goods-mode-card__price-wrap">
                <Text className="goods-mode-card__tag goods-mode-card__tag--group">拼团价</Text>
                <Text className="goods-mode-card__price goods-mode-card__price--group">¥{formatPrice(groupPriceCent)}</Text>
                {originPriceCent > 0 && <Text className="goods-mode-card__origin">¥{formatPrice(originPriceCent)}</Text>}
              </View>
              <Text className="goods-mode-card__team goods-mode-card__team--group">3人团</Text>
            </View>
            <View className="goods-mode-card__steps">
              <Text className="goods-mode-card__step">开团参团</Text>
              <Text className="goods-mode-card__step-arrow">→</Text>
              <Text className="goods-mode-card__step">邀请好友</Text>
              <Text className="goods-mode-card__step-arrow">→</Text>
              <Text className="goods-mode-card__step">满员成功</Text>
              <Text className="goods-mode-card__step-arrow">→</Text>
              <Text className="goods-mode-card__step">发货收货</Text>
            </View>
          </View>

          <View className="group-teams">
            <View className="group-teams__title-row">
              <View className="group-teams__title-bar" />
              <Text className="group-teams__title">正在拼团，可直接参团</Text>
            </View>
            {groupTeams.map((team, idx) => (
              <View key={team.groupNo || idx} className="group-teams__item">
                <View className="group-teams__avatar-wrap">
                  {team.leaderAvatar ? <Image className="group-teams__avatar" src={team.leaderAvatar} mode="aspectFill" /> : <Text className="group-teams__avatar-placeholder">拼</Text>}
                </View>
                <Text className="group-teams__name">{team.leaderNickname || '拼团用户'}</Text>
                <View className="group-teams__meta">
                  <Text className="group-teams__need">还差 {Math.max(0, (team.needCount || 3) - (team.joinedCount || 1))} 人</Text>
                  <Text className="group-teams__time">{formatGroupRemain(team.expireTime)}</Text>
                </View>
                <View className="group-teams__join-btn" onClick={() => handleGroupJoinClick(team)}>
                  <Text className="group-teams__join-text">去拼团</Text>
                </View>
              </View>
            ))}
            {groupTeams.length === 0 && (
              <View className="group-teams__empty">
                <Text className="group-teams__empty-text">暂无正在进行的团，快来开团吧</Text>
              </View>
            )}
          </View>
        </>
      )}
      {!isGroupBuyMode && (
        <View className={`goods-spec-select ${isSeckillMode ? 'goods-spec-select--seckill' : ''}`} onClick={() => showSkuSelectPopup(0)}>
          <Text className="goods-spec-select__label">已选</Text>
          <View className="goods-spec-select__content">
            <Text className={`goods-spec-select__value ${!selectedAttrStr ? 'goods-spec-select__value--hint' : ''}`}>
              {selectedAttrStr ? `${selectedAttrStr} × ${buyNum}` : '请选择规格'}
            </Text>
            <Text className="goods-spec-select__arrow">›</Text>
          </View>
        </View>
      )}

      {isSeckillMode && (
        <View className="goods-seckill-rules">
          <View className="goods-seckill-rules__title-row">
            <View className="goods-seckill-rules__bar" />
            <Text className="goods-seckill-rules__title">秒杀说明</Text>
          </View>
          <Text className="goods-seckill-rules__text">1. 秒杀商品库存有限，先到先得。</Text>
          <Text className="goods-seckill-rules__text">2. 活动结束后恢复原价，不支持补差价。</Text>
          <Text className="goods-seckill-rules__text">3. 下单后请在 30 分钟内完成支付。</Text>
        </View>
      )}

      {!isGroupBuyMode && !isSeckillMode && commentsStatistics.commentCount > 0 && (
        <View className="goods-comments">
          <View className="goods-comments__header" onClick={navToComments}>
            <View className="goods-comments__title-wrap">
              <Text className="goods-comments__title">商品评价</Text>
              <Text className="goods-comments__count">({commentsStatistics.commentCount})</Text>
            </View>
            <View className="goods-comments__rate-wrap">
              <Text className="goods-comments__good-rate">{commentsStatistics.goodRate}% 好评</Text>
              <Text className="goods-comments__arrow">›</Text>
            </View>
          </View>
          {commentsList.map((item, idx) => (
            <View key={idx} className="goods-comments__item">
              <View className="goods-comments__item-header">
                <Image className="goods-comments__avatar" src={item.userHeadUrl} />
                <View className="goods-comments__item-right">
                  <Text className="goods-comments__username">{item.userName}</Text>
                  <View className="goods-comments__stars">{renderStars(item.commentScore)}</View>
                </View>
              </View>
              {!!item.specInfo && <Text className="goods-comments__spec">规格：{item.specInfo}</Text>}
              <Text className="goods-comments__content">{item.commentContent}</Text>
              {!!item.sellerReply && (
                <View className="goods-comments__reply">
                  <Text className="goods-comments__reply-label">商家回复：</Text>
                  <Text className="goods-comments__reply-text">{item.sellerReply}</Text>
                </View>
              )}
            </View>
          ))}
        </View>
      )}

      {!isGroupBuyMode && descImages.length > 0 && (
        <View className="goods-desc">
          <View className="goods-desc__title-row">
            <View className="goods-desc__line" />
            <Text className="goods-desc__title-text">详情介绍</Text>
            <View className="goods-desc__line" />
          </View>
          {descImages.map((img: string, idx: number) => (
            <Image key={idx} className="goods-desc__img" src={img} mode="widthFix" />
          ))}
        </View>
      )}

      {isGroupBuyMode && (
        <View className="goods-group-placeholder">
          <Text className="goods-group-placeholder__text">商品评价 / 详情介绍</Text>
        </View>
      )}

      <View className="goods-bottom-placeholder" />

      {!isGroupBuyMode && (isH5() ? (
        <RootPortal>
          <View className={`goods-bottom-bar ${showSpecPopup ? 'goods-bottom-bar--hidden' : ''}`}>
            <View className="goods-bottom-bar__icons">
              <View className="goods-bottom-bar__icon-item" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>
                <Image className="goods-bottom-bar__icon-emoji" src={homeIcon} mode="aspectFit" />
                <Text className="goods-bottom-bar__icon-text">首页</Text>
              </View>
              <View className="goods-bottom-bar__icon-item" onClick={() => Taro.switchTab({ url: '/pages/cart/index' })}>
                <Image className="goods-bottom-bar__icon-emoji" src={cartIcon} mode="aspectFit" />
                <Text className="goods-bottom-bar__icon-text">购物车</Text>
              </View>
            </View>
            <View className="goods-bottom-bar__btns">
              {!isSeckillMode && (
                <View className="goods-bottom-bar__btn goods-bottom-bar__btn--cart" onClick={() => showSkuSelectPopup(2)}>
                  <Text className="goods-bottom-bar__btn-text">加入购物车</Text>
                </View>
              )}
              <View className="goods-bottom-bar__btn goods-bottom-bar__btn--buy" onClick={() => showSkuSelectPopup(1)}>
                <Text className="goods-bottom-bar__btn-text">{isSeckillMode ? '立即抢购' : '立即购买'}</Text>
              </View>
            </View>
          </View>
        </RootPortal>
      ) : (
        <View className={`goods-bottom-bar ${showSpecPopup ? 'goods-bottom-bar--hidden' : ''}`}>
          <View className="goods-bottom-bar__icons">
            <View className="goods-bottom-bar__icon-item" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>
              <Image className="goods-bottom-bar__icon-emoji" src={homeIcon} mode="aspectFit" />
              <Text className="goods-bottom-bar__icon-text">首页</Text>
            </View>
            <View className="goods-bottom-bar__icon-item" onClick={() => Taro.switchTab({ url: '/pages/cart/index' })}>
              <Image className="goods-bottom-bar__icon-emoji" src={cartIcon} mode="aspectFit" />
              <Text className="goods-bottom-bar__icon-text">购物车</Text>
            </View>
          </View>
          <View className="goods-bottom-bar__btns">
            {!isSeckillMode && (
              <View className="goods-bottom-bar__btn goods-bottom-bar__btn--cart" onClick={() => showSkuSelectPopup(2)}>
                <Text className="goods-bottom-bar__btn-text">加入购物车</Text>
              </View>
            )}
            <View className="goods-bottom-bar__btn goods-bottom-bar__btn--buy" onClick={() => showSkuSelectPopup(1)}>
              <Text className="goods-bottom-bar__btn-text">{isSeckillMode ? '立即抢购' : '立即购买'}</Text>
            </View>
          </View>
        </View>
      ))}

      {isGroupBuyMode && (isH5() ? (
        <RootPortal>
          <View className={`goods-group-bottom ${showSpecPopup ? 'goods-group-bottom--hidden' : ''}`}>
            <View className="goods-group-bottom__origin" onClick={() => handleGroupBottomClick('origin')}>
              <Text className="goods-group-bottom__origin-price">¥{formatPrice(originPriceCent)}</Text>
              <Text className="goods-group-bottom__origin-text">原价购买</Text>
            </View>
            <View className="goods-group-bottom__group" onClick={() => handleGroupBottomClick('group')}>
              <Text className="goods-group-bottom__group-price">¥{formatPrice(groupPriceCent)}</Text>
              <Text className="goods-group-bottom__group-text">立即开团</Text>
            </View>
          </View>
        </RootPortal>
      ) : (
        <View className={`goods-group-bottom ${showSpecPopup ? 'goods-group-bottom--hidden' : ''}`}>
          <View className="goods-group-bottom__origin" onClick={() => handleGroupBottomClick('origin')}>
            <Text className="goods-group-bottom__origin-price">¥{formatPrice(originPriceCent)}</Text>
            <Text className="goods-group-bottom__origin-text">原价购买</Text>
          </View>
          <View className="goods-group-bottom__group" onClick={() => handleGroupBottomClick('group')}>
            <Text className="goods-group-bottom__group-price">¥{formatPrice(groupPriceCent)}</Text>
            <Text className="goods-group-bottom__group-text">立即开团</Text>
          </View>
        </View>
      ))}

      {showSpecPopup && (
        <View className={`spec-mask ${specPopupVisible ? 'spec-mask--show' : ''}`} onClick={closeSpecPopup}>
          <View className={`spec-popup-panel ${specPopupVisible ? 'spec-popup-panel--show' : ''}`} onClick={(e) => e.stopPropagation()}>
            <View className="spec-popup">
              <View className="spec-popup__topbar">
                <Text className="spec-popup__topbar-title">选择规格</Text>
                <Text className="spec-popup__topbar-close" onClick={closeSpecPopup}>×</Text>
              </View>
              <View className="spec-popup__header">
                <Image className="spec-popup__img" src={specImg || primaryImage} mode="aspectFill" />
                <View className="spec-popup__header-info">
                  <Text className="spec-popup__price">¥{formatPrice(selectSkuSellsPrice || minSalePrice)}</Text>
                  {selectedAttrStr ? <Text className="spec-popup__selected-specs">{selectedAttrStr}</Text> : <Text className="spec-popup__hint">请选择规格</Text>}
                </View>
              </View>
              <View className="spec-popup__specs">
                {specList.map((spec: any) => (
                  <View key={spec.specId} className="spec-popup__spec-group">
                    <Text className="spec-popup__spec-title">{spec.title}</Text>
                    <View className="spec-popup__spec-values">
                      {(spec.specValueList || []).map((val: any) => (
                        <View key={val.specValueId} className={`spec-popup__spec-tag ${selectedSku[String(spec.specId)] === String(val.specValueId) ? 'spec-popup__spec-tag--active' : ''}`} onClick={() => handleSpecSelect(spec.specId, val.specValueId)}>
                          <Text className="spec-popup__spec-tag-text">{val.specValue}</Text>
                        </View>
                      ))}
                    </View>
                  </View>
                ))}
              </View>
              <View className="spec-popup__quantity">
                <Text className="spec-popup__quantity-label">购买数量</Text>
                <View className="spec-popup__stepper">
                  <View className="spec-popup__stepper-btn" onClick={() => handleBuyNumChange(-1)}><Text className="spec-popup__stepper-btn-text">-</Text></View>
                  <Text className="spec-popup__stepper-num">{buyNum}</Text>
                  <View className="spec-popup__stepper-btn" onClick={() => handleBuyNumChange(1)}><Text className="spec-popup__stepper-btn-text">+</Text></View>
                </View>
              </View>
              <View className="spec-popup__footer">
                <View className="spec-popup__confirm-btn" onClick={handleSpecConfirm}>
                  <Text className="spec-popup__confirm-text">{buyType === 1 ? (isSeckillMode ? '立即抢购' : '立即购买') : '加入购物车'}</Text>
                </View>
              </View>
            </View>
          </View>
        </View>
      )}
    </View>
  );
}
