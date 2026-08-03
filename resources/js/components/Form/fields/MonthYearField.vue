<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS Y EMITS
// ============================================================================
const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
    form: { type: Object, required: true },
    props: { type: Object, required: true },
});

const emit = defineEmits(['update:modelValue']);

// ============================================================================
// REFS - PICKER
// ============================================================================
const showPicker = ref(false);
const triggerRef = ref(null);
const pickerRef = ref(null);
const pickerStyle = ref({});

// ============================================================================
// REFS - SELECCIÓN CONFIRMADA
// ============================================================================
const confirmedAnio = ref('');
const confirmedMes = ref('');

// ============================================================================
// REFS - SELECCIÓN EN CURSO
// ============================================================================
const selectedAnio = ref('');
const selectedMes = ref('');

// ============================================================================
// DATOS ESTÁTICOS
// ============================================================================
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

// ============================================================================
// WATCHERS
// ============================================================================

/**
 * Sincroniza la selección interna cuando el modelValue cambia externamente
 */
watch(() => props.modelValue, (val) => {
    if (val && String(val).includes('-')) {
        const partes = String(val).split('-');
        selectedAnio.value = partes[0];
        selectedMes.value = partes[1];
        confirmedAnio.value = partes[0];
        confirmedMes.value = partes[1];
    } else {
        selectedAnio.value = '';
        selectedMes.value = '';
        confirmedAnio.value = '';
        confirmedMes.value = '';
    }
}, { immediate: true });

// ============================================================================
// COMPUTED - CONTEXTO DEL FORMULARIO
// ============================================================================

/**
 * Historial de estados del beneficiario, resuelto desde múltiples fuentes posibles
 */
const historial = computed(() =>
    props.form?.historial_completo ||
    props.props?.existingData?.historial_completo ||
    props.props?.data?.historial_completo ||
    []
);

/**
 * Meses ya pagados del beneficiario, resueltos desde múltiples fuentes posibles
 */
const mesesPagados = computed(() => {
    const pagados =
        props.form?.meses_pagados ??
        props.props?.existingData?.meses_pagados ??
        props.props?.data?.meses_pagados ??
        [];
    return Array.isArray(pagados) ? pagados : [];
});

/**
 * Modo "insertar estado intermedio": limita el picker a un rango fijo
 * (el segmento existente que se va a partir), en vez de las reglas de
 * fecha_registro o de la línea de tiempo completa.
 */
const modoIntermedio = computed(() => !!props.field.modoIntermedio);

const parsePartesFecha = (fecha) => {
    if (!fecha) return null;
    const [anio, mes] = String(fecha).split('T')[0].split('-');
    return { anio, mes };
};

const rangoBaseInicio = computed(() => parsePartesFecha(props.field.rangoBase?.inicio));
const rangoBaseFin = computed(() => parsePartesFecha(props.field.rangoBase?.fin));

// ============================================================================
// COMPUTED - DISPONIBILIDAD DE MESES
// ============================================================================

/**
 * Fecha mínima permitida: fecha_inicio del estado más antiguo del historial
 */
const fechaMinima = computed(() => {
    if (!historial.value.length) return null;
    const sorted = [...historial.value].sort((a, b) =>
        new Date(a.fecha_inicio) - new Date(b.fecha_inicio)
    );
    return new Date(sorted[0].fecha_inicio);
});

/**
 * Meses disponibles pasados desde el campo del formulario
 */
const mesesDelSistema = computed(() => props.field.mesesDisponibles || []);

/**
 * Meses válidos para seleccionar: los del sistema más el mes extra (el
 * siguiente al último registrado, para marcar bajas antes de que llegue
 * su PDF). Nunca más de un mes por delante del último real.
 */
const mesesValidos = computed(() => {
    if (!mesesDelSistema.value.length) return [];

    const validos = [...mesesDelSistema.value];

    const sorted = [...mesesDelSistema.value].sort((a, b) => {
        if (a.gestion !== b.gestion) return a.gestion - b.gestion;
        return a.mes - b.mes;
    });

    const ultimo = sorted[sorted.length - 1];
    let mes = parseInt(ultimo.mes) + 1;
    let anio = parseInt(ultimo.gestion);
    if (mes > 12) { mes = 1; anio++; }

    validos.push({
        gestion: String(anio),
        mes: String(mes).padStart(2, '0'),
    });

    return validos;
});

