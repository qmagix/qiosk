<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { offlineManager } from '../services/OfflineManager'
import QrCode from './QrCode.vue'

const props = defineProps({
  playlist: {
    type: Array,
    default: () => []
  },
  slug: {
    type: String,
    default: ''
  },
  autoPlay: {
    type: Boolean,
    default: true
  }
})

// Call useRoute at the top level
const route = useRoute()

const localPlaylist = ref([])
const playlistOrientation = ref('landscape')
const currentIndex = ref(0)
const isPlaying = ref(false)
const timer = ref(null)
const isLoading = ref(false)
const error = ref('')
const pollInterval = ref(null)

// QR Code / Upload settings
const playlistData = ref(null)
const showQrOverlay = ref(false)
const qrDisplayCount = ref(0)
const QR_DISPLAY_DURATION_MS = 6000

const uploadUrl = computed(() => {
  if (!playlistData.value?.allow_uploads || !playlistData.value?.upload_token) {
    return null
  }
  return `${window.location.origin}/upload/${playlistData.value.id}/${playlistData.value.upload_token}`
})

const qrFrequency = computed(() => {
  return playlistData.value?.qr_frequency || 5
})

const activePlaylist = computed(() => {
  return props.playlist.length ? props.playlist : localPlaylist.value
})

// Detect if we need to rotate for portrait mode on a landscape screen
const shouldRotate = computed(() => {
  // Only rotate if playlist is portrait but screen is landscape
  // We use a simple check for window aspect ratio
  if (typeof window === 'undefined') return false
  const isScreenLandscape = window.innerWidth > window.innerHeight
  return playlistOrientation.value === 'portrait' && isScreenLandscape
})

const containerStyle = computed(() => {
  if (shouldRotate.value) {
    return {
      width: '100vh',
      height: '100vw',
      transform: 'rotate(-90deg)',
      transformOrigin: 'center',
      position: 'absolute',
      left: 'calc(50% - 50vh)',
      top: 'calc(50% - 50vw)'
    }
  }
  return {
    width: '100vw',
    height: '100vh'
  }
})

const currentItem = computed(() => {
  if (!activePlaylist.value.length) return null
  return activePlaylist.value[currentIndex.value]
})

const nextIndex = computed(() => {
  if (!activePlaylist.value.length) return 0
  return (currentIndex.value + 1) % activePlaylist.value.length
})

const nextItem = computed(() => {
  if (!activePlaylist.value.length) return null
  return activePlaylist.value[nextIndex.value]
})

const transitionName = computed(() => {
  return currentItem.value?.transition_effect || 'fade'
})

async function fetchPlaylist(isPolling = false) {
  if (!props.slug && !route.params.id) return
  if (!isPolling) isLoading.value = true
  
  const id = route.params.id
  const token = route.params.token
  
  try {
    let response
    if (id) {
       // Play by ID (and optional token)
       let url = `/api/playlists/${id}/play-by-id`
       if (token) {
         url += `?token=${token}`
       }
       response = await axios.get(url)
    } else if (props.slug) {
       // Legacy play by slug
       response = await axios.get(`/api/playlists/${props.slug}/play`) 
    } else {
      // No ID or Slug provided
      return
    }
    
    playlistOrientation.value = response.data.orientation || 'landscape'

    // Store full playlist data for upload settings
    playlistData.value = response.data

    const newItems = response.data.items.map(item => ({
      id: item.id,
      type: item.asset.type,
      url: item.asset.url,
      duration_seconds: item.duration_seconds,
      transition_effect: item.transition_effect,
      crop_data: item.crop_data,
      asset: item.asset // Keep original asset data for syncing
    }))

    let finalPlaylist = { ...response.data, items: newItems };

    // Sync for offline if in Electron
    if (offlineManager.isElectron()) {
       finalPlaylist = await offlineManager.syncPlaylist(finalPlaylist);
       // Update URLs to use local paths
       finalPlaylist.items = finalPlaylist.items.map(item => ({
         ...item,
         url: item.localPath || item.url
       }));
       
       // Save the fully synced playlist (with local paths) for offline use
       // Use ID as key if available, else slug
       const key = id ? `id_${id}` : props.slug
       await offlineManager.savePlaylist(key, finalPlaylist);
    }

    // Only update if content changed to avoid resetting the player unnecessarily
    if (JSON.stringify(finalPlaylist.items) !== JSON.stringify(localPlaylist.value)) {
      localPlaylist.value = finalPlaylist.items
      
      // If we have items and aren't playing, start
      if (!isPlaying.value && props.autoPlay && localPlaylist.value.length > 0) {
        start()
      }
    }
    
  } catch (e) {
    console.error('Failed to load playlist from API:', e)
    
    // Try to load from offline storage if in Electron
    if (offlineManager.isElectron()) {
       console.log('Attempting to load offline playlist...');
       const id = route.params.id
       const key = id ? `id_${id}` : props.slug
       const offlinePlaylist = await offlineManager.loadPlaylist(key);
       if (offlinePlaylist) {
          playlistOrientation.value = offlinePlaylist.orientation || 'landscape';
          localPlaylist.value = offlinePlaylist.items;
          if (!isPlaying.value && props.autoPlay && offlinePlaylist.items.length > 0) {
            start();
          }
          return; // Successfully loaded offline
       }
    }

    if (!isPolling) {
      error.value = 'Failed to load playlist'
    }
  } finally {
    if (!isPolling) isLoading.value = false
  }
}

