import type React from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { getToken, getUser, homeFor } from './api'
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
import GateScanner from './pages/GateScanner'
import Budgets from './pages/Budgets'
import WorkOrders from './pages/WorkOrders'
import ClientPortal from './pages/ClientPortal'
import VendorPortal from './pages/VendorPortal'
import Reports from './pages/Reports'
import Admin from './pages/Admin'
import Analytics from './pages/Analytics'
import PublicBooking from './pages/PublicBooking'

function RequireAuth({ children }: { children: React.ReactElement }) {
  return getToken() ? children : <Navigate to="/login" replace />
}

function HomeRedirect() {
  return <Navigate to={homeFor(getUser()?.role)} replace />
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/book/:eventId" element={<PublicBooking />} />
      <Route
        path="/"
        element={
          <RequireAuth>
            <Layout />
          </RequireAuth>
        }
      >
        <Route index element={<HomeRedirect />} />
        <Route path="dashboard" element={<Dashboard />} />
        <Route path="analytics" element={<Analytics />} />
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
        <Route path="gate" element={<GateScanner />} />
        <Route path="budgets" element={<Budgets />} />
        <Route path="work-orders" element={<WorkOrders />} />
        <Route path="portal/client" element={<ClientPortal />} />
        <Route path="portal/vendor" element={<VendorPortal />} />
        <Route path="reports" element={<Reports />} />
        <Route path="admin" element={<Admin />} />
      </Route>
      <Route path="*" element={<HomeRedirect />} />
    </Routes>
  )
}
