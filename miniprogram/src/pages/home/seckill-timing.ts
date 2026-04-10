interface HomeSeckillPayload {
  seckillEndTime?: string | number | null;
  seckillActivityId?: string | number | null;
  seckillSessionId?: string | number | null;
}

interface SeckillSessionLike {
  id: string;
  activityId?: string;
  status: 'ongoing' | 'upcoming' | 'ended';
  time: string;
  startTime?: number;
  endTime?: number;
  remainingTime?: number;
}

interface HomeSeckillTarget {
  endTime: string | number | null;
  activityId: string | number | null;
  sessionId: string | number | null;
}

function toTargetTimestamp(value: unknown): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value > 1_000_000_000_000 ? value : value * 1000;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Date.parse(value.replace(/-/g, '/'));
    return Number.isNaN(parsed) ? 0 : parsed;
  }

  return 0;
}

function resolveSessionEndTime(session: SeckillSessionLike, nowMs: number): number {
  const endTime = Number(session.endTime || 0);
  if (endTime > 0) return endTime;

  const remainingTime = Number(session.remainingTime || 0);
  if (remainingTime > 0) return nowMs + remainingTime;

  return 0;
}

function pickLatestStartedSession(sessions: SeckillSessionLike[], nowMs: number): SeckillSessionLike | null {
  const started = sessions
    .filter((item) => {
      const startTime = Number(item.startTime || 0);
      return item.status !== 'ended' && startTime > 0 && startTime <= nowMs;
    })
    .sort((left, right) => Number(right.startTime || 0) - Number(left.startTime || 0));

  return started[0] || null;
}

function pickNearestUpcomingSession(sessions: SeckillSessionLike[], nowMs: number): SeckillSessionLike | null {
  const upcoming = sessions
    .filter((item) => {
      const startTime = Number(item.startTime || 0);
      return item.status !== 'ended' && startTime > nowMs;
    })
    .sort((left, right) => Number(left.startTime || 0) - Number(right.startTime || 0));

  return upcoming[0] || null;
}

export function resolveHomeSeckillTarget(
  payload: HomeSeckillPayload,
  sessions: SeckillSessionLike[],
  nowMs = Date.now(),
): HomeSeckillTarget {
  const normalizedSessions = Array.isArray(sessions) ? sessions : [];
  const session =
    pickLatestStartedSession(normalizedSessions, nowMs) ||
    normalizedSessions.find((item) => item.status === 'ongoing') ||
    pickNearestUpcomingSession(normalizedSessions, nowMs) ||
    null;

  if (session) {
    return {
      endTime: resolveSessionEndTime(session, nowMs) || null,
      activityId: session.activityId || null,
      sessionId: session.id || null,
    };
  }

  return {
    endTime: toTargetTimestamp(payload?.seckillEndTime ?? null) || null,
    activityId: payload?.seckillActivityId ?? null,
    sessionId: payload?.seckillSessionId ?? null,
  };
}
