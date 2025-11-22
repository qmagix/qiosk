<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'

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

const localPlaylist = ref([])
const currentIndex = ref(0)
const isPlaying = ref(false)
const timer = ref(null)
const isLoading = ref(false)
const error = ref('')
const pollInterval = ref(null)

const activePlaylist = computed(() => {
  return props.playlist.length ? props.playlist : localPlaylist.value
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
  if (!props.slug) return
  if (!isPolling) isLoading.value = true
  
  try {
    const response = await axios.get(`/api/playlists/${props.slug}/play`) 
    
    const newItems = response.data.items.map(item => ({
      id: item.id,
      type: item.asset.type,
      url: item.asset.url,
      duration_seconds: item.duration_seconds,
      transition_effect: item.transition_effect
    }))

    // Only update if content changed to avoid resetting the player unnecessarily
    if (JSON.stringify(newItems) !== JSON.stringify(localPlaylist.value)) {
      localPlaylist.value = newItems
      // If we were empty and now have items, start playing
      if (!isPlaying.value && props.autoPlay && newItems.length > 0) {
        start()
      }
    }
    
  } catch (e) {
    if (!isPolling) {
      error.value = 'Failed to load playlist'
      console.error(e)
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
  scheduleNext()
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
  if (props.slug) {
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
</script>

<template>
  <div class="media-player" @dblclick="toggleFullscreen">
    <Transition :name="transitionName">
      <div :key="currentItem?.id" class="media-item" v-if="currentItem">
        
        <img 
          v-if="currentItem.type === 'image'" 
          :src="currentItem.url" 
          class="media-content"
          alt="Slide"
        />
        
        <video 
          v-else-if="currentItem.type === 'video'"
          :src="currentItem.url"
          class="media-content"
          autoplay
          muted
          playsinline
          @ended="onVideoEnded"
        ></video>

      </div>
      <div v-else class="empty-state">
        No media to display
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.media-player {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: black;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: none; /* Hide cursor for TV experience */
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
  transition: opacity 1s ease;
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
</style>
