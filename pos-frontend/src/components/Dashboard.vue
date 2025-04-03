<script setup>
import Header from "./Header.vue";
import Sidebar from "./Sidebar.vue";
import { onMounted, onActivated, ref, computed } from "vue";
import { storeToRefs } from 'pinia'
import { useDashboardStore } from '@/stores/dashboard'
import {
  CurrencyDollarIcon,
  ShoppingCartIcon,
  UsersIcon,
  BuildingOfficeIcon,
  CubeIcon,
  UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from "chart.js";

ChartJS.register(ArcElement, Tooltip, Legend);

const isSidebarVisible = ref(false);
const dashboardStore = useDashboardStore()
const { dashboardStats, chartData, recentTransactions, isLoading } = storeToRefs(dashboardStore)

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value;
};

const closeSidebar = () => {
  isSidebarVisible.value = false;
};

const showSidebar = () => {
  isSidebarVisible.value = true;
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "bottom",
      labels: {
        color: "#e5e7eb",
      },
    },
  },
};

const getPaymentMethodStyle = (method) => {
  switch (method) {
    case 'CASH':
      return 'bg-green-500/10 text-green-400';
    case 'CREDIT_CARD':
    case 'DEBIT_CARD':
      return 'bg-blue-500/10 text-blue-400';
    default:
      return 'bg-purple-500/10 text-purple-400';
  }
};

const getStatusColor = (status) => {
  switch (status) {
    case 1:
      return 'bg-green-500';
    case 0:
      return 'bg-yellow-500';
    default:
      return 'bg-red-500';
  }
};

const userName = computed(() => {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  return user.name || 'User';
});

onMounted(async () => {
  await dashboardStore.fetchDashboardData()
})

onActivated(() => {
  dashboardStore.fetchDashboardData()
})

