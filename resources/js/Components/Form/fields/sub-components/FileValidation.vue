<script setup>
// ============ INICIO PROPS ============ //
const props = defineProps({
    validation: {
        type: Object,
        required: true
    }
});
// ============ FIN PROPS ============ //
</script>

<template>
    <div
        v-if="validation.message"
        class="mt-3 p-3 rounded-lg border"
        :class="validation.isValid === true
            ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700'
            : validation.isValid === false
                ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700'
                : 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700'"
    >
        <div class="flex items-start gap-2">
            <!-- Icono según estado -->
            <svg
                v-if="validation.isValid === true"
                class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>

            <svg
                v-else-if="validation.isValid === false"
                class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>

            <svg
                v-else
                class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <!-- Mensaje y detalles -->
            <div class="flex-1">
                <p
                    class="text-sm font-medium"
                    :class="validation.isValid === true
                        ? 'text-green-700 dark:text-green-300'
                        : validation.isValid === false
                            ? 'text-red-700 dark:text-red-300'
                            : 'text-blue-700 dark:text-blue-300'"
                >
                    {{ validation.message }}
                </p>

                <!-- Mostrar columnas encontradas -->
                <div v-if="validation.isValid === false && validation.foundColumns.length > 0" class="mt-2 space-y-1">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                        Columnas encontradas:
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="col in validation.foundColumns"
                            :key="col"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200"
                        >
                            {{ col }}
                        </span>
                    </div>
                </div>

                <!-- Mostrar columnas faltantes -->
                <div v-if="validation.missingColumns.length > 0" class="mt-2 space-y-1">
                    <p class="text-xs font-medium text-red-600 dark:text-red-400">
                        Columnas faltantes:
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="col in validation.missingColumns"
                            :key="col"
                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200"
                        >
                            {{ col }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
