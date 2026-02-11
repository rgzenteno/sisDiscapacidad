<script setup>
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import DateField from '@/components/DateField.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import Seccion from '@/components/Seccion.vue';
import {
    computed,
    onMounted,
    ref,
    watch
} from 'vue';
import {
    Head,
    Link,
    useForm,
    usePage
} from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import PagoReporte from '../Reporte/pagoReporte.vue';
import Rutas from '@/components/Rutas.vue';

// Referencias y estados
const page = usePage();
const gestiones = computed(() => page.props.gestiones);
const resultados = computed(() => page.props.resultados);
const resultadosReporte = computed(() => page.props.resultadosReporte)
const pagosDisponibles = computed(() => page.props.pagosDisponibles);
const notificacionTutor = computed(() => page.props.notificacionTutor);

// Agregar la referencia que faltaba para opcion

const reportes = ref(false)
const opcion = ref('');
const opcion2 = ref('');

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

const montoTotal = computed(() => {
    if (!resultados.value.data || resultados.value.data.length === 0) {
        return 0;
    }
    return resultados.value.data.reduce((total, resultado) => {
        return total + (parseFloat(resultado.monto) || 0); // Usar monto_gestion
    }, 0);
});

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

const discapacidades = [
    'FISICA-MOTORA',
    'AUDITIVA',
    'VISUAL',
    'MENTAL-PSIQUICA',
    'MULTIPLE',
];

const estados = [
    'ACTIVO',
    'BAJA TEMPORAL',
    'BAJA DEFINITIVA',
];

const form = useForm({
    pagados: '',
    carnet: '',
    gestion: '',
    distrito: '',
    estado: '',
    discapacidad: ''
});

const reiniciar = () => {
    console.log('entra a reiniciar');
    form.value = {
        pagados: '',
        gestion: '',
        distrito: '',
        estado: '',
        discapacidad: ''
    };
    opcion.value = 'pagados';
    opcion2.value = 'pagados';
};

// Watch para la opción de pagados
/* watch(opcion, (newValue) => {
    if (form.pagados === '') {
        form.pagados = null;
    } else {
        form.pagados = newValue === 'pagados' ? '1' : '0';
    }
}, { immediate: true }); */

/* watch(opcion2, (newValue) => {
    if (form.carnet === '') {
        form.carnet = null;
    } else {
        form.carnet = newValue === 'carnet' ? '1' : '0';
    }
}, { immediate: true }); */

