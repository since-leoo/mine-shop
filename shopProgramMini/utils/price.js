/**
 * 金额格式化工具函数
 * 后端以"分"为单位返回金额，前端展示时需要转换为"元"
 */

/**
 * 将分转换为元的字符串格式
 * @param {number|null|undefined} cents 金额（分）
 * @returns {string} 格式化后的金额字符串，如 '99.90'
 */
function formatPrice(cents) {
  if (cents === null || cents === undefined) return '0.00';
  return (Number(cents) / 100).toFixed(2);
}

/**
 * 将分转换为带¥符号的元字符串格式
 * @param {number|null|undefined} cents 金额（分）
 * @returns {string} 格式化后的金额字符串，如 '¥99.90'
 */
function formatPriceYuan(cents) {
  return '¥' + formatPrice(cents);
}

module.exports = {
  formatPrice,
  formatPriceYuan,
};
