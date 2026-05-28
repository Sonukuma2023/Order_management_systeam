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
const STATIC_BAR_LABELS = ['Jan Orders', 'Feb Orders', 'Mar Orders', 'Apr Orders', 'May Orders'];
const STATIC_BAR_VALUES = [45000, 32000, 61000, 29000, 50000];

const STATIC_PIE_LABELS = ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'];
const STATIC_PIE_VALUES = [40, 25, 15, 12, 8];


// 1. Data configuration for the Bar Chart (Dynamic vs Static Fallback)
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
                backgroundColor: '#ff2770',
                borderColor: '#ff2770',
                borderWidth: 1,
                data: sortedData.map(entry => entry[1])
            }]
        };
    }

    // FALLBACK: Return Static Data if no data from DB
    return {
        labels: STATIC_BAR_LABELS,
        datasets: [{
            label: 'Sales (₹) [Demo]',
            backgroundColor: 'rgba(255, 39, 112, 0.4)', // Faded variant for elegant empty state
            borderColor: '#ff2770',
            borderWidth: 1,
            data: STATIC_BAR_VALUES
        }]
    };
});

// Bar Chart Options (Configured for Dark Mode visibility)
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: '#aaa' }
        }
    },
    scales: {
        x: {
            grid: { color: '#333' },
            ticks: { color: '#aaa' }
        },
        y: {
            beginAtZero: true,
            grid: { color: '#333' },
            ticks: { color: '#aaa' }
        }
    }
}

// 2. Data configuration for the Pie Chart (Dynamic vs Static Fallback)
const pieChartData = computed(() => {
    const productSales = {};

    // Check if dynamic data exists from database
    if (props.result && props.result.length > 0) {
        props.result.forEach(item => {
            const productName = item.title || 'Unknown Product'; 
            if (!productSales[productName]) {
                productSales[productName] = 0;
            }
            productSales[productName] += item.price;
        });

        // Convert to array, sort by price descending, and take top 5
        const sortedProducts = Object.entries(productSales)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 5);

        return {
            labels: sortedProducts.map(p => p[0]), 
            datasets: [{
                label: 'Total Revenue',
                backgroundColor: ['#ff2770', '#00f2ff', '#7000ff', '#ff8700', '#00ff87'],
                borderWidth: 0,
                data: sortedProducts.map(p => p[1]) 
            }]
        };
    }

    // FALLBACK: Return Static Data if no data from DB
    return {
        labels: STATIC_PIE_LABELS, 
        datasets: [{
            label: 'Total Revenue [Demo]',
            backgroundColor: ['rgba(255, 39, 112, 0.5)', 'rgba(0, 242, 255, 0.5)', 'rgba(112, 0, 255, 0.5)', 'rgba(255, 135, 0, 0.5)', 'rgba(0, 255, 135, 0.5)'],
            borderWidth: 0,
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
            labels: { color: '#aaa', font: { size: 12 } }
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
            
            <h1> Vendor Dashboard </h1>

            <div class="filter-section">
                <div class="date-group">
                    <label>From</label>
                    <input type="date" v-model="filters.start_date" class="date-input">
                </div>
                <div class="date-group">
                    <label>To</label>
                    <input type="date" v-model="filters.end_date" class="date-input">
                </div>
                <button @click="applyFilter" class="filter-btn">
                    <i class='bx bx-filter-alt'></i> Filter
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card">
                <h3>Total Sales</h3>
                <p>₹{{ total_sales || 0 }}</p>
            </div>

            <div class="card">
                <h3>Products</h3>
                <p>{{ product_count || 0 }}</p>
            </div>

            <div class="card">
                <h3>Total Orders</h3>
                <p>{{ order_count || 0 }}</p>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Sales Analysis (Histogram/Bar)</h3>
                <div class="chart-container">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <div class="chart-card">
                <h3>Top Products </h3>
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
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.dashboard-header h1 {
    color: white;
    margin: 0;
}

.filter-section {
    display: flex;
    gap: 15px;
    background: #1a1a1e;
    padding: 10px 15px;
    border-radius: 10px;
    border: 1px solid #333;
    align-items: flex-end;
}

.date-group { 
    display: flex; 
    flex-direction: column; 
    gap: 5px; 
}

.date-group label { 
    font-size: 11px; 
    color: #ff2770; 
    text-transform: uppercase; 
    font-weight: bold; 
}

.date-input {
    background: #25252b; 
    border: 1px solid #444;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    outline: none;
    font-size: 13px;
}

.date-input:focus { 
    border-color: #ff2770; 
}

.filter-btn {
    background: #ff2770;
    color: white;
    border: none;
    padding: 8px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

.filter-btn:hover { 
    box-shadow: 0 0 15px rgba(255, 39, 112, 0.4); 
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #1a1a1e;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #333;
}

.card h3 {
    color: #aaa;
    margin-bottom: 10px;
}

.card p {
    color: #ff2770;
    font-size: 24px;
    font-weight: bold;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr; 
    gap: 20px;
}

.chart-card {
    background: #1a1a1e;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #333;
}

.chart-card h3 {
    color: #fff;
    font-size: 1rem;
    margin-bottom: 20px;
    font-weight: 500;
}

.chart-container {
    position: relative;
    height: 300px; 
    width: 100%;
}

@media (max-width: 992px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .filter-section {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>