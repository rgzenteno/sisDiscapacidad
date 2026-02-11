<template>
<div v-if="modelValue && datosValidos" class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-50 px-4 py-2 overflow-y-auto backdrop-blur-sm">
    <div class="relative w-full max-w-3xl max-h-[90vh] my-4 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200/30 dark:border-gray-700/30 transform transition-all duration-300 flex flex-col">

        <!-- Modal Header -->
        <div class="grid grid-cols-[1fr_auto] gap-6 px-6 pt-4 pb-4 border-b border-gray-100 dark:border-gray-600/50 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 sticky top-0 z-10 rounded-t-3xl">
            <div class="min-w-0">
                <div class="grid grid-cols-[auto_1fr] gap-4 items-center mb-2">
                    <!-- Avatar / Ícono -->
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Z" />
                            <path fill-rule="evenodd" d="M11 7V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm4.707 5.707a1 1 0 0 0-1.414-1.414L11 14.586l-1.293-1.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight truncate">
                            Resultados de Importación
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-blue-300 truncate">
                            Proceso completado con éxito
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botón cerrar -->
            <div class="flex items-start gap-3 flex-shrink-0">
                <button type="button" @click="$emit('close')" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body con scroll -->
        <div class="p-6 overflow-y-auto flex-1 space-y-5">

            <!-- Estado general de la importación -->
            <div class="p-5 rounded-xl border-2 shadow-lg" :class="{
                    'bg-gradient-to-r from-emerald-50 to-green-50 border-emerald-200 dark:from-emerald-900/20 dark:to-green-900/20 dark:border-emerald-700/50': resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0,
                    'bg-gradient-to-r from-amber-50 to-yellow-50 border-amber-200 dark:from-amber-900/20 dark:to-yellow-900/20 dark:border-amber-700/50': resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0,
                    'bg-gradient-to-r from-red-50 to-rose-50 border-red-200 dark:from-red-900/20 dark:to-rose-900/20 dark:border-red-700/50': resultadoImportacion.insertados.length === 0 && resultadoImportacion.errores.length > 0
                }">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-xl mr-4 shadow-md" :class="{
                            'bg-emerald-100 dark:bg-emerald-800/50': resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0,
                            'bg-amber-100 dark:bg-amber-800/50': resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0,
                            'bg-red-100 dark:bg-red-800/50': resultadoImportacion.insertados.length === 0 && resultadoImportacion.errores.length > 0
                        }">
                        <i class="text-xl" :class="{
                                'fas fa-check-circle text-emerald-600 dark:text-emerald-400': resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0,
                                'fas fa-exclamation-triangle text-amber-600 dark:text-amber-400': resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0,
                                'fas fa-times-circle text-red-600 dark:text-red-400': resultadoImportacion.insertados.length === 0 && resultadoImportacion.errores.length > 0
                            }"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold mb-1" :class="{
                                'text-emerald-800 dark:text-emerald-200': resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0,
                                'text-amber-800 dark:text-amber-200': resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0,
                                'text-red-800 dark:text-red-200': resultadoImportacion.insertados.length === 0 && resultadoImportacion.errores.length > 0
                            }">
                            <span v-if="resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0">
                                ✨ Importación Completada Exitosamente
                            </span>
                            <span v-else-if="resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0">
                                ⚠️ Importación Completada con Observaciones
                            </span>
                            <span v-else>
                                ❌ Importación Fallida
                            </span>
                        </h3>
                        <p class="text-sm" :class="{
                                'text-emerald-700 dark:text-emerald-300': resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0,
                                'text-amber-700 dark:text-amber-300': resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0,
                                'text-red-700 dark:text-red-300': resultadoImportacion.insertados.length === 0 && resultadoImportacion.errores.length > 0
                            }">
                            <span v-if="resultadoImportacion.insertados.length > 0 && resultadoImportacion.errores.length === 0">
                                Todos los registros fueron procesados correctamente sin problemas
                            </span>
                            <span v-else-if="resultadoImportacion.errores.length > 0 || resultadoImportacion.duplicados.length > 0">
                                El proceso finalizó pero se encontraron algunos registros con problemas
                            </span>
                            <span v-else>
                                No se pudo completar la importación debido a errores críticos
                            </span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
                    <!-- Total -->
                    <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-4 rounded-xl shadow-md border border-white/50 dark:border-gray-700/50 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Total</p>
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <i class="fas fa-database text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200">
                            {{ resultadoImportacion.total_procesado }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">registros procesados</p>
                    </div>

                    <!-- Insertados -->
                    <div v-if="resultadoImportacion.insertados.length > 0" class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-4 rounded-xl shadow-md border border-white/50 dark:border-gray-700/50 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Nuevos</p>
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                <i class="fas fa-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ resultadoImportacion.insertados.length }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">insertados correctamente</p>
                    </div>

                    <!-- Duplicados -->
                    <div v-if="resultadoImportacion.duplicados.length > 0" class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-4 rounded-xl shadow-md border border-white/50 dark:border-gray-700/50 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Duplicados</p>
                            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                <i class="fas fa-copy text-amber-600 dark:text-amber-400 text-sm"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                            {{ resultadoImportacion.duplicados.length }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">registros ya existentes</p>
                    </div>

                    <!-- Errores -->
                    <div v-if="resultadoImportacion.errores.length > 0" class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-4 rounded-xl shadow-md border border-white/50 dark:border-gray-700/50 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Errores</p>
                            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-sm"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400">
                            {{ resultadoImportacion.errores.length }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">con problemas</p>
                    </div>
                </div>
            </div>

            <!-- Mensaje informativo adicional -->
            <div v-if="resultadoImportacion.insertados.length > 0" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-l-4 border-blue-500 dark:border-blue-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-800/50 rounded-lg flex-shrink-0">
                        <i class="fas fa-lightbulb text-blue-600 dark:text-blue-400 text-base"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-blue-900 dark:text-blue-200 mb-1">💡 Información Importante</h4>
                        <p class="text-sm text-blue-800 dark:text-blue-300 leading-relaxed">
                            Los <strong>{{ resultadoImportacion.insertados.length }} nuevos registros</strong> fueron agregados exitosamente a la base de datos.

                        </p>
                    </div>
                </div>
            </div>

            <!-- Insertados -->
            <div v-if="resultadoImportacion.insertados.length > 0">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg">
                    <div @click="mostrarInsertados = !mostrarInsertados" class="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 px-4 py-2 border-b border-emerald-200 dark:border-emerald-800/50 cursor-pointer hover:from-emerald-100 hover:to-green-100 dark:hover:from-emerald-900/30 dark:hover:to-green-900/30 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-emerald-100 dark:bg-emerald-800/50 rounded-lg">
                                    <i class="fas fa-check-double text-emerald-600 dark:text-emerald-400 text-base"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-emerald-900 dark:text-emerald-200">Registros Insertados Exitosamente</h4>
                                    <p class="text-xs text-emerald-700 dark:text-emerald-400">Nuevos beneficiarios agregados al sistema</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-800/50 px-3 py-1.5 rounded-full shadow-sm">
                                    {{ resultadoImportacion.insertados.length }} registros
                                </span>
                                <svg :class="{'rotate-180': mostrarInsertados}" class="w-6 h-6 text-emerald-600 dark:text-emerald-400 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div v-show="mostrarInsertados" class="px-4 py-2 max-h-72 overflow-y-auto bg-gradient-to-b from-emerald-50/50 to-white dark:from-emerald-900/10 dark:to-gray-800">
                        <ul class="space-y-2">
                            <li v-for="item in resultadoImportacion.insertados" :key="item.fila" class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-emerald-400 dark:border-emerald-600 shadow-sm hover:shadow-md transition-shadow">
                                <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex-shrink-0">
                                    <i class="fas fa-user-check text-emerald-600 dark:text-emerald-400 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 px-2 py-0.5 rounded">
                                            Fila {{ item.fila }}
                                        </span>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ item.ci }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ item.nombre }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Duplicados -->
            <div v-if="resultadoImportacion.duplicados.length > 0">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg">
                    <div @click="mostrarDuplicados = !mostrarDuplicados" class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 px-4 py-2 border-b border-amber-200 dark:border-amber-800/50 cursor-pointer hover:from-amber-100 hover:to-yellow-100 dark:hover:from-amber-900/30 dark:hover:to-yellow-900/30 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-amber-100 dark:bg-amber-800/50 rounded-lg">
                                    <i class="fas fa-exclamation-circle text-amber-600 dark:text-amber-400 text-base"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-amber-900 dark:text-amber-200">Registros Duplicados (Omitidos)</h4>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">Estos C.I. ya estan registrados en el sistema</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-800/50 px-3 py-1.5 rounded-full shadow-sm">
                                    {{ resultadoImportacion.duplicados.length }} registros
                                </span>
                                <svg :class="{'rotate-180': mostrarDuplicados}" class="w-6 h-6 text-amber-600 dark:text-amber-400 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div v-show="mostrarDuplicados" class="px-4 py-2 max-h-72 overflow-y-auto bg-gradient-to-b from-amber-50/50 to-white dark:from-amber-900/10 dark:to-gray-800">
                        <ul class="space-y-2">
                            <li v-for="item in resultadoImportacion.duplicados" :key="item.fila" class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-amber-400 dark:border-amber-600 shadow-sm hover:shadow-md transition-shadow">
                                <div class="p-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex-shrink-0">
                                    <i class="fas fa-clone text-amber-600 dark:text-amber-400 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded">
                                            Fila {{ item.fila }}
                                        </span>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ item.ci }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ item.nombre }}</p>
                                    <!-- <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">{{ item.motivo }}</p> -->
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Errores -->
            <div v-if="resultadoImportacion.errores.length > 0">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg">
                    <div @click="mostrarErrores = !mostrarErrores" class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 px-4 py-2 border-b border-red-200 dark:border-red-800/50 cursor-pointer hover:from-red-100 hover:to-rose-100 dark:hover:from-red-900/30 dark:hover:to-rose-900/30 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-red-100 dark:bg-red-800/50 rounded-lg">
                                    <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-base"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-red-900 dark:text-red-200">Errores Detectados</h4>
                                    <p class="text-xs text-red-700 dark:text-red-400">Registros que no pudieron procesarse</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-800/50 px-3 py-1.5 rounded-full shadow-sm">
                                    {{ resultadoImportacion.errores.length }} registros
                                </span>
                                <svg :class="{'rotate-180': mostrarErrores}" class="w-6 h-6 text-red-600 dark:text-red-400 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div v-show="mostrarErrores" class="px-4 py-2 max-h-72 overflow-y-auto bg-gradient-to-b from-red-50/50 to-white dark:from-red-900/10 dark:to-gray-800">
                        <ul class="space-y-3">
                            <li v-for="item in resultadoImportacion.errores" :key="item.fila" class="p-4 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-red-400 dark:border-red-600 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-start gap-3">
                                    <div class="p-1.5 bg-red-100 dark:bg-red-900/30 rounded-lg flex-shrink-0">
                                        <i class="fas fa-bug text-red-600 dark:text-red-400 text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30 px-2 py-0.5 rounded">
                                                Fila {{ item.fila }}
                                            </span>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ item.ci }}</span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">{{ item.nombre }}</p>
                                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-2 border border-red-200 dark:border-red-800/50">
                                            <p class="text-xs text-red-700 dark:text-red-300">
                                                <span class="font-semibold">Motivo:</span> {{ item.motivo }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 rounded-b-3xl bg-gradient-to-r from-slate-50 to-gray-50 dark:from-gray-800 dark:to-gray-700/50 sticky bottom-0">
            <Button @click="cerrar" class="text-white bg-blue-600 hover:bg-blue-500">
                Aceptar
            </Button>
        </div>
    </div>
</div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Button from './Button.vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true
    },
    resultadoImportacion: {
        type: Object,
        default: () => ({
            insertados: [],
            duplicados: [],
            errores: [],
            total_procesado: 0
        })
    }
});

const emit = defineEmits(['update:modelValue']);

// ✅ Computed para asegurar datos válidos
const datosValidos = computed(() => {
    return props.resultadoImportacion &&
        typeof props.resultadoImportacion === 'object' &&
        Array.isArray(props.resultadoImportacion.insertados);
});

const mostrarInsertados = ref(false);
const mostrarDuplicados = ref(false);
const mostrarErrores = ref(false);

const cerrar = () => {
    emit('update:modelValue', false);
};
</script>
