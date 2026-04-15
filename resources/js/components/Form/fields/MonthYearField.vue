<script setup>
import Icon from '@/components/Icon.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';


const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
    form: { type: Object, required: true },
    props: { type: Object, required: true },
});

//console.log(props.modelValue);

const emit = defineEmits(['update:modelValue']);

const showPicker = ref(false);
const triggerRef = ref(null);
const pickerRef = ref(null);
const pickerStyle = ref({});

// Año y mes seleccionados
const selectedAnio = ref('');
const selectedMes = ref('');

const mesesAbrev = [
    { value: '01', label: 'Ene' },
    { value: '02', label: 'Feb' },
    { value: '03', label: 'Mar' },
    { value: '04', label: 'Abr' },
    { value: '05', label: 'May' },
    { value: '06', label: 'Jun' },
    { value: '07', label: 'Jul' },
    { value: '08', label: 'Ago' },
    { value: '09', label: 'Sep' },
    { value: '10', label: 'Oct' },
    { value: '11', label: 'Nov' },
    { value: '12', label: 'Dic' },
];

watch(() => props.modelValue, (val) => {
    if (val && String(val).includes('-')) {
        const partes = String(val).split('-');
        selectedAnio.value = partes[0];
        selectedMes.value = partes[1];
    } else {
        selectedAnio.value = '';
        selectedMes.value = '';
    }
}, { immediate: true });

// Datos del contexto
const historial = computed(() =>
    props.form?.historial_completo ||
    props.props?.existingData?.historial_completo ||
    props.props?.data?.historial_completo ||
    []
);
const mesesPagados = computed(() =>
    props.form?.meses_pagados ||
    props.props?.existingData?.meses_pagados ||
    props.props?.data?.meses_pagados ||
    []
);

// Fecha mínima: fecha_inicio del primer historial (el más antiguo)
const fechaMinima = computed(() => {
    if (!historial.value.length) return null;
    const sorted = [...historial.value].sort((a, b) =>
        new Date(a.fecha_inicio) - new Date(b.fecha_inicio)
    );
    return new Date(sorted[0].fecha_inicio);
});


// Meses disponibles pasados desde el field
const mesesDelSistema = computed(() => props.field.mesesDisponibles || []);

// Calcular meses válidos: los del sistema + 2 meses adelante
const mesesValidos = computed(() => {
    if (!mesesDelSistema.value.length) return [];

    const validos = [...mesesDelSistema.value];

    // Encontrar el último mes registrado
    const sorted = [...mesesDelSistema.value].sort((a, b) => {
        if (a.gestion !== b.gestion) return a.gestion - b.gestion;
        return a.mes - b.mes;
    });

    const ultimo = sorted[sorted.length - 1];
    let mes = parseInt(ultimo.mes);
    let anio = parseInt(ultimo.gestion);

    // Agregar 2 meses adelante
    for (let i = 0; i < 2; i++) {
        mes++;
        if (mes > 12) { mes = 1; anio++; }
        validos.push({
            gestion: String(anio),
            mes: String(mes).padStart(2, '0'),
        });
    }

    return validos;
});

// Años disponibles basados en mesesValidos
const aniosDisponibles = computed(() => {
    // Modo fecha_registro: años del sistema
    if (props.field.mesesDisponibles) {
        const años = [...new Set(mesesValidos.value.map(m => String(m.gestion)))];

        // En editMode, asegurar que el año del mes actual también aparezca
        if (props.field.mesActualEdicion) {
            const anioActual = String(props.field.mesActualEdicion).split('T')[0].split('-')[0];
            if (!años.includes(anioActual)) años.push(anioActual);
        }

        return años.sort();
    }

    // Modo estado: desde año mínimo del historial hasta el año actual
    const anioMin = fechaMinima.value
        ? fechaMinima.value.getFullYear()
        : new Date().getFullYear();
    const hoy = new Date();
    const mesMax = hoy.getMonth() + 3; // +2 meses adelante (getMonth es 0-based)
    const anioMax = mesMax > 12 ? hoy.getFullYear() + 1 : hoy.getFullYear();
    const años = [];
    for (let y = anioMin; y <= anioMax; y++) {
        años.push(String(y));
    }
    return años;
});