</script>
<template>
  <div
    class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"
  >
    <div
      class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger"
      @mouseenter="showSidebar"
    ></div>

    <Sidebar :isVisible="isSidebarVisible" @closeSidebar="closeSidebar" />
    <Header @toggleSidebar="toggleSidebar" />
    <div class="w-full p-4 lg:p-8 pt-28 lg:pt-28">
      <div
        class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4"
      >
        <div>
          <h1
            class="text-2xl lg:text-3xl font-bold mb-2 bg-gradient-to-r from-blue-400 to-teal-400 bg-clip-text text-transparent"
          >
            Welcome Back, {{ userName }}!
          </h1>
          <p class="text-gray-400 text-sm">Last login: Today at 15:32</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <template v-if="isLoading">
          <div v-for="i in 6" :key="i" 
            class="rounded-xl p-6 transform transition-all duration-200 shadow-lg relative overflow-hidden bg-gray-800/50">
            <div class="animate-pulse space-y-4">
              <div class="flex items-center justify-between">
                <div class="h-6 bg-gray-700 rounded w-1/3"></div>
                <div class="h-8 w-8 bg-gray-700 rounded"></div>
              </div>
              <div class="h-8 bg-gray-700 rounded w-1/2"></div>
              <div class="h-4 bg-gray-700 rounded w-2/3"></div>
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-gray-800/10 to-transparent shimmer"></div>
            </div>
          </div>
        </template>

        <template v-else>
          <div
            class="rounded-xl p-6 transform hover:scale-[1.02] transition-all duration-200 shadow-lg relative overflow-hidden animated-gradient-1"
          >
            <div class="absolute inset-0 animate-gradient-1"></div>
            <div class="relative z-10 text-white">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold opacity-90">Weekly Earnings</h3>
                <CurrencyDollarIcon class="w-8 h-8 opacity-75 animate-float-1" />
              </div>
              <div class="text-3xl font-bold mb-2">Rs. {{ dashboardStats.weeklyEarnings.toLocaleString() }}</div>
              <div class="text-sm font-medium opacity-75">
                {{ dashboardStats.salesGrowth }} from last week
              </div>
            </div>
          </div>

          <div
            class="rounded-xl p-6 transform hover:scale-[1.02] transition-all duration-200 shadow-lg relative overflow-hidden animated-gradient-2"
          >
            <div class="absolute inset-0 animate-gradient-2"></div>
            <div class="relative z-10 text-white">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold opacity-90">Total Sales</h3>
                <ShoppingCartIcon class="w-8 h-8 opacity-75 animate-float-2" />
              </div>
              <div class="text-3xl font-bold mb-2">{{ dashboardStats.totalSales }}</div>
              <div class="text-sm font-medium opacity-75">
                {{ dashboardStats.salesGrowth }} from last month
              </div>
            </div>
          </div>

          <div
            class="rounded-xl p-6 transform hover:scale-[1.02] transition-all duration-200 shadow-lg relative overflow-hidden animated-gradient-3"
          >
            <div class="absolute inset-0 animate-gradient-3"></div>
            <div class="relative z-10 text-white">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold opacity-90">Total Customers</h3>
                <UsersIcon class="w-8 h-8 opacity-75 animate-float-3" />
              </div>
              <div class="text-3xl font-bold mb-2">{{ dashboardStats.totalCustomers }}</div>
              <div class="text-sm font-medium opacity-75">
                {{ dashboardStats.customerGrowth }} from last month
              </div>
            </div>
          </div>

          <div
            class="rounded-xl p-6 transform hover:scale-[1.02] transition-all duration-200 shadow-lg relative overflow-hidden animated-gradient-4"
          >
            <div class="absolute inset-0 animate-gradient-4"></div>
            <div class="relative z-10 text-white">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold opacity-90">Total Suppliers</h3>
                <BuildingOfficeIcon class="w-8 h-8 opacity-75 animate-float-4" />
              </div>
              <div class="text-3xl font-bold mb-2">{{ dashboardStats.totalSuppliers }}</div>
              <div class="text-sm font-medium opacity-75">
                {{ dashboardStats.supplierGrowth }} from last month
              </div>
            </div>
          </div>

          <div
            class="rounded-xl p-6 transform hover:scale-[1.02] transition-all duration-200 shadow-lg relative overflow-hidden animated-gradient-5"
          >
            <div class="absolute inset-0 animate-gradient-5"></div>
            <div class="relative z-10 text-white">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold opacity-90">Total Products</h3>
                <CubeIcon class="w-8 h-8 opacity-75 animate-float-5" />
              </div>
              <div class="text-3xl font-bold mb-2">{{ dashboardStats.totalProducts }}</div>
              <div class="text-sm font-medium opacity-75">
                {{ dashboardStats.productGrowth }} from last month
              </div>
            </div>
          </div>

          <div
            class="rounded-xl p-6 transform hover:scale-[1.02] transition-all duration-200 shadow-lg relative overflow-hidden animated-gradient-6"
          >
            <div class="absolute inset-0 animate-gradient-6"></div>
            <div class="relative z-10 text-white">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold opacity-90">Total Employees</h3>
                <UserGroupIcon class="w-8 h-8 opacity-75 animate-float-6" />
              </div>
              <div class="text-3xl font-bold mb-2">{{ dashboardStats.totalEmployees }}</div>
              <div class="text-sm font-medium opacity-75">
                {{ dashboardStats.employeeGrowth }} - {{ dashboardStats.employeeStatus }}
              </div>
            </div>
          </div>
        </template>
      </div>

      <div class="flex gap-6">
        <div
          class="w-3/4 bg-gradient-to-br from-gray-800 to-gray-800/50 backdrop-blur-sm rounded-xl p-6 overflow-hidden shadow-xl border border-gray-700/50"
        >
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-200">Recent Transactions</h2>
            <button
              class="text-sm text-blue-400 hover:text-blue-300 transition-colors duration-200"
            >
              View All
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-gray-200">
              <thead>
                <tr class="text-left text-gray-300 text-sm">
                  <th class="pb-4 font-medium">Product</th>
                  <th class="pb-4 font-medium">Date</th>
                  <th class="pb-4 font-medium">Method</th>
                  <th class="pb-4 font-medium">Amount</th>
                  <th class="pb-4 font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading" class="hover:bg-gray-700">
                  <td colspan="5" class="px-6 py-4">
                    <div class="h-[400px] flex flex-col items-center justify-center space-y-4">
                      <div class="w-16 h-16 border-4 border-t-blue-500 border-r-transparent border-b-purple-500 border-l-transparent rounded-full animate-spin"></div>
                      <span class="text-sm text-gray-400">Loading transactions...</span>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="recentTransactions.length === 0" class="hover:bg-gray-700">
                  <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                    No transactions found
                  </td>
                </tr>
                <template v-else>
                  <tr v-for="transaction in recentTransactions" 
                      :key="transaction.id" 
                      class="border-t border-gray-700/50 hover:bg-gray-700/20 transition-colors duration-200">
                    <td class="py-4">
                      <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-700 to-gray-600 flex items-center justify-center text-white text-xs font-bold mr-3">
                          {{ transaction.product.charAt(0) }}
                        </div>
                        <div>
                          <div class="font-semibold text-gray-200">{{ transaction.product }}</div>
                          <div class="text-xs text-gray-400">Order #{{ transaction.id }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="text-gray-300">{{ transaction.date }}</td>
                    <td>
                      <span :class="[
                        getPaymentMethodStyle(transaction.method),
                        'px-2 py-1 rounded-full text-xs font-medium'
                      ]">
                        {{ transaction.method.replace('_', ' ') }}
                      </span>
                    </td>
                    <td class="font-bold">Rs. {{ transaction.amount.toLocaleString() }}</td>
                    <td>
                      <div class="flex items-center">
                        <div :class="[
                          getStatusColor(transaction.status),
                          'w-2 h-2 rounded-full mr-2'
                        ]"></div>
                        <span class="text-xs text-gray-400">
                          {{ transaction.status === 1 ? 'Completed' : 'Pending' }}
                        </span>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <div
          class="w-1/4 bg-gradient-to-br from-gray-800 to-gray-800/50 backdrop-blur-sm rounded-xl p-6 overflow-hidden shadow-xl border border-gray-700/50"
        >
          <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-200">
              Payment Distribution
            </h2>
          </div>
          <div class="h-[300px] flex items-center justify-center">
            <template v-if="isLoading">
              <div class="flex flex-col items-center justify-center space-y-4">
                <div class="w-16 h-16 border-4 border-t-blue-500 border-r-transparent border-b-purple-500 border-l-transparent rounded-full animate-spin"></div>
                <span class="text-sm text-gray-400">Loading payment data...</span>
              </div>
            </template>
            <Doughnut v-else :data="chartData" :options="chartOptions" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rounded-xl {
  backdrop-filter: blur(8px);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

.hover-trigger {
  background: transparent;
}

@keyframes gradient-1 {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

@keyframes gradient-2 {
  0% {
    background-position: 100% 50%;
  }
  50% {
    background-position: 0% 50%;
  }
  100% {
    background-position: 100% 50%;
  }
}

@keyframes gradient-3 {
  0% {
    background-position: 50% 0%;
  }
  50% {
    background-position: 50% 100%;
  }
  100% {
    background-position: 50% 0%;
  }
}

@keyframes float-1 {
  0% {
    transform: translateY(0px) rotate(0deg);
  }
  25% {
    transform: translateY(-4px) rotate(2deg);
  }
  50% {
    transform: translateY(0px) rotate(0deg);
  }
  75% {
    transform: translateY(4px) rotate(-2deg);
  }
  100% {
    transform: translateY(0px) rotate(0deg);
  }
}

@keyframes float-2 {
  0% {
    transform: translateY(0px) rotate(0deg);
  }
  25% {
    transform: translateY(4px) rotate(-2deg);
  }
  50% {
    transform: translateY(0px) rotate(0deg);
  }
  75% {
    transform: translateY(-4px) rotate(2deg);
  }
  100% {
    transform: translateY(0px) rotate(0deg);
  }
}

@keyframes float-3 {
  0% {
    transform: translateY(-4px) rotate(2deg);
  }
  25% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(4px) rotate(-2deg);
  }
  75% {
    transform: translateY(0px) rotate(0deg);
  }
  100% {
    transform: translateY(-4px) rotate(2deg);
  }
}

@keyframes gradient-4 {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

@keyframes gradient-5 {
  0% { background-position: 100% 50%; }
  50% { background-position: 0% 50%; }
  100% { background-position: 100% 50%; }
}

@keyframes gradient-6 {
  0% { background-position: 50% 0%; }
  50% { background-position: 50% 100%; }
  100% { background-position: 50% 0%; }
}

@keyframes float-4 {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-4px) rotate(2deg); }
}

