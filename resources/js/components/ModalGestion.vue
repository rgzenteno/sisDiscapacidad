<script setup>
import { ref, onUnmounted, onMounted, computed } from 'vue'
import { can } from '@/lib/can';
import Dropdown from './Dropdown.vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}) // Cambiado de array a objeto
    },
    planilla_cargada: {
        type: Boolean,
        default: true
    },
    fecha_carga_planilla: {
        type: String,
        default: null
    }
});

//console.log('props.data en ModalGestion.vue:', props.data);

const emit = defineEmits(['close', 'cargar-planilla', 'recargar-planilla']);
const currentDateTime = ref('')
const dropdownOpen = ref(false);

// Función para formatear fecha y hora
const getFormattedDateTime = () => {
    const date = new Date()
    const day = date.getDate()
    const month = date.toLocaleString('es-ES', { month: 'long' })
    const year = date.getFullYear()
    const hours = date.getHours().toString().padStart(2, '0')
    const minutes = date.getMinutes().toString().padStart(2, '0')
    return `${day} de ${month} de ${year}, ${hours}:${minutes}`
}

// Actualizar la fecha y hora
const updateDateTime = () => {
    currentDateTime.value = getFormattedDateTime()
}

// Función para obtener solo mes y año actuales
const getCurrentMonthYear = () => {
    const date = new Date()
    return {
        month: date.getMonth() + 1, // getMonth() devuelve 0-11, sumamos 1
        year: date.getFullYear()
    }
}

// Comparación corregida
const verifyDate = () => {
    const current = getCurrentMonthYear()

    if (current.month === props.data.mes && current.year === props.data.gestion) {
        return true
    } else {
        return false
    }
}

const getCurrentDay = () => {
    return new Date().getDate()
}

// Devuelve el mes actual (1 - 12)
const getMonthFromGestion = () => {
    const today = new Date();
    return today.getMonth() + 1;
};

// Devuelve un array con los días del mes de props.data.mes
const getDateFin = () => {
    const year = props.data.gestion || new Date().getFullYear(); // Usa gestion si existe, sino el año actual
    const month = props.data.mes; // Mes recibido (1 - 12)

    // Último día del mes
    const lastDay = new Date(year, month, 0).getDate();

    return Array.from({ length: lastDay }, (_, i) => i + 1); // [1, 2, ..., lastDay]
};

function getFromDate(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Convertir a número entero
    const index = parseInt(monthNumber, 10) - 1;

    // Validar que esté en rango 0–11
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

function getMonthDateRange(monthNumber, year = new Date().getFullYear()) {
    const startDate = new Date(year, monthNumber - 1, 1); // primer día
    const endDate = new Date(year, monthNumber, 0); // último día

    const format = (date) => {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        return `${day}/${month}/${date.getFullYear()}`;
    };

    return `Del ${format(startDate)} al ${format(endDate)}`;
}

// Función para generar reporte (estaba faltando)
const generateReport = () => {
    console.log('Generando reporte...')
    // Aquí iría la lógica del reporte
}

const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
};

// Configurar intervalo al montar el componente
onMounted(() => {
    updateDateTime() // Establecer valor inicial
    const interval = setInterval(updateDateTime, 60000) // Actualizar cada minuto

    // Limpiar intervalo al desmontar
    onUnmounted(() => {
        clearInterval(interval)
    })
})

// Computed para calcular el total de beneficiarios
const getTotalBeneficiarios = computed(() => {
    return Number(props.data.personas_activos || 0) +
        Number(props.data.personas_baja_temporal || 0) +
        Number(props.data.personas_baja_definitiva || 0)
})

// Función para calcular porcentajes
const calculatePercentage = (value) => {
    const total = getTotalBeneficiarios.value
    if (total === 0) return '0.0'
    return ((Number(value || 0) / total) * 100).toFixed(1)
}

