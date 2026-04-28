import Taro from '@tarojs/taro';

interface MenuButtonRectLike {
  top: number;
  height: number;
  left: number;
}

interface MiniProgramNavMetrics {
  statusBarHeight: number;
  windowWidth: number;
  navHeight: number;
  capsuleWidth: number;
}

let cachedNavMetrics: MiniProgramNavMetrics | null = null;

function isMiniProgramEnv(): boolean {
  try {
    return Taro.getEnv() === Taro.ENV_TYPE.WEAPP;
  } catch {
    return false;
  }
}

function getWxApi(): any {
  return typeof globalThis !== 'undefined' ? (globalThis as any).wx : null;
}

function getWindowInfo(): { statusBarHeight?: number; windowWidth?: number; screenWidth?: number } {
  if (!isMiniProgramEnv()) {
    return Taro.getSystemInfoSync();
  }
  const wxApi = getWxApi();
  if (typeof wxApi?.getWindowInfo === 'function') {
    return wxApi.getWindowInfo() || {};
  }
  return Taro.getSystemInfoSync();
}

function getMenuButtonRect(): MenuButtonRectLike | null {
  if (!isMiniProgramEnv()) {
    return null;
  }
  const wxApi = getWxApi();
  if (typeof wxApi?.getMenuButtonBoundingClientRect === 'function') {
    return wxApi.getMenuButtonBoundingClientRect() || null;
  }
  try {
    return Taro.getMenuButtonBoundingClientRect?.() || null;
  } catch {
    return null;
  }
}

export function getMiniProgramNavMetrics(): MiniProgramNavMetrics {
  if (cachedNavMetrics) {
    return cachedNavMetrics;
  }

  const windowInfo = getWindowInfo();
  const statusBarHeight = Number(windowInfo?.statusBarHeight || 20);
  const windowWidth = Number(windowInfo?.windowWidth || windowInfo?.screenWidth || 375);
  const menuButton = getMenuButtonRect();
  const navHeight = menuButton ? (menuButton.top - statusBarHeight) * 2 + menuButton.height : 44;
  const capsuleWidth = menuButton ? windowWidth - menuButton.left + 12 : 176;

  cachedNavMetrics = {
    statusBarHeight,
    windowWidth,
    navHeight,
    capsuleWidth,
  };

  return cachedNavMetrics;
}

export function getMiniProgramWindowWidth(): number {
  return getMiniProgramNavMetrics().windowWidth;
}
