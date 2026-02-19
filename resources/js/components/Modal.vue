<template>
    <div
        class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-40 px-4 py-6 overflow-y-auto backdrop-blur-sm">
        <div class="relative w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl transform transition-all duration-300 my-8"
            :class="maxWidth">

            <!-- Header (Condicional) -->
            <div v-if="showHeader"
                class="grid grid-cols-[1fr_auto] gap-6 px-4 pt-4 pb-4 border-b border-gray-100 dark:border-gray-600/50 rounded-t-3xl bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 sticky top-0 z-10">
                <div class="min-w-0">
                    <div class="grid grid-cols-[auto_1fr] gap-4 items-center mb-2">
                        <!-- Avatar / Ícono -->
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                            <slot name="icon">
                                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </slot>
                        </div>

                        <!-- Título y subtítulo -->
                        <div class="min-w-0">
                            <h2 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight truncate">
                                <slot name="label1">Título por defecto</slot>
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-blue-300 truncate">
                                <slot name="label2">Subtítulo por defecto</slot>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botón Cerrar en Header -->
                <div class="flex items-start gap-3 flex-shrink-0">
                    <button type="button" @click="emit('close')"
                        class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Botón Cerrar sin Header -->
            <button v-if="!showHeader" type="button" @click="emit('close')"
                class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition z-10">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>

            <!-- Body -->
            <div class="px-6 py-4 pb-0">
                <slot></slot>
            </div>

            <!-- Footer (Condicional) -->
            <div v-if="showFooter" class="border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-end space-x-3">
                    <slot name="buttons">
                        <!-- Botones por defecto -->
                        <Button type="submit" class="text-white bg-blue-600 hover:bg-blue-500"
                            :class="{ 'opacity-25': isDisabled }" :disabled="isDisabled" @click="emit('submit')">
                            <div class="flex items-center gap-2">
                                <svg v-if="showSpinner" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>{{ submitButtonText || 'Guardar' }}</span>
                            </div>
                        </Button>

                        <Button type="button" class="text-slate-700 bg-white hover:bg-slate-100"
                            @click="emit('cancel')">
                            Cancelar
                        </Button>

                        <Button v-if="showOmitir" type="button"
                            class="border-red-700 text-red-700 bg-white hover:bg-red-100" @click="emit('omitir')">
                            Omitir
                        </Button>
                    </slot>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    showHeader: {
        type: Boolean,
        default: true
    },
    showFooter: {
        type: Boolean,
        default: true
    },
    showSpinner: {
        type: Boolean,
        default: false
    },
    isDisabled: {
        type: Boolean,
        default: false
    },
    submitButtonText: {
        type: String,
        default: 'Guardar'
    },
    showOmitir: {
        type: Boolean,
        default: false
    },
    maxWidth: {
        type: String,
        default: 'max-w-2xl'
    }
});

const emit = defineEmits(['close', 'submit', 'cancel', 'omitir']);
</script>
