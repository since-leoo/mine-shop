import Taro from '@tarojs/taro';

export function isMiniProgram(): boolean {
  return Taro.getEnv() === Taro.ENV_TYPE.WEAPP;
}

export function isH5(): boolean {
  return Taro.getEnv() === Taro.ENV_TYPE.WEB;
}
