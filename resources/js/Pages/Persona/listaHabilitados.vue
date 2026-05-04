<script setup>
// ============================================================================
// IMPORTS
// ============================================================================

import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Link, router, usePage, useForm, Head } from '@inertiajs/vue3';


/**
 * Componentes
 */
import Paginacion from '@/components/Paginacion.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';
import Rutas from '@/components/Rutas.vue';
import PagoIndex from '../Reporte/pagoIndex.vue';
import Mensajes from '@/components/Mensajes.vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/DataTable.vue';
import Dropdown from '@/components/Dropdown.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';
import { useComprobantePDF } from '@/composables/useComprobantePDF';
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import ModalConfirmacion from '@/components/ModalConfirmacion.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

/**
 * Props principales
 */
const datosHabilitado = computed(() => page.props.datosHabilitado);
const datosRecibo = computed(() => page.props.datosRecibo);
const gestiones = computed(() => page.props.gestiones);
const filters = computed(() => page.props.filters);
const añoSeleccionado = computed(() => page.props.añoSeleccionado);
const { generarComprobante, mostrarModal, pdfUrl, pdfNombre, cerrarModal } = useComprobantePDF();

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const reporteRef = ref(null);
const modalConfirm = ref(false);
const idHabi = ref('');
const tipoHabi = ref('');
const openModalPagado = ref(false);
const selectedItem = ref(null);
const showPersonaModal = ref(false);
const openModalHabilitar = ref(false);
const currentPdfItem = ref(null);

// Después de tus refs, agrega:
const mensajes = ref([]);

const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// Selector de año
const getYearValue = (yearData) =>
    typeof yearData === 'object' && yearData?.gestion ? yearData.gestion : yearData;

const selectedYear = ref(getYearValue(filters.value?.año) ?? añoSeleccionado.value?.gestion);

const openPersonaModal = () => {
    showPersonaModal.value = true;
};

// Función para cerrar el modal
const closePersonaModal = () => {
    showPersonaModal.value = false;
};

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

// ✅ FUNCIÓN PARA GENERAR EL REPORTE
const createReporte = async (item) => {
    currentPdfItem.value = item;
    try {
        await generarComprobante(
            datosRecibo.value,
            item,
            item.pago.user,                 // ← usuario que registró el pago
            item.pago.user.digital_signature
        );
    } catch (error) {
        console.error('Error generando comprobante:', error);
        mostrarMensaje('advertencia', 'Error', 'No se pudo generar el comprobante.');
    }
};

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

const confirmar = (idHabilitado, tipo) => {
    idHabi.value = idHabilitado;
    tipoHabi.value = tipo;
    modalConfirm.value = true;
}

// ✅ CORRECCIÓN: Usar useForm de Inertia en lugar de vee-validate
const form = useForm({
    id_habilitado: '',
    id_tutor: '',
    tipo: ''
});

const closeModal = () => {
    modalConfirm.value = false;
}

// Definición de columnas para la tabla de habilitados
const tableColumns = [
    { label: 'Mes', field: 'mes', headerClass: 'text-center px-3', cellClass: 'capitalize whitespace-nowrap' },
    { label: 'Habilitado', field: 'habilitado', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'pagado', field: 'pago', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Gestión', field: 'gestion', headerClass: 'text-center px-3 hidden sm:block', cellClass: 'whitespace-nowrap' },
    { label: 'Monto - Persona', field: 'monto', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Estado', field: 'estado', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Observación', field: 'observaciones_habilitado', headerClass: 'text-center px-3 hidden sm:block', cellClass: '' },
    { label: 'Fecha Habilitado', field: 'fecha_habilitado', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Fecha Pago', field: 'fecha_pago', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center px-3', cellClass: '' }
];

const getUrl = (ruta, id) => {
    const url = route(ruta, window.btoa(id));
    router.visit(url);
}

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';

    const [datePart, timePart] = dateTimeString.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour, minute] = timePart.split(':');

    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return `${dateStr} ${hour}:${minute}`;
};

