<script setup>
// ============ INICIO IMPORTS ============ //
import { computed, watch } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
import Input from '@/components/Input.vue';
import { usePresupuestoCalculation } from '../composables/usePresupuestoCalculation';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
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
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['update:modelValue']);
// ============ FIN EMITS ============ //

// ============ INICIO COMPOSABLES ============ //
const { mostrarPresupuestoAnual, presupuestoSugerido } = usePresupuestoCalculation(
    props.form,
    props.props
);
// ============ FIN COMPOSABLES ============ //

// ============ INICIO COMPUTED ============ //
// PDF cargado y válido
const pdfValido = computed(() =>
    props.form.archivo_validacion?.isValid === true
);

// Monto ingresado y válido
const montoIngresado = computed(() =>
    !!props.form.monto && parseInt(props.form.monto) > 0
);

// Ambas condiciones cumplidas para habilitar el presupuesto
const presupuestoHabilitado = computed(() =>
    props.props.editMode || (pdfValido.value && montoIngresado.value)
);

const mensajeGuia = computed(() => {
    if (props.props.editMode) return null;
    if (!pdfValido.value && !montoIngresado.value) {
        return 'Cargue el archivo PDF y el monto por persona para calcular';
    }
    if (!pdfValido.value) {
        return 'Cargue el archivo PDF válido para calcular';
    }
    if (!montoIngresado.value) {
        return 'Ingrese el monto por persona para calcular';
    }
    return null;
});
// ============ FIN COMPUTED ============ //

// ============ INICIO WATCHERS ============ //
watch(() => props.form.presupuesto_anual, (nuevoValor) => {
    if (nuevoValor && props.form.errors.presupuesto_anual) {
        delete props.form.errors.presupuesto_anual;
    }
});

watch(() => props.modelValue, (nuevoValor) => {
    if (nuevoValor && props.error) {
        emit('update:modelValue', nuevoValor);
    }
});
// ============ FIN WATCHERS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Presupuesto Anual (solo si es primer registro del año) -->
        <div v-if="!props.props.editMode && mostrarPresupuestoAnual" class="mb-4">
            <div class="flex">
                <InputLabel
                    for="presupuesto_anual"
                    value="Presupuesto Anual (Bs.)"
                    class="block text-sm font-medium text-gray-900 dark:text-white"
                />
                <span class="ms-1 text-red-600">*</span>
            </div>
            <Input
                input-type="number"
                v-model="form.presupuesto_anual"
                placeholder="Ingrese el presupuesto anual"
                :customClass="field.nameStyle"
                :errors="form.errors.presupuesto_anual"
            />
        </div>

        <!-- Presupuesto Mensual -->
        <div>
            <div class="flex">
                <InputLabel
                    :for="field.name"
                    value="Presupuesto Mensual (Bs.)"
                    class="block text-sm font-medium text-gray-900 dark:text-white"
                />
                <span class="ms-1 text-red-600">*</span>
            </div>

            <!-- Input opaco si no están las condiciones -->
            <div :class="{ 'opacity-50 pointer-events-none': !presupuestoHabilitado }">
                <Input
                    :input-type="field.type"
                    :model-value="modelValue"
                    placeholder="Se calculará automáticamente"
                    :errors="error"
                    class="!bg-gray-100"
                    @update:model-value="emit('update:modelValue', $event)"
                />
            </div>

            <!-- Mensaje guía o presupuesto sugerido -->
            <p v-if="!presupuestoHabilitado && !props.props.editMode" class="text-sm text-gray-400 mt-1 italic">
                {{ mensajeGuia }}
            </p>
            <p v-else-if="presupuestoHabilitado && !props.props.editMode" class="text-sm text-gray-500 mt-1">
                Presupuesto sugerido: <span class="font-medium text-gray-700">Bs. {{ presupuestoSugerido }}</span>
            </p>
        </div>
    </div>
</template>
