<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    computed,
    onMounted,
    ref
} from 'vue';
import { useSidebar } from '@/composables/useSidebar';

import {
    Link,
    usePage
} from '@inertiajs/vue3';
import Seccion from '@/components/Seccion.vue';
const {
    isOpen
} = useSidebar()
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Footer from '@/components/Footer.vue';
import ModalRequisitos from '@/components/ModalRequisitos.vue';
import ModalEstado from '@/components/ModalEstado.vue';

import Mensajes from '@/components/Mensajes.vue';

const page = usePage();
const personas = computed(() => page.props.personas);
const tutorados = computed(() => page.props.tutorados);
const notificacionTutor = computed(() => page.props.notificacionTutor);
const mensajesTutor = computed(() => page.props.mensajesTutor);
const esVencido = computed(() => page.props.esVencido);

const modal = ref(false);
const modalEstado = ref(false);
const openEdit = ref(false);
const nombre = ref(null);
const selectedItem = ref(null);
const selectedId = ref(null);
const estadoBaja = ref('');
const tipo = ref('');
const contenido = ref('');
const showMensaje = ref(false);
const tipoMensaje = ref('');

const activeClass = "bg-[#223B87] border-gray-50 rounded-r-lg mr-2 text-white border-l-4 border-black dark:text-white dark:bg-gray-700 dark:text-white dark:border-white"
const inactiveClass = "border-gray-100 text-white hover:bg-blue-600 dark:hover:bg-gray-500"

// Get current route name
const currentRoute = ref('');
const activeButton = ref(localStorage.getItem('activeButton') || 'tutorados');

onMounted(() => {
    currentRoute.value = route().current();

    // If we're on the login page or tutorados view, set to tutorados
    if (currentRoute.value === 'login' || currentRoute.value === 'tutorVista.show') {
        activeButton.value = 'tutorados';
        localStorage.setItem('activeButton', 'tutorados');
    } else {
        // Extract the main route name
        const mainRoute = currentRoute.value.split('.')[0];
        activeButton.value = mainRoute;
        localStorage.setItem('activeButton', mainRoute);
    }
});

const setActiveButton = (button) => {
    activeButton.value = button;
    localStorage.setItem('activeButton', button);
}

const getNotificacion = () => {
    return notificacionTutor.value.filter(n => n.id_tutor === tutorados.value[0].id_tutor)
}

// Usa getNotificacion() para ver el resultado filtrado
/* console.log('notificaciones: ', notificacionTutor.value);
console.log('notificacion Filtrada: ', getNotificacion()); */

const personaFields = [{
    typeInput: 'label',
    textos: [
        'Una vez solicitada la baja definitiva del beneficiario, su solicitud será atendida lo mas antes posible.',
        'Para enviar la solicitud, por favor ingrese el número de cédula de identidad del beneficiario.',
    ],
},
{
    typeInput: 'text',
    name: 'carnet',
    label: 'C.I. del beneficiario. ',
    type: 'number',
    required: true,
    placeholder: 'la cédula de Identidad',
    readonly: false
},
];

//Inicio Mensajes
const MensajeErrorForm = {
    encontrado: {
        header: 'Solicitud realizada',
        text: 'La acción solicitada se ejecutó correctamente.'
    },
    noEncontrado: {
        header: 'Carnet incompatible',
        text: 'El numero de carnet proporcionado no existe.'
    }
};

const contMensajes = (tipoSms, tipoContenido) => {
    tipo.value = tipoSms;
    contenido.value = [MensajeErrorForm[tipoContenido]];
    showMensaje.value = true;
}

const handleClose = () => {
    showMensaje.value = false;
}
//Fin Mensajes

const getMensaje = (idPersona) => {
    return mensajesTutor.value.filter(m => m.beneficiario === idPersona)
}

const openModalEstado = (item, estado, idPersona) => {
    if (getMensaje(idPersona).length > 0) {
        console.log(idPersona);
        estadoBaja.value = 'existe';
        modalEstado.value = true;
        return;
    }

    selectedId.value = idPersona;
    estadoBaja.value = estado;
    selectedItem.value = {
        ...item
    };
    modalEstado.value = true;
}

const openModalBaja = (estado) => {
    estadoBaja.value = estado;
    modalEstado.value = true;
}

const modalEdit = (item, idPersona) => {
    selectedId.value = idPersona;
    selectedItem.value = {
        ...item
    };
    openEdit.value = true;
    modalEstado.value = false;
}

const form = useForm({
    id_persona: ''
});

const Cobros = (idPersona) => {
    form.id_persona = idPersona;
    form.get(route('tutorVista.cobros'));
}

const sinCarnet = () => {
    contMensajes('advertencia', 'noEncontrado');
}

const encontrado = () => {
    contMensajes('correcto', 'encontrado');
    openEdit.value = false;
}

const closeForm = () => {
    router.reload({ only: ['tutorados'] });
    openEdit.value = false;
}

