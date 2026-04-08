import { reactive } from 'vue'

class EventBus {
  constructor() {
    this.events = reactive({})
  }

  on(event, callback) {
    if (!this.events[event]) {
      this.events[event] = []
    }
    this.events[event].push(callback)
  }

  off(event, callback) {
    if (!this.events[event]) return
    if (!callback) {
      delete this.events[event]
      return
    }
    this.events[event] = this.events[event].filter(cb => cb !== callback)
  }

  emit(event, data) {
    if (!this.events[event]) return
    this.events[event].forEach(callback => callback(data))
  }
}

export const eventBus = new EventBus()