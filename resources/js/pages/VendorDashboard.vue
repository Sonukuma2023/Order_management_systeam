<script setup>
import VendorLayout from './VendorLayout.vue'
import { Head, router } from '@inertiajs/vue3' 
import { Bar, Pie } from 'vue-chartjs'
import { computed, reactive } from 'vue'; 
import { 
  Chart as ChartJS, 
  Title, 
  Tooltip, 
  Legend, 
  BarElement, 
  CategoryScale, 
  LinearScale, 
  ArcElement 
} from 'chart.js'

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const props = defineProps({
    product_count: Number,
    order_count : Number,
    total_sales : Number,
    result: Array,
    server_filters: Object,
});

// --- Fallback Static Data Constants ---
const STATIC_BAR_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];
const STATIC_BAR_VALUES = [0, 0, 0, 0, 0];

const STATIC_PIE_LABELS = ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'];
const STATIC_PIE_VALUES = [0, 0, 0, 0, 0];

// 1. Data configuration for the Bar Chart
const barChartData = computed(() => {
    const grouped = {};
    
    // Check if dynamic data exists from database
    if (props.result && props.result.length > 0) {
        props.result.forEach(item => {
            const key = item.order || 'Unknown';
            if (!grouped[key]) grouped[key] = 0;
            grouped[key] += item.price;
        });

        // Turn into [key, value] pairs and sort descending by the value
        const sortedData = Object.entries(grouped).sort((a, b) => b[1] - a[1]);

        return {
            labels: sortedData.map(entry => entry[0]),
            datasets: [{
                label: 'Sales (₹)',
                backgroundColor: '#3b82f6', // Electric Blue
                borderRadius: 4,
                borderWidth: 0,
                data: sortedData.map(entry => entry[1])
            }]
        };
    }

    // FALLBACK
    return {
        labels: STATIC_BAR_LABELS,
        datasets: [{
            label: 'Sales (₹) [Demo]',
            backgroundColor: 'rgba(59, 130, 246, 0.2)', // Faded Blue
            borderRadius: 4,
            borderWidth: 0,
            data: STATIC_BAR_VALUES
        }]
    };
});

// Bar Chart Options
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#18181b',
            titleColor: '#fff',
            bodyColor: '#a1a1aa',
            borderColor: '#27272a',
            borderWidth: 1,
            padding: 10,
            displayColors: false,
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#71717a', font: { family: 'Inter', size: 12 } }
        },
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: 'rgba(255,255,255,0.05)' },
            ticks: { color: '#71717a', font: { family: 'Inter', size: 12 }, padding: 10 }
        }
    }
}

// 2. Data configuration for the Pie Chart
const pieChartData = computed(() => {
    const productSales = {};

    if (props.result && props.result.length > 0) {
        props.result.forEach(item => {
            const productName = item.title || 'Unknown Product'; 
            if (!productSales[productName]) {
                productSales[productName] = 0;
            }
            productSales[productName] += item.price;
        });

        const sortedProducts = Object.entries(productSales)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 5);

        return {
            labels: sortedProducts.map(p => p[0]), 
            datasets: [{
                label: 'Total Revenue',
                backgroundColor: ['#3b82f6', '#8b5cf6', '#06b6d4', '#6366f1', '#a855f7'],
                borderColor: '#18181b',
                borderWidth: 2,
                data: sortedProducts.map(p => p[1]) 
            }]
        };
    }

    // FALLBACK
    return {
        labels: STATIC_PIE_LABELS, 
        datasets: [{
            label: 'Total Revenue [Demo]',
            backgroundColor: ['rgba(59,130,246,0.2)', 'rgba(139,92,246,0.2)', 'rgba(6,182,212,0.2)', 'rgba(99,102,241,0.2)', 'rgba(168,85,247,0.2)'],
            borderColor: '#18181b',
            borderWidth: 2,
            data: STATIC_PIE_VALUES 
        }]
    };
});

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: { color: '#a1a1aa', font: { family: 'Inter', size: 12 }, padding: 20, usePointStyle: true }
        },
        tooltip: {
            backgroundColor: '#18181b',
            titleColor: '#fff',
            bodyColor: '#a1a1aa',
            borderColor: '#27272a',
            borderWidth: 1,
            padding: 10,
        }
    }
}

