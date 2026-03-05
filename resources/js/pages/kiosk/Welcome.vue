<template>
  <div class="text-center">
    <h2 class="text-2xl font-bold mb-4">Welcome to Kiosk</h2>
    <button 
      @click="goToOfficeSelection"
      class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
    >
      Start
    </button>
  </div>
</template>

<script>
export default {
  name: 'KioskWelcome',
  methods: {
    goToOfficeSelection() {
      this.$router.push({ name: 'kiosk.office' })
    }
  }
}
</script>