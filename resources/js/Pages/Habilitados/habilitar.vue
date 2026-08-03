<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import ModalHabilitar from '@/components/ModalHabilitar.vue';
import Dropdown from '@/components/Dropdown.vue';
import Footer from '@/components/Footer.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';
import AppLayout from '@/Layouts/AppLayout.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const filters = computed(() => page.props.filters);
const gestiones = computed(() => page.props.gestiones);
const tiene_meses = computed(() => page.props.tiene_meses);
const datosPersona = computed(() => page.props.datosPersona);
const existe_gestion = computed(() => page.props.existe_gestion);
const datosHabilitado = computed(() => page.props.datosHabilitado);

const resumenMeses = computed(() => {
    const meses = datosHabilitado.value ?? [];
    return {
        habilitados: meses.filter(m => m.habilitado === true).length,
        deshabilitados: meses.filter(m => m.habilitado === false).length,
        bajaTemp: meses.filter(m => m.estado_mes === 'baja_temporal').length,
        sinHabilitar: meses.filter(m => m.habilitado === null).length,
        retro: meses.filter(m => m.es_retroactivo).length,
    };
});

/**
 * Vigencia del carnet del beneficiario (vigente/vencido/indefinido), tomada
 * de cualquier mes ya que el carnet no cambia entre meses — evita repetirla
 * en cada modal de habilitar y mostrarla una sola vez acá.
 */
const carnetEstadoPersona = computed(() => {
    const item = datosHabilitado.value?.[0];
    if (!item?.fecha_emision) return 'indefinido';
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const vencimiento = new Date(item.fecha_vencimiento);
    return vencimiento < hoy ? 'vencido' : 'vigente';
});

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const showPersonaModal = ref(false);
const openModalHabilitar = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const mensajes = ref([]);
const selectedItem = ref(null);
const unhabilitatedObservations = ref({});
const selectedYear = ref(getYearValue(filters.value?.año));

// ============================================================================
// REFS - FORMULARIOS
// ============================================================================

/**
 * Formulario principal para habilitar y editar estados de habilitación
 */
const form = useForm({
    id_habilitado: '',
    habilitado: 0,
    observaciones_habilitado: '',
    id_persona: '',
    id_gestion: '',
    id_mes: '',
});

// ============================================================================
// WATCHERS / LIFECYCLE
// ============================================================================
onMounted(() => {
    // Inicializar observaciones de ítems no habilitados
    datosHabilitado.value.forEach(item => {
        if (!item.habilitado) {
            unhabilitatedObservations.value[item.id_gestion] = '';
        }
    });

    // Cerrar modal de persona con tecla ESC
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Obtiene el nombre del mes desde su número
 * @param {number|string} monthNumber - Número del mes (1-12)
 * @returns {string} Nombre del mes en español
 */
function getMonthNameFromDate(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const index = parseInt(monthNumber, 10) - 1;
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

/**
 * Formatea una cadena de fecha y hora en formato legible
 * @param {string} dateTimeString - Fecha en formato 'YYYY-MM-DD HH:MM:SS'
 * @returns {string} Fecha formateada con hora o 'N/A' si no hay valor
 */
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';

    const [datePart, timePart] = dateTimeString.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour, minute] = timePart.split(':');
    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return `${dateStr} ${hour}:${minute}`;
};

/**
 * Formatea una fecha en formato legible o retorna 'Indefinido'
 * @param {string} data - Fecha en formato 'YYYY-MM-DD'
 * @returns {string} Fecha formateada o 'Indefinido' si no hay valor
 */
const getCurrentDate = (data) => {
    if (!data) return 'Indefinido';
    const [year, month, day] = data.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());
    return dateStr;
};

/**
 * Obtiene el valor numérico del año desde diferentes formatos
 * @param {number|Object} yearData - Año como número u objeto con propiedad 'gestion'
 * @returns {number} Año como número
 */
function getYearValue(yearData) {
    if (typeof yearData === 'object' && yearData?.gestion) {
        return yearData.gestion;
    }
    return yearData;
}

/**
 * Retorna el nombre legible del estado del beneficiario
 * @param {string} estado - Clave del estado
 * @returns {string} Nombre del estado en español
 */
