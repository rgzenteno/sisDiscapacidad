<script setup>
import Paginacion from '@/components/Paginacion.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import pagoIndex from '../Reporte/pagoIndex.vue';
import Footer from '@/components/Footer.vue';
import Rutas from '@/components/Rutas.vue';
import ModalPago from '@/components/ModalPago.vue';
import ModalConfirmacion from '@/components/ModalConfirmacion.vue';
import ModalComprobante from '@/components/ModalComprobante.vue';

import {
    computed,
    ref
} from 'vue';
import {
    Head,
    router,
    useForm,
    usePage
} from "@inertiajs/vue3";
import {
    Link
} from '@inertiajs/vue3';

// Obtener las props de la página
const page = usePage();
const persona = ref(page.props.persona);
const tutorTabla = computed(() => page.props.tutorTabla);
const pago = computed(() => page.props.pago);
const habilitado = computed(() => page.props.habilitado);
const habilitadoCompleto = ref(page.props.habilitadoCompleto);

const cuentaB = computed(() => page.props.cuentaB);
const cuentaQr = computed(() => page.props.cuentaQr);

const reporteRef = ref(null);
const modalConfirm = ref(false);
const idHabi = ref('');
const tipoHabi = ref('');
const datosPago = ref(null);
const tipoPago = ref(null);
const modalPagos = ref(false);
const id_habilitado = ref(null);
const showComprobante = ref(false);
const idPago = ref('');
const tipoComp = ref('');
const selectedItem = ref(null);

// Lista de mensajes
const mensajes = ref([]);

// Mostrar mensaje
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(), // ID único
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

// Eliminar mensaje cuando hijo emite @close
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

const form = useForm({
    id_habilitado: '',
    id_tutor: '',
    tipo: ''
});


const getPago = (idPago) => {
    return pago.value.filter(p => p.id_habilitado === idPago);
}

const createReporte = () => {
    if (reporteRef.value) {
        reporteRef.value.createReporte();
    }
};

// Método para obtener el nombre del mes
function getMonthNameFromNumber(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Convertir a número entero
    const index = parseInt(monthNumber, 10) - 1;

    // Validar que esté en rango 0–11
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

const formatDateTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString();
}

const nombreCompleto = computed(() => {
    const p = persona.value;
    return p ? `${p.nombre_persona || ''} ${p.apellido_persona || ''}`.trim() : '';
});

const getPagadoFiltrado = (idPago) => {
    return pago.value.filter(p => p.id_habilitado === idPago) || [];
}

const comprobante = (id, tipo, pago) => {
    if (tipo === 'subir') {
        idPago.value = id;
        tipoComp.value = tipo;
        showComprobante.value = true;
    } else {
        idPago.value = id;
        tipoComp.value = tipo;
        selectedItem.value = {
            ...pago
        };
        showComprobante.value = true;
    }
}

const confirmar = (idHabilitado, tipo) => {
    idHabi.value = idHabilitado;
    tipoHabi.value = tipo;
    modalConfirm.value = true;
}

const closeModal = () => {
    modalConfirm.value = false;
    showComprobante.value = false;
}

const submit = (idHabilitado, tipo) => {
    modalPagos.value = false;
    form.tipo = tipo;
    form.id_habilitado = idHabilitado;
    form.post(route('pago.store'), {
        onSuccess: () => {
            mostrarMensaje('correcto', 'Pago exitoso', 'El pago se registro correctamente.');
            modalConfirm.value = false;
            router.reload();
        },
        onError: () => {
            mostrarMensaje('advertencia', 'Pago no Registrado', 'El paago no se registro correctamente.');
        }
    });
}

const getFileType = (url) => {
    if (!url) return null;
    const extension = url.split('.').pop().toLowerCase();
    const types = {
        'jpg': 'JPEG',
        'jpeg': 'JPEG',
        'png': 'PNG',
        'pdf': 'PDF'
    };
    return types[extension] || extension.toUpperCase() + ' File';
};
</script>

