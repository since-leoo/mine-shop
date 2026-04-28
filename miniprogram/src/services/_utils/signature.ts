type JsonValue = null | boolean | number | string | JsonValue[] | { [key: string]: JsonValue };

interface SignatureHeadersInput {
  method: string;
  path: string;
  queryString: string;
  bodyString: string;
  clientId: string;
  secret: string;
  timestamp?: number;
  nonce?: string;
}

function toUtf8Bytes(input: string): number[] {
  if (typeof TextEncoder !== 'undefined') {
    return Array.from(new TextEncoder().encode(input));
  }

  const bytes: number[] = [];
  for (let i = 0; i < input.length; i += 1) {
    let codePoint = input.charCodeAt(i);

    if (codePoint < 0x80) {
      bytes.push(codePoint);
      continue;
    }

    if (codePoint < 0x800) {
      bytes.push(0xc0 | (codePoint >> 6), 0x80 | (codePoint & 0x3f));
      continue;
    }

    if (codePoint >= 0xd800 && codePoint <= 0xdbff) {
      const next = input.charCodeAt(i + 1);
      codePoint = (((codePoint - 0xd800) << 10) | (next - 0xdc00)) + 0x10000;
      i += 1;
      bytes.push(
        0xf0 | (codePoint >> 18),
        0x80 | ((codePoint >> 12) & 0x3f),
        0x80 | ((codePoint >> 6) & 0x3f),
        0x80 | (codePoint & 0x3f),
      );
      continue;
    }

    bytes.push(
      0xe0 | (codePoint >> 12),
      0x80 | ((codePoint >> 6) & 0x3f),
      0x80 | (codePoint & 0x3f),
    );
  }

  return bytes;
}

function bytesToHex(bytes: number[]): string {
  return bytes.map((byte) => byte.toString(16).padStart(2, '0')).join('');
}

function rightRotate(value: number, amount: number): number {
  return (value >>> amount) | (value << (32 - amount));
}

function sha256Bytes(input: number[]): number[] {
  const K = [
    0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
    0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
    0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
    0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
    0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
    0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
    0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
    0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2,
  ];

  const H = [
    0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a,
    0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19,
  ];

  const message = input.slice();
  const bitLength = message.length * 8;
  message.push(0x80);

  while ((message.length % 64) !== 56) {
    message.push(0);
  }

  const highBits = Math.floor(bitLength / 0x100000000);
  const lowBits = bitLength >>> 0;

  message.push(
    (highBits >>> 24) & 0xff,
    (highBits >>> 16) & 0xff,
    (highBits >>> 8) & 0xff,
    highBits & 0xff,
    (lowBits >>> 24) & 0xff,
    (lowBits >>> 16) & 0xff,
    (lowBits >>> 8) & 0xff,
    lowBits & 0xff,
  );

  const w = new Array<number>(64);

  for (let offset = 0; offset < message.length; offset += 64) {
    for (let i = 0; i < 16; i += 1) {
      const index = offset + (i * 4);
      w[i] = (
        (message[index] << 24)
        | (message[index + 1] << 16)
        | (message[index + 2] << 8)
        | message[index + 3]
      ) >>> 0;
    }

    for (let i = 16; i < 64; i += 1) {
      const s0 = rightRotate(w[i - 15], 7) ^ rightRotate(w[i - 15], 18) ^ (w[i - 15] >>> 3);
      const s1 = rightRotate(w[i - 2], 17) ^ rightRotate(w[i - 2], 19) ^ (w[i - 2] >>> 10);
      w[i] = (w[i - 16] + s0 + w[i - 7] + s1) >>> 0;
    }

    let [a, b, c, d, e, f, g, h] = H;

    for (let i = 0; i < 64; i += 1) {
      const s1 = rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25);
      const ch = (e & f) ^ (~e & g);
      const temp1 = (h + s1 + ch + K[i] + w[i]) >>> 0;
      const s0 = rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22);
      const maj = (a & b) ^ (a & c) ^ (b & c);
      const temp2 = (s0 + maj) >>> 0;

      h = g;
      g = f;
      f = e;
      e = (d + temp1) >>> 0;
      d = c;
      c = b;
      b = a;
      a = (temp1 + temp2) >>> 0;
    }

    H[0] = (H[0] + a) >>> 0;
    H[1] = (H[1] + b) >>> 0;
    H[2] = (H[2] + c) >>> 0;
    H[3] = (H[3] + d) >>> 0;
    H[4] = (H[4] + e) >>> 0;
    H[5] = (H[5] + f) >>> 0;
    H[6] = (H[6] + g) >>> 0;
    H[7] = (H[7] + h) >>> 0;
  }

  const output: number[] = [];
  H.forEach((value) => {
    output.push(
      (value >>> 24) & 0xff,
      (value >>> 16) & 0xff,
      (value >>> 8) & 0xff,
      value & 0xff,
    );
  });

  return output;
}

