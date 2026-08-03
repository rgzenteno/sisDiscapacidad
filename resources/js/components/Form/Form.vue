<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';

/**
 * Componentes
 */
import FormActions from './layout/FormActions.vue';
import FormField from './FormField.vue';
import Modal from '../Modal.vue';

/**
 * Composables
 */
import { useFormState } from './composables/useFormState';
import { useFormSubmit } from './composables/useFormSubmit';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import { tieneCamposRequeridosCompletos } from './composables/useFormValidation';

// ============================================================================
// PROPS
// ============================================================================
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
    montoPersonaActual: {
        type: [String, Number],
        default: null
    },
    soli: String,
    showModalCreate: Boolean,
    idFor: {
        type: [Number, String]
    },
    nombreFor: String,
    apellidoFor: {
        type: String,
        default: null
    },
    ciFor: {
        type: String,
        default: null
    },
    fechaNacimiento: String,
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
        default: 5
    },
    botonName: {
        type: String,
        default: 'Agregar'
    },
    importing: {
        type: Boolean,
        default: false
    },
    importingText: {
        type: String,
        default: 'Importando...'
    },
    keepButtonText: {
        type: Boolean,
        default: false
    },
    cisPersonasActivas: {
        type: Array,
        default: () => []
    },
    cisTodasPersonas: {
        type: Array,
        default: () => []
    },
    // Deshabilita el botón de enviar mientras falten campos requeridos, en
    // vez de avisar recién al hacer clic. Activado por defecto para que
    // "Agregar" tenga el mismo comportamiento que "Editar" en todos los
    // formularios; un formulario puntual puede pasar `:disable-until-valid="false"`
    // si necesita el comportamiento anterior.
    disableUntilValid: {
        type: Boolean,
        default: true
    },
});

// ============================================================================
// EMITS
// ============================================================================
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
    'openFormOption',
    'indefinidoChange',
    'importingStart',
    'importingEnd',
    'carnetInvalido'
]);

// ============================================================================
// REFS
// ============================================================================
const currentFileValidation = ref({});

// Un campo puede abrir su propio modal secundario (ej. vista previa del PDF
// en PresupuestoField) — mientras esté abierto, este modal se oculta (sin
// desmontarse, para no perder lo ya ingresado) en vez de quedar apilado
// detrás.
const subModalAbierto = ref(false);

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Retorna solo los campos visibles del formulario
 */
const visibleFields = computed(() =>
    props.fields.filter(field => !field.hidden)
);

/**
 * Clases CSS del grid según tipo y cantidad de campos visibles
 */
const gridClasses = computed(() => {
    const hasPermissions = visibleFields.value.some(f => f.typeInput === 'checkbox_permissions');
    const fieldCount = visibleFields.value.length;

    if (hasPermissions) return 'gap-2 grid-cols-1 mb-2';
    if (fieldCount >= 7) return 'gap-2 grid-cols-1 lg:grid-cols-2 mb-2';
    return 'gap-2 grid-cols-1';
});

/**
 * Clases CSS del contenedor según tipo y cantidad de campos visibles
 */
const contentClasses = computed(() => {
    const hasPermissions = visibleFields.value.some(f => f.typeInput === 'checkbox_permissions');
    const fieldCount = visibleFields.value.length;

    if (hasPermissions || fieldCount >= 7) {
        return 'px-5 py-2 sm:p-6 max-h-[calc(100vh-200px)]';
    }
    return 'px-5 py-2 max-h-[70vh]';
});

/**
 * Si el formulario tiene un campo de presupuesto en modo creación (el flujo
 * de "Agregar Mes"), no se puede guardar hasta que el presupuesto esté
 * realmente calculado — ver PresupuestoField.vue, que lo deja vacío hasta
 * que el usuario presiona "Calcular" y revisa el detalle. Sin esto, el botón
 * de guardar quedaba habilitado apenas se cargaba el PDF y el monto, antes
 * de calcular nada, y confirmar en ese momento fallaba.
 */
const presupuestoPendiente = computed(() => {
    if (props.editMode) return false;
    const tienePresupuesto = props.fields.some(f => f.typeInput === 'presupuesto' || f.name === 'presupuesto');
    return tienePresupuesto && !form.presupuesto;
});

