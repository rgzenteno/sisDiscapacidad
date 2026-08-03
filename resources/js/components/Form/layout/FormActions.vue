<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed } from 'vue';

/**
 * Componentes
 */
import Button from '@/components/Button.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    processing: {
        type: Boolean,
        default: false
    },
    editMode: {
        type: Boolean,
        default: false
    },
    existingData: {
        type: [Object, Array],
        default: () => ({})
    },
    showOmitir: {
        type: Boolean,
        default: false
    },
    soli: {
        type: String,
        default: ''
    },
    fieldCount: {
        type: Number,
        default: 0
    },
    botonName: {
        type: String
    },
    importing: {
        type: Boolean,
        default: false
    },
    importingText: {
        type: String,
        default: 'Importando...'
    },
    tutorFound: {
        type: Boolean,
        default: false
    },
    keepButtonText: {
        type: Boolean,
        default: false
    },
    isDirty: {
        type: Boolean,
        default: false
    },
    esPropioTutor: {
        type: Boolean,
        default: false
    },
    // Condición extra de bloqueo específica de un campo (ej. presupuesto sin
    // calcular en "Agregar Mes") — no depende de processing/importing.
    extraDisabled: {
        type: Boolean,
        default: false
    },
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits([
    'submit',
    'cancel',
    'omitir',
    'reset'
]);

// ============================================================================
// COMPUTED - ESTADO UI DEL FORMULARIO
// ============================================================================

const submitButtonText = computed(() => {
    if (props.importing) return props.importingText;
    if (props.processing) return 'Procesando...';
    if (props.tutorFound && !props.keepButtonText) return 'Siguiente';
    if (props.esPropioTutor) return 'Siguiente';
    if (props.botonName) return props.botonName;
    if (props.soli) return props.soli;
    if (props.existingData && Object.keys(props.existingData).length > 0) {
        return props.editMode ? 'Actualizar' : 'Guardar';
    }
    return 'Siguiente';
});

const isDisabled = computed(() => {
    return props.processing || props.importing || props.extraDisabled || (props.editMode && !props.isDirty);
});

const showSpinner = computed(() => {
    return props.importing || props.processing;
});

const showReset = computed(() => {
    return props.editMode && props.isDirty && !props.processing;
});

const containerClasses = computed(() => {
    const base = 'border-t border-gray-200 dark:border-gray-600 pb-4 bg-white dark:bg-gray-800 rounded-b-3xl';
    return props.fieldCount >= 6
        ? `${base} pt-4 px-4 sm:px-6`
        : `${base} pt-3 px-6`;
});
</script>

<template>
    <div :class="containerClasses">
    
        <div class="flex items-center gap-2 flex-nowrap justify-center sm:justify-end">

            <!-- Cancelar -->
            <Button type="button" class="text-slate-700 bg-white hover:bg-slate-100
                        flex-1 justify-center min-w-0 truncate
                        sm:flex-none sm:w-auto
                        px-3 sm:px-6 py-2.5 rounded-xl border border-gray-200" @click="emit('cancel')">
                Cancelar
            </Button>

            <!-- Omitir (condicional) -->
            <Button v-if="showOmitir" type="button" class="border-red-700 text-red-700 bg-white hover:bg-red-100
                        flex-1 justify-center min-w-0 truncate
                        sm:flex-none sm:w-auto
                        px-3 sm:px-6 py-2.5 rounded-xl border" @click="emit('omitir')">
                Omitir
            </Button>

            <!-- Restablecer (editMode) -->
            <Button v-if="showReset" type="button" class="text-orange-600 border-orange-300 bg-white hover:bg-orange-50
                        flex-1 justify-center min-w-0 truncate
                        sm:flex-none sm:w-auto
                        px-3 sm:px-6 py-2.5 rounded-xl border" @click="emit('reset')">
                Restablecer
            </Button>

            <!-- Submit -->
            <Button type="submit" form="main-form" class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]
                    flex-1 justify-center min-w-0 sm:flex-none sm:w-auto px-3 sm:px-6 py-2.5 rounded-xl"
                :class="{ 'opacity-25': isDisabled }" :disabled="isDisabled">
                <div class="flex items-center gap-2 justify-center">
                    <svg v-if="showSpinner" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                    <span class="truncate">{{ submitButtonText }}</span>
                </div>
            </Button>
        </div>
    </div>
</template>
