const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const goodsDetailPage = readFileSync(join(__dirname, '..', 'details', 'index.tsx'), 'utf8');
const commentsPage = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const detailCommentService = readFileSync(join(__dirname, '..', '..', '..', 'services', 'good', 'fetchGoodsDetailsComments.ts'), 'utf8');
const commentsService = readFileSync(join(__dirname, '..', '..', '..', 'services', 'comments', 'fetchComments.ts'), 'utf8');

assert.ok(!goodsDetailPage.includes('buildMockComments('), 'goods detail page should not fall back to mock comments');
assert.ok(!commentsPage.includes('buildMockComments('), 'comments page should not fall back to mock comments');
assert.ok(!commentsPage.includes('buildMockCount('), 'comments page should not fall back to mock count');
assert.match(detailCommentService, /specInfo:\s*item\.skuName/, 'goods detail comment service should map skuName to specInfo');
assert.match(detailCommentService, /sellerReply:\s*item\.adminReply/, 'goods detail comment service should map adminReply to sellerReply');
assert.match(commentsService, /specInfo:\s*item\.skuName/, 'comments service should map skuName to specInfo');
assert.match(commentsService, /sellerReply:\s*item\.adminReply/, 'comments service should map adminReply to sellerReply');

console.log('goods comments api wiring ok');
