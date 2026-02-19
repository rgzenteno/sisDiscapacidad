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
import Rutas from '@/components/Rutas.vue';
import Reporte from './reporte.vue';

const page = usePage();
const gestiones = computed(() => page.props.gestiones);
const resultados = computed(() => page.props.resultados);
const resultadoDatos = computed(() => page.props.resultadoDatos);
const pagosDisponibles = computed(() => page.props.pagosDisponibles);
const notificacionTutor = computed(() => page.props.notificacionTutor);

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

const form = useForm({
    gestion: '',
    errors: {
        gestion: false
    }
});

const getMonthNameFromDate = (dateString) => {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const year = dateString.substring(0, 4);
    const monthNumber = dateString.substring(5, 7);
    const monthName = months[parseInt(monthNumber) - 1] || 'Mes inválido';

    return `${monthName} - ${year}`;
};

// Función para obtener SOLO EL NOMBRE DEL MES
// Ejemplo: "2024-03-15" → "Marzo"
/* const getMonthNameFromDate = (dateString) => {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const monthNumber = dateString.substring(5, 7);
    return months[parseInt(monthNumber) - 1] || 'Mes inválido';
}; */

// Función para obtener SOLO EL AÑO
// Ejemplo: "2024-03-15" → "2024"
const getMonthNameFrom = (dateString) => {
    return dateString.substring(0, 4);
};

const validate = () => {
    // Reinicia los errores
    form.errors.gestion = false;

    // Valida cada campo
    if (!form.gestion) {
        form.errors.gestion = true;
    }

    // Retorna true si no hay errores
    return !form.errors.gestion;
};

const Log = useForm({
    descripcion: 'Impresión de reporte de pagos mensuales'
});

