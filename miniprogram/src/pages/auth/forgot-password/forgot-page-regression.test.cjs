const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const source = fs.readFileSync(path.join(__dirname, 'index.tsx'), 'utf8');
const styles = fs.readFileSync(path.join(__dirname, '..', 'login', 'index.scss'), 'utf8');

assert(source.includes("@nutui/icons-react-taro"), 'expected forgot-password page to use real icon components');
assert(source.includes('auth-page--forgot'), 'expected forgot-password page to use its own scoped class');
assert(source.includes('auth-card--forgot'), 'expected forgot-password page to use its own scoped card class');
assert(source.includes('placeholder="请输入手机号"'), 'expected phone placeholder to match the design');
assert(source.includes('placeholder="请输入 6 位验证码"'), 'expected code placeholder to match the design');
assert(source.includes('placeholder="请输入 6-20 位密码"'), 'expected password placeholder to match the design');
assert(source.includes('placeholder="请再次输入新密码"'), 'expected confirmation placeholder to match the design');
assert(source.includes('<Phone size={18} />'), 'expected phone icon to be rendered');
assert(source.includes('<Message size={18} />'), 'expected message icon to be rendered');
assert(source.includes('<Lock size={18} />'), 'expected lock icon to be rendered');
assert(source.includes('<Shield size={18} />'), 'expected shield icon to be rendered');
assert(source.includes('auth-safe-card'), 'expected forgot-password page to include a safe card');
assert(styles.includes('.auth-page--forgot .auth-field__input input'), 'expected forgot-password page to style the inner Taro input element');

console.log('forgot password page regression tests passed');
