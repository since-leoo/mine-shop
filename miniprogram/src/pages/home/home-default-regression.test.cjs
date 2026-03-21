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
assert(source.includes('const [hotList, setHotList] = useState<ProductCard[]>([])'), 'expected home page to keep a dedicated hot list state');
assert(source.includes('hotList={hotList}'), 'expected mini-program branch to receive a dedicated hot list');
assert(defaultViewSource.includes('home-activity-info'), 'expected mini-program view to include dedicated activity info cards');
assert(defaultViewSource.includes('openSeckillTopic'), 'expected mini-program activity info to reuse seckill topic navigation');
assert(defaultViewSource.includes('openGroupBuyTopic'), 'expected mini-program activity info to reuse group-buy topic navigation');
assert(!defaultViewSource.includes('<GoodsList'), 'expected mini-program recommend section to stop using GoodsList');
assert(defaultViewSource.includes('home-default-card'), 'expected mini-program recommend grid to use dedicated cards');
assert(styles.includes('.home-default-card'), 'expected styles for mini-program recommendation cards');
assert(styles.includes('.home-activity-info'), 'expected styles for mini-program activity info cards');

console.log('home default regression tests passed');
