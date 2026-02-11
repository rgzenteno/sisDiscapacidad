<script setup>
import {
    computed,
    ref
} from 'vue';
import {
    router,
    usePage
} from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import Paginacion from '@/components/Paginacion.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Footer from '@/components/Footer.vue';
import DataTable from '@/components/DataTable.vue';


const page = usePage();
const personas = ref(page.props.personas);
const tutorData = computed(() => page.props.tutorData);
const totalPersonas = computed(() => page.props.totalPersonas);

const getUrl = (ruta, id) => {
    const url = route(ruta, id); // ✅ UUID directo
    router.visit(url);
};

// Definición de columnas para la tabla de tutorados
const tableColumns = [
    { label: 'Nombre Completo', field: 'nombre', headerClass: 'px-6', cellClass: 'capitalize whitespace-nowrap' },
    { label: 'Distrito', field: 'distrito', headerClass: 'px-6', cellClass: '' },
    { label: 'Cedula de Identidad', field: 'ci', headerClass: 'px-6', cellClass: 'whitespace-nowrap' },
    { label: 'Meses Habilitados', field: 'tiene_habilitado', headerClass: 'text-center px-6', cellClass: 'whitespace-nowrap' },
    { label: 'Pagos Disponibles', field: 'tiene_habilitado', headerClass: 'text-center px-6', cellClass: 'whitespace-nowrap' },
    { label: 'Estado', field: 'estado', headerClass: 'text-center px-6', cellClass: '' },
    { label: 'Observación', field: 'observacion', headerClass: 'px-6', cellClass: '' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center px-6', cellClass: '' }
];
</script>

<template>

    <Head title="UMADIS" />
    <div class="flex h-screen bg-gray-200 font-roboto">
        <Sidebar />
        <div class="flex-1 flex flex-col overflow-hidden">
            <Header class="mb-0" />

            <div class="py-2">
                <div class="px-5 py-1 flex justify-between">
                    <h1 class="font-semibold text-2xl">Tutorados</h1>
                    <Rutas label1="Inicio" label2="Tutores" label3="Tutorados" />
                </div>
                <!-- <Seccion class="mt-1 " nombre="Tutor" texto="Gestione todos los tutores existentes" :botonReportTutor="true" :boton="false" /> -->
            </div>

            <!-- Versión alternativa más compacta -->
            <div class="bg-white border-x-2 border-t-2 rounded-t-lg mr-1">
                <div class="p-4 pb-0">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-blue-800 flex items-center">
                            <svg class="w-5 h-5 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                                    clip-rule="evenodd" />
                            </svg>
                            Tutor Responsable
                        </h3>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between capitalize">
                            <span class="text-blue-600 font-medium">{{ tutorData.nombre_tutor }} {{
                                tutorData.apellido_tutor }}</span>
                            <span class="text-gray-600">Cant. Tutorados: <span
                                    class="font-bold text-xl text-blue-500 px-3">{{ totalPersonas }}</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uso del componente de tabla para tutorados -->
            <DataTable :data="personas.data" :columns="tableColumns" row-key="id_tutor"
                empty-message="No hay datos, por favor resgistre beneficiarios">
                <!-- Slot personalizado para cada fila -->
                <template #row="{ item }">
                    <!-- Columna: Nombre Completo -->
                    <th scope="row"
                        class="capitalize px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ item.nombre }} {{ item.apellido }}
                    </th>

                    <!-- Columna: Distrito -->
                    <td class="px-6 py-4">
                        {{ item.distrito }}
                    </td>

                    <!-- Columna: Cedula de Identidad -->
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ item.ci }}
                        <span v-if="item.complemento !== null">-{{ item.complemento }}</span>
                    </td>

                    <!-- Columna: Meses Habilitados -->
                    <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <div v-if="item.tiene_habilitado === 1" class="rounded flex items-center justify-center">
                            <div class="h-2.5 w-2.5 rounded-full ring-2 ring-green-300 bg-green-500"></div>
                        </div>
                        <div v-else class="rounded flex items-center justify-center">
                            <div class="h-2.5 w-2.5 rounded-full ring-2 ring-gray-300 bg-gray-500"></div>
                        </div>
                    </td>

                    <!-- Columna: Pagos Disponibles -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div v-if="item.tiene_habilitado === 1" class="rounded flex items-center justify-center">
                            <div class="h-2.5 w-2.5 rounded-full ring-2 ring-green-300 bg-green-500"></div>
                        </div>
                        <div v-else class="rounded flex items-center justify-center">
                            <div class="h-2.5 w-2.5 rounded-full ring-2 ring-gray-300 bg-gray-500"></div>
                        </div>
                    </td>

                    <!-- Columna: Estado -->
                    <td class="px-3 py-1.5">
                        <li class="flex items-center space-x-2">
                            <div class="flex items-center space-x-1">
                                <span class="relative inline-block">
                                    <div
                                        class="rounded-lg block px-0 py-2 text-gray-700 dark:text-gray-200 dark:hover:text-white relative">
                                        <svg v-if="item.estado === 'activo'"
                                            class="w-4 h-4 text-green-500 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <svg v-if="item.estado === 'baja_temporal'"
                                            class="w-4 h-4 text-orange-500 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <svg v-if="item.estado === 'baja_definitiva'"
                                            class="w-4 h-4 text-red-600 dark:text-white" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd"
                                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </span>
                                <span class="text-gray-400">|</span>
                                <div class="font-medium"
                                    :class="item.estado === 'activo' ? 'text-green-600' : item.estado === 'baja_definitiva' ? 'text-red-600' : 'text-orange-600'">
                                    {{ item.estado === 'activo' ? 'Activo' : item.estado === 'baja_temporal' ? 'Baja Temporal' : 'Baja Definitiva' }}
                                </div>
                            </div>
                        </li>
                    </td>

                    <!-- Columna: Observación -->
                    <td class="px-4 py-2">
                        <span v-if="!item.observacion" class="block">
                            <span class="italic">ninguna</span>
                        </span>
                        <span v-else>
                            {{ item.observacion }}
                        </span>
                    </td>

                    <!-- Columna: Acciones -->
                    <td class="py-1">
                        <ul class="flex justify-center">
                            <li>
                                <a @click.prevent="item.tiene_habilitado === 1 ? getUrl('persona.showHabilitado', item.id_persona) : null"
                                    :title="item.id_carnet ? 'Pagos' : 'Sin Pagos Disponibles'"
                                    :class="item.tiene_habilitado === 1 ? 'cursor-pointer action-link text-gray-700' : 'cursor-not-allowed text-red-600'">
                                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                            d="M8 7V6a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-1M3 18v-7a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                    </svg>
                                </a>
                            </li>
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
                    <span class="text-lg py-2">No hay datos, por favor resgistre beneficiarios</span>
                </template>
            </DataTable>
            <Footer />
        </div>
    </div>
</template>
