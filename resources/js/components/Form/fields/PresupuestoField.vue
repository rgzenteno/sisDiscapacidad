<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';

/**
 * Componentes
 */
import Input from '@/components/Input.vue';
import ModalPreviewMes from '@/components/ModalPreviewMes.vue';
import { usePresupuestoCalculation } from '../composables/usePresupuestoCalculation';

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
    }
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue', 'sub-modal-toggle']);

// ============================================================================
// INICIALIZACIÓN DE COMPOSABLES
// ============================================================================
const {
    mostrarPresupuestoAnual,
    previsualizacion,
    cargandoPreview,
    errorPreview,
    calcularPreview,
} = usePresupuestoCalculation(
    props.form,
    props.props
);

const formatCurrency = (valor) =>
    new Intl.NumberFormat('es-BO', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(valor ?? 0);

// ============================================================================
// COMPUTED - VALIDACIÓN DEL PRESUPUESTO
// ============================================================================
// PDF cargado y válido
const pdfValido = computed(() =>
    props.form.archivo_validacion?.isValid === true
);

// Monto por persona configurado por el superusuario en Configuración — ya
// no se tipea acá, así que solo se valida que exista y sea válido.
const montoConfigurado = computed(() =>
    !!props.props.montoPersonaActual && parseFloat(props.props.montoPersonaActual) > 0
);

// Ambas condiciones cumplidas para poder calcular
const presupuestoHabilitado = computed(() =>
    props.props.editMode || (pdfValido.value && montoConfigurado.value)
);

const mensajeGuia = computed(() => {
    if (props.props.editMode) return null;
    if (!montoConfigurado.value) {
        return 'El "Monto - Persona" no está configurado. Pídele a un superusuario que lo configure en Configuración.';
    }
    if (!pdfValido.value) {
        return 'Cargue el archivo PDF válido para calcular';
    }
    return null;
});

// ============================================================================
// VISTA PREVIA — botón explícito, sin autocompletar el presupuesto a ciegas
// ============================================================================
const mostrarModalPreview = ref(false);

// Mientras el detalle esté abierto, el modal de "Agregar Mes" se oculta (sin
// perder lo ya cargado) para no quedar apilado detrás — ver Form.vue.
watch(mostrarModalPreview, (abierto) => emit('sub-modal-toggle', abierto));

const handleCalcular = async () => {
    await calcularPreview();
    if (previsualizacion.value) {
        mostrarModalPreview.value = true;
    }
};

// ============================================================================
// WATCHERS
// ============================================================================
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

</script>

<template>
    <div class="w-full">
        <!-- Presupuesto Anual (solo si es primer registro del año) -->
        <div v-if="!props.props.editMode && mostrarPresupuestoAnual" class="mb-4">

            <div class="flex items-center gap-0.5 mb-1">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                    Presupuesto Anual (Bs.)
                </label>
                <span class="text-rose-400 text-sm leading-none">*</span>
            </div>

            <Input input-type="number" v-model="form.presupuesto_anual" placeholder="Ingrese el presupuesto anual"
                :customClass="field.nameStyle" :errors="form.errors.presupuesto_anual" />
        </div>

        <!-- Monto - Persona: fijo, configurado por el superusuario en Configuración -->
        <div v-if="!props.props.editMode" class="mb-4">
            <div class="flex items-center gap-0.5 mb-1">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                    Monto - Persona (Bs.)
                </label>
            </div>
            <div class="w-full px-3 py-2.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <span v-if="montoConfigurado">Bs. {{ formatCurrency(props.props.montoPersonaActual) }}</span>
                <span v-else class="text-rose-500 font-normal">No configurado — ver Configuración</span>
            </div>
        </div>

        <!-- Presupuesto Mensual -->
        <div>
            <div class="flex items-center gap-0.5 mb-1">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                    Presupuesto Mensual (Bs.)
                </label>
                <span class="text-rose-400 text-sm leading-none">*</span>
            </div>

            <!-- ✅ MODO EDICIÓN: siempre habilitado, sin lógica de PDF -->
            <template v-if="props.props.editMode">
                <Input :input-type="'number'" :model-value="modelValue" placeholder="Ingrese el presupuesto mensual"
                    :errors="error" @update:model-value="emit('update:modelValue', $event)" />
            </template>

            <!-- ✅ MODO CREACIÓN -->
            <template v-else>
                <p v-if="!presupuestoHabilitado" class="text-sm text-red-400 italic">
                    {{ mensajeGuia }}
                </p>

                <!-- Todavía no se calculó: solo el botón, sin exponer ningún número sin revisar -->
                <template v-else-if="!previsualizacion">
                    <button type="button" @click="handleCalcular" :disabled="cargandoPreview"
                        class="w-full text-sm px-3 py-2.5 rounded-lg bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))] text-white font-medium disabled:opacity-50 transition-colors">
                        {{ cargandoPreview ? 'Calculando...' : 'Calcular presupuesto y ver detalle' }}
                    </button>
                    <p v-if="errorPreview" class="text-sm text-rose-600 mt-1">{{ errorPreview }}</p>
                </template>

                <!-- Ya se calculó: presupuesto fijo (no editable), solo acceso al detalle -->
                <template v-else>
                    <p v-if="error" class="text-sm text-rose-600 mt-1">{{ error }}</p>
                    <div class="flex items-center justify-end mt-1">
                        <button type="button" @click="mostrarModalPreview = true"
                            class="text-xs font-semibold text-[rgb(var(--brand-600))] dark:text-[rgb(var(--brand-400))] hover:underline">
                            Ver detalle
                        </button>
                    </div>
                </template>

                <ModalPreviewMes v-if="mostrarModalPreview" :mes="props.props.data?.mes"
                    :año="props.props.data?.año" :resumen="previsualizacion?.resumen"
                    :detalle="previsualizacion?.detalle"
                    :presupuesto-sugerido="previsualizacion?.presupuesto_sugerido"
                    @close="mostrarModalPreview = false" />
            </template>
        </div>
    </div>
</template>