<template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-x-hidden">
        <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido" @close="cerrarMensaje" />
        </div>
        <Header class="mb-0" />
        <ModalPago v-if="modalPagos" :id="id_habilitado" :datos="datosPago" :tipo="tipoPago" @close="modalPagos = false" @submit="submit" />
        <ModalConfirmacion v-if="modalConfirm" :id="idHabi" :tipo="tipoHabi" @confirmar="submit" @close="closeModal" />
        <ModalComprobante v-if="showComprobante" :existing-data="selectedItem || {}" :id="idPago" :tipo="tipoComp" :pagos="pago" @close="closeModal" />

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Pago Beneficiario</h1>
                <Rutas label1="Inicio" label2="Pagos" label3="Pago Beneficiario" />
            </div>
        </div>
        <div class=" p-4 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg mr-1">
            <!-- <Busqueda :initial-value="filters.buscador" name="beneficiario" only="persona" :data="persona" ruta-busqueda="persona.index" /> -->
            <div class="mx-auto py-1 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 mb-2">
                    <!-- Card Beneficiario - Modernized -->
                    <div class="group relative bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 animate-fade-in">
                        <div class="relative p-6 py-2.5">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg mr-4">
                                    <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd" />
                                    </svg>

                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-gray-900">Datos del Beneficiario</h2>
                                    <p class="text-sm text-gray-500">Información personal</p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">Nombre:</span>
                                    <span class="text-sm text-gray-900 font-semibold capitalize">{{ nombreCompleto }}</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">C.I.:</span>
                                    <span class="text-sm text-gray-900 font-semibold">{{ persona.ci_persona }}</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">Distrito:</span>
                                    <span class="text-sm text-gray-900 font-semibold capitalize">{{ persona.distrito }}</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">Discapacidad:</span>
                                    <span class="text-sm text-gray-900 font-semibold capitalize">{{ tutorTabla.discapacidad }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Tutor - Modernized -->
                    <div class="group relative bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 animate-fade-in">
                        <div class="relative p-6 py-2.5">
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 bg-indigo-700 rounded-xl flex items-center justify-center shadow-lg mr-4">
                                    <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-gray-900">Datos del Tutor</h2>
                                    <p class="text-sm text-gray-500">Información del responsable</p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">Nombre:</span>
                                    <span class="text-sm text-gray-900 font-semibold capitalize">{{ tutorTabla.nombre_tutor }} {{ tutorTabla.apellido_tutor }}</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">C.I.:</span>
                                    <span class="text-sm text-gray-900 font-semibold">{{ tutorTabla.ci_tutor }}</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">Teléfono:</span>
                                    <span class="text-sm text-gray-900 font-semibold">{{ tutorTabla.telefono }}</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 bg-gray-50/70 rounded-xl hover:bg-gray-100/70 transition-colors">
                                    <span class="text-sm font-medium text-gray-700">Dirección:</span>
                                    <span class="text-sm text-gray-900 font-semibold capitalize truncate">{{ tutorTabla.direccion }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white border-b-2 border-x-2 rounded-b-lg mr-1">

            <div v-if="habilitado.data.length > 0" class="mx-0 bg-white py-2">
                <form @submit.prevent="submit">
                    <!-- Tabla para desktop -->
                    <div class="relative mx-4 overflow-x-auto border rounded-lg shadow-md sm:rounded-lg">
                        <table class="w-full h-full text-xs text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr class="border-b">
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        <span>Mes</span>
                                    </th>
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        <span>Habilitado</span>
                                    </th>
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        <span>Pagado</span>
                                    </th>
                                    <th scope="col" class="text-center px-2 py-2.5">
                                        Gestion
                                    </th>
                                    <th scope="col" class="whitespace-nowrap min-w-[150px] text-center px-4 py-2.5">
                                        Monto Bs - Persona
                                    </th>
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        Observación
                                    </th>
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        Fecha Habilitado
                                    </th>
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        Fecha Pago
                                    </th>
                                    <th scope="col" class="text-center px-4 py-2.5">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody v-for="item in habilitado.data" :key="item.id_habilitado">
                                <tr v-if="item.habilitado === 1" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <th scope="row" class="text-center capitalize px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ getMonthNameFromNumber(item.mes) }}
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        <div class="flex items-center justify-center">
                                            <!-- Indicador de habilitado -->
                                            <div v-if="item.habilitado === 1" class="relative">
                                                <div class="h-3 w-3 rounded-full bg-green-500 ring-2 ring-green-300 dark:ring-green-800"></div>
                                            </div>
                                            <div v-else class="relative">
                                                <span class="absolute top-1 right-1 w-3 h-2 rounded-full bg-orange-400 z-20">
                                                    <span class="absolute inset-0 w-2 h-2 bg-orange-500 rounded-full opacity-75 animate-ping"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </th>

                                    <th v-if="item.fecha_pago" scope="col" class="px-4 py-3">
                                        <div class="flex items-center justify-center">
                                            <!-- Indicador de pago -->
                                            <div v-if="item.fecha_pago" class="relative">
                                                <div class="h-3 w-3 rounded-full bg-green-500 ring-2 ring-green-300 dark:ring-green-800"></div>
                                            </div>
                                            <div v-else class="relative">
                                                <div class="h-3 w-3 rounded-full bg-red-500 ring-2 ring-red-200 dark:ring-red-800">
                                                    <span class="absolute -top-0.5 -left-0.5 w-4 h-4 rounded-full bg-red-400 opacity-75 animate-ping"></span>
                                                </div>
                                            </div>                                            
                                        </div>
                                    </th>

                                    <td v-else class="py-4">
                                        <div class="flex items-center justify-center">
                                            <div class="relative">
                                                <div class="h-3 w-3 rounded-full bg-red-500 ring-2 ring-red-200 dark:ring-red-800">
                                                    <span class="absolute -top-0.5 -left-0.5 w-4 h-4 rounded-full bg-red-400 opacity-75 animate-ping"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <th scope="row" class="px-2 py-2 text-center font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ item.gestion }}
                                    </th>
                                    <th scope="row" class="capitalize text-center px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        bs. {{ item.monto }}
                                    </th>
                                    <td class="px-3 py-3.5">
                                        <span v-if="item.observacion_habilitado" >
                                            {{ item.observacion_habilitado }}   
                                        </span>                                        
                                        <span v-else class="italic block text-center">
                                            ninguna
                                        </span>
                                    </td>
                                    <td class="text-center px-4 py-2 whitespace-nowrap min-w-[150px]">
                                        {{ item.fecha_habilitado }}
                                    </td>
                                    <td class="text-center px-4 py-2 whitespace-nowrap min-w-[150px]">
                                        <div v-if="item.fecha_pago">
                                            {{item.fecha_pago}}
                                        </div>
                                        <div v-else>
                                            <span class="text-red-600 text-xl">--</span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-4 py-1">
                                        <ul class="flex items-center justify-center gap-1">
                                            <div v-if="!item.fecha_pago" class="flex items-center gap-1">
                                                <li class="relative">
                                                    <a @click.prevent="confirmar(item.id_habilitado, 'efectivo')" class="cursor-pointer block rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                                                            <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                                                            <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                                                        </svg>
                                                    </a>
                                                </li>                                                                                
                                            </div>                                            
                                            <div v-else class="flex">
                                                <div>
                                                    <Link href="#" @click.prevent="createReporte" target="_blank" class="cursor-pointer  block  rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                    <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z" clip-rule="evenodd" />
                                                    </svg>
                                                    </Link>
                                                    <!-- Usa el ref definido en el script -->
                                                    <pagoIndex :ref="el => reporteRef = el" :data="tutorTabla" :gestion="item.gestion" :monto="item.monto" :pago="item.fecha_pago" style="display: none;" target="_blank" />
                                                </div>
                                            </div>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Versión móvil - Tarjetas -->
                    <div class="md:hidden px-4 space-y-4">
                        <div v-for="item in habilitado.data" :key="item.id_habilitado" class="bg-white rounded-lg shadow-md p-4 border border-gray-200">
                            <div v-if="item.habilitado === 1">
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Mes</p>
                                        <p class="text-sm font-medium text-gray-800 capitalize">{{ getMonthNameFromNumber(item.gestion) }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <p class="text-xs text-center font-medium text-gray-500">Habilitado</p>
                                            <div class="flex items-center justify-center py-2">
                                                <div v-if="item.habilitado === 1" class="items-center h-3 w-3 rounded-full bg-green-500 ring-2 ring-green-300"></div>
                                                <div v-else class="h-3 w-3 rounded-full bg-red-500 ring-2 ring-red-300"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs text-center font-medium text-gray-500">Pagado</p>
                                            <div class="flex items-center justify-center py-2">
                                                <div v-if="getPago(item.id_habilitado) && getPago(item.id_habilitado).length > 0" v-for="pago in getPago(item.id_habilitado)" :key="pago.id_pago">
                                                    <div v-if="pago.pago != null" class="h-3 w-3 rounded-full bg-green-500 ring-2 ring-green-300"></div>
                                                    <div v-else class="h-3 w-3 rounded-full bg-red-500 ring-2 ring-red-300"></div>
                                                </div>
                                                <div v-else>
                                                    <div class="h-3 w-3 rounded-full bg-red-500 ring-2 ring-red-300"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Gestión</p>
                                        <p class="text-sm text-gray-800">{{ item.gestion }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Monto Bs</p>
                                        <p class="text-sm text-gray-800 capitalize">bs. {{ item.monto }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Observación</p>
                                        <p class="text-sm text-gray-800 truncate">
                                            {{ item.observacion_habilitado || 'ninguna' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Fecha Hab.</p>
                                        <p class="text-sm text-gray-800 whitespace-nowrap">{{ formatDateTime(item.created_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500">Fecha Pago</p>
                                        <p class="text-sm text-gray-800">
                                            <span v-if="pago.find(ite => ite.id_habilitado === item.id_habilitado)">
                                                <span v-for="(pago, index) in getPagadoFiltrado(item.id_habilitado)" :key="pago.id_habilitado">
                                                    {{ pago.fecha_pago }}
                                                </span>
                                            </span>
                                            <span v-else>-</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="mt-3">
                                    <div v-if="!pago.find(ite => ite.id_habilitado === item.id_habilitado)" class="flex flex-wrap gap-2 justify-between">
                                        <button @click.prevent="confirmar(item.id_habilitado, 'efectivo')" class="flex items-center justify-center p-2 bg-blue-100 rounded-lg text-blue-800 hover:bg-blue-200 transition-colors">
                                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M7 6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-2v-4a3 3 0 0 0-3-3H7V6Z" clip-rule="evenodd" />
                                                <path fill-rule="evenodd" d="M2 11a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7Zm7.5 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                                                <path d="M10.5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                                            </svg>
                                            <span class="ml-1 text-sm">Efectivo</span>
                                        </button>
                                    </div>

                                    <div v-else class="flex flex-wrap gap-2 justify-between">
                                        <div v-for="pago in getPago(item.id_habilitado)" :key="pago.id_pago">
                                            <div v-if="pago.tipo_pago != 'efectivo'">
                                                <div v-if="pago.comprobante === null">
                                                    <button @click="comprobante(pago.id_pago, 'subir')" class="flex items-center justify-center p-2 bg-red-100 rounded-lg text-red-800 hover:bg-red-200 transition-colors">
                                                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.394 9.553a1 1 0 0 0-1.817.062l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .894-1.447l-2-4A1 1 0 0 0 13.2 13.4l-.53.706-1.276-2.553ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="ml-1 text-sm">Subir Comprobante</span>
                                                    </button>
                                                </div>
                                                <div v-else>
                                                    <div @click="comprobante(pago.id_pago, 'ver', pago)" class="flex items-center justify-center p-2 bg-blue-100 rounded-lg text-blue-800 hover:bg-blue-200 transition-colors">
                                                        <span v-if="getFileType(pago.comprobante) === 'PDF'">
                                                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                        <span v-else>
                                                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.394 9.553a1 1 0 0 0-1.817.062l-2.5 6A1 1 0 0 0 8 19h8a1 1 0 0 0 .894-1.447l-2-4A1 1 0 0 0 13.2 13.4l-.53.706-1.276-2.553ZM13 9.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                        <span class="ml-1 text-sm">Ver Comprobante</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <Link href="#" @click.prevent="createReporte" target="_blank" class="flex items-center justify-center p-2 bg-gray-100 rounded-lg text-gray-800 hover:bg-gray-200 transition-colors">
                                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="ml-1 text-sm">Reporte</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div></div>
            </div>
            <div v-else class="px-4 py-6 text-center text-gray-600">
                No tiene meses habilitados
            </div>
        </main>
        <Paginacion :links="habilitado.links" :from="habilitado.from" :to="habilitado.to" :total="habilitado.total" />
        <Footer />
    </div>
</div>
</template>

<style>
/* Enhanced tooltip styles */
.tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 125%;
    left: 50%;
    margin-left: -60px;
    width: 120px;
    background-color: #1f2937;
    color: white;
    text-align: center;
    border-radius: 8px;
    padding: 8px 0;
    font-size: 12px;
    transition: opacity 0.3s;
    z-index: 1000;
}

.tooltip::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #1f2937 transparent transparent transparent;
}

.group:hover .tooltip {
    visibility: visible;
    opacity: 1;
}
</style>
