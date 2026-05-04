import { onMounted, onUnmounted, ref } from 'vue'

const DEFAULT_IDLE_TIMEOUT = 30 * 60 * 1000
const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click']

export function useIdleSessionTimeout(options = {}) {
  const timeout = options.timeout ?? DEFAULT_IDLE_TIMEOUT
  const onExpire = options.onExpire

  const isSessionExpired = ref(false)
  let idleTimer = null

  const stopIdleTimer = () => {
    if (idleTimer) {
      clearTimeout(idleTimer)
      idleTimer = null
    }

    ACTIVITY_EVENTS.forEach((eventName) => {
      window.removeEventListener(eventName, resetIdleTimer)
    })
  }

  const resetIdleTimer = () => {
    if (isSessionExpired.value) {
      return
    }

    if (idleTimer) {
      clearTimeout(idleTimer)
    }

    idleTimer = setTimeout(() => {
      isSessionExpired.value = true
      stopIdleTimer()

      if (typeof onExpire === 'function') {
        onExpire()
      }
    }, timeout)
  }

  const startIdleTimer = () => {
    isSessionExpired.value = false
    resetIdleTimer()

    ACTIVITY_EVENTS.forEach((eventName) => {
      window.addEventListener(eventName, resetIdleTimer)
    })
  }

  onMounted(() => {
    startIdleTimer()
  })

  onUnmounted(() => {
    stopIdleTimer()
  })

  return {
    isSessionExpired,
    resetIdleTimer,
    startIdleTimer,
    stopIdleTimer,
  }
}