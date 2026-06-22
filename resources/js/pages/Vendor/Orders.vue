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

// Targeted DOM node for PDF generation
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

    const rawFilename = `Invoice_${selectedOrder.value?.order_id || 'Details'}`;
    const cleanFilename = rawFilename.replace(/[^a-z0-9_-]/gi, '_').toLowerCase() + '.pdf';

    const options = {
        margin:       [15, 15, 15, 15],
        filename:     cleanFilename, 
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 3,                // High scale for crisp text rendering
            useCORS: true, 
            backgroundColor: '#ffffff', // Explicit clean white page background
            logging: false
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
    };

    try {
        html2pdf().set(options).from(element).save();
    } catch (error) {
        console.error("PDF generation failed, forcing blob fallback:", error);
        fallbackBlobDownload(element, cleanFilename);
    }
};

const fallbackBlobDownload = (element, filename) => {
    const opt = {
        margin: 15,
        filename: filename,
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).outputPdf('blob').then((blob) => {
        const fileURL = URL.createObjectURL(blob);
        const downloadLink = document.createElement('a');
        
        downloadLink.href = fileURL;
        downloadLink.download = filename;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        
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
                            {{ order.order_id || ('#' + order.order_id) }}
                        </div>
                        
                        <div class="col-customer">
                            <span class="customer-main-name">{{ order.customer_name }}</span>
                            <span class="customer-sub-email">{{ order.email || 'N/A' }}</span>
                        </div>
                        
                        <div class="col-product product-text">
                            {{ order.product_name }}
                        </div>
                        
                        <div class="col-amount price-text">
                            ₹{{ typeof order.price === 'number' ? order.price.toFixed(2) : parseFloat(order.price || 0).toFixed(2) }}
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
                            <button @click="viewOrderDetails(order)" class="action-icon-btn" title="View Details">
                                <i class='bx bx-show-alt'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="orders.length === 0" class="no-orders-state">
                    <i class='bx bx-folder-open'></i>
                    <p>No orders recorded assets yet.</p>
                </div>
            </div>
        </div>

        <div v-if="isModalOpen && selectedOrder" class="modal-backdrop">
            <div class="modal-surface animate-fade-in"> 
                
                <div class="modal-header">
                    <div class="modal-title-group">
                        <i class='bx bx-receipt modal-header-icon'></i>
                        <div>
                            <h2>Document Preview</h2>
                            <p class="modal-id-stamp">Order Reference #{{ selectedOrder.order_id }}</p>
                        </div>
                    </div>
                    <button class="modal-close-btn" @click="closeModal">×</button>
                </div>

                <div class="modal-body-scrollable">
                    
                    <div class="pdf-document-canvas" ref="modalContent">
                        
                        <div class="pdf-document-header">
                            <div>
                                <h1 class="pdf-main-title">Order Management System</h1>
                                <p class="pdf-subtitle">Official Order Transaction Statement</p>
                            </div>
                            <div class="pdf-header-meta">
                                <div class="pdf-meta-badge">ORDER STATEMENT</div>
                            </div>
                        </div>

                        <div class="pdf-horizontal-divider"></div>

                        <div class="pdf-info-grid">
                            <div class="pdf-info-block">
                                <span class="pdf-section-tag">CUSTOMER INFO</span>
                                <h3 class="pdf-info-value-primary">{{ selectedOrder.customer_name }}</h3>
                                <p class="pdf-info-subtext">{{ selectedOrder.email || 'No email registered' }}</p>
                            </div>

                            <div class="pdf-info-block alignment-right">
                                <span class="pdf-section-tag">TRANSACTION LOG</span>
                                <table class="pdf-compact-meta-table">
                                    <tr>
                                        <td><strong>Order ID:</strong></td>
                                        <td class="text-right">#{{ selectedOrder.order_id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td class="text-right">{{ selectedOrder.date }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td class="text-right"><span class="pdf-print-status">{{ selectedOrder.status }}</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="pdf-table-wrapper">
                            <table class="pdf-invoice-table">
                                <thead>
                                    <tr>
                                        <th align="left" width="55%">Product Details</th>
                                        <th align="left" width="25%">Product ID</th>
                                        <th align="right" width="20%">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="pdf-item-title">{{ selectedOrder.product_name }}</td>
                                        <td class="pdf-item-mono">PID-{{ selectedOrder.product_id }}</td>
                                        <td align="right" class="pdf-item-price">
                                            ₹{{ typeof selectedOrder.price === 'number' ? selectedOrder.price.toFixed(2) : parseFloat(selectedOrder.price || 0).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pdf-summary-block">
                            <div class="pdf-total-row">
                                <span class="pdf-total-label">Grand Total Due:</span>
                                <span class="pdf-total-amount">
                                    ₹{{ typeof selectedOrder.price === 'number' ? selectedOrder.price.toFixed(2) : parseFloat(selectedOrder.price || 0).toFixed(2) }}
                                </span>
                            </div>
                        </div>

                        <div class="pdf-document-footer">
                            <p>Thank you for your business. This document is automatically generated by the Order Management System.</p>
                        </div>
                    </div>

                    <div class="download-container-wrapper">
                        <button class="download-pdf-btn" @click="downloadOrderPDF">
                            <i class='bx bx-download'></i> Download Official PDF
                        </button>
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
/* Dashboard Styling Framework */
.page-container { color: white; font-family: 'Inter', sans-serif; }
.header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.header-left h1 { font-size: 28px; font-weight: 600; margin: 0; color: #fff; }
.total-badge { background: rgba(0, 229, 255, 0.05); border: 1px solid rgba(0, 229, 255, 0.15); padding: 8px 16px; border-radius: 8px; color: #00e5ff; font-size: 13px; display: flex; align-items: center; gap: 8px; font-weight: 500; }

.table-container { background: #1a1a1e; border: 1px solid #2a2a30; border-radius: 12px; overflow: hidden; }
.table-header-row, .table-data-row { display: grid; grid-template-columns: 1.2fr 1.8fr 1.8fr 1.2fr 1.2fr 1.5fr 1fr; align-items: center; padding: 16px 24px; }
.table-header-row { background: #1e1e24; border-bottom: 1px solid #2a2a30; font-size: 13px; font-weight: 600; color: #8a8a93; }
.table-data-row { border-bottom: 1px solid #232329; font-size: 14px; }
.table-data-row:hover { background: #22222a; }
.order-id-highlight { color: #00e5ff; font-weight: 600; font-family: monospace; }
.col-customer { display: flex; flex-direction: column; }
.customer-main-name { color: #fff; font-weight: 500; }
.customer-sub-email { font-size: 12px; color: #71717a; margin-top: 2px; }
.product-text { color: #e4e4e7; }
.price-text { color: #fff; font-weight: 500; }
.date-text { color: #d4d4d8; }

.status-pill { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.status-processing, .status-pending { background: rgba(241, 196, 15, 0.1); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.2); }
.status-open, .status-completed { background: rgba(52, 152, 219, 0.1); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.2); }
.status-cancelled { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.2); }

.action-icon-btn { background: #222226; border: 1px solid #333339; color: #a1a1aa; width: 34px; height: 34px; border-radius: 6px; display: flex; justify-content: center; align-items: center; cursor: pointer; }
.action-icon-btn:hover { color: #00e5ff; border-color: #00e5ff; }
.no-orders-state { text-align: center; padding: 50px 20px; color: #52525b; }

/* Interactive Popup Preview Styling */
.modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(8, 8, 10, 0.85); backdrop-filter: blur(8px); display: flex; justify-content: center; align-items: center; z-index: 1000; padding: 20px; }
.modal-surface { background: #16161a; border: 1px solid #2d2d34; border-radius: 16px; width: 100%; max-width: 750px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 30px 60px rgba(0,0,0,0.6); overflow: hidden; }
.animate-fade-in { animation: modalReveal 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalReveal { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid #232329; background: #1c1c21; }
.modal-title-group { display: flex; align-items: center; gap: 12px; }
.modal-header-icon { font-size: 20px; color: #00e5ff; }
.modal-header h2 { font-size: 16px; font-weight: 600; margin: 0; color: #fff; }
.modal-id-stamp { font-size: 12px; color: #71717a; margin: 2px 0 0 0; }
.modal-close-btn { background: none; border: none; color: #71717a; font-size: 28px; cursor: pointer; line-height: 1; }
.modal-close-btn:hover { color: #fff; }
.modal-body-scrollable { padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; background: #131317; }

/* ==========================================================================
   EXCLUSIVE PROFESSIONAL SINGLE-PAGE LIGHT PRINT CANVAS FOR HTML2PDF
   ========================================================================== */
.pdf-document-canvas {
    background: #ffffff !important;
    color: #1a1a1a !important;
    padding: 40px;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.pdf-document-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.pdf-main-title {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a !important;
    margin: 0 0 4px 0;
    letter-spacing: -0.5px;
}

.pdf-subtitle {
    font-size: 12px;
    color: #64748b !important;
    margin: 0;
}

.pdf-meta-badge {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    background: #f1f5f9;
    color: #334155;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}

.pdf-horizontal-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 24px 0;
}

/* Document Profiles Grid */
.pdf-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 35px;
}

.pdf-section-tag {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #94a3b8 !important;
    margin-bottom: 6px;
}

.pdf-info-value-primary {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b !important;
    margin: 0 0 4px 0;
}

.pdf-info-subtext {
    font-size: 13px;
    color: #475569 !important;
    margin: 0;
}

.alignment-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.pdf-compact-meta-table {
    border-collapse: collapse;
    font-size: 13px;
}

.pdf-compact-meta-table td {
    padding: 3px 0;
    color: #334155 !important;
}

.pdf-compact-meta-table td:first-child {
    padding-right: 15px;
    color: #64748b !important;
}

.text-right {
    text-align: right;
}

.pdf-print-status {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    color: #b45309 !important; /* Elegant slate amber standard print color */
}

/* Structural Corporate Table Layout */
.pdf-table-wrapper {
    margin-bottom: 30px;
}

.pdf-invoice-table {
    width: 100%;
    border-collapse: collapse;
}

.pdf-invoice-table th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 12px 14px;
    border-bottom: 2px solid #e2e8f0;
}

.pdf-invoice-table td {
    padding: 16px 14px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 13px;
    color: #334155 !important;
}

.pdf-item-title {
    font-weight: 600;
    color: #0f172a !important;
}

.pdf-item-mono {
    font-family: monospace;
    color: #475569 !important;
}

.pdf-item-price {
    font-weight: 600;
    color: #0f172a !important;
}

/* Document Calculations Summary Block */
.pdf-summary-block {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 40px;
}

.pdf-total-row {
    display: flex;
    align-items: center;
    gap: 30px;
    border-top: 2px solid #0f172a;
    padding-top: 12px;
    width: 250px;
    justify-content: space-between;
}

.pdf-total-label {
    font-size: 13px;
    font-weight: 700;
    color: #475569 !important;
}

.pdf-total-amount {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a !important;
}

/* Small Print Notice Footer */
.pdf-document-footer {
    text-align: center;
    border-top: 1px solid #f1f5f9;
    padding-top: 15px;
}

.pdf-document-footer p {
    font-size: 11px;
    color: #94a3b8 !important;
    margin: 0;
}

/* User Interactive Controls Container (Excluded from Generation canvas) */
.download-container-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 4px;
}

.download-pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background-color: #00e5ff;
    color: #111216;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.download-pdf-btn:hover {
    background-color: #00b8d4;
    transform: translateY(-1px);
}

.download-pdf-btn i { font-size: 16px; }

.modal-footer { padding: 14px 24px; border-top: 1px solid #232329; background: #1c1c21; display: flex; justify-content: flex-end; }
.footer-dismiss-btn { background: #27272a; border: 1px solid #3f3f46; color: #e4e4e7; padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 13px; cursor: pointer; }
.footer-dismiss-btn:hover { background: #3f3f46; color: #fff; }
</style>