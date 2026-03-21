import Taro from '@tarojs/taro';

type AnalyticsPayload = Record<string, string | number | boolean | null | undefined>;

function normalizePayload(payload: AnalyticsPayload): Record<string, string> {
  const normalized: Record<string, string> = {};
  Object.keys(payload || {}).forEach((key) => {
    const value = payload[key];
    if (value === undefined || value === null) return;
    normalized[key] = String(value);
  });
  return normalized;
}

export function trackEvent(eventName: string, payload: AnalyticsPayload = {}) {
  if (!eventName) return;
  const data = normalizePayload(payload);
  try {
    const reporter = (Taro as any).reportAnalytics;
    if (typeof reporter === 'function') {
      reporter(eventName, data);
      return;
    }
  } catch (error) {
    console.warn('[analytics] report failed', error);
  }
  console.info('[analytics]', eventName, data);
}
