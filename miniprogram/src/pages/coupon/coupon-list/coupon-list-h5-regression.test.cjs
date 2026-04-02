const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const page = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const style = readFileSync(join(__dirname, 'index.scss'), 'utf8');

assert.match(page, /className=\{`coupon-page coupon-list-page \$\{isH5\(\) \? 'coupon-list-page--h5' : ''\}`\}/, 'coupon list should keep a dedicated H5 shell');
assert.match(page, /className=\"coupon-list-page__header-panel\"/, 'coupon list should render the header panel');
assert.match(page, /className=\"coupon-ticket/, 'coupon list should render coupon tickets');

assert.match(style, /\.coupon-list-page--h5 \{[\s\S]*background: \$warm-bg-page;/, 'h5 coupon list should use the plain design page background');
assert.match(style, /\.coupon-list-page--h5 \.coupon-page__header,[\s\S]*padding-left: 0;[\s\S]*padding-right: 0;/, 'h5 coupon header should remove extra side padding');
assert.match(style, /\.coupon-list-page--h5 \.coupon-list-page__header-panel \{[\s\S]*margin: 10px 16px 0;[\s\S]*border-radius: 26px;[\s\S]*box-shadow: 0 4px 16px rgba\(200, 140, 110, 0\.12\);/, 'h5 coupon header panel should match the design spacing and shadow');
assert.match(style, /\.coupon-list-page--h5 \.coupon-page__title \{[\s\S]*font-size: 26px;/, 'h5 coupon title should match the design font size');
assert.match(style, /\.coupon-list-page--h5 \.coupon-page__subtitle \{[\s\S]*font-size: 13px;/, 'h5 coupon subtitle should match the design font size');
assert.match(style, /\.coupon-list-page--h5 \.coupon-page__tab-text \{[\s\S]*font-size: 12px;/, 'h5 coupon tabs should use the smaller design font size');
assert.match(style, /\.coupon-list-page--h5 \.coupon-ticket \{[\s\S]*margin: 0 16px 12px;[\s\S]*border-radius: 22px;[\s\S]*box-shadow: 0 2px 8px rgba\(200, 140, 110, 0\.08\);/, 'h5 coupon ticket should match the design card spacing and shadow');
assert.match(style, /\.coupon-list-page--h5 \.coupon-ticket__title \{[\s\S]*font-size: 15px;/, 'h5 coupon title text should match the design');
assert.match(style, /\.coupon-list-page--h5 \.coupon-ticket__value \{[\s\S]*font-size: 40px;/, 'h5 coupon amount should match the design scale');
assert.match(style, /\.coupon-list-page--h5 \.coupon-ticket__value--discount \{[\s\S]*font-size: 34px;/, 'h5 coupon discount value should match the design scale');
assert.match(style, /\.coupon-list-page--h5 \.coupon-ticket__condition,[\s\S]*font-size: 11px;/, 'h5 coupon condition/time/action copy should match the design size');
assert.match(style, /\.coupon-list-page--h5 \.coupon-ticket__status,[\s\S]*font-size: 10px;/, 'h5 coupon status and tag should match the design badge size');

console.log('coupon list h5 design ok');
