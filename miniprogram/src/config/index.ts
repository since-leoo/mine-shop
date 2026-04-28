export const config = {
  useMock: false,
  apiBaseUrl: 'http://127.0.0.1:9501',
  tokenStorageKey: 'accessToken',
  apiSignature: {
    enabled: true,
    clients: {
      h5: {
        clientId: 'h5',
        secret: 'change-me-h5-signature-secret',
      },
      miniapp: {
        clientId: 'miniapp',
        secret: 'change-me-miniapp-signature-secret',
      },
    },
  },
};

export const cdnBase = 'https://we-retail-static-1300977798.cos.ap-guangzhou.myqcloud.com/retail-mp';
