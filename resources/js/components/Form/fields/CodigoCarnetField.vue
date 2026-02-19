<script setup>
// ============ INICIO IMPORTS ============ //
import { computed, watch } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number], default: '' },
    error: { type: String, default: '' },
    form: { type: Object, required: true },
    props: { type: Object, required: true },
    nombreFor: { type: String }
});
// ============ FIN PROPS ============ //

const emit = defineEmits(['update:modelValue']);

// ============ INICIO COMPUTED ============ //
const canRegenerate = computed(() => {
    // Buscar nombreFor
    const nombre = props.props.nombreFor ||
        props.props.existingData?.nombre_completo ||
        props.props.existingData?.nombre_persona;

    // Buscar fecha de nacimiento en múltiples lugares
    const fecha = props.props.fechaNacimiento ||
        props.props.existingData?.fecha_nacimiento;

    const resultado = !!(nombre && fecha);

    return resultado;
});
// ============ FIN COMPUTED ============ //

// ============ INICIO MÉTODOS ============ //
/**
 * Genera el código de carnet automáticamente
 */
const generateCodigoCarnet = () => {
    // Buscar nombre en múltiples lugares
    const nombreCompleto = props.props.nombreFor ||
        props.props.existingData?.nombre_completo ||
        props.props.existingData?.nombre_persona;

    // Buscar fecha en múltiples lugares
    const fechaNac = props.props.fechaNacimiento ||
        props.props.existingData?.fecha_nacimiento;

    if (!nombreCompleto || !fechaNac) {
        return '';
    }

    const palabras = nombreCompleto.trim().split(' ');
    const iniciales = palabras
        .map(palabra => palabra.charAt(0).toUpperCase())
        .join('');
    const fechaFormateada = fechaNac.replace(/-/g, '');

    return `03-${fechaFormateada}${iniciales}`;
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
        .replace(/[^0-9A-Z-]/gi, '')
        .toUpperCase();

    emit('update:modelValue', newValue);
    e.target.value = newValue;
};

/**
 * Valida el formato del código de carnet
 */
const isFormatoValido = computed(() => {
    if (!props.modelValue) return true;
    const regex = /^03-\d{8}[A-Z]+$/;
    return regex.test(props.modelValue);
});
// ============ FIN MÉTODOS ============ //

// Auto-generar si está vacío
watch([
    () => props.props.nombreFor,
    () => props.props.fechaNacimiento,
    () => props.props.existingData?.nombre_completo,
    () => props.props.existingData?.fecha_nacimiento
], () => {
    if (canRegenerate.value && !props.modelValue) {
        const nuevoValor = generateCodigoCarnet();
        emit('update:modelValue', nuevoValor);
    }
}, { immediate: true });


</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <div class="flex items-center justify-between mb-1" v-show="field.label !== ''">
            <div class="flex">
                <InputLabel :for="field.name" :value="field.label"
                    class="block text-sm font-medium text-gray-900 dark:text-white" />
                <span :style="{ visibility: field.required === true ? 'visible' : 'hidden' }"
                    class="ms-1 text-red-600">*</span>
            </div>
            <span v-if="modelValue && !isFormatoValido && !error" class="text-xs text-amber-600 dark:text-amber-400">
                Formato no válido
            </span>
        </div>

        <!-- Input con botón de reload -->
        <div class="relative">
            <input type="text" :value="modelValue" @input="handleInput" :readonly="field.readonly" :class="[
                'uppercase',
                error ? 'border-red-500 text-red-500 placeholder-red-600' : 'border-gray-300 hover:border-blue-400',
                'focus:border-blue-500 focus:ring-blue-500',
                'w-full bg-gray-50 border shadow-sm text-gray-900 text-sm rounded-lg p-2 pr-10',
                'dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white',
                field.readonly ? 'bg-gray-100 cursor-not-allowed text-gray-600 dark:bg-gray-500' : ''
            ]" :placeholder="field.placeholder || 'Ingrese el código de carnet'" />

            <!-- Botón de reload -->
            <button v-if="canRegenerate && !field.readonly" type="button" @click="handleReload"
                :disabled="!canRegenerate" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md
                       text-gray-500 hover:text-blue-600 hover:bg-blue-50
                       dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-gray-700
                       transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500
                       disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                title="Regenerar código automáticamente">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8" />
                    <path d="M21 3v5h-5" />
                </svg>
            </button>
        </div>

        <!-- Mensaje de ayuda -->
        <p v-if="!field.readonly && !error" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Formato: 03-YYYYMMDD-INICIALES
            <span v-if="canRegenerate" class="text-blue-600 dark:text-blue-400">
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
