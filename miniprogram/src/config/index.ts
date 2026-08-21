export const config = {
  useMock: false,
  apiBaseUrl: 'http://127.0.0.1:9501',
  tokenStorageKey: 'accessToken',
  apiSignature: {
    enabled: true,
    clients: {
      h5: {
        clientId: 'h5',
        secret: 'd7b0decf52ec40b8c3b18ef80d4686e0',
      },
      miniapp: {
        clientId: 'miniapp',
        secret: 'd7b0decf52ec40b8c3b18ef80d4686e0',
      },
    },
  },
};

export const cdnBase = 'https://we-retail-static-1300977798.cos.ap-guangzhou.myqcloud.com/retail-mp';