const openModal = (nombreCompleto, tipo) => {
    nombre.value = nombreCompleto;
    tipoMensaje.value = tipo;
    modal.value = true;
}
</script>
<template>

    <Head title="UMADIS" />

    <div class="flex h-screen bg-gray-50 font-roboto">

        <div class="flex sm:p-1" style="padding-left:4px">
            <!-- Backdrop -->
            <div :class="isOpen ? 'block' : 'hidden'"
                class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden" @click="isOpen = false" />

            <div :class="isOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
                class="xs:m-1 rounded-lg fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-200 transform bg-[#13326A] dark:bg-gray-900 lg:translate-x-0 lg:static lg:inset-0">
                <div class="flex items-center justify-center mt-8">
                    <div class="flex items-center">
                        <span class="mx-2 text-4xl font-semibold text-white dark:text-white"
                            style="text-shadow: -5px 5px 4px rgb(158 158 158 / 79%);">UMADIS</span>
                    </div>
                </div>
                <nav class="mt-8">
                    <Link class="flex items-center px-4 py-2 mt-4 duration-200"
                        :class="[activeButton === 'tutorados' ? activeClass : inactiveClass]"
                        :href="route('tutorVista.show')" @click="setActiveButton('tutorados')">
                        <svg class="w-6 h-6 text-gray-400 dark:text-white" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 6a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm-1.5 8a4 4 0 0 0-4 4 2 2 0 0 0 2 2h7a2 2 0 0 0 2-2 4 4 0 0 0-4-4h-3Zm6.82-3.096a5.51 5.51 0 0 0-2.797-6.293 3.5 3.5 0 1 1 2.796 6.292ZM19.5 18h.5a2 2 0 0 0 2-2 4 4 0 0 0-4-4h-1.1a5.503 5.503 0 0 1-.471.762A5.998 5.998 0 0 1 19.5 18ZM4 7.5a3.5 3.5 0 0 1 5.477-2.889 5.5 5.5 0 0 0-2.796 6.293A3.501 3.501 0 0 1 4 7.5ZM7.1 12H6a4 4 0 0 0-4 4 2 2 0 0 0 2 2h.5a5.998 5.998 0 0 1 3.071-5.238A5.505 5.505 0 0 1 7.1 12Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">Tutorados</span>
                    </Link>
                </nav>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <div v-if="showMensaje">
                <Mensajes :tipo="tipo" :contenido="contenido" @close="handleClose" />
            </div>
            <Header :datos="getNotificacion()" :tutor="true" />
            <ModalRequisitos v-if="modal" :nombre="nombre" :tipo="tipoMensaje" @close="modal = false" />
            <ModalEstado v-if="modalEstado" :estadoBaja="estadoBaja" :persona="selectedItem || {}" :idFor="selectedId"
                @editar="modalEdit" @close="modalEstado = false" />
            <Form v-show="openEdit" :fields="personaFields" :existing-data="selectedItem || {}" :idFor="selectedId"
                soli="Solicitar" :edit-mode="true" label1="Tutorados" label2="Solicitar baja"
                rutalabel="Solicitar la baja definitiva del beneficiario." submit-label="Solicitud"
                submit-route="tutorVista.update" @add="closeForm" @noEncontrado="sinCarnet" @encontrado="encontrado"
                @cancel="closeForm" />
            <main v-show="!openEdit" class="flex-1 overflow-x-hidden overflow-y-auto mt-1">
                <Seccion class="mb-1" nombre="Tutorados" texto="Gestione todos los beneficiarios existentes."
                    :boton="false" />
                <div v-if="personas" class="mx-0 mr-1">
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">

                        <table
                            class="w-full text-sm border border-gray-300 text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3">
                                        Nombre Completo
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        Distrito
                                    </th>
                                    <th scope="col" class="text-center px-4 py-3">
                                        Cedula Identidad
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        Observación
                                    </th>
                                    <th scope="col" class="text-center px-4 py-3">
                                        Fecha ingreso
                                    </th>
                                    <th scope="col" class="text-center px-4 py-3">
                                        Documentación
                                    </th>
                                    <th scope="col" class="text-center px-4 py-3">
                                        estado
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in tutorados" :key="item.id_tutor"
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <th scope="row"
                                        class="capitalize px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ item.nombre_persona }} {{ item.apellido_persona }}
                                    </th>
                                    <td class="text-center px-4 py-2">
                                        {{ item.distrito }}
                                    </td>
                                    <td scope="row"
                                        class="text-center px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ item.ci_persona }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ item.observacion_persona }}
                                    </td>
                                    <td class="text-center px-4 py-2">
                                        {{ item.created_at }}
                                    </td>
                                    <th scope="col" class="whitespace-nowrap min-w-[150px] text-center px-4 py-2">
                                        <div v-if="esVencido === true">
                                            <div class="border border-yellow-200 bg-yellow-100 rounded text-center">
                                                <span class="text-yellow-900">Verificación</span>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <div v-if="item.id_carnet != null"
                                                class="border border-green-200 bg-green-100 rounded text-center">
                                                <span class="text-green-900">Completa</span>
                                            </div>
                                            <div v-else class="border border-red-200 bg-red-100 rounded text-center">
                                                <span class="text-red-900">Incompleta</span>
                                            </div>
                                        </div>
                                    </th>
                                    <th scope="col" class="whitespace-nowrap min-w-[150px] px-4 py-2">
                                        <div :class="{
                                            'border border-green-200 bg-green-100 px-4 text-green-900': item.estado === 'activo',
                                            'border border-yellow-200 bg-yellow-100 text-yellow-900 px-4': item.estado === 'bajatemporal',
                                            'border border-red-200 bg-red-100 text-red-900 px-4': item.estado === 'bajadefinitiva'
                                        }" class="rounded text-center">
                                            <span class="">
                                                {{
                                                    item.estado === 'activo' ? 'Activo' :
                                                        item.estado === 'bajatemporal' ? 'Baja Temporal' :
                                                            'Baja Definitiva'
                                                }}
                                            </span>
                                        </div>
                                    </th>
                                    <td class="py-1">
                                        <ul class="flex justify-center">
                                            <template v-if="esVencido === true">
                                                <li>
                                                    <a @click.prevent="openModal(item.nombre_persona + ' ' + item.apellido_persona, 'carnetVencido')"
                                                        class="cursor-pointer rounded-lg block px-1 py-2 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                        <span class="relative">
                                                            <svg class="font-bold text-2xl w-3 h-3 text-yellow-400 dark:text-yellow-400 absolute top-1 right-0 transform translate-x-1/2 -translate-y-1/2 z-20"
                                                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                                <path stroke="currentColor" stroke-linecap="round"
                                                                    stroke-linejoin="round" stroke-width="2"
                                                                    d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                            </svg>
                                                            <div
                                                                class="ripple-background bg-yellow-500 absolute top-0.5 right-0 z-0">
                                                            </div>
                                                            <svg class="w-6 h-6 text-gray-800 dark:text-white z-10 relative"
                                                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                                width="24" height="24" fill="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path fill-rule="evenodd"
                                                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </span>
                                                    </a>
                                                </li>
                                            </template>

                                            <li v-if="item.id_carnet">
                                                <div @click="Cobros(item.id_persona)" v-if="item.id_habilitado != null"
                                                    class="cursor-pointer rounded-lg block px-1 py-2 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                    <svg class="w-6 h-6 text-gray-800 dark:text-white"
                                                        aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                                        height="24" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd"
                                                            d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <a v-else @click.prevent="openModalBaja('sinMes')"
                                                    class="cursor-pointer rounded-lg block px-1 py-1.5 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                    <span class="relative">
                                                        <svg class="font-bold text-2xl w-3 h-3 text-red-500 dark:text-red-500 absolute top-1 right-0.5 transform translate-x-1/2 -translate-y-1/2"
                                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                            <path stroke="currentColor" stroke-linecap="round"
                                                                stroke-width="4"
                                                                d="m6 6 12 12m3-6a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                        <svg class="w-6 h-6 text-gray-800 dark:text-white"
                                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </a>
                                            </li>
                                            <li v-else>
                                                <a @click.prevent="openModal(item.nombre_persona + ' ' + item.apellido_persona, 'sinCarnet')"
                                                    class="cursor-pointer rounded-lg block px-1 py-2 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                    <span class="relative">
                                                        <svg class="font-bold text-2xl w-3 h-3 text-red-500 dark:text-red-500 absolute top-1 right-0 transform translate-x-1/2 -translate-y-1/2"
                                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                            <path stroke="currentColor" stroke-linecap="round"
                                                                stroke-width="4"
                                                                d="m6 6 12 12m3-6a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                        <svg class="w-6 h-6 text-gray-800 dark:text-white"
                                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </a>
                                            </li>
                                            <li v-if="item.estado != 'bajadefinitiva'">
                                                <a @click.prevent="openModalEstado(item, item.estado, item.id_persona, item.beneficiario)"
                                                    class="cursor-pointer rounded-lg block px-1 py-1.5 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                    <svg class="w-6 h-6 text-gray-800 dark:text-white"
                                                        aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                                        height="24" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd"
                                                            d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            </li>
                                            <li v-else>
                                                <a @click.prevent="openModalBaja(item.estado)"
                                                    class="cursor-pointer rounded-lg block px-1 py-1.5 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                    <span class="relative">
                                                        <svg class="w-6 h-6 text-red-800 dark:red-white"
                                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" fill="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path fill-rule="evenodd"
                                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center text-gray-700 h-3/5 w-full mt-10 pt-10">
                    <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-lg py-2">No se encontraron datos. ¡No tiene tutorados registrados!</span>
                </div>
                <Paginacion v-if="personas >= 1" :links="personas.links" :from="personas.from" :to="personas.to"
                    :total="personas.total" />
            </main>
            <Footer />
        </div>
    </div>
</template>
