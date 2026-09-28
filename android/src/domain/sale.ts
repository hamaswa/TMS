export const EXISTING_SALES_PERMISSION = 'clothing.sales' as const;

export type CustomerMode = 'existing' | 'new' | 'walk-in';
export type LocalSaveState = 'loading' | 'saving' | 'saved' | 'failed';

export type SaleLine = {
  localId: string;
  setCode: string;
  brand: string;
  brandId: number | null;
  clothType: string;
  clothTypeId: number | null;
  color: string;
  availableColors: string[];
  requiresColor: boolean;
  quantity: string;
  length: string;
  unitPrice: string;
};

export type SaleDraft = {
  schemaVersion: 1;
  localId: string;
  serverId: number | null;
  customerMode: CustomerMode;
  customerId: number | null;
  customerSearch: string;
  newCustomerName: string;
  newCustomerPhone: string;
  lines: SaleLine[];
  paymentMethod: string;
  receivedAmount: string;
  paymentReference: string;
  note: string;
  updatedAt: string;
};

export const makeId = () =>
  `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;

export const createSaleLine = (): SaleLine => ({
  localId: makeId(),
  setCode: '',
  brand: '',
  brandId: null,
  clothType: '',
  clothTypeId: null,
  color: '',
  availableColors: [],
  requiresColor: false,
  quantity: '1',
  length: '',
  unitPrice: '',
});

export const createSaleDraft = (): SaleDraft => ({
  schemaVersion: 1,
  localId: makeId(),
  serverId: null,
  customerMode: 'existing',
  customerId: null,
  customerSearch: '',
  newCustomerName: '',
  newCustomerPhone: '',
  lines: [],
  paymentMethod: 'Cash',
  receivedAmount: '',
  paymentReference: '',
  note: '',
  updatedAt: new Date().toISOString(),
});

const numberValue = (value: string) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

export const lineTotal = (line: SaleLine) =>
  numberValue(line.quantity) * numberValue(line.unitPrice);

export const saleTotal = (draft: SaleDraft) =>
  draft.lines.reduce((total, line) => total + lineTotal(line), 0);

export const remainingAmount = (draft: SaleDraft) =>
  saleTotal(draft) - numberValue(draft.receivedAmount);
