<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import {
    computed,
    ref,
    onMounted  
} from 'vue';
import {
    Head,
    Link,
    useForm,
    usePage
} from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Rutas from '@/components/Rutas.vue';
import Reporte from './reporte.vue';
import Dropdown from '@/components/Dropdown.vue';
import Botones from '@/components/Botones.vue';
import Paginacion from '@/components/Paginacion.vue';

const page = usePage();
const resultados = computed(() => page.props.resultados);
const resultadosReporte = computed(() => page.props.resultadosReporte);
const gestiones = computed(() => page.props.gestiones);
const mesesNumeros = computed(() => page.props.mesesNumeros);

//console.log("Resultados:", resultados.value);

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
    mes: '',
    errors: {
        gestion: false
    }
});

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

const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
};
// Script actualizado

// Estados reactivos
const selectedYear = ref(null);
const selectedMonth = ref(null);
const selectedMonthLabel = ref('');

onMounted(() => {
    selectedYear.value = localStorage.getItem('selectedYear') || null;
    selectedMonth.value = localStorage.getItem('selectedMonth') ? parseInt(localStorage.getItem('selectedMonth')) : null;
    selectedMonthLabel.value = localStorage.getItem('selectedMonthLabel') || '';
});

// Convertimos los números de mes a objetos para el dropdown
const mesesDropdown = computed(() => {
    const mesesMap = [
        { value: 1, label: 'Enero' },
        { value: 2, label: 'Febrero' },
        { value: 3, label: 'Marzo' },
        { value: 4, label: 'Abril' },
        { value: 5, label: 'Mayo' },
        { value: 6, label: 'Junio' },
        { value: 7, label: 'Julio' },
        { value: 8, label: 'Agosto' },
        { value: 9, label: 'Septiembre' },
        { value: 10, label: 'Octubre' },
        { value: 11, label: 'Noviembre' },
        { value: 12, label: 'Diciembre' },
    ];
    return mesesNumeros.value.map(n => mesesMap.find(m => m.value === n));
});

