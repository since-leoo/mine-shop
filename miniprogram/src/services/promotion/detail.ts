import { request } from '../request';
import { config } from '../../config';

type SessionStatus = 'ongoing' | 'upcoming' | 'ended';

interface SessionInfo {
  id: string;
  time: string;
  status: SessionStatus;
  startTime?: number;
  endTime?: number;
  remainingTime?: number;
}

interface FetchPromotionOptions {
  sessionId?: string | number;
}

function pickList(res: any): any[] {
  if (Array.isArray(res)) return res;
  if (Array.isArray(res?.list)) return res.list;
  if (Array.isArray(res?.records)) return res.records;
  if (Array.isArray(res?.products)) return res.products;
  if (Array.isArray(res?.items)) return res.items;
  if (Array.isArray(res?.goodsList)) return res.goodsList;
  if (Array.isArray(res?.seckillProducts)) return res.seckillProducts;
  if (Array.isArray(res?.data)) return res.data;
  if (Array.isArray(res?.data?.list)) return res.data.list;
  if (Array.isArray(res?.data?.records)) return res.data.records;
  if (Array.isArray(res?.data?.products)) return res.data.products;
  return [];
}

function toTimestamp(input: any): number {
  if (input === null || input === undefined || input === '') return 0;
  const num = Number(input);
  if (Number.isFinite(num) && num > 0) {
    if (num >= 1e12) return num;
    if (num >= 1e9) return num * 1000;
    return 0;
  }
  if (typeof input === 'string') {
    const raw = input.trim();
    const candidates: string[] = [raw];
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
      candidates.push(raw.replace(/-/g, '/'));
    }
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(raw)) {
      candidates.push(raw.replace(' ', 'T'));
      candidates.push(raw.replace(/-/g, '/'));
    }
    for (const text of candidates) {
      const time = new Date(text).getTime();
      if (Number.isFinite(time) && time > 0) return time;
    }
  }
  return 0;
}

function toDurationMs(input: any): number {
  if (input === null || input === undefined || input === '') return 0;
  const num = Number(input);
  if (!Number.isFinite(num) || num <= 0) return 0;
  if (num >= 1e12) return Math.max(0, num - Date.now());
  if (num >= 1e9) return Math.max(0, num * 1000 - Date.now());
  return num >= 1e6 ? num : num * 1000;
}

function normalizeSessionStatus(raw: any, startAt: number, endAt: number): SessionStatus {
  const text = String(raw || '').toLowerCase();
  if (text.includes('ing') || text.includes('run') || text.includes('start') || text.includes('进行') || text.includes('抢购中')) {
    return 'ongoing';
  }
  if (text.includes('end') || text.includes('finish') || text.includes('close') || text.includes('结束')) {
    return 'ended';
  }
  if (text.includes('up') || text.includes('wait') || text.includes('soon') || text.includes('即将')) {
    return 'upcoming';
  }

  const now = Date.now();
  if (startAt && now < startAt) return 'upcoming';
  if (endAt && now >= endAt) return 'ended';
  if (startAt && endAt && now >= startAt && now < endAt) return 'ongoing';
  return 'upcoming';
}

function buildSessionTimeText(item: any, startAt: number): string {
  const explicit = item.time || item.label || item.timeText || item.slot || item.name || item.title;
  if (explicit) return String(explicit);
  if (startAt > 0) {
    const date = new Date(startAt);
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    return `${hour}:${minute}`;
  }
  return '--:--';
}

function normalizeSessions(res: any): SessionInfo[] {
  const candidates = [
    res?.sessions,
    res?.sessionList,
    res?.timeSlots,
    res?.timeline,
    res?.activitySessions,
    res?.tabs,
    res?.data?.sessions,
    res?.data?.sessionList,
    res?.data?.timeSlots,
    res?.data?.timeline,
  ];

  const source = candidates.find((item) => Array.isArray(item)) as any[] | undefined;
  if (!source || source.length === 0) return [];

  const sessions = source
    .map((item: any) => {
      const startTime = toTimestamp(item.startTime || item.startAt || item.beginTime || item.beginAt || item.sessionStartTime || item.start);
      const endTime = toTimestamp(item.endTime || item.endAt || item.stopTime || item.sessionEndTime || item.end);
      const id = String(item.sessionId || item.id || item.code || item.value || item.time || startTime || '');
      if (!id) return null;

      return {
        id,
        time: buildSessionTimeText(item, startTime),
        status: normalizeSessionStatus(item.status || item.state || item.statusTag, startTime, endTime),
        startTime: startTime || undefined,
        endTime: endTime || undefined,
        remainingTime: toDurationMs(item.remainingTime || item.remainTime || item.countdown || item.leftTime) || undefined,
      };
    })
    .filter(Boolean) as SessionInfo[];

  const deduped = new Map<string, SessionInfo>();
  for (const session of sessions) {
    const existing = deduped.get(session.id);
    if (!existing) {
      deduped.set(session.id, session);
      continue;
    }

    // Prefer the richer/latest copy when upstream returns duplicate session rows.
    deduped.set(session.id, {
      ...existing,
      ...session,
      remainingTime: session.remainingTime ?? existing.remainingTime,
      startTime: session.startTime ?? existing.startTime,
      endTime: session.endTime ?? existing.endTime,
      status:
        existing.status === 'ongoing' || session.status === 'ongoing'
          ? 'ongoing'
          : existing.status === 'upcoming' || session.status === 'upcoming'
            ? 'upcoming'
            : 'ended',
    });
  }

  return Array.from(deduped.values());
}

