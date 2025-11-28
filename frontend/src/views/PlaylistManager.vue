<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const playlists = ref([])
const newPlaylistName = ref('')
const newPlaylistOrientation = ref('landscape')
const showCreateModal = ref(false)

const fetchPlaylists = async () => {
  try {
    const response = await axios.get('/api/playlists', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    playlists.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const createPlaylist = async () => {
  if (!newPlaylistName.value) return
  try {
    await axios.post('/api/playlists', {
      name: newPlaylistName.value,
      orientation: newPlaylistOrientation.value
    }, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    newPlaylistName.value = ''
    newPlaylistOrientation.value = 'landscape'
    showCreateModal.value = false
    await fetchPlaylists()
  } catch (e) {
    console.error(e)
  }
}

const deletePlaylist = async (id) => {
  if (!confirm('Are you sure?')) return
  try {
    await axios.delete(`/api/playlists/${id}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    await fetchPlaylists()
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchPlaylists)
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Playlists</h2>
      <button 
        @click="showCreateModal = true" 
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
      >
        New Playlist
      </button>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200">
        <li v-for="playlist in playlists" :key="playlist.id">
          <div class="px-4 py-4 flex items-center justify-between sm:px-6">
            <div class="flex items-center">
              <p class="text-sm font-medium text-blue-600 truncate">{{ playlist.name }}</p>
              <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                {{ playlist.items_count || 0 }} items
              </span>
              <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                :class="playlist.orientation === 'portrait' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'">
                {{ playlist.orientation || 'landscape' }}
              </span>
            </div>
            <div class="flex space-x-2">
              <router-link 
                :to="`/admin/playlists/${playlist.id}`" 
                class="text-blue-500 hover:text-blue-700"
              >
                Edit
              </router-link>
              <a :href="`/play/${playlist.slug}`" target="_blank" class="text-gray-500 hover:text-gray-700">
                Preview
              </a>
              <button @click="deletePlaylist(playlist.id)" class="text-red-500 hover:text-red-700">
                Delete
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-xl w-96">
        <h3 class="text-lg font-medium mb-4">Create Playlist</h3>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input 
            v-model="newPlaylistName" 
            class="border w-full p-2 rounded" 
            placeholder="Playlist Name"
          >
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Orientation</label>
          <select v-model="newPlaylistOrientation" class="border w-full p-2 rounded">
            <option value="landscape">Landscape (Horizontal)</option>
            <option value="portrait">Portrait (Vertical)</option>
          </select>
        </div>
        <div class="flex justify-end space-x-2">
          <button @click="showCreateModal = false" class="text-gray-500">Cancel</button>
          <button @click="createPlaylist" class="bg-blue-500 text-white px-4 py-2 rounded">Create</button>
        </div>
      </div>
    </div>
  </div>
</template>
