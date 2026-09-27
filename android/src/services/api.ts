import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { getToken } from '../storage/authStorage';
import { SaleDraft } from '../domain/sale';

const configuredUrl = process.env.EXPO_PUBLIC_API_URL ?? Constants.expoConfig?.extra?.apiUrl;
const browserHost = Platform.OS === 'web' ? globalThis.location?.hostname : null;
const localBrowserUrl = browserHost === 'localhost' || browserHost === '127.0.0.1'
  ? 'http://127.0.0.1:8010/api'
  : null;
export const API_BASE_URL = String(localBrowserUrl ?? configuredUrl ?? 'http://127.0.0.1:8010/api').replace(/\/$/, '');

export class ApiError extends Error {
  constructor(message: string, public status: number, public body?: unknown) {
    super(message);
  }
}

async function request<T>(path: string, options: RequestInit = {}, authenticated = true): Promise<T> {
  const token = authenticated ? await getToken() : null;
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });
  const body = await response.json().catch(() => ({}));
  if (!response.ok) {
    const firstError = Object.values((body as { errors?: Record<string, string[]> }).errors ?? {})[0]?.[0];
    throw new ApiError(firstError ?? (body as { message?: string }).message ?? 'Request failed.', response.status, body);
  }
  return body as T;
}

export type ServerSession = {
  uuid: string;
  status: 'active' | 'needs_attention' | 'claimed' | 'completed' | 'cancelled';
  revision: number;
  receipt?: { id: number; receipt_number: string } | null;
};

export const login = (loginValue: string, password: string) =>
  request<{ token: string; user: { id: number; name: string } }>('/sales-agent/login', {
    method: 'POST',
    body: JSON.stringify({ login: loginValue, password, device_name: 'BuyNStitch Sales Agent' }),
  }, false);

export const logout = () => request('/sales-agent/logout', { method: 'POST' });

export type CustomerResult = { id: number; name: string; phone_number1: string };
export const searchCustomers = (query: string) =>
  request<{ data: CustomerResult[] }>(`/sales-agent/customers?q=${encodeURIComponent(query)}`);

export type InventorySet = {
  setCode: string;
  brandId: number;
  brand: string;
  clothTypeId: number;
  clothType: string;
  salePrice: string;
  colors: { name: string; availableLength: string }[];
};

export type InventoryListItem = {
  id: number;
  setCode: string;
  brandId: number;
  brand: string;
  clothTypeId: number;
  clothType: string;
  salePrice: string;
  colors: { color: string; length: string }[];
};

export const listInventory = () =>
  request<{ data: InventoryListItem[] }>('/sales-agent/inventory');

export const lookupInventorySet = (scannedCode: string) => {
  const code = scannedCode.startsWith('BNS-SET:') ? scannedCode.slice(8) : scannedCode;
  return request<{ data: InventorySet }>(`/sales-agent/inventory/sets/${encodeURIComponent(code)}`);
};

const draftPayload = (draft: SaleDraft, revision: number) => ({
  revision,
  customerMode: draft.customerMode,
  customerId: draft.customerId,
  customer: { name: draft.newCustomerName || draft.customerSearch, phone: draft.newCustomerPhone },
  items: draft.lines,
  payment: {
    method: draft.paymentMethod,
    receivedAmount: draft.receivedAmount,
    reference: draft.paymentReference,
  },
  note: draft.note,
});

export const syncSession = (draft: SaleDraft, revision: number) =>
  request<{ data: ServerSession }>(`/sales-agent/sessions/${encodeURIComponent(draft.localId)}`, {
    method: 'PUT', body: JSON.stringify(draftPayload(draft, revision)),
  });

export const getSession = (uuid: string) =>
  request<{ data: ServerSession }>(`/sales-agent/sessions/${encodeURIComponent(uuid)}`);

export const requestAttention = (draft: SaleDraft, revision: number) =>
  request<{ data: ServerSession }>(`/sales-agent/sessions/${encodeURIComponent(draft.localId)}/attention`, {
    method: 'POST', body: JSON.stringify(draftPayload(draft, revision)),
  });

export const completeSession = (draft: SaleDraft, revision: number) =>
  request<{ data: ServerSession }>(`/sales-agent/sessions/${encodeURIComponent(draft.localId)}/complete`, {
    method: 'POST', body: JSON.stringify(draftPayload(draft, revision)),
  });