function start() {
  if (!activePlaylist.value.length) return
  isPlaying.value = true
  scheduleNext()
}

function stop() {
  isPlaying.value = false
  if (timer.value) clearTimeout(timer.value)
  if (pollInterval.value) clearInterval(pollInterval.value)
}

function next() {
  currentIndex.value = nextIndex.value
  qrDisplayCount.value++

  // Show QR every N items if uploads enabled
  if (uploadUrl.value && qrDisplayCount.value >= qrFrequency.value) {
    showQrCode()
    qrDisplayCount.value = 0
  }

  scheduleNext()
}

function showQrCode() {
  showQrOverlay.value = true
  setTimeout(() => {
    showQrOverlay.value = false
  }, QR_DISPLAY_DURATION_MS)
}

function scheduleNext() {
  if (!isPlaying.value) return
  if (timer.value) clearTimeout(timer.value)

  const item = currentItem.value
  if (!item) return

  if (item.type === 'image') {
    // Default 10s or use item duration
    const duration = (item.duration_seconds || 10) * 1000
    timer.value = setTimeout(() => {
      next()
    }, duration)
  } else if (item.type === 'video') {
    // Video handles its own transition via 'ended' event
    // But we set a fallback just in case
  }
}

function onVideoEnded() {
  if (currentItem.value?.type === 'video') {
    next()
  }
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(e => {
      console.log(`Error attempting to enable fullscreen: ${e.message}`)
    })
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
    }
  }
}

// Preload next image
watch(nextItem, (newItem) => {
  if (newItem && newItem.type === 'image') {
    const img = new Image()
    img.src = newItem.url
  }
})

onMounted(() => {
  const hasIdParam = route.params.id
  
  if (props.slug || hasIdParam) {
    fetchPlaylist()
    // Poll every 30 seconds for updates
    pollInterval.value = setInterval(() => fetchPlaylist(true), 30000)
  } else if (props.playlist.length && props.autoPlay) {
    start()
  }
})

onUnmounted(() => {
  stop()
})

