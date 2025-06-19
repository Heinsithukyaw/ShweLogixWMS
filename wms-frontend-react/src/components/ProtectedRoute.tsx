import React from 'react'
import { Navigate } from 'react-router-dom'
import { authGuard } from '../middleware'

type Props = {
  children: React.ReactNode
}

const ProtectedRoute: React.FC<Props> = ({ children }) => {
  const isAuthenticated = authGuard()

  return isAuthenticated ? children : <Navigate to="/signin" replace />
}

export default ProtectedRoute
