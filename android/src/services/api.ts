import Constants from 'expo-constants';
import { Platform } from 'react-native';
import { getToken } from '../storage/authStorage';
import {
  ConnectionProfile,
  getConnectionProfile,
  getDeviceId,
  normalizeApiBaseUrl,
} from '../storage/connectionStorage';
import { SaleDraft } from '../domain/sale';

const configuredUrl = process.env.EXPO_PUBLIC_API_URL ?? Constants.expoConfig?.extra?.apiUrl;
const browserHost = Platform.OS === 'web' ? globalThis.location?.hostname : null;
const localBrowserUrl = browserHost === 'localhost' || browserHost === '127.0.0.1'
  ? 'http://127.0.0.1:8010/api'
  : null;
export const DEFAULT_API_BASE_URL = String(localBrowserUrl ?? configuredUrl ?? 'http://127.0.0.1:8010/api').replace(/\/$/, '');

export class ApiError extends Error {
  constructor(message: string, public status: number, public body?: unknown) {
    super(message);
  }
}

async function apiBaseUrl(override?: string): Promise<string> {
  if (override) return normalizeApiBaseUrl(override);
  const profile = await getConnectionProfile();
  return profile?.apiBaseUrl ?? DEFAULT_API_BASE_URL;
}

async function request<T>(
  path: string,
  options: RequestInit = {},
  authenticated = true,
  baseOverride?: string,
): Promise<T> {
  const token = authenticated ? await getToken() : null;
  const [base, deviceId] = await Promise.all([apiBaseUrl(baseOverride), getDeviceId()]);
  const response = await fetch(`${base}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      'X-Shop-Device-Id': deviceId,
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

export type ShopConnection = {
  mode: 'shop' | 'cloud';
  hubId: string | null;
  hubName: string | null;
  shopOwnerId: number | null;
  withinShopSync: boolean;
  cloudConfigured: boolean;
  pendingCloudEvents: number | null;
  serverTime: string;
};

export const probeShopConnection = (baseUrl?: string) =>
  request<{ data: ShopConnection }>('/shop-hub/status', {}, false, baseUrl);

export const getShopContext = () =>
  request<{ data: ShopConnection }>('/sales-agent/shop-context');

export const login = (loginValue: string, password: string, baseUrl?: string) =>
  request<{ token: string; user: { id: number; name: string }; connection: ShopConnection }>('/sales-agent/login', {
    method: 'POST',
    body: JSON.stringify({ login: loginValue, password, device_name: 'BuyNStitch Sales Agent' }),
  }, false, baseUrl);

export const connectionProfileFrom = (baseUrl: string | undefined, connection: ShopConnection): ConnectionProfile => ({
  apiBaseUrl: normalizeApiBaseUrl(baseUrl || DEFAULT_API_BASE_URL),
  mode: connection.mode,
  hubId: connection.hubId,
  hubName: connection.hubName,
});

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
  defaultSaleLength: string;
  salePriceBasis: 'per_meter' | 'per_suit';
  colorTrackingMode: 'none' | 'display_only' | 'per_color';
  availableLength: string;
  colors: { name: string; availableLength: string | null }[];
};

export type InventoryListItem = {
  id: number;
  setCode: string;
  brandId: number;
  brand: string;
  clothTypeId: number;
  clothType: string;
  salePrice: string;
  defaultSaleLength: string;
  salePriceBasis: 'per_meter' | 'per_suit';
  colorTrackingMode: 'none' | 'display_only' | 'per_color';
  availableLength: string;
  colors: { color: string; length: string | null }[];
};

export const listInventory = () =>
  request<{ data: InventoryListItem[] }>('/sales-agent/inventory');

export const lookupInventorySet = (scannedCode: string) => {
  const code = scannedCode.startsWith('BNS-SET:') ? scannedCode.slice(8) : scannedCode;
  return request<{ data: InventorySet }>(`/sales-agent/inventory/sets/${encodeURIComponent(code)}`);
};

const operationUuid = () => {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
    const random = Math.random() * 16 | 0;
    const value = character === 'x' ? random : (random & 0x3 | 0x8);
    return value.toString(16);
  });
};

const draftPayload = (draft: SaleDraft, revision: number) => ({
  operationId: operationUuid(),
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
