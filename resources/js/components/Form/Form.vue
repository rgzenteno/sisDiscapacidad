<script setup>
// ============ INICIO IMPORTS ============ //
import { computed } from 'vue';

// Layout Components
import ModalWrapper from './layout/ModalWrapper.vue';
import FormHeader from './layout/FormHeader.vue';
import FormActions from './layout/FormActions.vue';

// Main Components
import FormField from './FormField.vue';

// Composables
import { useFormState } from './composables/useFormState';
import { useFormSubmit } from './composables/useFormSubmit';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    fields: {
        type: Array,
        default: () => []
    },
    distritos: {
        type: Array,
        default: () => []
    },
    discapacidad: {
        type: Array,
        default: () => []
    },
    text: String,
    gestion: {
        type: [Object, Array],
        default: () => []
    },
    submitLabel: String,
    submitRoute: String,
    dataGestion: {
        type: Array,
        default: () => []
    },
    personasValidas: Number,
    presupuestosAnuales: {
        type: Object,
        default: () => ({})
    },
    soli: String,
    showModalCreate: Boolean,
    idFor: {
        type: [Number, String]
    },
    nombreFor: String,
    fechaNacimiento: String,
    ciFor: String,
    required: Boolean,
    claveForanea: String,
    addTutor: Boolean,
    botonOmitir: Boolean,
    tutores: {
        type: Array,
        default: () => []
    },
    editMode: {
        type: Boolean,
        default: false
    },
    existingData: {
        type: [Object, Array],
        default: () => ({})
    },
    permissions: {
        type: Array,
        default: () => []
    },
    acceptedFileTypes: {
        type: String,
        default: '.xlsx,.xls,.csv'
    },
    maxFileSize: {
        type: Number,
        default: 5 // MB
    },
    botonName: {
        type: String,
        default: "Agregar"
    },
    importing: {
        type: Boolean,
        default: false
    },
    importingText: {
        type: String,
        default: 'Importando...'
    },
    keepButtonText: { // ✅ NUEVA PROP
        type: Boolean,
        default: false
    }
});
// ============ FIN PROPS ============ //
//console.log('datos', props);
// ============ INICIO EMITS ============ //
const emit = defineEmits([
    'add',
    'close',
    'cancel',
    'omitir',
    'sinDatos',
    'searchTutor',
    'encontrado',
    'noEncontrado',
    'mesExiste',
    'mesDelante',
    'tutorEncontradoSms',
    'fechaInvalida',
    'openFormOption'
]);
// ============ FIN EMITS ============ //

// ============ INICIO COMPUTED ============ //
const visibleFields = computed(() =>
    props.fields.filter(field => !field.hidden)
);

const gridClasses = computed(() => {
    const hasPermissions = visibleFields.value.some(f => f.typeInput === 'checkbox_permissions');
    const fieldCount = visibleFields.value.length;

    // Si tiene permisos, siempre 1 columna (los permisos usan col-span-full)
    if (hasPermissions) {
        return 'gap-2 grid-cols-1 mb-2';
    }

    // Si tiene muchos campos pero NO permisos, usa 2 columnas
    if (fieldCount >= 6) {
        return 'gap-2 grid-cols-1 lg:grid-cols-2 mb-2';
    }

    // Default: 1 columna
    return 'gap-2 grid-cols-1';
});

const contentClasses = computed(() => {
    const hasPermissions = visibleFields.value.some(f => f.typeInput === 'checkbox_permissions');
    const fieldCount = visibleFields.value.length;

    if (hasPermissions || fieldCount >= 6) {
        return 'p-6 max-h-[calc(100vh-200px)]';
    }
    return 'p-6 max-h-[70vh]';
});

const modalMaxWidth = computed(() => {
    const hasPermissions = visibleFields.value.some(f => f.typeInput === 'checkbox_permissions');
    const fieldCount = visibleFields.value.length;

    if (hasPermissions || fieldCount >= 6) {
        return 'max-w-2xl';
    }
    return 'max-w-md';
});
// ============ FIN COMPUTED ============ //

// ============ INICIO COMPOSABLES ============ //
// Estado del formulario
const { form, updateField, search } = useFormState(props, emit);

// Lógica de submit
const { handleSubmit, handleCancel, handleOmitir } = useFormSubmit(form, props, emit);

// Shortcuts de teclado (ESC para cerrar)
useKeyboardShortcuts(handleCancel);
// ============ FIN COMPOSABLES ============ //
</script>

<template>
    <div
        class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-40 px-4 py-6 overflow-y-auto backdrop-blur-sm">
        <ModalWrapper :max-width="modalMaxWidth">
            <form @submit.prevent="handleSubmit">
                <!-- Header del Modal -->
                <FormHeader @close="handleCancel">
                    <!-- Propagar slots explícitamente -->
                    <template #icon>
                        <slot name="icon">
                            <!-- Fallback por defecto -->
                            <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </slot>
                    </template>

                    <template #label1>
                        <slot name="label1">Título por defecto</slot>
                    </template>

                    <template #label2>
                        <slot name="label2">Subtítulo por defecto</slot>
                    </template>
                </FormHeader>

                <!-- Body del Formulario -->
                <div :class="['overflow-y-auto', contentClasses]">
                    <div :class="['grid', gridClasses]">
                        <FormField v-for="(field, index) in visibleFields" :key="field.name || index" :field="field"
                            :nombreFor="props.nombreFor" :model-value="form[field.name]"
                            :error="form.errors[field.name]" :form="form" :props="props"
                            @update:model-value="updateField(field.name, $event)"
                            @open-form-option="emit('openFormOption')" />
                    </div>
                </div>

                <!-- Footer con Botones de Acción -->
                <FormActions :processing="form.processing" :boton-name="botonName" :edit-mode="editMode"
                    :existing-data="existingData" :show-omitir="botonOmitir" :soli="soli"
                    :field-count="visibleFields.length" :importing="importing" :importing-text="importingText"
                    :tutor-found="search" :keep-button-text="keepButtonText" @submit="handleSubmit"
                    @cancel="handleCancel" @omitir="handleOmitir" />
            </form>
        </ModalWrapper>
    </div>
</template>
