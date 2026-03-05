<template>
    <!-- <div class="container">
        <h1 class="text-3xl font-bold text-blue-600">
            Welcome to Quennect!
        </h1>
    </div> -->
  <div id="app">
    <router-view />
  </div>
</template>

<script>
export default {
    name: 'App',
    mounted() {
        console.log('App mounted', this.$route)
    }
}
</script>