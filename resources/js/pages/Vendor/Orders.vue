<script setup>
import { ref, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import VendorLayout from '../VendorLayout.vue';
import html2pdf from 'html2pdf.js';

// Define incoming dynamic properties from Laravel controller
defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});

// Modal State Management
const isModalOpen = ref(false);
const selectedOrder = ref(null);

// This variable maps directly to the DOM node targeted with ref="modalContent"
const modalContent = ref(null);

// Open the modal and set the selected row data
const viewOrderDetails = (order) => {
    selectedOrder.value = order;
    isModalOpen.value = true;
};

// Reset state to close modal gracefully
const closeModal = () => {
    isModalOpen.value = false;
    selectedOrder.value = null;
};

const downloadOrderPDF = async () => {
    await nextTick();

    const element = modalContent.value;
    
    if (!element) {
        console.error("PDF target element not found.");
        return;
    }

    // 1. Clean filename formatting string to ensure it ends exactly with .pdf
    // Replacing spaces/special characters prevents browsers from guessing incorrect MIME types
    const rawFilename = `Invoice_${selectedOrder.value?.order_id || 'Details'}`;
    const cleanFilename = rawFilename.replace(/[^a-z0-9_-]/gi, '_').toLowerCase() + '.pdf';

    const options = {
        margin:       [12, 12, 12, 12],
        filename:     cleanFilename, 
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, 
            useCORS: true, 
            backgroundColor: '#15151a',
            ignoreElements: (el) => el.classList.contains('exclude-from-pdf')
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
    };

    try {
        // Execute generation worker sequence
        html2pdf().set(options).from(element).save();
    } catch (error) {
        console.error("PDF generation failed, forcing blob fallback:", error);
        fallbackBlobDownload(element, cleanFilename);
    }
};

// 2. High-reliability fallback method if browser intercept remains an issue
const fallbackBlobDownload = (element, filename) => {
    const opt = {
        margin: 12,
        filename: filename,
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).outputPdf('blob').then((blob) => {
        const fileURL = URL.createObjectURL(blob);
        const downloadLink = document.createElement('a');
        
        downloadLink.href = fileURL;
        downloadLink.download = filename;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        
        // Clean up memory space
        document.body.removeChild(downloadLink);
        URL.revokeObjectURL(fileURL);
    });
};
</script>