/**
 * 获取秒杀促销列表（对应 TDesign promotion 页面）
 */
export function fetchPromotion(ID: number | string = 0, options: FetchPromotionOptions = {}) {
  if (config.useMock) {
    const { delay } = require('../_utils/delay');
    const { getPromotion } = require('../../model/promotion');
    return delay().then(() => ({
      ...getPromotion(ID),
      sessions: [],
      currentSessionId: options.sessionId ? String(options.sessionId) : '',
      activityId: String(ID || ''),
    }));
  }

  const activityId = Number(ID || 0) > 0 ? Number(ID) : undefined;
  const sessionId = options.sessionId ? String(options.sessionId) : undefined;

  return request({
    url: '/api/v1/seckill/products',
    method: 'GET',
    data: {
      limit: 20,
      activityId,
      promotionId: activityId,
      sessionId,
    },
  }).then((res: any) => {
    const sessions = normalizeSessions(res);
    const list = pickList(res).map((item: any, index: number) => {
      const normalizedActivityId =
        item.activityId || item.promotionId || item.seckillActivityId || item.activity || activityId || '';
      const normalizedSessionId =
        item.sessionId ||
        item.timeSlotId ||
        item.slotId ||
        item.seckillSessionId ||
        item.session ||
        sessionId ||
        '';
      const normalizedSpuId = item.spuId || item.id || item.goodsId || item.productId || '';

      return {
        spuId: normalizedSpuId,
        activityId: normalizedActivityId,
        sessionId: normalizedSessionId,
        renderKey: [
          String(normalizedActivityId || 'activity'),
          String(normalizedSessionId || 'session'),
          String(normalizedSpuId || 'goods'),
          String(index),
        ].join('-'),
        thumb: item.thumb || item.primaryImage || item.image || item.pic || '',
        title: item.title || item.goodsName || item.name || item.spuName || '',
        price: item.price ?? item.minSalePrice ?? item.seckillPrice ?? 0,
        originPrice: item.originPrice ?? item.maxLinePrice ?? item.linePrice ?? item.marketPrice ?? 0,
        tags: item.tags || [],
        progress: item.progress ?? item.progressPercent ?? item.percent ?? 0,
        soldPercent: item.soldPercent ?? item.salePercent ?? item.soldRate ?? item.sold_ratio ?? 0,
        soldQuantity: item.soldQuantity ?? item.soldNum ?? item.sales ?? item.saleCount ?? 0,
        totalQuantity: item.totalQuantity ?? item.totalStock ?? item.stockTotal ?? item.limitStock ?? 0,
        stockQuantity: item.stockQuantity ?? item.stock ?? item.leftStock ?? item.remainStock ?? 0,
      };
    });

    const currentSessionId =
      String(
        res?.currentSessionId ||
          res?.sessionId ||
          res?.activeSessionId ||
          (sessions.find((item) => item.status === 'ongoing')?.id || ''),
      ) || '';

    const time =
      toDurationMs(res?.time || res?.remainTime || res?.remainingTime || res?.countdown || res?.leftTime) ||
      sessions.find((item) => item.id === currentSessionId)?.remainingTime ||
      sessions.find((item) => item.status === 'ongoing')?.remainingTime ||
      0;

    return {
      list,
      banner: res?.banner || res?.bannerUrl || res?.activityBanner || '',
      time,
      showBannerDesc: true,
      statusTag: res?.statusTag || (Number(time || 0) > 0 ? 'ongoing' : 'finish'),
      sessions,
      currentSessionId,
      activityId: String(res?.activityId || res?.promotionId || activityId || ''),
    };
  });
}
