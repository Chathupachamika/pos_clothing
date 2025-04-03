<script>
import Header from './Header.vue';
import Sidebar from './Sidebar.vue';
import {ref} from 'vue';

export default {
    components: {
        Header,
        Sidebar
    },
    setup() {
       

       
        const isSidebarVisible = ref(false)

        const ShowModel = ref(false);
        const ShowDeleteModel = ref(false); 
        const showPreviewModel=ref(false);       

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
};

const showSidebar = () => {
  isSidebarVisible.value = true
};

const closeSidebar = () => {
  isSidebarVisible.value = false
};


return{
    isSidebarVisible,
    toggleSidebar,
    showSidebar,
    closeSidebar,
    ShowModel,
    ShowDeleteModel,
    showPreviewModel
};

    }
}




</script>

<template>
<section class=" min-h-screen bg-gradient-to-br from-gray-900 to-gray-800">


    <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger"
         @mouseenter="showSidebar"></div>
    
    <Sidebar 
      :isVisible="isSidebarVisible" 
      @closeSidebar="closeSidebar"
    />

    <Header />


<div v-if="showPreviewModel" id="select-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 overflow-y-auto flex  right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full ">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-800 border-gray-100">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                
                <button @click="showPreviewModel=false" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm h-8 w-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="select-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="p-4 md:p-5 bg-slate-800 ">
                <div class="flex space-x-2">
                    <svg class="w-[24px] h-[24px] text-gray-800 dark:text-cyan-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                        <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                <p class="text-gray-800 dark:text-cyan-400 text-lg font-semibold mb-6">Products:</p>
                
                    </div>
                <ul class="space-y-4 mb-4">
                    <li>
                        <input type="radio" id="job-1" name="job" value="job-1" class="hidden peer" required />
                        <label for="job-1" class="inline-flex items-center justify-between w-full p-5 text-gray-900  border-gray-900 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-500 dark:text-cyan-400 peer-checked:border-blue-600 dark:peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900 boder-hover  dark:bg-gray-700">                           
                            <div class="block">
                                <div class="w-full text-lg font-semibold">Product Id</div>
                                <div class="w-full text-gray-500 dark:text-gray-100">Flowbite</div>
                            </div>
                        </label>
                    </li>
                    <li>
                        <input type="radio" id="job-2" name="job" value="job-2" class="hidden peer">
                        <label for="job-2" class="inline-flex items-center justify-between w-full p-5 text-gray-900  border-gray-900 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-500 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900  dark:text-cyan-400 dark:bg-gray-700">
                            <div class="block">
                                <div class="w-full text-lg font-semibold">Product name</div>
                                <div class="w-full text-gray-500 dark:text-gray-100">Alphabet</div>
                            </div>
                        </label>
                    </li>
                    <li>
                        <input type="radio" id="job-3" name="job" value="job-3" class="hidden peer">
                        <label for="job-3" class="inline-flex items-center justify-between w-full p-5 text-gray-900  border-gray-900 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-500 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900  dark:text-cyan-400 dark:bg-gray-700">
                            <div class="block">
                                <div class="w-full text-lg font-semibold">Qty</div>
                                <div class="w-full text-gray-500 dark:text-gray-100">7</div>
                            </div>
                        </label>
                    </li>
                    <li>
                        <input type="radio" id="job-3" name="job" value="job-3" class="hidden peer">
                        <label for="job-3" class="inline-flex items-center justify-between w-full p-5 text-gray-900  border-gray-900 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-500 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900  dark:text-cyan-400 dark:bg-gray-700 ">
                            <div class="block">
                                <div class="w-full text-lg font-semibold">Brand Name</div>
                                <div class="w-full text-gray-500 dark:text-gray-100">Apple</div>
                            </div>
                        </label>
                    </li>
                    <li>
                        <input type="radio" id="job-3" name="job" value="job-3" class="hidden peer">
                        <label for="job-3" class="inline-flex items-center justify-between w-full p-5 text-gray-900  border-gray-900 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-500 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-900  dark:text-cyan-400 dark:bg-gray-700 ">
                            <div class="block">
                                <div class="w-full text-lg font-semibold">Discount</div>
                                <div class="w-full text-gray-500 dark:text-gray-100">10%</div>
                            </div>
                        </label>
                    </li>
                </ul>
               
            </div>
        </div>
    </div>
</div> 
    


<div v-if="ShowDeleteModel" id="popup-modal" tabindex="-1" class="fixed inset-0 flex items-center justify-center  w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-900">
            <button @click="ShowDeleteModel=false" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="popup-modal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
            <div class="p-4 md:p-5 text-center">
                <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Are you sure you want to delete this stock?</h3>
                <button data-modal-hide="popup-modal" type="button" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center">
                    Yes, Delete
                </button>
                <button @click="ShowDeleteModel=false" data-modal-hide="popup-modal" type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">No, cancel</button>
            </div>
        </div>
    </div>
