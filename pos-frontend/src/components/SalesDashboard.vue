<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import * as Icons from 'lucide-vue-next'
import Chart from 'chart.js/auto'
import { useRouter } from 'vue-router'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import { connection } from '@/api/axios'
import { data } from 'autoprefixer'

const router = useRouter()

const weeklyChart = ref(null)
const fulfillmentChart = ref(null)

const stats =ref ([
  { 
    label: 'Today Earning', 
    value: '', 
    subtext: 'Last 7 days Overview', 
    icon: Icons.CircleDollarSign,
    decorativeIcon: Icons.Wallet,
    
    color: 'bg-gradient-to-br from-[#00dc82] via-[#36e4da] to-[#0ea5e9] border-[#0ea5e9]/20'
  },
  { 
    label: 'Number of Total Sales', 
    value: '', 
    subtext: 'Total Sales Count', 
    icon: Icons.BarChart3,
    decorativeIcon: Icons.ShoppingCart,
    
    color: 'bg-gradient-to-br from-[#fbbf24] via-[#fb923c] to-[#f97316] border-[#f97316]/20'
  },
  { 
    label: 'Total Customers', 
    value: '', 
    subtext: 'All Time Customers', 
    icon: Icons.UsersRound,
    decorativeIcon: Icons.Users2,
    
    color: 'bg-gradient-to-br from-[#ec4899] via-[#d946ef] to-[#a855f7] border-[#a855f7]/20'
  },
  { 
    label: 'Suppliers', 
    value: '17', 
    subtext: 'Last 24h Customers', 
    icon: Icons.Truck,
    decorativeIcon: Icons.Truck,
    
    color: 'bg-gradient-to-br from-[#3b82f6] via-[#6366f1] to-[#8b5cf6] border-[#8b5cf6]/20'
  }
])

const transactions = [
  { product: 'Metal Device Range', date: '2024/02/19', method: 'Cash', amount: 'Rs. 20,000.00' },
  { product: 'Dense Laminate Sheet', date: '2024/02/19', method: 'Card', amount: 'Rs. 15,000.00' },
  { product: 'Bathroom Supplies', date: '2024/02/19', method: 'Cash', amount: 'Rs. 25,000.00' },
  { product: 'Apple Type Switch', date: '2024/02/19', method: 'Card', amount: 'Rs. 18,000.00' },
]

const topProducts = [
  { name: 'Metal Device Range', percentage: 45 },
  { name: 'Dense Laminate Sheet', percentage: 35 },
  { name: 'Bathroom Supplies', percentage: 28 },
  { name: 'Apple Type Switch', percentage: 25 },
]

const today = new Date();
const fiveDaysAgo = new Date(today);
fiveDaysAgo.setDate(today.getDate() - 5);

const formattedToday = today.toISOString().split('T')[0];
const formattedFiveDaysAgo = fiveDaysAgo.toISOString().split('T')[0];


onMounted(async()=>{
 const sales=await connection.get('reports/sales/today');
 const data=await connection.get(`reports/sales?from=${formattedFiveDaysAgo}&to=${formattedToday}`);

 stats.value[0].value = sales.data.total_income;
  stats.value[1].value = sales.data.total_sales;
  stats.value[2].value = sales.data.total_customers;
  stats.value[3].value = sales.data.total_suppliers;

  chartData.value.labels = data.data.total_sales.map(item => item.date);
  chartData.value.data = data.data.total_sales.map(item => item.total);
 console.log(sales);
 console.log(data);
 console.log(chartData.value.labels);
 console.log(chartData.value.data);
 initCharts();
})

const chartData = ref({
  labels:[],
  data:[]
})

const initCharts = () => {
  if (weeklyChart.value) {
    new Chart(weeklyChart.value, {
      type: 'bar',
      data: {
        labels: chartData.value.labels,
        datasets: [{
          data: chartData.value.data,
          backgroundColor: '#3b82f680',
          hoverBackgroundColor: '#3b82f6',
          borderRadius: 8,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        plugins: { 
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1f2937',
            padding: 12,
            bodyFont: { size: 13 },
            displayColors: false
          }
        },
        scales: {
          y: {
            grid: { color: '#374151' },
            border: { display: false }
          },
          x: {
            grid: { display: false },
            border: { display: false }
          }
        }
      }
    })
  }

  if (fulfillmentChart.value) {
    new Chart(fulfillmentChart.value, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
        datasets: [{
          data: [30, 35, 32, 40, 38],
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: { 
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1f2937',
            padding: 12,
            bodyFont: { size: 13 },
            displayColors: false
          }
        },
        scales: {
          y: {
            grid: { color: '#374151' },
            border: { display: false }
          },
          x: {
            grid: { display: false },
            border: { display: false }
          }
        }
      }
    })
  }
}

const isSidebarVisible = ref(false)

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
}

const showSidebar = () => {
  isSidebarVisible.value = true
}

