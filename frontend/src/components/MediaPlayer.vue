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
const playlistOrientation = ref('landscape')
const currentIndex = ref(0)
const isPlaying = ref(false)
const timer = ref(null)
const isLoading = ref(false)
const error = ref('')
const pollInterval = ref(null)

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
  if (!props.slug) return
  if (!isPolling) isLoading.value = true
  
  try {
    const response = await axios.get(`/api/playlists/${props.slug}/play`) 
    
    playlistOrientation.value = response.data.orientation || 'landscape'

    const newItems = response.data.items.map(item => ({
      id: item.id,
      type: item.asset.type,
      url: item.asset.url,
      duration_seconds: item.duration_seconds,
      transition_effect: item.transition_effect,
      crop_data: item.crop_data
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
    </div>
  </div>
</template>

<style scoped>
/* ... existing styles ... */
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
/* ... */

<style scoped>
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
</style>