/**
 * Años disponibles según el modo activo (fecha_registro o estado)
 */
const aniosDisponibles = computed(() => {
    if (modoIntermedio.value) {
        const desde = Number(rangoBaseInicio.value?.anio ?? new Date().getFullYear());
        const hasta = rangoBaseFin.value ? Number(rangoBaseFin.value.anio) : desde + 2;
        const años = [];
        for (let y = desde; y <= hasta; y++) años.push(String(y));
        return años;
    }

    if (props.field.mesesDisponibles) {
        const años = [...new Set(mesesValidos.value.map(m => String(m.gestion)))];

        if (props.field.mesActualEdicion) {
            const anioActual = String(props.field.mesActualEdicion).split('T')[0].split('-')[0];
            if (!años.includes(anioActual)) años.push(anioActual);
        }

        return años.sort();
    }

    const anioMin = fechaMinima.value
        ? fechaMinima.value.getFullYear()
        : new Date().getFullYear();
    const hoy = new Date();
    const mesMax = hoy.getMonth() + 3;
    const anioMax = mesMax > 12 ? hoy.getFullYear() + 1 : hoy.getFullYear();
    const años = [];
    for (let y = anioMin; y <= anioMax; y++) {
        años.push(String(y));
    }
    return años;
});

/**
 * Indica si existen meses disponibles para editar sin conflicto con pagos registrados
 */
const hayMesesDisponiblesEnEdicion = computed(() => {
    if (!props.field.editMode) return true;
    if (!mesesPagados.value.length) return true;

    const pagadosNormalizados = mesesPagados.value.map(p => ({
        gestion: String(p.gestion),
        mes: String(p.mes).padStart(2, '0')
    }));

    const primerPago = [...pagadosNormalizados].sort((a, b) => {
        if (a.gestion !== b.gestion) return Number(a.gestion) - Number(b.gestion);
        return Number(a.mes) - Number(b.mes);
    })[0];

    if (!primerPago) return true;

    const mesActual = props.field.mesActualEdicion;
    if (mesActual) {
        const partes = String(mesActual).split('T')[0].split('-');
        if (
            partes[0] < primerPago.gestion ||
            (partes[0] === primerPago.gestion && partes[1] < primerPago.mes)
        ) return true;
    }

    return mesesValidos.value.some(m => {
        const anio = String(m.gestion);
        const mes = String(m.mes).padStart(2, '0');
        if (anio < primerPago.gestion) return true;
        if (anio === primerPago.gestion && mes < primerPago.mes) return true;
        return false;
    });
});

/**
 * Mes mínimo seleccionable para el año actualmente seleccionado
 */
const mesMinimo = computed(() => {
    if (!fechaMinima.value) return '01';
    if (selectedAnio.value === String(fechaMinima.value.getFullYear())) {
        return String(fechaMinima.value.getMonth() + 1).padStart(2, '0');
    }
    return '01';
});

/**
 * Mes del estado más reciente del historial del beneficiario
 */
const mesEstadoActual = computed(() => {
    if (!historial.value.length) return null;
    const reciente = [...historial.value].sort((a, b) =>
        new Date(b.fecha_inicio) - new Date(a.fecha_inicio)
    )[0];
    const partes = String(reciente.fecha_inicio).split('T')[0].split('-');
    return { anio: partes[0], mes: partes[1] };
});

/**
 * Texto visible en el campo del picker con el mes y año confirmados
 */
const displayValue = computed(() => {
    if (!confirmedAnio.value || !confirmedMes.value) return '';
    const m = mesesCompletos.find(m => m.value === confirmedMes.value);
    return `${m?.label || ''} ${confirmedAnio.value}`;
});

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Verifica si un mes corresponde al mes actualmente asignado en modo edición
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {boolean}
 */
const esMesActualEdicion = (mes) => {
    if (!props.field.editMode || !props.field.mesActualEdicion) return false;
    const partes = String(props.field.mesActualEdicion).split('T')[0].split('-');
    return partes[0] === selectedAnio.value && partes[1] === mes;
};

/**
 * Retorna el texto del tooltip para un mes según su estado
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {string} Texto descriptivo del estado del mes
 */
