<script setup>
import { Link } from '@inertiajs/vue3';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
const props = defineProps({
    data: {
        type: Object,
        default: () => ({}) 
    },

    nombre: String,
    texto: String,
    boton: Boolean,
    botonImport: Boolean,
    botonInforme: Boolean,
    botonReportTutor: Boolean,
    botonImportGes: Boolean,
    botonReporHabilitado: Boolean,
    botonReporUsuarios: Boolean,
    botonLista: Boolean,
    datos: Boolean
});

//console.log(props.data);

const emit = defineEmits(['openForm', 'importar', 'openEdit', 'generearInforme', 'plantillaPago', 'reporteGeneral', 'show-tooltip', 'hide-tooltip'])

const openForm = () => {

}

const openLista = () => {
    emit('openLista');
}
</script>
<template>
<div class="flex">
    <!-- Add Button -->

    <!-- Botón 1: agregar/abrir form -->
    <div v-if="data.agregar == true" id="btn-1" @click.prevent="emit('openForm');" @mouseenter="$emit('show-tooltip', `Agregar ${data.nombre}`, 'btn-1')" @mouseleave="$emit('hide-tooltip')" class="mx-1 bg-blue-500 text-white rounded-lg px-3 py-2 flex items-center justify-center cursor-pointer relative hover:bg-blue-600 transition-colors shadow-sm">
        <slot name="icon-agregar">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm5 11h-4v4a1 1 0 11-2 0v-4H7a1 1 0 110-2h4V7a1 1 0 112 0v4h4a1 1 0 110 2z" clip-rule="evenodd" />
            </svg>
        </slot>
        <span class="ms-2 text-sm font-bold">
            Agregar
        </span>
        <span v-if="data.nombre == 'mes'" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-orange-500 z-20">
            <span class="absolute inset-0 w-3 h-3 bg-orange-400 rounded-full opacity-75 animate-ping"></span>
        </span>
    </div>

    <div v-if="data.editar" id="btn-1" @click.prevent="emit('openEdit');" @mouseenter="$emit('show-tooltip', `Editar ${data.nombre}`, 'btn-1')" @mouseleave="$emit('hide-tooltip')" class="mx-1 bg-blue-500 text-white rounded-lg px-3 py-2 flex items-center justify-center cursor-pointer relative hover:bg-blue-600 transition-colors shadow-sm">
        <slot name="icon-editar">
            <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M12.512 8.72a2.46 2.46 0 0 1 3.479 0 2.461 2.461 0 0 1 0 3.479l-.004.005-1.094 1.08a.998.998 0 0 0-.194-.272l-3-3a1 1 0 0 0-.272-.193l1.085-1.1Zm-2.415 2.445L7.28 14.017a1 1 0 0 0-.289.702v2a1 1 0 0 0 1 1h2a1 1 0 0 0 .703-.288l2.851-2.816a.995.995 0 0 1-.26-.189l-3-3a.998.998 0 0 1-.19-.26Z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M7 3a1 1 0 0 1 1 1v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h1V4a1 1 0 0 1 1-1Zm10.67 8H19v8H5v-8h3.855l.53-.537a1 1 0 0 1 .87-.285c.097.015.233.13.277.087.045-.043-.073-.18-.09-.276a1 1 0 0 1 .274-.873l1.09-1.104a3.46 3.46 0 0 1 4.892 0l.001.002A3.461 3.461 0 0 1 17.67 11Z" clip-rule="evenodd" />
            </svg>
        </slot>
        <span class="ms-2 text-sm font-bold">
            Editar
        </span>
    </div>

    <!-- Botón 2: Importar/Datos -->
    <div v-if="data.importar"  id="btn-2" @click.prevent="emit('importar');" @mouseenter="$emit('show-tooltip', 'Importar datos', 'btn-2')" @mouseleave="$emit('hide-tooltip')" class="mx-1 bg-gray-600 rounded-lg px-3 py-2 flex items-center justify-center cursor-pointer relative hover:bg-gray-500 transition-colors shadow-sm">
        <a  class="flex">
        <slot name="icon-importar">
            <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 00-.5.365L4.586 6.5a2 2 0 00-.365.5H9zm2 0V2h7a2 2 0 012 2v16a2 2 0 01-2 2H6a2 2 0 01-2-2v-5h7.586l-.293.293a1 1 0 001.414 1.414l2-2a1 1 0 000-1.414l-2-2a1 1 0 00-1.414 1.414l.293.293H4V9h5a2 2 0 002-2z" clip-rule="evenodd" />
            </svg>
        </slot>
        <span class="text-gray-300 ms-2 text-sm font-bold">
            Importar
        </span>
        </a>

    </div>

    <!-- Botón 3: Documento/Reporte -->
    <div v-if="data.reporte" id="btn-3" @click.prevent="emit('generearInforme');" @mouseenter="$emit('show-tooltip', 'Generar reporte', 'btn-3')" @mouseleave="$emit('hide-tooltip')" class="ms-1 bg-gray-300 rounded-lg px-3 py-2 flex items-center justify-center cursor-pointer relative hover:bg-gray-200 transition-colors shadow-sm">
        <slot name="icon-reporte">
            <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
            </svg>
        </slot>
        <span class="text-gray-600 ms-2 text-sm font-bold">
            Informe
        </span>
    </div>

    <!-- Botón 3: Documento/Reporte -->
    <div v-if="data.plantillaPago" id="btn-3" @click.prevent="emit('generearInforme');" @mouseenter="$emit('show-tooltip', 'Generar reporte', 'btn-3')" @mouseleave="$emit('hide-tooltip')" class="ms-1 bg-gray-300 rounded-lg px-3 py-2 flex items-center justify-center cursor-pointer relative hover:bg-gray-200 transition-colors shadow-sm">
        <slot name="icon-reporte">
            <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
            </svg>
        </slot>
        <span class="text-gray-600 ms-2 text-sm font-bold">
            Plantilla pago de bono
        </span>
    </div>

    <!-- Botón 3: Documento/Reporte -->
    <div v-if="data.reporteGeneral" id="btn-3" @click.prevent="emit('reporteGeneral');" @mouseenter="$emit('show-tooltip', 'Generar reporte', 'btn-3')" @mouseleave="$emit('hide-tooltip')" class="ms-1 bg-gray-300 rounded-lg px-3 py-2 flex items-center justify-center cursor-pointer relative hover:bg-gray-200 transition-colors shadow-sm">
        <slot name="icon-reporte">
            <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
            </svg>
        </slot>
        <span class="text-gray-600 ms-2 text-sm font-bold">
            Informe General
        </span>
    </div>

    <!-- <PrimaryButton v-if="boton && nombre !== 'Pago'" @click.prevent="openForm" class="mx-1">

        <svg v-if="nombre === 'Beneficiario'" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2 -ml-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
        </svg>
        <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M8 14h8m-4-7V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z" />
        </svg>

        <svg v-if="nombre === 'Gestión'" xmlns="http://www.w3.org/2000/svg" class="font-bold h-3 w-3 mr-2 -ml-1" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 5.05h1a2 2 0 0 1 2 2v2H3v-2a2 2 0 0 1 2-2h1v-1a1 1 0 1 1 2 0v1h3v-1a1 1 0 1 1 2 0v1h3v-1a1 1 0 1 1 2 0v1Z" clip-rule="evenodd" />
        </svg>

        <svg v-if="nombre !== 'Beneficiario' && nombre !== 'Gestión'" class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
        </svg>

        <span class="capitalize">Agregar</span>
    </PrimaryButton> -->

    <!-- Lista Button -->
    <!-- <SecondaryButton v-if="botonLista" class="py-2">
        <Link :href="route('notificaciones.lista')" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3v4a1 1 0 0 1-1 1H5m8-2h3m-3 3h3m-4 3v6m4-3H8M19 4v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1ZM8 12v6h8v-6H8Z" />
        </svg>
        Lista
        </Link>
    </SecondaryButton> -->

    <!-- Import Button -->
    <!-- <SecondaryButton v-if="botonImport" class="py-2">
        <Link :href="route('persona.importar')" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5h7.586l-.293.293a1 1 0 0 0 1.414 1.414l2-2a1 1 0 0 0 0-1.414l-2-2a1 1 0 0 0-1.414 1.414l.293.293H4V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd" />
        </svg>
        <span class="capitalize">Importar</span>
        </Link>
    </SecondaryButton> -->
    <!-- Boton Importar Beneficiarios -->
    <!-- <SecondaryButton v-if="botonInforme" class="mx-1">
        <Link :href="route('persona.reporte')" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
        </svg>
        <span class="capitalize">Reporte Beneficiarios</span>
        </Link>
    </SecondaryButton>
    <SecondaryButton v-if="botonImportGes" class="mx-1">
        <Link :href="route('gestion.reporte')" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
        </svg>
        <span class="capitalize">Reporte por Gestion</span>
        </Link>
    </SecondaryButton>
    <SecondaryButton v-if="botonReportTutor" class="mx-1">
        <Link href="#" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
        </svg>
        <span class="capitalize">Reporte Tutores</span>
        </Link>
    </SecondaryButton>
    <SecondaryButton v-if="botonReporHabilitado" class="mx-1">
        <Link href="#" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
        </svg>
        <span class="capitalize">Reporte Habilitados</span>
        </Link>
    </SecondaryButton>
    <SecondaryButton v-if="botonReporUsuarios" class="mx-1">
        <Link href="#" class="flex">
        <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
        </svg>
        <span class="capitalize">Reporte Usuarios</span>
        </Link>
    </SecondaryButton> -->
    <!-- <AuthenticatedLayout>
        <PrimaryButton v-if="nombre === 'Pago' && $page.props.auth.user.rol != 'colaborador'" @click.prevent="openForm" class="mx-1">
            <Link :href="route('pago.transferencia')" class="flex">
            <svg class="w-3.5 h-3.5 mr-2 -ml-1 mt-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16h13M4 16l4-4m-4 4 4 4M20 8H7m13 0-4 4m4-4-4-4" />
            </svg>
            <span class="capitalize">Pago por Transferencias</span>
            </Link>
        </PrimaryButton>

        <SecondaryButton v-if="nombre === 'Pago'" class="mx-1">
            <Link :href="route('pago.reportePago')" class="flex">
            <svg class="h-3.5 w-3.5 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
            </svg>
            <span class="capitalize">Reporte Pagos</span>
            </Link>
        </SecondaryButton>
    </AuthenticatedLayout> -->

</div>
</template>
