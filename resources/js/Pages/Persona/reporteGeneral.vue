<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import {
    computed,
    ref
} from 'vue';
import {
    Head,
    Link,
    useForm,
    usePage
} from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Rutas from '@/components/Rutas.vue';
import Dropdown from '@/components/Dropdown.vue';
import Botones from '@/components/Botones.vue';

const page = usePage();
const resultados = computed(() => page.props.resultados);
const gestiones = computed(() => page.props.gestiones);

console.log("Resultados:", resultados.value);

// Lista de mensajes
const mensajes = ref([]);

// Mostrar mensaje
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

// Eliminar mensaje
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// Estados reactivos
const selectedYear = ref(null);

// Mapeo de números de mes a nombres
const mesesNombres = {
    1: 'Enero',
    2: 'Febrero',
    3: 'Marzo',
    4: 'Abril',
    5: 'Mayo',
    6: 'Junio',
    7: 'Julio',
    8: 'Agosto',
    9: 'Septiembre',
    10: 'Octubre',
    11: 'Noviembre',
    12: 'Diciembre'
};

// Cuando seleccionas una gestión
const selectGestion = (year) => {
    selectedYear.value = year.anio;

    // Pedimos al backend los datos de esa gestión
    router.get(route('persona.reporteGeneral'), {
        'gestion_gestion': selectedYear.value
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Función para obtener color del estado
const getEstadoColor = (estado) => {
    switch (estado) {
        case 'H':
            return 'text-green-500';
        case 'NH':
            return 'text-red-500';
        case 'R':
            return 'text-yellow-500';
        default:
            return 'bg-gray-800 text-gray-700';
    }
}

// Función para obtener texto del estado
const getEstadoTexto = (estado) => {
    if (!estado) return '-';
    switch (estado) {
        case 'H':
            return 'M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z';
        case 'NH':
            return 'M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z';
        case 'R':
            return 'M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z';
        default:
            return '-';
    }
}

// Función para obtener el mes específico o devolver valor por defecto
const getMesEstado = (mesesArray, mesNumero) => {
    if (!mesesArray || !Array.isArray(mesesArray)) {
        return { mes: mesNumero, estado: null };
    }
    const mesData = mesesArray.find(m => m.mes === mesNumero);
    return mesData || { mes: mesNumero, estado: null };
}
</script>

<template>
<Head title="UMADIS - Reporte General" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido" @close="cerrarMensaje" />
        </div>
        <Header class="mb-0" />

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Reporte General</h1>
                <Rutas label1="Inicio" label2="Beneficiarios" label3="Reporte General" />
            </div>
        </div>

        <div class="mx-0">
            <div class="flex flex-col border border-gray-200 dark:border-gray-700 p-4 py-1 mr-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <!-- Formulario de búsqueda -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end mb-1">
                    <!-- Campo Gestión -->
                    <div class="flex flex-col lg:col-span-1">
                        <div class="group">
                            <label for="gestion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <div class="flex items-center space-x-1">
                                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Gestión</span>
                                    <span class="text-red-500">*</span>
                                </div>
                            </label>

                            <Dropdown align="left" width="60">
                                <template #trigger>
                                    <button class="inline-flex items-center gap-3 p-3 text-sm font-semibold text-white bg-[#13326A] hover:bg-blue-800 transition-all focus:ring-1 focus:ring-blue-300 rounded-lg shadow-lg hover:shadow-xl w-full" type="button">
                                        <span class="flex-1 text-left">{{ selectedYear || 'Seleccionar Gestión' }}</span>
                                        <svg class="w-2.5 h-2.5 transition-transform duration-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="w-64 bg-gradient-to-r rounded-xl from-slate-50 to-slate-100 overflow-hidden">
                                        <div class="max-h-60 overflow-y-auto">
                                            <ul class="py-2">
                                                <li v-if="!gestiones || gestiones.length === 0" class="px-4 py-3">
                                                    <div class="flex items-center gap-3 text-slate-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                        <span class="text-sm">No hay datos disponibles</span>
                                                    </div>
                                                </li>
                                                <li v-for="year in gestiones" :key="year.anio">
                                                    <a href="#" @click.prevent="selectGestion(year)" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-blue-50 transition-colors duration-150" :class="selectedYear && selectedYear.toString() === year.anio.toString() ? 'bg-blue-100 text-blue-800 font-semibold border-r-4 border-blue-500' : 'text-slate-700 hover:text-blue-700'">
                                                        <span>{{ year.anio }}</span>
                                                        <svg v-if="selectedYear && selectedYear.toString() === year.anio.toString()" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
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

                    <!-- Leyenda de estados -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-center gap-4 h-full">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Habilitado</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-500 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">No Habilitado</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-yellow-500 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Regularizado</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end lg:col-span-1">
                        <Botones :data="{nombre: 'reporteGeneral', reporte: true}" />
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 overflow-x-hidden overflow-y-auto mr-1">
            <div class="mx-0" v-if="resultados && resultados.length > 0">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-1">
                    <table class="w-full text-xs text-left border dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-white dark:text-gray-400 sticky top-0">
                            <tr class="border-b">
                                <th scope="col" class="text-center px-2 py-2">Nº</th>
                                <th scope="col" class="text-center px-2 py-2">C.I.</th>
                                <th scope="col" class="text-center px-2 py-2">Apellidos y Nombres</th>
                                <!-- Generar columnas para cada mes (1-12) -->
                                <th v-for="mes in 12" :key="mes" scope="col" class="text-center px-2 py-2">
                                    {{ mesesNombres[mes] }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(persona, index) in resultados" :key="index" class="bg-white border-b hover:bg-gray-50">
                                <td class="text-center px-2 py-1.5">{{ index + 1 }}</td>
                                <td class="text-center px-2 py-1.5">{{ persona.ci_persona }}</td>
                                <td class="px-2 py-1.5 capitalize">
                                    {{ persona.apellido_persona }} {{ persona.nombre_persona }}
                                </td>
                                <!-- Mostrar estado de cada mes -->
                                <td v-for="mesData in persona.meses" :key="mesData.mes" class="px-2 py-1.5">
                                    <div class="flex items-center justify-center">
                                        <svg class="w-3 h-3" :class="getEstadoColor(mesData.estado)" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" :d="getEstadoTexto(mesData.estado)" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center h-[60vh] w-full py-8 px-4 text-gray-700 dark:text-gray-300">
                <div class="mb-4 p-4 rounded-full bg-gray-100 dark:bg-gray-800 transition-all duration-300 hover:scale-105">
                    <svg class="w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-center mb-2">Datos no disponibles</h3>
                <p class="text-gray-500 dark:text-gray-400 text-center max-w-md mx-auto mb-6">
                    Seleccione una gestión para ver el reporte general.
                </p>
            </div>
        </main>
    </div>
</div>
</template>