const getTitleMes = (mes) => {
    if (esMesActualEdicion(mes)) return '';
    if (isMesAnulado(selectedAnio.value, mes)) return 'Pago anulado';
    if (isMesRetro(selectedAnio.value, mes)) return 'Pago retroactivo ✓';
    if (isMesPagado(selectedAnio.value, mes)) return 'Pagado ✓';
    if (isMesDisabled(mes)) {
        const estado = getEstadoMes(selectedAnio.value, mes);
        if (estado === 'activo') return 'Activo';
        if (estado === 'baja_temporal') return 'Baja Temporal';
        if (estado === 'baja_definitiva') return 'Baja Definitiva';
        if (estado === 'depurado') return 'Depurado';
        if (estado === 'pagos_suspendidos') return 'Pagos Suspendidos';
        return 'No disponible';
    }
    return '';
};

/**
 * Determina si un mes debe estar deshabilitado según el modo y contexto activo
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {boolean} true si el mes no es seleccionable
 */
const isMesDisabled = (mes) => {
    if (modoIntermedio.value) {
        if (!selectedAnio.value) return true;

        // El propio mes de inicio del segmento base ya tiene su estado (no
        // es "intermedio", es el borde) — se bloquea. getEstadoMes también
        // bloquea el mes en que arranca el siguiente estado (el otro borde).
        if (getEstadoMes(selectedAnio.value, mes)) return true;

        const inicio = rangoBaseInicio.value;
        const fin = rangoBaseFin.value;

        if (inicio) {
            if (selectedAnio.value < inicio.anio) return true;
            if (selectedAnio.value === inicio.anio && mes < inicio.mes) return true;
        }

        if (fin) {
            if (selectedAnio.value > fin.anio) return true;
            if (selectedAnio.value === fin.anio && mes > fin.mes) return true;
        }

        // Un mes ya pagado siempre debe quedar como "activo": no se puede
        // insertar otro estado ahí. Un mes con pago anulado sí se puede
        // elegir: el backend reconcilia automáticamente si el mes siguiente
        // ya tiene un pago vigente (ver HistorialEstadoService::insertarIntermedio).
        if (isMesPagado(selectedAnio.value, mes)) return true;

        return false;
    }

    if (props.field.editMode) {
        if (!selectedAnio.value) return true;

        const mesActual = props.field.mesActualEdicion;
        if (mesActual) {
            const partes = String(mesActual).split('T')[0].split('-');
            if (partes[0] === selectedAnio.value && partes[1] === mes) return false;
        }

        // ✅ NUEVO: si el mes ya tiene un estado registrado en el historial, no es seleccionable
        if (getEstadoMes(selectedAnio.value, mes)) return true;

        // ✅ FIX: si el mes ya tiene un pago registrado, tampoco es seleccionable
        if (isMesPagado(selectedAnio.value, mes)) return true;

        // ✅ NUEVO: si el pago de ese mes fue anulado, tampoco es seleccionable
        if (isMesAnulado(selectedAnio.value, mes)) return true;

        const pagadosNormalizados = mesesPagados.value.map(p => ({
            gestion: String(p.gestion),
            mes: String(p.mes).padStart(2, '0')
        }));

        const primerPago = [...pagadosNormalizados].sort((a, b) => {
            if (a.gestion !== b.gestion) return Number(a.gestion) - Number(b.gestion);
            return Number(a.mes) - Number(b.mes);
        })[0];

        if (primerPago) {
            if (selectedAnio.value > primerPago.gestion) return true;
            if (selectedAnio.value === primerPago.gestion && mes >= primerPago.mes) return true;
        }

        const estaEnSistema = mesesValidos.value.some(
            m => String(m.gestion) === String(selectedAnio.value) && m.mes === mes
        );
        return !estaEnSistema;
    }

    if (!selectedAnio.value) return true;

    // Modo fecha_registro
    if (props.field.mesesDisponibles) {
        const estaEnSistema = mesesValidos.value.some(
            m => String(m.gestion) === String(selectedAnio.value) && m.mes === mes
        );
        if (!estaEnSistema) return true;

        // ✅ NUEVO
        if (getEstadoMes(selectedAnio.value, mes)) return true;

        // ✅ FIX: faltaba esta verificación
        if (isMesPagado(selectedAnio.value, mes)) return true;

        // Un mes con pago anulado sí se puede elegir para agregar un nuevo
        // estado: el backend reconcilia automáticamente si el mes siguiente
        // ya tiene un pago vigente (ver HistorialEstadoService::agregar).

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
    if (isMesAnulado(selectedAnio.value, mes)) return true;

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

/**
 * Verifica si un mes ya fue pagado (con un pago vigente, no anulado)
 * @param {string} anio - Año en formato 'YYYY'
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {boolean}
 */
const isMesPagado = (anio, mes) => {
    return mesesPagados.value.some(
        p => String(p.gestion) === String(anio) && String(p.mes).padStart(2, '0') === mes && !p.anulado
    );
};

/**
 * Verifica si el pago registrado para un mes fue anulado
 * @param {string} anio - Año en formato 'YYYY'
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {boolean}
 */
const isMesAnulado = (anio, mes) => {
    return mesesPagados.value.some(
        p => String(p.gestion) === String(anio) && String(p.mes).padStart(2, '0') === mes && p.anulado
    );
};

/**
 * Verifica si el pago registrado para un mes fue un pago retroactivo (no anulado)
 * @param {string} anio - Año en formato 'YYYY'
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {boolean}
 */
const isMesRetro = (anio, mes) => {
    return mesesPagados.value.some(
        p => String(p.gestion) === String(anio) && String(p.mes).padStart(2, '0') === mes && p.es_retroactivo && !p.anulado
    );
};

/**
 * Verifica si un mes corresponde al estado activo más reciente del historial
 * @param {string} anio - Año en formato 'YYYY'
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {boolean}
 */
const isMesEstadoActual = (anio, mes) => {
    if (!mesEstadoActual.value) return false;
    return String(mesEstadoActual.value.anio) === String(anio) &&
        mesEstadoActual.value.mes === mes;
};

/**
 * Retorna el estado del beneficiario vigente en un mes y año dado
 * @param {string} anio - Año en formato 'YYYY'
 * @param {string} mes - Número del mes en formato 'MM'
 * @returns {string|null} Clave del estado o null si no se encuentra
 */
const getEstadoMes = (anio, mes) => {
    // En modo intermedio no se usa el historial completo: solo interesan
    // los dos bordes del hueco — el propio inicio del segmento base (ya
    // tiene su estado) y el mes en que arranca el siguiente estado — para
    // marcarlos coloreados y bloqueados. Todo lo de en medio es el hueco.
    if (modoIntermedio.value) {
        const inicio = rangoBaseInicio.value;
        if (inicio && anio === inicio.anio && mes === inicio.mes) {
            return props.field.rangoBase?.estado ?? null;
        }

        const siguiente = props.field.estadoSiguiente;
        if (siguiente) {
            const partes = parsePartesFecha(siguiente.fecha_inicio);
            if (partes && anio === partes.anio && mes === partes.mes) {
                return siguiente.estado;
            }
        }

        return null;
    }

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
        // Un estado sin fecha_fin (el vigente) solo "ocupa" su propio mes de
        // inicio para esta verificación — los meses posteriores quedan
        // libres para elegir al agregar un nuevo estado (extender la línea
        // de tiempo hacia adelante es el uso normal, y el backend ya valida
        // que la fecha sea posterior a la del estado actual).
        const hastaFin = fin
            ? anio < anioFin || (anio === anioFin && mes <= mesFin)
            : anio === anioInicio && mes === mesInicio;

        if (desdeInicio && hastaFin) return h.estado;
    }
    return null;
};

// ============================================================================
// FUNCIONES - PICKER
// ============================================================================

/**
 * Abre o cierra el picker posicionándolo debajo del campo de entrada
 */
const openPicker = () => {
    if (props.field.soloLectura) return;
    if (props.field.editMode && !hayMesesDisponiblesEnEdicion.value) return;

    if (!selectedAnio.value) {
        selectedAnio.value = String(new Date().getFullYear());
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

/**
 * Confirma la selección de un mes y emite el valor al formulario
 * @param {string} mes - Número del mes en formato 'MM'
 */
const selectMes = (mes) => {
    if (isMesDisabled(mes)) return;
    selectedMes.value = mes;
    confirmedAnio.value = selectedAnio.value;
    confirmedMes.value = mes;
    emit('update:modelValue', `${selectedAnio.value}-${mes}-01`);
    showPicker.value = false;
};

/**
 * Cierra el picker al hacer clic fuera de él
 * @param {MouseEvent} e - Evento del clic
 */
const handleClickOutside = (e) => {
    if (
        pickerRef.value && !pickerRef.value.contains(e.target) &&
        triggerRef.value && !triggerRef.value.contains(e.target)
    ) {
        showPicker.value = false;
    }
};

// ============================================================================
// LIFECYCLE
// ============================================================================

// Modo solo lectura: el valor no lo elige el usuario, se fija desde afuera
// (ej. el mes extra para un no-superusuario) — hay que sincronizarlo con el
// form apenas monta para que se vea, no solo al enviar.
onMounted(() => {
    if (props.field.soloLectura && props.field.valorMostrado && !props.modelValue) {
        emit('update:modelValue', props.field.valorMostrado);
    }
});

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div class="mb-1">

        <div ref="triggerRef" tabindex="-1"
            @click="!(props.field.soloLectura || (props.field.editMode && !hayMesesDisponiblesEnEdicion)) && openPicker()"
            class="relative group"
            :class="props.field.soloLectura || (props.field.editMode && !hayMesesDisponiblesEnEdicion) ? 'cursor-not-allowed' : 'cursor-pointer'">

            <input readonly tabindex="-1" :value="displayValue" :placeholder="props.field.editMode && !hayMesesDisponiblesEnEdicion
                ? 'No hay meses disponibles para editar'
                : field.placeholder || 'Seleccionar mes y gestión'" :class="[
                    'w-full text-sm rounded-xl px-4 py-2.5 border transition-colors duration-200 outline-none',
                    props.field.soloLectura
                        ? 'bg-gray-100 dark:bg-gray-700/60 text-slate-500 dark:text-slate-400 border-gray-300 dark:border-gray-600/40 cursor-not-allowed focus:outline-none focus:ring-0'
                        : 'bg-gray-50 dark:bg-gray-700/40 text-slate-700 dark:text-slate-200',
                    !props.field.soloLectura && props.field.editMode && !hayMesesDisponiblesEnEdicion
                        ? 'border-amber-300 dark:border-amber-600/40 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/10 cursor-not-allowed focus:outline-none focus:ring-0 focus:border-amber-300'
                        : !props.field.soloLectura && error
                            ? 'border-rose-400 dark:border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                            : !props.field.soloLectura ? 'border-gray-400 dark:border-gray-600/40 ' : '',
                ]" />

            <!-- Ícono derecho -->
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <Icon v-if="props.field.soloLectura" :icon-button="true" name="calendar" class-name="text-slate-400"
                    fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                <Icon v-else-if="!(props.field.editMode && !hayMesesDisponiblesEnEdicion)" :icon-button="true"
                    name="calendar" class-name="text-slate-400" fill="none" stroke="currentColor" stroke-width="2"
                    :size="17" />
                <Icon v-else :icon-button="true" name="alertTriangle" class-name="text-amber-500" fill="none"
                    stroke="currentColor" stroke-width="2" :size="17" />
            </div>

            <!-- Tooltip -->
            <div v-if="props.field.editMode && !hayMesesDisponiblesEnEdicion"
                class="absolute bottom-full left-0 mb-2 w-64 z-50 opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0 transition-all duration-200 ease-out pointer-events-none">
                <div class="bg-slate-800 text-white text-xs rounded-xl px-3 py-2 shadow-lg leading-relaxed">
                    No hay meses disponibles para editar. El beneficiario ya tiene pagos registrados en todos los meses
                    posteriores a este estado.
                    <div class="absolute bottom-[-4px] left-4 w-2 h-2 bg-slate-800 rotate-45"></div>
                </div>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>

        <Teleport to="body">
            <Transition name="date-modal">
                <div v-if="showPicker" ref="pickerRef" :style="pickerStyle"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-3">

                    <!-- Selector de año -->
                    <div class="flex gap-1 mb-3 overflow-x-auto pb-1">
                        <button v-for="anio in aniosDisponibles" :key="anio" type="button" @click="selectedAnio = anio"
                            :class="[
                                'px-3 py-1 rounded-lg text-sm font-medium whitespace-nowrap transition-colors',
                                selectedAnio === anio
                                    ? 'bg-[rgb(var(--brand-600))] text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200'
                            ]">
                            {{ anio }}
                        </button>
                    </div>

                    <!-- Grid de meses -->
                    <div class="grid grid-cols-4 gap-1">

                        <button v-for="mes in mesesAbrev" :key="mes.value" type="button" @click="selectMes(mes.value)"
                            :disabled="isMesDisabled(mes.value)" :title="getTitleMes(mes.value)" :class="[
                                'py-2 rounded-lg text-xs font-medium transition-colors relative',
                                esMesActualEdicion(mes.value)
                                    ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-600))] ring-1 ring-[rgb(var(--brand-400))]'
                                    : isMesAnulado(selectedAnio, mes.value) && isMesDisabled(mes.value)
                                        ? 'bg-red-100 text-red-500 cursor-not-allowed'
                                        : isMesAnulado(selectedAnio, mes.value)
                                            ? 'bg-red-100 text-red-500 hover:bg-red-200 dark:hover:bg-red-200/80'
                                            : isMesRetro(selectedAnio, mes.value)
                                            ? 'bg-yellow-100 text-yellow-600 cursor-not-allowed'
                                            : isMesPagado(selectedAnio, mes.value)
                                        ? 'bg-green-100 text-green-500 cursor-not-allowed'
                                        : getEstadoMes(selectedAnio, mes.value) === 'activo'
                                            ? 'bg-green-50 text-green-600 cursor-not-allowed ring-1 ring-green-300'
                                            : getEstadoMes(selectedAnio, mes.value) === 'baja_temporal'
                                                ? 'bg-orange-50 text-orange-500 cursor-not-allowed ring-1 ring-orange-300'
                                                : getEstadoMes(selectedAnio, mes.value) === 'baja_definitiva'
                                                    ? 'bg-red-50 text-red-500 cursor-not-allowed ring-1 ring-red-300'
                                                    : getEstadoMes(selectedAnio, mes.value) === 'depurado'
                                                        ? 'bg-gray-200 text-gray-500 cursor-not-allowed ring-1 ring-gray-500'
                                                        : getEstadoMes(selectedAnio, mes.value) === 'pagos_suspendidos'
                                                            ? 'bg-purple-200 text-purple-500 cursor-not-allowed ring-1 ring-purple-500'
                                                            : isMesDisabled(mes.value)
                                                                ? 'bg-gray-50 text-gray-300 cursor-not-allowed dark:bg-gray-700 dark:text-gray-600'
                                                                : 'bg-gray-100 text-gray-700 hover:bg-[rgb(var(--brand-50))] hover:text-[rgb(var(--brand-600))] dark:bg-gray-700 dark:text-gray-200',
                            ]">
                            {{ mes.label }}

                            <span
                                v-if="!isMesPagado(selectedAnio, mes.value) && !isMesAnulado(selectedAnio, mes.value) && getEstadoMes(selectedAnio, mes.value)"
                                class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full pointer-events-none" :class="{
                                    'bg-emerald-500': getEstadoMes(selectedAnio, mes.value) === 'activo',
                                    'bg-amber-500': getEstadoMes(selectedAnio, mes.value) === 'baja_temporal',
                                    'bg-red-500': getEstadoMes(selectedAnio, mes.value) === 'baja_definitiva',
                                    'bg-gray-700': getEstadoMes(selectedAnio, mes.value) === 'depurado',
                                    'bg-purple-700': getEstadoMes(selectedAnio, mes.value) === 'pagos_suspendidos',
                                }">
                            </span>

                            <!-- Icono pagado / pago retro / pago anulado -->
                            <Icon v-if="isMesPagado(selectedAnio, mes.value) || isMesAnulado(selectedAnio, mes.value)"
                                name="cash" :size="15" :height="15" class="absolute top-0.5 py-0" :class="isMesAnulado(selectedAnio, mes.value)
                                    ? 'text-red-500'
                                    : isMesRetro(selectedAnio, mes.value) ? 'text-yellow-600' : 'text-[#0d9488]'
                                    "
                                :fill="selectedMes === mes.value && selectedAnio
                                    ? 'white'
                                    : isMesAnulado(selectedAnio, mes.value)
                                        ? '#ef4444'
                                        : isMesRetro(selectedAnio, mes.value) ? '#ca8a04' : '#0d9488'" icon-button />
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
