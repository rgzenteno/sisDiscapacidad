<template>
    <div v-if="modelValue && datosValidos"
        class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-40 px-4 py-2 overflow-y-auto backdrop-blur-sm">
        <div
            class="relative w-full max-w-4xl max-h-[90vh] my-4 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200/30 dark:border-gray-700/30 flex flex-col">

            <!-- Header -->
            <div
                class="px-6 pt-4 pb-4 border-b border-gray-100 dark:border-gray-600/50 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-t-3xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500 to-cyan-400 shadow-md">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd"
                                    d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Resultados del Procesamiento
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-blue-300">Resumen de personas procesadas</p>
                        </div>
                    </div>
                    <button @click="$emit('close')"
                        class="p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1 space-y-5">

                <!-- Resumen -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Total -->
                    <div
                        class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-xl border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                            <span class="text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase">Total</span>
                        </div>
                        <p class="text-3xl font-bold text-blue-900 dark:text-blue-200">{{ datos.total_procesado || 0 }}
                        </p>
                    </div>

                    <!-- Insertados -->
                    <div
                        class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-800/20 p-4 rounded-xl border border-green-200 dark:border-green-700">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                            </svg>
                            <span
                                class="text-xs font-semibold text-green-700 dark:text-green-300 uppercase">Nuevos</span>
                        </div>
                        <p class="text-3xl font-bold text-green-900 dark:text-green-200">{{ datos.insertados?.length ||
                            0 }}</p>
                    </div>

                    <!-- Habilitados -->
                    <div
                        class="bg-gradient-to-br from-cyan-50 to-sky-100 dark:from-cyan-900/20 dark:to-sky-800/20 p-4 rounded-xl border border-cyan-200 dark:border-cyan-700">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span
                                class="text-xs font-semibold text-cyan-700 dark:text-cyan-300 uppercase">Habilitados</span>
                        </div>
                        <p class="text-3xl font-bold text-cyan-900 dark:text-cyan-200">{{ datos.habilitados?.length || 0
                        }}</p>
                    </div>

                    <!-- Bajas -->
                    <div
                        class="bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-800/20 p-4 rounded-xl border border-amber-200 dark:border-amber-700">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span
                                class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase">Bajas</span>
                        </div>
                        <p class="text-3xl font-bold text-amber-900 dark:text-amber-200">{{ totalBajas }}</p>
                    </div>
                </div>

                <!-- Habilitados -->
                <div v-if="datos.habilitados?.length > 0"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarHabilitados = !mostrarHabilitados"
                        class="bg-gradient-to-r from-cyan-50 to-sky-50 dark:from-cyan-900/20 dark:to-sky-900/20 px-4 py-3 cursor-pointer hover:from-cyan-100 hover:to-sky-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-cyan-100 dark:bg-cyan-800/50 rounded-lg">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-cyan-900 dark:text-cyan-200">Personas Habilitadas</h4>
                                    <p class="text-xs text-cyan-700 dark:text-cyan-400">Beneficiarios activos para pago
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-cyan-700 dark:text-cyan-300 bg-cyan-100 dark:bg-cyan-800/50 px-3 py-1 rounded-full">
                                    {{ datos.habilitados.length }} registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarHabilitados }"
                                    class="w-6 h-6 text-cyan-600 dark:text-cyan-400 transition-transform" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarHabilitados"
                        class="px-4 py-3 max-h-60 overflow-y-auto bg-gradient-to-b from-cyan-50/30 to-white dark:from-cyan-900/10 dark:to-gray-800">
                        <ul class="space-y-2">
                            <li v-for="item in datos.habilitados" :key="item.fila"
                                class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-cyan-400">
                                <div class="p-1.5 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="text-xs font-bold text-cyan-700 dark:text-cyan-400 bg-cyan-100 dark:bg-cyan-900/30 px-2 py-0.5 rounded">Fila
                                            {{ item.fila }}</span>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ item.ci
                                        }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ item.nombre }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bajas Temporales -->
                <div v-if="datos.bajas_temporales?.length > 0"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarBajasTemporales = !mostrarBajasTemporales"
                        class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 px-4 py-3 cursor-pointer hover:from-yellow-100 hover:to-amber-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-yellow-100 dark:bg-yellow-800/50 rounded-lg">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-yellow-900 dark:text-yellow-200">Bajas Temporales</h4>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-400">Beneficiarios con baja
                                        temporal</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-800/50 px-3 py-1 rounded-full">
                                    {{ datos.bajas_temporales.length }} registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarBajasTemporales }"
                                    class="w-6 h-6 text-yellow-600 dark:text-yellow-400 transition-transform"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarBajasTemporales"
                        class="px-4 py-3 max-h-60 overflow-y-auto bg-gradient-to-b from-yellow-50/30 to-white dark:from-yellow-900/10 dark:to-gray-800">
                        <ul class="space-y-2">
                            <li v-for="item in datos.bajas_temporales" :key="item.fila"
                                class="p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-yellow-400">
                                <div class="flex items-start gap-3">
                                    <div class="p-1.5 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                        <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span
                                                class="text-xs font-bold text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 rounded">Fila
                                                {{ item.fila }}</span>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{
                                                item.ci }}</span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">{{
                                            item.nombre }}</p>
                                        <p
                                            class="text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 px-2 py-1 rounded">
                                            {{ item.motivo }}</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bajas Definitivas -->
                <div v-if="datos.bajas_definitivas?.length > 0"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarBajasDefinitivas = !mostrarBajasDefinitivas"
                        class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 px-4 py-3 cursor-pointer hover:from-red-100 hover:to-rose-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-red-100 dark:bg-red-800/50 rounded-lg">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900 dark:text-red-200">Bajas Definitivas</h4>
                                    <p class="text-xs text-red-700 dark:text-red-400">Beneficiarios con baja definitiva
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-800/50 px-3 py-1 rounded-full">
                                    {{ datos.bajas_definitivas.length }} registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarBajasDefinitivas }"
                                    class="w-6 h-6 text-red-600 dark:text-red-400 transition-transform" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarBajasDefinitivas"
                        class="px-4 py-3 max-h-60 overflow-y-auto bg-gradient-to-b from-red-50/30 to-white dark:from-red-900/10 dark:to-gray-800">
                        <ul class="space-y-2">
                            <li v-for="item in datos.bajas_definitivas" :key="item.fila"
                                class="p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-red-400">
                                <div class="flex items-start gap-3">
                                    <div class="p-1.5 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span
                                                class="text-xs font-bold text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30 px-2 py-0.5 rounded">Fila
                                                {{ item.fila }}</span>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{
                                                item.ci }}</span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">{{
                                            item.nombre }}</p>
                                        <p
                                            class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded">
                                            {{ item.motivo }}</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div
                class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 rounded-b-3xl bg-gradient-to-r from-slate-50 to-gray-50 dark:from-gray-800 dark:to-gray-700/50">
                <Button @click="$emit('close')" class="text-white bg-blue-600 hover:bg-blue-500">
                    Confirmar
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Button from './Button.vue';

const props = defineProps({
    modelValue: Boolean,
    datos: {
        type: Object,
        default: () => ({})
    }
});
const emit = defineEmits(['update:modelValue', 'close']);

const datosValidos = computed(() => {
    return props.datos && typeof props.datos === 'object';
});

const totalBajas = computed(() => {
    return (props.datos.bajas_temporales?.length || 0) + (props.datos.bajas_definitivas?.length || 0);
});

const mostrarHabilitados = ref(false);
const mostrarBajasTemporales = ref(false);
const mostrarBajasDefinitivas = ref(false);
</script>
