<script setup>
// ============ INICIO PROPS ============ //
const props = defineProps({
    options: {
        type: Array,
        default: () => []
    },
    selectedValue: {
        type: [String, Number],
        default: ''
    },
    showAddButton: {
        type: Boolean,
        default: false
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['select', 'add-new']);
// ============ FIN EMITS ============ //
</script>

<template>
    <div class="w-full max-w-sm bg-white rounded-lg border border-gray-200 shadow-lg dark:bg-gray-800 dark:border-gray-700">
        <!-- Lista scrolleable con altura limitada -->
        <div class="max-h-48 overflow-y-auto">
            <ul class="py-1">
                <!-- Mensaje cuando no hay opciones -->
                <li v-if="!options || options.length === 0" class="px-4 py-3">
                    <div class="flex items-center gap-3 text-slate-400 dark:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <span class="text-sm">No hay opciones disponibles</span>
                    </div>
                </li>

                <!-- Opciones del select -->
                <li v-for="option in options" :key="option.value">
                    <button
                        type="button"
                        @click="emit('select', option.value)"
                        class="flex items-center justify-between w-full px-4 py-2 text-sm text-left hover:bg-gray-100 transition-colors duration-150 dark:hover:bg-gray-700"
                        :class="selectedValue && selectedValue.toString() === option.value.toString()
                            ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900 dark:text-blue-200'
                            : 'text-gray-700 dark:text-gray-300'"
                    >
                        <span>{{ option.text }}</span>
                        <svg
                            v-if="selectedValue && selectedValue.toString() === option.value.toString()"
                            class="w-4 h-4 text-blue-600 dark:text-blue-400"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Botón para agregar nueva opción (fuera del scroll) -->
        <div v-if="showAddButton" class="border-t border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
            <button
                type="button"
                @click="emit('add-new')"
                class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150 dark:text-blue-400 dark:hover:bg-gray-600 dark:hover:text-blue-300"
            >
                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center dark:bg-blue-900">
                    <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                    </svg>
                </div>
                <span>Agregar nueva opción</span>
            </button>
        </div>
    </div>
</template>
