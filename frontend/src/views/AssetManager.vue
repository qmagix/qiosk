<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const assets = ref([])
const fileInput = ref(null)
const isUploading = ref(false)
const uploadStatus = ref('')
const showLinkModal = ref(false)
const linkForm = ref({
  url: '',
  type: 'image',
  filename: ''
})

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

const addLink = async () => {
  if (!linkForm.value.url) return
  
  try {
    await axios.post('/api/assets', {
      external_url: linkForm.value.url,
      type: linkForm.value.type,
      filename: linkForm.value.filename
    }, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    })
    
    showLinkModal.value = false
    linkForm.value = { url: '', type: 'image', filename: '' }
    await fetchAssets()
  } catch (e) {
    alert('Failed to add link')
    console.error(e)
  }
}

const uploadFile = async (event) => {
  const files = event.target.files
  if (!files.length) return

  isUploading.value = true
  const total = files.length
  let completed = 0
  uploadStatus.value = `Processing & Uploading 0/${total}...`

  try {
    // Upload files sequentially to avoid overwhelming the server or browser
    for (let i = 0; i < total; i++) {
      let file = files[i]
      
      // Client-side Image Compression
      if (file.type.startsWith('image/')) {
        try {
          uploadStatus.value = `Compressing ${file.name}...`
          file = await compressImage(file)
        } catch (err) {
          console.warn('Compression failed, uploading original.', err)
        }
      }

      const formData = new FormData()
      formData.append('file', file)

      try {
        uploadStatus.value = `Uploading ${file.name}...`
        await axios.post('/api/assets', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        })
        completed++
        uploadStatus.value = `Completed ${completed}/${total}`
      } catch (e) {
        console.error(`Failed to upload ${file.name}`, e)
      }
    }
    
    await fetchAssets()
    fileInput.value.value = ''
  } catch (e) {
    alert('Upload process encountered errors')
  } finally {
    isUploading.value = false
    uploadStatus.value = ''
  }
}

const compressImage = (file) => {
  return new Promise((resolve, reject) => {
    const maxWidth = 3840 // 4K Width
    const maxHeight = 2160 // 4K Height
    const quality = 0.85 // Good balance for 4K

    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = (event) => {
      const img = new Image()
      img.src = event.target.result
      img.onload = () => {
        let width = img.width
        let height = img.height

        // Calculate new dimensions
        if (width > height) {
          if (width > maxWidth) {
            height = Math.round((height * maxWidth) / width)
            width = maxWidth
          }
        } else {
          if (height > maxHeight) {
            width = Math.round((width * maxHeight) / height)
            height = maxHeight
          }
        }

        const canvas = document.createElement('canvas')
        canvas.width = width
        canvas.height = height
        const ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)

        // Convert to WebP for better compression/quality ratio
        // Fallback to jpeg if webp not supported (rare nowadays)
        const mimeType = 'image/webp'
        
        canvas.toBlob((blob) => {
            if (!blob) {
                reject(new Error('Canvas is empty'))
                return
            }
            // Create a new File object with the compressed blob
            // Change extension to .webp
            const newName = file.name.replace(/\.[^/.]+$/, "") + ".webp"
            const newFile = new File([blob], newName, {
                type: mimeType,
                lastModified: Date.now(),
            })
            resolve(newFile)
        }, mimeType, quality)
      }
      img.onerror = (err) => reject(err)
    }
    reader.onerror = (err) => reject(err)
  })
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
        <button 
          @click="showLinkModal = true"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2"
        >
          Add Link
        </button>
        <input 
          type="file" 
          ref="fileInput" 
          @change="uploadFile" 
          class="hidden" 
          accept="image/*,video/*"
          multiple
        >
        <button 
          @click="$refs.fileInput.click()" 
          class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded"
          :disabled="isUploading"
        >
          {{ isUploading ? uploadStatus : 'Upload Media' }}
        </button>
      </div>
    </div>

    <!-- Link Modal -->
    <div v-if="showLinkModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">Add External Asset</h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">URL</label>
          <input v-model="linkForm.url" type="url" class="w-full border rounded p-2" placeholder="https://example.com/image.jpg">
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Type</label>
          <select v-model="linkForm.type" class="w-full border rounded p-2">
            <option value="image">Image</option>
            <option value="video">Video</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Name (Optional)</label>
          <input v-model="linkForm.filename" type="text" class="w-full border rounded p-2" placeholder="My Image">
        </div>

        <div class="flex justify-end gap-2">
          <button @click="showLinkModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
          <button @click="addLink" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Add</button>
        </div>
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