export function buildBodySha256(body: string): string {
  return bytesToHex(sha256Bytes(toUtf8Bytes(body)));
}

function hmacSha256Hex(secret: string, message: string): string {
  const blockSize = 64;
  let keyBytes = toUtf8Bytes(secret);
  if (keyBytes.length > blockSize) {
    keyBytes = sha256Bytes(keyBytes);
  }

  while (keyBytes.length < blockSize) {
    keyBytes.push(0);
  }

  const outer = keyBytes.map((byte) => byte ^ 0x5c);
  const inner = keyBytes.map((byte) => byte ^ 0x36);
  const messageBytes = toUtf8Bytes(message);
  const innerDigest = sha256Bytes(inner.concat(messageBytes));

  return bytesToHex(sha256Bytes(outer.concat(innerDigest)));
}

function sortJsonValue(value: JsonValue): JsonValue {
  if (Array.isArray(value)) {
    return value.map((item) => sortJsonValue(item)) as JsonValue;
  }

  if (value && typeof value === 'object') {
    const sortedKeys = Object.keys(value).sort();
    const result: Record<string, JsonValue> = {};
    sortedKeys.forEach((key) => {
      result[key] = sortJsonValue((value as Record<string, JsonValue>)[key]);
    });
    return result;
  }

  return value;
}

export function buildCanonicalJson(value: Record<string, unknown>): string {
  return JSON.stringify(sortJsonValue(value as JsonValue));
}

function appendQueryValue(parts: string[], key: string, value: unknown): void {
  if (value === null || value === undefined) {
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((item) => appendQueryValue(parts, `${key}[]`, item));
    return;
  }

  if (typeof value === 'object' && (value as Record<string, unknown>).constructor === Object) {
    Object.keys(value as Record<string, unknown>).sort().forEach((childKey) => {
      appendQueryValue(parts, `${key}[${childKey}]`, (value as Record<string, unknown>)[childKey]);
    });
    return;
  }

  parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
}

export function buildQueryString(value: Record<string, unknown>): string {
  const parts: string[] = [];
  Object.keys(value).sort().forEach((key) => {
    appendQueryValue(parts, key, value[key]);
  });
  return parts.join('&');
}

function buildNonce(): string {
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

export function buildSignatureHeaders({
  method,
  path,
  queryString,
  bodyString,
  clientId,
  secret,
  timestamp = Math.floor(Date.now() / 1000),
  nonce = buildNonce(),
}: SignatureHeadersInput): Record<string, string> {
  const bodySha256 = buildBodySha256(bodyString);
  const payload = [
    method.toUpperCase(),
    path,
    queryString,
    String(timestamp),
    nonce,
    bodySha256,
    clientId,
  ].join('\n');

  return {
    'X-Client-Id': clientId,
    'X-Timestamp': String(timestamp),
    'X-Nonce': nonce,
    'X-Body-Sha256': bodySha256,
    'X-Signature': hmacSha256Hex(secret, payload),
  };
}
