import * as Updates from 'expo-updates';
import { useCallback, useEffect, useRef, useState } from 'react';
import { AppState, Modal, Platform, Pressable, StyleSheet, Text, View } from 'react-native';

const CHECK_INTERVAL_MS = 5 * 60 * 1000;

export function AppUpdateModal() {
  const [visible, setVisible] = useState(false);
  const [installing, setInstalling] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const checkingRef = useRef(false);
  const lastCheckedAtRef = useRef(0);

  const checkForUpdate = useCallback(async (force = false) => {
    if (__DEV__ || Platform.OS === 'web' || !Updates.isEnabled || checkingRef.current) return;

    const now = Date.now();
    if (!force && now - lastCheckedAtRef.current < CHECK_INTERVAL_MS) return;

    checkingRef.current = true;
    lastCheckedAtRef.current = now;

    try {
      const result = await Updates.checkForUpdateAsync();
      if (result.isAvailable) {
        setError(null);
        setVisible(true);
      }
    } catch {
      // Update checks must never interrupt sales when the device is offline.
    } finally {
      checkingRef.current = false;
    }
  }, []);

  useEffect(() => {
    const initialCheck = setTimeout(() => {
      void checkForUpdate(true);
    }, 0);

    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') void checkForUpdate();
    });

    return () => {
      clearTimeout(initialCheck);
      subscription.remove();
    };
  }, [checkForUpdate]);

  const installUpdate = async () => {
    if (installing) return;

    setInstalling(true);
    setError(null);
    try {
      await Updates.fetchUpdateAsync();
      await Updates.reloadAsync();
    } catch {
      setError('The update could not be downloaded. Check your internet connection and try again.');
      setInstalling(false);
    }
  };

  return (
    <Modal
      animationType="fade"
      onRequestClose={() => {
        if (!installing) setVisible(false);
      }}
      transparent
      visible={visible}
    >
      <View style={styles.backdrop}>
        <View accessibilityViewIsModal style={styles.card}>
          <View style={styles.icon}>
            <Text style={styles.iconText}>↻</Text>
          </View>
          <Text style={styles.title}>Update available</Text>
          <Text style={styles.message}>
            A new BuyNStitch version is ready. Update now to get the latest improvements without reinstalling the app.
          </Text>
          {error ? <Text style={styles.error}>{error}</Text> : null}
          <Pressable
            accessibilityRole="button"
            disabled={installing}
            onPress={installUpdate}
            style={({ pressed }) => [styles.updateButton, pressed && !installing && styles.pressed]}
          >
            <Text style={styles.updateButtonText}>{installing ? 'Updating…' : 'Update now'}</Text>
          </Pressable>
          <Pressable
            accessibilityRole="button"
            disabled={installing}
            onPress={() => setVisible(false)}
            style={styles.laterButton}
          >
            <Text style={styles.laterButtonText}>Later</Text>
          </Pressable>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(8, 28, 42, 0.62)',
    padding: 24,
  },
  card: {
    width: '100%',
    maxWidth: 420,
    borderRadius: 22,
    backgroundColor: '#fff',
    padding: 24,
    alignItems: 'center',
  },
  icon: {
    width: 58,
    height: 58,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#e7f5ef',
    marginBottom: 16,
  },
  iconText: { color: '#147a5a', fontSize: 32, fontWeight: '900' },
  title: { color: '#123b5d', fontSize: 23, fontWeight: '900', textAlign: 'center' },
  message: {
    color: '#536672',
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    marginTop: 10,
    marginBottom: 20,
  },
  error: { color: '#b42318', fontSize: 13, lineHeight: 18, textAlign: 'center', marginBottom: 14 },
  updateButton: {
    width: '100%',
    alignItems: 'center',
    borderRadius: 12,
    backgroundColor: '#147a5a',
    paddingVertical: 15,
  },
  pressed: { opacity: 0.82 },
  updateButtonText: { color: '#fff', fontSize: 15, fontWeight: '900' },
  laterButton: { paddingHorizontal: 18, paddingTop: 16, paddingBottom: 2 },
  laterButtonText: { color: '#536672', fontSize: 14, fontWeight: '800' },
});
