function clampQuantity(value, maxQuantity) {
  const max = Math.max(1, Number(maxQuantity) || 1);
  const next = Math.floor(Number(value) || 0);
  if (next < 1) return 1;
  if (next > max) return max;
  return next;
}

function centsToYuanInput(amount) {
  return ((Number(amount) || 0) / 100).toFixed(2);
}

function normalizeAmountInput(value) {
  const source = String(value || '');
  let normalized = source.replace(/[^\d.]/g, '');
  const dotIndex = normalized.indexOf('.');

  if (dotIndex >= 0) {
    normalized = normalized.slice(0, dotIndex + 1) + normalized.slice(dotIndex + 1).replace(/\./g, '');
  }

  if (normalized.startsWith('.')) {
    normalized = `0${normalized}`;
  }

  const parts = normalized.split('.');
  if (parts.length > 1) {
    normalized = `${parts[0]}.${parts[1].slice(0, 2)}`;
  }

  return normalized;
}

function computeMaxAmountByQuantity(maxAmount, maxQuantity, quantity) {
  const totalAmount = Math.max(0, Number(maxAmount) || 0);
  const totalQuantity = Math.max(1, Number(maxQuantity) || 1);
  const selectedQuantity = clampQuantity(quantity, totalQuantity);

  if (selectedQuantity >= totalQuantity) {
    return totalAmount;
  }

  return Math.floor((totalAmount * selectedQuantity) / totalQuantity);
}

function clampAmountByInput(value, maxAmount) {
  const normalized = normalizeAmountInput(value);
  if (!normalized) {
    return 0;
  }

  const parsed = Math.round(Number(normalized) * 100);
  if (!Number.isFinite(parsed) || parsed < 0) {
    return 0;
  }

  return Math.min(parsed, Math.max(0, Number(maxAmount) || 0));
}

function buildAmountInput(maxAmount) {
  return centsToYuanInput(Math.max(0, Number(maxAmount) || 0));
}

function resolveAmountInput(value, maxAmount) {
  return buildAmountInput(clampAmountByInput(value, maxAmount));
}

module.exports = {
  clampQuantity,
  centsToYuanInput,
  normalizeAmountInput,
  computeMaxAmountByQuantity,
  clampAmountByInput,
  buildAmountInput,
  resolveAmountInput,
};
