export function generateMixed(n: number, str: string): string {
  let res = '';
  for (let i = 0; i < n; i++) {
    const id = Math.ceil(Math.random() * 35);
    res += str[id];
  }
  return res;
}

export function getRandomNum(min: number, max: number): number {
  const range = max - min;
  return min + Math.round(Math.random() * range);
}

export function mockIp(): string {
  return `10.${getRandomNum(1, 254)}.${getRandomNum(1, 254)}.${getRandomNum(1, 254)}`;
}

export function mockReqId(): string {
  return `${getRandomNum(100000, 999999)}.${new Date().valueOf()}${getRandomNum(1000, 9999)}.${getRandomNum(10000000, 99999999)}`;
}
