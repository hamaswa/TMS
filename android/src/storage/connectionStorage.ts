import AsyncStorage from '@react-native-async-storage/async-storage';

const PROFILE_KEY = '@buynstitch/sales-agent/connection-v1';
const DEVICE_KEY = '@buynstitch/sales-agent/device-id-v1';

export type ConnectionMode = 'shop' | 'cloud';

export type ConnectionProfile = {
  apiBaseUrl: string;
  mode: ConnectionMode;
  hubId: string | null;
  hubName: string | null;
};

export function normalizeApiBaseUrl(value: string): string {
  const trimmed = value.trim().replace(/\/$/, '');
  if (!trimmed) return '';
  return trimmed.endsWith('/api') ? trimmed : `${trimmed}/api`;
}

export async function getConnectionProfile(): Promise<ConnectionProfile | null> {
  const raw = await AsyncStorage.getItem(PROFILE_KEY);
  if (!raw) return null;
  try {
    const profile = JSON.parse(raw) as ConnectionProfile;
    return profile.apiBaseUrl && (profile.mode === 'shop' || profile.mode === 'cloud') ? profile : null;
  } catch {
    return null;
  }
}

export async function setConnectionProfile(profile: ConnectionProfile): Promise<void> {
  await AsyncStorage.setItem(PROFILE_KEY, JSON.stringify(profile));
}

export async function getDeviceId(): Promise<string> {
  const stored = await AsyncStorage.getItem(DEVICE_KEY);
  if (stored) return stored;

  const randomPart = Math.random().toString(36).slice(2, 12);
  const value = `device-${Date.now().toString(36)}-${randomPart}`;
  await AsyncStorage.setItem(DEVICE_KEY, value);
  return value;
}
