<template>
  <AuthenticatedLayout>
    <div class="welcome-banner">
      <div class="header-text">
        <h1>Admin Dashboard</h1>
      </div>

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
      <div class="stat-card">
        <h3>Total Sales</h3>
        <p class="value">₹{{ totalsale ? totalsale.toLocaleString() : '0.00' }}</p>
        <span class="trend">+12.5%</span>
      </div>
      <div class="stat-card">
        <h3>Active Products</h3>
        <p class="value">{{ product_active_count || 0 }}</p>
        <span class="trend">+15.5%</span>
      </div>
      <div class="stat-card">
        <h3>Total Customers</h3>
        <p class="value">{{ user_count || 0 }}</p>
        <span class="trend">+5.2%</span>
      </div>
    </div>

    <div class="charts-section">
      <div class="chart-container">
        <h3>Sales (Histogram)</h3>
        <div class="chart-wrapper">
          <Bar :data="barData" :options="chartOptions" />
        </div>
      </div>

      <div class="chart-container">
        <h3>Highest Ordered Product</h3>
        <div class="chart-wrapper">
          <Pie :data="pieData" :options="chartOptions" />
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
            <div class="msg-bubble" style="position: relative; padding-right: 55px;">
              
              <p>{{ msg.text }}</p>
              
              <button 
                v-if="msg.sender === 'bot'"
                type="button"
                @click="copyAction(msg, index)" 
                style="position: absolute; right: 8px; top: 8px; background: #374151; color: #ef4444; border: none; padding: 2px 6px; border-radius: 4px; font-size: 11px; cursor: pointer; font-weight: bold; z-index: 10;"
              >
                {{ activeCopiedIndex === index ? 'Copied!' : 'copy' }}
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
const STATIC_BAR_LABELS = ['Jan Orders', 'Feb Orders', 'Mar Orders', 'Apr Orders', 'May Orders'];
const STATIC_BAR_VALUES = [75000, 52000, 94000, 48000, 81000];

const STATIC_PIE_LABELS = ['Product Alpha', 'Product Beta', 'Product Gamma', 'Product Delta', 'Product Epsilon'];
const STATIC_PIE_VALUES = [35, 25, 20, 12, 8];

// --- 1. Date Filter Logic ---
const filters = reactive({
  start_date: props.server_filters?.start_date || '',
  end_date: props.server_filters?.end_date || ''
});

