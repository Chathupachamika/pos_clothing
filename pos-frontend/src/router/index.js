import { createRouter, createWebHashHistory } from "vue-router";
import Signup from "@/components/Signup.vue";
import Signin from "@/components/Signin.vue";
import Dashboard from "@/components/Dashboard.vue";
import SalesDashboard from "@/components/SalesDashboard.vue";
import Customers from "@/components/Customers.vue";
import EmployeeManagement from "@/components/EmployeeManagement.vue";
import SupplierManagement from "@/components/SupplierManagement.vue";
import ProductManagement from "@/components/ProductManagement.vue";
import PlaceOrder from "@/components/PlaceOrder.vue";
import LowStock from "@/components/LowStock.vue";
import OrderManagement from "@/components/OrderManagement.vue";
import InventoryManagement from "@/components/InventoryManagement.vue";
import ReturnItem from "@/components/ReturnItem.vue";
import ManageAccountDetails from "@/components/ManageAccountDetails.vue";

const router = createRouter({
    history: createWebHashHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/",
            name: "signin",
            component: Signin
        },
        {
            path: "/signup",
            name: "signup",
            component: Signup
        },
        {
            path: "/dashboard",
            name: "dashboard",
            component: Dashboard
        },
        {
            path: "/sales-dashboard",
            name: "sales-dashboard",
            component: SalesDashboard
        },
        {
            path: "/customers",
            name: "customers",
            component: Customers
        },
        {
            path: "/low-stock",
            name: "LowStock",
            component: LowStock
        },
        {
            path: "/employees",
            name: "employees",
            component: EmployeeManagement
        },
        {
            path: "/suppliers",
            name: "suppliers",
            component: SupplierManagement
        },
        {
            path: "/products",
            name: "products",
            component: ProductManagement
        },
        {
            path: "/place-order",
            name: "place-order",
            component: PlaceOrder
        },
        {
            path: "/order-management",
            name: "order-management",
            component: OrderManagement
        },
        {
            path: "/inventory-management",
            name: "inventory-management",
            component: InventoryManagement
        },
        {
            path: "/return-items",
            name: "return-items",
            component: ReturnItem
        },
        {
            path: "/manage-account",
            name: "manage-account",
            component: ManageAccountDetails
        }
    ]
});

export default router;