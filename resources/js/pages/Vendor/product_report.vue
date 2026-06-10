<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import VendorLayout from '../VendorLayout.vue';

const props = defineProps({
    reportData: {
        type: Object,
        default: () => ({ data: [], links: [] })
    }
});

// Alias for cleaner template parsing
const result = computed(() => props.reportData.data || []);
const paginationLinks = computed(() => props.reportData.links || []);

const getStatusClass = (status) => {
    if (!status) return 'status-default';
    const lower = status.toLowerCase();
    if (lower.includes('deliver') || lower.includes('success') || lower.includes('complete')) return 'status-success';
    if (lower.includes('pend') || lower.includes('process')) return 'status-warning';
    if (lower.includes('cancel') || lower.includes('fail')) return 'status-danger';
    return 'status-default';
};
</script>

<template>
    <VendorLayout>
        <h1 class="page-title">Product Report</h1>

        <div class="product-report-card">
            <div class="report-header">
                <div class="report-title-group">
                    <i class="bx bx-bar-chart-square report-icon"></i>
                    <div>
                        <h2>Product Report</h2>
                        <p class="report-subtitle">Shows best-selling products.</p>
                    </div>
                </div>

                <span class="report-badge">
                    {{ reportData.total || 0 }} Products Total
                </span>
            </div>

            <hr class="report-divider" />

            <div class="report-grid-header">
                <span class="grid-label">Product</span>
                <span class="grid-label text-center">Status</span>
                <span class="grid-label text-right">Price</span>
                <span class="grid-label text-center">Sold</span>
                <span class="grid-label text-right">Revenue</span>
            </div>

            <div class="report-grid-body">
                <div
                    v-if="result.length"
                    v-for="item in result"
                    :key="item.product_id"
                    class="report-row"
                >
                    <div class="product-info">
                        <img
                            v-if="item.image"
                            :src="item.image"
                            :alt="item.name"
                            class="product-image"
                        />
                        <div v-else class="product-placeholder">
                            <i class="bx bx-package"></i>
                        </div>
                        <span class="product-name" :title="item.name">
                            {{ item.name }}
                        </span>
                    </div>

                    <div class="text-center">
                        <span class="badge-pill" :class="getStatusClass(item.status)">
                            {{ item.status || 'N/A' }}
                        </span>
                    </div>

                    <span class="text-right text-muted-white">
                        ₹{{ Number(item.product_price).toLocaleString('en-IN') }}
                    </span>

                    <span class="sold-count text-center">
                        {{ item.quantity }}
                    </span>

                    <span class="text-bright text-right">
                        ₹{{ Number(item.total).toLocaleString('en-IN') }}
                    </span>
                </div>

                <div v-else class="empty-state">
                    <i class="bx bx-layer-minus empty-icon"></i>
                    <p>No product report data found.</p>
                </div>
            </div>

            <div v-if="result.length && paginationLinks.length > 3" class="pagination-container">
                <nav class="pagination-nav">
                    <template v-for="(link, index) in paginationLinks" :key="index">
                        <div 
                            v-if="link.url === null" 
                            class="pagination-link disabled"
                            v-html="link.label"
                        ></div>
                        <Link 
                            v-else 
                            :href="link.url" 
                            class="pagination-link" 
                            :class="{ 'active': link.active }"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </VendorLayout>
</template>

<style scoped>
/* Base Title Header Typography Alignment */
.page-title {
    color: #ffffff;
    font-size: 1.8rem;
    margin-bottom: 24px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
}

/* Primary Document Panel Container Base Dark Design */
.product-report-card {
    background-color: #1a1a1e; 
    border: 1px solid #2d2d2d;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    font-family: 'Inter', sans-serif;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-title-group {
    display: flex;
    align-items: center;
    gap: 14px;
}

.report-icon {
    font-size: 2rem;
    color: #ff2770; /* Injected accent pink theme branding */
}

.report-header h2 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.report-subtitle {
    color: #888892;
    font-size: 0.88rem;
    margin: 3px 0 0 0;
}

.report-badge {
    background: rgba(255, 39, 112, 0.15);
    color: #ff2770;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    border: 1px solid rgba(255, 39, 112, 0.25);
}

.report-divider {
    border: 0;
    height: 1px;
    background-color: #2d2d2d;
    margin: 20px 0;
}

/* Master Grid Structure Definition */
.report-grid-header,
.report-row {
    display: grid;
    grid-template-columns: 2.2fr 1fr 1fr 0.8fr 1.2fr;
    gap: 16px;
    align-items: center;
}

.report-grid-header {
    padding-bottom: 12px;
    border-bottom: 1px solid #2d2d2d;
}

.grid-label {
    color: #66666f;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.report-row {
    padding: 14px 0;
    border-bottom: 1px solid #222226;
    transition: background-color 0.2s ease;
}

.report-row:last-child {
    border-bottom: none;
}

.report-row:hover {
    background-color: #222227;
    border-radius: 4px;
}

/* Text Alignment Utility Contexts */
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-muted-white { color: #dcdcdc; font-size: 0.92rem; }

/* Product Info Block Sub-elements */
.product-info {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0; /* Protects grid break parameters */
}

.product-image {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border-radius: 6px;
    background-color: #121214;
    border: 1px solid #333339;
}

.product-placeholder {
    width: 44px;
    height: 44px;
    background: #25252b;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #66666f;
    border: 1px solid #2d2d35;
}

.product-placeholder i {
    font-size: 1.3rem;
}

.product-name {
    color: #e0e0e6;
    font-weight: 500;
    font-size: 0.92rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Status Pill Indicators Architecture */
.badge-pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-success { background: rgba(34, 197, 94, 0.12); color: #4ade80; }
.status-warning { background: rgba(234, 179, 8, 0.12); color: #facc15; }
.status-danger  { background: rgba(239, 68, 68, 0.12); color: #f87171; }
.status-default { background: rgba(148, 163, 184, 0.12); color: #cbd5e1; }

.sold-count {
    color: #ffffff;
    font-weight: 500;
    font-size: 0.92rem;
}

/* Revenue Bright Highlight Target Style Rule */
.text-bright {
    font-weight: 600;
    color: #00bcd4; /* Clean contrast target element theme turquoise blue accent */
    font-size: 0.95rem;
}

/* Empty State Handling Design Parameters */
.empty-state {
    text-align: center;
    padding: 60px 40px;
    color: #55555f;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.empty-icon {
    font-size: 2.5rem;
    color: #3a3a42;
}

.empty-state p {
    margin: 0;
    font-size: 0.95rem;
}
/* Pagination Styling */
.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #222226;
}

.pagination-nav {
    display: flex;
    gap: 6px;
    align-items: center;
}

.pagination-link {
    background-color: #222227;
    border: 1px solid #2d2d2d;
    color: #e0e0e6;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.pagination-link:hover:not(.disabled) {
    background-color: #2d2d35;
    border-color: #ff2770;
    color: #ff2770;
}

.pagination-link.active {
    background-color: #ff2770;
    border-color: #ff2770;
    color: #ffffff;
    font-weight: 600;
}

.pagination-link.disabled {
    color: #44444f;
    background-color: #16161a;
    border-color: #222226;
    cursor: not-allowed;
}
</style>