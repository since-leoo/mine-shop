exports.isValidPhone = function isValidPhone(value) {
  return /^1[3-9]\d{9}$/.test(String(value || '').trim());
};

exports.isValidCode = function isValidCode(value) {
  return /^\d{6}$/.test(String(value || '').trim());
};

exports.isValidPassword = function isValidPassword(value) {
  return String(value || '').length >= 6;
};

exports.passwordsMatch = function passwordsMatch(password, confirmation) {
  return String(password || '') === String(confirmation || '');
};

exports.getCodeButtonText = function getCodeButtonText(countdown) {
  return countdown > 0 ? `${countdown}s` : '获取验证码';
};
