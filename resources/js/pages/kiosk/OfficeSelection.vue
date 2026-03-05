<template>
  <div>
    <h2 class="text-2xl font-bold mb-4">Select Office</h2>
    <div class="grid grid-cols-3 gap-4">
      <div 
        v-for="office in offices" 
        :key="office.id"
        @click="selectOffice(office)"
        class="bg-white shadow rounded-lg p-6 cursor-pointer hover:shadow-lg"
      >
        <h3 class="text-lg font-semibold">{{ office.name }}</h3>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OfficeSelection',
  data() {
    return {
      offices: [
        { id: 1, name: 'Office 1' },
        { id: 2, name: 'Office 2' },
        { id: 3, name: 'Office 3' }
      ]
    }
  },
  methods: {
    selectOffice(office) {
      console.log('Selected office:', office)
      this.$router.push({ name: 'kiosk.service' })
    }
  }
}
</script>