const buscar = () => {
    form.get(route('reporte.buscar'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {},
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
        <Header class="mb-0" />
        <!-- <Seccion class="mt-1 mr-1" nombre="Reporte de Pagos" texto="Gestione todos los tutores existentes o añada uno nuevo" :boton="false" /> -->

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Informe Genereal</h1>
                <Rutas label1="Inicio" label3="Informe General" />
            </div>
        </div>
        
        <div class="mx-0">
                <div class="flex flex-col p-3 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-lg shadow-xs">
                    <!-- Fila superior: Fechas y Radio Buttons -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <!-- Distrito -->
                        <div class="flex flex-col">
                            <label for="distrito" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Distrito
                            </label>
                            <select id="distrito" v-model="form.distrito" :class="['w-full cursor-pointer bg-gray-50 dark:bg-gray-700 border text-xs rounded-md p-2 dark:placeholder-gray-400 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500',
                                form.errors.distrito ? 'border-red-500 text-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']">
                                <option value="" selected disabled hidden class="text-gray-400">Seleccione el distrito</option>
                                <option v-for="distrito in distritos" :key="distrito" :value="distrito">
                                    {{ distrito }}
                                </option>
                            </select>
                        </div>

                        <!-- Gestión -->
                        <div class="flex flex-col">
                            <label for="gestion" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Gestión
                            </label>
                            <select id="gestion" v-model="form.gestion" :class="['w-full cursor-pointer bg-gray-50 dark:bg-gray-700 border text-xs rounded-md p-2 dark:placeholder-gray-400 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500',
                                form.errors.gestion ? 'border-red-500 text-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']">
                                <option value="" selected disabled hidden class="text-gray-400">Seleccione la gestión</option>
                                <option v-if="!gestiones || gestiones.length === 0" disabled>No hay datos</option>
                                <option v-for="gestion in gestiones" :key="gestion" :value="gestion">
                                    {{ getMonthNameFromDate(gestion) }}
                                </option>
                            </select>
                        </div>

                        <!-- Tipo Estado -->
                        <div class="flex flex-col">
                            <label for="estado" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tipo de Estado
                            </label>
                            <select id="estado" v-model="form.estado" :class="['w-full cursor-pointer bg-gray-50 dark:bg-gray-700 border text-xs rounded-md p-2 dark:placeholder-gray-400 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500',
                                form.errors.estado ? 'border-red-500 text-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']">
                                <option value="" selected disabled hidden class="text-gray-400">Seleccione el estado</option>
                                <option v-for="estado in estados" :key="estado" :value="estado">
                                    {{ estado }}
                                </option>
                            </select>
                        </div>

                        <!-- Estado de Carnet - Columna 2 -->
                        <!-- <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Carnet de Discapacidad
                            </label>
                            <div class="flex items-center justify-start md:justify-center space-x-4">
                                
                                <div>
                                    <input id="carnet" type="radio" v-model="opcion2" value="carnet" class="hidden peer">
                                    <label for="carnet" class="flex items-center px-3 py-1 border-2 rounded-lg cursor-pointer transition-all duration-200
                                                                peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:shadow-sm
                                                                hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300
                                                                border-gray-200 dark:border-gray-600 w-full">
                                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-semibold text-gray-900 dark:text-gray-100">Con Carnet</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">Identificado</span>
                                        </div>
                                    </label>
                                </div>

                                
                                <div>
                                    <input id="sin-carnet" type="radio" v-model="opcion2" value="sin-carnet" class="hidden peer">
                                    <label for="sin-carnet" class="flex items-center px-3 py-1 border-2 rounded-lg cursor-pointer transition-all duration-200
                                                                peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/20 peer-checked:shadow-sm
                                                                hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300
                                                                border-gray-200 dark:border-gray-600 w-full">
                                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 dark:bg-orange-900/30 mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-semibold text-gray-900 dark:text-gray-100">Sin Carnet</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">No identificado</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div> -->
                    </div>

                    <!-- Fila inferior: Distrito, Gestión y Botón -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                        <!-- Radio Buttons Estado Pagos -->
                        <!-- <div class="flex flex-col">
                            <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Estado de Pago
                            </label>                            
                            <div class="flex items-center justify-start md:justify-center space-x-4">
                                <div>
                                    <input id="pagados" type="radio" v-model="opcion" value="pagados" class="hidden peer">
                                    <label for="pagados" class="flex items-center px-3 py-1 border-2 rounded-lg cursor-pointer transition-all duration-200
                                            peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 peer-checked:shadow-sm
                                            hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300
                                            border-gray-200 dark:border-gray-600 w-full">
                                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-semibold text-gray-900 dark:text-gray-100">Pagados</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">Confirmados</span>
                                        </div>
                                    </label>
                                </div>                            
                                <div>
                                    <input id="no-pagados" type="radio" v-model="opcion" value="no-pagados" class="hidden peer">
                                    <label for="no-pagados" class="flex items-center px-3 py-1 border-2 rounded-lg cursor-pointer transition-all duration-200
                                            peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 peer-checked:shadow-sm
                                            hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300
                                            border-gray-200 dark:border-gray-600 w-full">
                                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="block text-xs font-semibold text-gray-900 dark:text-gray-100">Pendientes</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">Por cobrar</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div> -->

                        <!-- Tipo Discapacidad -->
                        <div class="flex flex-col">
                            <label for="discapacidad" class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tipo de Discapacidad
                            </label>
                            <select id="discapacidad" v-model="form.discapacidad" :class="['w-full cursor-pointer bg-gray-50 dark:bg-gray-700 border text-xs rounded-md p-2 dark:placeholder-gray-400 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500',
                                form.errors.discapacidad ? 'border-red-500 text-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']">
                                <option value="" selected disabled hidden class="text-gray-400">Seleccione la discapacidad</option>
                                <option v-for="discapacidad in discapacidades" :key="discapacidad" :value="discapacidad">
                                    {{ discapacidad }}
                                </option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-start md:justify-center space-x-4">
                            <button @click.prevent="buscar()" type="submit" class="h-[34px] w-full md:w-auto px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-md shadow-xs transition-colors duration-150 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                                Buscar
                            </button>
                            <Link :href="route('reporte.index')" type="button" class="h-[34px] w-full md:w-auto px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white font-medium text-xs rounded-md shadow-xs transition-colors duration-150 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reiniciar
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

        <main class="flex-1 overflow-x-hidden overflow-y-auto  mr-1 mt-1">
            <!-- Results Table -->
            <!-- Tabla de resultados -->
            <div v-if="resultados.data?.length > 0" class="relative overflow-x-auto shadow-md sm:rounded-lg mt-1">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-1">
                    <table class="w-full text-xs text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Nº
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Apellido Nombre
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            C.I.
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Distrito
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Estado
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Tutor
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Discapacidad
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Telefono
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Gestión
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Fecha Habilitado
                                        </div>
                                    </div>
                                </th>

                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Carnet
                                        </div>
                                    </div>
                                </th>
                                <th scope="col" class="p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Obs. Habilitado
                                        </div>
                                    </div>
                                </th>
                                <th v-if="resultados.data.some(item => item.fecha_pago)" scope="col" class="text-center p-2">
                                    <div class="flex">
                                        <div class="flex items-center">
                                            <input id="checkbox-all" type="checkbox" checked class="w-4 h-4 bg-gray-100 border-gray-300 rounded-sm focus:ring-0 focus:ring-offset-0 checked:border-blue-500 checked:bg-blue-500 hover:border-gray-400 transition-colors duration-200 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-blue-600">
                                            <label for="checkbox-all" class="sr-only">checkbox</label>
                                        </div>
                                        <div class="text-center px-1 py-2">
                                            Fecha Pago
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in resultados.data" :key="item.id_habilitado" class="bg-white border-b dark:bg-gray-800 font-medium text-gray-900 whitespace-nowrap dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="text-center px-2 py-2.5">
                                    {{ index + 1 }}
                                </td>
                                <td class="capitalize px-2 py-2.5 whitespace-nowrap min-w-[150px]">
                                    {{ item.apellido_persona }} {{ item.nombre_persona }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.ci_persona }}
                                </td>
                                <td class="text-center px-2 py-2.5">
                                    {{ item.distrito }}
                                </td>
                                <td class=" text-center px-2 py-2.5">
                                    {{ item.estado }}
                                </td>
                                <td class="capitalize px-2 py-2.5 whitespace-nowrap min-w-[150px]">
                                    {{ item.nombre_tutor }} {{ item.apellido_tutor }}
                                </td>
                                <td class="capitalize text-center px-2 py-2.5">
                                    {{ item.discapacidad }}
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
                                <td class="text-center px-2 py-2.5">
                                    {{ item.carnet }}
                                </td>
                                <td class="px-2 py-2.5">
                                    <span v-if="!item.observaciones_habilitado" class="block text-center">
                                        <span class="italic">ninguna</span>
                                    </span>
                                    <span v-else>
                                        {{ item.observaciones_habilitado }}
                                    </span>
                                </td>
                                <td v-if="item.fecha_pago" class="text-center px-2 py-2.5">
                                    {{ item.fecha_pago }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="fixed bottom-14 right-4 z-50">
                        <div href="#" @click.prevent="reportes = true" target="_blank" class="cursor-pointer rounded-full h-12 w-12 bg-[#13326A] hover:bg-gray-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white flex items-center justify-center">
                            <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <PagoReporte v-if="reportes" :datos="resultadosReporte" :download="true" tipoR="General" :tipo="resultados.data.some(itm => itm.fecha_pago)" style="display: none;" />
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center h-[60vh] w-full py-8 px-4 text-gray-700 dark:text-gray-300">
                <!-- Icono con animación sutil -->
                <div class="mb-0 p-4 rounded-full bg-gray-100 dark:bg-gray-800 transition-all duration-300 hover:scale-105">
                    <svg class="w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Mensaje principal -->
                <h3 class="text-xl font-semibold text-center mb-2">Datos no disponibles</h3>

                <!-- Mensaje secundario -->
                <p class="text-gray-500 dark:text-gray-400 text-center max-w-md mx-auto mb-6">
                    No hemos encontrado información con los filtros actuales.
                    <br> Por favor, ajuste los parámetros de búsqueda.
                </p>
            </div>
        </main>
        <Paginacion class="mb-1" :links="resultados.links" :from="resultados.from" :to="resultados.to" :total="resultados.total" />
    </div>
</div>
</template>
