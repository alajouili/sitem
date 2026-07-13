import { AuthProvider } from './context/AuthContext';
import { RBACProvider } from './context/RBACContext';
import AppRouter from './routes/AppRouter';

export default function App() {
  return (
    <AuthProvider>
      <RBACProvider>
        <AppRouter />
      </RBACProvider>
    </AuthProvider>
  );
}