const getNombreEstado = (estado) => {
    if (!estado) return '●';
    switch (estado.toLowerCase()) {
        case 'activo': return 'Activo';
        case 'baja_temporal': return 'Baja Temporal';
        case 'baja_definitiva': return 'Baja Definitiva';
        case 'depurado': return 'Depurado';
        case 'pagos_suspendidos': return 'Pago Suspendido';
        default: return '●';
    }
};

/**
 * Retorna las clases CSS de badge según el estado del beneficiario
 * @param {string} estado - Clave del estado
 * @returns {string} Clases Tailwind para el badge
 */
const getColorEstado = (estado) => {
    if (!estado) return '●';
    switch (estado.toLowerCase()) {
        case 'activo': return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
        case 'baja_temporal': return 'bg-amber-100 text-amber-700 border border-amber-200';
        case 'baja_definitiva': return 'bg-red-100 text-red-700 border border-red-200';
        case 'depurado': return 'bg-gray-100 text-gray-700 border border-gray-200';
        case 'pagos_suspendidos': return 'bg-purple-100 text-purple-700 border border-purple-200';
        default: return '●';
    }
};

/**
 * Retorna la clase CSS del círculo indicador según el estado del beneficiario
 * @param {string} estado - Clave del estado
 * @returns {string} Clase Tailwind para el color del círculo
 */
const getCircleEstado = (estado) => {
    if (!estado) return '●';
    switch (estado.toLowerCase()) {
        case 'activo': return 'bg-emerald-500';
        case 'baja_temporal': return 'bg-amber-500';
        case 'baja_definitiva': return 'bg-red-500';
        case 'depurado': return 'bg-gray-500';
        case 'pagos_suspendidos': return 'bg-purple-500';
        default: return '●';
    }
};

/**
 * Genera las iniciales de un nombre y apellido
 * @param {string} nombre - Nombre de la persona
 * @param {string} apellido - Apellido de la persona
 * @returns {string} Iniciales en mayúsculas
 */
const getInitials = (nombre, apellido) =>
    `${(nombre || '')[0] ?? ''}${(apellido || '')[0] ?? ''}`.toUpperCase() || '?';

// ============================================================================
// FUNCIONES - MENSAJES
// ============================================================================

/**
 * Muestra un mensaje en la interfaz
 * @param {string} tipo - Tipo de mensaje (error, correcto, info, advertencia)
 * @param {string} titulo - Título del mensaje
 * @param {string} texto - Contenido del mensaje
 */
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

/**
 * Cierra un mensaje específico por su ID
 * @param {number} id - ID del mensaje a cerrar
 */
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// ============================================================================
// FUNCIONES - NAVEGACIÓN Y FILTRADO
// ============================================================================

/**
 * Selecciona una gestión y recarga la vista con el año indicado
 * @param {number|Object} year - Año a seleccionar
 */