// Verificar si un mes está disponible para seleccionar
const isMesDisabled = (mes) => {
    // En modo edición: todos los meses disponibles sin restricciones
    if (props.field.editMode) {
        if (!selectedAnio.value) return true;

        // Verificar si está en mesesDisponibles
        const estaEnSistema = mesesValidos.value.some(
            m => String(m.gestion) === String(selectedAnio.value) && m.mes === mes
        );

        // Verificar si es el mes actual del registro editado
        const mesActual = props.field.mesActualEdicion;
        let esMesActualEdicion = false;
        if (mesActual) {
            const partes = String(mesActual).split('T')[0].split('-');
            esMesActualEdicion = partes[0] === selectedAnio.value && partes[1] === mes;
        }

        return !estaEnSistema && !esMesActualEdicion;
    }

    if (!selectedAnio.value) return true;

    // Modo fecha_registro
    if (props.field.mesesDisponibles) {
        const estaEnSistema = mesesValidos.value.some(
            m => String(m.gestion) === String(selectedAnio.value) && m.mes === mes
        );
        if (!estaEnSistema) return true;

        // En edición con 2+ estados: bloquear meses >= al primer estado
        if (props.field.fechaLimite) {
            const partes = props.field.fechaLimite.split('-');
            const anioLimite = partes[0];
            const mesLimite = partes[1];

            if (selectedAnio.value > anioLimite) return true;
            if (selectedAnio.value === anioLimite && mes >= mesLimite) return true;
        }

        return false;
    }

    // Modo estado
    const hoy = new Date();
    const anioActual = String(hoy.getFullYear());
    const mesActual = String(hoy.getMonth() + 1).padStart(2, '0');

    let mesMaximo = parseInt(mesActual) + 2;
    let anioMaximo = parseInt(anioActual);
    if (mesMaximo > 12) { mesMaximo -= 12; anioMaximo += 1; }

    const mesMaxStr = String(mesMaximo).padStart(2, '0');
    const anioMaxStr = String(anioMaximo);

    if (selectedAnio.value > anioMaxStr) return true;
    if (selectedAnio.value === anioMaxStr && mes > mesMaxStr) return true;

    if (isMesPagado(selectedAnio.value, mes)) return true;

    const ultimoEstado = [...historial.value].sort((a, b) =>
        new Date(b.fecha_inicio) - new Date(a.fecha_inicio)
    )[0];

    if (ultimoEstado) {
        const partes = String(ultimoEstado.fecha_inicio).split('T')[0].split('-');
        const anioUltimo = partes[0];
        const mesUltimo = partes[1];

        if (selectedAnio.value < anioUltimo) return true;
        if (selectedAnio.value === anioUltimo && mes <= mesUltimo) return true;
    }

    return false;
};

// Mes mínimo para el año seleccionado
const mesMinimo = computed(() => {
    if (!fechaMinima.value) return '01';
    if (selectedAnio.value === String(fechaMinima.value.getFullYear())) {
        return String(fechaMinima.value.getMonth() + 1).padStart(2, '0');
    }
    return '01';
});

// Verificar si un mes está pagado
const isMesPagado = (anio, mes) => {
    return mesesPagados.value.some(
        p => String(p.gestion) === String(anio) && String(p.mes).padStart(2, '0') === mes
    );
};

const mesesCompletos = [
    { value: '01', label: 'Enero' },
    { value: '02', label: 'Febrero' },
    { value: '03', label: 'Marzo' },
    { value: '04', label: 'Abril' },
    { value: '05', label: 'Mayo' },
    { value: '06', label: 'Junio' },
    { value: '07', label: 'Julio' },
    { value: '08', label: 'Agosto' },
    { value: '09', label: 'Septiembre' },
    { value: '10', label: 'Octubre' },
    { value: '11', label: 'Noviembre' },
    { value: '12', label: 'Diciembre' },
];

// Cambia displayValue para usar nombre completo
const displayValue = computed(() => {
    if (!selectedAnio.value || !selectedMes.value) return '';
    const m = mesesCompletos.find(m => m.value === selectedMes.value);
    return `${m?.label || ''} ${selectedAnio.value}`;
});

const openPicker = () => {
    // Si no hay año seleccionado aún, precargar con el actual
    if (!selectedAnio.value) {
        const hoy = new Date();
        selectedAnio.value = String(hoy.getFullYear());
    }

    const rect = triggerRef.value?.getBoundingClientRect();
    if (rect) {
        pickerStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
            width: `${Math.max(rect.width, 240)}px`,
            zIndex: 9999,
        };
    }
    showPicker.value = !showPicker.value;
};

// Mes del estado actual (el más reciente del historial)
const mesEstadoActual = computed(() => {
    if (!historial.value.length) return null;
    const reciente = [...historial.value].sort((a, b) =>
        new Date(b.fecha_inicio) - new Date(a.fecha_inicio)
    )[0];
    const partes = String(reciente.fecha_inicio).split('T')[0].split('-');
    return { anio: partes[0], mes: partes[1] };
});

const isMesEstadoActual = (anio, mes) => {
    if (!mesEstadoActual.value) return false;
    return String(mesEstadoActual.value.anio) === String(anio) &&
        mesEstadoActual.value.mes === mes;
};

const getEstadoMes = (anio, mes) => {
    if (!historial.value.length) return null;

    for (const h of historial.value) {
        const inicio = String(h.fecha_inicio).split('T')[0].split('-');
        const anioInicio = inicio[0];
        const mesInicio = inicio[1];

        const fin = h.fecha_fin
            ? String(h.fecha_fin).split('T')[0].split('-')
            : null;
        const anioFin = fin?.[0];
        const mesFin = fin?.[1];

        const desdeInicio = anio > anioInicio || (anio === anioInicio && mes >= mesInicio);
        const hastaFin = fin
            ? anio < anioFin || (anio === anioFin && mes <= mesFin)
            : anio === anioInicio && mes === mesInicio;

        if (desdeInicio && hastaFin) return h.estado;
    }
    return null;
};

