<template>
    <div class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-40 px-4 py-6 backdrop-blur-sm"
        :class="{ invisible: hidden, 'pointer-events-none': hidden }">
        <div class="relative w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl transform transition-all duration-300 max-h-[90vh] flex flex-col overflow-hidden"
            :class="maxWidth">

            <!-- Header -->
            <div v-if="showHeader"
                class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700/50 sticky top-0 z-10 bg-white dark:bg-gray-800 rounded-t-3xl">

                <div class="flex items-center gap-3 min-w-0">
                    <!-- Ícono -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :class="fondoIcon">
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
                        <h2 class="text-base sm:text-lg font-bold text-slate-700 dark:text-slate-200 truncate">
                            <slot name="label1">Título por defecto</slot>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-400 dark:text-slate-500 truncate">
                            <slot name="label2">Subtítulo por defecto</slot>
                        </p>
                    </div>
                </div>

                <!-- Badge a la derecha -->
                <div class="flex-shrink-0">
                    <slot name="badge"></slot>
                </div>

                <!-- Botón cerrar -->
                <button type="button" @click="emit('close')"
                    class="flex-shrink-0 p-1.5 rounded-full text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors duration-200">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>

            <!-- Botón cerrar sin header -->
            <button v-if="!showHeader" type="button" @click="emit('close')"
                class="absolute top-3 right-3 p-1.5 rounded-full text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors duration-200 z-10">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>

            <!-- Body -->
            <div :class="[styleBody, !styleBody.includes('overflow') ? 'overflow-y-auto' : '']" class="flex-1">
                <slot></slot>
            </div>

            <slot name="footer"></slot>
            <!-- Footer (Condicional) -->
            <div v-if="showFooter" class="border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-end space-x-3">
                    <slot name="buttons">
                        <!-- Botones por defecto -->
                        <Button type="submit"
                            class="text-white bg-[rgb(var(--brand-rgb))] hover:bg-[rgba(var(--brand-rgb),0.85)]"
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
// ============================================================================
// IMPORTS
// ============================================================================
import { onMounted, onUnmounted } from 'vue';
import Button from './Button.vue';

// ============================================================================
// PROPS Y EMITS
// ============================================================================
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
    },
    fondoIcon: {
        type: String,
        default: 'from-[rgb(var(--brand-500))] to-[rgb(var(--brand-700))] shadow-md ring-1 ring-[rgb(var(--brand-100))] bg-gradient-to-br'
    },
    styleBody: {
        type: String,
        default: 'p-3 sm:px-6 py-4 pb-4'
    },
    // Oculta el modal (visual e interactivamente) sin desmontarlo — para
    // cuando se abre un modal secundario encima y no se quiere perder el
    // estado del formulario de este (ver PresupuestoField/Form.vue).
    hidden: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'submit', 'cancel', 'omitir']);

// ============================================================================
// LIFECYCLE
// ============================================================================

/**
 * Cierra el modal al presionar la tecla ESC
 * @param {KeyboardEvent} e - Evento de teclado
 */
const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>
