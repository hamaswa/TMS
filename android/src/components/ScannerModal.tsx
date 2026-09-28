import { CameraView, useCameraPermissions } from 'expo-camera';
import { useRef, useState } from 'react';
import {
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

type Props = {
  visible: boolean;
  onClose: () => void;
  onScanned: (code: string) => boolean | Promise<boolean>;
  cartItems: { id: string; label: string; total: string }[];
};

export function ScannerModal({ visible, onClose, onScanned, cartItems }: Props) {
  const [permission, requestPermission] = useCameraPermissions();
  const [locked, setLocked] = useState(false);
  const [scanHint, setScanHint] = useState('Place one code inside the frame and hold steady');
  const candidate = useRef<{ data: string; firstSeenAt: number; lastSeenAt: number } | null>(null);
  const lastCaptured = useRef<{ data: string; at: number } | null>(null);
  const scanLocked = useRef(false);

  const resetScanner = () => {
    candidate.current = null;
    scanLocked.current = false;
    setLocked(false);
    setScanHint('Place one code inside the frame and hold steady');
  };

  const close = () => {
    resetScanner();
    onClose();
  };

  const observeCode = (data: string) => {
    if (!data || scanLocked.current) return;

    const now = Date.now();
    if (lastCaptured.current?.data === data && now - lastCaptured.current.at < 5000) {
      setScanHint('Item added — move to the next code');
      return;
    }
    const previous = candidate.current;
    if (!previous || previous.data !== data || now - previous.lastSeenAt > 400) {
      candidate.current = { data, firstSeenAt: now, lastSeenAt: now };
      setScanHint('Code found — hold steady…');
      return;
    }

    candidate.current = { ...previous, lastSeenAt: now };
    const focusedFor = now - previous.firstSeenAt;
    if (focusedFor < 1200) {
      setScanHint(`Hold steady… ${Math.max(1, Math.ceil((1200 - focusedFor) / 400))}`);
      return;
    }

    scanLocked.current = true;
    setLocked(true);
    setScanHint('Adding item…');
    lastCaptured.current = { data, at: now };
    Promise.resolve(onScanned(data)).then((added) => {
      candidate.current = null;
      scanLocked.current = false;
      setLocked(false);
      setScanHint(added ? 'Item added — scan the next code' : 'Not added — try another code');
    });
  };

  return (
    <Modal visible={visible} animationType="slide" onShow={resetScanner} onRequestClose={close}>
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <View>
            <Text style={styles.title}>Scan cloth set</Text>
            <Text style={styles.subtitle}>Point at the set QR or barcode</Text>
          </View>
          <Pressable accessibilityRole="button" onPress={close} style={styles.closeButton}>
            <Text style={styles.closeText}>Close</Text>
          </Pressable>
        </View>

        {!permission?.granted ? (
          <View style={styles.permissionPanel}>
            <Text style={styles.permissionTitle}>Camera permission is required</Text>
            <Text style={styles.permissionText}>
              BuyNStitch uses the camera only while this scanner is open.
            </Text>
            <Pressable accessibilityRole="button" onPress={requestPermission} style={styles.primaryButton}>
              <Text style={styles.primaryButtonText}>Allow camera</Text>
            </Pressable>
          </View>
        ) : (
          <View style={styles.cameraFrame}>
            <CameraView
              style={StyleSheet.absoluteFill}
              facing="back"
              barcodeScannerSettings={{
                barcodeTypes: ['qr', 'code128', 'ean13', 'ean8'],
              }}
              onBarcodeScanned={
                locked
                  ? undefined
                  : ({ data }) => observeCode(data)
              }
            />
            <View pointerEvents="none" style={styles.guide} />
            <View pointerEvents="none" style={styles.bottomPanel}>
              <Text style={styles.scanHint}>{scanHint}</Text>
              <Text style={styles.cartTitle}>{cartItems.length} items in cart</Text>
              {cartItems.slice(-3).map((item) => (
                <View key={item.id} style={styles.cartRow}>
                  <Text numberOfLines={1} style={styles.cartItemLabel}>{item.label || 'Scanned cloth set'}</Text>
                  <Text style={styles.cartItemTotal}>{item.total}</Text>
                </View>
              ))}
            </View>
          </View>
        )}
      </SafeAreaView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0b1f2d' },
  header: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  title: { color: '#fff', fontSize: 22, fontWeight: '800' },
  subtitle: { color: '#b7c7d3', marginTop: 3 },
  closeButton: { paddingHorizontal: 16, paddingVertical: 10 },
  closeText: { color: '#fff', fontWeight: '700' },
  cameraFrame: { flex: 1, overflow: 'hidden' },
  guide: {
    position: 'absolute',
    alignSelf: 'center',
    top: '28%',
    width: '76%',
    aspectRatio: 1,
    borderWidth: 3,
    borderColor: '#50d6a0',
    borderRadius: 24,
  },
  bottomPanel: {
    position: 'absolute',
    alignSelf: 'center',
    bottom: 24,
    width: '90%',
    maxWidth: '88%',
    paddingHorizontal: 16,
    paddingVertical: 13,
    borderRadius: 18,
    backgroundColor: 'rgba(6, 22, 32, .82)',
    gap: 6,
  },
  scanHint: { color: '#fff', fontSize: 15, fontWeight: '800', textAlign: 'center' },
  cartTitle: { color: '#50d6a0', fontSize: 13, fontWeight: '900', marginTop: 2 },
  cartRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 10 },
  cartItemLabel: { color: '#dce7ee', fontSize: 12, flex: 1 },
  cartItemTotal: { color: '#fff', fontSize: 12, fontWeight: '800' },
  permissionPanel: {
    margin: 24,
    padding: 24,
    borderRadius: 20,
    backgroundColor: '#fff',
    gap: 12,
  },
  permissionTitle: { fontSize: 19, fontWeight: '800', color: '#123b5d' },
  permissionText: { color: '#516371', lineHeight: 21 },
  primaryButton: {
    alignItems: 'center',
    backgroundColor: '#147a5a',
    borderRadius: 12,
    paddingVertical: 13,
    marginTop: 6,
  },
  primaryButtonText: { color: '#fff', fontWeight: '800' },
});
