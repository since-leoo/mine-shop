import Taro from '@tarojs/taro';
import { config } from '../../config';
import { request } from '../../services/request';
import { toString as qrCodeToString } from 'qrcode';

const INVITE_STORAGE_KEY = 'distributionPendingInvite';
const INVITE_TTL = 7 * 24 * 60 * 60 * 1000;

export interface DistributionInvite {
  shareToken?: string;
  inviteCode?: string;
}

interface StoredInvite extends DistributionInvite {
  capturedAt: number;
  expiresAt: number;
  verified: boolean;
  reason?: string;
}

export interface DistributionShareOptions {
  target: 'home' | 'product';
  channel: 'link' | 'card' | 'qrcode' | 'short_link' | 'url_link';
  spuId?: string | number;
  skuId?: string | number;
  activityId?: string | number;
  orderType?: string;
  sessionId?: string | number;
  groupNo?: string;
  groupBuyId?: string | number;
}

export interface DistributionShare {
  enabled?: boolean;
  reason?: string;
  shareToken?: string;
  path: string;
  h5Url: string;
  url?: string;
  imageUrl?: string;
  expiresAt?: string | number;
}

let captureQueue: Promise<void> = Promise.resolve();
let inviteGeneration = 0;

function hasSession(): boolean {
  return Boolean(Taro.getStorageSync(config.tokenStorageKey || 'accessToken'));
}

function decode(value: string): string {
  try { return decodeURIComponent(value); } catch { return ''; }
}

function parseQuery(value: string): Record<string, string> {
  const query: Record<string, string> = {};
  value.replace(/^\?/, '').split('&').forEach((part) => {
    const index = part.indexOf('=');
    if (index > 0) query[decode(part.slice(0, index))] = decode(part.slice(index + 1));
  });
  return query;
}

/** Parse only supported public invitation fields; never accept a member ID from a URL. */
export function parseDistributionInvite(input: unknown): DistributionInvite | null {
  let query: Record<string, any> = {};
  if (typeof input === 'string') {
    const [beforeHash, hash = ''] = input.split('#');
    const searchIndex = beforeHash.indexOf('?');
    const hashIndex = hash.indexOf('?');
    query = {
      ...parseQuery(searchIndex >= 0 ? beforeHash.slice(searchIndex + 1) : beforeHash),
      ...parseQuery(hashIndex >= 0 ? hash.slice(hashIndex + 1) : ''),
    };
  } else if (input && typeof input === 'object') {
    const options = input as Record<string, any>;
    query = { ...(options.query || options) };
  }

  if (typeof query.scene === 'string') {
    const scene = decode(query.scene);
    query = { ...parseQuery(scene), ...query };
  }
  const shareToken = String(query.share_token || query.shareToken || query.token || query.s || '').trim();
  const inviteCode = String(query.invite_code || query.inviteCode || '').trim();
  if (/^[A-Za-z0-9_-]{6,128}$/.test(shareToken)) return { shareToken };
  if (/^[A-Za-z0-9]{4,32}$/.test(inviteCode)) return { inviteCode };
  return null;
}

function readStoredInvite(): StoredInvite | null {
  const stored = Taro.getStorageSync(INVITE_STORAGE_KEY);
  if (!stored || typeof stored !== 'object') return null;
  const parsed = parseDistributionInvite(stored);
  if (!parsed || !Number.isFinite(stored.capturedAt) || !Number.isFinite(stored.expiresAt)
    || stored.expiresAt <= Date.now() || stored.capturedAt > Date.now()
    || stored.expiresAt - stored.capturedAt > INVITE_TTL) {
    Taro.removeStorageSync(INVITE_STORAGE_KEY);
    return null;
  }
  return { ...stored, ...parsed };
}

function sameInvite(left: DistributionInvite, right: DistributionInvite): boolean {
  return left.shareToken === right.shareToken && left.inviteCode === right.inviteCode;
}

async function verifyStoredInvite(stored: StoredInvite, generation: number): Promise<boolean> {
  const result = await request({
    url: '/api/v1/distribution/share/resolve',
    data: stored.shareToken ? { token: stored.shareToken } : { inviteCode: stored.inviteCode },
  });
  if (generation !== inviteGeneration || hasSession()) return false;
  if (!result?.valid) {
    if (result?.reason === 'full') {
      // Keep the rejected source for the registration receipt, but allow a later valid source.
      Taro.setStorageSync(INVITE_STORAGE_KEY, { ...stored, verified: false, reason: 'full' });
    } else {
      Taro.removeStorageSync(INVITE_STORAGE_KEY);
    }
    return false;
  }
  // Validation aids first-touch selection only. Registration must validate again on the server.
  Taro.setStorageSync(INVITE_STORAGE_KEY, { ...stored, verified: true });
  return true;
}

