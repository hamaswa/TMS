export const EXISTING_SALES_PERMISSION = 'clothing.sales' as const;

export type CustomerMode = 'existing' | 'new' | 'walk-in';
export type LocalSaveState = 'loading' | 'saving' | 'saved' | 'failed';
export type SalePriceBasis = 'per_meter' | 'per_suit';

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
  salePriceBasis: SalePriceBasis;
  /** Metres in one suit; only used when the cloth is sold per suit. */
  suitLength: string;
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
  salePriceBasis: 'per_meter',
  suitLength: '',
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

const decimalText = (value: number) => String(Math.round(value * 100) / 100);

// Per-suit cloth sends the total metres cut so the shop stock is reduced correctly.
export const suitTotalLength = (quantity: string, suitLength: string) => {
  const metres = numberValue(quantity) * numberValue(suitLength);
  return metres > 0 ? decimalText(metres) : '';
};

const sameText = (left: string, right: string) =>
  left.trim().toLowerCase() === right.trim().toLowerCase();

// A rescan of the same set (and the same chosen colour) is one more unit of an existing line.
export const isSameStockItem = (line: SaleLine, scanned: Partial<SaleLine>) =>
  Boolean(scanned.setCode)
  && sameText(line.setCode, scanned.setCode ?? '')
  && line.brandId === (scanned.brandId ?? null)
  && line.clothTypeId === (scanned.clothTypeId ?? null)
  && sameText(line.color, scanned.color ?? '');

export const addScannedUnit = (line: SaleLine, scanned: Partial<SaleLine>): SaleLine => {
  // Prices and basis come fresh from the server so an old line picks up price changes.
  const fresh = { ...line, ...scanned, color: line.color, localId: line.localId };
  const quantity = decimalText(numberValue(line.quantity || '1') + 1);

  if (fresh.salePriceBasis === 'per_suit') {
    const suitLength = fresh.suitLength || scanned.length || '';
    return { ...fresh, quantity, suitLength, length: suitTotalLength(quantity, suitLength) };
  }

  const scannedLength = numberValue(scanned.length ?? '');
  return {
    ...fresh,
    quantity,
    length: scannedLength > 0 ? decimalText(numberValue(line.length) + scannedLength) : line.length,
  };
};

export const lineTotal = (line: SaleLine) =>
  numberValue(line.salePriceBasis === 'per_suit' ? line.quantity : (line.length || line.quantity))
    * numberValue(line.unitPrice);

export const saleTotal = (draft: SaleDraft) =>
  draft.lines.reduce((total, line) => total + lineTotal(line), 0);

export const remainingAmount = (draft: SaleDraft) =>
  saleTotal(draft) - numberValue(draft.receivedAmount);
