<script setup>
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import {
    computed,
    nextTick,
    onMounted,
    ref,
    watch
} from 'vue';
import {
    Head,
    Link,
    router,
    useForm,
    usePage
} from '@inertiajs/vue3';
import PagoReporte from '../Reporte/pagoReporte.vue';
import Rutas from '@/components/Rutas.vue';

const page = usePage();
const gestiones = computed(() => page.props.gestiones);
const resultados = computed(() => page.props.resultados);
const resultadoDatos = computed(() => page.props.resultadoDatos);
const pagosDisponibles = computed(() => page.props.pagosDisponibles);
const notificacionTutor = computed(() => page.props.notificacionTutor);

const tipo = ref('');
const contenido = ref('');
const showMensaje = ref(false);
const reportes = ref(false);

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

const distritos = [
    'D-1',
    'D-2',
    'D-3',
    'D-4',
    'D-5',
    'D-6',
    'D-7',
    'AGUIRRE',
    'CHIÑATA',
    'LAVA LAVA',
    'UCUCHI',
    'PALCA',
];

const meses = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

const form = useForm({
    gestion: '',
    mes: '',
    distrito: '',
    errors: {
        gestion: false
    }
});

const montoTotal = computed(() => {
    if (!resultados.value.data || resultados.value.data.length === 0) {
        return 0;
    }
    return resultados.value.data.reduce((total, resultado) => {
        return total + (parseFloat(resultado.monto) || 0); // Usar monto_gestion
    }, 0);
});

const validate = () => {
    // Reinicia los errores
    form.errors.gestion = false;
    form.errors.mes = false;

    // Valida cada campo
    if (!form.gestion) {
        form.errors.gestion = true;
    }

    if (!form.mes) {
        form.errors.mes = true;
    }

    // Retorna true si no hay errores en ninguno de los dos
    return !form.errors.gestion && !form.errors.mes;
};


const Log = useForm({
    descripcion: 'Impresión de reporte de pagos mensuales'
});