const selectGestion = (year) => {
    const yearValue = getYearValue(year);
    selectedYear.value = yearValue;
    router.get(route('habilitado.show', { id: page.props.idPersona }), {
        año: yearValue,
        buscador: filters.value?.buscador || ''
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// ============================================================================
// FUNCIONES - HABILITADOS
// ============================================================================

/**
 * Envía el formulario para habilitar un mes al beneficiario
 * @param {Object} item - Datos del mes a habilitar
 */
const submit = (item) => {
    form.id_habilitado = '';
    form.id_persona = item.id_persona;
    form.id_gestion = item.id_gestion;
    form.id_mes = item.id_mes;
    form.observaciones_habilitado = item.observaciones_habilitado || '';

    form.post(route('habilitado.store'), {
        onSuccess: () => {
            openModalHabilitar.value = false;
            mostrarMensaje('correcto', 'Habilitación exitosa', 'Se ha habilitado correctamente.');
            form.reset();
        },
        onError: (errors) => {
            mostrarMensaje(
                errors.habilitado ? 'error' : 'advertencia',
                errors.habilitado ? 'No se pudo habilitar' : 'Algo inesperado ocurrió',
                errors.habilitado || 'Ha ocurrido un error inesperado. Intente nuevamente.'
            );
        }
    });
};

/**
 * Maneja el cambio de estado de habilitación desde el modal
 * @param {Object} data - Datos del cambio (id_habilitado, habilitado, observacion)
 */
const handleCambioEstado = (data) => {
    form.id_habilitado = data.id_habilitado;
    form.habilitado = data.habilitado;
    form.observaciones_habilitado = data.observacion;

    form.post(route('habilitado.edit', data.id_habilitado), {
        onSuccess: () => {
            openModalHabilitar.value = false;
            mostrarMensaje(
                'correcto',
                'Modificación exitosa',
                data.habilitado === 1 ? 'Se ha habilitado correctamente.' : 'Se ha deshabilitado correctamente.'
            );
            form.reset();
        },
        onError: (errors) => {
            mostrarMensaje(
                errors.habilitado ? 'error' : 'advertencia',
                errors.habilitado ? 'No se pudo procesar' : 'Algo inesperado ocurrió',
                errors.habilitado || 'Ha ocurrido un error inesperado. Intente nuevamente.'
            );
        }
    });
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Abre el modal de datos del beneficiario
 */
const openPersonaModal = () => {
    showPersonaModal.value = true;
};

/**
 * Cierra el modal de datos del beneficiario
 */
const closePersonaModal = () => {
    showPersonaModal.value = false;
};

/**
 * Abre el modal de habilitación con los datos del ítem seleccionado
 * @param {Object} item - Datos del mes a habilitar
 */
const openModal = (item) => {
    selectedItem.value = { ...item };
    openModalHabilitar.value = true;
};

/**
 * Cierra el modal de persona al presionar la tecla ESC
 * @param {KeyboardEvent} e - Evento de teclado
 */
const handleEscape = (e) => {
    if (e.key === 'Escape' && showPersonaModal.value) {
        closePersonaModal();
    }
};
</script>

<template>
    <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Habilitar Meses</h1>
            <Rutas label1="Inicio" label2="Beneficiarios" label3="Habilitar Meses" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- HEADER PRINCIPAL -->
        <!-- ============================================================================ -->
        <div class="bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
                <div class="py-4">
                    <!-- Título y Selector de Gestión -->
                    <div class="flex items-center justify-between sm:gap-4 mb-0 ">
                        <div class="flex items-center gap-3">
                            <div v-if="selectedYear" class="flex items-center">
                                <h1
                                    class="text-xl sm:text-3xl font-bold text-slate-800 text-center tracking-tight bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                                    GESTIÓN
                                </h1>
                                <Dropdown align="left" width="60">
                                    <template #trigger="{ open }">
                                        <button
                                            class="inline-flex ms-2 items-center gap-2 px-2 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow"
                                            type="button">
                                            <span class="text-xl font-semibold text-slate-700">
                                                {{ selectedYear }}
                                            </span>
                                            <Icon :icon-button="true" name="angleDown"
                                                :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                                fill="none" stroke="currentColor" stroke-width="2" :size="17"
                                                @click="isOpen = !isOpen" />
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li v-if="!gestiones || gestiones.length === 0" class="px-4 py-3">
                                                    <div class="flex items-center gap-3 text-rose-400">
                                                        <Icon :icon-button="true" name="alertTriangle"
                                                            class-name="text-rose-400" fill="none" stroke="currentColor"
                                                            stroke-width="2" :size="20" />
                                                        <span class="text-sm">No hay datos disponibles</span>
                                                    </div>
                                                </li>
                                                <li v-for="year in gestiones" :key="year.gestion">
                                                    <a href="#" @click.prevent="selectGestion(year)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150 group"
                                                        :class="selectedYear && selectedYear.toString() === year.gestion.toString()
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        <span class="flex items-center leading-none gap-1">
                                                            <Icon :icon-button="true" name="calendar"
                                                                class-name="text-slate-400 block" fill="none"
                                                                stroke="currentColor" stroke-width="1" :size="18" />
                                                            {{ year.gestion }}
                                                        </span>
                                                        <Icon
                                                            v-if="selectedYear && selectedYear.toString() === year.gestion.toString()"
                                                            :icon-button="true" name="check" class-name="text-[rgb(var(--brand-700))]"
                                                            :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Información del Beneficiario - Versión Mini -->
                        <div v-if="datosPersona && datosPersona.length > 0" class="relative">
                            <div v-for="persona in datosPersona" :key="persona.id_persona" @click="openPersonaModal"
                                class="bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600/40 rounded-xl px-3 py-2 cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">

                                <div class="flex items-center gap-2.5">
                                    <!-- Avatar -->
                                    <div
                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-gray-400 flex items-center justify-center flex-shrink-0 shadow-sm">
                                        <span class="text-xs font-bold text-white uppercase">
                                            {{ getInitials(
                                                persona.nombre_persona || persona.nombre_completo?.split(' ')[0],
                                                persona.apellido_persona || persona.nombre_completo?.split(' ')[1] || ''
                                            ) }}
                                        </span>
                                    </div>

                                    <!-- Nombre — oculto en móvil muy pequeño -->
                                    <div class="flex-1 min-w-0 hidden sm:block">
                                        <p
                                            class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-none">
                                            Beneficiario</p>
                                        <p
                                            class="text-xs font-semibold text-slate-700 dark:text-slate-200 capitalize truncate mt-0.5">
                                            {{ persona.apellido_persona && persona.nombre_persona
                                                ? `${persona.apellido_persona} ${persona.nombre_persona}`
                                                : persona.nombre_completo }}
                                        </p>
                                    </div>

                                    <!-- Estado -->
                                    <span
                                        class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border flex-shrink-0"
                                        :class="getColorEstado(persona.estado)">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="getCircleEstado(persona.estado)"></span>
                                        {{ getNombreEstado(persona.estado) }}
                                    </span>

                                    <!-- Chevron -->
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform"
                                        :class="showPersonaModal ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Modal Expandido -->
                            <Transition enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                                <div v-if="showPersonaModal" class="absolute top-full right-0 mt-2 z-50 w-80 sm:w-96"
                                    @click.stop>
                                    <div v-for="persona in datosPersona" :key="'modal-' + persona.id_persona"
                                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden">

                                        <!-- Header del modal -->
                                        <div
                                            class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-gray-400 flex items-center justify-center flex-shrink-0 shadow-sm">
                                                    <span class="text-xs font-bold text-white uppercase">
                                                        {{ getInitials(persona.nombre_persona
                                                            || persona.nombre_completo?.split(' ')[0],
                                                            persona.apellido_persona
                                                            || persona.nombre_completo?.split(' ')[1] || ''
                                                        ) }}
                                                    </span>
                                                </div>
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                                                        {{ persona.apellido_persona && persona.nombre_persona
                                                            ? `${persona.apellido_persona} ${persona.nombre_persona}`
                                                            : persona.nombre_completo }}
                                                    </p>
                                                    <p class="text-xs text-indigo-500 dark:text-indigo-300">CI: {{
                                                        persona.ci_persona }}</p>
                                                </div>
                                            </div>
                                            <button @click="closePersonaModal"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors flex-shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Contenido -->
                                        <div class="p-3.5 space-y-2.5">

                                            <!-- Fecha registro -->
                                            <div
                                                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/40 rounded-xl px-3.5 py-2.5 border border-gray-200 dark:border-gray-600/40">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">Registrado
                                                        el</span>
                                                </div>
                                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ getCurrentDate(persona.fecha_registro?.split('T')[0]) }}
                                                </span>
                                            </div>

                                            <!-- Vigencia carnet (una sola vez acá, no se repite en cada mes) -->
                                            <div
                                                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/40 rounded-xl px-3.5 py-1.5 border border-gray-200 dark:border-gray-600/40">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">Vigencia
                                                        carnet</span>
                                                </div>
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="{
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': carnetEstadoPersona === 'indefinido',
                                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': carnetEstadoPersona === 'vencido',
                                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': carnetEstadoPersona === 'vigente',
                                                }">
                                                    {{ carnetEstadoPersona === 'indefinido' ? 'Indefinido' :
                                                        carnetEstadoPersona === 'vencido' ? 'Vencido' : 'Vigente' }}
                                                </span>
                                            </div>

                                            <!-- Separador: Estado -->
                                            <div class="flex items-center gap-2 px-1">
                                                <span
                                                    class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">Estado
                                                    actual</span>
                                                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                            </div>

                                            <!-- Estado + fecha -->
                                            <div
                                                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-3.5 py-2.5 border border-gray-200 dark:border-gray-600/40 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                        :class="getColorEstado(persona.estado)">
                                                        <span class="w-1.5 h-1.5 rounded-full"
                                                            :class="getCircleEstado(persona.estado)"></span>
                                                        {{ getNombreEstado(persona.estado) }}
                                                    </span>
                                                    <span v-if="persona.fecha_inicio"
                                                        class="text-xs text-slate-500 dark:text-slate-400">
                                                        Desde: {{ getCurrentDate(persona.fecha_inicio) }}
                                                    </span>
                                                </div>
                                                <div class="h-px bg-gray-200 dark:bg-gray-600"></div>
                                                <div class="flex gap-2">
                                                    <span
                                                        class="text-xs text-slate-400 flex-shrink-0">Observación:</span>
                                                    <span v-if="persona.observaciones"
                                                        class="text-xs text-slate-600 dark:text-slate-300 break-words">
                                                        {{ persona.observaciones }}
                                                    </span>
                                                    <span v-else class="text-xs text-slate-400 italic">Sin
                                                        observaciones</span>
                                                </div>
                                            </div>

                                            <!-- Separador: Meses -->
                                            <div class="flex items-center gap-2 px-1">
                                                <span
                                                    class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">
                                                    Gestión {{ selectedYear }}
                                                </span>
                                                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                            </div>

                                            <!-- Resumen meses -->
                                            <div class="flex flex-wrap gap-1.5">
                                                <span v-if="resumenMeses.habilitados > 0"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-700/40">
                                                    <span class="font-bold">{{ resumenMeses.habilitados }}</span>
                                                    Habilitado(s)
                                                </span>
                                                <span v-if="resumenMeses.deshabilitados > 0"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-700/40">
                                                    <span class="font-bold">{{ resumenMeses.deshabilitados }}</span>
                                                    Deshabilitado(s)
                                                </span>
                                                <span v-if="resumenMeses.bajaTemp > 0"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-700/40">
                                                    <span class="font-bold">{{ resumenMeses.bajaTemp }}</span> Baja
                                                    temporal
                                                </span>
                                                <span v-if="resumenMeses.sinHabilitar > 0"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-slate-500 border border-gray-200 dark:bg-gray-700/40 dark:text-slate-400 dark:border-gray-600/40">
                                                    <span class="font-bold">{{ resumenMeses.sinHabilitar }}</span>
                                                    Sin habilitar
                                                </span>
                                                <span v-if="resumenMeses.retro > 0"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-700/40">
                                                    <span class="font-bold">{{ resumenMeses.retro }}</span>
                                                    Retroactivo(s)
                                                </span>
                                                <span v-if="datosHabilitado.length === 0"
                                                    class="text-xs text-slate-400 italic">
                                                    Sin meses registrados
                                                </span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Backdrop transparente para cerrar al hacer clic afuera -->
                        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <div v-if="showPersonaModal" @click="closePersonaModal" class="fixed inset-0 z-40">
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- CONTENIDO PRINCIPAL - GRID DE MESES -->
        <!-- ============================================================================ -->
        <main
            class="flex-1 overflow-x-hidden overflow-y-auto border-b-2 border-x-2 bg-white rounded-b-lg px-2 pt-3 mr-1">
            <!-- Grid de Cards optimizado para 1-12 items -->
            <div v-if="existe_gestion && tiene_meses && datosHabilitado.length > 0">
                <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
                    <div v-for="item in datosHabilitado" :key="item.id_habilitado" @click="openModal(item)"
                        class="group cursor-pointer h-[125px] rounded-2xl border-2 border-gray-100 hover:border-transparent
                                hover:shadow-xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1 relative z-0 bg-white" :class="{
                                    'hover:bg-emerald-500': item.estado_mes === 'activo',
                                    'hover:bg-amber-500': item.estado_mes === 'baja_temporal',
                                    'hover:bg-red-500': item.estado_mes === 'baja_definitiva',
                                    'hover:bg-gray-500': item.estado_mes === 'depurado',
                                    'hover:bg-purple-500': item.estado_mes === 'pagos_suspendidos',
                                    'hover:bg-gray-100': !item.estado_mes
                                }">
                        <!-- Círculo decorativo esquina -->
                        <div class="absolute h-[5em] w-[5em] -top-[2.5em] -right-[2.5em] rounded-full
                                    opacity-100 group-hover:opacity-0 transition-opacity duration-500 pointer-events-none"
                            :class="{
                                'bg-emerald-300': item.estado_mes === 'activo',
                                'bg-amber-300': item.estado_mes === 'baja_temporal',
                                'bg-red-300': item.estado_mes === 'baja_definitiva',
                                'bg-gray-400': item.estado_mes === 'depurado',
                                'bg-purple-400': item.estado_mes === 'pagos_suspendidos',
                                'bg-gray-50': !item.estado_mes
                            }"></div>

                        <!-- ═══════════════════════════════════ -->
                        <!--  ESTADO BASE (visible sin hover)   -->
                        <!-- ═══════════════════════════════════ -->
                        <div class="absolute inset-0 p-4 flex flex-col justify-between
                                    opacity-100 group-hover:opacity-0 translate-y-0 group-hover:-translate-y-2
                                    transition-all duration-400 pointer-events-none z-10">

                            <!-- Fila superior: Mes + ícono de pago -->
                            <div class="flex items-start justify-between">
                                <h1 class="font-bold text-2xl leading-tight text-gray-800">
                                    {{ getMonthNameFromDate(item.es_retroactivo ? item.mes_original : item.mes) }}
                                    <span v-if="item.es_retroactivo"
                                        class="block text-[10px] font-semibold text-amber-600">
                                        Retroactivo
                                    </span>
                                </h1>

                                <!-- Ícono de pago (solo si está habilitado) -->
                                <span v-if="item.habilitado == 1 || item.pagado !== null"
                                    class="flex items-center justify-center w-7 h-7 rounded-full mt-0.5" :class="{
                                        'bg-red-200': item.estado_mes === 'pagos_suspendidos' || item.pagado === 0,
                                        'bg-yellow-100': item.pagado == 1 && item.es_retroactivo,
                                        'bg-emerald-100 ': item.pagado == 1 && !item.es_retroactivo,
                                        'bg-gray-100': item.pagado !== 1 && item.pagado !== 0 && item.estado_mes !== 'pagos_suspendidos'
                                    }">
                                    <Icon :icon-button="true" name="dollar" :class-name="item.estado_mes === 'pagos_suspendidos' || item.pagado === 0
                                        ? 'text-red-600'
                                        : item.pagado == 1
                                            ? (item.es_retroactivo ? 'text-yellow-600' : 'text-emerald-600')
                                            : 'text-gray-500'" fill="none" stroke="currentColor" stroke-width="2"
                                        :size="23" />
                                </span>
                            </div>

                            <!-- Fila inferior: Badge de estado -->
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full" :class="{
                                    'bg-yellow-100 text-yellow-700': item.habilitado === true && item.es_retroactivo,
                                    'bg-emerald-100 text-emerald-700': item.habilitado === true && !item.es_retroactivo,
                                    'bg-red-100 text-red-700': item.habilitado === false,
                                    'bg-gray-100 text-gray-500': item.habilitado === null
                                }">
                                    {{ item.habilitado === true ? 'Habilitado' : item.habilitado === false ?
                                        'Deshabilitado' :
                                        'Sin habilitar' }}
                                </span>

                                <!-- Dot de estado del mes -->
                                <span class="flex items-center gap-1 text-[11px] text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block" :class="{
                                        'bg-emerald-400': item.estado_mes === 'activo',
                                        'bg-amber-400': item.estado_mes === 'baja_temporal',
                                        'bg-red-400': item.estado_mes === 'baja_definitiva',
                                        'bg-gray-400': item.estado_mes === 'depurado',
                                        'bg-purple-600': item.estado_mes === 'pagos_suspendidos',
                                        'bg-gray-200': !item.estado_mes
                                    }"></span>
                                    {{ item.estado_mes ? item.estado_mes.replace('_', ' ') : 'Sin estado' }}
                                </span>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════ -->
                        <!--  ESTADO HOVER (visible al pasar)   -->
                        <!-- ═══════════════════════════════════ -->
                        <div class="absolute inset-0 p-4 pt-3 flex flex-col justify-between
                                    opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0
                                    transition-all duration-500 pointer-events-none z-10">

                            <!-- Título mes en hover -->
                            <h1 class="font-bold text-xl text-white leading-tight">
                                {{ getMonthNameFromDate(item.es_retroactivo ? item.mes_original : item.mes) }}
                                <span v-if="item.es_retroactivo" class="block text-[10px] font-semibold text-amber-300">
                                    Retroactivo
                                </span>
                            </h1>

                            <!-- Detalles en hover -->
                            <div class="space-y-1.5">

                                <!-- Fecha de habilitación -->
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-white/70 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg>
                                        Habilitado
                                    </span>
                                    <span class="text-[11px] font-semibold text-white">
                                        {{ formatDateTime(item.fecha_habilitado) }}
                                    </span>
                                </div>

                                <!-- Fecha de pago (solo si pagado) -->
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-white/70 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <rect x="1" y="4" width="22" height="16" rx="2" />
                                            <line x1="1" y1="10" x2="23" y2="10" />
                                        </svg>
                                        Fecha pago
                                    </span>

                                    <span v-if="item.habilitado == 1 && item.pagado == 1"
                                        class="text-[11px] font-semibold text-white">
                                        {{ formatDateTime(item.fecha_pago) }}
                                    </span>
                                    <span v-else class="text-[11px] text-white/60">Pago N/A</span>
                                </div>

                                <div class="w-full h-px bg-white/20"></div>

                                <!-- Estado + pago -->
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20 text-white">
                                        {{ item.habilitado === true ? 'Habilitado' : item.habilitado === false ?
                                            'Deshabilitado'
                                            : 'Sin habilitar' }}
                                    </span>

                                    <span v-if="item.pagado === 1"
                                        class="text-[11px] font-semibold text-white">
                                        {{ formatDateTime(item.fecha_pago) }}
                                    </span>
                                    <span v-else-if="item.pagado === 0"
                                        class="text-[11px] font-semibold text-red-200">
                                        Pago Anulado
                                    </span>
                                    <span v-else class="text-[11px] text-white/60">Pago N/A</span>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-else class="flex flex-col items-center justify-center min-h-[400px] text-center">
                <!-- Icono dinámico según el estado -->
                <div class="bg-white rounded-full p-6 ">
                    <!-- Sin gestión - Icono de calendario con X -->
                    <Icon v-if="!existe_gestion" :icon-button="true" name="calendarMontSolid"
                        class-name="text-gray-400 dark:text-gray-500" :size="64" :height="64" />

                    <!-- Sin meses - Icono de reloj/calendario vacío -->
                    <svg v-else-if="!tiene_meses" class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>

                    <!-- Sin datos - Icono de carpeta vacía -->
                    <svg v-else class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>

                <h3 class="text-xl font-semibold text-gray-800 mb-2">
                    <span v-if="!existe_gestion">No hay gestiones registradas</span>
                    <span v-else-if="!tiene_meses">Sin meses registrados</span>
                    <span v-else>Sin datos disponibles</span>
                </h3>

                <p class="text-gray-500 max-w-md">
                    <span v-if="!existe_gestion">Debe crear una gestión en el sistema para comenzar a
                        trabajar</span>
                    <span v-else-if="!tiene_meses">Agregue meses a la gestión {{ selectedYear }} para habilitar
                        beneficiarios</span>
                    <span v-else>No se encontraron registros para mostrar</span>
                </p>
            </div>
        </main>

        <!-- ============================================================================ -->
        <!-- FOOTER Y PAGINACIÓN -->
        <!-- ============================================================================ -->
        <div class="mt-1">
            <Footer />
        </div>

        <!-- ============================================================================ -->
        <!-- MODALES -->
        <!-- ============================================================================ -->

        <!-- Modal: Habilitar Meses -->
        <Transition name="fade">
            <ModalHabilitar v-if="openModalHabilitar" :data="selectedItem || {}" :processing="form.processing"
                @habilitar="submit" @cambioEstado="handleCambioEstado"
                @sinDatos="(accion) => mostrarMensaje('error', accion === 'deshabilitar' ? 'Error al Deshabilitar' : 'Error al Habilitar', `Debe agregar una observación válida para ${accion}.`)"
                @close="openModalHabilitar = false" />
        </Transition>
    </AppLayout>
</template>
