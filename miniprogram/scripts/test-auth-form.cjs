const assert = require('node:assert/strict');
const {
  isValidPhone,
  isValidCode,
  isValidPassword,
  passwordsMatch,
  getCodeButtonText,
} = require('../src/pages/auth/shared/auth-form.js');

assert.equal(isValidPhone('13800138000'), true);
assert.equal(isValidPhone('12345678901'), false);
assert.equal(isValidCode('123456'), true);
assert.equal(isValidCode('12345'), false);
assert.equal(isValidPassword('abc123'), true);
assert.equal(isValidPassword('12345'), false);
assert.equal(passwordsMatch('secret1', 'secret1'), true);
assert.equal(passwordsMatch('secret1', 'secret2'), false);
assert.equal(getCodeButtonText(0), '获取验证码');
assert.equal(getCodeButtonText(18), '18s');

console.log('auth-form tests passed');
