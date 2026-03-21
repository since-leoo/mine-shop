const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const detailPage = readFileSync(join(__dirname, '..', 'details', 'index.tsx'), 'utf8');
const detailStyle = readFileSync(join(__dirname, '..', 'details', 'index.scss'), 'utf8');

assert.match(detailPage, /goods-comments__spec/, 'goods detail comments should render sku spec');
assert.match(detailPage, /goods-comments__reply/, 'goods detail comments should render seller reply block');
assert.match(detailStyle, /&__spec/, 'goods detail stylesheet should define spec text style');
assert.match(detailStyle, /&__reply/, 'goods detail stylesheet should define seller reply style');

console.log('goods detail comments preview ok');
