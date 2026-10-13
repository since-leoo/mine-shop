import Taro from '@tarojs/taro';
import { isLoggedIn } from '../../../common/auth';

export function showVerificationHelp() {
  return Taro.showModal({ title: '短信验证说明', content: '请填写本人手机号，获取验证码后在有效期内完成验证。未收到时请检查号码及短信拦截，倒计时结束后可重新获取。请勿向他人泄露验证码。', showCancel: false });
}

/** 正常发送显示短信提示；仅服务端主动返回测试验证码时显示调试弹窗。 */
export function showVerificationSent(result: { code?: string } | undefined) {
  if (result?.code) return Taro.showModal({ title: '开发验证码', content: `当前验证码：${result.code}`, showCancel: false });
  return Taro.showToast({ title: '验证码已发送', icon: 'success' });
}

export function contactSupport() {
  if (!isLoggedIn()) return Taro.showModal({ title: '联系客服', content: '请先登录后发起在线咨询。忘记密码时，可通过绑定的手机号验证并重置密码。', showCancel: false });
  return Taro.navigateTo({ url: '/pages/customer-service/index' });
}
