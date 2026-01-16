import { AuthProvider } from './contexts/AuthContext'
import MobileApp from './MobileApp'

export default function App() {
  return (
    <AuthProvider>
      <MobileApp />
    </AuthProvider>
  )
}