const closeSidebar = () => {
  isSidebarVisible.value = false
}

onUnmounted(() => {
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800">
    <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger"
         @mouseenter="showSidebar"></div>
    
    <Sidebar 
      :isVisible="isSidebarVisible" 
      @closeSidebar="closeSidebar"
    />

    <Header />

    <div class="w-full p-4 lg:p-8 pt-28 lg:pt-28">
      <div class="mb-12">
        <h1 class="text-3xl lg:text-4xl font-bold mb-3 bg-gradient-to-r from-emerald-400 via-blue-400 to-purple-400 bg-clip-text text-transparent">
          Sales Dashboard
        </h1>
        <p class="text-gray-400 text-sm flex items-center gap-2">
          <component :is="Icons.Clock" class="w-4 h-4" />
          Last updated: Today at 15:32
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div 
          v-for="stat in stats" 
          :key="stat.label"
          :class="[
            stat.color,
            'rounded-3xl p-7 backdrop-blur-xl',
            'transition-all duration-500 hover:shadow-2xl hover:scale-[1.02]',
            'border border-opacity-20 hover:border-opacity-40',
            'relative overflow-hidden group'
          ]"
        >
          <div class="flex items-start justify-between relative z-10">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <component 
                  :is="stat.icon" 
                  class="h-5 w-5 text-white animate-float-1" 
                />
                <p class="text-sm text-white font-medium">{{ stat.label }}</p>
              </div>
              <h3 class="text-2xl font-bold mt-2 text-white">{{ stat.value }}</h3>
              <p class="text-sm text-white/90 mt-2 flex items-center gap-1">
                <component :is="Icons.TrendingUp" class="w-4 h-4" />
                {{ stat.trend }}
              </p>
            </div>
          </div>
          <component 
            :is="stat.decorativeIcon" 
            class="absolute right-4 bottom-4 w-16 h-16 text-white/20 transform rotate-12 group-hover:scale-110 transition-transform duration-300"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-800 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
          <h3 class="text-base font-bold mb-6 flex items-center gap-2 text-white">
            <component :is="Icons.BarChart2" class="w-5 h-5 text-white" />
            Weekly Sales
          </h3>
          <canvas ref="weeklyChart" height="200"></canvas>
        </div>

        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-800 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
          <h3 class="text-base font-bold mb-6 flex items-center gap-2 text-white">
            <component :is="Icons.LineChart" class="w-5 h-5 text-white" />
            Customer Fulfillment
          </h3>
          <canvas ref="fulfillmentChart" height="200"></canvas>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-800 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
          <h3 class="text-base font-bold mb-6 flex items-center gap-2 text-white">
            <component :is="Icons.Trophy" class="w-5 h-5 text-white" />
            Top Products
          </h3>
          <div class="space-y-6">
            <div 
              v-for="product in topProducts" 
              :key="product.name" 
              class="space-y-2"
            >
              <div class="flex justify-between text-sm">
                <span class="text-white">{{ product.name }}</span>
                <span class="font-medium text-white">{{ product.percentage }}%</span>
              </div>
              <div class="h-2 rounded-full bg-gray-700/50">
                <div 
                  class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
                  :style="{ width: `${product.percentage}%` }"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-800 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-base font-bold flex items-center gap-2 text-white">
              <component :is="Icons.Receipt" class="w-5 h-5 text-white" />
              Recent Transactions
            </h3>
            <button class="text-sm text-white hover:text-gray-300 transition-colors duration-200 flex items-center gap-1">
              View All
              <component :is="Icons.ArrowRight" class="w-4 h-4" />
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="text-left text-white text-sm border-b border-gray-700/50">
                  <th class="pb-4 font-bold">Product</th>
                  <th class="pb-4 font-bold">Date</th>
                  <th class="pb-4 font-bold">Method</th>
                  <th class="pb-4 font-bold">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="transaction in transactions" 
                  :key="transaction.product"
                  class="border-b border-gray-700/50 hover:bg-gray-700/20 transition-colors duration-200"
                >
                  <td class="py-4 text-sm font-medium text-white">{{ transaction.product }}</td>
                  <td class="py-4 text-sm text-white">{{ transaction.date }}</td>
                  <td class="py-4">
                    <span 
                      :class="[
                        transaction.method === 'Card' 
                          ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' 
                          : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                        'px-3 py-1 rounded-full text-xs border'
                      ]"
                    >
                      {{ transaction.method }}
                    </span>
                  </td>
                  <td class="py-4 text-sm font-medium text-white">{{ transaction.amount }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rounded-2xl {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.group:hover {
  box-shadow: 0 0 40px rgba(255, 255, 255, 0.08);
}

.hover-trigger {
  background: transparent;
}

@keyframes float-1 {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-4px); }
  100% { transform: translateY(0px); }
}

.animate-float-1 {
  animation: float-1 2s ease-in-out infinite;
}
</style>