const reporte = (item) => {
    reportes.value = true;
    Log.get(route('pago.reporteLog'), {
        onSuccess: () => {
            // Puedes agregar algún mensaje de éxito si lo deseas
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

    form.get(route('gestion.reporte'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Puedes agregar algún mensaje de éxito si lo deseas
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
};

const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
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
        <Header class="mb-0" />

        <!-- <Seccion class="mt-1 mr-1" nombre="Reporte de Pagos" texto="Gestione todos los tutores existentes o añada uno nuevo" :boton="false" /> -->

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Informe Anual de Pagos</h1>
                <Rutas label1="Inicio" label2="Gestiones" label3="Informe Anual" />
            </div>
            <!-- <Seccion class="mt-1 " nombre="Tutor" texto="Gestione todos los tutores existentes" :botonReportTutor="true" :boton="false" /> -->
        </div>

        <div class="mx-0">
            <div class="flex flex-col border border-gray-200 dark:border-gray-700 p-4 py-1 mr-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <!-- Formulario de búsqueda -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end mb-1">
                    <!-- Campo Gestión -->
                    <div class="flex flex-col lg:col-span-1">
                        <label for="gestion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-400 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z" />
                                    </svg>
                                <span>Gestión</span>
                                <span class="text-red-500">*</span>
                            </div>
                        </label>
                        <select id="gestion" v-model="form.gestion" :class="[
                                            'w-full appearance-none bg-gray-50 dark:bg-gray-700 border text-sm rounded-lg px-4 py-2.5 pr-10 transition-all duration-200',
                                            'focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:bg-white dark:focus:bg-gray-600',
                                            'hover:border-gray-400 dark:hover:border-gray-500',
                                            form.errors.gestion 
                                                ? 'border-red-500 text-red-600 focus:ring-red-500/20 focus:border-red-500 bg-red-50 dark:bg-red-900/20' 
                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200'
                                        ]">
                            <option value="" selected disabled hidden class="text-gray-400">Seleccione la gestión</option>
                            <option v-if="!gestiones || gestiones.length === 0" disabled>No hay datos</option>
                            <option v-for="gestion in gestiones" :key="gestion" :value="gestion">
                                {{ gestion }}
                            </option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-center justify-start md:justify-center">
                        <button @click="buscar" type="button" class="group relative h-8 w-full md:w-auto px-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center transform hover:scale-105 focus:ring-2 focus:ring-blue-500/50 focus:outline-none">
                            <div class="flex items-center space-x-1">
                                <svg class="h-4 w-4 transition-transform group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                                <span>Buscar</span>
                            </div>
                        </button>

                        <Link :href="route('gestion.reporte')" class="group ms-2 relative h-8 w-full md:w-auto px-4 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-medium text-sm rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center transform hover:scale-105 focus:ring-2 focus:ring-gray-500/50 focus:outline-none">
                        <div class="flex items-center space-x-1">
                            <svg class="h-4 w-4 transition-transform group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reiniciar</span>
                        </div>
                        </Link>
                    </div>

                    <!-- Área de resultados con altura fija -->
                    <div class="lg:col-span-3">
                        <div class="min-h-[70px] flex items-center justify-center">
                            <!-- Resultados con datos -->
                            <div v-if="resultados.data?.length > 0" class="w-full space-y-2">
                                <!-- Header -->
                                <div class="text-center">
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        Información Presupuestaria
                                    </h2>
                                    <div class="w-12 h-0.5 bg-blue-600 mx-auto mt-1"></div>
                                </div>

                                <!-- Content -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                    <!-- Gestión -->
                                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-3 py-1 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0 w-6 h-6 mt-1 bg-blue-600 rounded-md flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    Gestión
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    {{ getMonthNameFrom(resultados.data[0].gestion) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Anual -->
                                    <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 p-3 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0 w-6 h-6 bg-emerald-600 rounded-md flex items-center justify-center">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    Anual
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    Bs. {{ formatCurrency(resultados.data[0].presupuesto_anual) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Utilizado -->
                                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 p-3 py-1 rounded-lg border border-orange-200 dark:border-orange-800">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0 w-6 h-6 bg-orange-600 rounded-md flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    Utilizado
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    Bs. {{ formatCurrency(resultados.data[0].total_pagado_anual) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Restante -->
                                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-3 py-1 rounded-lg border border-purple-200 dark:border-purple-800">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex-shrink-0 w-6 h-6 bg-purple-600 rounded-md flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    Restante
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    Bs. {{ formatCurrency(resultados.data[0].presupuesto_anual_restante) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado vacío -->
                            <div v-else class="text-center">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                    Sin datos disponibles
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Seleccione una gestión para ver la información.
                                </p>
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
                        <thead class="text-xs text-gray-700 uppercase bg-white dark:text-gray-400">
                            <tr class="border-b">
                                <th scope="col" class="text-center px-2 py-2">Nº</th>
                                <th scope="col" class="text-center px-2 py-2">Detalle</th>
                                <th scope="col" class="text-center px-2 py-2">Solicitud de desembolsos de Recursos Humanos en Bs.</th>
                                <th scope="col" class="text-center px-2 py-2">Número de beneficiarios PCD.</th>
                                <th scope="col" class="text-center px-2 py-2">Monto total pagado a PCD. en Bs.</th>
                                <th scope="col" class="text-center px-2 py-2">Número de pagos a PCD beneficiadas</th>
                                <th scope="col" class="text-center px-2 py-2">Cantidad de personas que no han cobrado el bono PCD.</th>
                                <th scope="col" class="text-center px-2 py-2">Total de saldos no cancelados</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in resultados.data" :key="item.id_habilitado" class="bg-white border-b font-medium text-gray-900 whitespace-nowrap dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="text-center px-2 py-2.5">
                                    {{ index + 1 }}
                                </td>
                                <td class="px-2 py-2.5 uppercase">
                                    Solicitud Económica <span class="ms-2">{{  getMonthNameFromDate(item.gestion) }}</span> 
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ formatCurrency(item.presupuesto) }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.cantidad_habilitadas }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ formatCurrency(item.total_pagado_contexto) }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.cantidad_total_pagos }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.cantidad_no_pagados }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ formatCurrency(item.presupuesto_restante) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="fixed bottom-8 right-8">
                        <div href="#" @click.prevent="reporte(resultados.data)" class="cursor-pointer border border-black/40 rounded-full h-12 w-12 bg-[#13326A]/20 hover:bg-[#13326A] dark:bg-blue-700/10 dark:hover:bg-blue-700 text-white/50 hover:text-white flex items-center justify-center transition-all duration-300">
                            <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <Reporte v-if="reportes" :datos="resultados.data" :gestion="resultados.data[0].gestion" :download="true" tipoR="Gestión" :tipo="false" style="display: none;" />
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
    </div>
</div>
</template>
