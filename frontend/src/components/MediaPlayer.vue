<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  playlist: {
    type: Array,
    required: true,
    default: () => []
  },
  autoPlay: {
    type: Boolean,
    default: true
  }
})

const currentIndex = ref(0)
const isPlaying = ref(false)
const timer = ref(null)

const currentItem = computed(() => {
  if (!props.playlist.length) return null
  return props.playlist[currentIndex.value]
})

const nextIndex = computed(() => {
  if (!props.playlist.length) return 0
  return (currentIndex.value + 1) % props.playlist.length
})

const nextItem = computed(() => {
  if (!props.playlist.length) return null
  return props.playlist[nextIndex.value]
})

function start() {
  if (!props.playlist.length) return
  isPlaying.value = true
  scheduleNext()
}

function stop() {
  isPlaying.value = false
  if (timer.value) clearTimeout(timer.value)
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

// Preload next image
watch(nextItem, (newItem) => {
  if (newItem && newItem.type === 'image') {
    const img = new Image()
    img.src = newItem.url
  }
})

onMounted(() => {
  if (props.autoPlay) {
    start()
  }
})

onUnmounted(() => {
  stop()
})
</script>

<template>
  <div class="media-player">
    <Transition name="fade" mode="in-out">
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
</style>
