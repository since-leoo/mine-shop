import { Button, Input, Text, View } from '@tarojs/components';
import { Eye, Lock, Message, Phone, Service, Shield } from '@nutui/icons-react-taro';
import Taro, { useRouter } from '@tarojs/taro';
import { useEffect, useMemo, useState } from 'react';
import { persistAuth } from '../../../common/auth';
import { navigateAfterLogin } from '../../../common/auth-guard';
import { isH5 } from '../../../common/platform';
import { fetchRegisterProtocols, passwordLogin } from '../../../services/auth/h5Auth';
import { isValidCode, isValidPassword, isValidPhone } from '../shared/auth-form';
import './index.scss';
import './page.scss';

type LoginTab = 'password' | 'sms';

function UnsupportedAuthEntry() {
  return (
    <View className="auth-page auth-page--unsupported">
      <View className="auth-plain-card">
        <Text className="auth-plain-card__title">请在 H5 端使用此入口</Text>
        <Text className="auth-plain-card__desc">小程序端保留原有微信登录和授权流程，这里不覆盖原来的体验。</Text>
        <Button className="auth-button auth-button--primary" onClick={() => Taro.switchTab({ url: '/pages/usercenter/index' })}>
          返回个人中心
        </Button>
      </View>
    </View>
  );
}

