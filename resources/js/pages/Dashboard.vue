<template>
  <AuthenticatedLayout>
    <div class="dashboard-header">
      <div>
        <h1 class="page-title">Admin Overview</h1>
        <p class="page-subtitle"> </p>
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
        <div class="stat-icon bg-blue-glow"><i class='bx bx-line-chart'></i></div>
        <div class="stat-info">
          <h3>Total Sales</h3>
          <p class="stat-value">₹{{ totalsale ? totalsale.toLocaleString() : '0.00' }}</p>
          <span class="trend">+12.5%</span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon bg-purple-glow"><i class='bx bx-cube-alt'></i></div>
        <div class="stat-info">
          <h3>Active Products</h3>
          <p class="stat-value">{{ product_active_count || 0 }}</p>
          <span class="trend">+15.5%</span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon bg-cyan-glow"><i class='bx bx-user'></i></div>
        <div class="stat-info">
          <h3>Total Customers</h3>
          <p class="stat-value">{{ user_count || 0 }}</p>
          <span class="trend">+5.2%</span>
        </div>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-card">
        <div class="chart-header">
          <h3>Sales Revenue</h3>
          <p>Global platform sales overview</p>
        </div>
        <div class="chart-container">
          <Bar :data="barData" :options="chartOptions" />
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <h3>Top Performing Products</h3>
          <p>Highest ordered items globally</p>
        </div>
        <div class="chart-container">
          <Pie :data="pieData" :options="pieOptions" />
        </div>
      </div>
    </div>

    <div class="chatbot-wrapper">
      <button @click="toggleChat" class="chat-fab" :class="{ 'fab-active': isChatOpen }">
        <i :class="isChatOpen ? 'bx bx-x' : 'bx bx-message-square-dots'"></i>
        <span v-if="!isChatOpen && unreadCount > 0" class="badge">{{ unreadCount }}</span>
      </button>

      <div v-if="isChatOpen" class="chat-window">
        <div class="chat-header">
          <div class="bot-info">
            <div class="bot-avatar">
              <i class='bx bx-bot'></i>
              <span class="status-indicator online"></span>
            </div>
            <div>
              <h4>Admin Assistant</h4>
              <p>Online & Ready</p>
            </div>
          </div>
          <button @click="toggleChat" class="close-chat-btn"><i class='bx bx-minus'></i></button>
        </div>
        
        <div class="chat-messages" ref="messageBox">
          <div v-for="(msg, index) in messages" :key="index" :class="['msg-row', msg.sender]">
            <div class="msg-bubble" style="position: relative;">
              <p>{{ msg.text }}</p>
              
              <button 
                v-if="msg.sender === 'bot'"
                type="button"
                @click="copyAction(msg, index)" 
                class="copy-btn"
              >
                {{ activeCopiedIndex === index ? 'Copied!' : 'Copy' }}
              </button>

              <span class="msg-time">{{ msg.time }}</span>
            </div>
          </div>
          
          <div v-if="isTyping" class="msg-row bot">
            <div class="msg-bubble typing-bubble">
              <div class="typing-dots"><span></span><span></span><span></span></div>
            </div>
          </div>
        </div>
       
        <form @submit.prevent="sendMessage" class="chat-input-area">
          <input 
            v-model="newMessage" 
            type="text" 
            placeholder="Ask about sales, products..." 
            maxLength="150"
            class="chat-input"
            :disabled="isTyping"
          />
          <button type="submit" :disabled="!newMessage.trim() || isTyping" class="chat-send-btn">
            <i class='bx bx-send'></i>
          </button>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { reactive, computed, ref, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from './AuthenticatedLayout.vue';
import { Bar, Pie } from 'vue-chartjs';
import axios from 'axios';
import { 
  Chart as ChartJS, Title, Tooltip, Legend, BarElement, 
  CategoryScale, LinearScale, ArcElement 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
  totalsale: Number,
  product_active_count: Number,
  result: Array,
  category: Array,
  user_count: Number,
  server_filters: Object 
});

