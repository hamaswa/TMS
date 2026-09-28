import NetInfo from '@react-native-community/netinfo';
import { router } from 'expo-router';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { ScannerModal } from '../components/ScannerModal';
import {
  CustomerMode,
  EXISTING_SALES_PERMISSION,
  lineTotal,
  remainingAmount,
  SaleLine,
  saleTotal,
} from '../domain/sale';
import { useSaleDraft } from '../hooks/useSaleDraft';
import {
  ApiError,
  completeSession,
  CustomerResult,
  getShopContext,
  InventoryListItem,
  listInventory,
  logout,
  lookupInventorySet,
  requestAttention,
  searchCustomers,
  ShopConnection,
} from '../services/api';
import { clearToken, getToken, getUserId } from '../storage/authStorage';

const BLUE = '#123b5d';
const GREEN = '#147a5a';
const BACKGROUND = '#f4f7f9';
const PAYMENT_METHODS = ['Cash', 'Easypaisa', 'JazzCash', 'Bank transfer', 'Raast', 'Cheque', 'Other'] as const;

export function SaleDraftScreen() {
  const insets = useSafeAreaInsets();
  const {
    draft,
    saveState,
    setField,
    setCustomerMode,
    updateLine,
    addScannedLine,
    removeLine,
    startNewDraft,
  } = useSaleDraft();
  const [scannerVisible, setScannerVisible] = useState(false);
  const [online, setOnline] = useState<boolean | null>(null);
  const [shopConnection, setShopConnection] = useState<ShopConnection | null>(null);
  const [syncState, setSyncState] = useState<'waiting' | 'syncing' | 'offline' | 'conflict' | 'claimed'>('waiting');
  const [completing, setCompleting] = useState(false);
  const [forwarding, setForwarding] = useState(false);
  const [customerPickerVisible, setCustomerPickerVisible] = useState(false);
  const [customerResults, setCustomerResults] = useState<CustomerResult[]>([]);
  const [customerPickerSearch, setCustomerPickerSearch] = useState('');
  const [customerPickerLoading, setCustomerPickerLoading] = useState(false);
  const [customerPickerError, setCustomerPickerError] = useState('');
  const [manualPickerVisible, setManualPickerVisible] = useState(false);
  const [manualInventory, setManualInventory] = useState<InventoryListItem[]>([]);
  const [manualSearch, setManualSearch] = useState('');
  const [manualLoading, setManualLoading] = useState(false);
  const [manualError, setManualError] = useState('');
  const serverRevisionRef = useRef(0);

  const rememberServerRevision = (revision: number) => {
    serverRevisionRef.current = revision;
  };

  async function handleRemoteCompletion(receipt?: string) {
    await startNewDraft();
    rememberServerRevision(0);
    setSyncState('waiting');
    Alert.alert('Sale completed', receipt ? `Receipt ${receipt} is ready on the dashboard.` : 'The dashboard completed this sale. You can start the next customer now.');
  }

  useEffect(() => NetInfo.addEventListener((state) => {
    const networkAvailable = Boolean(state.isConnected);
    if (!networkAvailable) {
      setOnline(false);
      setShopConnection(null);
      setSyncState('offline');
      return;
    }

    getShopContext()
      .then((result) => {
        setOnline(true);
        setShopConnection(result.data);
        setSyncState((current) => current === 'offline' ? 'waiting' : current);
      })
      .catch(() => {
        setOnline(false);
        setShopConnection(null);
        setSyncState('offline');
      });
  }), []);

  useEffect(() => {
    Promise.all([getToken(), getUserId()]).then(([token, userId]) => {
      if (!token || !userId) router.replace('/');
    });
  }, []);

  useEffect(() => {
    if (!customerPickerVisible) return;

    let active = true;
    const timer = setTimeout(() => {
      setCustomerPickerLoading(true);
      setCustomerPickerError('');
      searchCustomers(customerPickerSearch.trim())
        .then((result) => {
          if (active) setCustomerResults(result.data);
        })
        .catch((error) => {
          if (!active) return;
          setCustomerResults([]);
          setCustomerPickerError(error instanceof ApiError ? error.message : 'Customers could not be loaded.');
        })
        .finally(() => {
          if (active) setCustomerPickerLoading(false);
        });
    }, 250);

    return () => {
      active = false;
      clearTimeout(timer);
    };
  }, [customerPickerVisible, customerPickerSearch]);

  const totals = useMemo(
    () => ({ total: saleTotal(draft), remaining: remainingAmount(draft) }),
    [draft],
  );

  const saleProblem = (completingSale: boolean): string | null => {
    if (draft.lines.length === 0) return 'Scan a QR code or add an item from shop inventory first.';
    if (draft.customerMode === 'existing' && !draft.customerId) return 'Select an existing customer.';
    if (draft.customerMode === 'new' && !draft.newCustomerName.trim()) return 'Enter the new customer name.';
    if (draft.lines.some((line) => line.requiresColor && !line.color)) return 'Select a color for every color-tracked cloth set.';
    if (completingSale && draft.customerMode === 'walk-in' && totals.remaining > 0.009) {
      return 'A walk-in sale must be fully paid. Otherwise save the buyer as a new customer.';
    }
    return null;
  };

  const forwardToAdmin = async () => {
    const problem = saleProblem(false);
    if (problem) {
      Alert.alert('Sale is incomplete', problem);
      return;
    }
    setForwarding(true);
    setSyncState('syncing');
    try {
      await requestAttention(draft, serverRevisionRef.current);
      await startNewDraft();
      rememberServerRevision(0);
      setSyncState('waiting');
      Alert.alert('Forwarded to admin', 'The sale is now in the dashboard Sales Inbox. You can start the next sale.');
    } catch (error) {
      setSyncState('offline');
      Alert.alert('Could not forward sale', error instanceof ApiError ? error.message : 'Check the connection and try again.');
    } finally {
      setForwarding(false);
    }
  };

  const finishSale = async () => {
    const problem = saleProblem(true);
    if (problem) {
      Alert.alert('Sale is incomplete', problem);
      return;
    }
    setCompleting(true);
    setSyncState('syncing');
    try {
      const completed = await completeSession(draft, serverRevisionRef.current);
      await handleRemoteCompletion(completed.data.receipt?.receipt_number);
    } catch (error) {
      setSyncState('offline');
      Alert.alert('Sale not completed', error instanceof ApiError ? error.message : 'Check the connection and try again.');
    } finally {
      setCompleting(false);
    }
  };

  const signOut = async () => {
    try { await logout(); } catch {}
    await clearToken();
    router.replace('/');
  };

  const handleScannedSet = async (code: string) => {
    try {
      const result = await lookupInventorySet(code);
      const colors = uniqueColors(result.data.colors.map((color) => color.name));
      const requiresColor = result.data.colorTrackingMode === 'per_color';
      addScannedLine({
        setCode: result.data.setCode,
        brandId: result.data.brandId,
        brand: result.data.brand,
        clothTypeId: result.data.clothTypeId,
        clothType: result.data.clothType,
        unitPrice: result.data.salePrice,
        availableColors: colors,
        requiresColor,
        color: defaultColor(colors, requiresColor),
      });
      return true;
    } catch (error) {
      Alert.alert('QR not recognized', error instanceof ApiError ? error.message : 'This set could not be loaded from the shop inventory.');
      return false;
    }
  };

  const openManualPicker = async () => {
    setManualPickerVisible(true);
    setManualSearch('');
    setManualError('');
    setManualLoading(true);

    try {
      const result = await listInventory();
      setManualInventory(result.data);
    } catch (error) {
      setManualError(error instanceof ApiError ? error.message : 'Shop inventory could not be loaded.');
    } finally {
      setManualLoading(false);
    }
  };

  const openCustomerPicker = () => {
    setCustomerPickerSearch('');
    setCustomerPickerError('');
    setCustomerPickerVisible(true);
  };

  const selectCustomer = (customer: CustomerResult) => {
    setField('customerId', customer.id);
    setField('customerSearch', `${customer.name} · ${customer.phone_number1}`);
    setCustomerPickerVisible(false);
  };

  const addManualInventoryItem = (item: InventoryListItem) => {
    const colors = uniqueColors(item.colors.map((color) => color.color));
    const requiresColor = item.colorTrackingMode === 'per_color';
    addScannedLine({
      setCode: item.setCode,
      brandId: item.brandId,
      brand: item.brand,
      clothTypeId: item.clothTypeId,
      clothType: item.clothType,
      unitPrice: item.salePrice,
      availableColors: colors,
      requiresColor,
      color: defaultColor(colors, requiresColor),
    });
    setManualPickerVisible(false);
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView
          contentContainerStyle={[styles.content, { paddingBottom: 112 + insets.bottom }]}
          keyboardShouldPersistTaps="handled"
        >
          <View style={styles.header}>
            <View style={styles.headerText}>
              <Text style={styles.eyebrow}>BUYNSTITCH</Text>
              <Text style={styles.heading}>Sales Agent</Text>
              <Text style={styles.permission}>Existing access: {EXISTING_SALES_PERMISSION}</Text>
            </View>
            <View style={styles.statuses}>
              <StatusChip
                label={online
                  ? shopConnection?.mode === 'shop' ? 'Shop connected' : 'Cloud online'
                  : online === false ? 'Isolated' : 'Checking'}
                tone={online ? 'green' : 'blue'}
              />
              {shopConnection?.mode === 'shop' && shopConnection.pendingCloudEvents ? (
                <StatusChip label={`${shopConnection.pendingCloudEvents} cloud pending`} tone="blue" />
              ) : null}
              <StatusChip label={saveLabel(saveState)} tone={saveState === 'failed' ? 'red' : 'blue'} />
              {syncState === 'offline' || syncState === 'conflict' || syncState === 'claimed' ? (
                <StatusChip label={syncLabel(syncState)} tone={syncState === 'offline' || syncState === 'conflict' ? 'red' : 'blue'} />
              ) : null}
            </View>
          </View>

          <Section title="Customer">
            <View style={styles.segmentRow}>
              {(['existing', 'new', 'walk-in'] as CustomerMode[]).map((mode) => (
                <Segment
                  key={mode}
                  label={mode === 'walk-in' ? 'Walk-in' : titleCase(mode)}
                  selected={draft.customerMode === mode}
                  onPress={() => setCustomerMode(mode)}
                />
              ))}
            </View>
            {draft.customerMode === 'existing' && (
              <SelectionField
                label="Existing customer"
                value={draft.customerId ? draft.customerSearch : ''}
                placeholder="Choose from saved customers"
                onPress={openCustomerPicker}
              />
            )}
            {draft.customerMode === 'new' && (
              <>
                <Field
                  label="Customer name"
                  value={draft.newCustomerName}
                  onChangeText={(value) => setField('newCustomerName', value)}
                />
                <Field
                  label="Phone number"
                  value={draft.newCustomerPhone}
                  onChangeText={(value) => setField('newCustomerPhone', value)}
                  keyboardType="phone-pad"
                />
              </>
            )}
            {draft.customerMode === 'walk-in' && (
              <Text style={styles.help}>The sale can continue without selecting a customer record.</Text>
            )}
          </Section>

          <SecondaryButton label="Add cloth without QR" onPress={openManualPicker} fullWidth />

          <View style={styles.cartHeading}>
            <Text style={styles.cartTitle}>Items added</Text>
            <Text style={styles.cartCount}>{draft.lines.length} in cart</Text>
          </View>

          {draft.lines.length === 0 ? (
            <View style={styles.emptyCart}>
              <Text style={styles.emptyCartTitle}>Your cart is empty</Text>
              <Text style={styles.emptyCartHelp}>Scan a cloth-set QR or choose an item from shop inventory.</Text>
              <SecondaryButton label="Add cloth without QR" onPress={openManualPicker} fullWidth />
            </View>
          ) : (
            draft.lines.map((line, index) => (
              <SaleLineCard
                key={line.localId}
                line={line}
                number={index + 1}
                canRemove
                onChange={(patch) => updateLine(line.localId, patch)}
                onRemove={() => removeLine(line.localId)}
                onChooseStock={openManualPicker}
              />
            ))
          )}

          <Section title="Payment">
            <Text style={styles.fieldLabel}>Payment method</Text>
            <View style={styles.choiceGrid}>
              {PAYMENT_METHODS.map((method) => (
                <Pressable
                  accessibilityRole="button"
                  accessibilityState={{ selected: draft.paymentMethod === method }}
                  key={method}
                  onPress={() => setField('paymentMethod', method)}
                  style={[styles.choiceButton, draft.paymentMethod === method && styles.choiceButtonSelected]}
                >
                  <Text style={[styles.choiceButtonText, draft.paymentMethod === method && styles.choiceButtonTextSelected]}>{method}</Text>
                </Pressable>
              ))}
            </View>
            <Field label="Received amount" value={draft.receivedAmount} onChangeText={(value) => setField('receivedAmount', value)} keyboardType="decimal-pad" />
            <Field label="Payment reference (optional)" value={draft.paymentReference} onChangeText={(value) => setField('paymentReference', value)} />
            <Field label="Sale note (optional)" value={draft.note} onChangeText={(value) => setField('note', value)} multiline />
          </Section>

          <View style={styles.totalCard}>
            <MoneyRow label="Sale total" value={totals.total} />
            <MoneyRow label="Received" value={Number(draft.receivedAmount) || 0} />
            <MoneyRow label="Remaining" value={totals.remaining} emphasized />
          </View>

          <PrimaryButton label={completing ? 'Completing…' : 'Complete sale'} onPress={finishSale} fullWidth />
          <SecondaryButton label={forwarding ? 'Forwarding…' : 'Forward to admin'} onPress={forwardToAdmin} fullWidth />
          <Pressable
            accessibilityRole="button"
            onPress={() =>
              Alert.alert('Start a new draft?', 'The current local draft will be cleared.', [
                { text: 'Cancel', style: 'cancel' },
                { text: 'Clear', style: 'destructive', onPress: startNewDraft },
              ])
            }
          >
            <Text style={styles.clearText}>Clear and start a new draft</Text>
          </Pressable>
          <Pressable accessibilityRole="button" onPress={signOut}>
            <Text style={styles.signOutText}>Sign out</Text>
          </Pressable>

          <Text style={styles.boundaryNote}>
            Changes stay on this phone while you prepare the cart. Data is sent only when you complete
            the sale or forward it to an admin.
          </Text>
        </ScrollView>
      </KeyboardAvoidingView>

      <ScannerModal
        visible={scannerVisible}
        onClose={() => setScannerVisible(false)}
        onScanned={handleScannedSet}
        cartItems={draft.lines
          .filter((line) => line.setCode || line.brand || line.clothType)
          .map((line) => ({ id: line.localId, label: `${line.brand} ${line.clothType}`.trim(), total: money(lineTotal(line)) }))}
      />

      {manualPickerVisible ? (
        <ManualInventoryModal
          visible
          inventory={manualInventory}
          loading={manualLoading}
          error={manualError}
          search={manualSearch}
          onSearchChange={setManualSearch}
          onSelect={addManualInventoryItem}
          onClose={() => setManualPickerVisible(false)}
          onRetry={openManualPicker}
        />
      ) : null}

      <CustomerPickerModal
        visible={customerPickerVisible}
        customers={customerResults}
        loading={customerPickerLoading}
        error={customerPickerError}
        search={customerPickerSearch}
        onSearchChange={setCustomerPickerSearch}
        onSelect={selectCustomer}
        onClose={() => setCustomerPickerVisible(false)}
      />

      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Scan cloth set QR"
        onPress={() => setScannerVisible(true)}
        style={[styles.scanFloatingButton, { bottom: Math.max(insets.bottom, 16) + 8 }]}
      >
        <Text style={styles.scanFloatingIcon}>⌗</Text>
        <Text style={styles.scanFloatingText}>Scan QR</Text>
      </Pressable>
    </SafeAreaView>
  );
}