const selectMes = (mes) => {
    if (isMesDisabled(mes)) return;
    selectedMes.value = mes;
    // Siempre día 01
    emit('update:modelValue', `${selectedAnio.value}-${mes}-01`);
    showPicker.value = false;
};

const handleClickOutside = (e) => {
    if (
        pickerRef.value && !pickerRef.value.contains(e.target) &&
        triggerRef.value && !triggerRef.value.contains(e.target)
    ) {
        showPicker.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="mb-1">
        <label v-if="field.label" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ field.label }}
            <span class="text-red-600">*</span>
        </label>

        <div ref="triggerRef" @click="openPicker" class="relative cursor-pointer">
            <input readonly :value="displayValue" :placeholder="field.placeholder || 'Seleccionar mes y gestión'"
                :class="[
                    'w-full bg-gray-50 border shadow-sm text-gray-900 text-sm rounded-lg p-2 cursor-pointer',
                    error ? 'border-red-500 text-red-500' : 'border-gray-300 hover:border-blue-400',
                    'dark:bg-gray-600 dark:border-gray-500 dark:text-white'
                ]" />
            <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                        clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>

        <Teleport to="body">
            <Transition name="date-modal">
                <div v-if="showPicker" ref="pickerRef" :style="pickerStyle"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-3">

                    <!-- Selector de año -->
                    <div class="flex gap-1 mb-3 overflow-x-auto pb-1">
                        <button v-for="anio in aniosDisponibles" :key="anio" type="button"
                            @click="selectedAnio = anio; selectedMes = ''" :class="[
                                'px-3 py-1 rounded-lg text-sm font-medium whitespace-nowrap transition-colors',
                                selectedAnio === anio
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200'
                            ]">
                            {{ anio }}
                        </button>
                    </div>

                    <!-- Grid de meses -->
                    <div class="grid grid-cols-4 gap-1">

                        <button v-for="mes in mesesAbrev" :key="mes.value" type="button" @click="selectMes(mes.value)"
                            :disabled="isMesDisabled(mes.value)"
                            :title="isMesPagado(selectedAnio, mes.value) ? 'Pagado ✓' :
                                isMesDisabled(mes.value) ? (getEstadoMes(selectedAnio, mes.value) === 'activo' ? 'Activo' :
                                    getEstadoMes(selectedAnio, mes.value) === 'baja_temporal' ? 'Baja Temporal' :
                                        getEstadoMes(selectedAnio, mes.value) === 'baja_definitiva' ? 'Baja Definitiva' : 'No disponible') : ''" :class="[
                                            'py-2 rounded-lg text-xs font-medium transition-colors relative',
                                            isMesPagado(selectedAnio, mes.value)
                                                ? 'bg-green-100 text-green-500 cursor-not-allowed'
                                                : getEstadoMes(selectedAnio, mes.value) === 'activo'
                                                    ? 'bg-green-50 text-green-600 cursor-not-allowed ring-1 ring-green-300'
                                                    : getEstadoMes(selectedAnio, mes.value) === 'baja_temporal'
                                                        ? 'bg-orange-50 text-orange-500 cursor-not-allowed ring-1 ring-orange-300'
                                                        : getEstadoMes(selectedAnio, mes.value) === 'baja_definitiva'
                                                            ? 'bg-red-50 text-red-500 cursor-not-allowed ring-1 ring-red-300'
                                                            : isMesDisabled(mes.value)
                                                                ? 'bg-gray-50 text-gray-300 cursor-not-allowed dark:bg-gray-700 dark:text-gray-600'
                                                                : 'bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-600 dark:bg-gray-700 dark:text-gray-200'
                                        ]">

                            {{ mes.label }}

                            <span v-if="!isMesPagado(selectedAnio, mes.value)
                                && isMesDisabled(mes.value)
                                && getEstadoMes(selectedAnio, mes.value)"
                                class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full pointer-events-none" :class="{
                                    'bg-emerald-500': getEstadoMes(selectedAnio, mes.value) === 'activo',
                                    'bg-amber-500': getEstadoMes(selectedAnio, mes.value) === 'baja_temporal',
                                    'bg-red-500': getEstadoMes(selectedAnio, mes.value) === 'baja_definitiva',
                                }">
                            </span>

                            <!-- Icono pagado -->
                            <Icon v-if="isMesPagado(selectedAnio, mes.value)" name="cash" :size="15" :height="15"
                                class="absolute top-1 right-0"
                                :fill="selectedMes === mes.value && selectedAnio ? 'white' : '#0d9488'" icon-button />
                        </button>
                    </div>

                    <p v-if="!selectedAnio" class="text-xs text-gray-400 text-center mt-2">
                        Selecciona una gestión primero
                    </p>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.date-modal-enter-active,
.date-modal-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.date-modal-enter-from,
.date-modal-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