@keyframes float-5 {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(4px) rotate(-2deg); }
}

@keyframes float-6 {
  0%, 100% { transform: translateY(-4px) rotate(2deg); }
  50% { transform: translateY(4px) rotate(-2deg); }
}

.animate-gradient-1 {
  background: linear-gradient(-45deg, #00dc82, #36e4da, #0ea5e9, #0284c7);
  background-size: 400% 400%;
  animation: gradient-1 15s ease infinite;
  opacity: 0.95;
}

.animate-gradient-2 {
  background: linear-gradient(-45deg, #fbbf24, #fb923c, #f97316, #ea580c);
  background-size: 400% 400%;
  animation: gradient-2 15s ease infinite;
  opacity: 0.95;
}

.animate-gradient-3 {
  background: linear-gradient(-45deg, #ec4899, #d946ef, #a855f7, #8b5cf6);
  background-size: 400% 400%;
  animation: gradient-3 15s ease infinite;
  opacity: 0.95;
}

.animate-gradient-4 {
  background: linear-gradient(-45deg, #22c55e, #16a34a, #15803d, #166534);
  background-size: 400% 400%;
  animation: gradient-4 15s ease infinite;
  opacity: 0.95;
}

.animate-gradient-5 {
  background: linear-gradient(-45deg, #06b6d4, #0891b2, #0e7490, #155e75);
  background-size: 400% 400%;
  animation: gradient-5 15s ease infinite;
  opacity: 0.95;
}

.animate-gradient-6 {
  background: linear-gradient(-45deg, #f43f5e, #e11d48, #be123c, #9f1239);
  background-size: 400% 400%;
  animation: gradient-6 15s ease infinite;
  opacity: 0.95;
}

.animate-float-1 {
  animation: float-1 4s ease-in-out infinite;
}

.animate-float-2 {
  animation: float-2 4s ease-in-out infinite;
}

.animate-float-3 {
  animation: float-3 4s ease-in-out infinite;
}

.animate-float-4 {
  animation: float-4 4s ease-in-out infinite;
}

.animate-float-5 {
  animation: float-5 4s ease-in-out infinite;
}

.animate-float-6 {
  animation: float-6 4s ease-in-out infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.shimmer {
  animation: shimmer 2s infinite linear;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.05) 50%,
    transparent 100%
  );
  background-size: 200% 100%;
}
</style>
