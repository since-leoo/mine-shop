const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const source = fs.readFileSync(path.join(__dirname, 'index.tsx'), 'utf8');
const styles = fs.readFileSync(path.join(__dirname, 'index.scss'), 'utf8');

const defaultViewStart = source.indexOf('function HomeDefaultView');
const homeExportStart = source.indexOf('export default function Home');

assert(defaultViewStart !== -1, 'expected HomeDefaultView to exist');
assert(homeExportStart !== -1, 'expected page export to exist');

const defaultViewSource = source.slice(defaultViewStart, homeExportStart);

assert(source.includes('isH5()'), 'expected platform split to remain in place');
assert(defaultViewSource.includes('home-seckill'), 'expected mini-program view to include seckill section');
assert(defaultViewSource.includes('home-hot'), 'expected mini-program view to include hot section');
assert(defaultViewSource.includes('home-recommend'), 'expected mini-program view to include recommend section');
assert(!defaultViewSource.includes('home-promo-hub'), 'expected mini-program view to stop using the aggregated promo hub');
assert(!defaultViewSource.includes('<GoodsList'), 'expected mini-program recommend section to stop using GoodsList');
assert(defaultViewSource.includes('home-default-card'), 'expected mini-program recommend grid to use dedicated cards');
assert(styles.includes('.home-default-card'), 'expected styles for mini-program recommendation cards');

console.log('home default regression tests passed');
