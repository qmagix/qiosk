<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const stats = ref({
  is_admin: false,
  total_users: 0,
  regular_users: 0,
  total_playlists: 0,
  total_assets: 0,
  recent_signups: [],
  recent_playlists: []
})
const loading = ref(true)
const error = ref('')

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/dashboard', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    stats.value = { ...stats.value, ...response.data }
  } catch (e) {
    console.error(e)
    error.value = 'Failed to load dashboard data'
  } finally {
    loading.value = false
  }
}

onMounted(fetchStats)
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

    <div v-if="loading" class="text-gray-500">Loading statistics...</div>
    <div v-else-if="error" class="text-red-500">{{ error }}</div>
    
    <div v-else>
      <!-- Admin Dashboard -->
      <div v-if="stats.is_admin">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium uppercase">Total Users</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total_users }}</div>
            <div class="text-sm text-gray-500 mt-1">{{ stats.regular_users }} regular users</div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium uppercase">Total Assets</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ stats.total_assets }}</div>
            <div class="text-sm text-gray-500 mt-1">Photos & Videos</div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium uppercase">Playlists</div>
            <div class="mt-2 text-3xl font-bold text-green-600">{{ stats.total_playlists }}</div>
            <div class="text-sm text-gray-500 mt-1">Active playlists</div>
          </div>
        </div>

        <!-- Recent Signups -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent Signups</h3>
          </div>
          <ul class="divide-y divide-gray-200">
            <li v-for="user in stats.recent_signups" :key="user.id" class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-sm text-gray-500">{{ user.email }}</div>
                </div>
                <div class="flex items-center">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="{
                      'bg-purple-100 text-purple-800': user.role === 'superadmin',
                      'bg-blue-100 text-blue-800': user.role === 'admin',
                      'bg-gray-100 text-gray-800': user.role === 'regular'
                    }"
                  >
                    {{ user.role }}
                  </span>
                  <span class="ml-4 text-sm text-gray-500">
                    {{ new Date(user.created_at).toLocaleDateString() }}
                  </span>
                </div>
              </div>
            </li>
            <li v-if="!stats.recent_signups?.length" class="px-6 py-4 text-gray-500 text-sm">
              No recent signups found.
            </li>
          </ul>
        </div>
      </div>

      <!-- Regular User Dashboard -->
      <div v-else>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium uppercase">My Assets</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ stats.total_assets }}</div>
            <div class="mt-4">
              <router-link to="/admin/assets" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Manage Assets &rarr;
              </router-link>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium uppercase">My Playlists</div>
            <div class="mt-2 text-3xl font-bold text-green-600">{{ stats.total_playlists }}</div>
            <div class="mt-4">
              <router-link to="/admin/playlists" class="text-green-600 hover:text-green-800 text-sm font-medium">
                Manage Playlists &rarr;
              </router-link>
            </div>
          </div>
        </div>

        <!-- Recent Playlists -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Recent Playlists</h3>
            <router-link to="/admin/playlists/new" class="text-sm text-blue-600 hover:text-blue-800">
              + Create New
            </router-link>
          </div>
          <ul class="divide-y divide-gray-200">
            <li v-for="playlist in stats.recent_playlists" :key="playlist.id" class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ playlist.name }}</div>
                  <div class="text-sm text-gray-500">Updated {{ new Date(playlist.updated_at).toLocaleDateString() }}</div>
                </div>
                <router-link :to="'/admin/playlists/' + playlist.id" class="text-blue-600 hover:text-blue-900 text-sm">
                  Edit
                </router-link>
              </div>
            </li>
            <li v-if="!stats.recent_playlists?.length" class="px-6 py-4 text-gray-500 text-sm">
              No playlists found. Create one to get started!
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