export default function LoginPage() {
  const router = useRouter();
  const redirect = useMemo(() => router.params?.redirect || '/pages/home/index', [router.params]);
  const [activeTab, setActiveTab] = useState<LoginTab>('password');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [smsCode, setSmsCode] = useState('');
  const [userAgreement, setUserAgreement] = useState('');
  const [privacyPolicy, setPrivacyPolicy] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    setSmsCode('');
  }, [activeTab]);

  useEffect(() => {
    if (!isH5()) return;
    fetchRegisterProtocols()
      .then((result: any) => {
        setUserAgreement(result?.userAgreement || '');
        setPrivacyPolicy(result?.privacyPolicy || '');
      })
      .catch(() => {
        setUserAgreement('');
        setPrivacyPolicy('');
      });
  }, []);

  if (!isH5()) {
    return <UnsupportedAuthEntry />;
  }

  const showProtocol = (title: string, content: string) => {
    Taro.showModal({
      title,
      content: content || '当前暂未配置内容',
      showCancel: false,
      confirmText: '我知道了',
    });
  };

  const handlePasswordSubmit = async () => {
    if (!isValidPhone(phone)) {
      Taro.showToast({ title: '请输入正确手机号', icon: 'none' });
      return;
    }
    if (!isValidPassword(password)) {
      Taro.showToast({ title: '密码至少 6 位', icon: 'none' });
      return;
    }

    setSubmitting(true);
    try {
      const response = await passwordLogin(phone, password);
      persistAuth(response);
      Taro.showToast({ title: '登录成功', icon: 'success' });
      navigateAfterLogin(redirect);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '登录失败', icon: 'none' });
    } finally {
      setSubmitting(false);
    }
  };

  const handleSmsSubmit = async () => {
    if (!isValidPhone(phone)) {
      Taro.showToast({ title: '请输入正确手机号', icon: 'none' });
      return;
    }
    if (!isValidCode(smsCode)) {
      Taro.showToast({ title: '请输入 6 位验证码', icon: 'none' });
      return;
    }

    Taro.showToast({ title: '短信登录接口待接入', icon: 'none' });
  };

  return (
    <View className="auth-page auth-page--h5 auth-page--login auth-login-page-entry">
      <View className="auth-h5-shell">
        <View className="auth-hero auth-hero--login">
          <View className="auth-hero__brand">
            <View className="auth-hero__mark">
              <Text className="auth-hero__mark-text">M</Text>
            </View>
            <Text className="auth-hero__title">欢迎来到{`\n`}MineMall 会员中心</Text>
            <Text className="auth-hero__desc">延续 design.html 里的暖杏色与奶油卡片风格，让 H5 登录入口保持轻盈、亲切和可信赖的体验。</Text>
          </View>
        </View>

        <View className="auth-card auth-card--h5 auth-card--login">
          <View className="auth-card__head">
            <View className="auth-card__eyebrow auth-card__eyebrow--icon">
              <View className="auth-card__eyebrow-icon"><Shield size={14} /></View>
              <Text className="auth-card__eyebrow-text">双方式登录</Text>
            </View>
            <Text className="auth-card__title">登录你的商城账号</Text>
            <Text className="auth-card__desc">支持账号密码登录和短信验证码登录，H5 首次进入不再走小程序静默授权，登录体验严格按设计稿落地。</Text>
          </View>

          <View className="auth-tabs">
            <View className={`auth-tab ${activeTab === 'password' ? 'is-active' : ''}`} onClick={() => setActiveTab('password')}>
              账号密码登录
            </View>
            <View className={`auth-tab ${activeTab === 'sms' ? 'is-active' : ''}`} onClick={() => setActiveTab('sms')}>
              短信验证码登录
            </View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">登录账号</Text>
              <Text className="auth-field__hint">当前支持手机号登录</Text>
            </View>
            <View className="auth-field__box">
              <View className="auth-field__icon"><Phone size={18} /></View>
              <Input className="auth-field__input" type="number" maxlength={11} value={phone} onInput={(e) => setPhone(e.detail.value)} placeholder="请输入手机号" />
            </View>
          </View>

          {activeTab === 'password' ? (
            <View className="auth-field">
              <View className="auth-field__label-row">
                <Text className="auth-field__label">登录密码</Text>
                <Text className="auth-field__link" onClick={() => Taro.navigateTo({ url: '/pages/auth/forgot-password/index' })}>忘记密码</Text>
              </View>
              <View className="auth-field__box">
                <View className="auth-field__icon"><Lock size={18} /></View>
                <Input className="auth-field__input" password value={password} onInput={(e) => setPassword(e.detail.value)} placeholder="请输入 6-20 位密码" />
                <View className="auth-field__trail"><Eye size={16} /></View>
              </View>
            </View>
          ) : (
            <View className="auth-mini-switch">
              <View className="auth-mini-switch__head">
                <Text className="auth-mini-switch__title">短信验证码登录</Text>
                <Text className="auth-mini-switch__note">适合快速登录</Text>
              </View>

              <View className="auth-field auth-field--compact">
                <View className="auth-field__label-row">
                  <Text className="auth-field__label">手机号</Text>
                </View>
                <View className="auth-field__box">
                  <View className="auth-field__icon"><Phone size={18} /></View>
                  <Input className="auth-field__input" type="number" maxlength={11} value={phone} onInput={(e) => setPhone(e.detail.value)} placeholder="请输入手机号" />
                </View>
              </View>

              <View className="auth-field">
                <View className="auth-field__label-row">
                  <Text className="auth-field__label">短信验证码</Text>
                </View>
                <View className="auth-code-row">
                  <View className="auth-field__box auth-field__box--grow auth-field__box--code">
                    <View className="auth-field__icon"><Message size={18} /></View>
                    <Input className="auth-field__input" type="number" maxlength={6} value={smsCode} onInput={(e) => setSmsCode(e.detail.value)} placeholder="请输入短信验证码" />
                  </View>
                  <Button className="auth-code-row__button auth-code-row__button--ghost" onClick={() => Taro.showToast({ title: '短信登录验证码待接入', icon: 'none' })}>
                    获取验证码
                  </Button>
                </View>
              </View>
            </View>
          )}

          <View className="auth-check">
            <View className="auth-check__dot"><Text>✓</Text></View>
            <Text className="auth-check__text">
              我已阅读并同意
              <Text className="auth-check__link" onClick={() => showProtocol('用户协议', userAgreement)}>《用户协议》</Text>
              <Text className="auth-check__link" onClick={() => showProtocol('隐私政策', privacyPolicy)}>《隐私政策》</Text>
            </Text>
          </View>

          <Button className="auth-button auth-button--primary" loading={submitting} onClick={activeTab === 'password' ? handlePasswordSubmit : handleSmsSubmit}>
            立即登录
          </Button>

          <View className="auth-links auth-links--center">
            <Text className="auth-links__text">还没有账号？</Text>
            <Text className="auth-links__item" onClick={() => Taro.navigateTo({ url: '/pages/auth/register/index' })}>去注册</Text>
            <Text className="auth-links__split">|</Text>
            <Text className="auth-links__item" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>游客先逛逛</Text>
          </View>

          <View className="auth-help-divider">
            <Text className="auth-help-divider__text">更多帮助</Text>
          </View>

          <View className="auth-alt-actions">
            <View className="auth-alt-action">
              <View className="auth-alt-action__icon"><Message size={18} /></View>
              <Text className="auth-alt-action__text">短信验证说明</Text>
            </View>
            <View className="auth-alt-action">
              <View className="auth-alt-action__icon"><Service size={18} /></View>
              <Text className="auth-alt-action__text">联系客服</Text>
            </View>
          </View>
        </View>

        <View className="auth-safe-card">
          <Text className="auth-safe-card__title">登录安全提示</Text>
          <Text className="auth-safe-card__desc">账号密码登录更适合已注册会员，短信验证码登录更适合快捷进入；两者共用同一会员体系，后续也能继续关联更多平台身份。</Text>
        </View>
      </View>
    </View>
  );
}
