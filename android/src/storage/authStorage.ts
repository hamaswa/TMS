import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'buynstitch.sales-agent.token';
const USER_ID_KEY = 'buynstitch.sales-agent.user-id';

export async function getToken() {
  if (Platform.OS === 'web') return globalThis.localStorage?.getItem(TOKEN_KEY) ?? null;
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function getUserId(): Promise<number | null> {
  const raw = Platform.OS === 'web'
    ? globalThis.localStorage?.getItem(USER_ID_KEY) ?? null
    : await SecureStore.getItemAsync(USER_ID_KEY);
  const value = Number(raw);
  return Number.isInteger(value) && value > 0 ? value : null;
}

export async function setToken(token: string, userId: number) {
  if (Platform.OS === 'web') {
    globalThis.localStorage?.setItem(TOKEN_KEY, token);
    globalThis.localStorage?.setItem(USER_ID_KEY, String(userId));
    return;
  }
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  await SecureStore.setItemAsync(USER_ID_KEY, String(userId));
}

export async function clearToken() {
  if (Platform.OS === 'web') {
    globalThis.localStorage?.removeItem(TOKEN_KEY);
    globalThis.localStorage?.removeItem(USER_ID_KEY);
    return;
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  await SecureStore.deleteItemAsync(USER_ID_KEY);
}
