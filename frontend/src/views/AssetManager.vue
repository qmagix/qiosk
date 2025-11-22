<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const assets = ref([])
const fileInput = ref(null)
const isUploading = ref(false)

const fetchAssets = async () => {
  try {
    const response = await axios.get('/api/assets', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    assets.value = response.data
  } catch (e) {
    console.error(e)
  }
}

const uploadFile = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  isUploading.value = true
  try {
    await axios.post('/api/assets', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    })
    await fetchAssets()
    fileInput.value.value = ''
  } catch (e) {
    alert('Upload failed')
  } finally {
    isUploading.value = false
  }
}

const deleteAsset = async (id) => {
  if (!confirm('Are you sure?')) return
  try {
    await axios.delete(`/api/assets/${id}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    await fetchAssets()
  } catch (e) {
    console.error(e)
  }
}

onMounted(fetchAssets)
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold">Asset Library</h2>
      <div>
        <input 
          type="file" 
          ref="fileInput" 
          @change="uploadFile" 
          class="hidden" 
          accept="image/*,video/*"
        >
        <button 
          @click="$refs.fileInput.click()" 
          class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded"
          :disabled="isUploading"
        >
          {{ isUploading ? 'Uploading...' : 'Upload Media' }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="asset in assets" :key="asset.id" class="relative group border rounded overflow-hidden bg-white shadow-sm">
        <div class="aspect-video bg-gray-100 flex items-center justify-center">
          <img 
            v-if="asset.type === 'image'" 
            :src="asset.url" 
            class="w-full h-full object-cover"
          >
          <video 
            v-else 
            :src="asset.url" 
            class="w-full h-full object-cover"
          ></video>
        </div>
        <div class="p-2 text-sm truncate">{{ asset.filename }}</div>
        <button 
          @click="deleteAsset(asset.id)"
          class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>
