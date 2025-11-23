<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import draggable from 'vuedraggable'

const route = useRoute()
const playlistId = route.params.id

const playlist = ref(null)
const availableAssets = ref([])
const playlistItems = ref([])
const isSaving = ref(false)
const playlistContainer = ref(null)

// Fetch playlist and assets
const fetchData = async () => {
  try {
    const token = localStorage.getItem('token')
    const headers = { Authorization: `Bearer ${token}` }

    const [playlistRes, assetsRes] = await Promise.all([
      axios.get(`/api/playlists/${playlistId}`, { headers }),
      axios.get(`/api/assets`, { headers })
    ])

    playlist.value = playlistRes.data
    // Map existing items to a mutable structure
    playlistItems.value = playlistRes.data.items.map(item => ({
      asset_id: item.asset.id,
      url: item.asset.url,
      filename: item.asset.filename,
      type: item.asset.type,
      duration_seconds: item.duration_seconds,
      transition_effect: item.transition_effect,
      uniqueId: Math.random().toString(36).substr(2, 9) // for drag key
    }))

    availableAssets.value = assetsRes.data
  } catch (e) {
    console.error(e)
    alert('Failed to load data')
  }
}

const addToPlaylist = async (asset) => {
  playlistItems.value.push({
    asset_id: asset.id,
    url: asset.url,
    filename: asset.filename,
    type: asset.type,
    duration_seconds: asset.type === 'image' ? 5 : 0, // 0 or ignored for video
    transition_effect: 'fade',
    uniqueId: Math.random().toString(36).substr(2, 9)
  })

  await nextTick()
  if (playlistContainer.value) {
    playlistContainer.value.scrollTop = playlistContainer.value.scrollHeight
  }
}

const removeFromPlaylist = (index) => {
  playlistItems.value.splice(index, 1)
}

const savePlaylist = async () => {
  isSaving.value = true
  try {
    const token = localStorage.getItem('token')
    await axios.put(`/api/playlists/${playlistId}`, {
      items: playlistItems.value.map(item => ({
        asset_id: item.asset_id,
        duration_seconds: item.duration_seconds,
        transition_effect: item.transition_effect
      }))
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    alert('Playlist saved!')
  } catch (e) {
    console.error(e)
    alert('Failed to save')
  } finally {
    isSaving.value = false
  }
}

onMounted(fetchData)
</script>

<template>
  <div class="flex h-[calc(100vh-8rem)] gap-6">
    <!-- Left: Playlist Items (Draggable) -->
    <div class="w-2/3 flex flex-col">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">
          Editing: {{ playlist?.name }}
        </h2>
        <button 
          @click="savePlaylist" 
          class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
          :disabled="isSaving"
        >
          {{ isSaving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>

      <div ref="playlistContainer" class="bg-gray-50 flex-1 overflow-y-auto p-4 rounded border">
        <draggable 
          v-model="playlistItems" 
          item-key="uniqueId"
          class="space-y-2"
          handle=".drag-handle"
        >
          <template #item="{ element, index }">
            <div class="bg-white p-3 rounded shadow flex items-center gap-4">
              <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 px-2">
                ☰
              </div>
              
              <div class="w-16 h-16 bg-gray-100 flex-shrink-0">
                <img 
                  v-if="element.type === 'image'" 
                  :src="element.url" 
                  class="w-full h-full object-cover"
                >
                <video 
                  v-else 
                  :src="element.url" 
                  class="w-full h-full object-cover"
                ></video>
              </div>

              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ element.filename }}</div>
                <div class="text-xs text-gray-500 uppercase">{{ element.type }}</div>
              </div>

              <!-- Settings -->
              <div class="flex flex-col gap-2 items-end mr-4">
                <div class="flex items-center gap-2" v-if="element.type === 'image'">
                  <label class="text-xs text-gray-600">Duration:</label>
                  <input 
                    type="number" 
                    v-model.number="element.duration_seconds" 
                    class="w-16 border rounded px-2 py-1 text-sm"
                    min="1"
                  >
                </div>
                <div class="flex items-center gap-2">
                  <label class="text-xs text-gray-600">Effect:</label>
                  <select v-model="element.transition_effect" class="border rounded px-2 py-1 text-sm w-24">
                    <option value="fade">Fade</option>
                    <option value="slide">Slide</option>
                    <option value="zoom">Zoom</option>
                  </select>
                </div>
              </div>

              <button 
                @click="removeFromPlaylist(index)" 
                class="text-red-500 hover:text-red-700 px-2"
              >
                ✕
              </button>
            </div>
          </template>
        </draggable>
        
        <div v-if="playlistItems.length === 0" class="text-center text-gray-500 mt-10">
          Playlist is empty. Add assets from the right.
        </div>
      </div>
    </div>

    <!-- Right: Asset Library -->
    <div class="w-1/3 flex flex-col border-l pl-6 h-full">
      <h3 class="text-lg font-semibold mb-4">Available Assets</h3>
      <div class="overflow-y-auto flex-1 pr-2">
        <div class="grid grid-cols-2 gap-3 content-start">
          <div 
            v-for="asset in availableAssets" 
            :key="asset.id" 
            class="relative group cursor-pointer border rounded-lg overflow-hidden hover:ring-2 ring-blue-500 shadow-sm transition-shadow hover:shadow-md"
            @click="addToPlaylist(asset)"
          >
            <div class="aspect-square bg-gray-100 relative">
              <img 
                v-if="asset.type === 'image'" 
                :src="asset.url" 
                class="w-full h-full object-cover"
                loading="lazy"
              >
              <div v-else class="w-full h-full relative">
                <video 
                  :src="asset.url" 
                  class="w-full h-full object-cover"
                ></video>
                <div class="absolute bottom-1 right-1 bg-black bg-opacity-60 text-white text-xs px-1 rounded">
                  VIDEO
                </div>
              </div>
            </div>
            <div class="p-2 bg-white text-xs truncate border-t">
              {{ asset.filename }}
            </div>
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 flex items-center justify-center transition-all">
              <span class="text-white opacity-0 group-hover:opacity-100 font-bold text-2xl drop-shadow-md">+</span>
            </div>
          </div>
        </div>
        <div v-if="availableAssets.length === 0" class="text-center text-gray-500 mt-10">
          No assets found. Upload some in the Asset Manager.
        </div>
      </div>
    </div>
  </div>
</template>
