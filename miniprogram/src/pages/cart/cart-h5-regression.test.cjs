const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const page = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const style = readFileSync(join(__dirname, 'index.scss'), 'utf8');

const hasAll = (source, parts, message) => {
  assert.ok(parts.every((part) => source.includes(part)), message);
};

hasAll(page, [
  "cart-page cart-page--filled ${isH5() ? 'cart-page--h5' : ''}",
  'className="cart-store"',
  'className="cart-goods-item__image"',
  'className="cart-bar"',
  'className="cart-recommend"',
  'fetchRecommendGoods',
], 'cart page should keep the main H5 layout shell');
assert.doesNotMatch(page, /<GoodsCard/, 'h5 cart recommendation should use a cart-specific card instead of the generic GoodsCard');
hasAll(page, [
  'className="cart-goods-item__price-symbol"',
  'className="cart-bar__price-symbol"',
  'className="cart-recommend__price-symbol"',
  'className="cart-recommend__card"',
  'className="cart-recommend__thumb"',
  'className="cart-recommend__img"',
  'className="cart-recommend__price-row"',
  'className="cart-recommend__add-btn"',
], 'cart page should render the design-specific price symbols and recommendation card structure');

hasAll(style, [
  '.cart-page--h5 {',
  'background: $warm-bg-page;',
  'padding-bottom: calc(172PX + env(safe-area-inset-bottom));',
  '.cart-page--h5 .cart-list {',
  'padding: 0;',
  '.cart-page--h5 .cart-store {',
  'margin: 10PX 16PX 0;',
  'border-radius: 16PX;',
  'padding: 14PX;',
  'box-shadow: 0 2PX 8PX rgba(200, 140, 110, 0.08);',
  '.cart-page--h5 .cart-store__header {',
  'gap: 8PX;',
  'margin-bottom: 12PX;',
  '.cart-page--h5 .cart-store__title {',
  'font-size: 14PX;',
], 'h5 store shell should match the design card spacing');

hasAll(style, [
  '.cart-page--h5 .cart-goods-item {',
  'gap: 12PX;',
  'padding: 10PX 0;',
  '.cart-page--h5 .cart-goods-item__image {',
  'width: 90PX;',
  'height: 90PX;',
  'border-radius: 10PX;',
  '.cart-page--h5 .cart-goods-item__title {',
  'line-height: 1.3;',
  '.cart-page--h5 .cart-goods-item__spec {',
  'margin-top: 4PX;',
  'padding: 2PX 8PX;',
  'border-radius: 4PX;',
  '.cart-page--h5 .cart-goods-item__price {',
  '.cart-page--h5 .cart-goods-item__price-symbol {',
  '.cart-page--h5 .qty-stepper {',
  'border-radius: 16PX;',
  '.cart-page--h5 .qty-stepper__btn {',
  'width: 26PX;',
  'height: 26PX;',
  '.cart-page--h5 .qty-stepper__btn-text {',
  'font-size: 14PX;',
  '.cart-page--h5 .qty-stepper__value {',
  'width: 30PX;',
  '.cart-page--h5 .cart-goods-item__image .taro-img {',
  '.cart-page--h5 .cart-goods-item__image img {',
  'object-fit: cover;',
], 'h5 goods row should match the design scale');

hasAll(style, [
  '.cart-page--h5 .cart-bottom-spacer {',
  'height: 172PX;',
  '.cart-page--h5 .cart-bar {',
  'bottom: calc(82PX + env(safe-area-inset-bottom));',
  'width: 100%;',
  'max-width: 430PX;',
  'min-height: 56PX;',
  'padding: 0 16PX;',
  'border-radius: 0;',
  'box-shadow: 0 -2PX 10PX rgba(200, 140, 110, 0.08);',
  '.cart-page--h5 .cart-bar__left {',
  '.cart-page--h5 .cart-bar .cart-check-circle {',
  'width: 18PX;',
  'height: 18PX;',
  '.cart-page--h5 .cart-bar__all-text {',
  '.cart-page--h5 .cart-bar__total {',
  'margin-right: 12PX;',
  '.cart-page--h5 .cart-bar__total-label {',
  'font-size: 12PX;',
  '.cart-page--h5 .cart-bar__total-price {',
  'font-size: 18PX;',
  '.cart-page--h5 .cart-bar__price-symbol {',
  '.cart-page--h5 .cart-bar__settle {',
  'padding: 10PX 24PX;',
  '.cart-page--h5 .cart-bar__settle-text {',
  'font-size: 14PX;',
], 'h5 bottom settle bar should match the design scale');

hasAll(style, [
  '.cart-page--h5 .cart-recommend__title {',
  'font-size: 14PX;',
  '.cart-page--h5 .cart-recommend__grid {',
  'grid-template-columns: repeat(2, minmax(0, 1fr));',
  '.cart-page--h5 .cart-recommend__card {',
  '.cart-page--h5 .cart-recommend__thumb {',
  'height: 120PX;',
  '.cart-page--h5 .cart-recommend__body {',
  'padding: 10PX 12PX 12PX;',
  '.cart-page--h5 .cart-recommend__name {',
  'font-weight: 600;',
  'line-height: 1.4;',
  'min-height: 36PX;',
  '.cart-page--h5 .cart-recommend__price {',
  'font-size: 18PX;',
  '.cart-page--h5 .cart-recommend__price-symbol {',
  '.cart-page--h5 .cart-recommend__add-btn {',
  'width: 28PX;',
  'height: 28PX;',
  '.cart-page--h5 .cart-recommend__thumb .taro-img {',
  '.cart-page--h5 .cart-recommend__thumb img {',
  'object-fit: cover;',
], 'h5 recommend section should match the design card scale');

console.log('cart h5 design ok');

