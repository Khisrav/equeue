<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from "vue";
import axios from "axios";
import Echo from "laravel-echo";

interface Queue {
	id: string;
	ticket_number: string;
	patient_name: string;
	status: string;
	doctor: {
		id: number;
		name: string;
		specialization: string;
		room_number: string;
	};
	created_at: string;
}

interface Doctor {
	id: number;
	name: string;
	specialization: string;
	room_number: string;
	queues: Queue[];
}

interface QueueData {
	queues: Queue[];
	doctors: Doctor[];
	institution_name: string;
}

// Reactive data
const queues = ref<Queue[]>([]);
const doctors = ref<Doctor[]>([]);
const institutionName = ref<string>("Медицинское учреждение");
const currentTime = ref<string>("");
const lastUpdated = ref<string>("");
const institutionId = ref<number | null>(null);

// Create Echo instance manually for better control
const echo = new Echo({
	broadcaster: 'reverb',
	key: import.meta.env.VITE_REVERB_APP_KEY,
	wsHost: import.meta.env.VITE_REVERB_HOST,
	wsPort: import.meta.env.VITE_REVERB_PORT,
	wssPort: import.meta.env.VITE_REVERB_PORT,
	forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
	enabledTransports: ['ws', 'wss'],
});

// Computed properties
const currentlyServing = computed(() =>
	queues.value
		.filter((q: Queue) => q.status === "called")
		.sort((a: Queue, b: Queue) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
);

const nextInLine = computed(() =>
	queues.value
		.filter((q: Queue) => q.status === "waiting")
		.sort((a: Queue, b: Queue) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
);

// Methods
const updateCurrentTime = () => {
	const now = new Date();
	currentTime.value = now.toLocaleString("ru-RU", {
		weekday: "long",
		year: "numeric",
		month: "long",
		day: "numeric",
		hour: "2-digit",
		minute: "2-digit",
		second: "2-digit",
	});
};

const fetchQueueData = async () => {
	try {
		// Build API URL with institution_id parameter if available
		let apiUrl = "/api/queue-monitor";
		if (institutionId.value) {
			apiUrl += `?institution_id=${institutionId.value}`;
		}
		
		const response = await axios.get<QueueData>(apiUrl);
		queues.value = response.data.queues;
		doctors.value = response.data.doctors;
		institutionName.value = response.data.institution_name;
		lastUpdated.value = new Date().toLocaleTimeString("ru-RU");
	} catch (error) {
		console.error("Error fetching queue data:", error);
	}
};



// Lifecycle
onMounted(() => {
	// Get institution ID from URL parameters or use default
	const urlParams = new URLSearchParams(window.location.search);
	const institutionIdParam = urlParams.get('institution_id');
	if (institutionIdParam) {
		institutionId.value = parseInt(institutionIdParam);
	} else {
		institutionId.value = 1; // Default to first institution
	}

	// Initial data fetch
	fetchQueueData();
	updateCurrentTime();

	// Set up real-time updates
	if (institutionId.value && echo) {
		console.log(`Setting up Echo channel: queue-monitor.${institutionId.value}`);
		echo.channel(`queue-monitor.${institutionId.value}`)
			.listen('queue.updated', (event: any) => {
				console.log('Queue updated via broadcast:', event);
				// Update the data from the broadcast
				queues.value = event.data.queues;
				doctors.value = event.data.doctors;
				institutionName.value = event.data.institution_name;
				lastUpdated.value = new Date().toLocaleTimeString("ru-RU");
			});
	}

	// Update time every second
	const timeInterval = setInterval(updateCurrentTime, 1000);

	// Cleanup
	onUnmounted(() => {
		clearInterval(timeInterval);
		if (institutionId.value && echo) {
			echo.leaveChannel(`queue-monitor.${institutionId.value}`);
		}
	});
});
</script>

<template>
	<div class="min-h-screen bg-gray-200 p-4">
		<!-- Current Status -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-[calc(100vh-32px)]">
			<!-- Now Serving -->
			<div class="col-span-4 bg-white rounded-3xl p-4 text-center border border-4 border-green-400">
				<h2 class="text-5xl font-bold text-gray-800 mb-8">Обслуживается</h2>
				<div v-if="currentlyServing.length > 0" class="flex flex-wrap gap-4">
					<div v-for="queue in currentlyServing" :key="queue.id" class="border-2 border-green-300 rounded-xl bg-green-50 px-6 py-4">
						<div class="text-5xl font-bold text-green-600">{{ queue.ticket_number }}</div>
						<!-- <div class="text-3xl text-gray-700 font-semibold mb-2">{{ queue.doctor.name }}</div>
						<div class="text-2xl text-gray-600">Кабинет {{ queue.doctor.room_number }}</div> -->
					</div>
				</div>
				<div v-else class="text-5xl text-gray-400">---</div>
			</div>

			<!-- Next in Line -->
			<div class="col-span-8 bg-white rounded-3xl p-4 text-center">
				<h2 class="text-5xl font-bold text-gray-800 mb-8">Следующие в очереди</h2>
				<div v-if="nextInLine.length > 0" class="flex flex-wrap gap-4">
					<div v-for="queue in nextInLine" :key="queue.id" class="border-2 border-yellow-300 rounded-xl bg-yellow-50 px-6 py-4">
						<div class="text-5xl font-bold text-yellow-600">{{ queue.ticket_number }}</div>
					</div>
				</div>
				<div v-else class="text-5xl text-gray-400">---</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
@keyframes pulse {
	0%,
	100% {
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
