const assert = require('node:assert/strict');
const {
  clampQuantity,
  centsToYuanInput,
  normalizeAmountInput,
  computeMaxAmountByQuantity,
  clampAmountByInput,
  buildAmountInput,
  resolveAmountInput,
} = require('./helpers');

assert.equal(clampQuantity(0, 3), 1);
assert.equal(clampQuantity(5, 3), 3);
assert.equal(clampQuantity(2, 3), 2);

assert.equal(centsToYuanInput(1234), '12.34');
assert.equal(centsToYuanInput(0), '0.00');

assert.equal(normalizeAmountInput('12.3456'), '12.34');
assert.equal(normalizeAmountInput('abc1.2.3'), '1.23');
assert.equal(normalizeAmountInput('.5'), '0.5');

assert.equal(computeMaxAmountByQuantity(999, 3, 1), 333);
assert.equal(computeMaxAmountByQuantity(999, 3, 2), 666);
assert.equal(computeMaxAmountByQuantity(1000, 3, 2), 666);
assert.equal(computeMaxAmountByQuantity(1000, 3, 3), 1000);

assert.equal(clampAmountByInput('12.34', 2000), 1234);
assert.equal(clampAmountByInput('99.99', 2000), 2000);
assert.equal(clampAmountByInput('', 2000), 0);

assert.equal(buildAmountInput(666), '6.66');
assert.equal(buildAmountInput(0), '0.00');

assert.equal(resolveAmountInput('1', 666), '1.00');
assert.equal(resolveAmountInput('9.99', 666), '6.66');
assert.equal(resolveAmountInput('', 666), '0.00');

console.log('helpers ok');
