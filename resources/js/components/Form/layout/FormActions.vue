<script setup>
// ============ INICIO IMPORTS ============ //
import { computed } from 'vue';
import Button from '@/components/Button.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
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
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['submit', 'cancel', 'omitir']);
// ============ FIN EMITS ============ //

// ============ INICIO COMPUTED ============ //
const submitButtonText = computed(() => {
    // PRIORIDAD 1: Si está importando
    if (props.importing) {
        return props.importingText;
    }

    // PRIORIDAD 2: Si está procesando
    if (props.processing) {
        return 'Procesando...';
    }

    // PRIORIDAD 3: Si se encontró un tutor Y NO se debe mantener el texto original
    if (props.tutorFound && !props.keepButtonText) {
        return 'Siguiente';
    }

    // PRIORIDAD 4: Si viene botonName, usar ese
    if (props.botonName) {
        return props.botonName;
    }

    // PRIORIDAD 5: Si viene soli, usar ese
    if (props.soli) {
        return props.soli;
    }

    // PRIORIDAD 6: Si hay datos existentes
    if (props.existingData && Object.keys(props.existingData).length > 0) {
        return props.editMode ? 'Actualizar' : 'Guardar';
    }

    // PRIORIDAD 7: Default
    return 'Siguiente';
});

const isDisabled = computed(() => {
    return props.processing || props.importing;
});

const showSpinner = computed(() => {
    return props.importing || props.processing;
});

const containerClasses = computed(() => {
    if (props.fieldCount >= 6) {
        return 'border-t border-gray-200 dark:border-gray-600 pt-4 px-6 pb-4 bg-white dark:bg-gray-800 rounded-b-3xl';
    }
    return 'border-t border-gray-200 dark:border-gray-600 pt-3 px-6 pb-4 bg-white dark:bg-gray-800 rounded-b-3xl';
});
// ============ FIN COMPUTED ============ //
</script>

<template>
    <div :class="containerClasses">
        <div class="flex items-center justify-end space-x-3">
            <!-- Botón Cancelar -->
            <Button type="button" class="text-slate-700 bg-white hover:bg-slate-100" @click="emit('cancel')">
                Cancelar
            </Button>

            <!-- Botón Omitir (Condicional) -->
            <Button v-if="showOmitir" type="button" class="border-red-700 text-red-700 bg-white hover:bg-red-100"
                @click="emit('omitir')">
                Omitir
            </Button>

            <!-- Botón Submit con Spinner -->
            <Button
    type="submit"
    class="text-white bg-blue-600 hover:bg-blue-500"
    :class="{ 'opacity-25': isDisabled }"
    :disabled="isDisabled"
>
    <div class="flex items-center gap-2">
        <!-- ✅ Spinner ya existe -->
        <svg v-if="showSpinner" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
        <span>{{ submitButtonText }}</span>
    </div>
</Button>
        </div>
    </div>
</template>