const reporte = (item) => {
    reportes.value = true;
    Log.get(route('pago.reporteLog'), {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
}

const buscar = () => {
    if (!validate()) {
        mostrarMensaje('error', 'Datos vacios', 'Proporcione datos para realizar la busqueda.');
        return;
    }

    form.get(route('pago.reportePago'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {

        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
};
</script>

<template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido" @close="cerrarMensaje" />
        </div>
        <Header class="mb-0" :datos="notificacionTutor.data" :pagos="pagosDisponibles" />
        <!-- <Seccion class="mt-1 mr-1" nombre="Reporte de Pagos" texto="Gestione todos los tutores existentes o añada uno nuevo" :boton="false" /> -->

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Informe Mensual de Pagos</h1>
                <Rutas label1="Inicio" label2="Pagos" label3="Informe Mensual" />
            </div>
        </div>

        <div class="mx-0">
            <div class="flex flex-col border border-gray-200 dark:border-gray-700 p-2 py-0 mr-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="p-2">
                    <!-- Formulario de búsqueda mejorado -->
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end mb-0">
                        <!-- Campo Gestión con mejor diseño -->
                        <div class="lg:col-span-1">
                            <label for="gestion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-400 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z" />
                                    </svg>
                                    <span>Gestión</span>
                                    <span class="text-red-500">*</span>
                                </div>
                            </label>
                            <div class="relative">
                                <select id="gestion" v-model="form.gestion" :class="[
                                            'w-full appearance-none bg-gray-50 dark:bg-gray-700 border text-sm rounded-lg px-4 py-2.5 pr-10 transition-all duration-200',
                                            'focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-600',
                                            'hover:border-gray-400 dark:hover:border-gray-500',
                                            form.errors.gestion 
                                                ? 'border-red-500 text-red-600 focus:ring-red-500/20 focus:border-red-500 bg-red-50 dark:bg-red-900/20' 
                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200'
                                        ]">
                                    <option value="" selected disabled hidden class="text-gray-400">
                                        Seleccione la gestión
                                    </option>
                                    <option v-if="!gestiones || gestiones.length === 0" disabled class="text-gray-400">
                                        No hay datos disponibles
                                    </option>
                                    <option v-for="gestion in gestiones" :key="gestion" :value="gestion" class="text-gray-700 dark:text-gray-200">
                                        {{ gestion }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="lg:col-span-1">
                            <label for="meses" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-gray-400 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M8 14h8m-4-7V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z" />
                                    </svg>
                                    <span>Mes</span>
                                    <span class="text-red-500">*</span>
                                </div>
                            </label>
                            <div class="relative">
                                <select id="mes" v-model="form.mes" :class="[
                                            'w-full appearance-none bg-gray-50 dark:bg-gray-700 border text-sm rounded-lg px-4 py-2.5 pr-10 transition-all duration-200',
                                            'focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-600',
                                            'hover:border-gray-400 dark:hover:border-gray-500',
                                            form.errors.mes 
                                                ? 'border-red-500 text-red-600 focus:ring-red-500/20 focus:border-red-500 bg-red-50 dark:bg-red-900/20' 
                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200'
                                        ]">
                                    <option value="" selected disabled hidden class="text-gray-400">
                                        Seleccione el mes
                                    </option>
                                    <option v-for="mes in meses" :key="mes" :value="mes" class="text-gray-700 dark:text-gray-200">
                                        {{ mes }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Campo Distrito con mejor diseño -->
                        <div class="lg:col-span-1">
                            <label for="distrito" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                                <div class="flex items-center space-x-2">
                                    <svg class="h-4 w-4 text-gray-400 group-focus-within:text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Distrito</span>
                                </div>
                            </label>
                            <div class="relative">
                                <select id="distrito" v-model="form.distrito" class="w-full appearance-none bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-sm rounded-lg px-4 py-2.5 pr-10 text-gray-700 dark:text-gray-200 transition-all duration-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-600 hover:border-gray-400 dark:hover:border-gray-500">
                                    <option value="" selected disabled hidden class="text-gray-400">
                                        Seleccione el distrito
                                    </option>
                                    <option v-for="distrito in distritos" :key="distrito" :value="distrito" class="text-gray-700 dark:text-gray-200">
                                        {{ distrito }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Botones mejorados -->
                        <div class="flex items-center lg:col-span-1 ">
                            <button @click="buscar" type="button" class="group relative h-8 w-full md:w-auto px-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center transform hover:scale-105 focus:ring-2 focus:ring-blue-500/50 focus:outline-none">
                                <div class="flex items-center space-x-2">
                                    <svg class="h-4 w-4 transition-transform group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Buscar</span>
                                </div>
                            </button>

                            <Link :href="route('pago.reportePago')" class="group ms-2 relative h-8 w-full md:w-auto px-4 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-medium text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center transform hover:scale-105 focus:ring-2 focus:ring-gray-500/50 focus:outline-none">
                            <div class="flex items-center space-x-2">
                                <svg class="h-4 w-4 transition-transform group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Reiniciar</span>
                            </div>
                            </Link>
                        </div>

                        <div class="lg:col-span-2 px-3">
                            <div class="flex items-center justify-between">
                                <div v-if="resultados.data?.length > 0" class="flex items-center justify-center w-full min-h-[70px] p-0">
                                    <div class="flex items-center space-x-2  px-8 py-2 rounded-lg border border-emerald-200">
                                        <div class="flex-shrink-0 w-6 h-6 bg-emerald-600 rounded-md flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                Total Pagado
                                            </p>
                                            <p class="text-ms font-bold text-gray-900 dark:text-gray-100">
                                                Bs {{ montoTotal }} <span class="text-xs font-normal ms-6">({{ resultados.data.length }} registros)</span>
                                            </p>
                                            <span class="text-xs text-gray-500 dark:text-gray-400"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Placeholder cuando no hay resultados -->
                                <div v-else class="flex items-center justify-center w-full text-gray-500 dark:text-gray-400 p-0 rounded-xl min-h-[70px]">
                                    <div class="text-center">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium">Realiza una búsqueda para ver los resultados</p>
                                        <p class="text-xs mt-1">Los totales aparecerán aquí</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <main class="flex-1 overflow-x-hidden overflow-y-auto mr-1">
            <div class="mx-0" v-if="resultados.data?.length > 0">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-1">
                    <table class="w-full text-xs text-left rtl:text-right border  text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 bg-white dark:text-gray-400">
                            <tr class="border-b">
                                <th scope="col" class="text-center px-2 py-2">
                                    Nº
                                </th>
                                <th scope="col" class="px-2 py-2">
                                    Apellido Nombre
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    C.I.
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Distrito
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Tutor
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Telefono
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Gestión
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Fecha Habilitado
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Obs. Habilitado
                                </th>
                                <th scope="col" class="text-center px-2 py-2">
                                    Fecha Pago
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in resultados.data" :key="item.id_habilitado" class="bg-white border-b font-medium text-gray-900 whitespace-nowrap dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="text-center px-2 py-2.5">
                                    {{ index + 1 }}
                                </td>
                                <td class="capitalize px-2 py-2.5 whitespace-nowrap min-w-[150px]">
                                    {{ item.apellido_persona }} {{ item.nombre_persona }}
                                </td>
                                <td class="px-2 py-2.5">
                                    {{ item.ci_persona }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.distrito }}
                                </td>
                                <td class="capitalize px-2 py-2.5 whitespace-nowrap min-w-[150px]">
                                    {{ item.nombre_tutor }} {{ item.apellido_tutor }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.telefono }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.gestion }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.fecha_habilitado }}
                                </td>
                                <td class="px-2 py-2.5">
                                    <div v-if="item.observaciones_habilitado === 'ninguna' || item.observaciones_habilitado === ' ' || item.observaciones_habilitado === null" class="text-center italic">
                                        ninguna
                                    </div>
                                    <div v-else class="text-center">
                                        {{ item.observaciones_habilitado }}
                                    </div>
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.fecha_pago }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="fixed bottom-12 right-4">
                        <div href="#" @click.prevent="reporte(resultados.data)" class="z-50 cursor-pointer border border-black/40 rounded-full h-12 w-12 bg-[#13326A]/10 hover:bg-[#13326A] dark:bg-blue-700/20 dark:hover:bg-blue-700 text-white/50 hover:text-white flex items-center justify-center transition-all duration-300">
                            <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <PagoReporte v-if="reportes" :datos="resultadoDatos" :download="true" :monto="montoTotal" tipoR="pago" :tipo="true" style="display: none;" />
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center h-[60vh] w-full py-8 px-4 text-gray-700 dark:text-gray-300">
                <!-- Icono con animación sutil -->
                <div class="mb-4 p-4 rounded-full bg-gray-100 dark:bg-gray-800 transition-all duration-300 hover:scale-105">
                    <svg class="w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <!-- Mensaje principal -->
                <h3 class="text-xl font-semibold text-center mb-2">Datos no disponibles</h3>

                <!-- Mensaje secundario -->
                <p class="text-gray-500 dark:text-gray-400 text-center max-w-md mx-auto mb-6">
                    No hemos encontrado información con los filtros actuales. Por favor, ajuste los parámetros de búsqueda.
                </p>
            </div>
        </main>
        <Paginacion class="mb-1" :links="resultados.links" :from="resultados.from" :to="resultados.to" :total="resultados.total" />
    </div>
</div>
</template>