const applyFilter = () => {
  router.get('/dashboard', { 
    start_date: filters.start_date, 
    end_date: filters.end_date 
  }, {
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
};

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
                backgroundColor: '#ff2770',
                data: Object.values(grouped)
            }]
        };
    }
    return {
        labels: STATIC_BAR_LABELS,
        datasets: [{
            label: 'Sales (₹) [Demo]',
            backgroundColor: 'rgba(255, 39, 112, 0.4)',
            borderColor: '#ff2770',
            borderWidth: 1,
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
            .slice(0, 6);

        return {
            labels: sortedProducts.map(p => p[0]), 
            datasets: [{
                label: 'Total Revenue',
                backgroundColor: ['#ff2770', '#00f2ff', '#7000ff', '#ff8700', '#00ff87'],
                borderWidth: 1,
                data: sortedProducts.map(p => p[1]) 
            }]
        };
    }
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

// --- 4. Chatbot Functionality Layout Core Logic ---
const isChatOpen = ref(false);
const isTyping = ref(false);
const unreadCount = ref(1);
const newMessage = ref('');
const messageBox = ref(null);

// New Reactive Track variable to hold the currently copied element index value
const activeCopiedIndex = ref(null);

const messages = ref([
  { sender: 'bot', text: 'Hello! Ask me any question regarding categories, sales totals, or configurations.', time: getFormattedTime() }
]);

function getFormattedTime() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// FIXED JAVASCRIPT ACTION LOGIC BLOCK
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
/* Welcome Banner Layout */
.welcome-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header-text h1 { font-size: 28px; color: #fff; margin: 0; }
.header-text p { color: #a0aec0; margin: 0; }

/* Filter Section Styles */
.filter-section {
  display: flex;
  gap: 15px;
  background: #25252b;
  padding: 12px 20px;
  border-radius: 12px;
  border: 1px solid #333;
  align-items: flex-end;
}

.date-group { display: flex; flex-direction: column; gap: 5px; }
.date-group label { font-size: 11px; color: #ff2770; text-transform: uppercase; font-weight: bold; }

.date-input {
  background: #1a1a1e;
  border: 1px solid #444;
  color: #fff;
  padding: 6px 10px;
  border-radius: 6px;
  outline: none;
  font-size: 13px;
}
.date-input:focus { border-color: #ff2770; }

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
.filter-btn:hover { box-shadow: 0 0 15px rgba(255, 39, 112, 0.4); }

/* Grid & Card Styles */
.stats-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
  gap: 20px; 
  margin-bottom: 30px; 
}
.stat-card { 
  background: #25252b; 
  padding: 25px; 
  border-radius: 15px; 
  border: 1px solid #333; 
  transition: 0.3s; 
}
.stat-card:hover { border-color: #ff2770; transform: translateY(-5px); }
.stat-card h3 { font-size: 14px; color: #a0aec0; margin-bottom: 10px; }
.stat-card .value { font-size: 24px; font-weight: 700; color: #fff; }
.trend { color: #00f2ff; font-size: 12px; }

/* Charts */
.charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
.chart-container { background: #25252b; padding: 20px; border-radius: 15px; border: 1px solid #333; }
.chart-container h3 { 
  font-size: 16px; 
  color: #fff; 
  margin-bottom: 20px; 
  border-left: 4px solid #ff2770; 
  padding-left: 10px; 
}
.chart-wrapper {  height: 300px; width: 100%; }

@media (max-width: 992px) {
  .welcome-banner { flex-direction: column; align-items: flex-start; gap: 20px; }
  .charts-section { grid-template-columns: 1fr; }
}

/* --- Floating Widget Base Positioning --- */
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
  background: #ff2770;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 15px rgba(255, 39, 112, 0.4);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
}
.chat-fab:hover {
  transform: scale(1.08);
  box-shadow: 0 6px 20px rgba(255, 39, 112, 0.6);
}

.fab-active {
  background: #25252b;
  border: 1px solid #333;
  color: #ff2770;
  transform: rotate(90deg);
}

.chat-fab .badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background: #00f2ff;
  color: #1a1a1e;
  font-size: 10px;
  font-weight: 800;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* --- Main Interface Panel Window --- */
.chat-window {
  position: absolute;
  bottom: 75px;
  right: 0;
  width: 360px;
  max-height: 480px;
  height: calc(100vh - 120px);
  background: #1a1a1e;
  border: 1px solid #333;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: slideIn 0.25s ease-out;
}

@keyframes slideIn {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.chat-header {
  background: #25252b;
  padding: 15px;
  border-bottom: 1px solid #333;
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
  background: rgba(255, 39, 112, 0.1);
  border: 1px solid #ff2770;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ff2770;
  font-size: 20px;
  position: relative;
}

.status-indicator {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  border: 2px solid #25252b;
}
.status-indicator.online { background: #00ff87; }

.bot-info h4 { color: #fff; margin: 0; font-size: 14px; font-weight: 600; }
.bot-info p { color: #a0aec0; margin: 0; font-size: 11px; }

.close-chat-btn {
  background: transparent;
  border: none;
  color: #a0aec0;
  font-size: 18px;
  cursor: pointer;
}
.close-chat-btn:hover { color: #fff; }

.chat-messages {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

.msg-row { display: flex; width: 100%; }
.msg-row.bot { justify-content: flex-start; }
.msg-row.user { justify-content: flex-end; }

.msg-bubble {
  max-width: 80%;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 13px;
  line-height: 1.4;
  position: relative;
}

.bot .msg-bubble {
  background: #25252b;
  color: #e2e8f0;
  border-top-left-radius: 2px;
  border: 1px solid #333;
}

.user .msg-bubble {
  background: #ff2770;
  color: white;
  border-top-right-radius: 2px;
}

.msg-bubble p { margin: 0; word-break: break-word; }
.msg-time {
  display: block;
  font-size: 9px;
  color: #718096;
  margin-top: 4px;
  text-align: right;
}
.user .msg-time { color: rgba(255,255,255,0.7); }

.chat-input-area {
  padding: 12px;
  background: #25252b;
  border-top: 1px solid #333;
  display: flex;
  gap: 8px;
}

.chat-input {
  flex: 1;
  background: #1a1a1e;
  border: 1px solid #444;
  color: white;
  padding: 8px 12px;
  border-radius: 8px;
  outline: none;
  font-size: 13px;
}
.chat-input:focus { border-color: #ff2770; }
.chat-input:disabled { background: #222; color: #777; cursor: not-allowed; }

.chat-send-btn {
  background: #ff2770;
  border: none;
  color: white;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.2s;
}
.chat-send-btn:disabled {
  background: #444;
  color: #718096;
  cursor: not-allowed;
}

.typing-bubble { padding: 12px 16px !important; }
.typing-dots { display: flex; gap: 4px; align-items: center; height: 10px; }
.typing-dots span {
  width: 6px;
  height: 6px;
  background: #ff2770;
  border-radius: 50%;
  animation: bounce 1.4s infinite ease-in-out both;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1.0); }
}

@media (max-width: 480px) {
  .chat-window {
    width: calc(100vw - 30px);
    right: -10px;
    bottom: 70px;
  }
}
</style>