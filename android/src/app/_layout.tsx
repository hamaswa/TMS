import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { AppUpdateModal } from '../components/AppUpdateModal';

export default function RootLayout() {
  return (
    <>
      <StatusBar style="dark" />
      <Stack screenOptions={{ headerShown: false }} />
      <AppUpdateModal />
    </>
  );
}
