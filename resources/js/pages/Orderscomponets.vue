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

    <!-- Modal Popup Details & PDF Canvas Wrapper -->
    <div v-if="selectedOrder" class="modal-overlay">
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
          <!-- Printable Clean Document Section Target -->
          <div class="simple-pdf-page" ref="pdfContent">
            
            <!-- Document Heading -->
            <div class="pdf-document-header">
              <div>
                <h1 class="pdf-title">Order Statement</h1>
                <p class="pdf-subtitle">Official Transaction Summary</p>
              </div>
              <div class="pdf-meta-badge">OFFICIAL</div>
            </div>

            <div class="pdf-divider"></div>

            <!-- Profile Info Grid Layout -->
            <div class="pdf-data-grid">
              <div class="pdf-info-block">
                <span class="pdf-label">CUSTOMER PROFILE</span>
                <h3 class="pdf-value-bold">{{ selectedOrder.customer_name || 'N/A' }}</h3>
                <p class="pdf-value-subtext">{{ selectedOrder.email || 'N/A' }}</p>
              </div>
              
              <div class="pdf-info-block text-right-aligned">
                <span class="pdf-label">TRANSACTION DETAILS</span>
                <p class="pdf-value-text"><strong>Order ID:</strong> #{{ selectedOrder.order_name || selectedOrder.order_id }}</p>
                <p class="pdf-value-text"><strong>Date:</strong> {{ formatDate(selectedOrder.date) }}</p>
                <p class="pdf-value-text"><strong>Status:</strong> <span class="pdf-status-text">{{ selectedOrder.status || '-' }}</span></p>
              </div>
            </div>

            <!-- Line Items Table Structure -->
            <table class="pdf-invoice-table">
              <thead>
                <tr>
                  <th align="left">Product Description</th>
                  <th align="left">Database ID</th>
                  <th align="right">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="pdf-item-name">{{ selectedOrder.product_name || 'N/A' }}</td>
                  <td class="pdf-item-mono">{{ selectedOrder.product_id || 'PID-9055313821924' }}</td>
                  <td align="right" class="pdf-item-price">₹{{ formatPrice(selectedOrder.price) }}</td>
                </tr>
              </tbody>
            </table>

            <!-- Grand Total Section -->
            <div class="pdf-total-wrapper">
              <div class="pdf-total-row">
                <span class="pdf-total-label">Grand Total:</span>
                <span class="pdf-total-val">₹{{ formatPrice(selectedOrder.price) }}</span>
              </div>
            </div>

            <div class="pdf-footer-note">
              <p>Thank you for your business. Generated via Order Management System.</p>
            </div>
          </div>
          
          <!-- Download Trigger Element (Hidden from the print canvas target) -->
          <div class="download-action-container">
            <button class="download-pdf-btn" @click="downloadSimplePDF">
              <i class='bx bx-download'></i> Download Clean PDF
            </button>
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
import { ref, onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from './AuthenticatedLayout.vue';
import Swal from 'sweetalert2';
import html2pdf from 'html2pdf.js'; // Ensure this package is installed: npm install html2pdf.js

// Props from Laravel
const props = defineProps({
   result: Array,
});

const page = usePage();

// Reactive references
const selectedOrder = ref(null);
const pdfContent = ref(null);

// Functions to manage modal
const viewOrder = (order) => {
  selectedOrder.value = order;
};

const closeModal = () => {
  selectedOrder.value = null;
};

// Generate and export simple structural PDF file format
const downloadSimplePDF = async () => {
  await nextTick();
  const element = pdfContent.value;
  
  if (!element) return;

  const orderIdentifier = selectedOrder.value.order_name || selectedOrder.value.order_id;
  const filename = `order_${orderIdentifier}.pdf`.toLowerCase();

  const options = {
    margin:       [15, 15, 15, 15],
    filename:     filename,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { 
      scale: 3,                // Multiplied scale factor resolves grainy text artifacts
      useCORS: true, 
      backgroundColor: '#ffffff', // Ensures no dark theme bleed
      logging: false 
    },
    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };

  html2pdf().set(options).from(element).save();
};

// Toast configuration for flash messages
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

const formatPrice = (price) => {
  if (!price) return '0.00';
  return Number(price).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const statusClass = (status) => {
  switch ((status || 'open').toLowerCase()) {
    case 'delivered': return 'delivered';
    case 'pending': return 'pending';
    case 'cancelled': return 'cancelled';
    case 'open': return 'open';
    default: return '';
  }
};

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
  max-width: 580px;
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
.close-btn-x:hover { color: #fff; }

.modal-body {
  padding: 24px;
  max-height: 70vh;
  overflow-y: auto;
}

/* ==========================================================================
   SIMPLE CLEAN PRINT CANVAS FORMATTING (White Background Paper Style)
   ========================================================================== */
.simple-pdf-page {
  background: #ffffff !important;
  color: #1e293b !important;
  padding: 30px;
  border-radius: 8px;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  box-shadow: inset 0 0 0 1px #e2e8f0;
}

.pdf-document-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.pdf-title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a !important;
  margin: 0;
}

.pdf-subtitle {
  font-size: 12px;
  color: #64748b !important;
  margin: 4px 0 0 0;
}

.pdf-meta-badge {
  font-size: 10px;
  font-weight: 700;
  background: #f1f5f9 !important;
  color: #475569 !important;
  padding: 4px 10px;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
}

.pdf-divider {
  height: 1px;
  background: #e2e8f0 !important;
  margin: 20px 0;
}

.pdf-data-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.pdf-info-block {
  display: flex;
  flex-direction: column;
}

.text-right-aligned {
  align-items: flex-end;
  text-align: right;
}

.pdf-label {
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8 !important;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.pdf-value-bold {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a !important;
  margin: 0;
}

.pdf-value-subtext {
  font-size: 12px;
  color: #475569 !important;
  margin: 2px 0 0 0;
}

.pdf-value-text {
  font-size: 12px;
  color: #334155 !important;
  margin: 2px 0 0 0;
}

.pdf-status-text {
  font-weight: 700;
  color: #0284c7 !important;
  text-transform: uppercase;
}

/* Custom Table for Simple PDF */
.pdf-invoice-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.pdf-invoice-table th {
  background: #f8fafc !important;
  color: #475569 !important;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 10px 12px;
  border-bottom: 2px solid #e2e8f0 !important;
}

.pdf-invoice-table td {
  padding: 12px;
  border-bottom: 1px solid #f1f5f9 !important;
  font-size: 13px;
  color: #334155 !important;
}

.pdf-item-name { font-weight: 600; color: #0f172a !important; }
.pdf-item-mono { font-family: monospace; color: #64748b !important; }
.pdf-item-price { font-weight: 600; color: #0f172a !important; }

.pdf-total-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.pdf-total-row {
  display: flex;
  justify-content: space-between;
  width: 200px;
  border-top: 2px solid #0f172a !important;
  padding-top: 10px;
}

.pdf-total-label { font-size: 13px; font-weight: 700; color: #475569 !important; }
.pdf-total-val { font-size: 16px; font-weight: 700; color: #0f172a !important; }

.pdf-footer-note {
  text-align: center;
  margin-top: 30px;
  border-top: 1px solid #f1f5f9 !important;
  padding-top: 12px;
}
.pdf-footer-note p { font-size: 10px; color: #94a3b8 !important; margin: 0; }

/* Interactive UI Elements Container */
.download-action-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.download-pdf-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #00f2ff;
  color: #000;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: 0.2s;
}
.download-pdf-btn:hover {
  background: #00d1dc;
  transform: translateY(-1px);
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