const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const page = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const style = readFileSync(join(__dirname, 'index.scss'), 'utf8');

assert.match(page, /function H5UserCenterView/, 'should keep a dedicated H5 usercenter view');
assert.match(page, /className=\"usercenter usercenter--h5\"/, 'h5 usercenter should use the shared page shell');
assert.match(page, /className=\"usercenter__header-spacer\"/, 'h5 usercenter should include top spacer like the design');
assert.match(page, /className=\"usercenter__header-bg\"/, 'h5 usercenter should reuse design-aligned header block');
assert.match(page, /className=\"usercenter__wallet-card\"/, 'h5 usercenter should reuse design-aligned wallet card');
assert.match(page, /className=\"usercenter__orders-card\"/, 'h5 usercenter should reuse design-aligned order section');
assert.match(page, /className=\"usercenter__menu-card\"/, 'h5 usercenter should reuse design-aligned menu section');
assert.match(page, /className=\"usercenter__qrcode-icon\"/, 'h5 usercenter should keep the qrcode entry from the design');
assert.match(page, /className=\"usercenter__order-tag-icon\" src=\{orderIconMap\[tag\.tabType\] \|\| orderServiceIcon\}/, 'h5 order area should use the same svg icons as the design-aligned layout');
assert.doesNotMatch(page, /usercenter-h5__tip/, 'h5 usercenter should remove the extra H5-only tip card');
assert.doesNotMatch(page, /usercenter-h5__hero/, 'h5 usercenter should remove the old custom hero layout');

assert.match(style, /\.usercenter--h5 \{[\s\S]*background-color: \$warm-bg-page;[\s\S]*padding-bottom: calc\(112px \+ env\(safe-area-inset-bottom\)\);/, 'h5 shell should use the design page background and tab-bar safe area spacing');
assert.match(style, /\.usercenter--h5 \.usercenter__header-bg \{[\s\S]*background: \$warm-gradient-header;[\s\S]*padding: 24px 40rpx 60rpx;[\s\S]*border-radius: 0 0 60rpx 60rpx;/, 'h5 header should match the design header gradient and spacing');
assert.match(style, /\.usercenter--h5 \.usercenter__header-circle \{[\s\S]*right: -80rpx;[\s\S]*top: 40rpx;[\s\S]*width: 360rpx;[\s\S]*height: 360rpx;[\s\S]*background: rgba\(255, 255, 255, 0\.08\);/, 'h5 header should keep the soft highlight orb from the design');
assert.match(style, /\.usercenter--h5 \.usercenter__header-spacer \{[\s\S]*height: 40rpx;/, 'h5 header should reserve the top breathing room from the design');
assert.match(style, /\.usercenter--h5 \.usercenter__wallet-card \{[\s\S]*margin: -40rpx 32rpx 0;[\s\S]*background: rgba\(255, 255, 255, 0\.98\);[\s\S]*border: 1rpx solid rgba\(248, 237, 229, 0\.92\);[\s\S]*box-shadow: 0 8rpx 32rpx rgba\(200, 140, 110, 0\.12\);/, 'h5 wallet card should match the white surface, border and shadow from the design');
assert.match(style, /\.usercenter--h5 \.usercenter__orders-card \{[\s\S]*margin: 28rpx 32rpx 0;[\s\S]*background: rgba\(255, 255, 255, 0\.98\);[\s\S]*border: 1rpx solid rgba\(248, 237, 229, 0\.92\);[\s\S]*box-shadow: 0 4rpx 16rpx rgba\(200, 140, 110, 0\.08\);/, 'h5 order card should match the design card surface and shadow');
assert.match(style, /\.usercenter--h5 \.usercenter__menu-card \{[\s\S]*margin: 0 32rpx 28rpx;[\s\S]*background: rgba\(255, 255, 255, 0\.98\);[\s\S]*border: 1rpx solid rgba\(248, 237, 229, 0\.92\);[\s\S]*box-shadow: 0 4rpx 16rpx rgba\(200, 140, 110, 0\.08\);/, 'h5 menu cards should keep the same card treatment as the design');
assert.match(style, /\.usercenter--h5 \.usercenter__order-tag-icon \{[\s\S]*width: 56rpx;[\s\S]*height: 56rpx;/, 'h5 order icons should be enlarged to the design scale');
assert.match(style, /\.usercenter--h5 \.usercenter__menu-icon \{[\s\S]*width: 40rpx;[\s\S]*height: 40rpx;/, 'h5 menu icons should use the smaller line-icon size from the design');

console.log('usercenter h5 design ok');
