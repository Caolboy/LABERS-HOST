<template>
  <div class="min-w-full bg-gray-50 p-4">

    <div class="mb-6">
      <div class="flex items-center mb-3">
        <div class="bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold mr-3">
          3
        </div>
        <h2 class="text-xl font-semibold text-orange-600">Select Date & Time</h2>
      </div>
      <p class="text-sm text-gray-600 ml-11">Choose your preferred date and time slot for {{ isMultipleRooms ? 'each room' : 'the booking' }}.</p>
    </div>

    <div class="ml-11">
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3">Date:</label>
        
        <div class="bg-white rounded-lg shadow-lg overflow-hidden max-w-2xl">
          <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <button
              @click="previousMonth"
              :disabled="!canGoPrevious"
              :class="[
                'px-3 py-2 rounded-lg font-medium transition-colors',
                canGoPrevious 
                  ? 'bg-orange-500 text-white hover:bg-orange-600' 
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            
            <h3 class="text-xl font-bold text-orange-500">
              {{ currentMonthName }} {{ currentYear }}
            </h3>
            
            <button
              @click="nextMonth"
              class="bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 transition-colors font-medium"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>

          <div class="grid grid-cols-7 bg-gray-50">
            <div v-for="day in dayHeaders" :key="day" class="text-center text-gray-600 font-semibold text-sm py-3 border-r border-gray-200 last:border-r-0">
              {{ day }}
            </div>
          </div>

          <div class="grid grid-cols-7">
            <button
              v-for="day in calendarDays"
              :key="`${day.date}-${day.isCurrentMonth}`"
              @click="selectDate(day)"
              :disabled="day.isPast || !day.isCurrentMonth"
              :class="[
                'h-12 border-r border-b border-gray-200 last:border-r-0 text-sm font-semibold transition-all duration-150 hover:bg-gray-50',
                day.isSelected
                  ? 'bg-orange-500 text-white hover:bg-orange-600'
                  : day.isToday
                    ? 'bg-amber-100 text-orange-600 font-bold'
                    : day.isCurrentMonth && !day.isPast
                      ? 'text-gray-700 hover:bg-gray-50'
                      : day.isCurrentMonth && day.isPast
                        ? 'text-gray-400 cursor-not-allowed bg-gray-50'
                        : 'text-gray-300 cursor-not-allowed bg-gray-50',
                !day.isCurrentMonth ? 'text-gray-300' : ''
              ]"
            >
              {{ day.date }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="isRoom" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Time Selection{{ isMultipleRooms ? 's' : '' }}:
        </label>
        
        <div v-if="isMultipleRooms" class="space-y-6">
          <div 
            v-for="(room, index) in selectedItems" 
            :key="room.id"
            class="bg-white rounded-lg shadow-lg overflow-hidden"
          >
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-200 p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <h4 class="text-lg font-semibold text-blue-800">{{ room.name }}</h4>
                    <p class="text-sm text-blue-600">{{ room.lab_name }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <div v-if="roomTimeSelections[room.id]?.start_time && roomTimeSelections[room.id]?.end_time" 
                       class="text-sm font-medium text-blue-700">
                    {{ formatTime(roomTimeSelections[room.id].start_time) }} - 
                    {{ formatTime(roomTimeSelections[room.id].end_time) }}
                  </div>
                  <div v-else class="text-xs text-blue-500">Not scheduled</div>
                </div>
              </div>
            </div>

            <div class="p-6">
              <div v-if="loadingSlots[room.id]" class="py-8">
                <div class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-3 h-6 w-6 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span class="text-gray-600 font-medium">Loading available slots...</span>
                </div>
              </div>

              <div v-else>
                <div v-if="selectedDate" class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <div class="flex items-center justify-between mb-2">
                    <h6 class="font-medium text-gray-700 text-sm">
                      Availability for {{ formatDate(selectedDate) }}
                    </h6>
                    <div class="flex items-center space-x-3 text-xs">
                      <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-1"></div>
                        <span class="text-gray-600">Available</span>
                      </div>
                      <div class="flex items-center">
                        <div class="w-2 h-2 bg-red-400 rounded-full mr-1"></div>
                        <span class="text-gray-600">Booked</span>
                      </div>
                    </div>
                  </div>
                  
                  <div v-if="bookedTimeSlots[room.id]?.length > 0" class="mb-2">
                    <p class="text-xs text-gray-600 mb-1">Booked slots:</p>
                    <div class="flex flex-wrap gap-1">
                      <span 
                        v-for="slot in bookedTimeSlots[room.id]" 
                        :key="slot.id"
                        class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium border border-red-200"
                      >
                        {{ slot.slot }}
                      </span>
                    </div>
                  </div>
                  <div v-else class="text-xs text-green-700 bg-green-50 p-2 rounded border border-green-200">
                    ✓ No existing bookings
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      <svg class="w-3 h-3 inline mr-1 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Start Time
                    </label>
                    <select 
                      v-model="roomTimeSelections[room.id].start_time"
                      @change="validateTimeSelection(room.id)"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-white text-gray-700 text-sm"
                    >
                      <option value="">Select start time</option>
                      <option 
                        v-for="time in availableStartTimes" 
                        :key="`start-${room.id}-${time}`" 
                        :value="time"
                      >
                        {{ formatTime(time) }}
                      </option>
                    </select>
                  </div>

                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">
                      <svg class="w-3 h-3 inline mr-1 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      End Time
                    </label>
                    <select 
                      v-model="roomTimeSelections[room.id].end_time"
                      @change="validateTimeSelection(room.id)"
                      :disabled="!roomTimeSelections[room.id].start_time"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 disabled:bg-gray-100 disabled:cursor-not-allowed transition-all duration-200 bg-white text-gray-700 text-sm"
                    >
                      <option value="">Select end time</option>
                      <option 
                        v-for="time in getAvailableEndTimes(room.id)" 
                        :key="`end-${room.id}-${time}`" 
                        :value="time"
                      >
                        {{ formatTime(time) }}
                      </option>
                    </select>
                  </div>
                </div>

                <div v-if="roomTimeSelections[room.id].start_time && roomTimeSelections[room.id].end_time" 
                     class="mt-4 p-3 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <svg class="w-4 h-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-green-800">Scheduled</p>
                        <p class="text-xs text-green-700">
                          {{ formatTime(roomTimeSelections[room.id].start_time) }} - 
                          {{ formatTime(roomTimeSelections[room.id].end_time) }}
                        </p>
                      </div>
                    </div>
                    <div class="text-right">
                      <p class="text-sm font-bold text-green-600">
                        {{ calculateDuration(roomTimeSelections[room.id].start_time, roomTimeSelections[room.id].end_time) }}
                      </p>
                    </div>
                  </div>
                </div>

                <div v-if="timeConflictMessages[room.id]" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                  <div class="flex items-start">
                    <svg class="w-4 h-4 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.88-.833-2.65 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-red-800">Booking Conflict</p>
                      <p class="text-xs text-red-700">{{ timeConflictMessages[room.id] }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else>
          <div class="mb-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
            <div class="flex items-center mb-2">
              <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <h4 class="text-lg font-semibold text-blue-800">{{ selectedItems[0].name }}</h4>
            </div>
            <div class="text-sm text-blue-700">
              <p><strong>Lab:</strong> {{ selectedItems[0].lab_name }}</p>
              <p class="mt-1 text-blue-600">{{ selectedItems[0].lab_description }}</p>
            </div>
          </div>

          <div v-if="loadingSlots[selectedItems[0].id]" class="bg-white rounded-lg shadow-lg p-8">
            <div class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-6 w-6 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-gray-600 font-medium">Loading available time slots...</span>
            </div>
          </div>

          <div v-else class="bg-white rounded-lg shadow-lg p-6 max-w-3xl">
            <div v-if="selectedDate" class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <div class="flex items-center justify-between mb-3">
                <h5 class="font-semibold text-gray-800 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Availability for {{ formatDate(selectedDate) }}
                </h5>
                <div class="flex items-center space-x-4 text-sm">
                  <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-400 rounded-full mr-1"></div>
                    <span class="text-gray-600">Available</span>
                  </div>
                  <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-400 rounded-full mr-1"></div>
                    <span class="text-gray-600">Booked</span>
                  </div>
                </div>
              </div>
              
              <div v-if="bookedTimeSlots[selectedItems[0].id]?.length > 0" class="mb-3">
                <p class="text-sm text-gray-600 mb-2">Currently booked slots:</p>
                <div class="flex flex-wrap gap-2">
                  <span 
                    v-for="slot in bookedTimeSlots[selectedItems[0].id]" 
                    :key="slot.id"
                    class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium border border-red-200"
                  >
                    {{ slot.slot }}
                  </span>
                </div>
              </div>
              <div v-else class="text-sm text-green-700 bg-green-50 p-2 rounded border border-green-200">
                ✓ No existing bookings for this date
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">
                  <svg class="w-4 h-4 inline mr-1 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Start Time
                </label>
                <select 
                  v-model="roomTimeSelections[selectedItems[0].id].start_time"
                  @change="validateTimeSelection(selectedItems[0].id)"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-white text-gray-700 font-medium"
                >
                  <option value="" class="text-gray-500">Select start time</option>
                  <option 
                    v-for="time in availableStartTimes" 
                    :key="`start-${time}`" 
                    :value="time"
                    class="text-gray-700"
                  >
                    {{ formatTime(time) }}
                  </option>
                </select>
                <p class="text-xs text-gray-500">Choose when your session begins</p>
              </div>

              <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">
                  <svg class="w-4 h-4 inline mr-1 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  End Time
                </label>
                <select 
                  v-model="roomTimeSelections[selectedItems[0].id].end_time"
                  @change="validateTimeSelection(selectedItems[0].id)"
                  :disabled="!roomTimeSelections[selectedItems[0].id].start_time"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 disabled:bg-gray-100 disabled:cursor-not-allowed transition-all duration-200 bg-white text-gray-700 font-medium"
                >
                  <option value="" class="text-gray-500">Select end time</option>
                  <option 
                    v-for="time in getAvailableEndTimes(selectedItems[0].id)" 
                    :key="`end-${time}`" 
                    :value="time"
                    class="text-gray-700"
                  >
                    {{ formatTime(time) }}
                  </option>
                </select>
                <p class="text-xs text-gray-500">
                  {{ roomTimeSelections[selectedItems[0].id].start_time ? 'Choose when your session ends' : 'Please select a start time first' }}
                </p>
              </div>
            </div>

            <div v-if="roomTimeSelections[selectedItems[0].id].start_time && roomTimeSelections[selectedItems[0].id].end_time" class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <svg class="w-6 h-6 text-orange-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="font-semibold text-orange-800">Booking Summary</p>
                    <p class="text-sm text-orange-700">
                      {{ formatTime(roomTimeSelections[selectedItems[0].id].start_time) }} - {{ formatTime(roomTimeSelections[selectedItems[0].id].end_time) }}
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-orange-600">
                    {{ calculateDuration(roomTimeSelections[selectedItems[0].id].start_time, roomTimeSelections[selectedItems[0].id].end_time) }}
                  </p>
                  <p class="text-sm text-orange-600">duration</p>
                </div>
              </div>
            </div>

            <div v-if="timeConflictMessages[selectedItems[0].id]" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.88-.833-2.65 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                  <p class="font-semibold text-red-800 mb-1">Booking Conflict</p>
                  <p class="text-sm text-red-700">{{ timeConflictMessages[selectedItems[0].id] }}</p>
                  <p class="text-xs text-red-600 mt-1">Please select a different time slot.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-between">
      <button
        class="px-6 py-2 rounded-lg font-medium transition-all duration-200 bg-gray-200 text-gray-700 hover:bg-gray-300 flex items-center"
        @click="$emit('back')"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back
      </button>
      <button
        :class="[
          'px-6 py-2 rounded-lg font-medium transition-all duration-200 flex items-center',
          canContinue 
            ? 'bg-orange-500 text-white hover:bg-orange-600 shadow-lg hover:shadow-xl' 
            : 'bg-gray-200 text-gray-400 cursor-not-allowed'
        ]"
        :disabled="!canContinue"
        @click="onNext"
      >
        <span>Next</span>
        <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, reactive } from 'vue'
import axios from 'axios'

const props = defineProps({
  selectedItem:     Object,  
  selectedItems:    Array,     
  selectedDateTime: Object,   
  selectedDateTimes: Object,     
})
const emit = defineEmits(['update:selectedDateTime', 'update:selectedDateTimes', 'next', 'back'])

const isMultipleRooms = computed(() => Array.isArray(props.selectedItems) && props.selectedItems.length > 1)
const selectedItems = computed(() => {
  if (props.selectedItems) return props.selectedItems
  if (props.selectedItem) return [props.selectedItem]
  return []
})

// Initialize selectedDate from both possible sources
const selectedDate = ref(
  props.selectedDateTimes?.[Object.keys(props.selectedDateTimes)[0]]?.date || 
  props.selectedDateTime?.date || 
  ''
)
const isRoom = computed(() => selectedItems.value.some(item => item?.type === 'room'))

const roomTimeSelections = reactive({})
const timeConflictMessages = reactive({})
const bookedTimeSlots = reactive({})
const loadingSlots = reactive({})

const initializeRoomSelections = () => {
  selectedItems.value.forEach(room => {
    if (room.type === 'room') {
      if (!roomTimeSelections[room.id]) {
        // Initialize from props for both single and multiple selections
        const existingSelection = props.selectedDateTimes?.[room.id] || 
                                (props.selectedItem?.id === room.id ? props.selectedDateTime : null)
        
        roomTimeSelections[room.id] = {
          start_time: existingSelection?.start_time || '',
          end_time: existingSelection?.end_time || ''
        }
      }
      timeConflictMessages[room.id] = ''
      bookedTimeSlots[room.id] = []
      loadingSlots[room.id] = false
    }
  })
}

const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
]

