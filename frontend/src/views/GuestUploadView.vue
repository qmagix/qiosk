<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const playlistId = route.params.id
const uploadToken = route.params.token

const playlistInfo = ref(null)
const isLoading = ref(true)
const error = ref(null)

const isDragging = ref(false)
const selectedFile = ref(null)
const previewUrl = ref(null)
const isUploading = ref(false)
const uploadProgress = ref('')
const uploadResult = ref(null)

const fetchPlaylistInfo = async () => {
  try {
    const response = await axios.get(`/api/playlists/${playlistId}/upload-info?token=${uploadToken}`)
    playlistInfo.value = response.data
  } catch (e) {
    if (e.response?.status === 403) {
      error.value = 'Uploads are not available for this playlist.'
    } else {
      error.value = 'Failed to load playlist information.'
    }
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = event.dataTransfer.files
  if (files.length > 0) {
    selectFile(files[0])
  }
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files.length > 0) {
    selectFile(files[0])
  }
}

const selectFile = (file) => {
  // Validate file type
  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'video/mp4', 'video/quicktime']
  if (!validTypes.includes(file.type)) {
    alert('Please select an image (JPEG, PNG, WebP) or video (MP4, MOV)')
    return
  }

  // Validate file size (50MB max)
  if (file.size > 50 * 1024 * 1024) {
    alert('File size must be less than 50MB')
    return
  }

  selectedFile.value = file

  // Generate preview
  if (file.type.startsWith('image/')) {
    previewUrl.value = URL.createObjectURL(file)
  } else {
    previewUrl.value = null
  }
}

const clearSelection = () => {
  selectedFile.value = null
  previewUrl.value = null
  uploadResult.value = null
}

const compressImage = (file) => {
  return new Promise((resolve, reject) => {
    const maxWidth = 3840
    const maxHeight = 2160
    const quality = 0.85

    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = (event) => {
      const img = new Image()
      img.src = event.target.result
      img.onload = () => {
        let width = img.width
        let height = img.height

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

        const mimeType = 'image/webp'

        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Canvas is empty'))
            return
          }
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

const uploadFile = async () => {
  if (!selectedFile.value) return

  isUploading.value = true
  uploadProgress.value = 'Preparing...'

  try {
    let file = selectedFile.value

    // Compress image if needed
    if (file.type.startsWith('image/')) {
      try {
        uploadProgress.value = 'Compressing...'
        file = await compressImage(file)
      } catch (err) {
        console.warn('Compression failed, uploading original.', err)
      }
    }

    uploadProgress.value = 'Uploading...'

    const formData = new FormData()
    formData.append('file', file)
    formData.append('upload_token', uploadToken)

    const response = await axios.post(`/api/playlists/${playlistId}/guest-upload`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      }
    })

    uploadResult.value = {
      success: true,
      message: response.data.message,
      mode: response.data.mode,
    }

    selectedFile.value = null
    previewUrl.value = null
  } catch (e) {
    console.error(e)
    uploadResult.value = {
      success: false,
      message: e.response?.data?.message || 'Upload failed. Please try again.',
    }
  } finally {
    isUploading.value = false
    uploadProgress.value = ''
  }
}

const uploadAnother = () => {
  uploadResult.value = null
  selectedFile.value = null
  previewUrl.value = null
}

onMounted(fetchPlaylistInfo)
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 p-4 flex items-center justify-center">
    <!-- Loading State -->
    <div v-if="isLoading" class="text-white text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent mx-auto mb-4"></div>
      <p>Loading...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white rounded-2xl shadow-xl p-8 max-w-md text-center">
      <div class="text-red-500 text-5xl mb-4">:(</div>
      <p class="text-gray-700">{{ error }}</p>
    </div>

    <!-- Upload Card -->
    <div v-else class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-xl p-6">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">
          Add to "{{ playlistInfo.name }}"
        </h1>
        <p class="text-gray-500 text-center mb-6 text-sm">
          Share a photo or video to the slideshow
        </p>

        <!-- Upload Result -->
        <div v-if="uploadResult" class="text-center py-8">
          <div v-if="uploadResult.success" class="mb-4">
            <div class="text-green-500 text-6xl mb-4">✓</div>
            <p class="text-xl font-semibold text-gray-800 mb-2">{{ uploadResult.message }}</p>
            <p v-if="uploadResult.mode === 'auto_add'" class="text-gray-500 text-sm">
              Look at the screen - your contribution is now part of the slideshow!
            </p>
          </div>
          <div v-else class="mb-4">
            <div class="text-red-500 text-6xl mb-4">✕</div>
            <p class="text-red-600">{{ uploadResult.message }}</p>
          </div>
          <button
            @click="uploadAnother"
            class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-xl transition"
          >
            Upload Another
          </button>
        </div>

        <!-- File Selection / Upload UI -->
        <div v-else>
          <!-- Preview -->
          <div v-if="selectedFile" class="mb-4">
            <div class="relative rounded-xl overflow-hidden bg-gray-100">
              <img
                v-if="previewUrl"
                :src="previewUrl"
                class="w-full h-48 object-contain"
              >
              <div v-else class="w-full h-48 flex items-center justify-center">
                <div class="text-center">
                  <div class="text-4xl mb-2">🎬</div>
                  <p class="text-gray-600">{{ selectedFile.name }}</p>
                </div>
              </div>
              <button
                @click="clearSelection"
                class="absolute top-2 right-2 bg-black bg-opacity-50 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-70"
              >
                ✕
              </button>
            </div>
            <p class="text-sm text-gray-500 mt-2 text-center">
              {{ (selectedFile.size / 1024 / 1024).toFixed(1) }} MB
            </p>
          </div>

          <!-- Drop Zone -->
          <div
            v-else
            class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all"
            :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleDrop"
            @click="$refs.fileInput.click()"
          >
            <input
              type="file"
              ref="fileInput"
              class="hidden"
              accept="image/*,video/*"
              capture="environment"
              @change="handleFileSelect"
            >
            <div class="text-5xl mb-4">📷</div>
            <p class="text-gray-600 font-medium mb-2">
              Tap to choose a photo or video
            </p>
            <p class="text-gray-400 text-sm">
              or drag and drop here
            </p>
          </div>

          <!-- Upload Button -->
          <button
            v-if="selectedFile"
            @click="uploadFile"
            :disabled="isUploading"
            class="w-full mt-4 bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white font-semibold py-4 rounded-xl transition text-lg"
          >
            {{ isUploading ? uploadProgress : 'Upload' }}
          </button>
        </div>
      </div>

      <!-- Info Footer -->
      <p class="text-white text-center text-sm mt-4 opacity-80">
        {{ playlistInfo.item_count }} items in this slideshow
      </p>
    </div>
  </div>
</template>
