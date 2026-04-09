<template>
  <div class="relative min-h-screen bg-black">

    <!-- BACKGROUND VIDEO - RESPONSIVE SA PORTRAIT -->
    <video
      ref="videoPlayer"
      class="w-full h-full"
      autoplay
      muted
      loop
      playsinline
      style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;"
    >
      <source src="/storage/videos/LIGAO-CITY-AVP.mp4" type="video/mp4" />
    </video>

    <!-- BACKGROUND MUSIC -->
    <audio
      ref="audioPlayer"
      autoplay
      loop
      style="display: none;"
    >
      <source src="/storage/music/background-music.mp3" type="audio/mpeg" />
    </audio>

    <!-- DARK OVERLAY -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 2;"></div>

    <!-- START BUTTON -->
    <div style="position: fixed; bottom: 50px; left: 0; right: 0; text-align: center; z-index: 3;">
      <button
        @click="goToWelcomePage"
        style="background: transparent; color: white; padding: 15px 40px; border-radius: 50px; font-size: 20px; font-weight: bold; border: 2px solid white; cursor: pointer; backdrop-filter: blur(5px); transition: all 0.3s ease;"
        @mouseenter="e => e.target.style.background = 'rgba(255,255,255,0.2)'"
        @mouseleave="e => e.target.style.background = 'transparent'"
      >
        MAGPATULOY
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const videoPlayer = ref(null)
const audioPlayer = ref(null)

let videoInterval = null

const goToWelcomePage = () => {
  router.push('/kiosk/welcome')
}

onMounted(() => {
  const video = videoPlayer.value
  const audio = audioPlayer.value
  
  if (video) {
    // Play video
    video.play().catch(e => console.log('Video autoplay error:', e))
    
    // Monitor video end to restart if needed
    videoInterval = setInterval(() => {
      if (video.ended) {
        video.play().catch(e => console.log('Video replay error:', e))
      }
    }, 1000)
  }
  
  if (audio) {
    // Play music
    audio.play().catch(e => console.log('Audio autoplay error:', e))
  }
})

onBeforeUnmount(() => {
  if (videoInterval) clearInterval(videoInterval)
})
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
</style>