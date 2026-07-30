import { beforeEach, describe, expect, it, vi } from 'vitest';

const requestMock = vi.fn();
const uploadFileMock = vi.fn();
const getStorageSyncMock = vi.fn();
const getEnvMock = vi.fn();
const showToastMock = vi.fn();
const ensureAuthenticatedMock = vi.fn();
const getStoredMemberProfileMock = vi.fn();
const getStoredTokenMock = vi.fn();
const clearAuthStorageMock = vi.fn();
const redirectToLoginMock = vi.fn();

vi.mock('@tarojs/taro', () => ({
  default: {
    request: requestMock,
    uploadFile: uploadFileMock,
    getStorageSync: getStorageSyncMock,
    getEnv: getEnvMock,
    showToast: showToastMock,
    ENV_TYPE: {
      WEB: 'WEB',
      WEAPP: 'WEAPP',
    },
  },
}));

vi.mock('../../common/auth', () => ({
  ensureAuthenticated: ensureAuthenticatedMock,
  getStoredMemberProfile: getStoredMemberProfileMock,
  getStoredToken: getStoredTokenMock,
  clearAuthStorage: clearAuthStorageMock,
}));

vi.mock('../../common/auth-guard', () => ({
  redirectToLogin: redirectToLoginMock,
}));

describe('request signing', () => {
  beforeEach(() => {
    vi.resetModules();
    requestMock.mockReset();
    uploadFileMock.mockReset();
    getStorageSyncMock.mockReset();
    getEnvMock.mockReset();
    showToastMock.mockReset();
    ensureAuthenticatedMock.mockReset();
    getStoredMemberProfileMock.mockReset();
    getStoredTokenMock.mockReset();
    clearAuthStorageMock.mockReset();
    redirectToLoginMock.mockReset();
    getStorageSyncMock.mockReturnValue('token-demo');
    getStoredTokenMock.mockReturnValue('token-demo');
    getStoredMemberProfileMock.mockReturnValue(null);
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

  it('swallows h5 auth failures after redirecting to login', async () => {
    getEnvMock.mockReturnValue('WEB');
    requestMock.mockImplementation(({ success }) => {
      success({ statusCode: 401, data: { code: 401, message: 'token expired' } });
    });

    const { request } = await import('../request');

    const promise = request({
      url: '/api/v1/member/profile',
      method: 'GET',
      needAuth: true,
    });

    const result = await Promise.race([
      promise.then(() => 'resolved', () => 'rejected'),
      Promise.resolve('pending'),
    ]);

    expect(result).toBe('pending');
    await Promise.resolve();
    await Promise.resolve();

    expect(clearAuthStorageMock).toHaveBeenCalledTimes(1);
    expect(redirectToLoginMock).toHaveBeenCalledTimes(1);
  });
});
