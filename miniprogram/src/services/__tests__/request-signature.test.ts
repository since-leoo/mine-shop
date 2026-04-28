import { beforeEach, describe, expect, it, vi } from 'vitest';

const requestMock = vi.fn();
const uploadFileMock = vi.fn();
const getStorageSyncMock = vi.fn();
const getEnvMock = vi.fn();

vi.mock('@tarojs/taro', () => ({
  default: {
    request: requestMock,
    uploadFile: uploadFileMock,
    getStorageSync: getStorageSyncMock,
    getEnv: getEnvMock,
    ENV_TYPE: {
      WEB: 'WEB',
      WEAPP: 'WEAPP',
    },
  },
}));

vi.mock('../../common/auth', () => ({
  ensureAuthenticated: vi.fn(),
  getStoredMemberProfile: vi.fn(),
  getStoredToken: vi.fn(),
  clearAuthStorage: vi.fn(),
}));

vi.mock('../../common/auth-guard', () => ({
  redirectToLogin: vi.fn(),
}));

describe('request signing', () => {
  beforeEach(() => {
    vi.resetModules();
    requestMock.mockReset();
    uploadFileMock.mockReset();
    getStorageSyncMock.mockReset();
    getEnvMock.mockReset();
    getStorageSyncMock.mockReturnValue('token-demo');
  });

  it('attaches signature headers for h5 requests', async () => {
    getEnvMock.mockReturnValue('WEB');
    requestMock.mockImplementation(({ success }) => {
      success({ statusCode: 200, data: { code: 200, data: {} } });
    });

    const { request } = await import('../request');

    await request({
      url: '/api/v1/auth/captcha',
      method: 'POST',
      data: { phone: '13800138000' },
    });

    const options = requestMock.mock.calls[0][0];
    expect(options.header['X-Client-Id']).toBe('h5');
    expect(options.header['X-Timestamp']).toBeTruthy();
    expect(options.header['X-Nonce']).toBeTruthy();
    expect(options.header['X-Body-Sha256']).toBeTruthy();
    expect(options.header['X-Signature']).toBeTruthy();
  });

  it('attaches signature headers for miniapp upload requests', async () => {
    getEnvMock.mockReturnValue('WEAPP');
    uploadFileMock.mockImplementation(({ success }) => {
      success({ data: JSON.stringify({ code: 200, data: { url: 'https://example.com/a.png' } }) });
    });

    const { uploadImage } = await import('../upload');

    await uploadImage('/tmp/demo.png');

    const options = uploadFileMock.mock.calls[0][0];
    expect(options.header.Authorization).toBe('Bearer token-demo');
    expect(options.header['X-Client-Id']).toBe('miniapp');
    expect(options.header['X-Timestamp']).toBeTruthy();
    expect(options.header['X-Nonce']).toBeTruthy();
    expect(options.header['X-Body-Sha256']).toBeTruthy();
    expect(options.header['X-Signature']).toBeTruthy();
  });
});
