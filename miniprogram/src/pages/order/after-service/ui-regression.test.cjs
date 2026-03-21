const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const shared = readFileSync(join(__dirname, 'shared.ts'), 'utf8');
const listPage = readFileSync(join(__dirname, '../after-service-list/index.tsx'), 'utf8');
const detailPage = readFileSync(join(__dirname, '../after-service-detail/index.tsx'), 'utf8');
const detailStyle = readFileSync(join(__dirname, '../after-service-detail/index.scss'), 'utf8');

assert.match(shared, /rejectReason/, 'timeline should support reject reason');
assert.match(shared, /getAfterSaleStatusText/, 'status helper should exist');
assert.ok(listPage.includes('waiting_reship'));
assert.ok(listPage.includes('reshipped'));
assert.ok(!listPage.includes('????'), 'list buttons should not contain question marks');
assert.ok(!shared.includes(String.raw`\\u`), 'shared copy should not contain double unicode escapes');
assert.ok(!listPage.includes(String.raw`\\u`), 'list copy should not contain double unicode escapes');
assert.ok(!detailPage.includes(String.raw`\\u`), 'detail copy should not contain double unicode escapes');
assert.match(detailPage, /getTimelineTone/, 'detail page should compute timeline tone');
assert.match(detailStyle, /timeline-item--warning/, 'detail style should include warning tone');
assert.match(detailStyle, /timeline-item--success/, 'detail style should include success tone');

console.log('after-service ui ok');
