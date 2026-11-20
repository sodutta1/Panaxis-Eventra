import type React from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { getToken } from './api'
import Layout from './components/Layout'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import Events from './pages/Events'
import EventDetail from './pages/EventDetail'
import Leads from './pages/Leads'
import Clients from './pages/Clients'
import Venues from './pages/Venues'
import Vendors from './pages/Vendors'
import Quotations from './pages/Quotations'
import Invoices from './pages/Invoices'
import Expenses from './pages/Expenses'
import Guests from './pages/Guests'
import Tickets from './pages/Tickets'

function RequireAuth({ children }: { children: React.ReactElement }) {
  return getToken() ? children : <Navigate to="/login" replace />
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/"
        element={
          <RequireAuth>
            <Layout />
          </RequireAuth>
        }
      >
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<Dashboard />} />
        <Route path="events" element={<Events />} />
        <Route path="events/:id" element={<EventDetail />} />
        <Route path="leads" element={<Leads />} />
        <Route path="clients" element={<Clients />} />
        <Route path="venues" element={<Venues />} />
        <Route path="vendors" element={<Vendors />} />
        <Route path="quotations" element={<Quotations />} />
        <Route path="invoices" element={<Invoices />} />
        <Route path="expenses" element={<Expenses />} />
        <Route path="guests" element={<Guests />} />
        <Route path="tickets" element={<Tickets />} />
      </Route>
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