function getMediaStyle(item) {
  if (!item.crop_data) {
    return { objectFit: 'contain' } 
  }
  
  const crop = item.crop_data
  
  // Logic:
  // We have a container (100% w, 100% h).
  // We want the CROP BOX to fill this container.
  // The image is larger than the crop box.
  // 
  // If crop.width is 50% of image, then the image needs to be 200% of the container width.
  // Scale = 100 / crop.width
  //
  // Position:
  // If crop.x is 10%, we need to shift the image left by 10% of its OWN width.
  // But wait, standard CSS 'left' percentage is relative to PARENT width.
  // So we can't use 'left: -10%'.
  //
  // Let's use width/height and top/left.
  // width = (100 / crop.width) * 100 %
  // height = (100 / crop.height) * 100 %
  // left = - (crop.x / crop.width) * 100 %  <-- relative to container width?
  // Let's trace:
  // Image Width = Container Width * (100/crop.width)
  // We want the point at crop.x (which is crop.x% of Image Width) to be at 0.
  // So we shift left by: Image Width * (crop.x/100).
  // In percentages of Container Width:
  // Shift = (Container Width * 100/crop.width) * (crop.x/100)
  //       = Container Width * (crop.x / crop.width)
  // So left = - (crop.x / crop.width) * 100 %
  
  return {
    width: `${(100 / crop.width) * 100}%`,
    height: `${(100 / crop.height) * 100}%`,
    left: `${-(crop.x / crop.width) * 100}%`,
    top: `${-(crop.y / crop.height) * 100}%`,
    position: 'absolute'
  }
}

// We need to update the template to call getMediaStyle, but first let's handle the data format issue.
// I will update ImageCropperModal to return percentages.
// Then I can use:
// width: (100 / cropWidthPercent) * 100 %
// left: - (cropXPercent / cropWidthPercent) * 100 %
// This works!
</script>

<template>
  <div class="media-player-container">
    <div class="media-player" :style="containerStyle" @dblclick="toggleFullscreen">
      <Transition :name="transitionName">
        <div :key="currentItem?.id" class="media-item" v-if="currentItem">
          
          <!-- We use a wrapper for cropping if needed -->
          <div v-if="currentItem.type === 'image' && currentItem.crop_data" class="crop-wrapper">
             <img 
              :src="currentItem.url" 
              class="media-content-cropped"
              alt="Slide"
              :style="getMediaStyle(currentItem)"
              @error="next"
            />
          </div>
          <img 
            v-else-if="currentItem.type === 'image'" 
            :src="currentItem.url" 
            class="media-content"
            alt="Slide"
            @error="next"
          />
          
          <video 
            v-else-if="currentItem.type === 'video'"
            :src="currentItem.url"
            class="media-content"
            autoplay
            muted
            playsinline
            @ended="onVideoEnded"
            @error="next"
          ></video>

        </div>
        <div v-else class="empty-state">
          No media to display
        </div>
      </Transition>

      <!-- QR Code Overlay -->
      <Transition name="fade-quick">
        <div v-if="showQrOverlay && uploadUrl" class="qr-overlay">
          <div class="qr-container">
            <QrCode :value="uploadUrl" :size="120" />
            <p class="qr-label">Scan to add photos</p>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.crop-wrapper {
  width: 100%;
  height: 100%;
  overflow: hidden;
  position: relative;
}

.media-content-cropped {
  position: absolute;
  max-width: none;
  max-height: none;
}

.media-player-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: black;
  overflow: hidden;
}

.media-player {
  /* Removed fixed positioning here as it's handled by containerStyle or parent */
  background: black;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: none;
}

.media-item {
  position: absolute;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.media-content {
  width: 100%;
  height: 100%;
  object-fit: contain; /* or cover, depending on preference */
}

.empty-state {
  color: white;
  font-family: sans-serif;
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 2.5s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide Transition */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.8s ease;
}

.slide-enter-from {
  transform: translateX(100%);
}

.slide-leave-to {
  transform: translateX(-100%);
}

/* Zoom Transition */
.zoom-enter-active,
.zoom-leave-active {
  transition: transform 0.8s ease, opacity 0.8s ease;
}

.zoom-enter-from {
  transform: scale(1.5);
  opacity: 0;
}

.zoom-leave-to {
  transform: scale(0.5);
  opacity: 0;
}

/* QR Code Overlay */
.qr-overlay {
  position: absolute;
  bottom: 40px;
  right: 40px;
  z-index: 100;
  pointer-events: none;
}

.qr-container {
  background: rgba(255, 255, 255, 0.95);
  padding: 16px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.qr-label {
  margin-top: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  font-family: sans-serif;
}

/* Quick Fade for QR */
.fade-quick-enter-active,
.fade-quick-leave-active {
  transition: opacity 0.5s ease;
}

.fade-quick-enter-from,
.fade-quick-leave-to {
  opacity: 0;
}
</style>
