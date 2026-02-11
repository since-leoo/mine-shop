import updateManager from './common/updateManager';
import { ensureMiniProgramLogin } from './common/auth';

App({
  onLaunch: function () {
    // 静默登录，获取token
    ensureMiniProgramLogin().catch((err) => {
      console.warn('App onLaunch silent login failed', err);
    });
  },
  onShow: function () {
    updateManager();
  },
});
