import { describe, expect, it, vi } from 'vitest';

vi.mock('../request', () => ({
  request: vi.fn(() => Promise.resolve({
    page: { key: 'home', title: '首页' },
    components: [],
    publishedAt: null,
  })),
}));

import { request } from '../request';
import { fetchDiyPage } from './page';

describe('DIY 页面请求', () => {
  it('使用后端约定的 page_type 参数', async () => {
    await fetchDiyPage('home', 'h5');

    expect(request).toHaveBeenCalledWith({
      url: '/api/v1/diy/pages/home',
      method: 'GET',
      data: { page_type: 'h5' },
    });
  });
});
