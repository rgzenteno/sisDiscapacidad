<script setup>
// ============ INICIO PROPS ============ //
const props = defineProps({
    fileName: {
        type: String,
        required: true
    },
    file: {
        type: File,
        default: null
    },
    validation: {
        type: Object,
        default: () => ({
            isValid: null,
            message: '',
            missingColumns: [],
            foundColumns: []
        })
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['remove']);
// ============ FIN EMITS ============ //
</script>

<template>
    <div v-if="file" class="w-full">
        <div class="inline-flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-700 rounded-lg border shadow-sm w-full"
            :class="validation.isValid === true
                ? 'border-green-300 dark:border-green-600'
                : validation.isValid === false
                    ? 'border-red-300 dark:border-red-600'
                    : 'border-gray-200 dark:border-gray-600'">
            <!-- Icono de Excel con estado -->
            <div class="relative flex-shrink-0">
                <svg class="w-10 h-10" :class="validation.isValid === true
                    ? 'text-green-600'
                    : validation.isValid === false
                        ? 'text-red-600'
                        : 'text-gray-600'" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" />
                    <path d="M14 2v6h6M9.5 13.5l1.5 2.5 1.5-2.5M9.5 17.5l1.5-2.5 1.5 2.5" />
                </svg>
                <!-- Badge de validación -->
                <div v-if="validation.isValid !== null"
                    class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center"
                    :class="validation.isValid ? 'bg-green-500' : 'bg-red-500'">
                    <svg v-if="validation.isValid" class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>

            <!-- Info del archivo -->
            <div class="text-left flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                    {{ fileName }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ (file.size / 1024).toFixed(2) }} KB
                    <span v-if="validation.totalRows" class="ml-2">
                        • {{ validation.totalRows }} {{ validation.totalRows === 1 ? 'fila' : 'filas' }}
                    </span>
                </p>
                <!-- Mensaje de validación -->
                <div v-if="validation.message" class="text-xs mt-1"
                    :class="validation.isValid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">

                    <!-- Mensaje de formato incorrecto con HTML -->
                    <div v-if="validation.message === 'formato_incorrecto'" v-html="`
                        ❌ Formato de Excel incorrecto.<br>
                        📋 <strong>Asegúrese de que:</strong><br>
                        • Sea el archivo correcto<br>
                        • Los nombres de las columnas coincidan exactamente con el cuadro de ejemplo<br>
                        • Las columnas estén ubicadas en cualquiera de las 3 primeras filas<br><br>
                        💡 <strong>Recomendación:</strong> Descargue la plantilla de ejemplo y utilícela como base.
                    `"></div>

                    <!-- Otros mensajes normales -->
                    <span v-else>{{ validation.message }}</span>
                </div>
            </div>

            <!-- Botón para eliminar archivo -->
            <button type="button" @click.stop="emit('remove')"
                class="p-1.5 rounded-full hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>