const tieneAcciones = computed(() => {
    return props.data.personas_sin_tutor > 0 ||
        props.data.sin_carnet_discapacidad > 0 ||
        props.data.carnet_vencidos > 0
})

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div
        class="fixed inset-0 bg-slate-900/75 flex items-start justify-center z-50 px-4 py-0 overflow-y-auto backdrop-blur-sm">
        <div
            class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl transform transition-all duration-300 my-8">
            <!-- Modal Header -->
            <div
                class="grid grid-cols-[1fr_auto] gap-6 px-6 py-3 border-b border-gray-100 bg-gray-50 dark:bg-gray-700/50 rounded-t-3xl">
                <!-- Contenido principal -->
                <div class="min-w-0">
                    <!-- Fila 1: Avatar/ícono y título -->
                    <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                        <!-- Avatar -->
                        <div class="p-2 rounded-lg" :class="verifyDate() ? 'bg-blue-200' : 'bg-gray-400'">
                            <svg class="w-6 h-6" :class="verifyDate() ? 'text-blue-600' : 'text-gray-600'"
                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Título y subtítulo -->
                        <div class="rounded-lg">
                            <p class="mt-0 text-2xl font-bold"
                                :class="verifyDate() ? 'text-blue-700 dark:text-white' : 'text-gray-500 dark:text-white'">
                                {{ getFromDate(props.data.mes) }}</p>
                            <p class="text-xs text-gray-600 dark:text-blue-300">{{ getMonthDateRange(props.data.mes) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex items-start gap-3 flex-shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- <div class="top-0 z-10 flex items-center justify-between px-6 py-2 border-b dark:border-gray-600/50 rounded-t-xl bg-gradient-to-r bg-gray-100 dark:bg-gray-900">
            <div class="flex items-center space-x-4">
                <div class="p-2 rounded-lg" :class="verifyDate() ? 'bg-blue-200' : 'bg-gray-400'">
                    <svg class="w-6 h-6" :class="verifyDate() ? 'text-blue-600' : 'text-gray-600'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="rounded-lg">
                    <p class="mt-0 text-2xl font-bold" :class="verifyDate() ? 'text-blue-700 dark:text-white' : 'text-gray-500 dark:text-white'">{{getFromDate(props.data.mes)}}</p>
                    <p class="text-xs text-gray-600 dark:text-blue-300">{{ getMonthDateRange(props.data.mes) }} </p>
                </div>
            </div>

            <button @click="$emit('close')" class="text-red-500 hover:text-red-700 bg-transparent hover:bg-white/10 rounded-lg p-2 transition-colors duration-200">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div> -->

            <!-- Modal Body -->
            <div class="p-4 space-y-4">
                <!-- Resumen de Gestión -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Monto Total -->
                    <div
                        class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">Monto Total Pagado</h3>
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z"
                                    clip-rule="evenodd" />
                                <path fill-rule="evenodd"
                                    d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z"
                                    clip-rule="evenodd" />
                                <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800 dark:text-white mb-3">Bs. {{
                            formatCurrency(props.data.total_pagado)}}</p>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Presupuesto:</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Bs. {{
                                    formatCurrency(props.data.presupuesto) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Presupuesto restante</span>
                                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Bs. {{
                                    formatCurrency((props.data.presupuesto || 0) - (props.data.total_pagado || 0))
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Beneficiarios Habilitados -->
                    <div
                        class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">Beneficiarios Habilitados
                            </h3>
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800 dark:text-white mb-3">{{
                            props.data.cantidad_habilitadas }}</p>
                        <div class="space-y-2">
                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{
                                props.data.porcentaje_habilitado }}% del total</span>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-indigo-600 dark:bg-indigo-400 h-2 rounded-full transition-all duration-300"
                                    :style="`width: ${props.data.porcentaje_habilitado}%`"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Beneficiarios Pagados -->
                    <div
                        class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-medium text-gray-800 dark:text-gray-200">Beneficiarios Pagados</h3>
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                    d="M8 7V6a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-1M3 18v-7a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800 dark:text-white mb-3">{{
                            props.data.cantidad_total_pagos }}</p>
                        <div class="space-y-2">
                            <span class="text-xs font-medium text-blue-600 dark:text-blue-400">{{
                                props.data.porcentaje_pagado }}% de habilitados</span>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full transition-all duration-300"
                                    :style="`width: ${props.data.porcentaje_pagado}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalle de Pagos -->
                <div
                    class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 mr-2" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-6 8a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                                clip-rule="evenodd" />
                        </svg>
                        Detalle de Pagos
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Información General -->
                        <div
                            class="bg-gray-50 border border-gray-200 dark:bg-gray-700/30 dark:border-gray-600 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3">Información General
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Beneficiarios totales</span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{
                                        props.data.total_personas }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Monto por beneficiario</span>
                                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Bs.
                                        {{ formatCurrency(props.data.monto) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Presupuesto mensual</span>
                                    <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Bs. {{
                                        formatCurrency(props.data.presupuesto) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Presupuesto anual</span>
                                    <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Bs. {{
                                        formatCurrency(props.data.presupuesto_anual) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de los Beneficiarios -->
                        <div
                            class="bg-gray-50 border border-gray-200 dark:bg-gray-700/30 dark:border-gray-600 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3">Estado de los
                                Beneficiarios</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Activos</span>
                                    <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                        {{ props.data.personas_activos }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{
                                            calculatePercentage(props.data.personas_activos) }}%)</span>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Baja Temporal</span>
                                    <span class="text-sm font-medium text-amber-600 dark:text-amber-400">
                                        {{ props.data.personas_baja_temporal }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{
                                            calculatePercentage(props.data.personas_baja_temporal) }}%)</span>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Baja Definitiva</span>
                                    <span class="text-sm font-medium text-red-600 dark:text-red-400">
                                        {{ props.data.personas_baja_definitiva }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{
                                            calculatePercentage(props.data.personas_baja_definitiva) }}%)</span>
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Total</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ getTotalBeneficiarios }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">(100%)</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de Proceso -->
                        <div
                            class="bg-gray-50 border border-gray-200 dark:bg-gray-700/30 dark:border-gray-600 p-4 rounded-lg">
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3"
                                    :class="verifyDate() ? 'text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'">
                                    {{ getFromDate(props.data.mes) }}
                                </div>
                                <div class="grid grid-cols-7 gap-1">
                                    <template v-for="day in getDateFin()" :key="day">
                                        <div class="w-6 h-6 flex items-center justify-center text-xs rounded-full"
                                            :class="{
                                                'bg-blue-500 text-white': verifyDate() && day === getCurrentDay(),
                                                'bg-gray-400 text-white': !verifyDate() && day === getDateFin()[getDateFin().length - 1],
                                                'text-gray-400 dark:text-gray-500': parseInt(day) > (verifyDate() ? getCurrentDay() : getDateFin()[getDateFin().length - 1]),
                                                'font-medium text-gray-700 dark:text-gray-300': parseInt(day) <= (verifyDate() ? getCurrentDay() : getDateFin()[getDateFin().length - 1]) && !((verifyDate() && day === getCurrentDay()) || (!verifyDate() && day === getDateFin()[getDateFin().length - 1]))
                                            }">
                                            {{ day }}
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Planilla Oficial -->
                <!-- <div class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 rounded-lg shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.221ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Z" clip-rule="evenodd"/>
        </svg>
        Planilla Oficial de {{ getFromDate(props.data.mes) }}
    </h3>

    <div v-if="props.planilla_cargada" class="text-center py-6">
        <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            Planilla Pendiente de Importación
        </h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Se han pre-habilitado {{ props.data.cantidad_habilitadas }} beneficiarios que cumplen los requisitos.<br>
            Importa la planilla oficial para confirmar las habilitaciones.
        </p>
        <button @click="$emit('cargar-planilla')" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-all duration-200 flex items-center mx-auto">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Cargar Planilla de {{ getFromDate(props.data.mes) }}
        </button>
    </div>

    <div v-else class="bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800 p-4 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h4 class="text-sm font-medium text-emerald-900 dark:text-emerald-100">
                    Planilla Oficial Cargada
                </h4>
                <div class="mt-2 text-sm text-emerald-700 dark:text-emerald-300">
                    <p>Fecha de carga: <span class="font-medium">{{ props.fecha_carga_planilla }}</span></p>
                    <p class="mt-1">Registros procesados: <span class="font-medium">{{ props.data.cantidad_habilitadas }}</span></p>
                </div>
                <div class="mt-3">
                    <button @click="$emit('recargar-planilla')" class="text-sm font-medium text-emerald-700 dark:text-emerald-300 hover:text-emerald-800 dark:hover:text-emerald-200">
                        🔄 Recargar planilla
                    </button>
                </div>
            </div>
        </div>
    </div>
</div> -->

                <!-- Beneficiarios con Situaciones Especiales -->
                <div
                    class="bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mr-2" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Beneficiarios con Situaciones Especiales
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            class="bg-gray-50 border border-gray-200 dark:bg-gray-700/30 dark:border-gray-600 p-4 rounded-lg">
                            <div class="space-y-3">
                                <!-- Documentación Pendiente -->
                                <div class="pb-2 border-b border-gray-200 dark:border-gray-600">
                                    <h5 class="text-sm font-medium text-gray-800 dark:text-gray-200">Documentación
                                        Pendiente</h5>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Sin tutor asignado</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                        props.data.personas_sin_tutor
                                        }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Sin Carnet de Discapacidad</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                        props.data.sin_carnet_discapacidad
                                        }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">Carnet de Discapacidad Vencido</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                        props.data.carnet_vencidos }}</span>
                                </div>

                                <div
                                    class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total</span>
                                    <span class="text-lg font-bold text-amber-600 dark:text-amber-400">
                                        {{
                                            Number(props.data.personas_sin_tutor || 0) +
                                            Number(props.data.sin_carnet_discapacidad || 0) +
                                            Number(props.data.carnet_vencidos || 0)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 border border-gray-200 dark:bg-gray-700/30 dark:border-gray-600 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3">Acciones Requeridas
                            </h4>

                            <!-- Mostrar lista si hay al menos una acción -->
                            <ul v-if="tieneAcciones" class="space-y-2 text-sm">
                                <li v-if="props.data.personas_sin_tutor > 0" class="flex items-center">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    <span class="text-gray-600 dark:text-gray-400">Asignar tutores a
                                        beneficiarios</span>
                                </li>
                                <li v-if="props.data.sin_carnet_discapacidad > 0" class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    <span class="text-gray-600 dark:text-gray-400">Actualizar carnet de
                                        discapacidad</span>
                                </li>
                                <li v-if="props.data.carnet_vencidos > 0" class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                    <span class="text-gray-600 dark:text-gray-400">Registrar carnet de
                                        discapacidad</span>
                                </li>
                            </ul>

                            <!-- Mostrar mensaje cuando no hay acciones -->
                            <div v-else class="text-center">
                                <div
                                    class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                    No existen acciones requeridas
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Todas las tareas están completadas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="sticky bottom-0 flex justify-between items-center px-6 py-4 border-t border-gray-200 dark:border-gray-700 rounded-b-xl bg-gray-50 dark:bg-gray-800/50 backdrop-blur-sm">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">Última actualización:</span>
                        <span class="font-medium">{{ currentDateTime }}</span>
                    </div>
                    <div class="flex space-x-3 items-center">
                        <!-- Dropdown de Reportes -->
                        <div v-if="can('reporte-mes')" class="relative">
                            <button @click="dropdownOpen = !dropdownOpen" type="button"
                                class="inline-flex items-center justify-between gap-3 px-5 py-2 text-sm font-medium rounded-lg transition-all focus:ring-2 focus:outline-none bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-blue-400 dark:focus:ring-blue-500 cursor-pointer">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="truncate">Generar Reporte</span>
                                <svg class="w-2.5 h-2.5 flex-shrink-0 transition-transform"
                                    :class="{ 'rotate-180': dropdownOpen }" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </button>

                            <!-- Overlay -->
                            <div v-show="dropdownOpen" @click="dropdownOpen = false" class="fixed inset-0 z-40"></div>

                            <!-- Dropdown Menu (hacia arriba) -->
                            <Transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <div v-show="dropdownOpen" class="absolute bottom-full right-0 mb-2 w-60 z-50">
                                    <div
                                        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-xl">
                                        <div class="max-h-64 overflow-y-auto">
                                            <ul class="py-1">
                                                <li>
                                                    <button type="button"
                                                        @click="generarReporte('pagados'); dropdownOpen = false"
                                                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left transition-colors duration-150 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <svg class="w-5 h-5 text-red-600"
                                                            xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Reporte Pagados</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button"
                                                        @click="generarReporte('no-pagados'); dropdownOpen = false"
                                                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left transition-colors duration-150 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <svg class="w-5 h-5 text-red-600"
                                                            xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Reporte No Pagados</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button"
                                                        @click="generarReporte('fallecidos'); dropdownOpen = false"
                                                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left transition-colors duration-150 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <svg class="w-5 h-5 text-red-600"
                                                            xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Reporte Fallecidos</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button"
                                                        @click="generarReporte('funcionarios'); dropdownOpen = false"
                                                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left transition-colors duration-150 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <svg class="w-5 h-5 text-red-600"
                                                            xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Reporte Funcionarios</span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Botón Aceptar -->
                        <button @click="$emit('close')"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 dark:focus:ring-blue-500 flex items-center">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
