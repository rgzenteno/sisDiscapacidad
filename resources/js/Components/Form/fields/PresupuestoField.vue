<script setup>
// ============ INICIO IMPORTS ============ //
import { watch } from 'vue';
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

// ============ INICIO WATCHERS ============ //
// Watch para limpiar errores cuando se ingresa presupuesto anual
watch(() => props.form.presupuesto_anual, (nuevoValor) => {
    if (nuevoValor && props.form.errors.presupuesto_anual) {
        delete props.form.errors.presupuesto_anual;
    }
});

// Watch para limpiar errores cuando se ingresa presupuesto
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

        <!-- Presupuesto Mensual (calculado automáticamente) -->
        <div>
            <div class="flex">
                <InputLabel
                    :for="field.name"
                    value="Presupuesto Mensual (Bs.)"
                    class="block text-sm font-medium text-gray-900 dark:text-white"
                />
                <span class="ms-1 text-red-600">*</span>
            </div>
            <Input
                :input-type="field.type"
                :model-value="modelValue"
                placeholder="Se calculará automáticamente al ingresar el monto"
                :errors="error"
                class="!bg-gray-100"
                @update:model-value="emit('update:modelValue', $event)"
            />
            <p class="text-sm text-gray-500 mt-1">
                Presupuesto sugerido: {{ presupuestoSugerido }}
            </p>
        </div>
    </div>
</template>
