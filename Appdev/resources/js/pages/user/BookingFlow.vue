<template>
  <div class="min-h-screen p-6">
      <div class="bg-gradient-to-r from-orange-400 to-orange-500 text-white p-6 rounded-2xl mb-6">
        <h1 class="text-2xl font-bold mb-2">Ready to Book?</h1>
        <p class="text-orange-100">Follow the steps to book a laboratory or any equipment you need.</p>
      </div>

      <!-- Steps Container -->
      <div class="bg-white rounded-lg shadow-lg p-2 relative overflow-hidden">
        <!-- Step 1 -->
        <div class="relative">
          <transition name="step-slide" mode="out-in">
            <div v-if="currentStep === 0" key="step-0">
              <StepCategory
                :categories="categories"
                :selectedCategory="selectedCategory"
                @update:selectedCategory="(val) => (selectedCategory = val)"
                @next="nextStep"
              />
            </div>
            <div v-else key="step-0-collapsed" class="p-6 border-b border-gray-100">
              <div class="flex items-center">
                <div class="bg-gray-300 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">
                  1
                </div>
                <div class="flex-1">
                  <h3 class="text-sm font-medium text-gray-600">Choose Booking Type</h3>
                  <p class="text-sm text-gray-500">{{ selectedCategory?.name }}</p>
                </div>
              </div>
            </div>
          </transition>
          <div v-if="currentStep > 0" class="absolute left-8 top-16 w-0.5 h-full bg-orange-200 -z-10"></div>
        </div>

        <!-- Step 2 -->
        <div v-if="currentStep >= 1" class="relative">
          <transition name="step-slide" mode="out-in">
            <div v-if="currentStep === 1" key="step-1">
              <StepItem
                :items="items"
                :selectedItem="selectedItems"
                @update:selectedItem="(val) => (selectedItems = val)"
                @next="nextStep"
                @back="prevStep"
              />
            </div>
            <div v-else-if="currentStep > 1" key="step-1-collapsed" class="p-6 border-b border-gray-100">
              <div class="flex items-center">
                <div class="bg-gray-300 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">
                  2
                </div>
                <div class="flex-1">
                  <h3 class="text-sm font-medium text-gray-600">Select a Room or Equipment</h3>
                  <p class="text-sm text-gray-500">
                    {{ selectedItems.length }} item(s) selected
                    <template v-if="selectedItems.length <= 2">
                      : {{ selectedItems.map(item => `${item.name}${item.type === 'equipment' ? ` (${item.quantity})` : ''}`).join(', ') }}
                    </template>
                  </p>
                </div>
              </div>
            </div>
          </transition>
          <div v-if="currentStep > 1" class="absolute left-8 top-16 w-0.5 h-full bg-orange-200 -z-10"></div>
        </div>

        <!-- Step 3 -->
        <div v-if="currentStep >= 2" class="relative">
          <transition name="step-slide" mode="out-in">
            <div v-if="currentStep === 2" key="step-2">
              <StepDateTime
                v-if="isSingleSelection"
                :selectedItem="selectedItems[0]"
                :selectedDateTime="singleDateTime"
                @update:selectedDateTime="handleSingleDateTimeUpdate"
                @next="nextStep"
                @back="prevStep"
              />
              <StepDateTime
                v-else
                :selectedItems="selectedItems"
                :selectedDateTimes="selectedDateTimes"
                @update:selectedDateTimes="handleMultipleDateTimeUpdate"
                @next="nextStep"
                @back="prevStep"
              />
            </div>
            <div v-else-if="currentStep > 2" key="step-2-collapsed" class="p-6 border-b border-gray-100">
              <div class="flex items-center">
                <div class="bg-gray-300 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">
                  3
                </div>
                <div class="flex-1">
                  <h3 class="text-sm font-medium text-gray-600">Select Date & Time</h3>
                  <div class="text-sm text-gray-500">
                    <div v-if="isMultipleRooms">
                      <!-- Multiple rooms display -->
                      <div v-for="(room, index) in roomItems" :key="room.id" class="mb-1">
                        <span class="font-medium">{{ room.name }}:</span>
                        {{ formatDateTimeForRoom(room.id) }}
                      </div>
                    </div>
                    <div v-else>
                      <!-- Single room or equipment display -->
                      {{ formatSingleDateTime() }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </transition>
          <div v-if="currentStep > 2" class="absolute left-8 top-16 w-0.5 h-full bg-orange-200 -z-10"></div>
        </div>

        <!-- Step 4 -->
        <div v-if="currentStep >= 3">
          <transition name="step-slide" mode="out-in">
            <div v-if="currentStep === 3" key="step-3">
              <StepConfirm
                :selectedItems="selectedItems"
                :date="getBookingDate()"
                :roomTimeSelections="getRoomTimeSelections()"
                @back="prevStep"
                @complete="handleComplete"
              />
            </div>
          </transition>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <transition name="fade-scale">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
        @click.self="closeModal"
      >
        <div
          class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md mx-auto text-center"
          @click.stop
        >
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h2 class="text-2xl font-bold mb-2">Booking Complete!</h2>
          <p class="text-gray-600 mb-6">Your booking has been successfully completed.</p>
          <button
            @click="closeModal"
            class="bg-orange-500 text-white px-8 py-3 rounded-lg hover:bg-orange-600 w-full"
          >
            OK
          </button>
        </div>
      </div>
    </transition>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue' 
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import StepCategory from '@/components/StepCategory.vue'
import StepItem from '@/components/StepItem.vue'
import StepDateTime from '@/components/StepDateTime.vue'
import StepConfirm from '@/components/StepConfirm.vue'

const currentStep = ref(0)

const categories = ref([])
const selectedCategory = ref(null)
const items = ref([])
const selectedItems = ref([])

// Separate data structures for single vs multiple selections
const singleDateTime = ref({}) // For single item selections
const selectedDateTimes = ref({}) // For multiple item selections

const showModal = ref(false)

// Computed properties
const hasRoomSelected = computed(() => {
  return selectedItems.value.some(item => item.type === 'room')
})

const roomItems = computed(() => {
  return selectedItems.value.filter(item => item.type === 'room')
})

const isMultipleRooms = computed(() => {
  return roomItems.value.length > 1
})

const isSingleSelection = computed(() => {
  return selectedItems.value.length === 1
})

// Handle date time updates from child component
const handleSingleDateTimeUpdate = (dateTimeData) => {
  singleDateTime.value = dateTimeData || {}
}

const handleMultipleDateTimeUpdate = (dateTimeData) => {
  selectedDateTimes.value = dateTimeData || {}
}

// Helper functions for StepConfirm
const getBookingDate = () => {
  if (isSingleSelection.value) {
    return singleDateTime.value.date || ''
  } else {
    // Get the first available date from selectedDateTimes
    const firstDateTime = Object.values(selectedDateTimes.value)[0]
    return firstDateTime?.date || ''
  }
}

const getRoomTimeSelections = () => {
  const roomTimeSelections = {}
  
  if (isSingleSelection.value) {
    // Handle single selection
    const singleItem = selectedItems.value[0]
    if (singleItem && singleItem.type === 'room' && singleDateTime.value.start_time && singleDateTime.value.end_time) {
      roomTimeSelections[singleItem.id] = {
        startTime: singleDateTime.value.start_time,
        endTime: singleDateTime.value.end_time
      }
    }
  } else {
    // Handle multiple selections
    Object.keys(selectedDateTimes.value).forEach(itemId => {
      const dateTime = selectedDateTimes.value[itemId]
      if (dateTime && dateTime.start_time && dateTime.end_time) {
        roomTimeSelections[itemId] = {
          startTime: dateTime.start_time,
          endTime: dateTime.end_time
        }
      }
    })
  }
  
  return roomTimeSelections
}

// Helper functions for display
const formatTime = (time) => {
  if (!time) return ''
  const [hour, minute] = time.split(':')
  const hourInt = parseInt(hour)
  const period = hourInt >= 12 ? 'PM' : 'AM'
  const displayHour = hourInt > 12 ? hourInt - 12 : hourInt === 0 ? 12 : hourInt
  return `${displayHour}:${minute} ${period}`
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatDateTimeForRoom = (roomId) => {
  let dateTime
  
  if (isSingleSelection.value && selectedItems.value[0]?.id === roomId) {
    dateTime = singleDateTime.value
  } else {
    dateTime = selectedDateTimes.value[roomId]
  }
  
  if (!dateTime) return 'Not scheduled'
  
  let result = formatDate(dateTime.date)
  if (dateTime.start_time && dateTime.end_time) {
    result += ` - ${formatTime(dateTime.start_time)} - ${formatTime(dateTime.end_time)}`
  }
  return result
}

const formatSingleDateTime = () => {
  if (isSingleSelection.value) {
    const singleItem = selectedItems.value[0]
    if (singleItem.type === 'room') {
      return formatDateTimeForRoom(singleItem.id)
    } else {
      // Equipment only
      return singleDateTime.value.date ? formatDate(singleDateTime.value.date) : 'No date selected'
    }
  } else if (roomItems.value.length === 1) {
    return formatDateTimeForRoom(roomItems.value[0].id)
  }
  
  // For equipment only in multiple selection, show the shared date
  const firstDateTime = Object.values(selectedDateTimes.value)[0]
  return firstDateTime ? formatDate(firstDateTime.date) : 'No date selected'
}

const handleComplete = () => {
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  router.get(route('bookinghistory'))
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/categories')
    categories.value = data
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
})

watch(selectedCategory, async (newCat) => {
  if (!newCat) {
    items.value = []
    selectedItems.value = []
    singleDateTime.value = {}
    selectedDateTimes.value = {}
    return
  }
  try {
    const { data } = await axios.get('/items', {
      params: { category_id: newCat.id }
    })
    items.value = data
    selectedItems.value = []
    singleDateTime.value = {}
    selectedDateTimes.value = {}
  } catch (error) {
    console.error('Failed to fetch items:', error)
  }
})

// Watch for changes in selected items and reset date/time selections
watch(selectedItems, (newItems, oldItems) => {
  // Reset both date/time structures when items change
  singleDateTime.value = {}
  selectedDateTimes.value = {}
}, { deep: true })

const nextStep = () => {
  if (currentStep.value === 0 && !selectedCategory.value) return
  if (currentStep.value === 1 && selectedItems.value.length === 0) return
  
  if (currentStep.value === 2) {
    // Validation for step 3 (date/time selection)
    if (isSingleSelection.value) {
      // Single selection validation
      const singleItem = selectedItems.value[0]
      if (singleItem.type === 'room') {
        // Room needs complete date/time
        if (!singleDateTime.value.date || !singleDateTime.value.start_time || !singleDateTime.value.end_time) {
          return
        }
      } else {
        // Equipment only needs date
        if (!singleDateTime.value.date) {
          return
        }
      }
    } else {
      // Multiple selection validation
      if (hasRoomSelected.value) {
        // Check if all rooms have complete date/time selections
        const roomsValid = roomItems.value.every(room => {
          const dateTime = selectedDateTimes.value[room.id]
          return dateTime && 
                 dateTime.date && 
                 dateTime.start_time && 
                 dateTime.end_time
        })
        if (!roomsValid) return
      } else {
        // For equipment only, just need a date
        const hasValidDate = Object.values(selectedDateTimes.value).some(dt => dt && dt.date)
        if (!hasValidDate) return
      }
    }
  }
  
  if (currentStep.value < 3) currentStep.value++
}

const prevStep = () => {
  if (currentStep.value > 0) currentStep.value--
}
</script>

<style scoped>
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

.step-slide-enter-active,
.step-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.step-slide-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.step-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>