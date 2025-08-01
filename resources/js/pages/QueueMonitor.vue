<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-6">
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-6xl font-bold text-gray-800 mb-2">Электронная очередь</h1>
      <p class="text-2xl text-gray-600">{{ currentTime }}</p>
      <p class="text-xl text-gray-500">{{ institutionName }}</p>
    </div>

    <!-- Current Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Now Serving -->
      <div class="bg-white rounded-2xl shadow-lg p-8 text-center border-l-8 border-green-500">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Обслуживается</h2>
        <div v-if="currentlyServing.length > 0">
          <div v-for="queue in currentlyServing" :key="queue.id" class="mb-4">
            <div class="text-6xl font-bold text-green-600 mb-2">{{ queue.ticket_number }}</div>
            <div class="text-xl text-gray-600">{{ queue.doctor.name }}</div>
            <div class="text-lg text-gray-500">Каб. {{ queue.doctor.room_number }}</div>
          </div>
        </div>
        <div v-else class="text-4xl text-gray-400">---</div>
      </div>

      <!-- Next in Line -->
      <div class="bg-white rounded-2xl shadow-lg p-8 text-center border-l-8 border-yellow-500">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Следующий</h2>
        <div v-if="nextInLine.length > 0">
          <div v-for="queue in nextInLine" :key="queue.id" class="mb-4">
            <div class="text-6xl font-bold text-yellow-600 mb-2">{{ queue.ticket_number }}</div>
            <div class="text-xl text-gray-600">{{ queue.doctor.name }}</div>
            <div class="text-lg text-gray-500">Каб. {{ queue.doctor.room_number }}</div>
          </div>
        </div>
        <div v-else class="text-4xl text-gray-400">---</div>
      </div>

      <!-- Statistics -->
      <div class="bg-white rounded-2xl shadow-lg p-8 text-center border-l-8 border-blue-500">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Статистика</h2>
        <div class="space-y-3">
          <div>
            <div class="text-3xl font-bold text-blue-600">{{ statistics.waiting }}</div>
            <div class="text-sm text-gray-600">В ожидании</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-green-600">{{ statistics.completed }}</div>
            <div class="text-sm text-gray-600">Завершено</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Queue by Doctor -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="doctor in doctorsWithQueues" 
        :key="doctor.id"
        class="bg-white rounded-xl shadow-lg overflow-hidden"
      >
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 text-white">
          <h3 class="text-xl font-semibold">{{ doctor.name }}</h3>
          <p class="text-indigo-100">{{ doctor.specialization }}</p>
          <p class="text-indigo-100">Кабинет {{ doctor.room_number }}</p>
        </div>
        
        <div class="p-4">
          <div v-if="doctor.queues.length > 0" class="space-y-2">
            <div 
              v-for="queue in doctor.queues.slice(0, 5)" 
              :key="queue.id"
              class="flex items-center justify-between p-3 rounded-lg border-2"
              :class="{
                'border-green-200 bg-green-50': queue.status === 'called',
                'border-yellow-200 bg-yellow-50': queue.status === 'waiting',
                'border-red-200 bg-red-50': queue.status === 'skipped',
                'border-gray-200 bg-gray-50': queue.status === 'done'
              }"
            >
              <div class="flex items-center space-x-3">
                <div 
                  class="text-2xl font-bold px-3 py-1 rounded-lg"
                  :class="{
                    'text-green-700 bg-green-200': queue.status === 'called',
                    'text-yellow-700 bg-yellow-200': queue.status === 'waiting',
                    'text-red-700 bg-red-200': queue.status === 'skipped',
                    'text-gray-700 bg-gray-200': queue.status === 'done'
                  }"
                >
                  {{ queue.ticket_number }}
                </div>
                <div class="text-gray-700">{{ queue.patient_name }}</div>
              </div>
              
              <div 
                class="px-3 py-1 rounded-full text-sm font-medium"
                :class="{
                  'text-green-700 bg-green-200': queue.status === 'called',
                  'text-yellow-700 bg-yellow-200': queue.status === 'waiting',
                  'text-red-700 bg-red-200': queue.status === 'skipped',
                  'text-gray-700 bg-gray-200': queue.status === 'done'
                }"
              >
                {{ getStatusText(queue.status) }}
              </div>
            </div>
            
            <div v-if="doctor.queues.length > 5" class="text-center text-gray-500 pt-2">
              +{{ doctor.queues.length - 5 }} еще в очереди
            </div>
          </div>
          
          <div v-else class="text-center text-gray-400 py-8">
            Нет пациентов в очереди
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with last update time -->
    <div class="fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-3">
      <div class="flex items-center space-x-2 text-sm text-gray-600">
        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
        <span>Обновлено: {{ lastUpdated }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'

interface Queue {
  id: string
  ticket_number: string
  patient_name: string
  status: string
  doctor: {
    id: number
    name: string
    specialization: string
    room_number: string
  }
  created_at: string
}

interface Doctor {
  id: number
  name: string
  specialization: string
  room_number: string
  queues: Queue[]
}

interface QueueData {
  queues: Queue[]
  doctors: Doctor[]
  institution_name: string
}

// Reactive data
const queues = ref<Queue[]>([])
const doctors = ref<Doctor[]>([])
const institutionName = ref<string>('Медицинское учреждение')
const currentTime = ref<string>('')
const lastUpdated = ref<string>('')
const refreshInterval = ref<number | null>(null)

// Computed properties
const currentlyServing = computed(() => 
  queues.value.filter((q: Queue) => q.status === 'called').slice(0, 3)
)

const nextInLine = computed(() => 
  queues.value.filter((q: Queue) => q.status === 'waiting').slice(0, 3)
)

const statistics = computed(() => ({
  waiting: queues.value.filter((q: Queue) => q.status === 'waiting').length,
  completed: queues.value.filter((q: Queue) => q.status === 'done').length,
  total: queues.value.length
}))

const doctorsWithQueues = computed(() => 
  doctors.value.map((doctor: Doctor) => ({
    ...doctor,
    queues: queues.value
      .filter((q: Queue) => q.doctor.id === doctor.id && q.status !== 'done')
      .sort((a: Queue, b: Queue) => {
        // Sort by status priority: called > waiting > skipped
        const statusPriority: Record<string, number> = { called: 1, waiting: 2, skipped: 3 }
        return statusPriority[a.status] - statusPriority[b.status]
      })
  }))
  .filter((doctor: Doctor) => doctor.queues.length > 0)
)

// Methods
const updateCurrentTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleString('ru-RU', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const fetchQueueData = async () => {
  try {
    const response = await axios.get<QueueData>('/api/queue-monitor')
    queues.value = response.data.queues
    doctors.value = response.data.doctors
    institutionName.value = response.data.institution_name
    lastUpdated.value = new Date().toLocaleTimeString('ru-RU')
  } catch (error) {
    console.error('Error fetching queue data:', error)
  }
}

const getStatusText = (status: string): string => {
  const statusMap: Record<string, string> = {
    waiting: 'Ожидание',
    called: 'Вызван',
    skipped: 'Пропущен',
    done: 'Завершен',
    canceled: 'Отменен'
  }
  return statusMap[status] || status
}

// Lifecycle
onMounted(() => {
  fetchQueueData()
  updateCurrentTime()
  
  // Update every 5 seconds
  refreshInterval.value = setInterval(() => {
    fetchQueueData()
    updateCurrentTime()
  }, 5000)
})

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style> 