function CustomerPickerModal({
  visible,
  customers,
  loading,
  error,
  search,
  onSearchChange,
  onSelect,
  onClose,
}: {
  visible: boolean;
  customers: CustomerResult[];
  loading: boolean;
  error: string;
  search: string;
  onSearchChange: (value: string) => void;
  onSelect: (customer: CustomerResult) => void;
  onClose: () => void;
}) {
  return (
    <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
      <SafeAreaView style={styles.manualSafeArea}>
        <View style={styles.manualHeader}>
          <View style={styles.manualHeaderText}>
            <Text style={styles.manualTitle}>Choose customer</Text>
            <Text style={styles.manualSubtitle}>Only customers saved by this shop are shown.</Text>
          </View>
          <Pressable accessibilityRole="button" onPress={onClose} hitSlop={12}>
            <Text style={styles.manualClose}>Close</Text>
          </Pressable>
        </View>
        <TextInput
          accessibilityLabel="Search saved customers"
          value={search}
          onChangeText={onSearchChange}
          placeholder="Search name or phone"
          placeholderTextColor="#8796a1"
          style={styles.manualSearch}
        />
        <ScrollView contentContainerStyle={styles.manualList} keyboardShouldPersistTaps="handled">
          {loading ? <Text style={styles.manualState}>Loading saved customers…</Text> : null}
          {!loading && error ? <Text style={styles.manualErrorText}>{error}</Text> : null}
          {!loading && !error && customers.length === 0 ? (
            <Text style={styles.manualState}>No saved customer found. Choose New for a new customer.</Text>
          ) : null}
          {!loading && !error ? customers.map((customer) => (
            <Pressable
              accessibilityRole="button"
              key={customer.id}
              onPress={() => onSelect(customer)}
              style={styles.inventoryItem}
            >
              <View style={styles.inventoryText}>
                <Text style={styles.inventoryName}>{customer.name}</Text>
                <Text style={styles.inventoryMeta}>{customer.phone_number1 || 'No phone recorded'}</Text>
              </View>
              <Text style={styles.inventoryAdd}>Select</Text>
            </Pressable>
          )) : null}
        </ScrollView>
      </SafeAreaView>
    </Modal>
  );
}