/**
 * Bloqueo extra opt-in (ver prop `disableUntilValid`): además del
 * presupuesto pendiente, deshabilita el botón mientras falten campos
 * requeridos por completar.
 */
const bloqueoExtra = computed(() => {
    if (presupuestoPendiente.value) return true;
    if (!props.disableUntilValid) return false;
    return !tieneCamposRequeridosCompletos(props.fields, form);
});

/**
 * Determina si el tutor marcado es el propio beneficiario
 */
const esPropioTutor = computed(() => {
    const propioField = props.fields.find(f => f.typeInput === 'propio_check');
    return propioField ? form[propioField.name] == 1 : false;
});

/**
 * Retorna el ancho máximo del modal según la cantidad y tipo de campos
 */
const modalMaxWidth = computed(() => {
    const hasPermissions = visibleFields.value.some(f => f.typeInput === 'checkbox_permissions');
    const fieldCount = visibleFields.value.length;

    if (hasPermissions || fieldCount >= 7) return 'max-w-2xl';
    return 'max-w-md';
});

/**
 * Extiende los props con los registros extraídos de la validación del archivo PDF
 */
const propsConRegistros = computed(() => ({
    ...props,
    registrosPdf: currentFileValidation.value?.extractedData?.registros || []
}));

// ============================================================================
// COMPOSABLES
// ============================================================================

/**
 * Estado del formulario: campos, búsqueda y control de cambios
 */
const { form, updateField, search, isDirty, resetToInitial } = useFormState(props, emit);

/**
 * Lógica de envío, cancelación y omisión del formulario
 */
const { handleSubmit, handleCancel, handleOmitir } = useFormSubmit(form, props, emit);

// Registra el shortcut ESC para cerrar el formulario — no si hay un modal
// secundario abierto encima (ese debe cerrarse primero con su propio ESC,
// sin cancelar este formulario y perder lo ya cargado).
useKeyboardShortcuts(() => {
    if (!subModalAbierto.value) handleCancel();
});

// ============================================================================
// FUNCIONES
// ============================================================================

/**
 * Captura y almacena el resultado de validación del archivo cargado
 * @param {Object} validation - Resultado de validación del componente de archivo
 */
const handleValidationUpdate = (validation) => {
    currentFileValidation.value = validation;
};
</script>

<template>
    <Modal :show-header="true" :show-footer="false" :max-width="modalMaxWidth" :style-body="contentClasses"
        :hidden="subModalAbierto || importing" @close="handleCancel">
        <template #icon>
            <slot name="icon">
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

        <!-- Body -->
        <form id="main-form" @submit.prevent="handleSubmit(currentFileValidation)">
            <div :class="['grid', gridClasses]">
                <TransitionGroup name="field">
                    <FormField v-for="(field, index) in visibleFields" :key="field.name || index" :field="field"
                        :nombreFor="props.nombreFor" :apellidoFor="props.apellidoFor" :ciFor="props.ciFor" :model-value="form[field.name]" :error="form.errors[field.name]"
                        :form="form" :props="propsConRegistros" @update:model-value="updateField(field.name, $event)"
                        @update:validation="handleValidationUpdate" @open-form-option="emit('openFormOption')"
                        @sub-modal-toggle="subModalAbierto = $event" />
                </TransitionGroup>
            </div>
        </form>

        <!-- Footer independiente pero vinculado al form por form="main-form" -->
        <template #footer>
            <FormActions form="main-form" :processing="form.processing" :boton-name="botonName" :edit-mode="editMode"
                :existing-data="existingData" :show-omitir="botonOmitir" :soli="soli"
                :field-count="visibleFields.length" :importing="importing" :importing-text="importingText"
                :tutor-found="search" :keep-button-text="keepButtonText" :is-dirty="isDirty"
                :es-propio-tutor="esPropioTutor" :extra-disabled="bloqueoExtra" @reset="resetToInitial"
                @submit="handleSubmit" @cancel="handleCancel" @omitir="handleOmitir" />
        </template>
    </Modal>
</template>
