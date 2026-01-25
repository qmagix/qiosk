<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import draggable from 'vuedraggable'
import ImageCropperModal from '../components/ImageCropperModal.vue'
import QrCode from '../components/QrCode.vue'

const route = useRoute()
const playlistId = route.params.id

const playlist = ref(null)
const availableAssets = ref([])
const playlistItems = ref([])
const isSaving = ref(false)
const playlistContainer = ref(null)

// Cropper State
const showCropper = ref(false)
const croppingItemIndex = ref(null)
const currentCropImage = ref('')
const currentCropData = ref(null)

// Upload Settings State
const pendingUploads = ref([])
const showUploadQrModal = ref(false)

// Computed properties for visual feedback
const orientation = computed(() => playlist.value?.orientation || 'landscape')

const thumbnailClass = computed(() => {
  return orientation.value === 'portrait' 
    ? 'w-9 h-16' 
    : 'w-28 h-16'
})

const libraryAspectClass = computed(() => {
  return orientation.value === 'portrait'
    ? 'aspect-[9/16]'
    : 'aspect-video'
})

const targetAspectRatio = computed(() => {
  return orientation.value === 'portrait' ? 9 / 16 : 16 / 9
})

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
      crop_data: item.crop_data,
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
    crop_data: null,
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

const openCropper = (index) => {
  const item = playlistItems.value[index]
  if (item.type !== 'image') return
  
  croppingItemIndex.value = index
  currentCropImage.value = item.url
  currentCropData.value = item.crop_data
  showCropper.value = true
}

const saveCrop = (data) => {
  if (croppingItemIndex.value !== null) {
    playlistItems.value[croppingItemIndex.value].crop_data = data
  }
  showCropper.value = false
  croppingItemIndex.value = null
}

const previewPlaylist = async () => {
  await savePlaylist()
  // Use the new URL format that works for both public and private playlists
  const url = getPlaylistUrl()
  if (url) {
    window.open(url, '_blank')
  }
}