// Cuando seleccionas una gestión
const selectGestion = (year) => {
    if (!selectedMonth.value) {
        mostrarMensaje('info', 'Campos requeridos', 'Seleccione el mes para filtar los registros.');
        
    }
    selectedYear.value = year.anio;
    localStorage.setItem('selectedYear', selectedYear.value); 
    selectedMonth.value = null;
    selectedMonthLabel.value = '';

    // Pedimos al backend los meses disponibles para esta gestión
    router.get(route('persona.reporte'), { 
        'gestion_gestion': selectedYear.value  // Cambiado de 'gestion.gestion' a 'gestion_gestion'
        
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

const generearInforme = () => {
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

// Cuando seleccionas un mes
const selectMes = (mes) => {
    selectedMonth.value = mes.value;
    selectedMonthLabel.value = mes.label;
    localStorage.setItem('selectedMonth', selectedMonth.value); 
    localStorage.setItem('selectedMonthLabel', selectedMonthLabel.value);

    if (selectedYear.value && selectedMonth.value) {
        router.get(route('persona.reporte'), {
            'gestion_gestion': selectedYear.value,  // Cambiado aquí también
            mes: selectedMonth.value
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }
}

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

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Informe Beneficiarios</h1>
                <Rutas label1="Inicio" label2="Beneficiarios" label3="Informe Beneficiarios" />
            </div>           
        </div>

        <div class="mx-0">
            <div class="flex flex-col border border-gray-200 dark:border-gray-700 p-4 py-1 mr-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <!-- Formulario de búsqueda -->
                <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end mb-1">
                    <!-- Campo Gestión -->
                    <div class="flex flex-col lg:col-span-1">
                        <div class="group">
                            <label for="gestion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                                <div class="flex items-center space-x-1">
                                    <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z" clip-rule="evenodd"/>
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

                    <!-- Campo Mes convertido a Dropdown -->
                    <div class="lg:col-span-1">
                        <label for="meses" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 transition-colors group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd" />
                                </svg>
                                <span>Mes</span>
                                <span class="text-red-500">*</span>
                            </div>
                        </label>

                        <Dropdown align="left" width="60">
                            <template #trigger>
                                <button class="inline-flex items-center gap-3 p-3 text-sm font-semibold text-white bg-[#13326A] hover:bg-blue-800 transition-all focus:ring-1 focus:ring-blue-300 rounded-lg shadow-lg hover:shadow-xl w-full" type="button">
                                    <span class="flex-1 text-left">{{ selectedMonthLabel || 'Seleccionar Mes' }}</span>
                                    <svg class="w-2.5 h-2.5 transition-transform duration-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="w-64 bg-gradient-to-r rounded-xl from-slate-50 to-slate-100 overflow-hidden">
                                    <div class="max-h-60 overflow-y-auto">
                                        <ul class="py-2">
                                            <li v-for="mes in mesesDropdown" :key="mes.value">
                                                <a href="#" @click.prevent="selectMes(mes)" class="flex items-center justify-between px-4 py-3 text-sm hover:bg-blue-50 transition-colors duration-150" :class="selectedMonth && selectedMonth === mes.value ? 'bg-blue-100 text-blue-800 font-semibold border-r-4 border-blue-500' : 'text-slate-700 hover:text-blue-700'">
                                                    <span>{{ mes.label }}</span>
                                                    <svg v-if="selectedMonth && selectedMonth === mes.value" class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
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
                                                    {{ resultados.data[0].gestion }}
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
                                                    Total
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    Bs. {{ formatCurrency(resultados.data[0].monto_total) }}
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
                                                    No pagar
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    Bs. {{ formatCurrency(resultados.data[0].monto_bajas) }}
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
                                                    Pagar
                                                </p>
                                                <p class="text-xs font-bold text-gray-900 dark:text-gray-100">
                                                    Bs. {{ formatCurrency(resultados.data[0].monto_activos) }}
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

                    <div class="flex justify-end lg:col-span-1">
                        <Botones :data="{nombre: 'beneficiario', reporte: true}" @generearInforme="generearInforme"/>
                    </div>
                </div>
            </div>
        </div>
        <main class="flex-1 overflow-x-hidden overflow-y-auto mr-1">
            <div class="mx-0" v-if="resultados.data?.length > 0">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-1">
                    <table class="w-full text-xs text-left rtl:text-right border dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-white dark:text-gray-400">
                            <tr class="border-b">
                                <th scope="col" class="text-center px-2 py-2">Nº</th>
                                <th scope="col" class="text-center px-2 py-2">C.I.</th>
                                <th scope="col" class="text-center px-2 py-2">Apellidos y nombres P.C.D.</th>
                                <th scope="col" class="text-center px-2 py-2">Grado de discapacidad</th>
                                <th scope="col" class="text-center px-2 py-2">Monto a Pagar (bs.)</th>
                                <th scope="col" class="text-center px-2 py-2">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in resultados.data" :key="item.id_habilitado" :class="{
                                    'bg-white border-b font-bold text-black whitespace-nowrap': item.estado_actual.estado === 'activo',
                                    'bg-blue-800 border-b font-medium text-blue-400 whitespace-nowrap': item.estado_actual.estado === 'baja_temporal',
                                    'bg-yellow-300 border-b font-bold text-red-600 whitespace-nowrap': item.estado_actual.estado === 'baja_definitiva'
                                }">
                                <td class="text-center px-2 py-1.5">
                                    {{ index + 1 }}
                                </td>
                                <td class="text-center px-2 py-1.5 ">
                                    {{ item.ci }}
                                </td>
                                <td class="px-2 py-1.5 uppercase">
                                    {{ item.apellido }} {{ item.nombre }}
                                </td>
                                <td class="text-center px-2 py-1.5">
                                    <span>GRAVE MUY GRAVE</span>
                                </td>
                                <td class="text-center px-2 py-1.5">
                                    {{ item.monto }}
                                </td>
                                <td class="text-center px-2 py-1.5">
                                    <span v-if="item.estado_actual.estado !== 'activo'" class="uppercase">{{ item.observaciones }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Reporte v-if="reportes" :datos="resultadosReporte" :gestion="resultados.data[0].gestion" :mes="resultados.data[0].mes" :download="true" tipoR="Gestión" :tipo="false" style="display: none;" />
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
        <div :class="resultados.length <= 100 ? 'mt-0.5' : 'mt-0'">
            <Paginacion v-if="resultados?.last_page > 1" :links="resultados.links" :from="resultados.from" :to="resultados.to" :total="resultados.total" />
        </div>
    </div>
</div>
</template>
