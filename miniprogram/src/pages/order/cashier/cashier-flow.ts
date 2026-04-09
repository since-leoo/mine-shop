export function resolveSubmitResultState(input: any): {
  shouldRetry: boolean;
  failed: boolean;
  reason: string;
} {
  const status = String(input?.status || '').toLowerCase();
  const reason = String(input?.error || input?.msg || input?.message || '');

  if (status === 'processing' || status === 'pending' || status === 'not_found' || !status) {
    return {
      shouldRetry: true,
      failed: false,
      reason: '',
    };
  }

  if (status === 'failed') {
    return {
      shouldRetry: false,
      failed: true,
      reason,
    };
  }

  return {
    shouldRetry: false,
    failed: false,
    reason: '',
  };
}
