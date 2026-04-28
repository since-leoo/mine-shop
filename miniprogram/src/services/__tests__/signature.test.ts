import { createHash, createHmac } from 'node:crypto';
import { describe, expect, it } from 'vitest';
import {
  buildBodySha256,
  buildCanonicalJson,
  buildQueryString,
  buildSignatureHeaders,
} from '../_utils/signature';

describe('signature utils', () => {
  it('builds deterministic body sha256', () => {
    const body = buildCanonicalJson({ phone: '13800138000', nested: { b: 2, a: 1 } });

    expect(body).toBe('{"nested":{"a":1,"b":2},"phone":"13800138000"}');
    expect(buildBodySha256(body)).toBe(createHash('sha256').update(body).digest('hex'));
  });

  it('builds sorted query string', () => {
    expect(buildQueryString({
      page: 2,
      filter: { b: '2', a: '1' },
      tags: ['b', 'a'],
    })).toBe('filter%5Ba%5D=1&filter%5Bb%5D=2&page=2&tags%5B%5D=b&tags%5B%5D=a');
  });

  it('builds deterministic signature headers', () => {
    const body = '{"phone":"13800138000"}';
    const bodySha256 = createHash('sha256').update(body).digest('hex');
    const payload = [
      'POST',
      '/api/v1/auth/captcha',
      '',
      '1714219200',
      'nonce-fixed',
      bodySha256,
      'h5',
    ].join('\n');

    expect(buildSignatureHeaders({
      method: 'POST',
      path: '/api/v1/auth/captcha',
      queryString: '',
      bodyString: body,
      clientId: 'h5',
      secret: 'h5-secret',
      timestamp: 1714219200,
      nonce: 'nonce-fixed',
    })).toEqual({
      'X-Client-Id': 'h5',
      'X-Timestamp': '1714219200',
      'X-Nonce': 'nonce-fixed',
      'X-Body-Sha256': bodySha256,
      'X-Signature': createHmac('sha256', 'h5-secret').update(payload).digest('hex'),
    });
  });
});