function ManualInventoryModal({
  visible,
  inventory,
  loading,
  error,
  search,
  onSearchChange,
  onSelect,
  onClose,
  onRetry,
}: {
  visible: boolean;
  inventory: InventoryListItem[];
  loading: boolean;
  error: string;
  search: string;
  onSearchChange: (value: string) => void;
  onSelect: (item: InventoryListItem) => void;
  onClose: () => void;
  onRetry: () => void;
}) {
  const [selectedBrandId, setSelectedBrandId] = useState<number | null>(null);

  const query = search.trim().toLocaleLowerCase();
  const filtered = inventory.filter((item) =>
    !query || [item.setCode, item.brand, item.clothType]
      .some((value) => String(value ?? '').toLocaleLowerCase().includes(query)),
  );
  const brands = filtered.reduce<{ id: number; name: string }[]>((options, item) => {
    if (!options.some((option) => option.id === item.brandId)) {
      options.push({ id: item.brandId, name: item.brand });
    }
    return options;
  }, []);
  const brandStock = selectedBrandId
    ? filtered.filter((item) => item.brandId === selectedBrandId)
    : [];

  return (
    <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
      <SafeAreaView style={styles.manualSafeArea}>
        <View style={styles.manualHeader}>
          <View style={styles.manualHeaderText}>
            <Text style={styles.manualTitle}>Add cloth without QR</Text>
            <Text style={styles.manualSubtitle}>Use the same brand and cloth-type choices as the shop sale form.</Text>
          </View>
          <Pressable accessibilityRole="button" onPress={onClose} hitSlop={12}>
            <Text style={styles.manualClose}>Close</Text>
          </Pressable>
        </View>

        <TextInput
          accessibilityLabel="Search inventory"
          autoCapitalize="none"
          value={search}
          onChangeText={onSearchChange}
          placeholder="Search set code, brand, or cloth type"
          placeholderTextColor="#8796a1"
          style={styles.manualSearch}
        />

        <ScrollView contentContainerStyle={styles.manualList} keyboardShouldPersistTaps="handled">
          {loading ? <Text style={styles.manualState}>Loading shop inventory…</Text> : null}
          {!loading && error ? (
            <View style={styles.manualErrorCard}>
              <Text style={styles.manualErrorText}>{error}</Text>
              <SecondaryButton label="Try again" onPress={onRetry} fullWidth />
            </View>
          ) : null}
          {!loading && !error && filtered.length === 0 ? (
            <Text style={styles.manualState}>No matching inventory item found.</Text>
          ) : null}
          {!loading && !error && filtered.length ? (
            <>
              <Text style={styles.pickerStep}>1. Choose brand</Text>
              <View style={styles.choiceGrid}>
                {brands.map((brand) => (
                  <Pressable
                    accessibilityRole="button"
                    accessibilityState={{ selected: selectedBrandId === brand.id }}
                    key={brand.id}
                    onPress={() => setSelectedBrandId(brand.id)}
                    style={[styles.choiceButton, selectedBrandId === brand.id && styles.choiceButtonSelected]}
                  >
                    <Text style={[styles.choiceButtonText, selectedBrandId === brand.id && styles.choiceButtonTextSelected]}>{brand.name}</Text>
                  </Pressable>
                ))}
              </View>
              <Text style={styles.pickerStep}>2. Choose cloth type / model</Text>
              {!selectedBrandId ? <Text style={styles.pickerHint}>Choose a brand first.</Text> : null}
            </>
          ) : null}
          {!loading && !error ? brandStock.map((item) => {
            const availableLength = item.colors.reduce((total, color) => total + (Number(color.length) || 0), 0);
            return (
              <Pressable
                accessibilityRole="button"
                key={item.id}
                onPress={() => onSelect(item)}
                style={styles.inventoryItem}
              >
                <View style={styles.inventoryText}>
                  <Text style={styles.inventoryName}>{item.clothType}</Text>
                  <Text style={styles.inventoryMeta}>
                    {item.brand} · Set {item.setCode || 'without code'} · {item.colorTrackingMode === 'per_color' ? `${item.colors.length} colors` : 'no color tracking'} · {availableLength.toLocaleString('en-PK')} available
                  </Text>
                </View>
                <View style={styles.inventoryPriceWrap}>
                  <Text style={styles.inventoryPrice}>{money(Number(item.salePrice) || 0)}</Text>
                  <Text style={styles.inventoryAdd}>Select</Text>
                </View>
              </Pressable>
            );
          }) : null}
        </ScrollView>
      </SafeAreaView>
    </Modal>
  );
}

