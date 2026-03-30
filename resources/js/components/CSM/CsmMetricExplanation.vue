<template>
  <div class="inline-block">
    <Button variant="ghost" size="sm" class="h-8 w-8 rounded-full p-0" :aria-label="`More details about ${title}`" @click="isOpen = true">
      <Info class="h-4 w-4 text-gray-500" />
    </Button>

    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" @click.self="closeModal">
      <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">
          <h4 class="text-base font-semibold text-gray-900">{{ title }}</h4>
          <button
            type="button"
            class="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
            :aria-label="`Close ${title} details`"
            @click="closeModal"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="space-y-5 p-5 text-sm">
          <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">What this shows</p>
            <p class="leading-relaxed text-gray-700">{{ meaning }}</p>
          </div>

          <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">How it is computed</p>
            <p class="leading-relaxed text-gray-700">{{ computation }}</p>
          </div>

          <div v-if="formula" class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Formula</p>
            <pre class="overflow-x-auto whitespace-pre-wrap rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-700">{{ formula }}</pre>
          </div>

          <div v-if="interpretation.length" class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">How to interpret</p>
            <ul class="list-disc space-y-2 pl-4 text-gray-700">
              <li v-for="(item, index) in interpretation" :key="`${title}-interpretation-${index}`">
                {{ item }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Info, X } from 'lucide-vue-next'

const isOpen = ref(false)

const closeModal = () => {
  isOpen.value = false
}

defineProps({
  title: {
    type: String,
    default: '',
  },
  meaning: {
    type: String,
    default: '',
  },
  computation: {
    type: String,
    default: '',
  },
  formula: {
    type: String,
    default: '',
  },
  interpretation: {
    type: Array,
    default: () => [],
  },
})
</script>