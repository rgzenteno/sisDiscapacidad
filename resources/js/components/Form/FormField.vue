<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed } from 'vue';

/**
 * Componentes
 */
import CheckboxPermissions from './fields/CheckboxPermissions.vue';
import CheckboxGroupField from './fields/CheckboxGroupField.vue';
import IndefinidoCheckField from './fields/IndefinidoCheckField.vue';
import CodigoCarnetField from './fields/CodigoCarnetField.vue';
import MonthYearField from './fields/MonthYearField.vue';
import PresupuestoField from './fields/PresupuestoField.vue';
import PropioCheckField from './fields/PropioCheckField.vue';
import TextareaField from './fields/TextareaField.vue';
import ReadOnlyField from './fields/ReadOnlyField.vue';
import SelectField from './fields/SelectField.vue';
import CedulaField from './fields/CedulaField.vue';
import TextField from './fields/TextField.vue';
import FileUpload from './fields/FileUpload.vue';
import LabelField from './fields/LabelField.vue';
import TimeField from './fields/TimeField.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number, Array, Boolean, Object, null],
        default: null
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
        type: String,
    },
    apellidoFor: {
        type: String,
        default: null
    },
    ciFor: {
        type: String,
        default: null
    },
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits([
    'update:modelValue',
    'openFormOption',
    'update:validation',
    'sub-modal-toggle'
]);

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Mapa de tipos de input a sus componentes correspondientes
 */
const fieldComponents = {
    text: TextField,
    number: TextField,
    date: TextField,
    email: TextField,
    tel: TextField,
    textarea: TextareaField,
    select: SelectField,
    cedula: CedulaField,
    check: CheckboxGroupField,
    checkbox_pago: CheckboxGroupField,
    checkbox_permissions: CheckboxPermissions,
    file_upload: FileUpload,
    presupuesto: PresupuestoField,
    time: TimeField,
    label: LabelField,
    id: ReadOnlyField,
    verificar: ReadOnlyField,
    codigo_carnet: CodigoCarnetField,
    indefinido_check: IndefinidoCheckField,
    month_year: MonthYearField,
    propio_check: PropioCheckField,
};

/**
 * Resuelve el componente a renderizar según el tipo y configuración del campo
 */
const fieldComponent = computed(() => {
    const typeInput = props.field.typeInput || props.field.type;

    if (props.field.name === 'presupuesto') {
        return PresupuestoField;
    }

    if (props.field.typeCi === 'ci' && props.field.typeInput === 'cedula') {
        return CedulaField;
    }

    return fieldComponents[typeInput] || TextField;
});

/**
 * Reúne todas las props necesarias para pasarlas al componente hijo resuelto
 */
const fieldProps = computed(() => ({
    field: props.field,
    modelValue: props.modelValue,
    error: props.error,
    form: props.form,
    props: props.props,
    nombreFor: props.nombreFor,
    apellidoFor: props.apellidoFor,
    ciFor: props.ciFor
}));

// ============================================================================
// FUNCIONES - EVENTOS
// ============================================================================

/**
 * Emite la actualización del valor del campo
 * @param {*} value - Nuevo valor del campo
 */
const handleUpdate = (value) => {
    emit('update:modelValue', value);
};

/**
 * Emite el evento para abrir el formulario de opciones
 */
const handleOpenFormOption = () => {
    emit('openFormOption');
};

/**
 * Emite la actualización del estado de validación del campo
 * @param {boolean|Object} validation - Resultado de la validación
 */
const handleValidationUpdate = (validation) => {
    emit('update:validation', validation);
};
</script>

<template>
    <div class="w-full" :class="{ 'opacity-40 pointer-events-none': field.opaco }">

        <div v-if="field.label" class="flex items-center gap-0.5 mb-1">
            <label :for="field.name"
                class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                {{ field.label }}
            </label>
            <span :style="{ visibility: field.required ? 'visible' : 'hidden' }"
                class="text-rose-400 text-sm leading-none">*</span>
        </div>

        <component :is="fieldComponent" v-bind="fieldProps" @update:model-value="handleUpdate"
            @update:validation="handleValidationUpdate" @open-form-option="handleOpenFormOption"
            @sub-modal-toggle="emit('sub-modal-toggle', $event)" />
    </div>
</template>
