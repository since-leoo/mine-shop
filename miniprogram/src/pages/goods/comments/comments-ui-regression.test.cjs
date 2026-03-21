const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const commentsPage = readFileSync(join(__dirname, 'index.tsx'), 'utf8');
const createPage = readFileSync(join(__dirname, 'create/index.tsx'), 'utf8');
const createStyle = readFileSync(join(__dirname, 'create/index.scss'), 'utf8');
const listStyle = readFileSync(join(__dirname, 'index.scss'), 'utf8');

assert.ok(!commentsPage.includes(String.raw`\u`), 'comments page should not contain raw unicode text');
assert.ok(!createPage.includes(String.raw`\u`), 'create comment page should not contain raw unicode text');
assert.ok(!commentsPage.includes('???'), 'comments page should not contain question-mark placeholders');
assert.ok(!createPage.includes('???'), 'create comment page should not contain question-mark placeholders');
assert.match(commentsPage, new RegExp('\u533f\u540d\u7528\u6237'), 'comments page should render anonymous user text');
assert.match(createPage, new RegExp('\u8bc4\u4ef7\u63d0\u4ea4\u6210\u529f'), 'create comment page should render submit success text');
assert.match(createStyle, /comment-rate/, 'create comment style should define custom rate block');
assert.match(listStyle, /comment-star/, 'comments list style should define stars');
assert.match(createPage, /decodeURIComponent/, 'create comment page should decode order params');
assert.match(createPage, /comment-rate/, 'create comment page should render custom rate stars');
assert.match(createPage, /anonymous-check/, 'create comment page should render custom anonymous toggle');

console.log('comments ui copy ok');
