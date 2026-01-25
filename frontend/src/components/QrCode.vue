<script setup>
import { ref, onMounted, watch } from 'vue'
import QRCode from 'qrcode'

const props = defineProps({
  value: {
    type: String,
    required: true
  },
  size: {
    type: Number,
    default: 128
  }
})

const canvas = ref(null)

const generateQR = async () => {
  if (!canvas.value || !props.value) return

  try {
    await QRCode.toCanvas(canvas.value, props.value, {
      width: props.size,
      margin: 1,
      color: {
        dark: '#000000',
        light: '#ffffff'
      }
    })
  } catch (err) {
    console.error('Failed to generate QR code:', err)
  }
}

onMounted(generateQR)

watch(() => props.value, generateQR)
watch(() => props.size, generateQR)
</script>

<template>
  <canvas ref="canvas"></canvas>
</template>
