<template>
  <div class="bg-black text-white min-h-screen p-8">
    <h1 class="text-4xl font-bold">Monitor Display</h1>
    <p class="mt-4">Office ID: {{ $route.params.officeId }}</p>
  </div>
</template>

<script>
export default {
  name: 'MonitorDisplay'
}
</script>