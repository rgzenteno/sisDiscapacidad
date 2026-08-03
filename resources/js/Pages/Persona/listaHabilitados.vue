<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';

/**
 * Componentes
 */
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import Dropdown from '@/components/Dropdown.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';
import MesSuspensionPicker from '@/components/MesSuspensionPicker.vue';

/**
 * Composables
 */
import { useComprobantePDF } from '@/composables/useComprobantePDF';

/**
 * Utilidades
 */
import { can } from '@/lib/can';

// ============================================================================
// INICIALIZACIÓN DE COMPOSABLES
// ============================================================================
const { generarComprobante, mostrarModal, pdfUrl, pdfNombre, cerrarModal } = useComprobantePDF();

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const datosHabilitado = computed(() => page.props.datosHabilitado);
const datosRecibo = computed(() => page.props.datosRecibo);
const gestiones = computed(() => page.props.gestiones);
const filters = computed(() => page.props.filters);
const añoSeleccionado = computed(() => page.props.añoSeleccionado);
const suspensionPagoActual = computed(() => page.props.suspensionPagoActual);
const mesesDisponiblesSuspension = computed(() => page.props.mesesDisponiblesSuspension ?? []);
const mesesPagadosSuspension = computed(() => page.props.mesesPagadosSuspension ?? []);

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const modalConfirm = ref(false);
const openModalPagado = ref(false);
// Expande/colapsa el motivo completo de la anulación dentro del modal de
// "Pago Anulado" (ver bloque "Aviso de anulación"). Se resetea cada vez que
// el modal se cierra o se abre para otro beneficiario, para no arrastrar el
// estado expandido de un pago a otro.
const mostrarMotivoAnulacion = ref(false);
watch(openModalPagado, (abierto) => {
    if (!abierto) mostrarMotivoAnulacion.value = false;
});
const showPersonaModal = ref(false);
const showSuspenderModal = ref(false);
const showReactivarModal = ref(false);
const showDetalleSuspensionModal = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedItem = ref(null);
const currentPdfItem = ref(null);
const idHabi = ref('');
const tipoHabi = ref('');
const mensajes = ref([]);
const selectedYear = ref(getYearValue(filters.value?.año) ?? añoSeleccionado.value?.gestion);

// ============================================================================
// REFS - FORMULARIOS
// ============================================================================

/**
 * Formulario para registrar el pago de un habilitado
 */
const form = useForm({
    id_habilitado: '',
    id_tutor: '',
    tipo: ''
});

/**
 * Formulario para suspender los pagos del beneficiario (indefinido, desde
 * un mes en adelante) — independiente del historial de estados.
 */
const suspenderForm = useForm({
    id_persona: '',
    fecha_inicio: '',
    observaciones: '',
});

/**
 * Habilita el botón "Suspender Pagos" solo cuando el mes y el motivo están
 * completos.
 */
const puedeSuspender = computed(() =>
    !!suspenderForm.fecha_inicio && !!suspenderForm.observaciones.trim()
);

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Mes', field: 'mes', headerClass: 'text-center px-3', cellClass: 'capitalize whitespace-nowrap' },
    { label: 'Estado Hab.', field: 'habilitado', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Estado Pago', field: 'pago', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Gestión', field: 'gestion', headerClass: 'text-center px-3 hidden sm:block', cellClass: 'whitespace-nowrap' },
    { label: 'Monto - Persona', field: 'monto', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Estado', field: 'estado', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Observación', field: 'observaciones_habilitado', headerClass: 'text-center px-3 hidden sm:block', cellClass: '' },
    { label: 'Fecha Habilitado', field: 'fecha_habilitado', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Fecha Pago', field: 'fecha_pago', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center px-3', cellClass: '' }
];

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Obtiene el valor numérico del año desde diferentes formatos
 * @param {number|Object} yearData - Año como número u objeto con propiedad 'gestion'
 * @returns {number} Año como número
 */
function getYearValue(yearData) {
    return typeof yearData === 'object' && yearData?.gestion ? yearData.gestion : yearData;
}

function getPagoStatus(item) {
    // Caso 1: existe un registro de pago
    if (item.pago) {
        if (Number(item.pago.pago) === 0) {
            return { label: 'Anulado', icon: 'closeCircleOutline', color: 'red' }
        }
        if (item.es_retroactivo) {
            return { label: 'Cobrado', icon: 'badgeCheckOutline', color: 'yellow' }
        }
        return { label: 'Cobrado', icon: 'badgeCheckOutline', color: 'green' }
    }

    // Caso 2: no existe registro de pago -> aún no cobró
    if (item.pago_suspendido) {
        return { label: 'Suspendido', icon: 'cash', color: 'purple' }
    }
    if (item.habilitado === true) {
        return { label: 'Pendiente de cobro', icon: 'circleMinusOutline', color: 'gray' }
    }
    return { label: 'Sin cobro', icon: 'closeCircleOutline', color: 'red' }
}

const colorClasses = {
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    purple: 'bg-purple-100 text-purple-800',
    gray: 'bg-gray-100 text-gray-800',
    red: 'bg-red-100 text-red-600',
}
const iconColorClasses = {
    green: 'text-green-500',
    yellow: 'text-yellow-600',
    purple: 'text-purple-500',
    gray: 'text-gray-500',
    red: 'text-red-500',
}

function isAnulado(item) {
    return !!item.pago && Number(item.pago.pago) === 0
}

/**
 * Obtiene el nombre del mes desde su número
 * @param {number|string} monthNumber - Número del mes (1-12)
 * @returns {string} Nombre del mes en español
 */
function getMonthNameFromNumber(monthNumber) {
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
    return `${dateStr} - ${hour}:${minute}`;
};

/**
 * Convierte una cadena de texto a formato título (primera letra de cada palabra en mayúscula)
 * @param {string} str - Texto a formatear
 * @returns {string} Texto en formato título
 */
