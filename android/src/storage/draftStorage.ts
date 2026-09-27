import AsyncStorage from '@react-native-async-storage/async-storage';
import { SaleDraft } from '../domain/sale';

const LEGACY_DRAFT_KEY = '@buynstitch/sales-agent/active-draft-v1';
const draftKey = (userId: number) => `@buynstitch/sales-agent/active-draft-v2/user/${userId}`;

export async function loadDraft(userId: number): Promise<SaleDraft | null> {
  // The former global key had no account ownership. Never show it to another employee.
  await AsyncStorage.removeItem(LEGACY_DRAFT_KEY);
  const value = await AsyncStorage.getItem(draftKey(userId));
  if (!value) {
    return null;
  }

  const draft = JSON.parse(value) as Partial<SaleDraft>;
  if (draft.schemaVersion !== 1 || !draft.localId || !Array.isArray(draft.lines)) {
    return null;
  }

  return draft as SaleDraft;
}

export async function saveDraft(userId: number, draft: SaleDraft): Promise<void> {
  await AsyncStorage.setItem(draftKey(userId), JSON.stringify(draft));
}

export async function clearDraft(userId: number): Promise<void> {
  await AsyncStorage.removeItem(draftKey(userId));
}
