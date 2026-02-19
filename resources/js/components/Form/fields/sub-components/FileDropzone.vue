<script setup>
// ============ INICIO PROPS ============ //
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    isDragging: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['click', 'drop', 'dragover', 'dragleave']);
// ============ FIN EMITS ============ //
</script>

<template>
    <div @drop.prevent="emit('drop', $event)" @dragover.prevent="emit('dragover')"
        @dragleave.prevent="emit('dragleave')" @click="emit('click')" :class="[
            'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200',
            isDragging
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : error
                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                    : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500'
        ]">
        <div class="flex flex-col items-center justify-center">
            <!-- Icono de upload -->
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none"
                viewBox="0 0 48 48">
                <path
                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>

            <!-- Texto informativo -->
            <div class="mt-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ field.placeholder || 'Arrastra tu archivo aquí o haz clic para seleccionar' }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ field.acceptedTypes || '.xlsx,.xls,.csv' }} hasta {{ field.maxSize || 5 }}MB
                </p>

                <!-- Mostrar columnas requeridas -->
                <!-- <div v-if="field.requiredColumns && field.requiredColumns.length > 0" class="mt-3">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Columnas requeridas:
                    </p>
                    <div class="flex flex-wrap gap-1 justify-center">
                        <span
                            v-for="col in field.requiredColumns"
                            :key="col"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                        >
                            {{ col }}
                        </span>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</template>
