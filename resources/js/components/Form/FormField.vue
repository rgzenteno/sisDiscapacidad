<script setup>
// ============ INICIO IMPORTS ============ //
import { computed } from 'vue';

// Field Components
import TextField from './fields/TextField.vue';
import TextareaField from './fields/TextareaField.vue';
import SelectField from './fields/SelectField.vue';
import CedulaField from './fields/CedulaField.vue';
import CheckboxGroupField from './fields/CheckboxGroupField.vue';
import CheckboxPermissions from './fields/CheckboxPermissions.vue';
import FileUpload from './fields/FileUpload.vue';
import PresupuestoField from './fields/PresupuestoField.vue';
import TimeField from './fields/TimeField.vue';
import LabelField from './fields/LabelField.vue';
import ReadOnlyField from './fields/ReadOnlyField.vue';
import CodigoCarnetField from './fields/CodigoCarnetField.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
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
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits([
    'update:modelValue',
    'openFormOption'
]);
// ============ FIN EMITS ============ //

// ============ INICIO COMPUTED ============ //
// Mapeo de tipos de input a componentes
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
};

// Componente a renderizar basado en el tipo de campo
const fieldComponent = computed(() => {
    const typeInput = props.field.typeInput || props.field.type;

    // Para campos de presupuesto
    if (props.field.name === 'presupuesto') {
        return PresupuestoField;
    }

    // Para campos de cédula
    if (props.field.typeCi === 'ci' && props.field.typeInput === 'cedula') {
        return CedulaField;
    }

    return fieldComponents[typeInput] || TextField;
});

// Props a pasar al componente hijo
const fieldProps = computed(() => ({
    field: props.field,
    modelValue: props.modelValue,
    error: props.error,
    form: props.form,
    props: props.props,
    nombreFor: props.nombreFor
}));
// ============ FIN COMPUTED ============ //

// ============ INICIO MÉTODOS ============ //
const handleUpdate = (value) => {
    emit('update:modelValue', value);
};

const handleOpenFormOption = () => {
    emit('openFormOption');
};
// ============ FIN MÉTODOS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Renderizado Dinámico del Componente -->
        <component :is="fieldComponent" v-bind="fieldProps" @update:model-value="handleUpdate"
            @open-form-option="handleOpenFormOption" />
    </div>
</template>