type FieldProps = {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  keyboardType?: 'default' | 'phone-pad' | 'decimal-pad';
  multiline?: boolean;
};

function Field({ label, multiline, ...props }: FieldProps) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        {...props}
        multiline={multiline}
        placeholderTextColor="#8796a1"
        style={[styles.input, multiline && styles.multilineInput]}
      />
    </View>
  );
}

function SelectionField({
  label,
  value,
  placeholder,
  onPress,
}: {
  label: string;
  value: string;
  placeholder: string;
  onPress: () => void;
}) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <Pressable accessibilityRole="button" onPress={onPress} style={styles.selectionField}>
        <Text style={[styles.selectionValue, !value && styles.selectionPlaceholder]} numberOfLines={1}>
          {value || placeholder}
        </Text>
        <Text style={styles.selectionChevron}>⌄</Text>
      </Pressable>
    </View>
  );
}

function LockedField({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <View style={styles.lockedField}>
        <Text style={styles.lockedValue}>{value}</Text>
        <Text style={styles.lockedBadge}>From stock</Text>
      </View>
    </View>
  );
}

function SaleLineCard({
  line,
  number,
  canRemove,
  onChange,
  onRemove,
  onChooseStock,
}: {
  line: SaleLine;
  number: number;
  canRemove: boolean;
  onChange: (patch: Partial<SaleLine>) => void;
  onRemove: () => void;
  onChooseStock: () => void;
}) {
  const hasStockItem = Boolean(line.brandId && line.clothTypeId);

  return (
    <Section title={`Item ${number}`}>
      {!hasStockItem ? (
        <View style={styles.emptyItem}>
          <Text style={styles.emptyItemTitle}>No stock item selected</Text>
          <Text style={styles.emptyItemHelp}>Scan its QR or choose it from the available shop stock.</Text>
          <SecondaryButton label="Choose stock item" onPress={onChooseStock} fullWidth />
        </View>
      ) : null}
      {hasStockItem && line.setCode ? <Text style={styles.scanned}>Stock set: {line.setCode}</Text> : null}
      {hasStockItem ? <LockedField label="Brand" value={line.brand} /> : null}
      {hasStockItem ? <LockedField label="Cloth type / model" value={line.clothType} /> : null}
      {hasStockItem && line.requiresColor ? (
        <>
          <Text style={styles.fieldLabel}>Color *</Text>
          {!line.color && line.availableColors.length > 1 ? (
            <Text style={styles.help}>This stock has multiple colors. Select the sold color manually.</Text>
          ) : null}
        <View style={styles.colorChoices}>
          {line.availableColors.map((color) => (
            <Pressable key={color} onPress={() => onChange({ color })} style={[styles.colorChoice, line.color === color && styles.colorChoiceSelected]}>
              <Text style={[styles.colorChoiceText, line.color === color && styles.colorChoiceTextSelected]}>{color}</Text>
            </Pressable>
          ))}
        </View>
        </>
      ) : null}
      {hasStockItem && !line.requiresColor ? (
        <Text style={styles.help}>This set is stored without color tracking. No color selection is needed.</Text>
      ) : null}
      {hasStockItem ? (
        <>
          <View style={styles.twoColumns}>
            <View style={styles.column}>
              <Field label="Quantity" value={line.quantity} onChangeText={(value) => onChange({ quantity: value })} keyboardType="decimal-pad" />
            </View>
            <View style={styles.column}>
              <Field label="Length" value={line.length} onChangeText={(value) => onChange({ length: value })} keyboardType="decimal-pad" />
            </View>
          </View>
          <View style={styles.twoColumns}>
            <View style={styles.column}>
              <Field label="Unit price" value={line.unitPrice} onChangeText={(value) => onChange({ unitPrice: value })} keyboardType="decimal-pad" />
            </View>
            <View style={styles.column}>
              <Text style={styles.itemTotal}>Item total: {money(lineTotal(line))}</Text>
            </View>
          </View>
        </>
      ) : null}
      {canRemove && (
        <Pressable accessibilityRole="button" onPress={onRemove}>
          <Text style={styles.removeText}>Remove item</Text>
        </Pressable>
      )}
    </Section>
  );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {children}
    </View>
  );
}