const savePlaylist = async () => {
  isSaving.value = true
  try {
    const token = localStorage.getItem('token')
    const response = await axios.put(`/api/playlists/${playlistId}`, {
      orientation: playlist.value.orientation,
      visibility: playlist.value.visibility,
      items: playlistItems.value.map(item => ({
        asset_id: item.asset_id,
        duration_seconds: item.duration_seconds,
        transition_effect: item.transition_effect,
        crop_data: item.crop_data
      }))
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    // Update local playlist data (especially for access_token if it changed)
    playlist.value = response.data
    
    alert('Playlist saved!')
  } catch (e) {
    console.error(e)
    alert('Failed to save')
  } finally {
    isSaving.value = false
  }
}

const getPlaylistUrl = () => {
  if (!playlist.value) return ''
  const baseUrl = window.location.origin
  // Use ID-based URL as requested
  let url = `${baseUrl}/p/${playlist.value.id}`
  
  if (playlist.value.visibility === 'private' && playlist.value.access_token) {
    url += `/${playlist.value.access_token}`
  }
  
  return url
}

const copyUrl = () => {
  const url = getPlaylistUrl()
  if (url) {
    navigator.clipboard.writeText(url).then(() => {
      alert('URL copied to clipboard!')
    })
  }
}

// Upload Settings Methods
const getUploadUrl = () => {
  if (!playlist.value || !playlist.value.allow_uploads || !playlist.value.upload_token) return ''
  const baseUrl = window.location.origin
  return `${baseUrl}/upload/${playlist.value.id}/${playlist.value.upload_token}`
}

const copyUploadUrl = () => {
  const url = getUploadUrl()
  if (url) {
    navigator.clipboard.writeText(url).then(() => {
      alert('Upload URL copied to clipboard!')
    })
  }
}

const toggleUploads = async () => {
  const newValue = !playlist.value.allow_uploads
  try {
    const token = localStorage.getItem('token')
    const response = await axios.put(`/api/playlists/${playlistId}`, {
      allow_uploads: newValue
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    playlist.value = response.data
    if (newValue) {
      fetchPendingUploads()
    }
  } catch (e) {
    console.error(e)
    alert('Failed to update upload settings')
  }
}

const updateUploadMode = async () => {
  try {
    const token = localStorage.getItem('token')
    const response = await axios.put(`/api/playlists/${playlistId}`, {
      upload_mode: playlist.value.upload_mode
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    playlist.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const updateQrFrequency = async () => {
  try {
    const token = localStorage.getItem('token')
    const response = await axios.put(`/api/playlists/${playlistId}`, {
      qr_frequency: playlist.value.qr_frequency
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    playlist.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const fetchPendingUploads = async () => {
  if (!playlist.value?.allow_uploads) return
  try {
    const token = localStorage.getItem('token')
    const response = await axios.get(`/api/playlists/${playlistId}/pending-uploads`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    pendingUploads.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const approveUpload = async (uploadId) => {
  try {
    const token = localStorage.getItem('token')
    await axios.post(`/api/playlists/${playlistId}/pending-uploads/${uploadId}/approve`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    await fetchPendingUploads()
    await fetchData()
  } catch (e) {
    console.error(e)
    alert('Failed to approve upload')
  }
}

const rejectUpload = async (uploadId) => {
  if (!confirm('Are you sure you want to reject this upload?')) return
  try {
    const token = localStorage.getItem('token')
    await axios.delete(`/api/playlists/${playlistId}/pending-uploads/${uploadId}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    await fetchPendingUploads()
  } catch (e) {
    console.error(e)
    alert('Failed to reject upload')
  }
}

// Helper to generate style for cropped thumbnail
const getThumbnailStyle = (item) => {
  if (!item.crop_data) return {}
  
  // If we have crop data, we want to zoom in to show the cropped area.
  // This is tricky with simple CSS 'object-fit'.
  // We can use object-position and scale, but it depends on the container size.
  // A simpler way for the thumbnail is to just let it be 'cover' (center crop) 
  // OR try to approximate.
  // But for WYSIWYG, we really want to see the crop.
  // Let's try using a background image approach for the thumbnail if cropped.
  
  // Actually, let's just stick to object-cover for the thumbnail for now unless we want to implement complex CSS math here.
  // The user asked for "save the crop parameters... when showing/playing".
  // They also said "The landscape pictures are cropped to 9:16 in the assets area... Some looks really good...".
  // So the default 'cover' behavior is what they liked in the asset view.
  // But they want to CUSTOMIZE it.
  
  // If we want to show the CUSTOM crop in the thumbnail:
  // We can use `transform` on the img tag.
  // crop_data has { x, y, width, height } (all relative to natural image dimensions if we used cropper correctly).
  // But wait, cropper.getData() returns absolute pixels.
  // We need to know the natural dimensions to calculate percentages.
  // This is hard to do synchronously in a v-for without loading the image.
  
  // For now, let's just add a "Cropped" badge to the thumbnail so they know it's custom.
  return {}
}

onMounted(async () => {
  await fetchData()
  fetchPendingUploads()
})
</script>

<template>
  <div class="flex h-[calc(100vh-8rem)] gap-6">
    <!-- Left: Playlist Items (Draggable) -->
    <div class="w-2/3 flex flex-col">
      <div class="flex justify-between items-center mb-4">
        <div>
          <h2 class="text-xl font-bold">
            Editing: {{ playlist?.name }}
          </h2>
          <div class="mt-1 flex flex-wrap items-center gap-4" v-if="playlist">
             <div class="flex items-center gap-2">
               <label class="text-sm text-gray-600">Orientation:</label>
               <select v-model="playlist.orientation" class="border rounded px-2 py-1 text-sm">
                 <option value="landscape">Landscape</option>
                 <option value="portrait">Portrait</option>
               </select>
             </div>

             <div class="flex items-center gap-2">
               <label class="text-sm text-gray-600">Visibility:</label>
               <select v-model="playlist.visibility" class="border rounded px-2 py-1 text-sm">
                 <option value="public">Public</option>
                 <option value="private">Private</option>
               </select>
             </div>

             <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-600">URL:</span>
                <div class="flex items-center bg-gray-100 rounded px-2 py-1">
                  <span class="truncate max-w-[200px] text-gray-800 select-all">{{ getPlaylistUrl() }}</span>
                  <button @click="copyUrl" class="ml-2 text-blue-600 hover:text-blue-800 text-xs font-bold">COPY</button>
                </div>
             </div>
          </div>

          <!-- Upload Settings Row -->
          <div v-if="playlist" class="mt-2 flex flex-wrap items-center gap-4 pt-2 border-t">
            <div class="flex items-center gap-2">
              <label class="text-sm text-gray-600">Guest Uploads:</label>
              <button
                @click="toggleUploads"
                :class="playlist.allow_uploads ? 'bg-green-500' : 'bg-gray-300'"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
              >
                <span
                  :class="playlist.allow_uploads ? 'translate-x-6' : 'translate-x-1'"
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                />
              </button>
            </div>

            <template v-if="playlist.allow_uploads">
              <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Mode:</label>
                <select v-model="playlist.upload_mode" @change="updateUploadMode" class="border rounded px-2 py-1 text-sm">
                  <option value="auto_add">Auto-add</option>
                  <option value="require_approval">Require Approval</option>
                </select>
              </div>

              <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">QR every:</label>
                <input
                  type="number"
                  v-model.number="playlist.qr_frequency"
                  @change="updateQrFrequency"
                  class="w-16 border rounded px-2 py-1 text-sm"
                  min="1"
                  max="100"
                >
                <span class="text-sm text-gray-500">items</span>
              </div>

              <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-600">Upload URL:</span>
                <button @click="copyUploadUrl" class="text-blue-600 hover:text-blue-800 text-xs font-bold">COPY</button>
                <button @click="showUploadQrModal = true" class="text-blue-600 hover:text-blue-800 text-xs font-bold">QR</button>
              </div>
            </template>
          </div>
        </div>
        <div class="flex gap-2">
          <button 
            @click="previewPlaylist" 
            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 disabled:opacity-50"
            :disabled="isSaving"
          >
            Save & Preview
          </button>
          <button 
            @click="savePlaylist" 
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
            :disabled="isSaving"
          >
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
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
              
              <div :class="['bg-gray-100 flex-shrink-0 transition-all duration-300 relative overflow-hidden', thumbnailClass]">
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
                
                <!-- Crop Badge -->
                <div v-if="element.crop_data" class="absolute bottom-0 right-0 bg-green-500 text-white text-[10px] px-1">
                  CROP
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ element.filename }}</div>
                <div class="text-xs text-gray-500 uppercase">{{ element.type }}</div>
              </div>

              <!-- Settings -->
              <div class="flex flex-col gap-2 items-end mr-4">
                <div class="flex items-center gap-2" v-if="element.type === 'image'">
                  <button 
                    @click="openCropper(index)"
                    class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded flex items-center gap-1"
                  >
                    <span v-if="element.crop_data">Edit Crop</span>
                    <span v-else>Crop</span>
                  </button>
                  
                  <label class="text-xs text-gray-600 ml-2">Duration:</label>
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
            <div :class="['bg-gray-100 relative transition-all duration-300', libraryAspectClass]">
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

    <!-- Cropper Modal -->
    <ImageCropperModal
      v-if="showCropper"
      :image-url="currentCropImage"
      :aspect-ratio="targetAspectRatio"
      :initial-data="currentCropData"
      @save="saveCrop"
      @cancel="showCropper = false"
    />

    <!-- Upload QR Modal -->
    <div v-if="showUploadQrModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg text-center">
        <h3 class="text-lg font-bold mb-4">Scan to Upload</h3>
        <QrCode :value="getUploadUrl()" :size="200" />
        <p class="text-sm text-gray-500 mt-4 max-w-[200px] break-all">{{ getUploadUrl() }}</p>
        <button @click="showUploadQrModal = false" class="mt-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Close</button>
      </div>
    </div>

    <!-- Pending Uploads Panel -->
    <div v-if="playlist?.allow_uploads && playlist?.upload_mode === 'require_approval' && pendingUploads.length > 0"
         class="fixed bottom-4 right-4 bg-white rounded-lg shadow-xl p-4 max-w-sm max-h-96 overflow-y-auto z-40">
      <h4 class="font-bold text-sm mb-3 flex items-center gap-2">
        <span class="bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full">{{ pendingUploads.length }}</span>
        Pending Uploads
      </h4>
      <div class="space-y-2">
        <div v-for="upload in pendingUploads" :key="upload.id" class="flex items-center gap-3 p-2 bg-gray-50 rounded">
          <div class="w-12 h-12 bg-gray-200 rounded overflow-hidden flex-shrink-0">
            <img v-if="upload.asset?.type === 'image'" :src="upload.asset?.url" class="w-full h-full object-cover">
            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-500">VID</div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs truncate">{{ upload.asset?.filename }}</p>
          </div>
          <div class="flex gap-1">
            <button @click="approveUpload(upload.id)" class="text-green-600 hover:text-green-800 text-lg" title="Approve">✓</button>
            <button @click="rejectUpload(upload.id)" class="text-red-600 hover:text-red-800 text-lg" title="Reject">✕</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
