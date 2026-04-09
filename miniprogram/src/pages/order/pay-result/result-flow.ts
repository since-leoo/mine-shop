export function resolvePaymentResultState(input: any): {
  status: 'processing' | 'success' | 'failed';
  reason: string;
} {
  const status = String(input?.status || '').toLowerCase();
  const payStatus = String(input?.payStatus || '').toLowerCase();

  if (payStatus === 'paid' || ['paid', 'partial_shipped', 'shipped', 'completed', 'refunded'].includes(status)) {
    return { status: 'success', reason: '' };
  }

  if (status === 'cancelled' || payStatus === 'cancelled' || payStatus === 'failed') {
    return { status: 'failed', reason: '订单已取消' };
  }

  return { status: 'processing', reason: '' };
}