const toTitleCase = (str) =>
    str
        ? str.toLocaleLowerCase('es').replace(/(^|\s)\S/g, l => l.toLocaleUpperCase('es'))
        : '';

/**
 * Genera las iniciales de un nombre y apellido
 * @param {string} nombre - Nombre de la persona
 * @param {string} apellido - Apellido de la persona
 * @returns {string} Iniciales en mayúsculas
 */
const getInitials = (nombre, apellido) =>
    `${(nombre || '')[0] ?? ''}${(apellido || '')[0] ?? ''}`.toUpperCase() || '?';

/**
 * Retorna el texto y clase CSS correspondiente al estado del beneficiario
 * @param {string} estado - Clave del estado
 * @returns {Object} Objeto con propiedades texto y clase
 */
const getEstadoInfo = (estado) => {
    if (estado === 'activo') return { texto: 'Activo', clase: 'text-green-600' };
    if (estado === 'baja_temporal') return { texto: 'Baja Temporal', clase: 'text-orange-600' };
    if (estado === 'baja_definitiva') return { texto: 'Baja Definitiva', clase: 'text-red-600' };
    if (estado === 'depurado') return { texto: 'Depurado', clase: 'text-gray-600' };
    if (estado === 'pagos_suspendidos') return { texto: 'Pagos Suspendidos', clase: 'text-purple-600' };
    return { texto: '-', clase: 'text-gray-600' };
};

const getEstadoBadgeColor = (estado) => {
    const colors = {
        activo: {
            badge: 'bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700/40',
            dot: 'bg-emerald-500',
        },
        baja_temporal: {
            badge: 'bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-700/40',
            dot: 'bg-yellow-500',
        },
        baja_definitiva: {
            badge: 'bg-red-100 text-red-800 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700/40',
            dot: 'bg-red-500',
        },
        depurado: {
            badge: 'bg-gray-100 text-gray-800 border border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-700/40',
            dot: 'bg-gray-500',
        },
        pagos_suspendidos: {
            badge: 'bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700/40',
            dot: 'bg-purple-500',
        },
    }
    return colors[estado] || {
        badge: 'bg-gray-100 text-gray-800 border border-gray-200',
        dot: 'bg-gray-500',
    }
}

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
 * Navega a una ruta específica con un ID codificado en base64
 * @param {string} ruta - Nombre de la ruta
 * @param {number|string} id - ID a codificar como parámetro
 */
const getUrl = (ruta, id) => {
    const url = route(ruta, window.btoa(id));
    router.visit(url);
};

/**
 * Selecciona una gestión y recarga la vista con el año indicado
 * @param {number|Object} year - Año a seleccionar
 */
const selectGestion = (year) => {
    const yearValue = getYearValue(year);
    selectedYear.value = yearValue;
    router.get(route('persona.showHabilitado', { id: datosRecibo.value.id_persona }), {
        año: yearValue,
    }, {
        preserveState: true,
        preserveScroll: true,
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
 * Abre el modal para suspender los pagos del beneficiario
 */
const openSuspenderModal = () => {
    suspenderForm.reset();
    suspenderForm.clearErrors();
    suspenderForm.id_persona = datosRecibo.value.id_persona;
    showSuspenderModal.value = true;
};

/**
 * Título del botón de suspensión de pagos, según haya o no una suspensión
 * vigente y el permiso del usuario que está viendo la pantalla.
 */
const tituloBotonSuspension = computed(() => {
    if (!suspensionPagoActual.value) return 'Suspender los pagos de este beneficiario';
    return can('suspender-pago')
        ? 'Ver o reactivar la suspensión de pagos'
        : 'Ver por qué se suspendieron los pagos de este beneficiario';
});

/**
 * Decide qué modal abrir al hacer clic en el botón de suspensión: solo
 * quienes tienen permiso pueden suspender/reactivar — el resto (ej. un
 * cajero) puede ver el motivo y quién lo suspendió, pero no actuar.
 */
const abrirModalSuspension = () => {
    if (!suspensionPagoActual.value) {
        openSuspenderModal();
        return;
    }

    if (can('suspender-pago')) {
        showReactivarModal.value = true;
    } else {
        showDetalleSuspensionModal.value = true;
    }
};

/**
 * Envía el formulario de suspensión de pagos
 */
const submitSuspender = () => {
    suspenderForm.post(route('pagoSuspendido.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showSuspenderModal.value = false;
            mostrarMensaje('correcto', 'Pagos suspendidos', 'Se suspendieron los pagos del beneficiario.');
            router.reload({ only: ['datosHabilitado', 'suspensionPagoActual'] });
        },
    });
};

/**
 * Reactiva los pagos del beneficiario (cierra la suspensión vigente)
 */
const reactivarPagos = () => {
    if (!suspensionPagoActual.value) return;

    router.delete(route('pagoSuspendido.destroy', suspensionPagoActual.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showReactivarModal.value = false;
            mostrarMensaje('correcto', 'Pagos reactivados', 'Se reactivaron los pagos del beneficiario.');
            router.reload({ only: ['datosHabilitado', 'suspensionPagoActual'] });
        },
    });
};

/**
 * Formatea "YYYY-MM-DD" a "Nombre del mes - Año"
 * @param {string} fecha - Fecha en formato YYYY-MM-DD
 */
const formatMonthYear = (fecha) => {
    if (!fecha) return '-';
    const [year, month] = fecha.split('-');
    return `${getMonthNameFromNumber(month)} - ${year}`;
};

/**
 * Abre el modal de confirmación para registrar un pago
 * @param {number|string} idHabilitado - ID del registro habilitado
 * @param {string} tipo - Tipo de pago
 */
const confirmar = (idHabilitado, tipo) => {
    idHabi.value = idHabilitado;
    tipoHabi.value = tipo;
    modalConfirm.value = true;
};

/**
 * Cierra el modal de confirmación de pago
 */
const closeModal = () => {
    modalConfirm.value = false;
};

// ============================================================================
// FUNCIONES - PAGOS Y REPORTES
// ============================================================================

/**
 * Genera el comprobante PDF del pago del mes seleccionado
 * @param {Object} item - Datos del habilitado con información del pago
 */
const createReporte = async (item) => {
    currentPdfItem.value = item;
    try {
        await generarComprobante(
            datosRecibo.value,
            item,
            item.pago.user,
            item.pago.user.digital_signature
        );
    } catch (error) {
        mostrarMensaje('advertencia', 'Error', 'No se pudo generar el comprobante.');
    }
};

/**
 * Nombre legible del comprobante para mostrar en el modal (el nombre real
 * del archivo, con guiones bajos y extensión, se usa tal cual al descargar)
 */
const pdfNombreLegible = computed(() =>
    pdfNombre.value
        ? pdfNombre.value.replace(/\.pdf$/i, '').replace(/_/g, ' ')
        : ''
);

/**
 * Descarga el PDF generado y registra el log de descarga
 */
const descargarPDF = async () => {
    if (!pdfUrl.value || !pdfNombre.value) return;
    await registrarDescargaLog();
    const a = document.createElement('a');
    a.href = pdfUrl.value;
    a.download = pdfNombre.value;
    a.click();
};

/**
 * Imprime el PDF generado usando un iframe oculto
 */
const imprimirPDFSync = () => {
    if (!pdfUrl.value) return;
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = pdfUrl.value;
    document.body.appendChild(iframe);
    iframe.onload = () => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        iframe.contentWindow.addEventListener('afterprint', () => {
            document.body.removeChild(iframe);
        });
    };
};

