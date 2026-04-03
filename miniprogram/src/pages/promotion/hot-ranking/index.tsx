import { View, Text, Image } from '@tarojs/components'
import Taro, { usePullDownRefresh } from '@tarojs/taro'
import { useCallback, useEffect, useMemo, useState } from 'react'
import { isH5 } from '../../../common/platform'
import PageNav from '../../../components/page-nav'
import { fetchHotGoods, fetchRecommendGoods } from '../../../services/good/fetchGoods'
import './index.scss'

interface RankItem {
  spuId: string
  thumb: string
  title: string
  price: number
  originPrice: number
  soldCount: number
}

const RANK_LABELS = ['TOP 1', 'TOP 2', 'TOP 3', 'TOP 4', 'TOP 5']

function toPrice(value: any): number {
  const amount = Number(value ?? 0)
  if (!Number.isFinite(amount)) return 0
  return amount > 999 ? amount / 100 : amount
}

function toSoldCount(value: any): number {
  const count = Number(value ?? 0)
  if (!Number.isFinite(count) || count < 0) return 0
  return count
}

function toRankItem(item: any): RankItem {
  return {
    spuId: String(item?.spuId || item?.spu_id || item?.id || ''),
    thumb: item?.thumb || item?.image || item?.main_image || '',
    title: item?.title || item?.name || '商品',
    price: toPrice(item?.price ?? item?.salePrice ?? item?.minSalePrice ?? item?.min_price),
    originPrice: toPrice(item?.originPrice ?? item?.linePrice ?? item?.maxLinePrice ?? item?.max_price),
    soldCount: toSoldCount(item?.soldCount ?? item?.sold_count ?? item?.sales ?? item?.sale_count ?? 0),
  }
}

export default function HotRankingPage() {
  const h5 = isH5()
  const [loading, setLoading] = useState(true)
  const [rankList, setRankList] = useState<RankItem[]>([])

  const loadData = useCallback(() => {
    setLoading(true)
    Promise.all([fetchHotGoods(20), fetchRecommendGoods(20)])
      .then(([hotList, recommendList]) => {
        const source = Array.isArray(hotList) && hotList.length > 0 ? hotList : recommendList
        const mapped = (source || []).map(toRankItem).filter((item) => !!item.spuId)
        setRankList(mapped)
      })
      .catch(() => setRankList([]))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    loadData()
  }, [loadData])

  usePullDownRefresh(() => {
    loadData()
    Taro.stopPullDownRefresh()
  })

  const topItem = rankList[0]
  const listItems = rankList.slice(1, 8)
  const totalSold = useMemo(() => rankList.reduce((sum, item) => sum + item.soldCount, 0), [rankList])

  const openGoods = useCallback((item: RankItem) => {
    if (!item.spuId) return
    Taro.navigateTo({ url: `/pages/goods/details/index?spuId=${item.spuId}` })
  }, [])

  const handleBack = useCallback(() => {
    const pages = Taro.getCurrentPages()
    if (pages.length > 1) {
      Taro.navigateBack()
      return
    }
    Taro.switchTab({ url: '/pages/home/index' }).catch(() => Taro.reLaunch({ url: '/pages/home/index' }))
  }, [])

  return (
    <View className="hot-ranking">
      {!h5 ? <PageNav title="热卖榜单" light background="transparent" /> : null}
      <View className="hot-ranking__hero">
        <View className="hot-ranking__topbar">
          {h5 ? (
            <View className="hot-ranking__topbar-btn" onClick={handleBack}>
              <Text className="hot-ranking__topbar-icon">‹</Text>
            </View>
          ) : (
            <View className="hot-ranking__topbar-btn hot-ranking__topbar-btn--placeholder" />
          )}
          <View className="hot-ranking__topbar-title-wrap">
            <Text className="hot-ranking__eyebrow">HOT SALE RANKING</Text>
            <Text className="hot-ranking__headline">热卖榜单</Text>
            <Text className="hot-ranking__subline">大家都在买的口碑好物，按实时销量更新</Text>
          </View>
          {h5 ? (
            <View className="hot-ranking__topbar-btn">
              <Text className="hot-ranking__topbar-icon">↗</Text>
            </View>
          ) : (
            <View className="hot-ranking__topbar-btn hot-ranking__topbar-btn--placeholder" />
          )}
        </View>
        <View className="hot-ranking__hero-metrics">
          <View className="hot-ranking__metric">
            <Text className="hot-ranking__metric-label">上榜商品</Text>
            <Text className="hot-ranking__metric-value">{rankList.length} 款</Text>
          </View>
          <View className="hot-ranking__metric">
            <Text className="hot-ranking__metric-label">累计销量</Text>
            <Text className="hot-ranking__metric-value">{totalSold}</Text>
          </View>
        </View>
      </View>

      <View className="hot-ranking__content">
        {topItem ? (
          <View className="hot-ranking__top-card" onClick={() => openGoods(topItem)}>
            <View className="hot-ranking__top-cover-wrap">
              <Image className="hot-ranking__top-cover" src={topItem.thumb} mode="aspectFill" />
              <Text className="hot-ranking__tag">{RANK_LABELS[0]}</Text>
            </View>
            <View className="hot-ranking__top-info">
              <Text className="hot-ranking__top-title">{topItem.title}</Text>
              <Text className="hot-ranking__top-sell">近 24h 销量 {topItem.soldCount}</Text>
              <View className="hot-ranking__price-row">
                <Text className="hot-ranking__price">¥{topItem.price.toFixed(2)}</Text>
                {topItem.originPrice > 0 ? <Text className="hot-ranking__origin">¥{topItem.originPrice.toFixed(2)}</Text> : null}
              </View>
              <View className="hot-ranking__buy-btn"><Text className="hot-ranking__buy-btn-text">立即抢购</Text></View>
            </View>
          </View>
        ) : null}

        {loading ? <View className="hot-ranking__state">加载中...</View> : null}
        {!loading && rankList.length === 0 ? <View className="hot-ranking__state">暂无热卖榜单数据</View> : null}

        {listItems.length > 0 ? (
          <View className="hot-ranking__list">
            {listItems.map((item, index) => (
              <View key={`${item.spuId}-${index}`} className="hot-ranking__item" onClick={() => openGoods(item)}>
                <View className="hot-ranking__item-cover-wrap">
                  <Image className="hot-ranking__item-cover" src={item.thumb} mode="aspectFill" />
                  <Text className="hot-ranking__tag hot-ranking__tag--small">{RANK_LABELS[index + 1] || `TOP ${index + 2}`}</Text>
                </View>
                <View className="hot-ranking__item-info">
                  <Text className="hot-ranking__item-title">{item.title}</Text>
                  <Text className="hot-ranking__item-sell">销量 {item.soldCount}</Text>
                  <View className="hot-ranking__price-row">
                    <Text className="hot-ranking__price hot-ranking__price--small">¥{item.price.toFixed(2)}</Text>
                    {item.originPrice > 0 ? (
                      <Text className="hot-ranking__origin hot-ranking__origin--small">¥{item.originPrice.toFixed(2)}</Text>
                    ) : null}
                  </View>
                </View>
                <View className="hot-ranking__item-btn"><Text className="hot-ranking__item-btn-text">去购买</Text></View>
              </View>
            ))}
          </View>
        ) : null}
      </View>
    </View>
  )
}
