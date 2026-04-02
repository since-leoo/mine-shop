import { Button, Input, Text, View } from '@tarojs/components';
import { Lock, Message, Phone, Service, Shield } from '@nutui/icons-react-taro';
import Taro from '@tarojs/taro';
import { useEffect, useState } from 'react';
import { isH5 } from '../../../common/platform';
import { resetPasswordByPhone, sendVerificationCode } from '../../../services/auth/h5Auth';
import { getCodeButtonText, isValidCode, isValidPassword, isValidPhone, passwordsMatch } from '../shared/auth-form';
import '../login/index.scss';

function UnsupportedForgotEntry() {
  return (
    <View className="auth-page auth-page--unsupported">
      <View className="auth-plain-card">
        <Text className="auth-plain-card__title">请在 H5 端找回密码</Text>
        <Text className="auth-plain-card__desc">小程序端仍沿用微信授权和资料完善流程，这里只处理 H5 账号密码体系。</Text>
        <Button className="auth-button auth-button--primary" onClick={() => Taro.switchTab({ url: '/pages/usercenter/index' })}>
          返回个人中心
        </Button>
      </View>
    </View>
  );
}

export default function ForgotPasswordPage() {
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [countdown, setCountdown] = useState(0);

  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setTimeout(() => setCountdown((value) => value - 1), 1000);
    return () => clearTimeout(timer);
  }, [countdown]);

  if (!isH5()) {
    return <UnsupportedForgotEntry />;
  }

  const handleSendCode = async () => {
    if (!isValidPhone(phone)) {
      Taro.showToast({ title: '请输入正确手机号', icon: 'none' });
      return;
    }

    try {
      const result: any = await sendVerificationCode(phone, 'forgot_password');
      Taro.showModal({ title: '开发验证码', content: `当前验证码：${result?.code || '已发送'}`, showCancel: false });
      setCountdown(60);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '验证码发送失败', icon: 'none' });
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
      await resetPasswordByPhone({ phone, code, password, passwordConfirmation });
      Taro.showToast({ title: '密码已重置', icon: 'success' });
      setTimeout(() => {
        Taro.redirectTo({ url: '/pages/auth/login/index' });
      }, 300);
    } catch (error: any) {
      Taro.showToast({ title: error?.msg || '重置失败', icon: 'none' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View className="auth-page auth-page--h5 auth-page--forgot">
      <View className="auth-h5-shell">
        <View className="auth-hero auth-hero--forgot">
          <View className="auth-hero__brand">
            <View className="auth-hero__mark">
              <Text className="auth-hero__mark-text">R</Text>
            </View>
            <Text className="auth-hero__title">先校验手机{`\n`}再重置登录密码</Text>
            <Text className="auth-hero__desc">把“手机验证 + 重设密码”的路径做成更清晰、更可信赖的 H5 表单流程。</Text>
          </View>
        </View>

        <View className="auth-card auth-card--h5 auth-card--forgot">
          <View className="auth-card__head">
            <View className="auth-card__eyebrow auth-card__eyebrow--icon">
              <View className="auth-card__eyebrow-icon"><Shield size={14} /></View>
              <Text className="auth-card__eyebrow-text">密码重置</Text>
            </View>
            <Text className="auth-card__title">重设你的登录密码</Text>
            <Text className="auth-card__desc">先完成手机验证，再设置新的登录密码。开发环境会直接弹出验证码，方便联调，也保持和设计稿一致的节奏感。</Text>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">手机号</Text>
              <Text className="auth-field__hint">用于验证身份</Text>
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
              <View className="auth-field__box auth-field__box--grow auth-field__box--code">
                <View className="auth-field__icon"><Message size={18} /></View>
                <Input className="auth-field__input" type="number" maxlength={6} value={code} onInput={(e) => setCode(e.detail.value)} placeholder="请输入 6 位验证码" />
              </View>
              <Button className="auth-code-row__button auth-code-row__button--ghost" disabled={countdown > 0} onClick={handleSendCode}>
                {getCodeButtonText(countdown)}
              </Button>
            </View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">新密码</Text>
              <Text className="auth-field__hint">建议字母和数字组合</Text>
            </View>
            <View className="auth-field__box">
              <View className="auth-field__icon"><Lock size={18} /></View>
              <Input className="auth-field__input" password value={password} onInput={(e) => setPassword(e.detail.value)} placeholder="请输入 6-20 位密码" />
            </View>
          </View>

          <View className="auth-field">
            <View className="auth-field__label-row">
              <Text className="auth-field__label">确认新密码</Text>
              <Text className="auth-field__hint">请再次输入新密码</Text>
            </View>
            <View className="auth-field__box">
              <View className="auth-field__icon"><Shield size={18} /></View>
              <Input className="auth-field__input" password value={passwordConfirmation} onInput={(e) => setPasswordConfirmation(e.detail.value)} placeholder="请再次输入新密码" />
            </View>
          </View>

          <Button className="auth-button auth-button--primary" loading={submitting} onClick={handleSubmit}>
            重置密码
          </Button>

          <View className="auth-links auth-links--center">
            <Text className="auth-links__text">想起密码了？</Text>
            <Text className="auth-links__item" onClick={() => Taro.redirectTo({ url: '/pages/auth/login/index' })}>返回登录</Text>
          </View>

          <View className="auth-help-divider">
            <Text className="auth-help-divider__text">更多帮助</Text>
          </View>

          <View className="auth-alt-actions">
            <View className="auth-alt-action">
              <View className="auth-alt-action__icon"><Message size={18} /></View>
              <Text className="auth-alt-action__text">验证码说明</Text>
            </View>
            <View className="auth-alt-action">
              <View className="auth-alt-action__icon"><Service size={18} /></View>
              <Text className="auth-alt-action__text">联系客服</Text>
            </View>
          </View>
        </View>

        <View className="auth-safe-card">
          <Text className="auth-safe-card__title">安全提示</Text>
          <Text className="auth-safe-card__desc">重置密码后，原密码将立即失效。建议设置字母与数字组合的新密码，并妥善保管验证码和账号信息。</Text>
        </View>
      </View>
    </View>
  );
}
