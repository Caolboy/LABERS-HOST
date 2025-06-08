<template>
  <div class="min-h-screen p-6">
      <div class="grid md:grid-cols-2 gap-6 md:items-start">
        <div class="space-y-6">
          <div class="bg-gradient-to-r from-orange-400 to-orange-500 text-white p-6 rounded-3xl relative overflow-hidden">
            <div class="flex justify-between items-start">
              <div class="z-10 relative">
                <h1 class="text-2xl font-bold mb-2">Welcome back, {{ userName || 'User' }}!</h1>
                <p class="text-orange-100 mb-1" v-if="pendingBookingsCount > 0">
                  You still have <strong>{{ pendingBookingsCount }} pending request{{ pendingBookingsCount !== 1 ? 's' : '' }}</strong>.
                </p>
                <p class="text-orange-100 mb-1" v-else>
                  You have no pending requests.
                </p>
                <p class="text-orange-100">Check their status.</p> 
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-sm">
            <div class="p-6">
              <h2 class="text-xl font-semibold text-orange-600 mb-4">My Bookings</h2>
              
              <div v-if="loading" class="text-center py-8 text-gray-500">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto"></div>
                <p class="mt-2">Loading...</p>
              </div>
              
              <div v-else-if="error" class="text-center py-8">
                <div class="text-red-600">{{ error }}</div>
              </div>
              
              <div v-else-if="roomBookings.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                  <svg class="w-16 h-16 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <p class="text-gray-600">No room bookings yet</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="booking in paginatedRoomBookings"
                  :key="'room-' + booking.id"
                  class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <h3 class="font-medium text-gray-900 mb-1">
                        {{ booking.lab_name }}
                      </h3>
                      <p class="text-sm text-gray-600 mb-2">
                        Room {{ booking.room_number }}
                      </p>
                      <div class="flex items-center text-sm text-gray-500 space-x-4">
                        <span class="flex items-center">
                          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                          </svg>
                          {{ formatDate(booking.date) }}
                        </span>
                        <span class="flex items-center">
                          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                          </svg>
                          {{ booking.time }}
                        </span>
                      </div>
                      <div class="mt-2">
                        <span 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getStatusClass(booking.status)"
                        >
                          {{ booking.status.charAt(0).toUpperCase() + booking.status.slice(1) }}
                        </span>
                      </div>
                    </div>
                    <button
                      v-if="booking.status === 'pending'"
                      @click="openCancelModal(booking, 'room')"
                      class="ml-4 px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors text-sm font-medium"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>

              <div v-if="roomBookings.length > 0 && totalRoomPages > 1" class="flex justify-center mt-4 gap-3">
                <button
                  @click="prevRoomPage"
                  :disabled="currentRoomPage === 1"
                  class="bg-orange-300 hover:bg-orange-400 text-white px-4 py-2 rounded text-sm font-medium disabled:opacity-50"
                >
                  Previous
                </button>
                <span class="text-sm text-gray-700 flex items-center">Page {{ currentRoomPage }} of {{ totalRoomPages }}</span>
                <button
                  @click="nextRoomPage"
                  :disabled="currentRoomPage === totalRoomPages"
                  class="bg-orange-300 hover:bg-orange-400 text-white px-4 py-2 rounded text-sm font-medium disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6 md:sticky md:top-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-gradient-to-r from-orange-400 to-orange-500 text-white p-4 rounded-2xl relative overflow-hidden">
              <div class="relative z-10">
                <h3 class="text-sm font-medium text-orange-100 mb-1">Total Room Bookings</h3>
                <p class="text-2xl font-bold">{{ roomBookings.length }}</p>
              </div>
            </div>
            
            <div class="bg-gradient-to-r from-orange-400 to-orange-500 text-white p-4 rounded-2xl relative overflow-hidden">
              <div class="relative z-10">
                <h3 class="text-sm font-medium text-orange-100 mb-1">Total Equipment Bookings</h3>
                <p class="text-2xl font-bold">{{ equipmentBookings.length }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-sm">
            <div class="p-6">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-orange-600">Equipment Reservations</h2>
              </div>
              
              <div v-if="loading" class="text-center py-8 text-gray-500">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto"></div>
                <p class="mt-2">Loading...</p>
              </div>
              
              <div v-else-if="equipmentBookings.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                  <svg class="w-16 h-16 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                  </svg>
                </div>
                <p class="text-gray-600">No equipment reservations yet</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="booking in paginatedEquipmentBookings"
                  :key="'equipment-' + booking.id"
                  class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <h3 class="font-medium text-gray-900 mb-1">
                        {{ booking.equipment }}
                      </h3>
                      <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        {{ formatDate(booking.date) }}
                      </div>
                      <div class="mt-2">
                        <span 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getStatusClass(booking.status)"
                        >
                          {{ booking.status.charAt(0).toUpperCase() + booking.status.slice(1) }}
                        </span>
                      </div>
                    </div>
                    <button
                      v-if="booking.status === 'pending'"
                      @click="openCancelModal(booking, 'equipment')"
                      class="ml-4 px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors text-sm font-medium"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>

              <div v-if="equipmentBookings.length > 0 && totalEquipmentPages > 1" class="flex justify-center mt-4 gap-3">
                <button
                  @click="prevEquipmentPage"
                  :disabled="currentEquipmentPage === 1"
                  class="bg-orange-300 hover:bg-orange-400 text-white px-4 py-2 rounded text-sm font-medium disabled:opacity-50"
                >
                  Previous
                </button>
                <span class="text-sm text-gray-700 flex items-center">Page {{ currentEquipmentPage }} of {{ totalEquipmentPages }}</span>
                <button
                  @click="nextEquipmentPage"
                  :disabled="currentEquipmentPage === totalEquipmentPages"
                  class="bg-orange-300 hover:bg-orange-400 text-white px-4 py-2 rounded text-sm font-medium disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            </div>
          </div>

          <div class="w-full">
            <button
              @click="goToBooking"
              class="w-full bg-gradient-to-r from-orange-400 to-orange-500 text-white px-12 py-5 rounded-2xl hover:from-orange-500 hover:to-orange-600 transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-1"
            >
              <span class="flex items-center justify-center">
                Start Booking
                <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
              </span>
            </button>
          </div>
        </div>
      </div>
 

    <transition name="fade-scale">
      <div
        v-if="showCancelModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm px-4"
        @click.self="closeCancelModal"
      >
        <div class="bg-white p-6 rounded-xl w-full max-w-sm shadow-lg text-center" @click.stop>
          <h3 class="text-xl font-semibold text-orange-600 mb-3">Cancel Booking</h3>
          <p class="text-gray-700 mb-5">
            Are you sure you want to cancel 
            <strong>{{ cancelBooking.lab_name || cancelBooking.equipment }}</strong>
            <strong><span v-if="cancelBooking.room_number"> - Room {{ cancelBooking.room_number }}</span></strong>
            <span v-if="cancelBooking.date"> booking on {{ formatDate(cancelBooking.date) }}</span>?
          </p>
          <div class="flex justify-center gap-4">
            <button 
              @click="confirmCancelBooking" 
              class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md font-semibold"
            >
              Cancel Booking
            </button>
            <button 
              @click="closeCancelModal" 
              class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-md font-semibold"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const bookings = ref([])
const loading = ref(true)
const error = ref(null)
const userName = ref('')
const pendingBookingsCount = ref(0)

const showCancelModal = ref(false)
const cancelBooking = ref({})
const cancelBookingType = ref('')

const itemsPerPage = 3
const currentRoomPage = ref(1)
const currentEquipmentPage = ref(1)

const roomBookings = computed(() => 
  bookings.value.filter(booking => booking.type === 'room')
)

const equipmentBookings = computed(() => 
  bookings.value.filter(booking => booking.type === 'equipment')
)

const paginatedRoomBookings = computed(() => {
  const start = (currentRoomPage.value - 1) * itemsPerPage
  return roomBookings.value.slice(start, start + itemsPerPage)
})

const totalRoomPages = computed(() => 
  Math.ceil(roomBookings.value.length / itemsPerPage)
)

const nextRoomPage = () => {
  if (currentRoomPage.value < totalRoomPages.value) {
    currentRoomPage.value++
  }
}

const prevRoomPage = () => {
  if (currentRoomPage.value > 1) {
    currentRoomPage.value--
  }
}

const paginatedEquipmentBookings = computed(() => {
  const start = (currentEquipmentPage.value - 1) * itemsPerPage
  return equipmentBookings.value.slice(start, start + itemsPerPage)
})

const totalEquipmentPages = computed(() => 
  Math.ceil(equipmentBookings.value.length / itemsPerPage)
)

const nextEquipmentPage = () => {
  if (currentEquipmentPage.value < totalEquipmentPages.value) {
    currentEquipmentPage.value++
  }
}

const prevEquipmentPage = () => {
  if (currentEquipmentPage.value > 1) {
    currentEquipmentPage.value--
  }
}

function getStatusClass(status) {
  const classes = {
    'approved': 'bg-green-100 text-green-800',
    'returned': 'bg-green-100 text-green-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

async function fetchDashboardData() {
  try {
    const { data } = await axios.get('/dashboard-data')
    userName.value = data.user_name
    pendingBookingsCount.value = data.pending_bookings_count
  } catch (e) {
    console.error('Failed to load dashboard data:', e)
  }
}

async function fetchBookings() {
  loading.value = true
  error.value = null
  
  try {
    const { data } = await axios.get('/bookings')
    bookings.value = data
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Failed to load bookings'
  } finally {
    loading.value = false
  }
}

function openCancelModal(booking, type) {
  cancelBooking.value = booking
  cancelBookingType.value = type
  showCancelModal.value = true
}

function closeCancelModal() {
  showCancelModal.value = false
  cancelBooking.value = {}
  cancelBookingType.value = ''
}

async function confirmCancelBooking() {
  try {
    await axios.post(`/bookings/${cancelBooking.value.id}/cancel`, {
      type: cancelBookingType.value
    })
    
    const bookingIndex = bookings.value.findIndex(
      b => b.id === cancelBooking.value.id && b.type === cancelBookingType.value
    )
    if (bookingIndex !== -1) {
      bookings.value[bookingIndex].status = 'cancelled'
    }
    
    if (cancelBooking.value.status === 'pending') {
      pendingBookingsCount.value = Math.max(0, pendingBookingsCount.value - 1)
    }
    
    closeCancelModal()
  } catch (e) {
    alert('Cancel failed: ' + (e.response?.data?.message || e.message))
  }
}

function goToBooking() {
  window.location.href = '/booking'
}

onMounted(async () => {
  await fetchDashboardData()
  await fetchBookings()
})
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
</style>