<template>
  <div class="min-w-full bg-gray-50 p-4">

    <div class="mb-6">
      <div class="flex items-center mb-3">
        <div class="bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">
          4
        </div>
        <h2 class="text-xl font-semibold text-orange-600">Confirm Booking</h2>
      </div>
      <p class="text-sm text-gray-600 ml-11">Please review your booking details before confirming.</p>
    </div>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-sm">

      <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Booking Summary</h3>
        <div class="space-y-4">
          
          <!-- Date -->
          <div class="flex justify-between items-start">
            <span class="text-sm text-gray-600">Date:</span>
            <span class="text-sm font-medium text-gray-900">{{ formatDate(date) }}</span>
          </div>
          
          <!-- Selected Items -->
          <div class="border-t pt-3">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Items ({{ selectedItems.length }})</h4>
            <div class="space-y-3">
              <div v-for="item in selectedItems" :key="item.id + '-' + item.type" class="bg-white p-3 rounded border">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <span class="text-sm font-medium text-gray-900">{{ item.name }}</span>
                    <span class="text-xs text-gray-500 block capitalize">({{ item.type }})</span>
                    <span v-if="item.lab_name" class="text-xs text-gray-500 block">{{ item.lab_name }}</span>
                  </div>
                  <div class="text-right text-sm">
                    <span v-if="item.type === 'equipment'" class="text-gray-600">
                      Qty: {{ item.quantity || 1 }}
                    </span>
                    <div v-if="item.type === 'room' && getRoomTimeSelection(item.id)" class="text-gray-600">
                      {{ getRoomTimeSelection(item.id).startTime }} - {{ getRoomTimeSelection(item.id).endTime }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="missingTimeSelections.length > 0" class="border-t pt-3">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div>
                  <h4 class="text-sm font-medium text-yellow-800">Missing Time Selections</h4>
                  <p class="text-sm text-yellow-700 mt-1">
                    Please select time slots for: {{ missingTimeSelections.join(', ') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="ml-11 mb-6">
      <div v-if="success" class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <div>
            <h4 class="text-sm font-medium text-green-800">Booking Successful!</h4>
            <p class="text-sm text-green-700 mt-1">Your booking has been confirmed and saved.</p>
          </div>
        </div>
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <div>
            <h4 class="text-sm font-medium text-red-800">Booking Failed</h4>
            <p class="text-sm text-red-700 mt-1">{{ error }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-between">
      <button
        @click="$emit('back')"
        class="px-6 py-2 rounded-lg font-medium transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300"
        :disabled="loading"
      >
        ← Back
      </button>
      <button
        @click="submitBooking"
        :class="[
          'px-6 py-2 rounded-lg font-medium transition-all duration-200 flex items-center',
          loading || success || !canConfirmBooking
            ? 'bg-gray-400 text-white cursor-not-allowed'
            : 'bg-orange-500 text-white hover:bg-orange-600'
        ]"
        :disabled="loading || success || !canConfirmBooking"
      >
        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else-if="success" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        {{ loading ? 'Processing...' : success ? 'Confirmed' : 'Confirm Booking' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  selectedItems: {
    type: Array,
    required: true,
    default: () => []
  },
  date: {
    type: String,
    required: true
  },
  roomTimeSelections: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['back', 'complete'])
const loading = ref(false)
const success = ref(false)
const error = ref(null)

const missingTimeSelections = computed(() => {
  const roomItems = props.selectedItems.filter(item => item.type === 'room')
  return roomItems
    .filter(room => !getRoomTimeSelection(room.id))
    .map(room => room.name)
})

const canConfirmBooking = computed(() => {
  return props.selectedItems.length > 0 && missingTimeSelections.value.length === 0
})

watch([() => props.selectedItems, () => props.date, () => props.roomTimeSelections], () => {
  success.value = false
  error.value = null
}, { deep: true })

onMounted(() => {
  success.value = false
  error.value = null
})

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function getRoomTimeSelection(roomId) {
  return props.roomTimeSelections[roomId] || null
}

async function submitBooking() {
  loading.value = true
  error.value = null
  success.value = false

  try {
    if (!props.selectedItems || props.selectedItems.length === 0) {
      throw new Error('No items selected for booking')
    }

    const roomItems = props.selectedItems.filter(item => item.type === 'room')
    for (const room of roomItems) {
      const timeSelection = getRoomTimeSelection(room.id)
      if (!timeSelection || !timeSelection.startTime || !timeSelection.endTime) {
        throw new Error(`Time selection missing for room ${room.name}`)
      }
    }

    const payload = {
      selected_items: props.selectedItems,
      date: props.date
    }

    if (roomItems.length === 1) {
      const roomTimeSelection = getRoomTimeSelection(roomItems[0].id)
      payload.start_time = roomTimeSelection.startTime
      payload.end_time = roomTimeSelection.endTime
    }

    payload.room_time_selections = props.roomTimeSelections

    console.log('Submitting booking payload:', payload)

    const response = await axios.post('/bookings', payload)
    
    console.log('Booking response:', response.data)
    
    success.value = true
    
    setTimeout(() => {
      emit('complete')
    }, 1500)
    
  } catch (e) {
    console.error('Booking error:', e)
    
    let errorMessage = 'An error occurred while processing your booking'
    
    if (e.response?.data?.message) {
      errorMessage = e.response.data.message
    } else if (e.response?.data?.errors) {
      const validationErrors = Object.values(e.response.data.errors).flat()
      errorMessage = validationErrors.join(', ')
    } else if (e.message) {
      errorMessage = e.message
    }
    
    error.value = errorMessage
  } finally {
    loading.value = false
  }
}
</script>