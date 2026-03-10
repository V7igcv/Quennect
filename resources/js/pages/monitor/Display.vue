<template>
  <MonitorLayout :office-id="$route.params.officeId" />
</template>

<script>
import MonitorLayout from '@/layouts/MonitorLayout.vue'

export default {
  name: 'MonitorDisplay',
  components: {
    MonitorLayout
  }
}
</script>