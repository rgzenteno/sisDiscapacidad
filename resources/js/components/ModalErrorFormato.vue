<script setup>
const props = defineProps({
    errorData: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])

const cerrar = () => {
    emit('close')
}
</script>

<template>
<div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-5xl max-h-[95vh] bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-red-200 dark:border-red-700/30 transform transition-all duration-300 flex flex-col">
        
        <!-- Header -->
        <div class="px-6 pt-6 pb-4 border-b border-red-100 dark:border-red-800/50 bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-t-3xl flex-shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-gradient-to-br from-red-500 to-rose-600 shadow-lg ring-2 ring-red-100 flex-shrink-0">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold text-red-900 dark:text-red-200">
                        ⚠️ Formato Inválido
                    </h2>
                    <p class="text-sm text-red-700 dark:text-red-300">
                        El archivo no cumple con el formato esperado
                    </p>
                </div>
                <button @click="cerrar" class="p-2.5 rounded-full bg-white shadow-lg hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition-all hover:scale-110">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body con scroll -->
        <div class="p-6 overflow-y-auto flex-1 space-y-6">
            
            <!-- Mensaje principal -->
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-400 rounded-lg p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="p-2 bg-red-100 dark:bg-red-800/50 rounded-lg flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-red-900 dark:text-red-200 mb-2">
                            {{ errorData.mensaje }}
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-300 leading-relaxed">
                            Por favor, asegúrese de que su archivo Excel contenga las siguientes columnas en el orden correcto.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Comparación lado a lado -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Columnas esperadas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border-2 border-green-200 dark:border-green-700 overflow-hidden shadow-lg">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 px-5 py-4 border-b-2 border-green-200 dark:border-green-800/50">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 dark:bg-green-800/50 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-green-900 dark:text-green-200">✅ Formato Correcto</h4>
                                <p class="text-xs text-green-700 dark:text-green-400">Columnas que debe contener</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 max-h-96 overflow-y-auto bg-gradient-to-b from-green-50/30 to-white dark:from-green-900/10 dark:to-gray-800">
                        <ol class="space-y-3">
                            <li v-for="(col, index) in errorData.esperados" :key="`esperado-${index}`" 
                                class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-l-4 border-green-400 dark:border-green-600 shadow-sm">
                                <span class="flex-shrink-0 w-8 h-8 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-bold shadow-sm">
                                    {{ index + 1 }}
                                </span>
                                <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ col }}</span>
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Columnas encontradas -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border-2 border-red-200 dark:border-red-700 overflow-hidden shadow-lg">
                    <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 px-5 py-4 border-b-2 border-red-200 dark:border-red-800/50">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-red-100 dark:bg-red-800/50 rounded-lg">
                                <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-red-900 dark:text-red-200">❌ Columnas Encontradas</h4>
                                <p class="text-xs text-red-700 dark:text-red-400">En su archivo Excel</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 max-h-96 overflow-y-auto bg-gradient-to-b from-red-50/30 to-white dark:from-red-900/10 dark:to-gray-800">
                        <ol class="space-y-3">
                            <li v-for="(col, index) in errorData.recibidos" :key="`recibido-${index}`" 
                                class="flex items-center gap-3 p-3 rounded-lg border-l-4 shadow-sm"
                                :class="col === errorData.esperados[index] 
                                    ? 'bg-green-50 dark:bg-green-900/20 border-green-400 dark:border-green-600' 
                                    : 'bg-red-50 dark:bg-red-900/20 border-red-400 dark:border-red-600'">
                                <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-sm"
                                    :class="col === errorData.esperados[index] 
                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' 
                                        : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                                    {{ index + 1 }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <span class="font-semibold text-sm block truncate"
                                        :class="col === errorData.esperados[index] 
                                            ? 'text-green-700 dark:text-green-300' 
                                            : 'text-red-700 dark:text-red-300'">
                                        {{ col || '(vacío)' }}
                                    </span>
                                    <span v-if="col !== errorData.esperados[index]" class="text-xs text-red-600 dark:text-red-400 mt-1 block">
                                        Esperado: {{ errorData.esperados[index] }}
                                    </span>
                                </div>
                                <i v-if="col === errorData.esperados[index]" class="fas fa-check text-green-600 dark:text-green-400"></i>
                                <i v-else class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </li>
                        </ol>
                    </div>
                </div>

            </div>

            <!-- Instrucciones -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-l-4 border-blue-500 dark:border-blue-400 rounded-lg p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="p-2 bg-blue-100 dark:bg-blue-800/50 rounded-lg flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-base font-bold text-blue-900 dark:text-blue-200 mb-3">📝 Instrucciones para corregir:</h4>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-2 text-sm text-blue-800 dark:text-blue-300">
                                <i class="fas fa-arrow-right text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <span>Verifique que la <strong>primera fila</strong> contenga los encabezados correctos</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-blue-800 dark:text-blue-300">
                                <i class="fas fa-arrow-right text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <span>Las columnas deben estar en el <strong>orden exacto</strong> indicado arriba</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-blue-800 dark:text-blue-300">
                                <i class="fas fa-arrow-right text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <span><strong>No agregue ni elimine</strong> columnas del formato original</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-blue-800 dark:text-blue-300">
                                <i class="fas fa-arrow-right text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <span>Descargue una <strong>plantilla de ejemplo</strong> si es necesario</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="flex justify-between items-center gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 rounded-b-3xl bg-gradient-to-r from-slate-50 to-gray-50 dark:from-gray-800 dark:to-gray-700/50 flex-shrink-0">
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-lightbulb text-yellow-500"></i>
                <span>Corrija el formato y vuelva a intentar</span>
            </div>
            <button @click="cerrar" 
                class="px-8 py-3 text-white bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 rounded-xl text-sm font-bold transition-all duration-200 focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-times-circle mr-2"></i>
                Cerrar
            </button>
        </div>
    </div>
</div>
</template>