// --- Fallback Static Data Constants ---
const STATIC_BAR_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May'];
const STATIC_BAR_VALUES = [ 0, 0, 0, 0, 0];

const STATIC_PIE_LABELS = ['Product Alpha', 'Product Beta', 'Product Gamma', 'Product Delta', 'Product Epsilon'];
const STATIC_PIE_VALUES = [0, 0, 0, 0, 0];

// --- 1. Date Filter Logic ---
const filters = reactive({
  start_date: props.server_filters?.start_date || '',
  end_date: props.server_filters?.end_date || ''
});

const applyFilter = () => {
  router.get('/dashboard', { 
    start_date: filters.start_date, 
    end_date: filters.end_date 
  },{
    preserveState: true,
    replace: true
  });
};

// --- 2. Chart Configurations ---
const chartOptions = {
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
};

const pieOptions = {
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

// --- 3. Chart Data Processing ---
const barData = computed(() => {
    const grouped = {};
    if (props.result && props.result.length > 0) {
        props.result.forEach(item => {
            const key = item.order || 'Unknown';
            if (!grouped[key]) grouped[key] = 0;
            grouped[key] += item.price;
        });

        return {
            labels: Object.keys(grouped),
            datasets: [{
                label: 'Sales (₹)',
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                borderWidth: 0,
                data: Object.values(grouped)
            }]
        };
    }
    return {
        labels: STATIC_BAR_LABELS,
        datasets: [{
            label: 'Sales (₹) [Demo]',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderRadius: 4,
            borderWidth: 0,
            data: STATIC_BAR_VALUES
        }]
    };
});

const pieData = computed(() => {
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

// --- 4. Chatbot Functionality Layout Core Logic ---
const isChatOpen = ref(false);
const isTyping = ref(false);
const unreadCount = ref(1);
const newMessage = ref('');
const messageBox = ref(null);

const activeCopiedIndex = ref(null);

const messages = ref([
  { sender: 'bot', text: 'Hello! Ask me any question regarding categories, sales totals, or configurations.', time: getFormattedTime() }
]);

function getFormattedTime() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

const copyAction = (msg, index) => {
  if (navigator && navigator.clipboard) {
    navigator.clipboard.writeText(msg.text)
      .then(() => {
        activeCopiedIndex.value = index;
        setTimeout(() => {
          if (activeCopiedIndex.value === index) {
            activeCopiedIndex.value = null;
          }
        }, 2000);
      })
      .catch(err => {
        console.error("Could not copy chat text output: ", err);
      });
  }
};

const toggleChat = () => {
  isChatOpen.value = !isChatOpen.value;
  if (isChatOpen.value) {
    unreadCount.value = 0;
    scrollToBottom();
  }
};

const scrollToBottom = async () => {
  await nextTick();
  if (messageBox.value) {
    messageBox.value.scrollTop = messageBox.value.scrollHeight;
  }
};

const sendMessage = () => {
  const cleanInput = newMessage.value.trim();
  if (!cleanInput || isTyping.value) return;

  messages.value.push({
    sender: 'user',
    text: cleanInput,
    time: getFormattedTime()
  });

  newMessage.value = '';
  scrollToBottom();
  
  processBotIntent(cleanInput);
};

const processBotIntent = async (input) => {
  isTyping.value = true;
  scrollToBottom();

  try {
    const response = await axios.post('/admin/chatbot/query', { 
      question: input 
    });

    if (response.data && response.data.success) {
      messages.value.push({
        sender: 'bot',
        text: response.data.reply,
        time: getFormattedTime()
      });
    } else {
      messages.value.push({
        sender: 'bot',
        text: "I processed the query but couldn't parse the internal metrics profile.",
        time: getFormattedTime()
      });
    }
  } catch (error) {
    console.error("Chatbot query tracking error:", error);
    messages.value.push({
      sender: 'bot',
      text: "Connection failure with backend processing models.",
      time: getFormattedTime()
    });
  } finally {
    isTyping.value = false;
    scrollToBottom();
  }
};

watch(isChatOpen, (val) => {
  if (val) setTimeout(scrollToBottom, 80);
}); 
</script>

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
    font-size: 11px;
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
.trend { 
    color: #10b981; 
    font-size: 12px; 
    font-weight: 500; 
    margin-left: 8px;
    background: rgba(16, 185, 129, 0.1);
    padding: 2px 6px;
    border-radius: 4px;
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

/* --- Chatbot Styles Update --- */
.chatbot-wrapper {
  position: fixed;
  bottom: 25px;
  right: 25px;
  z-index: 9999;
  font-family: inherit;
}

.chat-fab {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #3b82f6;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.chat-fab:hover {
  transform: scale(1.08);
  box-shadow: 0 12px 30px rgba(59, 130, 246, 0.5);
}

.fab-active {
  background: #18181b;
  border: 1px solid #333;
  color: #3b82f6;
  transform: rotate(90deg);
}

.chat-fab .badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background: #ef4444;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #000;
}

/* --- Main Interface Panel Window --- */
.chat-window {
  position: absolute;
  bottom: 75px;
  right: 0;
  width: 380px;
  max-height: 520px;
  height: calc(100vh - 120px);
  background: #0f0f11;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.6);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideIn 0.25s ease-out;
}

@keyframes slideIn {
  from { transform: translateY(20px) scale(0.95); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

.chat-header {
  background: #18181b;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bot-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.bot-avatar {
  width: 36px;
  height: 36px;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3b82f6;
  font-size: 20px;
  position: relative;
}

.status-indicator {
  position: absolute;
  bottom: 0;
  right: -2px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid #18181b;
}
.status-indicator.online { background: #10b981; }

.bot-info h4 { color: #fff; margin: 0; font-size: 14px; font-weight: 600; }
.bot-info p { color: #10b981; margin: 0; font-size: 11px; }

.close-chat-btn {
  background: rgba(255, 255, 255, 0.05);
  border: none;
  color: #a1a1aa;
  font-size: 18px;
  cursor: pointer;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.close-chat-btn:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.chat-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.msg-row { display: flex; width: 100%; }
.msg-row.bot { justify-content: flex-start; }
.msg-row.user { justify-content: flex-end; }

.msg-bubble {
  max-width: 85%;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.5;
  position: relative;
}

.bot .msg-bubble {
  background: #18181b;
  color: #e4e4e7;
  border-top-left-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding-right: 50px;
}

.user .msg-bubble {
  background: #3b82f6;
  color: white;
  border-top-right-radius: 4px;
}

.msg-bubble p { margin: 0; word-break: break-word; }

.copy-btn {
  position: absolute;
  right: 8px;
  top: 8px;
  background: #27272a;
  color: #a1a1aa;
  border: 1px solid #3f3f46;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 10px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}
.copy-btn:hover { background: #3f3f46; color: #fff; }

.msg-time {
  display: block;
  font-size: 10px;
  color: #71717a;
  margin-top: 6px;
  text-align: right;
}
.user .msg-time { color: rgba(255,255,255,0.8); }

.chat-input-area {
  padding: 16px;
  background: #18181b;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  gap: 10px;
}

.chat-input {
  flex: 1;
  background: #0f0f11;
  border: 1px solid #27272a;
  color: white;
  padding: 10px 14px;
  border-radius: 10px;
  outline: none;
  font-size: 13px;
  transition: border-color 0.2s;
}
.chat-input:focus { border-color: #3b82f6; }
.chat-input:disabled { background: #0f0f11; opacity: 0.5; cursor: not-allowed; }

.chat-send-btn {
  background: #3b82f6;
  border: none;
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.2s;
}
.chat-send-btn:disabled {
  background: #27272a;
  color: #71717a;
  cursor: not-allowed;
}

.typing-bubble { padding: 16px !important; }
.typing-dots { display: flex; gap: 4px; align-items: center; height: 10px; }
.typing-dots span {
  width: 6px;
  height: 6px;
  background: #3b82f6;
  border-radius: 50%;
  animation: bounce 1.4s infinite ease-in-out both;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1.0); }
}
</style>