</div>


<div v-if="ShowModel" id="select-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 overflow-y-auto flex  right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full ">
    <div class="relative p-4 w-full max-w-lg">
        <div class="bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-3">
                <h3 class="text-lg font-semibold text-white">
                    Edit Stock
                </h3>
                <button @click="ShowModel=false" type="button" class="text-gray-400 bg-transparent hover:bg-gray-600 hover:text-white rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
            <form class="space-y-7">
                <div class="bg-gray-700 p-3 rounded-lg">
                    <label for="id" class="block text-sm font-medium text-gray-300 mb-1">Product Id</label>
                    <input type="number" name="id" id="id" class="w-full px-4 py-2.5 bg-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-800" placeholder="Enter product name">
                </div>
                <div class="bg-gray-700 p-3 rounded-lg">
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Product Name</label>
                    <input type="text" name="name" id="name" class="w-full px-4 py-2.5 bg-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-800" placeholder="Enter product name">
                </div>
                <div class="bg-gray-700 p-3 rounded-lg">
                    <label for="quitity" class="block text-sm font-medium text-gray-300 mb-1">Qty</label>
                    <input type="number" name="email" id="quitity" class="w-full px-4 py-2.5 bg-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-800" placeholder="Enter qty">
                </div>
                <div class="bg-gray-700 p-3 rounded-lg">
                    <label for="brand" class="block text-sm font-medium text-gray-300 mb-1">Brand Name</label>
                    <input type="text" name="brand" id="contact" class="w-full px-4 py-2.5 bg-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-800" placeholder="Brand name">
                </div>
                <div class="bg-gray-700 p-3 rounded-lg">
                    <label for="discount" class="block text-sm font-medium text-gray-300 mb-1">Discount</label>
                    <input type="discount" name="discount" id="contact" class="w-full px-4 py-2.5 bg-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-800" placeholder="discount">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" @click="ShowModel=false" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mx-auto p-6 ">
        <div class="sub_header flex flex-col md:flex-row justify-between items-center mb-6 ">
            <div class="flex space-x-4">
            <div>        
                    <img class="size-40" src="@/assets/img/image-removebg-preview (5).png" alt="Customers Image">
                </div>
                <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                    Low Stock</h1>
                     <div></div>
                     
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="search" placeholder="Search products..."
                                class="w-80 px-4 py-2 bg-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-700 text-cyan-50">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute right-2 top-2.5 text-gray-400" />
                        </div>
                        <button
                            class="px-4 py-2 bg-blue-600 rounded-md hover:bg-blue-700 font-medium inline-flex items-center text-cyan-50">
                            Search
                        </button>
                    </div>
        </div>

        <div class="overflow-x-auto">
           <table class="w-full table-auto">
          
                    <thead>
                    <tr class=" bg-gray-700">
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Product ID</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Product Name</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Brand Name</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Discount</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                 
                    <tr class="bg-gray-800 hover:bg-gray-700 transition-colors duration-200 text-white border-collapse border-cyan-300">
                        <td class="px-6 py-4 whitespace-nowrap">201</td>
                        <td class="px-6 py-4 whitespace-nowrap">glasses</td>
                        <td class="px-6 py-4 whitespace-nowrap">8 pack</td>
                        <td class="px-6 py-4 whitespace-nowrap">ATW</td>
                        <td class="px-6 py-4 whitespace-nowrap">10%</td>
                        <td class="px-6 py-4 whitespace-nowrap text-left space-x-2">
                            <button @click="showPreviewModel=true" class="bg-gray-600 p-2 rounded-lg hover:bg-gray-500">
                         <svg class="w-[24px] h-[24px] text-gray-800 dark:text-cyan-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                        <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                </button>
                            <button @click="ShowModel=true" class="bg-gray-600 p-2 rounded-lg hover:bg-gray-500">
                    <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.779 17.779 4.36 19.918 6.5 13.5m4.279 4.279 8.364-8.643a3.027 3.027 0 0 0-2.14-5.165 3.03 3.03 0 0 0-2.14.886L6.5 13.5m4.279 4.279L6.499 13.5m2.14 2.14 6.213-6.504M12.75 7.04 17 11.28"/>
                    </svg>
                </button>
                <button @click="ShowDeleteModel=true" class="bg-gray-600 p-2 rounded-lg hover:bg-gray-500">
                    <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M10 12v1h4v-1m4 7H6a1 1 0 0 1-1-1V9h14v9a1 1 0 0 1-1 1ZM4 5h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                    </svg>
                </button>
                
                        </td>
                    </tr>
    </tbody>
</table>


        </div>
    </div>


</section>
</template>

<style>
.sub_header{
    margin-top: 6%;
}

.cust-table{
    background-color: #171821;
}
.cust-section{
    background-color: #242424;
}
</style>