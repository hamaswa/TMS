import { router } from 'expo-router';
import { useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiError, connectionProfileFrom, login as apiLogin } from '../services/api';
import { setToken } from '../storage/authStorage';
import { setConnectionProfile } from '../storage/connectionStorage';

const BLUE = '#123b5d';
const GREEN = '#147a5a';

export function LoginScreen() {
  const passwordInputRef = useRef<TextInput>(null);
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [shopServer, setShopServer] = useState('');
  const [showShopServer, setShowShopServer] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const signIn = async () => {
    if (!login.trim() || !password) {
      setMessage('Enter the employee username/email and password.');
      return;
    }
    setSubmitting(true);
    setMessage(null);
    try {
      const selectedServer = shopServer.trim() || undefined;
      const result = await apiLogin(login.trim(), password, selectedServer);
      await setConnectionProfile(connectionProfileFrom(selectedServer, result.connection));
      await setToken(result.token, result.user.id);
      router.replace('/sale');
    } catch (error) {
      setMessage(error instanceof ApiError ? error.message : 'Could not reach the server. Check the connection and try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <KeyboardAvoidingView
        style={styles.keyboardArea}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          contentContainerStyle={styles.page}
          keyboardDismissMode="on-drag"
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.brandMark}>
            <Text style={styles.brandMarkText}>B&S</Text>
          </View>
          <Text style={styles.eyebrow}>BUYNSTITCH</Text>
          <Text style={styles.title}>Sales Agent</Text>
          <Text style={styles.subtitle}>
            Sign in with the same employee account used by the shop dashboard.
          </Text>

          <Text style={styles.connectionHelp}>
            When the internet is unavailable, enter the Shop Hub address shown on the admin computer.
          </Text>

          <Pressable
            accessibilityRole="button"
            accessibilityState={{ expanded: showShopServer }}
            onPress={() => setShowShopServer((visible) => !visible)}
            style={styles.connectionToggle}
          >
            <Text style={styles.connectionToggleText}>
              {showShopServer ? 'Hide Shop Hub address' : 'Use a Shop Hub address'}
            </Text>
          </Pressable>

          <View style={styles.card}>
            {showShopServer ? (
              <View style={styles.fieldWrap}>
                <Text style={styles.label}>Shop Hub address</Text>
                <TextInput
                  autoCapitalize="none"
                  autoCorrect={false}
                  keyboardType="url"
                  value={shopServer}
                  onChangeText={setShopServer}
                  placeholder="http://192.168.1.10:8010/api"
                  placeholderTextColor="#8796a1"
                  style={styles.input}
                />
              </View>
            ) : null}
            <View style={styles.fieldWrap}>
              <Text style={styles.label}>Username or email</Text>
              <TextInput
                autoCapitalize="none"
                autoComplete="username"
                blurOnSubmit={false}
                onSubmitEditing={() => passwordInputRef.current?.focus()}
                returnKeyType="next"
                value={login}
                onChangeText={setLogin}
                placeholder="Employee username or email"
                placeholderTextColor="#8796a1"
                style={styles.input}
              />
            </View>
            <View style={styles.fieldWrap}>
              <Text style={styles.label}>Password</Text>
              <TextInput
                ref={passwordInputRef}
                autoCapitalize="none"
                autoComplete="current-password"
                onSubmitEditing={signIn}
                returnKeyType="done"
                secureTextEntry
                value={password}
                onChangeText={setPassword}
                placeholder="Password"
                placeholderTextColor="#8796a1"
                style={styles.input}
              />
            </View>
            {message ? <Text style={styles.validation}>{message}</Text> : null}
            <Pressable accessibilityRole="button" disabled={submitting} onPress={signIn} style={styles.button}>
              <Text style={styles.buttonText}>{submitting ? 'Signing in…' : 'Sign in'}</Text>
            </Pressable>
          </View>

          <Text style={styles.permission}>Existing required permission: clothing.sales</Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#f4f7f9' },
  keyboardArea: { flex: 1 },
  page: {
    flexGrow: 1,
    width: '100%',
    maxWidth: 500,
    alignSelf: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    paddingVertical: 20,
  },
  brandMark: {
    width: 68,
    height: 68,
    borderRadius: 22,
    backgroundColor: BLUE,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
    ...Platform.select({
      web: { boxShadow: '0 8px 30px rgba(18, 59, 93, 0.18)' },
      default: {
        shadowColor: '#123b5d',
        shadowOpacity: 0.18,
        shadowRadius: 18,
        shadowOffset: { width: 0, height: 8 },
      },
    }),
  },
  brandMarkText: { color: '#fff', fontSize: 22, fontWeight: '900' },
  eyebrow: { color: GREEN, fontSize: 12, fontWeight: '900', letterSpacing: 2 },
  title: { color: BLUE, fontSize: 38, fontWeight: '900', marginTop: 3 },
  subtitle: { color: '#536672', fontSize: 16, lineHeight: 23, marginTop: 8, marginBottom: 14 },
  connectionHelp: { color: '#536672', fontSize: 13, lineHeight: 19, marginBottom: 12 },
  connectionToggle: { alignSelf: 'flex-start', marginBottom: 12, paddingVertical: 2 },
  connectionToggleText: { color: GREEN, fontSize: 13, fontWeight: '800' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#e1e8ec',
    padding: 18,
    gap: 15,
  },
  fieldWrap: { gap: 7 },
  label: { color: '#425460', fontSize: 13, fontWeight: '800' },
  fieldHint: { color: '#768691', fontSize: 11, lineHeight: 16 },
  input: {
    borderColor: '#c7d2d9',
    borderWidth: 1,
    borderRadius: 12,
    color: '#172b3a',
    fontSize: 16,
    paddingHorizontal: 14,
    paddingVertical: 13,
  },
  validation: { color: '#b42318', fontSize: 13, lineHeight: 18 },
  button: { backgroundColor: GREEN, borderRadius: 12, alignItems: 'center', paddingVertical: 15 },
  buttonText: { color: '#fff', fontWeight: '900', fontSize: 15 },
  permission: { color: '#768691', fontSize: 12, textAlign: 'center', marginTop: 16 },
});
