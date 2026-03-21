import Taro from '@tarojs/taro';

export const getPermission = ({
  code,
  name,
}: {
  code: string;
  name: string;
}): Promise<void> => {
  return new Promise((resolve, reject) => {
    Taro.getSetting({
      success: (res) => {
        if (res.authSetting[code] === false) {
          Taro.showModal({
            title: `获取${name}失败`,
            content: `获取${name}失败，请在【右上角】-小程序【设置】项中，将【${name}】开启。`,
            confirmText: '去设置',
            confirmColor: '#E8836B',
            success(modalRes) {
              if (modalRes.confirm) {
                Taro.openSetting({
                  success(settingRes) {
                    if (settingRes.authSetting[code] === true) {
                      resolve();
                    } else {
                      reject();
                    }
                  },
                });
              } else {
                reject();
              }
            },
            fail() {
              reject();
            },
          });
        } else {
          resolve();
        }
      },
      fail() {
        reject();
      },
    });
  });
};
