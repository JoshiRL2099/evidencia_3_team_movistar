<template>
  <!-- Búsqueda de pedido (vista inicial) - estructura según login.blade.php -->
  <div v-if="!showDetails" class="lookup-page">
    <div class="hero" aria-hidden="true"></div>

    <div class="card shadow-sm lookup-card" role="region" aria-label="Consulta pública de pedidos">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <img src="http://127.0.0.1:8000/images/boxhalcon-logo.png" alt="BOX HALCON" style="max-width:160px;" />
        </div>

        <h3 class="card-title text-center mb-2">Consulta pública de pedidos</h3>
        <p class="text-muted text-center small mb-4">Ingresa tu número de pedido y número de cliente para consultar el
          estado.</p>

        <form @submit.prevent="lookupOrder">
          <div class="mb-3">
            <label for="invoice_number" class="form-label">Número de pedido</label>
            <input id="invoice_number" type="text" class="form-control" v-model="form.invoice_number"
              placeholder="Ej. FAC-1001" />
          </div>

          <div class="mb-3">
            <label for="customer_number" class="form-label">Número de cliente</label>
            <input id="customer_number" type="text" class="form-control" v-model="form.customer_number"
              placeholder="Ej. CL-10001" />
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary" :disabled="loading">{{ loading ? 'Consultando...' : 'Buscar'
            }}</button>
          </div>
        </form>

        <div v-if="errorMessage" class="alert error mt-3">{{ errorMessage }}</div>
        <div v-if="successMessage" class="alert success mt-3">{{ successMessage }}</div>
      </div>
    </div>
  </div>

  <!-- Detalles del pedido (vista después de búsqueda) -->
  <div v-else class="page-details">
    <div class="header">
      <button class="menu-toggle" @click="toggleMenu">☰</button>
      <div class="header-title">ORDER DETAILS</div>
      <div class="header-user">
        <div class="user-circle">{{ getUserInitial }}</div>
        <div class="user-info">
          <div class="user-name">{{ getUserName }}</div>
          <div class="user-role">User</div>
        </div>
      </div>
    </div>

    <div class="layout">
      <!-- Sidebar -->
      <aside class="sidebar" :class="{ 'show-menu': showSidebar }">
        <div class="sidebar-header">
          <div class="logo">📦</div>
        </div>
        <nav class="sidebar-nav">
          <div class="nav-item active">📊 Dashboard</div>
          <div class="nav-item">⚙️ Settings</div>
        </nav>
      </aside>

      <!-- Contenido principal -->
      <main class="content">
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
          <h1 class="welcome-title">Welcome, {{ getUserName }}.</h1>
          <p class="welcome-subtitle">Here you will see a summary of the order status information.</p>
        </div>

        <!-- Sección de estado -->
        <div class="order-status-section">
          <h2 class="section-title">ORDER STATUS</h2>
          <div class="status-cards">
            <div class="status-card">
              <div class="card-label">Customer Number</div>
              <div class="card-value">{{ order.customer?.customer_number || 'N/A' }}</div>
            </div>
            <div class="status-card">
              <div class="card-label">Invoice Number</div>
              <div class="card-value">{{ order.invoice_number }}</div>
            </div>
            <div class="status-card">
              <div class="card-label">Current Status</div>
              <div class="card-value-status">
                <span class="status-dot" :class="'status-' + (order.status?.toLowerCase() || 'pending')"></span>
                {{ formatStatus(order.status) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Flujo de entrega y detalles -->
        <div class="delivery-section">
          <div class="delivery-flow">
            <div class="flow-icon">📦</div>
            <div class="flow-arrow">→</div>
            <div class="flow-icon">🚚</div>
            <div class="flow-arrow">→</div>
            <div class="flow-icon">📦</div>
          </div>

          <div class="order-details-panel">
            <h3 class="panel-title">ORDER DETAILS</h3>
            <div class="details-grid">
              <div class="detail-row">
                <span class="detail-label">CLIENT</span>
                <span class="detail-value">{{ order.customer?.display_name || 'N/A' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">ADDRESS</span>
                <span class="detail-value">{{ order.address || 'N/A' }}</span>
              </div>

              <div class="detail-row">
                <span class="detail-label">MATERIALS</span>

                <ul v-if="order.materials && order.materials.length" class="materials-list">
                  <li v-for="item in order.materials" :key="item.product_id">
                    {{ item.product_name }} - {{ item.quantity }} {{ item.unit || 'unidades' }}
                  </li>
                </ul>

                <span v-else class="detail-value">N/A</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">NOTES</span>
                <span class="detail-value">{{ order.notes || 'Sin notas' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">ORDER DATE</span>
                <span class="detail-value">{{ formatDate(order.order_datetime) }}</span>
              </div>
            </div>
          </div>
        </div>

        <button class="btn-back" @click="goBack">← Volver a búsqueda</button>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import axios from 'axios'

const loading = ref(false)
const showDetails = ref(false)
const showSidebar = ref(true)
const form = reactive({ invoice_number: '', customer_number: '' })
const order = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
const userName = ref('UserName')

const getUserName = computed(() => userName.value)
const getUserInitial = computed(() => (userName.value ? userName.value.charAt(0).toUpperCase() : 'U'))

const API_BASE = import.meta.env.VITE_API_BASE || 'http://127.0.0.1:8000'

async function lookupOrder() {
  loading.value = true
  order.value = null
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await axios.post(
      `${API_BASE}/api/public/orders/lookup`,
      {
        invoice_number: form.invoice_number,
        customer_number: form.customer_number,
      },
      {
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
      }
    )

    successMessage.value = response.data.message
    order.value = response.data.data
    showDetails.value = true
  } catch (error) {
    if (error.response) {
      if (error.response.status === 404) {
        errorMessage.value = error.response.data.message || 'No se encontró un pedido con esos datos.'
      } else if (error.response.status === 422) {
        const errors = error.response.data.errors
        if (errors) {
          errorMessage.value = Object.values(errors).flat().join(' ')
        } else {
          errorMessage.value = 'Error de validación.'
        }
      } else {
        errorMessage.value = error.response.data.message || 'Ocurrió un error al consultar el pedido.'
      }
    } else {
      errorMessage.value = 'No fue posible conectar con el servidor.'
    }
  } finally {
    loading.value = false
  }
}

function goBack() {
  showDetails.value = false
  order.value = null
  form.invoice_number = ''
  form.customer_number = ''
  errorMessage.value = ''
  successMessage.value = ''
}

function toggleMenu() {
  showSidebar.value = !showSidebar.value
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' }
  return new Date(dateString).toLocaleDateString('es-ES', options)
}

function formatStatus(status) {
  if (!status) return 'Pendiente'

  const statusMap = {
    ORDERED: 'Ordenado',
    IN_PROCESS: 'En proceso',
    IN_ROUTE: 'En ruta',
    DELIVERED: 'Entregado',
    DELETED: 'Eliminado',
  }

  return statusMap[status] || status
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

/* ============ Vista de búsqueda (inicial) ============ */
.page {
  min-height: 100vh;
  background: #87c8ee;
  padding: 40px 20px;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  background: transparent;
}

.title {
  text-align: center;
  font-size: 32px;
  margin-bottom: 10px;
  color: #1b1b1b;
}

.subtitle {
  text-align: center;
  font-size: 16px;
  margin-bottom: 30px;
  color: #2f2f2f;
}

.lookup-form {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.form-grid {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  justify-content: center;
}

.form-group {
  display: flex;
  flex-direction: column;
  min-width: 260px;
}

.form-group label {
  margin-bottom: 8px;
  font-weight: 600;
  color: #222;
}

.form-group input {
  padding: 12px 14px;
  border: none;
  outline: none;
  background: #d8d8d8;
  font-size: 15px;
}

.btn {
  background: #5dd44f;
  color: #111;
  border: none;
  padding: 12px 28px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.alert {
  max-width: 800px;
  margin: 0 auto 20px auto;
  padding: 14px 16px;
  font-weight: 500;
}

.alert.error {
  background: #ffd7d7;
  color: #8a1f1f;
}

.alert.success {
  background: #d8f7d4;
  color: #1f6b2d;
}

/* ============ Nuevo layout: imagen izquierda + tarjeta derecha ============ */
.lookup-page {
  display: flex;
  min-height: 100vh;
}

.hero {
  flex: 1 1 60%;
  background-image: linear-gradient(rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.08)), url('http://127.0.0.1:8000/images/login-hero.png');
  background-size: cover;
  background-position: center;
}

.lookup-card {
  width: 420px;
  background: #ffffff;
  box-shadow: 0 6px 24px rgba(16, 24, 40, 0.08);
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0;
  /* padding moved to .card-body */
  border-left: 4px solid rgba(15, 99, 255, 0.06);
}

.card-top {
  display: flex;
  justify-content: center;
  align-items: center;
  padding-bottom: 8px;
}

.card-logo {
  font-weight: 800;
  color: #11a0b8;
  font-size: 18px;
}

.card-logo span {
  color: #0b2946;
  margin-left: 6px;
}

.card-body {
  padding-top: 6px;
}

.card-title {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 6px 0;
}

.card-subtitle {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 18px;
}

.card-form .form-field {
  margin-bottom: 12px;
}

.card-form label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #374151;
  font-size: 13px;
}

.card-form input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 8px;
  border: 1px solid #e6e6e6;
  background: #fff;
  font-size: 14px;
  outline: none;
}

.form-extra {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 12px 0 18px 0;
}

.remember {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #374151;
  font-size: 13px;
}

.forgot {
  color: #0f63ff;
  text-decoration: none;
  font-size: 13px;
}

.btn-primary {
  background: #0f63ff;
  color: #fff;
  border: none;
  padding: 12px 16px;
  border-radius: 8px;
  width: 100%;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Utilidades similares a Bootstrap usadas en login.blade.php */
.card-body {
  padding: 24px;
}

.text-center {
  text-align: center;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-3 {
  margin-bottom: 0.75rem;
}

.small {
  font-size: 13px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #374151;
  font-size: 13px;
}

.form-control {
  width: 100%;
  padding: 12px 14px;
  border-radius: 8px;
  border: 1px solid #e6e6e6;
  background: #fff;
  font-size: 14px;
  outline: none;
}

.form-check {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.form-check-input {
  width: 16px;
  height: 16px;
}

.form-check-label {
  font-size: 13px;
  color: #374151;
}

.d-flex {
  display: flex;
}

.justify-content-between {
  justify-content: space-between;
}

.align-items-center {
  align-items: center;
}

.d-grid {
  display: block;
}

.mt-3 {
  margin-top: 12px;
}

@media (max-width: 768px) {
  .lookup-page {
    flex-direction: column;
  }

  .hero {
    height: 220px;
    flex: none;
  }

  .lookup-card {
    width: 100%;
    padding: 20px;
    border-left: none;
  }
}


/* ============ Vista de detalles de pedido ============ */
.page-details {
  min-height: 100vh;
  background: #f5f5f5;
  display: flex;
  flex-direction: column;
}

.header {
  background: #fff;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  gap: 20px;
}

.menu-toggle {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 8px;
  display: none;
}

.header-title {
  flex: 1;
  text-align: center;
  font-size: 18px;
  font-weight: 600;
  color: #333;
  letter-spacing: 1px;
}

.header-user {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #a0826d;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 18px;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.user-role {
  font-size: 12px;
  color: #999;
}

.layout {
  display: flex;
  flex: 1;
}

/* ============ Sidebar ============ */
.sidebar {
  width: 180px;
  background: #e8f4f8;
  padding: 20px 0;
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
}

.sidebar-header {
  text-align: center;
  padding: 20px 0 30px 0;
  font-size: 28px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
}

.nav-item {
  padding: 12px 20px;
  color: #333;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.nav-item.active {
  background: #c8e6f0;
  color: #0066cc;
  border-left: 4px solid #0066cc;
}

.nav-item:hover {
  background: #d8eef5;
}

/* ============ Contenido principal ============ */
.content {
  flex: 1;
  overflow-y: auto;
  padding: 30px;
}

.welcome-section {
  margin-bottom: 40px;
}

.welcome-title {
  font-size: 28px;
  color: #0066cc;
  margin: 0 0 10px 0;
}

.welcome-subtitle {
  font-size: 14px;
  color: #666;
  margin: 0;
}

/* ============ Sección ORDER STATUS ============ */
.order-status-section {
  margin-bottom: 40px;
}

.section-title {
  font-size: 16px;
  font-weight: 700;
  color: #222;
  margin: 0 0 16px 0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.status-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.status-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  padding: 20px;
  border-radius: 4px;
  text-align: center;
}

.card-label {
  font-size: 12px;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.card-value {
  font-size: 24px;
  font-weight: 700;
  color: #222;
}

.card-value-status {
  font-size: 18px;
  font-weight: 600;
  color: #222;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.status-dot.status-pending {
  background: #ffc107;
}

.status-dot.status-in_progress {
  background: #ff9800;
}

.status-dot.status-delivered {
  background: #4caf50;
}

.status-dot.status-cancelled {
  background: #f44336;
}

/* ============ Flujo y detalles ============ */
.delivery-section {
  margin-bottom: 40px;
}

.delivery-flow {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 30px;
  padding: 20px;
  background: #fff;
  border-radius: 4px;
}

.flow-icon {
  font-size: 32px;
}

.flow-arrow {
  font-size: 20px;
  color: #999;
}

/* ============ Panel de detalles ============ */
.order-details-panel {
  background: #fff;
  border: 1px solid #e0e0e0;
  padding: 24px;
  border-radius: 4px;
}

.panel-title {
  font-size: 14px;
  font-weight: 700;
  color: #222;
  margin: 0 0 20px 0;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f0f0f0;
}

.details-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 12px;
  border-bottom: 1px solid #f5f5f5;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 600;
  color: #333;
  min-width: 100px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  color: #666;
  text-align: right;
  flex: 1;
}

.materials-list {
  list-style: none;
  margin: 0;
  padding: 0;
  text-align: right;
}

.materials-list li {
  color: #666;
  font-size: 14px;
  padding: 4px 0;
}

.btn-back {
  background: #5dd44f;
  color: #111;
  border: none;
  padding: 12px 24px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  margin-top: 20px;
}

.btn-back:hover {
  background: #50c63f;
}

/* ============ Responsive ============ */
@media (max-width: 768px) {
  .menu-toggle {
    display: block;
  }

  .sidebar {
    position: fixed;
    left: -180px;
    top: 0;
    height: 100vh;
    transition: left 0.3s;
    z-index: 1000;
  }

  .sidebar.show-menu {
    left: 0;
  }

  .status-cards {
    grid-template-columns: 1fr;
  }

  .content {
    padding: 16px;
  }

  .header {
    flex-wrap: wrap;
  }

  .header-title {
    flex: 0 0 100%;
  }

  .order-details-panel {
    padding: 16px;
  }

  .detail-row {
    flex-direction: column;
    gap: 8px;
  }

  .detail-value {
    text-align: left;
  }
}
</style>