/** Launch and re-entry use one serialized queue so a slower first source cannot lose to a later one. */
export function captureDistributionInvite(input: unknown): Promise<void> {
  const candidate = parseDistributionInvite(input);
  if (!candidate || hasSession()) return captureQueue;
  const generation = inviteGeneration;
  const capturedAt = Date.now();
  captureQueue = captureQueue.then(async () => {
    if (generation !== inviteGeneration || hasSession()) return;
    const existing = readStoredInvite();
    if (existing?.verified) return;
    if (existing) {
      if (await verifyStoredInvite(existing, generation)) return;
      if (sameInvite(existing, candidate)) return;
    }
    if (generation !== inviteGeneration || hasSession()) return;
    const stored: StoredInvite = { ...candidate, capturedAt, expiresAt: capturedAt + INVITE_TTL, verified: false };
    Taro.setStorageSync(INVITE_STORAGE_KEY, stored);
    await verifyStoredInvite(stored, generation);
  }).catch(() => {
    // Keep the unverified source on transient network failure; the next entry/login retries it.
  });
  return captureQueue;
}

export async function getDistributionInviteForRegistration(): Promise<DistributionInvite> {
  if (hasSession()) return {};
  // H5 may start login before the App effect has captured the initial URL.
  if (typeof window !== 'undefined') await captureDistributionInvite(window.location.href);
  else if (typeof Taro.getEnterOptionsSync === 'function') await captureDistributionInvite(Taro.getEnterOptionsSync());
  else if (typeof Taro.getLaunchOptionsSync === 'function') await captureDistributionInvite(Taro.getLaunchOptionsSync());
  await captureQueue;
  let stored = readStoredInvite();
  if (!stored) return {};
  if (!stored.verified) {
    try { await verifyStoredInvite(stored, inviteGeneration); } catch { /* Server registration remains authoritative. */ }
    stored = readStoredInvite();
  }
  if (!stored) return {};
  return stored.shareToken ? { shareToken: stored.shareToken } : { inviteCode: stored.inviteCode };
}

/** Clear only after successful authentication; failed attempts retain their invitation. */
export function clearDistributionInvite(): void {
  inviteGeneration += 1;
  Taro.removeStorageSync(INVITE_STORAGE_KEY);
}

export function notifyDistributionBinding(result: any): void {
  if (result?.referralBinding?.reason === 'full') {
    Taro.showToast({ title: '邀请人直属名额已满，已为你完成注册，归属平台', icon: 'none', duration: 3500 });
  }
}

/** Build from a whitelist, deliberately discarding any invitation carried by the incoming page. */
export function buildDistributionSharePath(options: DistributionShareOptions, shareToken = ''): string {
  const path = options.target === 'product' ? '/pages/goods/details/index' : '/pages/home/index';
  const query: Record<string, string | number | undefined> = options.target === 'product' ? {
    spuId: options.spuId,
    skuId: options.skuId,
    activityId: options.activityId,
    orderType: options.orderType,
    sessionId: options.sessionId,
    groupNo: options.groupNo,
    groupBuyId: options.groupBuyId,
  } : {};
  if (shareToken) query.share_token = shareToken;
  const encoded = Object.entries(query)
    .filter(([, value]) => value !== undefined && value !== '')
    .map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`).join('&');
  return `${path}${encoded ? `?${encoded}` : ''}`;
}

export function buildDistributionH5Url(path: string, currentUrl = typeof window !== 'undefined' ? window.location.href : ''): string {
  if (!currentUrl) return path;
  const hashIndex = currentUrl.indexOf('#');
  if (hashIndex >= 0) return `${currentUrl.slice(0, hashIndex).split('?')[0]}#${path}`;
  const origin = currentUrl.match(/^https?:\/\/[^/]+/i)?.[0];
  return origin ? `${origin}${path}` : path;
}

export async function createDistributionShare(options: DistributionShareOptions): Promise<DistributionShare> {
  const ordinaryPath = buildDistributionSharePath(options);
  const platform = Taro.getEnv() === Taro.ENV_TYPE.WEAPP ? 'mini_program' : 'h5';
  if (!hasSession()) return { path: ordinaryPath, h5Url: platform === 'h5' ? buildDistributionH5Url(ordinaryPath) : '' };
  const result = await request({
    url: '/api/v1/distribution/share',
    method: 'POST',
    needAuth: true,
    data: { ...options, platform },
  });
  // Build the target from known business parameters, and use only the server-issued identity.
  const path = buildDistributionSharePath(options, result?.shareToken || '');
  const share: DistributionShare = { ...result, path, h5Url: result?.h5Url || (platform === 'h5' ? buildDistributionH5Url(path) : '') };
  if (share.imageUrl?.startsWith('/')) share.imageUrl = `${config.apiBaseUrl.replace(/\/+$/, '')}${share.imageUrl}`;
  if (platform === 'h5' && options.channel === 'qrcode' && share.shareToken && share.enabled !== false && !share.imageUrl) {
    const svg = await qrCodeToString(share.h5Url, { type: 'svg', width: 320, margin: 4, errorCorrectionLevel: 'M' });
    share.imageUrl = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
  }
  return share;
}
