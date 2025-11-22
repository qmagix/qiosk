<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const logout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Media Kiosk Admin</h1>
        <button @click="logout" class="text-red-600 hover:text-red-800">Logout</button>
      </div>
    </header>
    <div class="flex flex-1">
      <aside class="w-64 bg-gray-800 text-white">
        <nav class="mt-5 px-2">
          <router-link 
            to="/admin/assets" 
            class="group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150"
            :class="{ 'bg-gray-900': $route.name === 'assets' }"
          >
            Assets
          </router-link>
          <router-link 
            to="/admin/playlists" 
            class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150"
            :class="{ 'bg-gray-900': $route.name === 'playlists' }"
          >
            Playlists
          </router-link>
        </nav>
      </aside>
      <main class="flex-1 p-6">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>
