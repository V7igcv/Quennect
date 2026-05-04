<template>
  <Transition name="session-expired-fade">
    <div v-if="visible" class="fixed inset-0 z-70 flex items-center justify-center bg-black/60 px-4">
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.4 12.84A2 2 0 004.6 20h14.8a2 2 0 001.71-3.3l-7.4-12.84a2 2 0 00-3.42 0z" />
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-900">Session expired</h3>
          </div>
        </div>

        <p class="mb-6 text-sm leading-6 text-gray-600">
          Please log in again to continue using Quennect.
        </p>

        <div class="flex justify-end">
          <button
            type="button"
            class="rounded-lg bg-[#0F5C5C] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#167D7F]"
            @click="$emit('login')"
          >
            Log in again
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
export default {
  name: 'SessionExpiredModal',
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['login'],
}
</script>

<style scoped>
.session-expired-fade-enter-active,
.session-expired-fade-leave-active {
  transition: opacity 0.2s ease;
}

.session-expired-fade-enter-from,
.session-expired-fade-leave-to {
  opacity: 0;
}
</style>