import { request } from '../request';

export function authorizeProfile({ nickname, avatarUrl, gender }) {
  if (!nickname || !avatarUrl) {
    return Promise.reject(new Error('缺少头像或昵称信息'));
  }
  return request({
    url: '/api/v1/member/profile/authorize',
    method: 'POST',
    needAuth: true,
    data: {
      nickname,
      avatar_url: avatarUrl,
      gender,
    },
  });
}