const currentMonthName = computed(() => monthNames[currentMonth.value])

const canGoPrevious = computed(() => {
  const currentDate = new Date()
  const currentCalendarDate = new Date(currentYear.value, currentMonth.value, 1)
  const currentActualDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1)
  return currentCalendarDate > currentActualDate
})

const today = computed(() => {
  const d = new Date()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const year = d.getFullYear()
  return `${year}-${month}-${day}`
})

const todayDate = new Date()
const todayYear = todayDate.getFullYear()
const todayMonth = todayDate.getMonth()
const todayDay = todayDate.getDate()

const calendarDays = computed(() => {
  const days = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    
    const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    const isCurrentMonth = date.getMonth() === currentMonth.value
    const isToday = date.getFullYear() === todayYear && date.getMonth() === todayMonth && date.getDate() === todayDay
    const isPast = date < todayDate && !isToday
    const isSelected = selectedDate.value === dateStr
    
    days.push({
      date: date.getDate(),
      dateStr,
      isCurrentMonth,
      isToday,
      isPast,
      isSelected,
      fullDate: date
    })
  }
  
  return days
})

const timeOptions = computed(() => {
  const times = []
  for (let hour = 8; hour <= 18; hour++) {
    times.push(String(hour).padStart(2, '0') + ':00')
  }
  return times
})

