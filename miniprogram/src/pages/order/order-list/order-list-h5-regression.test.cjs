const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const page = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const style = readFileSync(join(__dirname, 'index.scss'), 'utf8');

assert.match(page, /className=\{`order-list-page \$\{isH5\(\) \? 'order-list-page--h5' : ''\}`\}/, 'order list should keep a dedicated H5 shell');
assert.match(page, /className=\"order-tabs\"/, 'order list should render tab bar');
assert.match(page, /className=\"order-card\"/, 'order list should render order cards');

assert.match(style, /\.order-list-page--h5 \{[\s\S]*background: \$warm-bg-page;/, 'h5 order list should use plain page background like the design');
assert.match(style, /\.order-list-page--h5 \.order-list-page__tabs \{[\s\S]*margin: 0;[\s\S]*border-radius: 0;[\s\S]*background: #fff;[\s\S]*box-shadow: 0 1px 4px rgba\(200, 140, 110, 0\.06\);/, 'h5 order tabs shell should match the flat design tab bar');
assert.match(style, /\.order-list-page--h5 \.order-tabs__item \{[\s\S]*height: 48px;/, 'h5 order tabs should match the design tab height');
assert.match(style, /\.order-list-page--h5 \.order-tabs__text \{[\s\S]*font-size: 14px;/, 'h5 order tab text should match the design font size');
assert.match(style, /\.order-list-page--h5 \.order-list-page__content \{[\s\S]*padding: 0 0 28px;/, 'h5 order list content should remove extra side padding');
assert.match(style, /\.order-list-page--h5 \.order-card \{[\s\S]*margin: 10px 16px;[\s\S]*border-radius: 16px;[\s\S]*box-shadow: 0 2px 8px rgba\(200, 140, 110, 0\.08\);/, 'h5 order cards should match the design card spacing and shadow');
assert.match(style, /\.order-list-page--h5 \.order-card__header,\s*[\s\S]*padding-left: 16px;[\s\S]*padding-right: 16px;/, 'h5 order card inner paddings should match the design');
assert.match(style, /\.order-list-page--h5 \.order-card__goods \{[\s\S]*gap: 12px;/, 'h5 order goods rows should match the design spacing');
assert.match(style, /\.order-list-page--h5 \.order-card__goods-img \{[\s\S]*width: 76px;[\s\S]*height: 76px;[\s\S]*border-radius: 10px;/, 'h5 order goods image should match the design size');
assert.match(style, /\.order-list-page--h5 \.order-card__no,\s*[\s\S]*font-size: 12px;/, 'h5 order number and status text should be smaller like the design');
assert.match(style, /\.order-list-page--h5 \.order-card__goods-title \{[\s\S]*font-size: 13px;/, 'h5 order goods title should match the design font size');
assert.match(style, /\.order-list-page--h5 \.order-card__total-price \{[\s\S]*font-size: 15px;/, 'h5 order total price should match the design font size');
assert.match(style, /\.order-list-page--h5 \.order-card__action-btn \{[\s\S]*height: 32px;[\s\S]*padding: 0 16px;[\s\S]*border-radius: 20px;/, 'h5 order action buttons should match the design size');
assert.match(style, /\.order-list-page--h5 \.order-card__action-text,\s*[\s\S]*font-size: 12px;/, 'h5 order action text should match the design size');

console.log('order list h5 design ok');