/**
 * Registra en el servidor el log de impresión de una boleta
 * @param {Object} item - Datos del habilitado con información del pago
 */
const registrarImpresionLog = async (item) => {
    if (!item) return;
    try {
        await axios.post(route('pago.imprimirBoleta'), {
            id_pago: item.pago.id_pago,
            gestion: item.gestion.gestion,
            mes: item.mes,
        });
    } catch (error) {
        mostrarMensaje('advertencia', 'Error', 'No se pudo registrar la impresión.');
    }
};

/**
 * Registra en el servidor el log de descarga de una boleta y recarga los datos
 */
const registrarDescargaLog = async () => {
    if (!currentPdfItem.value) return;
    try {
        await axios.post(route('pago.descargarBoleta'), {
            id_pago: currentPdfItem.value.pago.id_pago,
            gestion: currentPdfItem.value.gestion.gestion,
            mes: currentPdfItem.value.mes,
        });
        router.reload({ only: ['datosHabilitado'] });
    } catch (error) {
        mostrarMensaje('advertencia', 'Error', 'No se pudo registrar la descarga.');
    }
};

/**
 * Envía el formulario para registrar el pago y abre el modal de confirmación al completar
 * @param {number|string} idHabilitado - ID del registro habilitado
 * @param {string} tipo - Tipo de pago
 */
const submit = (idHabilitado, tipo) => {
    if (form.processing) return;

    form.tipo = tipo;
    form.id_habilitado = idHabilitado;

    form.post(route('pago.store'), {
        onSuccess: () => {
            mostrarMensaje('correcto', 'Pago exitoso', 'El pago se registró correctamente.');
            modalConfirm.value = false;
            form.reset();

            router.reload({
                only: ['datosHabilitado'],
                onSuccess: () => {
                    const item = datosHabilitado.value.find(
                        h => h.id_habilitado === idHabilitado
                    );
                    if (item) {
                        selectedItem.value = item;
                        openModalPagado.value = true;
                    }
                }
            });
        },
        onError: (errors) => {
            modalConfirm.value = false;
            mostrarMensaje(
                'advertencia',
                'Pago Duplicado',
                errors.pago ?? 'El pago no se registró correctamente.'
            );
        }
    });
};
</script>

