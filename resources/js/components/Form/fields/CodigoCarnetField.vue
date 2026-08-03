<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, watch } from 'vue';

/**
 * Componentes
 */
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number],
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    form: {
        type: Object,
        required: true
    },
    props: {
        type: Object,
        required: true
    },
    nombreFor: {
        type: String
    }
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue']);

// ============================================================================
// COMPUTED
// ============================================================================
const canRegenerate = computed(() => {
    const nombre = props.props.nombreFor || props.props.existingData?.nombre_persona;
    const apellido = props.props.apellidoFor?.trim() ||
        props.props.existingData?.apellido_persona?.trim() || '';
    const fecha = props.props.fechaNacimiento || props.props.existingData?.fecha_nacimiento;
    return !!(nombre && apellido && fecha);
});

// ============================================================================
// FUNCIONES - CÓDIGO DE CARNET
// ============================================================================

/**
 * Genera el código de carnet automáticamente
 */
const generateCodigoCarnet = () => {
    const nombre = props.props.nombreFor?.trim().split(' ')[0] ||
        props.props.existingData?.nombre_persona?.trim().split(' ')[0] || '';
    const apellido = props.props.apellidoFor?.trim() ||
        props.props.existingData?.apellido_persona?.trim() || '';
    const fechaNac = props.props.fechaNacimiento || props.props.existingData?.fecha_nacimiento;

    if (!nombre || !apellido || !fechaNac) return '';
    const inicialNombre = nombre.charAt(0).toUpperCase();
    const inicialesApellido = apellido.split(' ').map(p => p.charAt(0).toUpperCase()).join('');
    return `03-${fechaNac.replace(/-/g, '')}${inicialNombre}${inicialesApellido}`;
};

/**
 * Maneja el click del botón de reload
 */
const handleReload = () => {
    const nuevoValor = generateCodigoCarnet();

    emit('update:modelValue', nuevoValor);
};

/**
 * Maneja el input del usuario
 * Permite: números, letras, guiones
 */
const handleInput = (e) => {
    let newValue = e.target.value;

    // Solo permite: números, letras mayúsculas y guiones
    newValue = newValue
        .replace(/[^0-9A-ZÑ-]/gi, '')
        .toUpperCase();

    emit('update:modelValue', newValue);
    e.target.value = newValue;
};

// ============================================================================
// WATCHERS
// ============================================================================

// Auto-genera el código de carnet cuando los datos del beneficiario cambian
watch([
    () => props.props.nombreFor,
    () => props.props.apellidoFor,
    () => props.props.fechaNacimiento,
    () => props.props.existingData?.nombre_persona,
    () => props.props.existingData?.apellido_persona,
    () => props.props.existingData?.fecha_nacimiento
], () => {
    if (canRegenerate.value && !props.modelValue) {
        emit('update:modelValue', generateCodigoCarnet());
    }
}, { immediate: true });

</script>

<template>
    <div class="w-full">

        <!-- Input con botón de reload -->
        <div class="relative">
            <input type="text" :value="modelValue" @input="handleInput" :readonly="field.readonly" :class="[
                'uppercase',
                error ? 'border-red-500 text-red-500 placeholder-red-600' : 'border-gray-300 hover:border-[rgb(var(--brand-400))]',
                'focus:border-[rgb(var(--brand-500))] focus:ring-[rgb(var(--brand-500))]',
                'w-full bg-gray-50 border shadow-sm text-gray-900 text-sm rounded-xl px-2 py-2.5 pr-10',
                'dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white',
                field.readonly ? 'bg-gray-100 cursor-not-allowed text-gray-600 dark:bg-gray-500' : ''
            ]" :placeholder="field.placeholder || 'Ingrese el código de carnet'" />

            <!-- Botón de reload -->
            <button v-if="canRegenerate && !field.readonly" type="button" @click="handleReload"
                :disabled="!canRegenerate"
                class="absolute right-2 top-1/2 -translate-y-1/2  rounded-md bg-gray-100 hover:bg-gray-200 leading-none px-1 pt-1"
                title="Regenerar código automáticamente">
                <Icon :icon-button="true" name="reload" :class-name="error ? 'text-red-500' : 'text-[rgb(var(--brand-600))]'"
                    fill="none" stroke="currentColor" stroke-width="2" :size="20" />
            </button>
        </div>

        <!-- Mensaje de ayuda -->
        <p v-if="!field.readonly && !error" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Formato: 03-YYYYMMDD-INICIALES
            <span v-if="canRegenerate" class="text-[rgb(var(--brand-600))] dark:text-[rgb(var(--brand-400))]">
                • Haz clic en el botón para regenerar
            </span>
            <span v-else class="text-red-600 dark:text-red-400">
                • Fecha de nacimiento no registrado
            </span>
        </p>

        <!-- Mensaje de error -->
        <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
