<script setup>
import Paginacion from '@/components/Paginacion.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';
import { computed, ref } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import Rutas from '@/components/Rutas.vue';
import { Head } from '@inertiajs/vue3';
import PagoIndex from '../Reporte/pagoIndex.vue';
import ModalConfirmacion from '@/components/ModalConfirmacion.vue';
import Mensajes from '@/components/Mensajes.vue';
import DataTable from '@/components/DataTable.vue';
import { can } from '@/lib/can';
// ✅ IMPORTAR EL COMPOSABLE
import { useComprobantePDF } from '@/composables/useComprobantePDF';
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';

const page = usePage();
const datosHabilitado = computed(() => page.props.datosHabilitado);
const datosRecibo = computed(() => page.props.datosRecibo);
// ✅ DESTRUCTURAR LA FUNCIÓN DEL COMPOSABLE
const { generarComprobante } = useComprobantePDF();

// Muestra TODAS las props que llegan


// console.log('TODAS LAS PROPS:', page.props);
//console.log('datosRecibo:', page.props.datosRecibo);
//console.log('totalHabilitado:', page.props.totalHabilitado);

//console.log(datosRecibo?.value);

const reporteRef = ref(null);
const modalConfirm = ref(false);
const idHabi = ref('');
const tipoHabi = ref('');
const openModalPagado = ref(false);
const selectedItem = ref(null);

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

// ✅ FUNCIÓN PARA GENERAR EL REPORTE
const createReporte = async (item) => {
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
    { label: 'Gestión', field: 'gestion', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Monto - Persona', field: 'monto', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Estado', field: 'estado', headerClass: 'text-center px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Observación', field: 'observaciones_habilitado', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Fecha Habilitado', field: 'fecha_habilitado', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Fecha de Pago', field: 'fecha_pago', headerClass: 'text-center px-3', cellClass: '' },
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
    return { texto: '-', clase: 'text-gray-600' };
};

const submit = (idHabilitado, tipo) => {
    form.tipo = tipo;
    form.id_habilitado = idHabilitado;
    form.post(route('pago.store'), {
        onSuccess: () => {
            modalConfirm.value = false;
            form.reset();
            mostrarMensaje('correcto', 'Pago exitoso', 'El pago se registró correctamente.');
            // ✅ Recarga primero, LUEGO muestra el mensaje
            router.reload();
        },
        onError: () => {
            mostrarMensaje('advertencia', 'Pago no Registrado', 'El pago no se registró correctamente.');
        }
    });
}

const registrarYDescargar = async (item) => {
    try {
        await axios.post(route('pago.descargarBoleta'), {
            id_pago: item.pago.id_pago,
            gestion: item.gestion.gestion,
            mes: item.mes,
        });
    } catch (error) {
        mostrarMensaje('advertencia', 'Error', 'No se pudo registrar la descarga.');
        return;
    }

    try {
        await createReporte(item);
        router.reload({ only: ['datosHabilitado'] });
    } catch (error) {
        // Log registrado pero PDF falló — avisar sin bloquear
        mostrarMensaje('advertencia', 'Advertencia', 'Boleta registrada pero el PDF no se generó. Contacte al administrador.');
        router.reload({ only: ['datosHabilitado'] });
    }
};
</script>