<template>
    <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje" estilo="mb-1">

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Meses Disponibles</h1>
            <Rutas label1="Inicio" label2="Beneficiarios" label3="Meses Disponibles" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- FILTROS + RESUMEN + BOTONES -->
        <!-- ============================================================================ -->
        <div class="bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-3">
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
                                            class="inline-flex items-center gap-2 ms-2 px-2 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow"
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

                        <div class="flex items-center gap-2">

                        <!-- Suspensión de Pagos -->
                        <div v-if="can('suspender-pago') || suspensionPagoActual" class="relative">
                            <button type="button" @click="abrirModalSuspension"
                                :title="tituloBotonSuspension"
                                class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-xl border text-xs font-semibold transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                                :class="suspensionPagoActual
                                    ? 'bg-purple-50 border-purple-200 text-purple-700 dark:bg-purple-900/20 dark:border-purple-700/40 dark:text-purple-300'
                                    : 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-gray-700/40 dark:border-gray-600/40 hover:text-gray-700 dark:hover:text-gray-200'">
                                <Icon :icon-button="true" name="cash"
                                    :class-name="suspensionPagoActual ? 'text-purple-500' : 'text-gray-400'"
                                    fill="none" stroke="currentColor" stroke-width="2" :size="15" />
                                <span class="hidden sm:inline">
                                    {{ suspensionPagoActual ? 'Pagos Suspendidos' : 'Suspender Pagos' }}
                                </span>
                            </button>
                        </div>

                        <!-- Información del Beneficiario -->
                        <div class="relative">
                            <div @click="openPersonaModal"
                                class="bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600/40 rounded-xl px-3 py-2 cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                                <div class="flex items-center gap-2.5">
                                    <!-- Avatar -->
                                    <div
                                        class="w-8 h-8 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-gray-400 flex items-center justify-center flex-shrink-0 shadow-sm">
                                        <span class="text-xs font-bold text-white uppercase">
                                            {{ getInitials(datosRecibo?.nombre_persona,
                                                datosRecibo?.apellido_persona) }}
                                        </span>
                                    </div>

                                    <!-- Nombre — oculto en móvil -->
                                    <div class="flex-1 min-w-0 hidden sm:block">
                                        <p
                                            class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-none">
                                            Beneficiario</p>
                                        <p
                                            class="text-xs font-semibold text-slate-700 dark:text-slate-200 capitalize truncate mt-0.5">
                                            {{ datosRecibo?.apellido_persona }} {{ datosRecibo?.nombre_persona }}
                                        </p>
                                    </div>

                                    <!-- Badge activo — solo sm+ -->
                                    <span
                                        class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0"
                                        :class="getEstadoBadgeColor(datosRecibo?.ultimo_estado?.estado).badge">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="getEstadoBadgeColor(datosRecibo?.ultimo_estado?.estado).dot">
                                        </span>
                                        {{ getEstadoInfo(datosRecibo?.ultimo_estado?.estado).texto }}
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
                                    <div
                                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden">

                                        <!-- Header -->
                                        <div
                                            class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-gray-400 flex items-center justify-center flex-shrink-0 shadow-sm">
                                                    <span class="text-xs font-bold text-white uppercase">
                                                        {{ getInitials(datosRecibo?.nombre_persona,
                                                            datosRecibo?.apellido_persona) }}
                                                    </span>
                                                </div>
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                                                        {{ datosRecibo?.apellido_persona }} {{
                                                            datosRecibo?.nombre_persona }}
                                                    </p>
                                                    <p class="text-xs text-indigo-500 dark:text-indigo-300">CI: {{
                                                        datosRecibo?.ci_persona }}
                                                    </p>
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

                                        <div class="p-3.5 space-y-2.5">

                                            <!-- Separador: Beneficiario -->
                                            <div class="flex items-center gap-2 px-1">
                                                <span
                                                    class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">Beneficiario</span>
                                                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                            </div>

                                            <!-- Datos beneficiario -->
                                            <div
                                                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-3.5 py-2.5 space-y-1.5">
                                                <div class="flex justify-between">
                                                    <span class="text-xs text-slate-400">Distrito</span>
                                                    <span
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{
                                                            datosRecibo?.distrito }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-xs text-slate-400">Discapacidad</span>
                                                    <span
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200 capitalize">{{
                                                            datosRecibo?.carnet?.discapacidad }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-xs text-slate-400">Fecha Registro</span>
                                                    <span
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200 capitalize">{{
                                                            datosRecibo?.fecha_registro }}</span>
                                                </div>
                                            </div>

                                            <!-- Separador: Tutor -->
                                            <div class="flex items-center gap-2 px-1">
                                                <span
                                                    class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">Tutor
                                                    / Representante</span>
                                                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                            </div>

                                            <!-- Tutor propio -->
                                            <div v-if="datosRecibo?.tutor_nombre === 'propio'"
                                                class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/40 rounded-xl px-3.5 py-2.5">
                                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                        d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">El
                                                    beneficiario es su
                                                    propio tutor</span>
                                            </div>

                                            <!-- Tutor normal -->
                                            <div v-else
                                                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-3.5 py-2.5 space-y-1.5">
                                                <div class="flex justify-between">
                                                    <span class="text-xs text-slate-400">Nombre</span>
                                                    <span
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase">
                                                        {{ datosRecibo?.tutor?.apellido_tutor }} {{
                                                            datosRecibo?.tutor?.nombre_tutor }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-xs text-slate-400">CI</span>
                                                    <span
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{
                                                            datosRecibo?.tutor?.ci_tutor }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-xs text-slate-400">Teléfono</span>
                                                    <span v-if="datosRecibo?.tutor?.telefono"
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                        {{ datosRecibo?.tutor?.telefono }}
                                                    </span>
                                                    <span v-else class="text-xs text-rose-400 italic">Sin
                                                        teléfono</span>
                                                </div>
                                                <div class="flex justify-between gap-4">
                                                    <span class="text-xs text-slate-400 flex-shrink-0">Dirección</span>
                                                    <span v-if="datosRecibo?.tutor?.direccion"
                                                        class="text-xs font-semibold text-slate-700 dark:text-slate-200 text-right break-words">
                                                        {{ datosRecibo?.tutor?.direccion }}
                                                    </span>
                                                    <span v-else class="text-xs text-rose-400 italic">Sin
                                                        dirección</span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>

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
        <!-- TABLA DE DATOS -->
        <!-- ============================================================================ -->
        <DataTable :data="datosHabilitado" :columns="tableColumns" row-key="id_habilitado"
            empty-message="No se encontraron datos. ¡Habilite beneficiarios para continuar!">
            <!-- Slot personalizado para cada fila -->
            <template #row="{ item }">
                <!-- Columna: Mes -->
                <td scope="row"
                    class="text-center capitalize px-3 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    {{ getMonthNameFromNumber(item.es_retroactivo ? item.mes_original : item.mes) }}
                    <span v-if="item.es_retroactivo"
                        class="block text-[10px] font-semibold text-amber-600 normal-case">
                        Retroactivo
                    </span>
                </td>
                <!-- Columna: Aptitud -->
                <td class="text-center px-1 py-3">
                    <span
                        class="inline-flex items-center justify-center gap-1 text-xs font-medium px-2 py-0 rounded-full w-28"
                        :class="{
                            'bg-gray-100 text-gray-600': item.habilitado === null,
                            'bg-yellow-100 text-yellow-800': item.habilitado === true && item.es_retroactivo,
                            'bg-green-100 text-green-800': item.habilitado === true && !item.es_retroactivo,
                            'bg-red-100 text-red-800': item.habilitado === false,
                        }">
                        <Icon :icon-button="true" name="circleMinus" class-name="text-gray-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="14" v-if="item.habilitado === null" />
                        <Icon :icon-button="true" name="badgeCheckOutline"
                            :class-name="item.es_retroactivo ? 'text-yellow-600' : 'text-green-500'" fill="none"
                            stroke="currentColor" stroke-width="2" :size="14" v-if="item.habilitado === true" />
                        <Icon :icon-button="true" name="closeCircleOutline" class-name="text-red-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="14" v-if="item.habilitado === false" />
                        {{ item.habilitado === null ? 'Sin habilitar' : item.habilitado ? 'Habilitado' : 'Deshabilitado'
                        }}
                    </span>
                </td>

                <!-- Columna: Cobro -->
                <td class="text-center px-1 py-3">
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0 rounded-full"
                        :class="colorClasses[getPagoStatus(item).color]">
                        <Icon :icon-button="true" :name="getPagoStatus(item).icon"
                            :class-name="iconColorClasses[getPagoStatus(item).color]" fill="none" stroke="currentColor"
                            stroke-width="2" :size="14" />
                        {{ getPagoStatus(item).label }}
                    </span>
                </td>

                <!-- Columna: Gestión -->
                <td class="px-0 py-1 hidden sm:table-cell">
                    <div class="text-center text-gray-900">
                        <span class="text-xs line-clamp-1">
                            {{ item.gestion.gestion }}
                        </span>
                    </div>
                </td>

                <!-- Columna: Monto - Persona -->
                <td scope="row"
                    class="text-center px-1 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    Bs {{ item.monto }}
                </td>

                <!-- Columna: Estado -->
                <td scope="row"
                    class="text-center px-1 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    <li class="flex items-center space-x-2">
                        <div class="flex items-center space-x-1">
                            <span class="relative inline-block">
                                <a
                                    class="rounded-lg block p-0 text-gray-700 dark:text-gray-200 dark:hover:text-white relative">
                                    <!-- Icono: Activo -->
                                    <Icon v-if="item.estado_mes === 'activo'" :icon-button="true" name="checkCircle"
                                        class-name="text-green-500 pt-1" :size="17" />
                                    <!-- Icono: Baja Temporal -->
                                    <Icon v-if="item.estado_mes === 'baja_temporal'" :icon-button="true"
                                        name="timeCircle" class-name="text-orange-500 pt-1" :size="17" />
                                    <!-- Icono: Baja Definitiva -->
                                    <Icon v-if="item.estado_mes === 'baja_definitiva'" :icon-button="true"
                                        name="circleMinus" class-name="text-red-600 pt-1" :size="17" />
                                    <!-- Icono: Depurado -->
                                    <Icon v-if="item.estado_mes === 'depurado'" :icon-button="true" name="depurado"
                                        class-name="text-gray-500 pt-1" :size="17" />
                                    <!-- Icono: Pagos Suspendidos -->
                                    <Icon v-if="item.estado_mes === 'pagos_suspendidos'" :icon-button="true" name="cash"
                                        class-name="text-purple-500 pt-1" :size="17" />
                                </a>
                            </span>
                            <span class="text-gray-400">|</span>
                            <!-- En el template, columna Estado -->
                            <div class="font-medium" :class="getEstadoInfo(item.estado_mes).clase">
                                {{ getEstadoInfo(item.estado_mes).texto }}
                            </div>
                        </div>
                    </li>
                </td>

                <!-- Columna: Observación -->
                <td class="px-0 py-1 hidden sm:table-cell">
                    <div class="text-gray-700 dark:text-gray-300">
                        <span v-if="!item.observaciones_habilitado"
                            class="block text-center italic text-red-500 text-xs">
                            ninguna
                        </span>
                        <span v-else class="text-xs line-clamp-1 cursor-help" :title="item.observaciones_habilitado">
                            {{ item.observaciones_habilitado }}
                        </span>
                    </div>
                </td>

                <!-- Columna: Fecha Habilitado -->
                <td class="text-center px-3 py-3 whitespace-nowrap">
                    <div v-if="item.fecha_habilitado">
                        {{ formatDateTime(item.fecha_habilitado) }}
                    </div>
                    <div v-else class="text-base">
                        -
                    </div>
                </td>

                <!-- Columna: Fecha de Pago -->
                <td class="text-center px-3 py-3 whitespace-nowrap">
                    <div v-if="item.pago?.fecha_pago">
                        {{ formatDateTime(item.pago.fecha_pago) }}
                    </div>
                    <div v-else>
                        <span class="text-base">-</span>
                    </div>
                </td>
                <!-- Columna: Acciones -->
                <td class="text-center py-0">
                    <div v-if="!item.pago && (item.estado_mes !== 'activo' || !item.habilitado || item.pago_suspendido)" class="text-base">
                        -
                    </div>
                    <ul v-else class="flex items-center justify-center gap-1">
                        <div class="flex items-center gap-1">
                            <li v-if="!item.pago" class="relative">
                                <a v-if="can('registrar-pago')"
                                    @click.prevent="confirmar(item.id_habilitado, 'efectivo')"
                                    class="cursor-pointer block rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path fill-rule="evenodd"
                                            d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z"
                                            clip-rule="evenodd" />
                                        <path fill-rule="evenodd"
                                            d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z"
                                            clip-rule="evenodd" />
                                        <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                                    </svg>
                                </a>
                            </li>
                            <div v-else>
                                <!-- Sin descarga registrada → mostrar botón PDF -->
                                <!-- <a v-if="can('comprobante-pago') && !item.boleta_descargada"
                                        @click.prevent="registrarYDescargar(item)"
                                        class="cursor-pointer block rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                        <svg class="w-6 h-6 text-gray-800" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a> -->

                                <!-- Ya descargada → mostrar sello PAGADO -->
                                <!-- <div @click.prevent="selectedItem = item; item.boleta_descargada ? openModalPagado = true : createReporte(item)" -->
                                <div>
                                    <div @click.prevent="selectedItem = item; openModalPagado = true"
                                        class="cursor-pointer relative flex items-center justify-center w-20 h-14 select-none">
                                        <svg viewBox="0 0 56 56" class="w-full h-full"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="28" cy="28" r="21" fill="none"
                                                :stroke="isAnulado(item) ? '#dc2626' : item.es_retroactivo ? '#d97706' : '#16a34a'"
                                                stroke-width="2" stroke-dasharray="2.5 1" stroke-linecap="round" />
                                            <circle cx="28" cy="28" r="15" fill="none"
                                                :stroke="isAnulado(item) ? '#dc2626' : item.es_retroactivo ? '#d97706' : '#16a34a'"
                                                stroke-width="1.2" stroke-dasharray="2 2.5" stroke-linecap="round" />
                                            <g transform="rotate(-18, 28, 28)">
                                                <rect x="0" y="20" width="55" height="18" rx="2.5"
                                                    :fill="isAnulado(item) ? '#dc2626' : item.es_retroactivo ? '#d97706' : '#16a34a'" />
                                                <text x="28" y="31.5" text-anchor="middle"
                                                    :font-size="isAnulado(item) ? 7.5 : item.es_retroactivo ? 8.5 : 8.5"
                                                    fill="white" font-weight="900"
                                                    :letter-spacing="isAnulado(item) ? 1 : item.es_retroactivo ? 2 : 2"
                                                    font-family="Arial Black, sans-serif">
                                                    {{ isAnulado(item) ? 'ANULADO' : 'PAGADO' }}
                                                </text>
                                            </g>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ul>
                </td>
            </template>

            <!-- Slot personalizado para estado vacío -->
            <template #empty>
                <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-lg py-2">No se encontraron datos. ¡Habilite beneficiarios para continuar!</span>
            </template>
        </DataTable>

        <!-- ============================================================================ -->
        <!-- MODALES -->
        <!-- ============================================================================ -->

        <!-- Modal: Suspender Pagos -->
        <Transition name="fade">
            <Modal v-if="showSuspenderModal" :showHeader="false" :showFooter="false" maxWidth="max-w-md"
                @close="showSuspenderModal = false">
                <div class="py-4 text-center">
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="cash" class-name="text-purple-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="40" :height="40" />
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Suspender pagos
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 px-4">
                        Desde el mes seleccionado, este beneficiario no podrá cobrar hasta que reactives sus pagos
                        manualmente.
                    </p>
                </div>

                <div class="px-6 pb-2 space-y-4 text-left">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">
                            Suspender desde <span class="text-red-500">*</span>
                        </label>
                        <MesSuspensionPicker v-model="suspenderForm.fecha_inicio"
                            :meses-disponibles="mesesDisponiblesSuspension" :meses-pagados="mesesPagadosSuspension"
                            :error="suspenderForm.errors.fecha_inicio" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">
                            Motivo de la suspensión <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="suspenderForm.observaciones" rows="3"
                            placeholder="¿Por qué se suspenden los pagos?"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:border-purple-400"></textarea>
                        <p v-if="suspenderForm.errors.observaciones" class="text-xs text-red-500 mt-1">
                            {{ suspenderForm.errors.observaciones }}
                        </p>
                    </div>
                </div>

                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="showSuspenderModal = false"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cancelar
                            </Button>
                            <Button @click="submitSuspender" :disabled="suspenderForm.processing || !puedeSuspender"
                                :style="'items-center py-3 px-5 sm:px-12 sm:py-2.5 rounded-xl border relative w-40'"
                                :class="suspenderForm.processing || !puedeSuspender ? 'opacity-60 cursor-not-allowed bg-purple-400' : 'bg-purple-600 hover:bg-purple-500'"
                                class="text-white">
                                <span v-if="suspenderForm.processing"
                                    class="absolute inset-0 flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Procesando...
                                </span>
                                <span v-else class="whitespace-nowrap flex items-center justify-center">Suspender Pagos</span>
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal: Reactivar Pagos -->
        <Transition name="fade">
            <Modal v-if="showReactivarModal" :showHeader="false" :showFooter="false" maxWidth="max-w-md"
                @close="showReactivarModal = false">
                <div class="py-4 text-center">
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="checkCircle" class-name="text-emerald-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="40" :height="40" />
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Reactivar pagos
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 px-4">
                        Se levantará la suspensión iniciada en
                        <span class="font-semibold text-purple-600">{{
                            formatMonthYear(suspensionPagoActual?.fecha_inicio) }}</span>
                        y todos los meses pendientes volverán a estar disponibles para cobro.
                    </p>
                    <div v-if="suspensionPagoActual?.observaciones"
                        class="mt-3 mx-4 text-left bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2 text-xs text-slate-500">
                        <span class="font-medium text-slate-600 dark:text-slate-300">Motivo original: </span>{{
                            suspensionPagoActual.observaciones }}
                    </div>
                    <p v-if="suspensionPagoActual?.usuario_modificacion"
                        class="mt-2 text-xs text-slate-400">
                        Suspendido por <span class="capitalize font-semibold">{{ suspensionPagoActual.usuario_modificacion }}</span>
                    </p>
                </div>

                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="showReactivarModal = false"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cancelar
                            </Button>
                            <Button @click="reactivarPagos" :style="'py-3 px-8 sm:px-12 sm:py-2.5 rounded-xl'"
                                class="text-white bg-emerald-600 hover:bg-emerald-500">
                                Reactivar Pagos
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal: Detalle de Suspensión de Pagos (solo lectura, sin permiso para reactivar) -->
        <Transition name="fade">
            <Modal v-if="showDetalleSuspensionModal" :showHeader="false" :showFooter="false" maxWidth="max-w-md"
                @close="showDetalleSuspensionModal = false">
                <div class="py-4 text-center">
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="cash" class-name="text-purple-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="40" :height="40" />
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Pagos suspendidos
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 px-4">
                        Este beneficiario no puede cobrar desde
                        <span class="font-semibold text-purple-600">{{
                            formatMonthYear(suspensionPagoActual?.fecha_inicio) }}</span>.
                    </p>
                    <div v-if="suspensionPagoActual?.observaciones"
                        class="mt-3 mx-4 text-left bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2 text-xs text-slate-500">
                        <span class="font-medium text-slate-600 dark:text-slate-300">Motivo: </span>{{
                            suspensionPagoActual.observaciones }}
                    </div>
                    <p v-if="suspensionPagoActual?.usuario_modificacion"
                        class="mt-2 text-xs text-slate-400">
                        Suspendido por <span class="capitalize font-semibold">{{ suspensionPagoActual.usuario_modificacion }}</span>
                    </p>
                    <p class="mt-3 text-xs text-slate-400 px-4">
                        No tienes permiso para reactivar los pagos. Consulta con un administrador si crees que esto
                        es un error.
                    </p>
                </div>

                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center">
                            <Button @click="showDetalleSuspensionModal = false"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cerrar
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal: Confirmacion de Pago a Beneficiario -->
        <Transition name="fade">
            <Modal v-if="modalConfirm" :showHeader="false" :showFooter="false" maxWidth="max-w-md" @close="closeModal">
                <div class="py-4 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-red-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="warning" class-name="text-red-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="50" :height="50" />
                    </div>
                    <!-- Título -->
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Confirmar pago
                    </h2>

                    <!-- Mensaje principal -->
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        ¿Confirmas que el beneficiario <span class="font-semibold text-emerald-600">recibió su
                            pago</span>?
                    </p>

                    <!-- Advertencia -->
                    <p class="text-xs mt-2 text-red-500 dark:text-red-400">
                        Esta acción es irreversible.
                    </p>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="closeModal"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cancelar
                            </Button>
                            <Button @click="submit(idHabi, tipoHabi)" :disabled="form.processing"
                                :style="'items-center py-3 px-5 sm:px-12 sm:py-2.5 rounded-xl border relative w-36'"
                                :class="form.processing ? 'opacity-60 cursor-not-allowed bg-[rgb(var(--brand-400))]' : 'bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]'"
                                class="text-white">
                                <span v-if="form.processing"
                                    class="absolute inset-0 flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Procesando...
                                </span>
                                <span v-else class="whitespace-nowrap flex items-center justify-center">Confirmar
                                    Pago</span>
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal: Pago Realizado a Beneficiario -->
        <Transition name="fade">
            <Modal v-if="openModalPagado" :showHeader="true" :showFooter="false" maxWidth="max-w-md"
                @close="openModalPagado = false">

                <template #icon>
                    <Icon :icon-button="true" :name="isAnulado(selectedItem) ? 'closeCircleOutline' : 'checkCircle'"
                        class-name="text-white" :fill="isAnulado(selectedItem) ? 'none' : undefined"
                        :stroke="isAnulado(selectedItem) ? 'currentColor' : undefined"
                        :stroke-width="isAnulado(selectedItem) ? 2 : undefined" :size="22" />
                </template>
                <template #label1>{{ isAnulado(selectedItem) ? 'Pago Anulado' : 'Pago Realizado' }}</template>
                <template #label2>
                    {{ isAnulado(selectedItem) ?
                        'Este pago fue anulado y ya no es válido' : 'El pago fue registrado correctamente' }}
                </template>

                <!-- Body -->
                <div class="space-y-1.5 sm:space-y-3 ">

                    <!-- Aviso de anulación -->
                    <div v-if="isAnulado(selectedItem)"
                        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/40 rounded-xl px-4 py-2">
                        <button type="button" class="w-full flex items-center gap-3 text-left"
                            :disabled="!selectedItem?.pago?.anulacion"
                            @click="mostrarMotivoAnulacion = !mostrarMotivoAnulacion">
                            <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm font-medium text-red-700 dark:text-red-300 flex-1">
                                La información se conserva para fines de auditoría.
                            </p>
                            <svg v-if="selectedItem?.pago?.anulacion"
                                class="w-4 h-4 text-red-500 flex-shrink-0 transition-transform duration-200"
                                :class="mostrarMotivoAnulacion ? 'rotate-180' : 'rotate-0'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Motivo completo, quién anuló y cuándo -->
                        <div v-if="mostrarMotivoAnulacion && selectedItem?.pago?.anulacion"
                            class="mt-3 pt-3 border-t border-red-200 dark:border-red-700/40 space-y-2">
                            <div>
                                <p class="text-xs text-red-500 dark:text-red-300/80 mb-0.5">Motivo de la anulación</p>
                                <p class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap break-words">
                                    {{ selectedItem.pago.anulacion.observaciones }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-1">
                                <div v-if="selectedItem.pago.anulacion.usuario_modificacion">
                                    <p class="text-xs text-red-500 dark:text-red-300/80 mb-0.5">Anulado por</p>
                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                                        {{ selectedItem.pago.anulacion.usuario_modificacion }}
                                    </p>
                                </div>
                                <div v-if="selectedItem.pago.anulacion.created_at">
                                    <p class="text-xs text-red-500 dark:text-red-300/80 mb-0.5">Fecha</p>
                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                                        {{ formatDateTime(selectedItem.pago.anulacion.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usuario que registró -->
                    <div
                        class="flex items-center gap-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-700/40 rounded-xl px-4 py-3">
                        <div
                            class="w-11 h-11 rounded-full bg-gradient-to-br from-violet-500 to-purple-400 flex items-center justify-center shadow-md flex-shrink-0">
                            <span class="text-white font-bold text-sm uppercase">
                                {{ getInitials(selectedItem?.pago?.user?.nombre, selectedItem?.pago?.user?.apellido) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Usuario del sistema</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                                {{ toTitleCase(selectedItem?.pago?.user?.nombre) }} {{
                                    toTitleCase(selectedItem?.pago?.user?.apellido) }}
                            </p>
                            <p class="text-xs text-violet-500 dark:text-violet-300 truncate">{{
                                selectedItem?.pago?.user?.cargo
                            }}</p>
                        </div>
                    </div>

                    <!-- Separador: Estado -->
                    <div class="flex items-center gap-2 my-6">
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent"></div>
                        <div class="flex items-center justify-center">
                            <span class="text-sm text-slate-400">Detalles del Pago</span>
                        </div>
                        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent"></div>
                    </div>

                    <!-- Número de boleta -->
                    <div
                        class="flex items-center justify-between bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600/40 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-900 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-slate-500">N° de Boleta</p>
                        </div>
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200 tracking-wide">
                            # {{ selectedItem?.pago?.numero_boleta }}
                        </span>
                    </div>

                    <!-- Boleta, tipo pago, gestión, mes -->
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                            <p class="text-xs text-slate-500 mb-1">Gestión</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ selectedItem?.es_retroactivo && selectedItem?.mes_original === 12
                                    ? selectedItem?.gestion?.gestion - 1
                                    : selectedItem?.gestion?.gestion }}
                            </p>
                        </div>
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                            <p class="text-xs text-slate-500 mb-1">Mes</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 capitalize">
                                {{ getMonthNameFromNumber(selectedItem?.es_retroactivo ? selectedItem?.mes_original : selectedItem?.mes) }}
                                <span v-if="selectedItem?.es_retroactivo" class="ml-1 text-[10px] font-semibold text-amber-600 normal-case whitespace-nowrap">
                                    (Retroactivo)
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Monto destacado -->
                    <div class="flex items-center justify-between rounded-xl px-4 py-3 border" :class="isAnulado(selectedItem)
                        ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700/40'
                        : selectedItem?.es_retroactivo
                            ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700/40'
                            : 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-700/40'">
                        <span class="text-sm font-medium" :class="isAnulado(selectedItem)
                            ? 'text-red-700 dark:text-red-300'
                            : selectedItem?.es_retroactivo
                                ? 'text-yellow-700 dark:text-yellow-300'
                                : 'text-emerald-700 dark:text-emerald-300'">
                            {{ isAnulado(selectedItem) ? 'Monto Anulado' : selectedItem?.es_retroactivo ? 'Monto Retroactivo' : 'Monto Pagado' }}
                        </span>
                        <span class="text-xl font-bold" :class="isAnulado(selectedItem)
                            ? 'text-red-600 dark:text-red-400 line-through'
                            : selectedItem?.es_retroactivo
                                ? 'text-yellow-600 dark:text-yellow-400'
                                : 'text-emerald-600 dark:text-emerald-400'">
                            Bs. {{ selectedItem?.pago?.monto }}
                        </span>
                    </div>

                    <!-- Fecha de pago -->
                    <div
                        class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                        <svg class="w-4 h-4 text-slate-900 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <p class="text-xs text-slate-500">Fecha y hora del pago</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ formatDateTime(selectedItem?.pago?.fecha_pago) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="openModalPagado = false"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border'" :class="can('superusuario') || selectedItem?.pago?.user?.id === page.props.auth.user?.id
                                    ? 'text-slate-700 bg-slate-100 hover:bg-slate-200 border-gray-200'
                                    : 'text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]'">
                                Aceptar
                            </Button>

                            <Button
                                v-if="!isAnulado(selectedItem) && (can('superusuario') || selectedItem?.pago?.user?.id === page.props.auth.user?.id)"
                                @click="openModalPagado = false; createReporte(selectedItem)"
                                :style="'py-3 px-8 sm:px-12 sm:py-2.5 rounded-xl'"
                                class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                                Comprobante
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal Comprobante PDF -->
        <Transition name="fade">
            <Modal v-if="mostrarModal" maxWidth="max-w-2xl" :showFooter="false" styleBody="p-0 overflow-hidden"
                @close="cerrarModal">
                <template #icon>
                    <Icon :icon-button="true" name="receipt" class-name="text-white" :size="22" />
                </template>

                <template #label1>Vista Previa del Comprobante</template>
                <template #label2>{{ pdfNombreLegible }}</template>

                <!-- PDF con escala adaptable -->
                <div v-if="pdfUrl" class="w-full overflow-hidden relative bg-[#525659]">
                    <iframe :src="`${pdfUrl}#toolbar=0&navpanes=0&scrollbar=0&view=FitH`" class="border-0 block w-full"
                        style="height: 68vh;" />
                </div>

                <!-- Footer -->
                <template #footer>
                    <div
                        class="flex items-center justify-center sm:justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <!-- Imprimir -->
                        <Button type="button" @click="registrarImpresionLog(currentPdfItem); imprimirPDFSync()"
                            :style="'px-9 sm:px-12 sm:py-2.5 rounded-xl border border-gray-500'"
                            class="hidden sm:block text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:text-white">
                            <div class="flex items-center gap-2">
                                <Icon :icon-button="true" name="printer" class-name="text-black" :size="17" />
                                Imprimir
                            </div>
                        </Button>

                        <!-- Descargar -->
                        <Button type="button" @click="descargarPDF" :style="'py-2 px-3 sm:px-6 sm:py-2.5 rounded-xl'"
                            class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                            <div class="flex items-center gap-2">
                                <Icon :icon-button="true" name="arrowDownload" fill="none" stroke="currentColor"
                                    stroke-width="2" class-name="text-white" :size="22" />
                                Descargar PDF
                            </div>
                        </Button>

                        <!-- Cerrar -->
                        <!-- <Button type="button" @click="cerrarModal"
                                :style="'py-2 px-3 sm:px-6 sm:py-2.5 rounded-xl'"
                                class="text-slate-700 bg-white hover:bg-slate-100 border border-gray-200">
                                Cerrar
                            </Button> -->
                    </div>
                </template>
            </Modal>

        </Transition>

    </AppLayout>
</template>
