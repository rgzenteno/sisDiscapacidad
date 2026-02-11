<template>
    <Head title="Prueba IA - Separador de Nombres" />
    <div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    🤖 Separador de Nombres con IA
                </h1>
                <p class="text-gray-600">
                    Tecnología de Machine Learning con spaCy
                </p>
            </div>

            <!-- Status de la API -->
            <div 
                class="mb-6 p-4 rounded-lg text-center font-medium"
                :class="statusClass"
            >
                {{ statusMensaje }}
            </div>

            <!-- Card principal -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <!-- Formulario -->
                <div class="mb-6">
                    <label for="nombreCompleto" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre Completo
                    </label>
                    <input
                        v-model="nombreCompleto"
                        @keypress.enter="separarNombre"
                        type="text"
                        id="nombreCompleto"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                        placeholder="Ej: María José Pérez de la Cruz"
                    />
                </div>

                <!-- Botón -->
                <button
                    @click="separarNombre"
                    :disabled="loading || !nombreCompleto.trim()"
                    class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center"
                >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-if="loading">Procesando con IA...</span>
                    <span v-else>✨ Separar con IA</span>
                </button>

                <!-- Resultado -->
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                >
                    <div v-if="resultado" class="mt-6">
                        <div 
                            class="p-6 rounded-lg"
                            :class="resultado.success ? 'bg-green-50 border-l-4 border-green-500' : 'bg-yellow-50 border-l-4 border-yellow-500'"
                        >
                            <h3 class="text-lg font-semibold mb-4" :class="resultado.success ? 'text-green-800' : 'text-yellow-800'">
                                {{ resultado.success ? '✅ Resultado' : '⚠️ Método de respaldo' }}
                            </h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-600">Original:</span>
                                    <p class="text-gray-900 font-medium">{{ resultado.original }}</p>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white p-4 rounded-lg">
                                        <span class="text-sm font-medium text-gray-600">Nombre:</span>
                                        <p class="text-xl font-bold text-green-600">{{ resultado.nombre || '(vacío)' }}</p>
                                    </div>
                                    
                                    <div class="bg-white p-4 rounded-lg">
                                        <span class="text-sm font-medium text-gray-600">Apellido:</span>
                                        <p class="text-xl font-bold text-blue-600">{{ resultado.apellido || '(vacío)' }}</p>
                                    </div>
                                </div>

                                <p v-if="resultado.fallback" class="text-sm text-yellow-700 italic mt-2">
                                    * La API de IA no está disponible. Se usó separación básica.
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Error -->
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                >
                    <div v-if="error" class="mt-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                        <p class="text-red-800">
                            <span class="font-semibold">Error:</span> {{ error }}
                        </p>
                    </div>
                </transition>
            </div>

            <!-- Ejemplos -->
            <div class="mt-8 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    📋 Ejemplos para probar:
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button
                        v-for="ejemplo in ejemplos"
                        :key="ejemplo"
                        @click="nombreCompleto = ejemplo"
                        class="text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition border border-gray-200 hover:border-green-500"
                    >
                        {{ ejemplo }}
                    </button>
                </div>
            </div>

            <!-- Historial -->
            <div v-if="historial.length > 0" class="mt-8 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    📊 Historial (últimos 5)
                </h3>
                <div class="space-y-3">
                    <div 
                        v-for="(item, index) in historial.slice(0, 5)"
                        :key="index"
                        class="p-4 bg-gray-50 rounded-lg"
                    >
                        <div class="text-sm text-gray-600">{{ item.original }}</div>
                        <div class="flex gap-4 mt-2 text-sm">
                            <span class="text-green-600">👤 {{ item.nombre }}</span>
                            <span class="text-blue-600">📋 {{ item.apellido }}</span>
                        </div>
                    </div>
                </div>
                <button
                    @click="historial = []"
                    class="mt-4 text-sm text-red-600 hover:text-red-800"
                >
                    Limpiar historial
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';

// Estado
const nombreCompleto = ref('');
const resultado = ref(null);
const error = ref(null);
const loading = ref(false);
const apiDisponible = ref(null);
const historial = ref([]);

// Ejemplos
const ejemplos = [
    'Juan Pérez García',
    'María José de la Cruz',
    'Carlos Alberto Rodríguez Méndez',
    'Ana de los Santos',
    'Miguel Ángel Díaz Flores',
    'José Luis Fernández'
];

// Computed
const statusClass = computed(() => {
    if (apiDisponible.value === null) {
        return 'bg-gray-100 text-gray-700';
    }
    return apiDisponible.value 
        ? 'bg-green-100 text-green-800' 
        : 'bg-yellow-100 text-yellow-800';
});

const statusMensaje = computed(() => {
    if (apiDisponible.value === null) {
        return '⏳ Verificando estado de la API...';
    }
    return apiDisponible.value 
        ? '✅ API de IA disponible y funcionando' 
        : '⚠️ API de IA no disponible (usando método de respaldo)';
});

// Métodos
const verificarEstado = async () => {
    try {
        const response = await axios.get('/nombres/status');
        apiDisponible.value = response.data.api_disponible;
    } catch (err) {
        apiDisponible.value = false;
        console.error('Error al verificar estado:', err);
    }
};

const separarNombre = async () => {
    if (!nombreCompleto.value.trim()) {
        error.value = 'Por favor ingresa un nombre completo';
        return;
    }

    loading.value = true;
    error.value = null;
    resultado.value = null;

    try {
        const response = await axios.post('/nombres/separar', {
            nombre_completo: nombreCompleto.value
        });

        resultado.value = response.data;

        // Agregar al historial
        historial.value.unshift({
            original: response.data.original,
            nombre: response.data.nombre,
            apellido: response.data.apellido
        });

    } catch (err) {
        error.value = err.response?.data?.message || 'Error al procesar el nombre';
        console.error('Error:', err);
    } finally {
        loading.value = false;
    }
};

// Lifecycle
onMounted(() => {
    verificarEstado();
});
</script>