const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const detailPage = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const detailStyle = readFileSync(join(__dirname, 'index.scss'), 'utf8');

assert.match(detailPage, /goods-bottom-bar__badge/, 'goods detail bottom bar should render cart badge element');
assert.match(detailPage, /cartBadgeCount/, 'goods detail page should track cart badge count');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-placeholder \{[\s\S]*height: calc\(136px \+ env\(safe-area-inset-bottom\)\);/, 'h5 bottom placeholder should reserve footer height');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar \{[\s\S]*min-height: 112px;[\s\S]*padding: 14px 24px calc\(env\(safe-area-inset-bottom\) \+ 14px\);/, 'h5 bottom bar should keep current outer height');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar__icons \{[\s\S]*gap: 36px;[\s\S]*margin-right: 24px;/, 'h5 icon spacing should match enlarged icon group');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar__icon-item \{[\s\S]*gap: 10px;[\s\S]*min-width: 68px;/, 'h5 icon item should have larger footprint');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar__icon-emoji \{[\s\S]*width: 56px;[\s\S]*height: 56px;/, 'h5 icon size should be enlarged');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar__icon-text \{[\s\S]*font-size: 16px;/, 'h5 icon text should be enlarged');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar__btns \{[\s\S]*height: 72px;[\s\S]*border-radius: 36px;/, 'h5 action frame should be enlarged');
assert.match(detailStyle, /\.goods-detail-page--h5 \.goods-bottom-bar__btn-text \{[\s\S]*font-size: 19px;/, 'h5 action text should be enlarged');

console.log('goods detail bottom design ok');
