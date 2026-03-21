const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const shared = readFileSync(join(__dirname, 'after-service/shared.ts'), 'utf8');
const detailPage = readFileSync(join(__dirname, 'after-service-detail/index.tsx'), 'utf8');
const listPage = readFileSync(join(__dirname, 'after-service-list/index.tsx'), 'utf8');
const fillReturnPage = readFileSync(join(__dirname, 'fill-return-shipping/index.tsx'), 'utf8');
const applyPage = readFileSync(join(__dirname, 'apply-service/index.tsx'), 'utf8');
const controller = readFileSync(join(__dirname, '../../../../app/Interface/Api/Controller/V1/AfterSaleController.php'), 'utf8');

assert.match(shared, /getAfterSaleProgressInfo/, 'shared progress helper should exist');
assert.match(shared, /getAfterSalePrimaryAction/, 'shared primary action helper should exist');
assert.match(detailPage, /after-service-detail__progress/, 'detail page should render progress card');
assert.match(detailPage, /getAfterSaleProgressInfo/, 'detail page should use progress helper');
assert.match(listPage, /getAfterSalePrimaryAction/, 'list page should use shared action helper');
assert.match(fillReturnPage, /redirectTo\(\{ url: `\/pages\/order\/after-service-detail\/index\?id=\$\{id\}` \}\)/, 'fill return shipping should redirect to after-sale detail');
assert.match(shared, /canReapplyAfterSale/, 'shared should support reapply after rejected after-sale');
assert.match(shared, /refundRecord/, 'shared should understand refund record fields');
assert.match(listPage, /reapply/, 'list page should handle reapply action');
assert.match(listPage, /buildAfterSaleApplyUrl/, 'list page should jump to apply-service when reapplying');
assert.match(detailPage, new RegExp('\u9000\u6b3e\u8bb0\u5f55'), 'detail page should render refund record section');
assert.match(detailPage, /refundRecord/, 'detail page should render refund record data');
assert.match(detailPage, /reapply/, 'detail page should support reapply action');
assert.match(applyPage, /after-service-detail/, 'apply page should redirect to after-sale detail after submit');
assert.match(controller, /confirm-exchange-received/, 'member api should expose confirm exchange received route');
assert.ok(!shared.includes(String.raw`\u`), 'shared should not render raw unicode text');
assert.ok(!detailPage.includes(String.raw`\u`), 'detail page should not render raw unicode text');
assert.ok(!listPage.includes(String.raw`\u`), 'list page should not render raw unicode text');
assert.ok(!fillReturnPage.includes(String.raw`\u`), 'fill return page should not render raw unicode text');
assert.ok(!applyPage.includes(String.raw`\u`), 'apply page should not render raw unicode text');

console.log('after-sale flow ok');
