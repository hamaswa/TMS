import { useCallback, useEffect, useRef, useState } from 'react';
import {
  createSaleDraft,
  createSaleLine,
  CustomerMode,
  LocalSaveState,
  SaleDraft,
  SaleLine,
} from '../domain/sale';
import { clearDraft, loadDraft, saveDraft } from '../storage/draftStorage';
import { getUserId } from '../storage/authStorage';

type DraftField = Exclude<keyof SaleDraft, 'lines' | 'schemaVersion' | 'updatedAt'>;

export function useSaleDraft() {
  const [draft, setDraft] = useState<SaleDraft>(createSaleDraft);
  const [saveState, setSaveState] = useState<LocalSaveState>('loading');
  const [userId, setUserId] = useState<number | null>(null);
  const hydrated = useRef(false);

  useEffect(() => {
    let active = true;

    getUserId()
      .then(async (storedUserId) => {
        if (!active) return;
        if (!storedUserId) {
          hydrated.current = true;
          setSaveState('failed');
          return;
        }
        setUserId(storedUserId);
        const stored = await loadDraft(storedUserId);
        if (!active) return;
        if (stored) setDraft(stored);
        hydrated.current = true;
        setSaveState('saved');
      })
      .catch(() => {
        if (!active) return;
        hydrated.current = true;
        setSaveState('failed');
      });

    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (!hydrated.current || !userId) return;

    setSaveState('saving');
    const timer = setTimeout(() => {
      saveDraft(userId, draft)
        .then(() => setSaveState('saved'))
        .catch(() => setSaveState('failed'));
    }, 250);

    return () => clearTimeout(timer);
  }, [draft, userId]);

  const mutate = useCallback((change: (current: SaleDraft) => SaleDraft) => {
    setDraft((current) => ({ ...change(current), updatedAt: new Date().toISOString() }));
  }, []);

  const setField = useCallback(
    <K extends DraftField>(field: K, value: SaleDraft[K]) => {
      mutate((current) => ({ ...current, [field]: value }));
    },
    [mutate],
  );

  const setCustomerMode = useCallback(
    (customerMode: CustomerMode) => setField('customerMode', customerMode),
    [setField],
  );

  const updateLine = useCallback(
    (localId: string, patch: Partial<SaleLine>) => {
      mutate((current) => ({
        ...current,
        lines: current.lines.map((line) =>
          line.localId === localId ? { ...line, ...patch } : line,
        ),
      }));
    },
    [mutate],
  );

  const addLine = useCallback(() => {
    mutate((current) => ({ ...current, lines: [...current.lines, createSaleLine()] }));
  }, [mutate]);

  const addScannedLine = useCallback(
    (patch: Partial<SaleLine>) => {
      mutate((current) => {
        const onlyLine = current.lines.length === 1 ? current.lines[0] : null;
        const onlyLineIsBlank = onlyLine
          && !onlyLine.setCode
          && !onlyLine.brandId
          && !onlyLine.brand
          && !onlyLine.clothTypeId
          && !onlyLine.clothType
          && !onlyLine.color
          && !onlyLine.length
          && !onlyLine.unitPrice;

        if (onlyLineIsBlank) {
          return { ...current, lines: [{ ...onlyLine, ...patch }] };
        }

        return { ...current, lines: [...current.lines, { ...createSaleLine(), ...patch }] };
      });
    },
    [mutate],
  );

  const removeLine = useCallback(
    (localId: string) => {
      mutate((current) => {
        const lines = current.lines.filter((line) => line.localId !== localId);
        return { ...current, lines: lines.length ? lines : [createSaleLine()] };
      });
    },
    [mutate],
  );

  const startNewDraft = useCallback(async () => {
    if (userId) await clearDraft(userId);
    setDraft(createSaleDraft());
    setSaveState('saved');
  }, [userId]);

  return {
    draft,
    saveState,
    setField,
    setCustomerMode,
    updateLine,
    addLine,
    addScannedLine,
    removeLine,
    startNewDraft,
  };
}