<template>

    <Head title="UMADIS" />
    <div class="flex h-screen bg-gray-200 font-roboto">
        <Sidebar />
        <div class="flex-1 flex flex-col">
            <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
                <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                    @close="cerrarMensaje" />
            </div>
            <Header class="mb-0" />
            <Transition name="fade">
                <ModalConfirmacion v-if="modalConfirm" tipo="pago" :id="idHabi" @confirmar="submit"
                    @close="closeModal" />
            </Transition>
            <div class="py-2">
                <div class="px-5 py-1 flex justify-between">
                    <h1 class="font-semibold text-2xl">Meses Disponibles</h1>
                    <Rutas label1="Inicio" label2="Beneficiarios" label3="Meses Disponibles" />
                </div>
            </div>

            <div class="p-4 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg mr-1 ">
                <div class="mr-1 mb-0">
                    <div
                        class="flex justify-between p-3 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg border-gray-300">
                        <h2 class="text-base font-semibold text-gray-800">Datos para Pago de Bono</h2>
                    </div>

                    <div class="bg-white border-x-2 border-b-2 border-gray-300 rounded-b-lg px-3 pt-1">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="bg-gray-50 p-2 rounded border">
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Beneficiario</h3>
                                <div class="space-y-0.5 text-xs">
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">Nombre:</span>
                                        <span class="capitalize font-medium">{{
                                            datosRecibo?.apellido_persona }} {{ datosRecibo?.nombre_persona }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">CI:</span>
                                        <span class="font-medium">{{ datosRecibo?.ci_persona }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">Distrito:</span>
                                        <span class="font-medium">{{ datosRecibo?.distrito }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">Discapacidad:</span>
                                        <span class="capitalize">{{ datosRecibo?.carnet?.discapacidad }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-2 rounded border">
                                <h3 class="text-xs font-semibold text-gray-700 mb-1">Tutor / Representante</h3>
                                <div class="space-y-0.5 text-xs">
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">Nombre:</span>
                                        <span class="capitalize font-medium">
                                            {{ datosRecibo?.tutor?.apellido_tutor }} {{ datosRecibo?.tutor?.nombre_tutor
                                            }}
                                        </span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">CI:</span>
                                        <span class="font-medium">{{ datosRecibo.tutor?.ci_tutor }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">Teléfono:</span>
                                        <span v-if="datosRecibo.tutor?.telefono">{{ datosRecibo.tutor?.telefono
                                            }}</span>
                                        <span v-else class="text-red-500 italic">Sin teléfono</span>
                                    </div>
                                    <div class="flex">
                                        <span class="text-gray-600 w-20 flex-shrink-0">Dirección:</span>
                                        <span v-if="datosRecibo.tutor?.direccion">{{ datosRecibo.tutor?.direccion
                                            }}</span>
                                        <span v-else class="text-red-500 italic">Sin dirección</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uso del componente de tabla para habilitados -->
            <DataTable :data="datosHabilitado?.data" :columns="tableColumns" row-key="id_habilitado"
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
                        class="text-center capitalize px-3 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
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
                                        class="rounded-lg block px-0 py-2 text-gray-700 dark:text-gray-200 dark:hover:text-white relative">
                                        <!-- Verde para ACTIVO -->
                                        <svg v-if="item.estado_mes === 'activo'"
                                            class="w-4 h-4 text-green-500 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
                                                clip-rule="evenodd" />
                                        </svg>

                                        <!-- Naranja para BAJA TEMPORAL -->
                                        <svg v-if="item.estado_mes === 'baja_temporal'"
                                            class="w-4 h-4 text-orange-500 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z"
                                                clip-rule="evenodd" />
                                        </svg>

                                        <!-- Rojo para BAJA DEFINITIVA -->
                                        <svg v-if="item.estado_mes === 'baja_definitiva'"
                                            class="w-4 h-4 text-red-600 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z"
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
                    <td class="text-center px-3 py-3">
                        <div class="text-xs text-gray-700 dark:text-gray-300">
                            <span v-if="!item.observaciones_habilitado" class="block text-center italic text-gray-500">
                                ninguna
                            </span>
                            <span v-else>
                                {{ item.observaciones_habilitado }}
                            </span>
                        </div>
                    </td>

                    <!-- Columna: Fecha Habilitado -->
                    <td class="text-center px-3 py-3">
                        <div v-if="item.fecha_habilitado">
                            {{ formatDateTime(item.fecha_habilitado) }}
                        </div>
                        <div v-else class="text-base">
                            -
                        </div>
                    </td>

                    <!-- Columna: Fecha de Pago -->
                    <td class="text-center px-3 py-3">
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
                                    <a v-if="can('comprobante-pago') && !item.boleta_descargada"
                                        @click.prevent="registrarYDescargar(item)"
                                        class="cursor-pointer block rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100">
                                        <svg class="w-6 h-6 text-gray-800" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </a>

                                    <!-- Ya descargada → mostrar sello PAGADO -->
                                    <div v-else-if="item.boleta_descargada"
                                        @click.prevent="selectedItem = item; openModalPagado = true"
                                        class="cursor-pointer relative flex items-center justify-center w-20 h-14 select-none">
                                        <svg viewBox="0 0 56 56" class="w-full h-full"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="28" cy="28" r="21" fill="none" stroke="#16a34a" stroke-width="2"
                                                stroke-dasharray="2.5 1" stroke-linecap="round" />
                                            <circle cx="28" cy="28" r="15" fill="none" stroke="#16a34a"
                                                stroke-width="1.2" stroke-dasharray="2 2.5" stroke-linecap="round" />
                                            <g transform="rotate(-18, 28, 28)">
                                                <rect x="0" y="20" width="55" height="18" rx="2.5" fill="#16a34a" />
                                                <text x="28" y="31.5" text-anchor="middle" font-size="8.5" fill="white"
                                                    font-weight="900" letter-spacing="2"
                                                    font-family="Arial Black, sans-serif">PAGADO</text>
                                            </g>
                                        </svg>
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
                    <template #label2>El pago ya fue registrado correctamente</template>

                    <!-- Body -->
                    <div class="py-0 space-y-3">

                        <!-- Usuario que registró -->
                        <div
                            class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/40 rounded-xl px-4 py-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-cyan-400 flex items-center justify-center shadow-md flex-shrink-0">
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
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs text-slate-400">N° de Boleta</p>
                            </div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200 tracking-wide">
                                # {{ selectedItem?.pago?.numero_boleta }}
                            </span>
                        </div>

                        <!-- Boleta, tipo pago, gestión, mes -->
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                                <p class="text-xs text-slate-400 mb-1">Gestión</p>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ selectedItem?.gestion?.gestion }}
                                </p>
                            </div>
                            <div
                                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                                <p class="text-xs text-slate-400 mb-1">Mes</p>
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
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-xs text-slate-400">Fecha y hora del pago</p>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ formatDateTime(selectedItem?.pago?.fecha_pago) }}
                                </p>
                            </div>
                        </div>

                    </div>
                    <template #footer>
                        <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                            <div class="flex justify-center gap-3">
                                <Button @click="openModalPagado = false" :style="'px-12 py-2.5 rounded-xl'"
                                    class="text-white bg-blue-600 hover:bg-blue-500">
                                    Aceptar
                                </Button>
                            </div>
                        </div>
                    </template>
                </Modal>
            </Transition>
            <Paginacion v-if="datosHabilitado?.last_page > 1" :links="datosHabilitado.links"
                :from="datosHabilitado.from" :to="datosHabilitado.to" :total="datosHabilitado.total" />
            <Footer />
        </div>
    </div>
</template>
