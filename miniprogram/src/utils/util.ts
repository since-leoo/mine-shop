import Taro from '@tarojs/taro';
import dayjs from 'dayjs';
import { getMiniProgramWindowWidth } from './system-info';

export const formatTime = (date: Date | string | number, template: string): string =>
  dayjs(date).format(template);

export function priceFormat(price: number | string | null, fill = 0): string | number | null {
  if (price === null || price === Infinity || isNaN(Number(price))) return price;
  let priceFormatValue =
    Math.round(parseFloat(`${price}`) * 10 ** 8) / 10 ** 8;
  let result = `${Math.ceil(priceFormatValue) / 100}`;
  if (fill > 0) {
    if (result.indexOf('.') === -1) result = `${result}.`;
    const n = fill - (result.split('.')[1]?.length ?? 0);
    for (let i = 0; i < n; i++) result = `${result}0`;
  }
  return result;
}

export const cosThumb = (url: string, width: number, height: number = width): string => {
  if (url.indexOf('?') > -1) return url;
  let normalizedUrl = url;
  if (normalizedUrl.indexOf('http://') === 0) {
    normalizedUrl = normalizedUrl.replace('http://', 'https://');
  }
  return `${normalizedUrl}?imageMogr2/thumbnail/${~~width}x${~~height}`;
};

export const get = <T = any>(
  source: any,
  paths: string | string[],
  defaultValue?: T,
): T | undefined => {
  let pathArray: string[];
  if (typeof paths === 'string') {
    pathArray = paths
      .replace(/\[/g, '.')
      .replace(/\]/g, '')
      .split('.')
      .filter(Boolean);
  } else {
    pathArray = paths;
  }
  const { length } = pathArray;
  let index = 0;
  let current: any = source;
  while (current != null && index < length) {
    current = current[pathArray[index++]];
  }
  return current === undefined || index === 0 ? defaultValue : current;
};

let systemWidth = 0;

export const loadSystemWidth = (): number => {
  if (systemWidth) return systemWidth;
  try {
    systemWidth = getMiniProgramWindowWidth();
  } catch (_e) {
    systemWidth = 0;
  }
  return systemWidth;
};

export const rpx2px = (rpx: number, round = false): number => {
  loadSystemWidth();
  const result = (rpx * systemWidth) / 750;
  if (round) return Math.floor(result);
  return result;
};

export const phoneEncryption = (phone: string): string =>
  phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2');

const innerPhoneReg = '^1(?:3\\d|4[4-9]|5[0-35-9]|6[67]|7[0-8]|8\\d|9\\d)\\d{8}$';

export const phoneRegCheck = (phone: string): boolean =>
  new RegExp(innerPhoneReg).test(phone);