function Segment({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ selected }}
      onPress={onPress}
      style={[styles.segment, selected && styles.segmentSelected]}
    >
      <Text style={[styles.segmentText, selected && styles.segmentTextSelected]}>{label}</Text>
    </Pressable>
  );
}

function StatusChip({ label, tone }: { label: string; tone: 'green' | 'blue' | 'red' }) {
  const palette = {
    green: { background: '#e7f6ef', color: GREEN },
    blue: { background: '#eaf2f8', color: BLUE },
    red: { background: '#fdeceb', color: '#b42318' },
  }[tone];

  return (
    <View style={[styles.statusChip, { backgroundColor: palette.background }]}>
      <Text style={[styles.statusText, { color: palette.color }]}>{label}</Text>
    </View>
  );
}

type ButtonProps = { label: string; onPress: () => void; fullWidth?: boolean };

function PrimaryButton({ label, onPress, fullWidth }: ButtonProps) {
  return (
    <Pressable accessibilityRole="button" onPress={onPress} style={[styles.primaryButton, fullWidth && styles.fullWidth]}>
      <Text style={styles.primaryButtonText}>{label}</Text>
    </Pressable>
  );
}

function SecondaryButton({ label, onPress, fullWidth }: ButtonProps) {
  return (
    <Pressable accessibilityRole="button" onPress={onPress} style={[styles.secondaryButton, fullWidth && styles.fullWidth]}>
      <Text style={styles.secondaryButtonText}>{label}</Text>
    </Pressable>
  );
}

