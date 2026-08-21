import Taro from '@tarojs/taro';
import { config } from '../../config';
import { request } from '../request';

export function openCustomerConversation() {
  return request({ url: '/api/v1/customer-service/conversations', method: 'POST', needAuth: true });
}

export function getCustomerMessages(conversationNo: string) {
  return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(conversationNo)}/messages`, needAuth: true });
}

export function getCustomerSocketTicket() {
  return request({ url: '/api/v1/customer-service/socket-ticket', method: 'POST', needAuth: true });
}

export function getCustomerServiceConfig() {
  return request({ url: '/api/v1/customer-service/config', needAuth: true });
}

export function closeCustomerConversation(conversationNo: string) {
  return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(conversationNo)}/close`, method: 'POST', needAuth: true });
}

export function customerSocketUrl(gatewayUrl = '', socketPath = '/customer-service'): string {
  if (gatewayUrl) return `${gatewayUrl.replace(/\/$/, '')}${socketPath}`;
  const base = (config.apiBaseUrl || '').replace(/^http/, 'ws').replace(/\/$/, '');
  return `${base.replace(/:\d+$/, '')}:9502${socketPath}`;
}

export function createClientMessageId() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export { Taro };
