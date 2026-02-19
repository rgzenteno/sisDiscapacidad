<script setup>
import Busqueda from '@/components/Busqueda.vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import ModalLogs from '@/components/ModalLogs.vue';
import Paginacion from '@/components/Paginacion.vue';
import Rutas from '@/components/Rutas.vue';
import Seccion from '@/components/Seccion.vue';
import Sidebar from '@/components/Sidebar.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue'

const showModal = ref(false);
const dataLog = ref('');
const idLog = ref('');

const props = defineProps({
    logs: Object
})

const formatDate = (date) => {
    return new Date(date).toLocaleString()
}

const modalLogs = (id, log) => {
    idLog.value = id;
    dataLog.value = log;
    showModal.value = true;
}

const getActionColorClass = (action) => {
    // Sistema de colores mejorado y consistente sin repeticiones
    const classes = {
        'actualizar': 'bg-blue-50 text-blue-700 border border-blue-200',
        'eliminar': 'bg-orange-50 text-orange-700 border border-orange-200',
        'delete': 'bg-amber-50 text-amber-700 border border-amber-200',
        'habilitado': 'bg-green-50 text-green-700 border border-green-200',
        'deshabilitado': 'bg-slate-50 text-slate-700 border border-slate-200',
        'importar': 'bg-purple-50 text-purple-700 border border-purple-200',
        'error': 'bg-red-50 text-red-700 border border-red-200',
        'ver': 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        'pago realizado': 'bg-teal-50 text-teal-700 border border-teal-200',
        'reporte imprimido': 'bg-pink-50 text-pink-700 border border-pink-200',
        'crear': 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    };

    return classes[action] || 'bg-gray-50 text-gray-700 border border-gray-200';
};

const getModuleColorClass = (module) => {
    // Colores para módulos - más sutiles pero profesionales
    return 'bg-gray-50 text-gray-700 border border-gray-200';
};
</script>
<template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col">
        <Header class="mb-0" />
        <!-- <Busqueda :initial-value="filters.buscador" name="beneficiario" only="logs" :data="logs" ruta-busqueda="persona.index" /> -->
        <ModalLogs v-if="showModal" :id="idLog" :data="dataLog" @close="showModal = false" />

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Registro Actividad</h1>
                <Rutas label1="Inicio" label3="Registro Actividad" />
            </div>
            <!-- <Seccion class="mt-1 " nombre="Tutor" texto="Gestione todos los tutores existentes" :botonReportTutor="true" :boton="false" /> -->
        </div>

        <div class="flex justify-between p-4 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg mr-1">

        </div>

        <main class="flex-1 overflow-x-hidden overflow-y-auto border-b-2 border-x-2 rounded-b-lg mr-1">
            <div v-if="logs.data.length > 0" class="mx-0 bg-white py-2">
                <div class="relative mx-4 overflow-x-auto border rounded-lg shadow-md sm:rounded-lg">
                    <table class="w-full rounded-lg text-xs text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                            <tr class="border-b">
                                <th class="px-6 py-2 tracking-wider">Fecha</th>
                                <th class="px-6 py-2 tracking-wider">Usuario</th>
                                <th class="px-6 py-2 tracking-wider">Módulo</th>
                                <th class="px-6 py-2 tracking-wider">Acción</th>
                                <th class="px-6 py-2 tracking-wider">Descripción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="log in logs.data" :key="log.id" @click="modalLogs(log.id, log)" class="cursor-pointer hover:bg-gray-50 transition duration-200">
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 font-medium whitespace-nowrap">
                                    <span class="capitalize">{{ log.causer?.nombre || 'Usuario Eliminado' }}</span>
                                </td>
                                <!-- Celda del Módulo - Diseño Profesional -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold tracking-wide shadow-sm ring-1 ring-inset" :class="getModuleColorClass(log.properties.module)">
                                            {{ log.properties.module }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Celda de la Acción - Diseño Profesional -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold tracking-wide shadow-sm ring-1 ring-inset capitalize" :class="getActionColorClass(log.properties.action)">
                                            {{ log.properties.action }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ log.description.length > 30 ? log.description.slice(0, 30) + '...' : log.description }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center text-gray-800 h-3/5 w-full mt-10 pt-10">
                <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                </svg>
                <span class="text-lg py-2">No se encontraron datos. ¡Agregue beneficiarios para continuar!</span>
            </div>
        </main>
        <Paginacion v-if="logs.data.length > 0" :links="logs.links" :from="logs.from" :to="logs.to" :total="logs.total" />
        <Footer />
    </div>
</div>
</template>
