const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const orderDetailPage = readFileSync(join(__dirname, 'order-detail/index.tsx'), 'utf8');
const orderListPage = readFileSync(join(__dirname, 'order-list/index.tsx'), 'utf8');
const goodsDetailStyle = readFileSync(join(__dirname, '../goods/details/index.scss'), 'utf8');

assert.ok(!orderDetailPage.includes('???'), 'order detail should not contain placeholder question marks');
assert.ok(!orderDetailPage.includes("text.includes('?')"), 'order detail should not treat any question mark as invalid text');
assert.match(orderDetailPage, /canReapplyAfterSale/, 'order detail should support reapplying after rejected after-sale');
assert.match(orderDetailPage, /rejectReason/, 'order detail should check reject reason for reapply');
assert.match(orderListPage, /canReapplyAfterSale/, 'order list should support reapplying after rejected after-sale');
assert.match(orderListPage, /rejectReason/, 'order list should check reject reason for reapply');
assert.ok(!orderListPage.includes(String.raw`\u`), 'order list should not render raw unicode text');
assert.ok(!goodsDetailStyle.includes('?????'), 'goods detail styles should not contain garbled comments');
assert.match(orderDetailPage, new RegExp('\u5df2\u9000\u6b3e'), 'order detail should render refunded status copy');
assert.match(orderListPage, new RegExp('\u5df2\u9000\u6b3e'), 'order list should support refunded status copy');
assert.match(orderDetailPage, new RegExp('\u67e5\u770b\u552e\u540e'), 'order detail should allow viewing after-sale for refunded orders');
assert.match(orderDetailPage, new RegExp('\u518d\u6b21\u8d2d\u4e70'), 'order detail should allow repurchase for refunded orders');

console.log('order ui copy ok');