<template>
    <Head title="Vendor Orders" />
    
    <VendorLayout> 
        <div class="page-container">
            <div class="header-section">
                <div class="header-left">
                    <h1>Orders</h1>
                    <p class="subtitle"></p>
                </div>
                <div class="header-right">
                    <div class="total-badge">
                        <i class='bx bx-package'></i>
                        <span>{{ orders.length }} Total Orders</span>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header-row">
                    <div class="col-id">Order ID</div>
                    <div class="col-customer">Customer</div>
                    <div class="col-product">Product</div>
                    <div class="col-amount">Amount</div>
                    <div class="col-status">Status</div>
                    <div class="col-date">Date</div>
                    <div class="col-actions">Actions</div>
                </div>

                <div class="table-body">
                    <div 
                        v-for="order in orders" 
                        :key="order.order_id + '-' + order.product_id" 
                        class="table-data-row"
                    >
                        <div class="col-id order-id-highlight">
                            {{ order.order_name || ('#' + order.order_id) }}
                        </div>
                        
                        <div class="col-customer">
                            <span class="customer-main-name">{{ order.customer_name }}</span>
                            <span class="customer-sub-email">{{ order.email || 'N/A' }}</span>
                        </div>
                        
                        <div class="col-product product-text">
                            {{ order.product_name }}
                        </div>
                        
                        <div class="col-amount price-text">
                            <!-- Safe fallback check in case price comes as a raw string from Laravel -->
                            Scale: ₹{{ typeof order.price === 'number' ? order.price.toFixed(2) : parseFloat(order.price || 0).toFixed(2) }}
                        </div>
                        
                        <div class="col-status">
                            <span :class="['status-pill', 'status-' + (order.status || 'pending').toLowerCase()]">
                                {{ order.status }}
                            </span>
                        </div>
                        
                        <div class="col-date date-text">
                            {{ order.date }}
                        </div>
                        
                        <div class="col-actions">
                            <!-- Attached view details event hook here -->
                            <button @click="viewOrderDetails(order)" class="action-icon-btn" title="View Details">
                                <i class='bx bx-show-alt'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="orders.length === 0" class="no-orders-state">
                    <i class='bx bx-folder-open'></i>
                    <p>No orders matched your profile record assets yet.</p>
                </div>
            </div>
        </div>

        <!-- Pop-up Details Modal Overlay Layout -->
        <div v-if="isModalOpen && selectedOrder" class="modal-backdrop">
        <div class="modal-surface animate-fade-in"> 
            
            <div class="modal-header">
                <div class="modal-title-group">
                    <i class='bx bx-receipt modal-header-icon'></i>
                    <div>
                        <h2>Order Details</h2>
                        <p class="modal-id-stamp">{{ selectedOrder.order_name || ('#' + selectedOrder.order_id) }}</p>
                    </div>
                </div>
                <button class="modal-close-btn" @click="closeModal">×</button>
            </div>

            <div class="modal-body-scrollable" ref="modalContent">
                
                <div class="detail-section">
                    <div class="info-status-banner">
                        <span class="label-dim">Current Status:</span>
                        <span :class="['status-pill', 'status-' + (selectedOrder.status || 'pending').toLowerCase()]">
                            {{ selectedOrder.status }}
                        </span>
                    </div>
                </div>

                <div class="detail-section">
                    <h3><i class='bx bx-user'></i> Customer Profile</h3>
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Full Name</span>
                            <span class="meta-value text-bright">{{ selectedOrder.customer_name }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Email Address</span>
                            <span class="meta-value text-link">{{ selectedOrder.email || 'N/A' }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Purchase Date</span>
                            <span class="meta-value">{{ selectedOrder.date }}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section highlight-bg">
                    <h3><i class='bx bx-shopping-bag'></i> Line Item Breakdown</h3>
                    <div class="meta-grid">
                        <div class="meta-item spans-all">
                            <span class="meta-label">Product Title / Variant Name</span>
                            <span class="meta-value text-bright text-large">{{ selectedOrder.product_name }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Database Identifier</span>
                            <span class="meta-value code-style">PID-{{ selectedOrder.product_id }}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Unit Settlement Price</span>
                            <span class="meta-value text-accent font-semibold">
                                ₹{{ typeof selectedOrder.price === 'number' ? selectedOrder.price.toFixed(2) : parseFloat(selectedOrder.price || 0).toFixed(2) }}
                            </span>
                        </div>
                        
                        <div class="meta-item spans-all download-container exclude-from-pdf">
                            <button class="download-pdf-btn" @click="downloadOrderPDF">
                                <i class='bx bx-download'></i> Download PDF
                            </button> 
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="footer-dismiss-btn" @click="closeModal">Close</button>
            </div>
        </div>
    </div>
    </VendorLayout>
</template>

<style scoped>
/* Page Layout Containers */
.page-container {
    color: white;
    font-family: 'Inter', sans-serif;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.header-left h1 {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 6px 0;
    color: #fff;
}

.header-left .subtitle {
    font-size: 14px;
    color: #8a8a93;
    margin: 0;
}

/* Header Count Badge */
.total-badge {
    background: rgba(0, 229, 255, 0.05);
    border: 1px solid rgba(0, 229, 255, 0.15);
    padding: 8px 16px;
    border-radius: 8px;
    color: #00e5ff;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

/* Main Table Board UI Layout */
.table-container {
    background: #1a1a1e;
    border: 1px solid #2a2a30;
    border-radius: 12px;
    overflow: hidden;
}

.table-header-row, .table-data-row {
    display: grid;
    grid-template-columns: 1.2fr 1.8fr 1.8fr 1.2fr 1.2fr 1.5fr 1fr;
    align-items: center;
    padding: 16px 24px;
}

.table-header-row {
    background: #1e1e24;
    border-bottom: 1px solid #2a2a30;
    font-size: 13px;
    font-weight: 600;
    color: #8a8a93;
}

/* Data Row Border Seams */
.table-data-row {
    border-bottom: 1px solid #232329;
    font-size: 14px;
    transition: background-color 0.2s;
}

.table-data-row:last-child {
    border-bottom: none;
}

.table-data-row:hover {
    background: #22222a;
}

/* Data Sub-Element Colors & Typography */
.order-id-highlight {
    color: #00e5ff;
    font-weight: 600;
    font-family: monospace;
    font-size: 14px;
}

.col-customer {
    display: flex;
    flex-direction: column;
}

.customer-main-name {
    color: #fff;
    font-weight: 500;
}

.customer-sub-email {
    font-size: 12px;
    color: #52525b;
    margin-top: 2px;
}

.product-text {
    color: #e4e4e7;
}

.price-text {
    color: #fff;
    font-weight: 500;
}

.date-text {
    color: #d4d4d8;
}

/* Dynamic Status Pills */
.status-pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-processing, .status-pending {
    background: rgba(241, 196, 15, 0.08);
    color: #f1c40f;
    border: 1px solid rgba(241, 196, 15, 0.2);
}

.status-open, .status-completed {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.25);
}

.status-cancelled {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.25);
}

/* Action Icons Styling */
.action-icon-btn {
    background: #222226;
    border: 1px solid #333339;
    color: #a1a1aa;
    width: 34px;
    height: 34px;
    border-radius: 6px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s;
}

.action-icon-btn:hover {
    color: #00e5ff;
    border-color: #00e5ff;
    background: rgba(0, 229, 255, 0.05);
}

.action-icon-btn i {
    font-size: 18px;
}

/* Empty Table Component Box */
.no-orders-state {
    text-align: center;
    padding: 50px 20px;
    color: #52525b;
}

.no-orders-state i {
    font-size: 40px;
    margin-bottom: 10px;
    color: #3f3f46;
}

/* ==========================================================================
   POPUP MODAL COMPONENT STYLES
   ========================================================================== */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(10, 10, 12, 0.85);
    backdrop-filter: blur(6px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding: 20px;
}

.modal-surface {
    background: #16161a;
    border: 1px solid #2d2d34;
    border-radius: 14px;
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    overflow: hidden;
}

.animate-fade-in {
    animation: modalReveal 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalReveal {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #232329;
    background: #1c1c21;
}

.modal-title-group {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-header-icon {
    font-size: 24px;
    color: #00e5ff;
    background: rgba(0, 229, 255, 0.08);
    padding: 8px;
    border-radius: 8px;
    border: 1px solid rgba(0, 229, 255, 0.15);
}

.modal-header h2 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    color: #fff;
}

.modal-id-stamp {
    font-size: 12px;
    color: #8a8a93;
    font-family: monospace;
    margin: 2px 0 0 0;
}

.modal-close-btn {
    background: none;
    border: none;
    color: #71717a;
    font-size: 50px;
    cursor: pointer;
    transition: color 0.15s;
    padding: 4px;
    line-height: 1;
}

.modal-close-btn:hover {
    color: #fff;
}

.modal-body-scrollable {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.detail-section h3 {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #8a8a93;
    letter-spacing: 0.5px;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-section h3 i {
    font-size: 16px;
}

.info-status-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #1c1c21;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #232329;
}

.label-dim {
    color: #71717a;
    font-size: 13px;
}

.highlight-bg {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid #232329;
    padding: 16px;
    border-radius: 10px;
}

.meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.meta-item.spans-all {
    grid-column: 1 / -1;
}

.meta-label {
    font-size: 12px;
    color: #71717a;
}

.meta-value {
    font-size: 14px;
    color: #d4d4d8;
}

.text-bright {
    color: #00e5ff;
    font-weight: 500;
}

.text-large {
    font-size: 15px;
}

.text-link {
    color: #00e5ff;
}

.text-accent {
    color: #00e5ff;
}

.font-semibold {
    font-weight: 600;
}

.code-style {
    font-family: monospace;
    background: #c0c0c5;
    padding: 2px 6px;
    border-radius: 4px;
    width: fit-content;
    border: 1px solid #2d2d34;
    color: #2d2d34;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #232329;
    background: #1c1c21;
    display: flex;
    justify-content: flex-end;
}

.footer-dismiss-btn {
    background: #27272a;
    border: 1px solid #3f3f46;
    color: #e4e4e7;
    padding: 9px 18px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 13px;
    cursor: pointer;
    transition: background-color 0.15s, color 0.15s;
}

.footer-dismiss-btn:hover {
    background: #3f3f46;
    color: #fff;
}


.download-pdf-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 10px;
  padding: 6px 12px;
  background-color: #00bcd4; /* Accent color matching your email link/price text */
  color: #ffffff;
  border: none;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.download-pdf-btn:hover {
  background-color: #0097a7;
  transform: translateY(-1px);
}

.download-pdf-btn:active {
  transform: translateY(0);
}

.download-pdf-btn i {
  font-size: 1.1rem;
}
</style>