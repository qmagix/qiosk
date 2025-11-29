<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'

const props = defineProps({
  imageUrl: {
    type: String,
    required: true
  },
  aspectRatio: {
    type: Number,
    default: 16 / 9
  },
  initialData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['save', 'cancel'])

const imageRef = ref(null)
let cropper = null

onMounted(() => {
  cropper = new Cropper(imageRef.value, {
    aspectRatio: props.aspectRatio,
    viewMode: 1, // Restrict crop box to canvas
    dragMode: 'move',
    autoCropArea: 1,
    restore: false,
    guides: true,
    center: true,
    highlight: false,
    cropBoxMovable: true,
    cropBoxResizable: true,
    toggleDragModeOnDblclick: false,
    ready() {
      if (props.initialData) {
        cropper.setData(props.initialData)
      }
    }
  })
})

onUnmounted(() => {
  if (cropper) {
    cropper.destroy()
  }
})

const save = () => {
  if (cropper) {
    const data = cropper.getData()
    const imageData = cropper.getImageData()
    
    // Calculate percentages relative to the NATURAL image dimensions
    // cropper.getData() returns values relative to natural size (if checkOrientation is true, which is default)
    // But let's verify. Yes, getData() returns absolute pixels of the crop box on the original image.
    // imageData.naturalWidth is the original width.
    
    const relativeData = {
      x: (data.x / imageData.naturalWidth) * 100,
      y: (data.y / imageData.naturalHeight) * 100,
      width: (data.width / imageData.naturalWidth) * 100,
      height: (data.height / imageData.naturalHeight) * 100,
      rotate: data.rotate
    }
    
    emit('save', relativeData)
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
    <div class="bg-white p-4 rounded-lg shadow-xl w-[90vw] h-[90vh] flex flex-col">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold">Crop Image</h3>
        <button @click="$emit('cancel')" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>
      
      <div class="flex-1 bg-gray-100 overflow-hidden relative">
        <img ref="imageRef" :src="imageUrl" class="max-w-full max-h-full block" />
      </div>

      <div class="mt-4 flex justify-end gap-2">
        <button 
          @click="$emit('cancel')" 
          class="px-4 py-2 border rounded hover:bg-gray-50"
        >
          Cancel
        </button>
        <button 
          @click="save" 
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Save Crop
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Ensure image fits */
img {
  display: block;
  max-width: 100%;
}
</style>
