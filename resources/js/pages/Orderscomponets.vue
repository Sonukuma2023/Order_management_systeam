<template>
  <AuthenticatedLayout>
    <div class="welcome-banner">
      <div class="header-text">
        <h1>Orders</h1>
        
      </div>
      <div class="stats-mini">
        <span class="stat-item"><i class='bx bx-package'></i> {{ result.length }} Total Orders</span>
      </div>
    </div>

    <div class="table-container">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(order, index) in result" :key="index">
            <td><span class="order-id">#{{ order.order_name || order.order_id }}</span></td>
            <td>
              <div class="customer-info">
                <span class="name">{{ order.customer_name || 'N/A' }}</span>
                <span class="email">{{ order.email || 'N/A' }}</span>
              </div>
            </td>
            <td>{{ order.product_name || 'N/A' }}</td>
            <td class="amount">₹{{ formatPrice(order.price) }}</td>
            <td>
              <span class="status-badge" :class="statusClass(order.status)">
                {{ order.status || 'Open' }}
              </span>
            </td>
            <td>{{ formatDate(order.date) }}</td>
            <td>
              <button class="view-icon" title="View Details" @click="viewOrder(order)">
                <i class='bx bx-show'></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="selectedOrder" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <div class="header-title-wrapper">
            <div class="header-icon">
              <i class='bx bx-detail'></i>
            </div>
            <div>
              <h2>Order Details</h2>
              <span class="modal-order-id">#{{ selectedOrder.order_name || selectedOrder.order_id }}</span>
            </div>
          </div>
          <button class="close-btn-x" @click="closeModal">&times;</button>
        </div>

        <div class="modal-body">
          <div class="status-row">
            <span class="status-label">Current Status:</span>
            <span class="status-badge" :class="statusClass(selectedOrder.status)">
              {{ selectedOrder.status || 'Open' }}
            </span>
          </div>

          <div class="detail-section">
            <h3 class="section-title"><i class='bx bx-user'></i> CUSTOMER PROFILE</h3>
            <div class="grid-2-col">
              <div class="info-group">
                <span class="info-label">Full Name</span>
                <span class="info-value text-bold">{{ selectedOrder.customer_name || 'N/A' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Email Address</span>
                <span class="info-value text-cyan">{{ selectedOrder.email || 'N/A' }}</span>
              </div>
            </div>
            <div class="grid-2-col margin-top-md">
              <div class="info-group">
                <span class="info-label">Purchase Date</span>
                <span class="info-value text-bold">{{ formatDate(selectedOrder.date) }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section inner-card">
            <h3 class="section-title"><i class='bx bx-shopping-bag'></i> LINE ITEM BREAKDOWN</h3>
            <div class="info-group">
              <span class="info-label">Product Title / Variant Name</span>
              <span class="info-value text-bold">{{ selectedOrder.product_name || 'N/A' }}</span>
            </div>
            <div class="grid-2-col margin-top-md">
              <div class="info-group">
                <span class="info-label">Database Identifier</span>
                <span class="db-id-badge">{{ selectedOrder.product_id || 'PID-9055313821924' }}</span>
              </div>
              <div class="info-group">
                <span class="info-label">Unit Settlement Price</span>
                <span class="info-value text-cyan text-bold">₹{{ formatPrice(selectedOrder.price) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="close-btn-action" @click="closeModal">Close</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from './AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

// Props from Laravel
const props = defineProps({
   result: Array,
});

const page = usePage();

// Reactive reference to store the active order for the popup
const selectedOrder = ref(null);

// Functions to manage modal
const viewOrder = (order) => {
  selectedOrder.value = order;
};

const closeModal = () => {
  selectedOrder.value = null;
};

// Toast for flash messages
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  background: '#1a1a1e',
  color: '#fff',
  iconColor: '#00f2ff',
});

onMounted(() => {
  if (page.props.flash?.success) {
    Toast.fire({
      icon: 'success',
      title: page.props.flash.success
    });
  }
});

// Format price with commas and 2 decimals
const formatPrice = (price) => {
  if (!price) return '0.00';
  return Number(price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Map status to CSS class
const statusClass = (status) => {
  switch ((status || 'open').toLowerCase()) {
    case 'delivered': return 'delivered';
    case 'pending': return 'pending';
    case 'cancelled': return 'cancelled';
    case 'open': return 'open';
    default: return '';
  }
};

// Format date to 'May 25, 2026'
const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<style scoped>
.welcome-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header-text h1 { font-size: 24px; color: #fff; margin: 0; }
.header-text p { color: #a0aec0; font-size: 14px; margin-top: 5px; }

.stat-item {
  background: #25252b;
  color: #00f2ff;
  padding: 8px 15px;
  border-radius: 8px;
  font-size: 13px;
  border: 1px solid #333;
}

/* Table Styles */
.table-container {
  background: #25252b;
  border-radius: 12px;
  border: 1px solid #333;
  padding: 20px;
}

.custom-table { width: 100%; border-collapse: collapse; }
.custom-table th { text-align: left; color: #a0aec0; padding: 12px; border-bottom: 1px solid #333; font-size: 13px; }
.custom-table td { padding: 15px 12px; color: #fff; border-bottom: 1px solid #333; font-size: 14px; }

.order-id { color: #00f2ff; font-weight: 600; }

.customer-info { display: flex; flex-direction: column; }
.customer-info .name { font-weight: 500; }
.customer-info .email { font-size: 12px; color: #718096; }

.amount { font-weight: 600; color: #fff; }

/* Status Badges */
.status-badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  display: inline-block;
}
.delivered { background: rgba(0, 255, 178, 0.1); color: #00ffb2; border: 1px solid rgba(0, 255, 178, 0.2); }
.pending { background: rgba(255, 184, 0, 0.1); color: #ffb800; border: 1px solid rgba(255, 184, 0, 0.2); }
.cancelled { background: rgba(255, 39, 112, 0.1); color: #ff2770; border: 1px solid rgba(255, 39, 112, 0.2); }
.open { background: rgba(33, 150, 243, 0.1); color: #2196f3; border: 1px solid rgba(33, 150, 243, 0.2); }

.view-icon {
  background: transparent;
  border: 1px solid #444;
  color: #a0aec0;
  padding: 5px 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.3s;
}
.view-icon:hover {
  background: #00f2ff;
  color: #000;
  border-color: #00f2ff;
  box-shadow: 0 0 10px rgba(0, 242, 255, 0.3);
}

/* Modal Popup Styles Setup */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(3px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: #1a1a1e;
  border: 1px solid #2d2d34;
  width: 100%;
  max-width: 540px;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 20px 24px;
  border-bottom: 1px solid #2d2d34;
}

.header-title-wrapper {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
  background: rgba(0, 242, 255, 0.08);
  border: 1px solid rgba(0, 242, 255, 0.2);
  color: #00f2ff;
  font-size: 20px;
  padding: 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-header h2 {
  font-size: 18px;
  color: #fff;
  margin: 0;
  font-weight: 600;
}

.modal-order-id {
  font-size: 12px;
  color: #718096;
  margin-top: 2px;
  display: block;
}

.close-btn-x {
  background: transparent;
  border: none;
  color: #718096;
  font-size: 22px;
  cursor: pointer;
  transition: 0.2s;
}
.close-btn-x:hover {
  color: #fff;
}

.modal-body {
  padding: 24px;
}

.status-row {
  background: #212126;
  border-radius: 8px;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 24px;
}

.status-label {
  color: #718096;
  font-size: 13px;
}

.detail-section {
  margin-bottom: 24px;
}

.inner-card {
  background: #212126;
  border: 1px solid #2d2d34;
  border-radius: 10px;
  padding: 18px;
  margin-bottom: 0;
}

.section-title {
  color: #718096;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.5px;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.grid-2-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.margin-top-md {
  margin-top: 14px;
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  color: #52525b;
  font-size: 12px;
}

.info-value {
  color: #e4e4e7;
  font-size: 14px;
}

.text-bold {
  font-weight: 600;
}

.text-cyan {
  color: #00f2ff !important;
}

.db-id-badge {
  background: #161619;
  border: 1px solid #2d2d34;
  color: #a1a1aa;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-family: monospace;
  display: inline-block;
  max-width: max-content;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #2d2d34;
  display: flex;
  justify-content: flex-end;
}

.close-btn-action {
  background: #27272a;
  border: 1px solid #3f3f46;
  color: #e4e4e7;
  padding: 8px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: 0.2s;
}

.close-btn-action:hover {
  background: #3f3f46;
  color: #fff;
}
</style>