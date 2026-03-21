import { Button, Input, Text, View } from '@tarojs/components';
import { Lock, Message, Phone, Shield } from '@nutui/icons-react-taro';
import Taro from '@tarojs/taro';
import { useEffect, useState } from 'react';
import { persistAuth } from '../../../common/auth';
import { navigateAfterLogin } from '../../../common/auth-guard';
import { isH5 } from '../../../common/platform';
import { fetchRegisterProtocols, registerByPhone, sendVerificationCode } from '../../../services/auth/h5Auth';
import { getCodeButtonText, isValidCode, isValidPassword, isValidPhone, passwordsMatch } from '../shared/auth-form';
import '../login/index.scss';

function UnsupportedRegisterEntry() {
  return (
    <View className="auth-page auth-page--unsupported">
      <View className="auth-plain-card">
        <Text className="auth-plain-card__title">请在 H5 端注册</Text>
        <Text className="auth-plain-card__desc">小程序端仍保留既有授权和会员体系，这里只承载 H5 显式注册入口。</Text>
        <Button className="auth-button auth-button--primary" onClick={() => Taro.switchTab({ url: '/pages/home/index' })}>
          返回首页
        </Button>
      </View>
    </View>
  );
}

export default function RegisterPage() {
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [userAgreement, setUserAgreement] = useState('');
  const [privacyPolicy, setPrivacyPolicy] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [sending, setSending] = useState(false);
  const [countdown, setCountdown] = useState(0);

  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setTimeout(() => setCountdown((value) => value - 1), 1000);
    return () => clearTimeout(timer);
  }, [countdown]);

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
    return <UnsupportedRegisterEntry />;
  }

  const showProtocol = (title: string, content: string) => {
    Taro.showModal({
      title,
      content: content || '当前暂未配置内容',
      showCancel: false,
      confirmText: '我知道了',
    });
  };

  const handleSendCode = async () => {
    if (!isValidPhone(phone)) {
      Taro.showToast({ title: '请输入正确手机号', icon: 'none' });
      return;
    }

    setSending(true);
    try {
      const result: any = await sendVerificationCode(phone, 'register');
      Taro.showModal({ title: '开发验证码', content: `当前验证码：${result?.code || '已发送'}`, showCancel: false });
      setCountdown(60);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '验证码发送失败', icon: 'none' });
    } finally {
      setSending(false);
    }
  };

  const handleSubmit = async () => {
    if (!isValidPhone(phone)) {
      Taro.showToast({ title: '请输入正确手机号', icon: 'none' });
      return;
    }
    if (!isValidCode(code)) {
      Taro.showToast({ title: '请输入 6 位验证码', icon: 'none' });
      return;
    }
    if (!isValidPassword(password)) {
      Taro.showToast({ title: '密码至少 6 位', icon: 'none' });
      return;
    }
    if (!passwordsMatch(password, passwordConfirmation)) {
      Taro.showToast({ title: '两次密码输入不一致', icon: 'none' });
      return;
    }

    setSubmitting(true);
    try {
      const response = await registerByPhone({ phone, code, password, passwordConfirmation });
      persistAuth(response);
      Taro.showToast({ title: '注册成功', icon: 'success' });
      setTimeout(() => navigateAfterLogin('/pages/usercenter/index'), 200);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '注册失败', icon: 'none' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View className="auth-page auth-page--h5">
      <View className="auth-h5-shell">
        <View className="auth-hero auth-hero--register">
          <View className="auth-hero__badge">H5 新会员注册</View>
          <Text className="auth-hero__title">创建你的{`\n`}MineMall 新账号</Text>
          <Text className="auth-hero__desc">沿用设计稿里的暖色主题，但把“手机号验证 + 设置密码”的路径做得更直接，方便 H5 新用户快速入会。</Text>
        </View>

        <View className="auth-card auth-card--h5 auth-register-form">
          <View className="auth-card__head">
            <Text className="auth-card__eyebrow">手机号注册</Text>
            <Text className="auth-card__title">手机号快速创建账号</Text>
            <Text className="auth-card__desc">注册完成后，可用账号密码登录，也可在后端补齐后接入短信验证码登录。</Text>
          </View>

          <View className="auth-steps">
            <View className="auth-step is-active">1. 验证手机</View>
            <View className="auth-step is-active">2. 设置密码</View>
            <View className="auth-step">3. 完成注册</View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">手机号</Text>
              <Text className="auth-field__hint">用于登录和找回密码</Text>
            </View>
            <View className="auth-field__box">
              <View className="auth-field__icon"><Phone size={18} /></View>
              <Input className="auth-field__input" type="number" maxlength={11} value={phone} onInput={(e) => setPhone(e.detail.value)} placeholder="请输入手机号" />
            </View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">短信验证码</Text>
              <Text className="auth-field__hint">开发环境会直接回显验证码</Text>
            </View>
            <View className="auth-code-row">
              <View className="auth-field__box auth-field__box--grow">
                <View className="auth-field__icon"><Message size={18} /></View>
                <Input className="auth-field__input" type="number" maxlength={6} value={code} onInput={(e) => setCode(e.detail.value)} placeholder="请输入 6 位验证码" />
              </View>
              <Button className="auth-code-row__button" disabled={sending || countdown > 0} onClick={handleSendCode}>
                {getCodeButtonText(countdown)}
              </Button>
            </View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">设置密码</Text>
              <Text className="auth-field__hint">建议字母和数字组合</Text>
            </View>
            <View className="auth-field__box">
              <View className="auth-field__icon"><Lock size={18} /></View>
              <Input className="auth-field__input" password value={password} onInput={(e) => setPassword(e.detail.value)} placeholder="请输入 6-20 位登录密码" />
            </View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">确认密码</Text>
              <Text className="auth-field__hint">请再次输入</Text>
            </View>
            <View className="auth-field__box">
              <View className="auth-field__icon"><Shield size={18} /></View>
              <Input className="auth-field__input" password value={passwordConfirmation} onInput={(e) => setPasswordConfirmation(e.detail.value)} placeholder="请再次输入登录密码" />
            </View>
          </View>

          <View className="auth-tip-box">
            <Text className="auth-tip-box__icon">i</Text>
            <Text className="auth-tip-box__text">注册成功后默认进入 H5 会员中心，后续如果接入小程序绑定，可以把当前手机号账号关联到同一会员主体。</Text>
          </View>

          <View className="auth-check">
            <View className="auth-check__dot"><Text>✓</Text></View>
            <Text className="auth-check__text">
              注册即表示同意
              <Text className="auth-check__link" onClick={() => showProtocol('用户协议', userAgreement)}>《用户协议》</Text>
              <Text className="auth-check__link" onClick={() => showProtocol('隐私政策', privacyPolicy)}>《隐私政策》</Text>
              与短信验证规则
            </Text>
          </View>

          <Button className="auth-button auth-button--primary" loading={submitting} onClick={handleSubmit}>
            注册并登录
          </Button>

          <View className="auth-links auth-links--center">
            <Text className="auth-links__text">已经有账号？</Text>
            <Text className="auth-links__item" onClick={() => Taro.redirectTo({ url: '/pages/auth/login/index' })}>返回登录</Text>
          </View>
        </View>
      </View>
    </View>
  );
}

