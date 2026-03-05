<template>
  <div class="min-h-screen bg-black text-white">
    <!-- Monitor Header -->
    <header class="bg-gray-900 border-b border-gray-800">
      <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold text-white">{{ officeName }}</h1>
          <div class="text-2xl font-mono">
            {{ currentTime }}
          </div>
        </div>
      </div>
    </header>

    <!-- Monitor Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>

    <!-- Footer Message -->
    <footer class="fixed bottom-0 w-full bg-gray-900 border-t border-gray-800 py-4">
      <div class="max-w-7xl mx-auto px-4 text-center text-xl">
        Maraming salamat sa inyong paghihintay! 🇵🇭
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'MonitorLayout',
  props: {
    officeName: {
      type: String,
      default: 'Ligao City Hall'
    }
  },
  data() {
    return {
      currentTime: new Date().toLocaleTimeString()
    }
  },
  mounted() {
    this.timer = setInterval(() => {
      this.currentTime = new Date().toLocaleTimeString()
    }, 1000)
  },
  beforeUnmount() {
    clearInterval(this.timer)
  }
}
</script>