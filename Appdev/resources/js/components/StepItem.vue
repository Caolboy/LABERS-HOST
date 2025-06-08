<template>
  <div class="min-w-full bg-gray-50 p-4">
    <div class="mb-6">
      <div class="flex items-center mb-3">
        <div class="bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">
          2
        </div>
        <h2 class="text-xl font-semibold text-orange-600">Select a Room or Equipment</h2>
      </div>
      <p class="text-sm text-gray-600 ml-11">Choose the room or equipment you want to book. You can select multiple items.</p>
    </div>

    <div class="space-y-3 mb-8 ml-11">
      <div
        v-for="item in items"
        :key="item.id"
        @click="toggleItem(item)"
        :class="[
          'border rounded-lg p-4 cursor-pointer transition-all duration-200',
          isSelected(item) 
            ? 'border-orange-500 bg-orange-50' 
            : 'border-gray-200 hover:border-gray-300'
        ]"
      >
        <div class="flex items-center">
          <div class="flex-shrink-0 mr-4">
            <div :class="[
                'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                isSelected(item) 
                  ? 'border-orange-500 bg-orange-500' 
                  : 'border-gray-300'
              ]"
            >
              <div v-if="isSelected(item)" class="w-2 h-2 bg-white rounded-full"></div>
            </div>
          </div>
          <div class="flex-1">
            <div class="flex items-center mb-1">
              <div class="text-orange-500 mr-3">
                <svg v-if="item.type === 'room'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 2h8v8H6V6z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd" />
                </svg>
              </div>
              <h3 class="font-medium text-gray-800">{{ item.name }}</h3>
            </div>
            
            <template v-if="item.type === 'room'">
              <p v-if="item.lab_description" class="text-sm text-gray-600 mb-1">
                {{ item.lab_description }}
              </p>
              <p v-if="item.lab_name" class="text-sm text-gray-500 italic">{{ item.lab_name }}</p>
            </template>

            <template v-else>
              <p v-if="item.description" class="text-sm text-gray-600 mb-2">
                {{ item.description }}
              </p>
              <p v-if="item.available_quantity" class="text-xs text-gray-500 mb-2">
                Available: {{ item.available_quantity }}
              </p>
              
              <!-- Quantity Controls for Equipment -->
              <div v-if="isSelected(item)" class="flex items-center space-x-2" @click.stop>
                <span class="text-sm text-gray-600">Quantity:</span>
                <div class="flex items-center space-x-1">
                  <button
                    @click="decrementQuantity(item)"
                    :disabled="getQuantity(item) <= 1"
                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                  </button>
                  <span class="w-8 text-center text-sm font-medium">{{ getQuantity(item) }}</span>
                  <button
                    @click="incrementQuantity(item)"
                    :disabled="getQuantity(item) >= item.available_quantity"
                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Selected Items Summary -->
    <div v-if="selectedItems.length > 0" class="mb-6 ml-11">
      <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <h4 class="font-medium text-orange-800 mb-2">Selected Items ({{ selectedItems.length }})</h4>
        <div class="space-y-1">
          <div v-for="selected in selectedItems" :key="selected.id" class="flex justify-between items-center text-sm">
            <span class="text-gray-700">
              {{ selected.name }} 
              <span class="text-gray-500">({{ selected.type }})</span>
            </span>
            <span v-if="selected.type === 'equipment'" class="text-orange-600 font-medium">
              Qty: {{ selected.quantity }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-between">
      <button
        class="px-6 py-2 rounded-lg font-medium transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300"
        @click="$emit('back')"
      >
        ← Back
      </button>
      <button
        :class="[
          'px-6 py-2 rounded-lg font-medium transition-all duration-200',
          selectedItems.length > 0
            ? 'bg-orange-500 text-white hover:bg-orange-600' 
            : 'bg-gray-200 text-gray-400 cursor-not-allowed'
        ]"
        :disabled="selectedItems.length === 0"
        @click="$emit('next')"
      >
        Next →
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  items: Array,
  selectedItem: [Object, Array], // Can be single item or array of items
})

const emit = defineEmits(['update:selectedItem', 'next', 'back'])

// Internal state for selected items with quantities
const selectedItems = ref([])

// Initialize selectedItems from props
watch(() => props.selectedItem, (newValue) => {
  if (Array.isArray(newValue)) {
    selectedItems.value = [...newValue]
  } else if (newValue) {
    selectedItems.value = [{ ...newValue, quantity: newValue.quantity || 1 }]
  } else {
    selectedItems.value = []
  }
}, { immediate: true })

// Emit changes to parent
watch(selectedItems, (newValue) => {
  emit('update:selectedItem', newValue)
}, { deep: true })

function isSelected(item) {
  return selectedItems.value.some(selected => selected.id === item.id && selected.type === item.type)
}

function toggleItem(item) {
  const index = selectedItems.value.findIndex(selected => selected.id === item.id && selected.type === item.type)
  
  if (index > -1) {
    // Remove item
    selectedItems.value.splice(index, 1)
  } else {
    // Add item with default quantity
    const newItem = { ...item, quantity: 1 }
    selectedItems.value.push(newItem)
  }
}

function getQuantity(item) {
  const selected = selectedItems.value.find(selected => selected.id === item.id && selected.type === item.type)
  return selected ? selected.quantity : 1
}

function incrementQuantity(item) {
  const selected = selectedItems.value.find(selected => selected.id === item.id && selected.type === item.type)
  if (selected && selected.quantity < item.available_quantity) {
    selected.quantity++
  }
}

function decrementQuantity(item) {
  const selected = selectedItems.value.find(selected => selected.id === item.id && selected.type === item.type)
  if (selected && selected.quantity > 1) {
    selected.quantity--
  }
}
</script>