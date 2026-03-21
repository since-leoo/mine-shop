import { config, cdnBase } from '../../config';
import { request } from '../request';

const fallbackSwiper = [
  'https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner1.png',
  'https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner2.png',
  'https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner3.png',
  'https://tdesign.gtimg.com/miniprogram/template/retail/home/v2/banner4.png',
];

/** 获取首页数据 (mock) */
function mockFetchHome() {
  const { delay } = require('../_utils/delay');
  const { genSwiperImageList } = require('../../model/swiper');
  return delay().then(() => {
    return {
      swiper: genSwiperImageList(),
      tabList: [
        { text: '精选推荐', key: 0 },
        { text: '夏日防晒', key: 1 },
        { text: '二胎大作战', key: 2 },
        { text: '人气榜', key: 3 },
        { text: '好评榜', key: 4 },
        { text: 'RTX 30', key: 5 },
        { text: '手机也疯狂', key: 6 },
      ],
      activityImg: `${cdnBase}/activity/banner.png`,
    };
  });
}

/** 获取首页数据 */
export function fetchHome() {
  if (config.useMock) {
    return mockFetchHome();
  }
  return request({ url: '/api/v1/home', method: 'GET' }).then((data: any = {}) => {
    const pickArray = (input: any): any[] => {
      if (Array.isArray(input)) return input;
      if (Array.isArray(input?.list)) return input.list;
      if (Array.isArray(input?.records)) return input.records;
      if (Array.isArray(input?.data)) return input.data;
      return [];
    };
    const sections = data.sections || {};
    const swiper =
      pickArray(data.swiper).length > 0
        ? pickArray(data.swiper)
        : pickArray(data.banners).length > 0
          ? pickArray(data.banners)
          : pickArray(data.bannerList).length > 0
            ? pickArray(data.bannerList)
            : pickArray(data.carousel).length > 0
              ? pickArray(data.carousel)
              : pickArray(data.slides).length > 0
                ? pickArray(data.slides)
                : pickArray(data.images);
    const finalSwiper = swiper.length > 0 ? swiper : fallbackSwiper;
    return {
      swiper: finalSwiper,
      tabList: data.tabList || [],
      activityImg: data.activityImg || null,
      categoryList: data.categoryList || [],
      recommendList: pickArray(data.recommendList).length > 0
        ? pickArray(data.recommendList)
        : pickArray(sections.featured),
      hotList: pickArray(data.hotList).length > 0
        ? pickArray(data.hotList)
        : pickArray(sections.hot),
      newList: pickArray(data.newList).length > 0
        ? pickArray(data.newList)
        : pickArray(sections.new),
      seckillList: data.seckillList || [],
      groupBuyList: data.groupBuyList || [],
      seckillEndTime: data.seckillEndTime || null,
      seckillTitle: data.seckillTitle || '限时秒杀',
      seckillActivityId: data.seckillActivityId || null,
      seckillSessionId: data.seckillSessionId || null,
    };
  });
}