const availableStartTimes = computed(() => {
  return timeOptions.value.slice(0, -1) 
})

const getAvailableEndTimes = (roomId) => {
  if (!roomTimeSelections[roomId]?.start_time) return []
  
  const startIndex = timeOptions.value.findIndex(time => time === roomTimeSelections[roomId].start_time)
  return timeOptions.value.slice(startIndex + 1) 
}

function selectDate(day) {
  if (day.isPast || !day.isCurrentMonth) return
  selectedDate.value = day.dateStr
  
  selectedItems.value.forEach(room => {
    if (room.type === 'room') {
      roomTimeSelections[room.id].start_time = ''
      roomTimeSelections[room.id].end_time = ''
      timeConflictMessages[room.id] = ''
    }
  })
}

function previousMonth() {
  if (!canGoPrevious.value) return
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

async function fetchBookedSlots() {
  if (!isRoom.value || !selectedDate.value) {
    selectedItems.value.forEach(room => {
      if (room.type === 'room') {
        bookedTimeSlots[room.id] = []
      }
    })
    return
  }
  
  const roomItems = selectedItems.value.filter(item => item.type === 'room')
  
  await Promise.all(roomItems.map(async (room) => {
    loadingSlots[room.id] = true
    try {
      const { data } = await axios.get('/booked-slots', {
        params: { 
          room_id: room.id, 
          date: selectedDate.value 
        }
      })
      bookedTimeSlots[room.id] = data
    } catch (error) {
      console.error(`Error fetching booked slots for room ${room.id}:`, error)
      bookedTimeSlots[room.id] = []
    } finally {
      loadingSlots[room.id] = false
    }
  }))
}

function validateTimeSelection(roomId) {
  timeConflictMessages[roomId] = ''
  
  if (!roomTimeSelections[roomId].start_time || !roomTimeSelections[roomId].end_time) return
  
  const requestedStart = roomTimeSelections[roomId].start_time
  const requestedEnd = roomTimeSelections[roomId].end_time
  
  for (const bookedSlot of bookedTimeSlots[roomId] || []) {
    const [bookedStart, bookedEnd] = bookedSlot.slot.split('-')
    
    if (
      (requestedStart >= bookedStart && requestedStart < bookedEnd) ||
      (requestedEnd > bookedStart && requestedEnd <= bookedEnd) ||
      (requestedStart <= bookedStart && requestedEnd >= bookedEnd)
    ) {
      timeConflictMessages[roomId] = `Time conflict with existing booking: ${bookedSlot.slot}`
      return
    }
  }
}

function formatTime(time) {
  const [hour, minute] = time.split(':')
  const hourInt = parseInt(hour)
  const period = hourInt >= 12 ? 'PM' : 'AM'
  const displayHour = hourInt > 12 ? hourInt - 12 : hourInt === 0 ? 12 : hourInt
  return `${displayHour}:${minute} ${period}`
}

function formatDate(dateStr) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

function calculateDuration(startTime, endTime) {
  const start = parseInt(startTime.split(':')[0])
  const end = parseInt(endTime.split(':')[0])
  const duration = end - start
  return duration === 1 ? '1 hour' : `${duration} hours`
}

watch(() => selectedItems.value, initializeRoomSelections, { immediate: true })
watch([selectedDate], fetchBookedSlots, { immediate: true })

// Fixed watchers for proper emission handling
watch([roomTimeSelections, selectedDate], () => {
  if (isMultipleRooms.value) {
    const dateTimeSelections = {}
    selectedItems.value.forEach(room => {
      if (room.type === 'room' && roomTimeSelections[room.id]) {
        dateTimeSelections[room.id] = {
          date: selectedDate.value,
          start_time: roomTimeSelections[room.id].start_time,
          end_time: roomTimeSelections[room.id].end_time,
          time: roomTimeSelections[room.id].start_time && roomTimeSelections[room.id].end_time 
                ? `${roomTimeSelections[room.id].start_time} - ${roomTimeSelections[room.id].end_time}` 
                : null
        }
      } else if (room.type === 'equipment') {
        dateTimeSelections[room.id] = {
          date: selectedDate.value,
          start_time: '',
          end_time: '',
          time: null
        }
      }
    })
    emit('update:selectedDateTimes', dateTimeSelections)
  } else {
    // Handle single selection - emit to selectedDateTime
    const singleItem = selectedItems.value[0]
    if (singleItem) {
      if (singleItem.type === 'room' && roomTimeSelections[singleItem.id]) {
        emit('update:selectedDateTime', {
          date: selectedDate.value,
          start_time: roomTimeSelections[singleItem.id].start_time,
          end_time: roomTimeSelections[singleItem.id].end_time,
          time: roomTimeSelections[singleItem.id].start_time && roomTimeSelections[singleItem.id].end_time 
                ? `${roomTimeSelections[singleItem.id].start_time} - ${roomTimeSelections[singleItem.id].end_time}` 
                : null
        })
      } else if (singleItem.type === 'equipment') {
        emit('update:selectedDateTime', {
          date: selectedDate.value,
          start_time: '',
          end_time: '',
          time: null
        })
      }
    }
  }
}, { deep: true })

// Fixed canContinue logic
const canContinue = computed(() => {
  if (!selectedDate.value) return false
  
  const roomItems = selectedItems.value.filter(item => item.type === 'room')
  const equipmentItems = selectedItems.value.filter(item => item.type === 'equipment')
  
  // If only equipment items, just need date
  if (roomItems.length === 0 && equipmentItems.length > 0) {
    return true
  }
  
  // If we have room items, they need time selections
  if (roomItems.length > 0) {
    return roomItems.every(room => {
      const selection = roomTimeSelections[room.id]
      return selection?.start_time && 
             selection?.end_time && 
             !timeConflictMessages[room.id]
    })
  }
  
  return false
})

function onNext() {
  if (canContinue.value) emit('next')
}
</script>