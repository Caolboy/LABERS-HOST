<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { ref, computed } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
  type?: string
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const isPassword = computed(() => props.type === 'password')
const showPassword = ref(false)
const isFocused = ref(false)

const inputType = computed(() => {
  if (isPassword.value) return showPassword.value ? 'text' : 'password'
  return props.type || 'text'
})

const iconColor = computed(() => {
  return (isFocused.value || modelValue.value) ? '#FD7C21' : '#b0b0b0'
})
</script>

<template>
  <div class="relative">
    <input
      v-model="modelValue"
      v-bind="$attrs"
      :type="inputType"
      data-slot="input"
      :class="cn(
        'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
        props.class,
      )"
      @focus="isFocused = true"
      @blur="isFocused = false"
    >
    <button
      v-if="isPassword"
      type="button"
      tabindex="-1"
      class="absolute inset-y-0 right-2 flex items-center px-1 bg-transparent border-0 outline-none focus:outline-none"
      @mousedown.prevent
      @click="showPassword = !showPassword"
      aria-label="Toggle password visibility"
    >
      <Eye v-if="!showPassword" :color="iconColor" :stroke="iconColor" :fill="'none'" :size="20" />
      <EyeOff v-else :color="iconColor" :stroke="iconColor" :fill="'none'" :size="20" />
    </button>
  </div>
</template>

<style scoped>
input::placeholder {
  text-shadow: 0 2px 6px rgba(0,0,0,0.18) !important;
}
</style>
