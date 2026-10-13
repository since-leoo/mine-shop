import { afterEach, expect, it, vi } from 'vitest';
import { fetchSeckillSessions } from '../../../services/promotion/detail';
import { request } from '../../../services/request';
vi.mock('../../../services/request', () => ({ request: vi.fn() }));
vi.mock('../../../config', () => ({ config: { useMock: false } }));
afterEach(() => vi.useRealTimers());

it('upcoming 不因包含 ing 被当成进行中', async () => {
  vi.mocked(request).mockResolvedValue([{ id: '1', status: 'upcoming' }, { id: '2', status: 'ongoing' }]);
  const sessions = await fetchSeckillSessions();
  expect(sessions.map(session => session.status)).toEqual(['upcoming', 'ongoing']);
});

it('已过结束时间的场次不会沿用接口旧的 ongoing', async () => {
  vi.useFakeTimers();
  vi.setSystemTime(new Date('2026-10-13T12:00:00+08:00'));
  vi.mocked(request).mockResolvedValue([{ id: '1', status: 'ongoing', startTime: '2026-10-13T09:00:00+08:00', endTime: '2026-10-13T10:00:00+08:00' }]);
  expect((await fetchSeckillSessions())[0].status).toBe('ended');
});

it('剩余不足十七分钟仍保持 API 毫秒单位', async () => {
  vi.mocked(request).mockResolvedValue([{ id: '1', status: 'ongoing', remainingTime: 900_000 }, { id: '2', status: 'ongoing', remainingTime: 2_000_000_000 }]);
  expect((await fetchSeckillSessions()).map(session => session.remainingTime)).toEqual([900_000, 2_000_000_000]);
});
