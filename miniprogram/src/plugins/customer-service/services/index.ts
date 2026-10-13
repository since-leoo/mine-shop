import Taro from '@tarojs/taro';
import { config } from '../../../config';
import { request } from '../../../services/request';

export function openCustomerConversation(categoryId?: number) {
  return request({ url: '/api/v1/customer-service/conversations', method: 'POST', data: { protocol_version: 2, category_id: categoryId }, needAuth: true });
}

export function getCustomerMessages(conversationNo: string, afterSequence = 0, limit = 100) {
  return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(conversationNo)}/messages`, data: { after_sequence: afterSequence, limit }, needAuth: true });
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
  return `${base.replace(/:\d+$/, '')}${socketPath}`;
}

export function createClientMessageId() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export { Taro };

export function getCustomerConversation(no: string) { return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(no)}`, needAuth: true }); }
export function requestHuman(no: string) { return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(no)}/request-human`, method: 'POST', needAuth: true }); }
export function getCustomerCategories() { return request({ url: '/api/v1/customer-service/categories', needAuth: true }); }
export function classifyCustomerConversation(no: string, categoryId: number) { return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(no)}/category`, method: 'POST', data: { category_id: categoryId }, needAuth: true }); }
export function sendCustomerText(no: string, clientMessageId: string, text: string) { return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(no)}/messages`, method: 'POST', data: { client_message_id: clientMessageId, text }, needAuth: true }); }
export function rateCustomerConversation(no: string, score: number, comment = '') { return request({ url: `/api/v1/customer-service/conversations/${encodeURIComponent(no)}/rating`, method: 'POST', data: { score, comment }, needAuth: true }); }
