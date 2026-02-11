<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    data: Array,
});

const resultadosImportacion = ref(null);
const emit = defineEmits(['close']);

// Computed para estadísticas más legibles
const estadisticas = computed(() => {
    if (!resultadosImportacion.value) return null;

    const resultado = resultadosImportacion.value;
    return {
        total: resultado.total || 0,
        insertados: resultado.insertados || 0,
        duplicados: resultado.duplicados || 0,
        errores: resultado.errores ?.length || 0,
        tieneErrores: (resultado.errores ?.length || 0) > 0,
        esExitoso: resultado.insertados === resultado.total,
        esParcial: resultado.insertados > 0 && resultado.insertados < resultado.total,
        esFallido: resultado.insertados === 0
    };
});

</script>

<template>
<!-- Modal de resultados de importación -->
<div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 px-4 py-2 overflow-y-auto backdrop-blur-sm">
    <div class="relative w-full max-w-2xl max-h-[90vh] my-4 bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200/30 dark:border-gray-700/30 transform transition-all duration-300 overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700/50 rounded-t-xl bg-gradient-to-r from-slate-50 to-gray-50 dark:from-gray-800 dark:to-gray-700/50 sticky top-0 z-10">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md">
                    <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5h7.586l-.293.293a1 1 0 0 0 1.414 1.414l2-2a1 1 0 0 0 0-1.414l-2-2a1 1 0 0 0-1.414 1.414l.293.293H4V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd" />
                    </svg>

                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Resultados de Importación</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Resumen del proceso</p>
                </div>
            </div>

            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-2 transition-all duration-200 shadow-sm border border-gray-200 dark:border-gray-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 py-0 pt-2">
            <!-- Estado general de la importación -->
            <div class="mb-5 p-4 rounded-lg border-2 shadow-sm" :class="{
                'bg-gradient-to-r from-emerald-50 to-green-50 border-emerald-200 dark:from-emerald-900/20 dark:to-green-900/20 dark:border-emerald-700/50': estadisticas.esExitoso,
                'bg-gradient-to-r from-amber-50 to-yellow-50 border-amber-200 dark:from-amber-900/20 dark:to-yellow-900/20 dark:border-amber-700/50': estadisticas.esParcial,
                'bg-gradient-to-r from-red-50 to-rose-50 border-red-200 dark:from-red-900/20 dark:to-rose-900/20 dark:border-red-700/50': estadisticas.esFallido
            }">
                <div class="flex items-center mb-3">
                    <div class="p-2 rounded-lg mr-3" :class="{
                        'bg-emerald-200 dark:bg-emerald-800/50': estadisticas.esExitoso,
                        'bg-amber-200 dark:bg-amber-800/50': estadisticas.esParcial,
                        'bg-red-200 dark:bg-red-800/50': estadisticas.esFallido
                    }">
                        <i class="text-base" :class="{
                            'fas fa-check-circle text-emerald-600 dark:text-emerald-400': estadisticas.esExitoso,
                            'fas fa-exclamation-triangle text-amber-600 dark:text-amber-400': estadisticas.esParcial,
                            'fas fa-times-circle text-red-600 dark:text-red-400': estadisticas.esFallido
                        }"></i>
                    </div>
                    <h3 class="text-base font-semibold" :class="{
                        'text-emerald-800 dark:text-emerald-200': estadisticas.esExitoso,
                        'text-amber-800 dark:text-amber-200': estadisticas.esParcial,
                        'text-red-800 dark:text-red-200': estadisticas.esFallido
                    }">
                        <span v-if="estadisticas.esExitoso">Importación Completada Exitosamente</span>
                        <span v-else-if="estadisticas.esParcial">Importación Completada con Observaciones</span>
                        <span v-else>Importación Fallida</span>
                    </h3>
                </div>

                <!-- Estadísticas en tarjetas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Total</p>
                            <i class="fas fa-database text-gray-400 text-xs"></i>
                        </div>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-200">
                            {{ estadisticas.total }}
                        </p>
                    </div>

                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Insertados</p>
                            <i class="fas fa-check text-emerald-500 text-xs"></i>
                        </div>
                        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ estadisticas.insertados }}
                        </p>
                    </div>

                    <div v-if="estadisticas.duplicados > 0" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Duplicados</p>
                            <i class="fas fa-copy text-blue-500 text-xs"></i>
                        </div>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                            {{ estadisticas.duplicados }}
                        </p>
                    </div>

                    <div v-if="estadisticas.errores > 0" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Errores</p>
                            <i class="fas fa-exclamation-triangle text-red-500 text-xs"></i>
                        </div>
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">
                            {{ estadisticas.errores }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Lista de errores detallada -->
            <div v-if="estadisticas.tieneErrores && resultadosImportacion.errores && resultadosImportacion.errores.length > 0" class="mb-5">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="bg-red-50 dark:bg-red-900/20 px-4 py-3 border-b border-red-200 dark:border-red-800/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-sm"></i>
                                <h4 class="text-sm font-semibold text-red-800 dark:text-red-200">Errores Detallados</h4>
                            </div>
                            <span class="text-xs text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-800/50 px-2 py-1 rounded-full font-medium">
                                {{ estadisticas.errores }} error{{ estadisticas.errores !== 1 ? 'es' : '' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 max-h-48 overflow-y-auto">
                        <ul class="space-y-2">
                            <li v-for="(error, index) in resultadosImportacion.errores" :key="index" class="flex items-start gap-2 p-2 bg-red-25 dark:bg-red-900/10 rounded border-l-3 border-red-300 dark:border-red-700">
                                <i class="fas fa-times-circle text-red-500 text-xs mt-1 flex-shrink-0"></i>
                                <span class="text-xs text-red-700 dark:text-red-300">{{ error }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Mensaje informativo (no de error) -->
            <div v-if="resultadosImportacion.message" class="mb-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg overflow-hidden shadow-sm">
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-blue-100 dark:bg-blue-800/50 rounded-lg">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-1">Resumen del Proceso</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 leading-relaxed">{{ resultadosImportacion.message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 rounded-b-xl bg-gradient-to-r from-slate-50 to-gray-50 dark:from-gray-800 dark:to-gray-700/50 sticky bottom-0">
            <button @click="$emit('close')" class="px-6 py-2.5 text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg text-sm font-medium transition-all duration-200 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-check mr-2"></i>
                Entendido
            </button>
        </div>
    </div>
</div>
</template>