// --- Date Filter Logic ---
const filters = reactive({
    start_date: props.server_filters?.start_date || '',
    end_date: props.server_filters?.end_date || ''
});

const applyFilter = () => {
    router.get('/vendor/dashboard', { 
        start_date: filters.start_date, 
        end_date: filters.end_date 
    }, {
        preserveState: true,
        replace: true
    });
};
</script>

<template>
    <Head title="Vendor Dashboard" />

    <VendorLayout>
        <div class="dashboard-header">
            <div>
                <h1 class="page-title">Overview</h1>
                <p class="page-subtitle">Welcome back. Here's what's happening with your store today.</p>
            </div>

            <div class="filter-section">
                <div class="date-group">
                    <input type="date" v-model="filters.start_date" class="date-input" aria-label="Start Date">
                </div>
                <span class="to-divider">→</span>
                <div class="date-group">
                    <input type="date" v-model="filters.end_date" class="date-input" aria-label="End Date">
                </div>
                <button @click="applyFilter" class="filter-btn">
                    Filter
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue-glow"><i class='bx bx-wallet'></i></div>
                <div class="stat-info">
                    <h3>Total Sales</h3>
                    <p class="stat-value">₹{{ total_sales || 0 }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-purple-glow"><i class='bx bx-box'></i></div>
                <div class="stat-info">
                    <h3>Products</h3>
                    <p class="stat-value">{{ product_count || 0 }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-cyan-glow"><i class='bx bx-cart'></i></div>
                <div class="stat-info">
                    <h3>Total Orders</h3>
                    <p class="stat-value">{{ order_count || 0 }}</p>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Sales Revenue</h3>
                    <p>Overview of your gross sales</p>
                </div>
                <div class="chart-container">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3>Top Performing Products</h3>
                    <p>Revenue distribution</p>
                </div>
                <div class="chart-container">
                    <Pie :data="pieChartData" :options="pieChartOptions" />
                </div>
            </div>
        </div>
    </VendorLayout>
</template>

<style scoped>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.page-title {
    color: #ffffff;
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
}

.page-subtitle {
    color: #a1a1aa;
    font-size: 14px;
    margin: 0;
}

.filter-section {
    display: flex;
    align-items: center;
    gap: 12px;
}

.to-divider {
    color: #71717a;
    font-size: 14px;
}

.date-input {
    background: #18181b; 
    border: 1px solid #27272a;
    color: #ffffff;
    padding: 10px 14px;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color-scheme: dark;
    transition: all 0.2s;
}

.date-input:focus { 
    border-color: #3b82f6; 
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.filter-btn {
    background: #ffffff;
    color: #000000;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
}

.filter-btn:hover { 
    background: #f4f4f5;
    transform: translateY(-1px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: #0f0f11;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.bg-blue-glow { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
.bg-purple-glow { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.2); }
.bg-cyan-glow { background: rgba(6, 182, 212, 0.1); color: #06b6d4; border: 1px solid rgba(6, 182, 212, 0.2); }

.stat-info h3 {
    color: #a1a1aa;
    margin: 0 0 4px 0;
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    color: #ffffff;
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    line-height: 1.1;
    letter-spacing: -1px;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr; 
    gap: 24px;
}

.chart-card {
    background: #0f0f11;
    padding: 24px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.chart-header {
    margin-bottom: 24px;
}

.chart-header h3 {
    color: #ffffff;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.chart-header p {
    color: #71717a;
    font-size: 13px;
    margin: 0;
}

.chart-container {
    position: relative;
    height: 320px; 
    width: 100%;
}

@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .filter-section {
        width: 100%;
        flex-wrap: wrap;
    }
}
</style>