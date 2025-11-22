<script setup>
import MediaPlayer from './components/MediaPlayer.vue'

// Dummy data for demonstration
const playlist = [
  {
    id: 1,
    type: 'image',
    url: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
    duration_seconds: 5
  },
  {
    id: 2,
    type: 'image',
    url: 'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
    duration_seconds: 5
  },
  {
    id: 3,
    type: 'video',
    url: 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
    duration_seconds: 10 // Ignored for video usually, but good fallback
  }
]
</script>

<template>
  <MediaPlayer :playlist="playlist" />
</template>

<style>
/* Reset default styles for full screen player */
body {
  margin: 0;
  padding: 0;
  background: black;
}
</style>