const getEstadoInfo = (estado) => {
    if (estado === 'activo') return { texto: 'Activo', clase: 'text-green-600' };
    if (estado === 'baja_temporal') return { texto: 'Baja Temporal', clase: 'text-orange-600' };
    if (estado === 'baja_definitiva') return { texto: 'Baja Definitiva', clase: 'text-red-600' };
    if (estado === 'depurado') return { texto: 'Depurado', clase: 'text-gray-600' };
    return { texto: '-', clase: 'text-gray-600' };
};

const descargarPDF = async () => {
    if (!pdfUrl.value || !pdfNombre.value) return;
    await registrarDescargaLog();
    const a = document.createElement('a');
    a.href = pdfUrl.value;
    a.download = pdfNombre.value;
    a.click();
};

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

const registrarImpresionLog = async (item) => {
    if (!item) return;
    try {
        console.log('entra al log Imprimir', item);
        await axios.post(route('pago.imprimirBoleta'), {
            id_pago: item.pago.id_pago,
            gestion: item.gestion.gestion,
            mes: item.mes,
        });
    } catch (error) {
        mostrarMensaje('advertencia', 'Error', 'No se pudo registrar la impresión.');
    }
};

const registrarDescargaLog = async () => {
    if (!currentPdfItem.value) return;
    try {
        console.log('entra al log');
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

const resetearDescarga = async (idPago) => {
    try {
        const { data } = await axios.delete(route('pago.resetearDescarga', idPago));

        if (data.modo === 'sin_cambios') {
            mostrarMensaje('info', 'Sin cambios', 'Esta descarga ya fue reseteada anteriormente.');
            return;
        }

        openModalPagado.value = false;  // ← también faltaba .value aquí
        router.reload({ only: ['datosHabilitado'] });
        mostrarMensaje('correcto', 'Descarga reseteada', 'La descarga se reseteo correctamente.');
    } catch (error) {
        const msg = error.response?.data?.error ?? 'No se pudo resetear la descarga.';
        mostrarMensaje('advertencia', 'Error', msg);
    }
};



const getInitials = (nombre, apellido) =>
    `${(nombre || '')[0] ?? ''}${(apellido || '')[0] ?? ''}`.toUpperCase() || '?';

const openModal = (item) => {
    selectedItem.value = {
        ...item,
    };
    openModalHabilitar.value = true;
};

const submit = (idHabilitado, tipo) => {
    if (form.processing) return;

    form.tipo = tipo;
    form.id_habilitado = idHabilitado;
    form.post(route('pago.store'), {
        onSuccess: () => {
            mostrarMensaje('correcto', 'Pago exitoso', 'El pago se registró correctamente.');
            modalConfirm.value = false;
            form.reset();

            // Recargar datos y luego abrir el modal con el item actualizado
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
        onError: () => {
            mostrarMensaje('advertencia', 'Pago no Registrado', 'El pago no se registró correctamente.');
        }
    });
};
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex h-screen -ml-1 bg-gray-200 font-roboto">
        <!-- Sidebar de navegación -->
        <Sidebar />

        <!-- Contenedor principal -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- ============================================================================ -->
            <!-- SISTEMA DE MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                @close="cerrarMensaje" />

            <!-- Header -->
            <Header class="mb-0" />

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Meses Disponibles</h1>
                <Rutas label1="Inicio" label2="Beneficiarios" label3="Meses Disponibles" class="sm:text-xs" />
            </div>

            <Transition name="fade">
                <ModalConfirmacion v-if="modalConfirm" tipo="pago" :id="idHabi" :processing="form.processing"
                    @confirmar="submit" @close="closeModal" />
            </Transition>

            <!-- Header Principal -->
            <div class="bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="py-3">
                        <!-- Título y Selector de Gestión -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-0 ">
                            <div class="flex items-center gap-3">
                                <div v-if="selectedYear" class="flex items-center">
                                    <h1
                                        class="text-3xl p-1.5 font-bold text-slate-800 text-center tracking-tight bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                                        GESTIÓN
                                    </h1>
                                    <Dropdown align="left" width="60">
                                        <template #trigger="{ open }">
                                            <button
                                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow"
                                                type="button">
                                                <span class="text-xl font-semibold text-slate-700">
                                                    {{ selectedYear }}
                                                </span>
                                                <svg class="w-4 h-4 text-gray-500 transition-transform flex-shrink-0"
                                                    :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <div
                                                class="w-64 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl overflow-hidden shadow-xl border border-slate-200">
                                                <!-- Lista scrolleable -->
                                                <div
                                                    class="max-h-60 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-transparent">
                                                    <ul class="py-2">
                                                        <li v-if="!gestiones || gestiones.length === 0"
                                                            class="px-4 py-3">
                                                            <div class="flex items-center gap-3 text-slate-400">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                                </svg>
                                                                <span class="text-sm">No hay datos disponibles</span>
                                                            </div>
                                                        </li>
                                                        <li v-for="year in gestiones" :key="year.gestion">
                                                            <a href="#" @click.prevent="selectGestion(year)"
                                                                class="flex items-center justify-between px-4 py-3 text-sm hover:bg-blue-50 transition-all duration-150 group"
                                                                :class="selectedYear.toString() === year.gestion.toString()
                                                                    ? 'bg-blue-100 text-blue-800 font-semibold border-r-4 border-blue-500'
                                                                    : 'text-slate-700 hover:text-blue-700'">
                                                                <span class="flex items-center gap-2">
                                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                                                        fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                    {{ year.gestion }}
                                                                </span>
                                                                <svg v-if="selectedYear.toString() === year.gestion.toString()"
                                                                    class="w-5 h-5 text-blue-600" fill="currentColor"
                                                                    viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd"
                                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                        clip-rule="evenodd" />
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <!-- Información del Beneficiario (Solo en Desktop) -->
                            <div class="hidden lg:block relative">
                                <div @click="openPersonaModal"
                                    class="border border-gray-200 rounded-lg px-4 py-1 shadow-sm cursor-pointer hover:shadow-md hover:border-gray-300 transition-all duration-200">

                                    <div class="flex items-center gap-3">
                                        <!-- Avatar iniciales -->
                                        <div
                                            class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-medium text-blue-700">
                                                {{ getInitials(datosRecibo?.nombre_persona,
                                                    datosRecibo?.apellido_persona) }}
                                            </span>
                                        </div>

                                        <!-- Nombre -->
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-[10px] text-gray-500 font-medium uppercase tracking-wide leading-none">
                                                Beneficiario
                                            </p>
                                            <p
                                                class="text-[13px] font-semibold text-gray-900 capitalize truncate mt-0.5 leading-tight">
                                                {{ datosRecibo?.apellido_persona }} {{ datosRecibo?.nombre_persona }}
                                            </p>
                                        </div>

                                        <!-- Estado + CI -->
                                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700">
                                                Activo
                                            </span>
                                            <span class="text-[10px] text-gray-500">CI:
                                                <span class="text-gray-900">{{ datosRecibo?.ci_persona }}</span>
                                            </span>
                                        </div>

                                        <!-- Chevron -->
                                        <svg class="w-3.5 h-3.5 text-gray-500 transition-transform flex-shrink-0"
                                            :class="showPersonaModal ? 'rotate-180' : ''" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Modal Expandido -->
                                <Transition enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-1">
                                    <div v-if="showPersonaModal" class="absolute top-full right-0 mt-2 z-50"
                                        @click.stop>
                                        <div
                                            class="bg-gradient-to-br from-white to-gray-50 w-96 border border-gray-200 rounded-xl p-4 shadow-2xl">

                                            <!-- Botón cerrar -->
                                            <button @click="closePersonaModal"
                                                class="absolute top-2 right-2 p-2 rounded-full bg-red-200 hover:bg-red-400 transition-colors"
                                                title="Cerrar (ESC)">
                                                <svg class="w-4 h-4 text-red-500 hover:text-red-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>

                                            <div class="mb-0">
                                                <div
                                                    class="flex justify-between p-3 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg border-gray-300">
                                                    <h2 class="text-base font-semibold text-gray-800">Datos del
                                                        Beneficiario</h2>
                                                </div>

                                                <div
                                                    class="bg-white border-x-2 border-b-2 border-gray-300 rounded-b-lg px-3 py-3 space-y-3">

                                                    <!-- Beneficiario -->
                                                    <div class="bg-gray-50 p-2 rounded border">
                                                        <h3 class="text-xs font-semibold text-gray-700 mb-2">
                                                            Beneficiario</h3>
                                                        <div class="grid grid-cols-[6rem_1fr] gap-x-2 gap-y-1 text-xs">
                                                            <span class="text-gray-500">Nombre:</span>
                                                            <span class="uppercase font-medium break-words">
                                                                {{ datosRecibo?.apellido_persona }} {{
                                                                    datosRecibo?.nombre_persona }}
                                                            </span>
                                                            <span class="text-gray-500">CI:</span>
                                                            <span class="font-medium">{{ datosRecibo?.ci_persona
                                                            }}</span>
                                                            <span class="text-gray-500">Distrito:</span>
                                                            <span class="font-medium break-words">{{
                                                                datosRecibo?.distrito }}</span>
                                                            <span class="text-gray-500">Discapacidad:</span>
                                                            <span class="capitalize break-words">{{
                                                                datosRecibo?.carnet?.discapacidad }}</span>
                                                        </div>
                                                    </div>

                                                    <!-- Tutor -->
                                                    <div class="bg-gray-50 p-2 rounded border">
                                                        <h3 class="text-xs font-semibold text-gray-700 mb-2">Tutor /
                                                            Representante</h3>

                                                        <!-- Tutor propio -->
                                                        <div v-if="datosRecibo?.tutor_nombre === 'propio'"
                                                            class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded px-3 py-2">
                                                            <svg class="w-4 h-4 text-blue-500 flex-shrink-0"
                                                                fill="currentColor" viewBox="0 0 24 24">
                                                                <path fill-rule="evenodd"
                                                                    d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <span class="text-xs font-medium text-blue-700">
                                                                El beneficiario es su propio tutor
                                                            </span>
                                                        </div>

                                                        <!-- Tutor normal -->
                                                        <div v-else
                                                            class="grid grid-cols-[6rem_1fr] gap-x-2 gap-y-1 text-xs">
                                                            <span class="text-gray-500">Nombre:</span>
                                                            <span class="uppercase font-medium break-words">
                                                                {{ datosRecibo?.tutor?.apellido_tutor }} {{
                                                                    datosRecibo?.tutor?.nombre_tutor }}
                                                            </span>
                                                            <span class="text-gray-500">CI:</span>
                                                            <span class="font-medium">{{ datosRecibo.tutor?.ci_tutor
                                                                }}</span>
                                                            <span class="text-gray-500">Teléfono:</span>
                                                            <span v-if="datosRecibo.tutor?.telefono"
                                                                class="font-medium">
                                                                {{ datosRecibo.tutor?.telefono }}
                                                            </span>
                                                            <span v-else class="text-red-500 italic">Sin teléfono</span>
                                                            <span class="text-gray-500">Dirección:</span>
                                                            <span v-if="datosRecibo.tutor?.direccion"
                                                                class="font-medium break-words">
                                                                {{ datosRecibo.tutor?.direccion }}
                                                            </span>
                                                            <span v-else class="text-red-500 italic">Sin
                                                                dirección</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Backdrop transparente para cerrar al hacer clic afuera -->
                            <Transition enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0" enter-to-class="opacity-100"
                                leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                                <div v-if="showPersonaModal" @click="closePersonaModal" class="fixed inset-0 z-40">
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uso del componente de tabla para habilitados -->
            <DataTable :data="datosHabilitado" :columns="tableColumns" row-key="id_habilitado"
                empty-message="No se encontraron datos. ¡Habilite beneficiarios para continuar!">
                <!-- Slot personalizado para cada fila -->
                <template #row="{ item }">
                    <!-- Columna: Mes -->
                    <td scope="row"
                        class="text-center capitalize px-3 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ getMonthNameFromNumber(item.mes) }}
                    </td>

                    <!-- Columna: Habilitado -->
                    <th scope="col" class="px-3 py-3">
                        <div class="rounded flex items-center justify-center">
                            <div class="h-2.5 w-2.5 rounded-full ring-2"
                                :class="item.habilitado ? 'ring-green-300 bg-green-500' : 'ring-gray-300 bg-gray-500'">
                            </div>
                        </div>
                    </th>

                    <!-- Columna: Pagado -->
                    <th scope="col" class="text-center px-3 py-3">
                        <div class="rounded flex items-center justify-center">
                            <div v-if="item.pago">
                                <div class="h-2.5 w-2.5 rounded-full ring-2 ring-green-300 bg-green-500"></div>
                            </div>
                            <div v-else-if="item.habilitado === 1"
                                class="flex flex-col items-center justify-center relative">
                                <div class="h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-red-200"></div>
                                <div class="w-2.5 h-2.5 ripple-background bg-red-500 absolute animate-ping"></div>
                            </div>
                            <div v-else>
                                <div class="h-2.5 w-2.5 rounded-full ring-2 ring-gray-300 bg-gray-500"></div>
                            </div>
                        </div>
                    </th>

                    <!-- Columna: Gestión -->
                    <td scope="row"
                        class="text-center capitalize px-3 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white hidden sm:block">
                        {{ item.gestion.gestion }}
                    </td>

                    <!-- Columna: Monto - Persona -->
                    <td scope="row"
                        class="text-center px-3 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        Bs {{ item.monto }}
                    </td>

                    <!-- Columna: Estado -->
                    <td scope="row"
                        class="text-center px-3 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
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
                                        <svg v-if="item.estado_mes === 'depurado'" class="w-4 h-4 text-gray-500"
                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                                                clip-rule="evenodd" />
                                        </svg>
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
                    <td class="text-center px-3 py-3 hidden sm:block">
                        <div class="text-xs text-gray-700 dark:text-gray-300">
                            <span v-if="!item.observaciones_habilitado" class="block text-center italic text-red-400">
                                ninguna
                            </span>
                            <span v-else>
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
                        <div v-if="(item.estado_mes !== 'activo' && !item.pago) || !item.habilitado" class="text-base">
                            -
                        </div>
                        <ul v-else class="flex items-center justify-center gap-1">
                            <div class="flex items-center gap-1">
                                <li v-if="!item.pago" class="relative">
                                    <a v-if="can('registrar-pago')"
                                        @click.prevent="confirmar(item.id_habilitado, 'efectivo')"
                                        class="cursor-pointer block rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
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
                                                <circle cx="28" cy="28" r="21" fill="none" stroke="#16a34a"
                                                    stroke-width="2" stroke-dasharray="2.5 1" stroke-linecap="round" />
                                                <circle cx="28" cy="28" r="15" fill="none" stroke="#16a34a"
                                                    stroke-width="1.2" stroke-dasharray="2 2.5"
                                                    stroke-linecap="round" />
                                                <g transform="rotate(-18, 28, 28)">
                                                    <rect x="0" y="20" width="55" height="18" rx="2.5" fill="#16a34a" />
                                                    <text x="28" y="31.5" text-anchor="middle" font-size="8.5"
                                                        fill="white" font-weight="900" letter-spacing="2"
                                                        font-family="Arial Black, sans-serif">PAGADO</text>
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
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-lg py-2">No se encontraron datos. ¡Habilite beneficiarios para continuar!</span>
                </template>
            </DataTable>

            <Transition name="fade">
                <Modal v-if="openModalPagado" :showHeader="true" :showFooter="false" maxWidth="max-w-md"
                    @close="openModalPagado = false">

                    <template #icon>
                        <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
                                clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template #label1>Pago Realizado</template>
                    <template #label2>El pago fue registrado correctamente</template>

                    <!-- Body -->
                    <div class="space-y-1.5 sm:space-y-3 ">

                        <!-- Usuario que registró -->
                        <div
                            class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/40 rounded-xl px-4 py-3">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-cyan-400 flex items-center justify-center shadow-md flex-shrink-0">
                                <span class="text-white font-bold text-sm">
                                    {{ selectedItem?.pago?.user?.nombre?.charAt(0) }}{{
                                        selectedItem?.pago?.user?.apellido?.charAt(0) }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-slate-800 mb-0.5">Pagado por</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">
                                    {{ selectedItem?.pago?.user?.nombre }} {{ selectedItem?.pago?.user?.apellido }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-blue-300 truncate">{{
                                    selectedItem?.pago?.user?.cargo }}
                                </p>
                            </div>
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
                                    {{ selectedItem?.gestion?.gestion }}
                                </p>
                            </div>
                            <div
                                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                                <p class="text-xs text-slate-500 mb-1">Mes</p>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 capitalize">
                                    {{ getMonthNameFromNumber(selectedItem?.mes) }}
                                </p>
                            </div>
                        </div>

                        <!-- Monto destacado -->
                        <div
                            class="flex items-center justify-between bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/40 rounded-xl px-4 py-3">
                            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Monto Pagado</span>
                            <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
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

                    <template #footer>
                        <div class="border-t border-gray-100 dark:border-gray-700/50 py-5">
                            <div class="flex justify-center gap-3">

                                <!--  <Button v-if="can('superusuario') && selectedItem?.boleta_descargada"
                                    @click.prevent="resetearDescarga(selectedItem?.pago?.id_pago)"
                                    :style="'py-2 px-3 sm:px-12 sm:py-2.5 rounded-xl border border-red-500'"
                                    class="text-red-500 bg-red-200 hover:bg-red-100">
                                    <div class="flex items-center gap-2">
                                        Resetear Boleta
                                    </div>
                                </Button> -->

                                <Button @click="openModalPagado = false"
                                    :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-500'"
                                    class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                    Aceptar
                                </Button>

                                <Button v-if="selectedItem?.pago?.user?.id === page.props.auth.user?.id"
                                    @click="openModalPagado = false; createReporte(selectedItem)"
                                    :style="'py-3 px-8 sm:px-12 sm:py-2.5 rounded-xl'"
                                    class="text-white bg-blue-600 hover:bg-blue-500">
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
                    fondoIcon="bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-blue-300"
                    @close="cerrarModal">
                    <template #icon>
                        <Icon :icon-button="true" name="filePDF" class-name="text-white" :size="22" />
                    </template>

                    <template #label1>Vista Previa del Comprobante</template>
                    <template #label2>{{ pdfNombre }}</template>

                    <!-- PDF con escala adaptable -->
                    <div v-if="pdfUrl" class="w-full overflow-hidden relative bg-[#525659]">
                        <iframe :src="`${pdfUrl}#toolbar=0&navpanes=0&scrollbar=0&view=FitH`"
                            class="border-0 block w-full" style="height: 68vh;" />
                    </div>

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
                            <Button type="button" @click="descargarPDF"
                                :style="'py-2 px-3 sm:px-6 sm:py-2.5 rounded-xl'"
                                class="text-white bg-blue-700 hover:bg-blue-600">
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
            <Footer />
        </div>
    </div>
</template>
