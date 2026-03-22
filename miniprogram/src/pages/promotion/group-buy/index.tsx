import { View, Text, Image } from '@tarojs/components';
import Taro, { usePullDownRefresh } from '@tarojs/taro';
import { useState, useEffect, useRef, useCallback } from 'react';
import { fetchGroupBuyList } from '../../../services/promotion/groupBuy';
import PageNav from '../../../components/page-nav';
import './index.scss';

interface GroupItem {
  spuId: string;
  activityId: string;
  thumb: string;
  title: string;
  priceInt: string;
  priceDec: string;
  originPriceFmt: string;
  originPrice: number;
  tags: { title: string }[];
  soldText: string;
}

interface CountdownState {
  d: number;
  h: string;
  m: string;
  s: string;
}

function fmtPrice(cents: number) {
  const yuan = (cents / 100).toFixed(2);
  const [int, dec] = yuan.split('.');
  return { int, dec: dec === '00' ? '' : dec };
}

function calcCountdown(ms: number): CountdownState | null {
  if (ms <= 0) return null;
  const totalSec = Math.floor(ms / 1000);
  const d = Math.floor(totalSec / 86400);
  const h = String(Math.floor((totalSec % 86400) / 3600)).padStart(2, '0');
  const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
  const s = String(totalSec % 60).padStart(2, '0');
  return { d, h, m, s };
}

export default function GroupBuy() {
  const [list, setList] = useState<GroupItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [countdown, setCountdown] = useState<CountdownState | null>(null);

  const endTimeRef = useRef(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const clearTimer = useCallback(() => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }
  }, []);

  const startTimer = useCallback(() => {
    clearTimer();
    const tick = () => {
      const remaining = endTimeRef.current - Date.now();
      if (remaining <= 0) {
        setCountdown(null);
        clearTimer();
        return;
      }
      setCountdown(calcCountdown(remaining));
    };
    tick();
    timerRef.current = setInterval(tick, 1000);
  }, [clearTimer]);

  const loadData = useCallback(() => {
    setLoading(true);
    fetchGroupBuyList(50)
      .then((res: any) => {
        const items = (res.list || []).map((item: any) => {
          const p = fmtPrice(item.price || 0);
          const op = ((item.originPrice || 0) / 100).toFixed(2);
          const sold = item.soldQuantity || 0;
          return {
            spuId: item.spuId,
            activityId: item.activityId,
            thumb: item.thumb || '',
            title: item.title || '',
            priceInt: p.int,
            priceDec: p.dec,
            originPrice: item.originPrice || 0,
            originPriceFmt: op,
            tags: item.tags || [],
            soldText: sold > 9999 ? (sold / 10000).toFixed(1) + '万' : String(sold),
          };
        });
        endTimeRef.current = Date.now() + (res.time || 0);
        setList(items);
        if (res.time > 0) startTimer();
      })
      .catch(() => setList([]))
      .finally(() => setLoading(false));
  }, [startTimer]);

  useEffect(() => {
    loadData();
    return () => clearTimer();
  }, [loadData, clearTimer]);

  usePullDownRefresh(() => {
    loadData();
    Taro.stopPullDownRefresh();
  });

  const handleItemTap = useCallback((item: GroupItem) => {
    if (item.spuId) {
      Taro.navigateTo({
        url: `/pages/goods/details/index?spuId=${item.spuId}&orderType=group_buy&groupBuyId=${item.activityId || ''}`,
      });
    }
  }, []);

  return (
    <View className="group-buy">
      <PageNav title="拼团特惠" light background="transparent" />
      {/* Header banner */}
      <View className="group-buy__header">
        <View className="group-buy__header-circle group-buy__header-circle--right" />
        <View className="group-buy__header-circle group-buy__header-circle--left" />
        <View className="group-buy__header-bg" />
        <View className="group-buy__header-body">
          <View className="group-buy__header-left">
            <Text className="group-buy__header-title">拼团特惠</Text>
            <Text className="group-buy__header-desc">好物拼着买 更划算</Text>
          </View>
          {countdown && (
            <View className="group-buy__header-right">
              <Text className="group-buy__cd-label">距结束</Text>
              <View className="group-buy__cd-row">
                <Text className="group-buy__cd-block">{countdown.h}</Text>
                <Text className="group-buy__cd-sep">:</Text>
                <Text className="group-buy__cd-block">{countdown.m}</Text>
                <Text className="group-buy__cd-sep">:</Text>
                <Text className="group-buy__cd-block">{countdown.s}</Text>
              </View>
            </View>
          )}
        </View>
      </View>

      {/* Loading */}
      {loading && (
        <View className="group-buy__state">
          <Text className="group-buy__state-text">加载中...</Text>
        </View>
      )}

      {/* Empty */}
      {!loading && list.length === 0 && (
        <View className="group-buy__state">
          <Text className="group-buy__state-text">暂无拼团活动</Text>
        </View>
      )}

      {/* Product grid */}
      {list.length > 0 && (
        <View className="group-buy__grid">
          {list.map((item) => (
            <View
              key={item.spuId}
              className="group-buy__card"
              onClick={() => handleItemTap(item)}
            >
              <View className="group-buy__card-img-box">
                <Image className="group-buy__card-img" src={item.thumb} mode="aspectFill" lazyLoad />
                {item.tags[0]?.title && (
                  <View className="group-buy__card-tag">
                    <Text className="group-buy__card-tag-text">{item.tags[0].title}</Text>
                  </View>
                )}
              </View>
              <View className="group-buy__card-body">
                <Text className="group-buy__card-title">{item.title}</Text>
                <View className="group-buy__card-price-row">
                  <View className="group-buy__group-price-wrap">
                    <Text className="group-buy__price-tag">拼团价</Text>
                    <View className="group-buy__group-price">
                      <Text className="group-buy__gp-sym">¥</Text>
                      <Text className="group-buy__gp-int">{item.priceInt}</Text>
                      {item.priceDec && (
                        <Text className="group-buy__gp-dec">.{item.priceDec}</Text>
                      )}
                    </View>
                  </View>
                  {item.originPrice > 0 && (
                    <Text className="group-buy__origin">¥{item.originPriceFmt}</Text>
                  )}
                </View>
                <View className="group-buy__card-footer">
                  <Text className="group-buy__sold">已拼{item.soldText}件</Text>
                  <View className="group-buy__btn">
                    <Text className="group-buy__btn-text">去拼团</Text>
                  </View>
                </View>
              </View>
            </View>
          ))}
        </View>
      )}
    </View>
  );
}