function MoneyRow({ label, value, emphasized }: { label: string; value: number; emphasized?: boolean }) {
  return (
    <View style={styles.moneyRow}>
      <Text style={styles.moneyLabel}>{label}</Text>
      <Text style={[styles.moneyValue, emphasized && styles.moneyEmphasized]}>{money(value)}</Text>
    </View>
  );
}

const saveLabel = (state: 'loading' | 'saving' | 'saved' | 'failed') => ({
  loading: 'Loading…',
  saving: 'Saving…',
  saved: 'Saved on phone',
  failed: 'Save failed',
}[state]);

const syncLabel = (state: 'waiting' | 'syncing' | 'offline' | 'conflict' | 'claimed') => ({
  waiting: '', syncing: '',
  offline: 'Offline — queued', conflict: 'Changed elsewhere', claimed: 'Admin handling',
}[state]);

const titleCase = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);
const money = (value: number) => `Rs. ${value.toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const uniqueColors = (colors: string[]) => [...new Set(colors.map((color) => color.trim()).filter(Boolean))];
const defaultColor = (colors: string[], requiresColor: boolean) => requiresColor && colors.length === 1 ? colors[0] : '';

const styles = StyleSheet.create({
  flex: { flex: 1 },
  safeArea: { flex: 1, backgroundColor: BACKGROUND },
  content: { padding: 16, gap: 14 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12, marginBottom: 4 },
  headerText: { flex: 1 },
  eyebrow: { color: GREEN, fontWeight: '900', letterSpacing: 1.8, fontSize: 12 },
  heading: { color: BLUE, fontSize: 30, fontWeight: '900', marginTop: 2 },
  permission: { color: '#687986', fontSize: 11, marginTop: 4 },
  statuses: { alignItems: 'flex-end', gap: 6 },
  statusChip: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  statusText: { fontSize: 11, fontWeight: '800' },
  section: { backgroundColor: '#fff', borderRadius: 18, padding: 16, gap: 11, borderWidth: 1, borderColor: '#e4eaee' },
  sectionTitle: { color: BLUE, fontWeight: '900', fontSize: 18, marginBottom: 2 },
  segmentRow: { flexDirection: 'row', gap: 6 },
  segment: { flex: 1, borderWidth: 1, borderColor: '#b9c6cf', borderRadius: 10, alignItems: 'center', paddingVertical: 10 },
  segmentSelected: { backgroundColor: BLUE, borderColor: BLUE },
  segmentText: { color: BLUE, fontWeight: '700', fontSize: 12 },
  segmentTextSelected: { color: '#fff' },
  fieldWrap: { gap: 6 },
  fieldLabel: { color: '#425460', fontWeight: '700', fontSize: 12 },
  input: { borderWidth: 1, borderColor: '#c9d3da', borderRadius: 11, backgroundColor: '#fff', color: '#172b3a', paddingHorizontal: 12, paddingVertical: 11, fontSize: 15 },
  multilineInput: { minHeight: 78, textAlignVertical: 'top' },
  selectionField: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10, borderWidth: 1, borderColor: '#b9c6cf', borderRadius: 11, backgroundColor: '#fff', paddingHorizontal: 13, paddingVertical: 11 },
  selectionValue: { flex: 1, color: '#172b3a', fontSize: 14, fontWeight: '700' },
  selectionPlaceholder: { color: '#8796a1', fontWeight: '500' },
  selectionChevron: { color: BLUE, fontSize: 19, fontWeight: '900' },
  lockedField: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10, borderWidth: 1, borderColor: '#d8e2e7', borderRadius: 11, backgroundColor: '#f4f8fa', paddingHorizontal: 12, paddingVertical: 10 },
  lockedValue: { flex: 1, color: BLUE, fontSize: 14, fontWeight: '900' },
  lockedBadge: { color: GREEN, backgroundColor: '#e7f6ef', borderRadius: 999, paddingHorizontal: 8, paddingVertical: 4, fontSize: 10, fontWeight: '900' },
  help: { color: '#61727f', lineHeight: 20 },
  customerResult: { borderWidth: 1, borderColor: '#d4dde3', borderRadius: 10, padding: 10, backgroundColor: '#f8fafb' },
  customerResultSelected: { borderColor: GREEN, backgroundColor: '#e7f6ef' },
  customerResultName: { color: BLUE, fontWeight: '800' },
  customerResultPhone: { color: '#687986', fontSize: 12, marginTop: 2 },
  selectedCustomer: { color: GREEN, fontWeight: '800', fontSize: 12 },
  colorChoices: { flexDirection: 'row', flexWrap: 'wrap', gap: 7 },
  colorChoice: { borderWidth: 1, borderColor: '#c9d3da', borderRadius: 999, paddingHorizontal: 12, paddingVertical: 7 },
  colorChoiceSelected: { backgroundColor: BLUE, borderColor: BLUE },
  colorChoiceText: { color: BLUE, fontSize: 12, fontWeight: '700' },
  colorChoiceTextSelected: { color: '#fff' },
  choiceGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 7 },
  choiceButton: { borderWidth: 1, borderColor: '#c9d3da', borderRadius: 10, paddingHorizontal: 12, paddingVertical: 9, backgroundColor: '#fff' },
  choiceButtonSelected: { borderColor: BLUE, backgroundColor: BLUE },
  choiceButtonText: { color: BLUE, fontWeight: '800', fontSize: 12 },
  choiceButtonTextSelected: { color: '#fff' },
  pickerStep: { color: BLUE, fontWeight: '900', fontSize: 14, marginTop: 3 },
  pickerHint: { color: '#687986', fontSize: 12, paddingVertical: 8 },
  cartHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 2, marginTop: 2 },
  cartTitle: { color: BLUE, fontSize: 18, fontWeight: '900' },
  cartCount: { color: GREEN, fontSize: 12, fontWeight: '800' },
  emptyCart: { gap: 12, borderWidth: 1, borderStyle: 'dashed', borderColor: '#b9c6cf', borderRadius: 14, backgroundColor: '#fff', padding: 16 },
  emptyCartTitle: { color: BLUE, fontWeight: '900', fontSize: 16 },
  emptyCartHelp: { color: '#687986', lineHeight: 20, fontSize: 13 },
  primaryButton: { flex: 1, backgroundColor: GREEN, borderRadius: 12, paddingHorizontal: 16, paddingVertical: 14, alignItems: 'center', justifyContent: 'center' },
  primaryButtonText: { color: '#fff', fontWeight: '900' },
  secondaryButton: { flex: 1, backgroundColor: '#fff', borderWidth: 1, borderColor: BLUE, borderRadius: 12, paddingHorizontal: 16, paddingVertical: 13, alignItems: 'center', justifyContent: 'center' },
  secondaryButtonText: { color: BLUE, fontWeight: '900' },
  fullWidth: { width: '100%', flex: 0 },
  scanned: { color: GREEN, fontWeight: '800', backgroundColor: '#e7f6ef', padding: 10, borderRadius: 9 },
  emptyItem: { gap: 10, borderWidth: 1, borderStyle: 'dashed', borderColor: '#b9c6cf', borderRadius: 13, backgroundColor: '#f8fafb', padding: 14 },
  emptyItemTitle: { color: BLUE, fontWeight: '900', fontSize: 15 },
  emptyItemHelp: { color: '#687986', lineHeight: 19, fontSize: 12 },
  twoColumns: { flexDirection: 'row', gap: 10 },
  column: { flex: 1 },
  itemTotal: { color: BLUE, fontWeight: '900', textAlign: 'right' },
  removeText: { color: '#b42318', fontWeight: '800', textAlign: 'center', paddingVertical: 5 },
  totalCard: { backgroundColor: '#e7f6ef', borderRadius: 18, padding: 16, gap: 9 },
  moneyRow: { flexDirection: 'row', justifyContent: 'space-between' },
  moneyLabel: { color: '#3b5249' },
  moneyValue: { color: BLUE, fontWeight: '900' },
  moneyEmphasized: { color: GREEN, fontSize: 17 },
  clearText: { color: '#b42318', textAlign: 'center', fontWeight: '700', paddingVertical: 6 },
  signOutText: { color: BLUE, textAlign: 'center', fontWeight: '800', paddingVertical: 6 },
  boundaryNote: { color: '#687986', lineHeight: 19, fontSize: 12, textAlign: 'center', paddingHorizontal: 8 },
  manualSafeArea: { flex: 1, backgroundColor: BACKGROUND },
  manualHeader: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12, paddingHorizontal: 18, paddingTop: 10, paddingBottom: 14 },
  manualHeaderText: { flex: 1 },
  manualTitle: { color: BLUE, fontSize: 24, fontWeight: '900' },
  manualSubtitle: { color: '#687986', marginTop: 3 },
  manualClose: { color: GREEN, fontWeight: '900', paddingTop: 6 },
  manualSearch: { marginHorizontal: 18, borderWidth: 1, borderColor: '#c9d3da', borderRadius: 12, backgroundColor: '#fff', color: '#172b3a', paddingHorizontal: 14, paddingVertical: 12, fontSize: 15 },
  manualList: { padding: 18, paddingBottom: 40, gap: 10 },
  manualState: { color: '#687986', textAlign: 'center', paddingVertical: 34 },
  manualErrorCard: { backgroundColor: '#fdeceb', borderRadius: 14, padding: 15, gap: 12 },
  manualErrorText: { color: '#b42318', textAlign: 'center', lineHeight: 20 },
  inventoryItem: { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#fff', borderWidth: 1, borderColor: '#e0e7eb', borderRadius: 14, padding: 14 },
  inventoryText: { flex: 1, gap: 4 },
  inventoryName: { color: BLUE, fontWeight: '900', fontSize: 15 },
  inventoryMeta: { color: '#687986', fontSize: 12, lineHeight: 17 },
  inventoryPriceWrap: { alignItems: 'flex-end', gap: 4 },
  inventoryPrice: { color: BLUE, fontWeight: '900', fontSize: 13 },
  inventoryAdd: { color: GREEN, fontWeight: '900', fontSize: 12 },
  scanFloatingButton: {
    position: 'absolute', left: 18, right: 18, bottom: 16, minHeight: 58,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 9,
    borderRadius: 18, backgroundColor: GREEN, borderWidth: 2, borderColor: '#fff',
    ...Platform.select({
      web: { boxShadow: '0 8px 28px rgba(11, 47, 36, 0.28)' },
      default: { shadowColor: '#0b2f24', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.28, shadowRadius: 14, elevation: 9 },
    }),
  },
  scanFloatingIcon: { color: '#fff', fontWeight: '900', fontSize: 22 },
  scanFloatingText: { color: '#fff', fontWeight: '900', fontSize: 16 },
});
