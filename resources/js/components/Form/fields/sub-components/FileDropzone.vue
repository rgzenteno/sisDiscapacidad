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
            'group relative border-[1.5px] border-dashed rounded-xl px-6 py-2 text-center cursor-pointer transition-all duration-200',
            isDragging
                ? 'border-blue-400 bg-blue-50 dark:bg-blue-950/30 dark:border-blue-500'
                : error
                    ? 'border-red-400 bg-red-50 dark:bg-red-950/30 dark:border-red-500'
                    : 'border-gray-200 hover:border-gray-300 bg-white dark:bg-transparent dark:border-gray-700 dark:hover:border-gray-500'
        ]">
        <div class="flex flex-col items-center gap-3">

            <!-- Ícono -->
            <div :class="[
                'w-12 h-12 rounded-lg flex items-center justify-center transition-colors duration-200',
                isDragging
                    ? 'bg-blue-100 dark:bg-blue-900/40'
                    : error
                        ? 'bg-red-100 dark:bg-red-900/40'
                        : 'bg-gray-100 dark:bg-gray-800 group-hover:bg-gray-200 dark:group-hover:bg-gray-700'
            ]">
                <!-- Ícono de error -->
                <svg v-if="error" class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <!-- Ícono dragging -->
                <svg v-else-if="isDragging" class="w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="12" y1="18" x2="12" y2="12" />
                    <line x1="9" y1="15" x2="15" y2="15" />
                </svg>
                <!-- Ícono normal -->
                <svg v-else class="w-5 h-5 text-gray-400 dark:text-gray-500 transition-colors group-hover:text-gray-500"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="12" y1="18" x2="12" y2="12" />
                    <line x1="9" y1="15" x2="15" y2="15" />
                </svg>
            </div>

            <!-- Texto -->
            <div class="space-y-1">
                <p :class="[
                    'text-sm font-medium',
                    isDragging ? 'text-blue-600 dark:text-blue-400'
                        : error ? 'text-red-600 dark:text-red-400'
                            : 'text-gray-700 dark:text-gray-200'
                ]">
                    {{ isDragging ? 'Suelta para cargar' : error
                        ? error : (field.placeholder || 'Arrastra tu archivo aquí') }}
                </p>
                <p v-if="!isDragging" :class="[
                    'text-xs',
                    error ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'
                ]">
                    o <span :class="error ? '' : 'text-blue-500 hover:underline'">haz clic para seleccionar</span>
                </p>
            </div>

            <!-- Pill de tipos aceptados -->
            <span :class="[
                'text-[11px] px-3 py-1 rounded-full border transition-colors duration-200',
                isDragging
                    ? 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800'
                    : error
                        ? 'bg-red-100 text-red-600 border-red-200 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800'
                        : 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'
            ]">
                {{ field.acceptedTypes || '.xlsx · .xls · .csv' }} — máx {{ field.maxSize || 5 }}MB
            </span>

        </div>
    </div>
</template>
