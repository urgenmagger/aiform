import type { ContactPayload } from '../types/contact';

const BASE = '/api';

export async function submitContact(payload: ContactPayload): Promise<{ message: string }> {
  const res = await fetch(`${BASE}/contact`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new Error(data.message || data.error || 'Failed to send message');
  }

  return data;
}
