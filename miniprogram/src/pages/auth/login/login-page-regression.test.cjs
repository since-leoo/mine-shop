const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const source = fs.readFileSync(path.join(__dirname, 'index.tsx'), 'utf8');
const styles = fs.readFileSync(path.join(__dirname, 'index.scss'), 'utf8');

assert(source.includes('auth-help-divider'), 'expected login page to include the draft helper divider');
assert(source.includes('auth-alt-actions'), 'expected login page to include draft alternate actions');
assert(source.includes('auth-safe-card'), 'expected login page to include the draft safety card');
assert(source.includes("useState<LoginTab>('password')"), 'expected password tab to stay as the default tab');
assert(source.includes("@nutui/icons-react-taro"), 'expected login page to use real icon components');
assert(source.includes('placeholder="请输入手机号"'), 'expected phone placeholder to match the design');
assert(source.includes('placeholder="请输入 6-20 位密码"'), 'expected password placeholder to match the design');
assert(source.includes('placeholder="请输入短信验证码"'), 'expected sms code placeholder to match the design');
assert(source.includes('<Phone size={18} />'), 'expected phone icon to be rendered');
assert(source.includes('<Lock size={18} />'), 'expected lock icon to be rendered');
assert(source.includes('<Message size={18} />'), 'expected message icon to be rendered');
assert(source.includes('<Service size={18} />'), 'expected service icon to be rendered');
assert(!source.includes('>A<'), 'expected placeholder letter icons to be removed');
assert(!source.includes('>P<'), 'expected placeholder letter icons to be removed');
assert(!source.includes('>C<'), 'expected placeholder letter icons to be removed');
assert(!source.includes('>[]<'), 'expected bracket placeholder icon to be removed');
assert(styles.includes('.auth-page--login .auth-field__input input'), 'expected login page to style the inner Taro input element');
assert(styles.includes('height: 100%;'), 'expected inner input to stretch to field height');
assert(styles.includes('display: flex;'), 'expected login input wrapper rules to support vertical centering');

console.log('login page regression tests passed');
