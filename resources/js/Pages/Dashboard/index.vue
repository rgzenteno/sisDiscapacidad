<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Icon from '@/components/Icon.vue';
import { router } from '@inertiajs/vue3'
import { watch } from 'vue'

// Page Data
const page = usePage();


const totalHabilitados = computed(() => page.props.totalHabilitados)
const totalBajas = computed(() => page.props.totalBajas)
const montoPagado = computed(() => page.props.montoPagado)
const personasPagadas = computed(() => page.props.personasPagadas)
const totalPersonasRegistradas = computed(() => page.props.totalPersonasRegistradas)

// Para los filtros iniciales (en lugar de hardcodear el año actual en el ref):

// Computed Properties
const totalPersonas = computed(() => page.props.totalPersonas);

const totalHabilitadosKPI = computed(() => page.props.totalHabilitados)
const totalBajaDef = computed(() => page.props.totalBajaDef);
const carnetVencido = computed(() => page.props.carnetVencido);

const totalTutores = computed(() => page.props.totalTutores);

console.log(totalTutores.value);


const distribucionRegional = computed(() => page.props.distribucionRegional);

const discapacidadPorDistrito = computed(() => page.props.discapacidadPorDistrito);
const conteoEstados = computed(() => page.props.conteoEstados);
const conteoDiscapacidad = computed(() => page.props.conteoDiscapacidad);
const personasSinCarnet = computed(() => page.props.personasSinCarnet);
const registros = computed(() => page.props.registros);


const props = defineProps({ debugEstados: Array })
//console.log(props.debugEstados)

// Chart References
const disabilityChartRef = ref(null);
const genderDistributionRef = ref(null);
const disabilityByDistrictRef = ref(null);
const regionalDistributionRef = ref(null);
const paymentTrendsRef = ref(null);
const stackedBarChartRef = ref(null);
const areaChartRef = ref(null);

// Formatting Functions
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB'
    }).format(value);
};

function formatAmount(value, locale = 'es-BO') {
    const amount = Number(value) || 0;
    return amount.toLocaleString(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getMonthNameFromDate(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const index = parseInt(monthNumber, 10) - 1;
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

const gestionSeleccionada = ref(page.props.gestionActual)
const mesSeleccionado = ref('all')

const datosFiltrados = computed(() => {
    if (mesSeleccionado.value === 'all') {
        return {
            beneficiarios: totalBeneficiarios.value,
            bajaTemp: totalBajaTemp.value,
            bajaDef: totalBajaDef.value,
            carnetVencido: carnetVencido.value,
            label: `Acumulado ${gestionSeleccionada.value}`
        }
    }
    const item = mesesFiltrados.value.find(i => i.MES === mesSeleccionado.value)
    return {
        beneficiarios: item?.CANTIDAD_HABILITADOS ?? 0,
        bajaTemp: item?.BAJA_TEMPORAL ?? '—',
        bajaDef: item?.BAJA_DEFINITIVA ?? '—',
        carnetVencido: item?.CARNET_VENCIDO ?? '—',
        label: `${getMonthNameFromNumber(mesSeleccionado.value)} ${gestionSeleccionada.value}`
    }
})

const gestiones = computed(() =>
    [...new Set(registros.value.map(i => i.GESTION))]
)

const mesesFiltrados = computed(() =>
    gestionSeleccionada.value
        ? registros.value.filter(i => i.GESTION === gestionSeleccionada.value)
        : []
)


const totalPagos = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + i.CANTIDAD_PAGOS, 0)
)

const totalGeneral = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + i.TOTAL, 0)
)

watch([gestionSeleccionada, mesSeleccionado], ([gestion, mes]) => {
    router.get(route('dashboard'),
        { gestion, mes },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['totalHabilitados', 'totalBajas', 'montoPagado', 'personasPagadas', 'totalPersonasRegistradas', 'totalPago']
        }
    )
})

function getMonthNameFromNumber(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const index = parseInt(monthNumber, 10) - 1;
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

// Chart Creation Functions
const createDisabilityChart = () => {
    if (!disabilityChartRef.value) return;
    if (disabilityChartRef.value.chart) {
        disabilityChartRef.value.chart.destroy();
    }
    const ctx = disabilityChartRef.value.getContext('2d');
    const chartData = {
        fisica: conteoDiscapacidad.value?.fisica || 0,
        auditiva: conteoDiscapacidad.value?.auditiva || 0,
        intelectual: conteoDiscapacidad.value?.intelectual || 0,
        mental_psiquica: conteoDiscapacidad.value?.mental_psiquica || 0
    };
    disabilityChartRef.value.chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['FISICA-MOTORA', 'AUDITIVA', 'INTELECTUAL', 'MENTAL-PSIQUICA'],
            datasets: [{
                data: [chartData.fisica, chartData.auditiva, chartData.intelectual, chartData.mental_psiquica],
                backgroundColor: ['#5B8FF9', '#7EC8E3', '#003366', '#708090'],
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#4B5563',
                        font: { family: "'Inter', sans-serif", size: 11 },
                        padding: 14,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: { boxPadding: 6, displayColors: false }
            },
            animation: { animateScale: true, duration: 800 }
        }
    });
};

const createGenderDistribution = () => {
    if (!genderDistributionRef.value) return;
    const estadosData = conteoEstados.value;
    const ctx = genderDistributionRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Activos', 'Baja Temporal', 'Baja Definitiva'],
            datasets: [{
                data: [estadosData.activos || 0, estadosData.baja_temporal || 0, estadosData.baja_definitiva || 0],
                backgroundColor: ['#4682B4', '#7EC8E3', '#1E3F66'],
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true, pointStyle: 'rectRounded', font: { size: 11 } } } }
        }
    });
};

const createRegionalDistribution = () => {
    if (!regionalDistributionRef.value) return;
    const distribucionData = distribucionRegional.value;
    const ctx = regionalDistributionRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'AGUIRRE', 'CHIÑATA', 'LAVA LAVA', 'UCUCHI', 'PALCA'],
            datasets: [{
                data: [
                    distribucionData.d1 || 0, distribucionData.d2 || 0, distribucionData.d3 || 0,
                    distribucionData.d4 || 0, distribucionData.d5 || 0, distribucionData.d6 || 0,
                    distribucionData.d7 || 0, distribucionData.aguirre || 0, distribucionData.chinata || 0,
                    distribucionData.lava_lava || 0, distribucionData.ucuchi || 0, distribucionData.palca || 0
                ],
                backgroundColor: ['#003366', '#4A7BA7', '#5B8FF9', '#7EC8E3', '#708090', '#003366', '#5B8FF9', '#003366', '#4A7BA7', '#5B8FF9', '#7EC8E3', '#708090'],
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y} beneficiarios` } }
            },
            scales: {
                x: { ticks: { font: { weight: '600', size: 11 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { font: { weight: '600', size: 11 } }, grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
};

const createDisabilityByDistrict = () => {
    if (!disabilityByDistrictRef.value) return;
    if (disabilityByDistrictRef.value.chart) {
        disabilityByDistrictRef.value.chart.destroy();
    }
    const ordenDistritos = ['D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'AGUIRRE', 'CHIÑATA', 'LAVA LAVA', 'UCUCHI', 'PALCA'];
    const dataMap = {};
    discapacidadPorDistrito.value.forEach(item => { dataMap[item.distrito] = item; });
    const fisicaData = ordenDistritos.map(d => dataMap[d]?.fisica || 0);
    const auditivaData = ordenDistritos.map(d => dataMap[d]?.auditiva || 0);
    const intelectualData = ordenDistritos.map(d => dataMap[d]?.intelectual || 0);
    const mentalData = ordenDistritos.map(d => dataMap[d]?.mental_psiquica || 0);
    const ctx = disabilityByDistrictRef.value.getContext('2d');
    disabilityByDistrictRef.value.chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ordenDistritos,
            datasets: [
                { label: 'Física-Motora', data: fisicaData, backgroundColor: '#5B8FF9', borderRadius: 2 },
                { label: 'Auditiva', data: auditivaData, backgroundColor: '#7EC8E3', borderRadius: 2 },
                { label: 'Intelectual', data: intelectualData, backgroundColor: '#003366', borderRadius: 2 },
                { label: 'Mental-Psíquica', data: mentalData, backgroundColor: '#708090', borderRadius: 2 }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, usePointStyle: true } },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.x} personas` } }
            },
            scales: {
                x: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
                y: { stacked: true, ticks: { font: { weight: '600', size: 10 } } }
            }
        }
    });
};

const createPaymentTrends = () => {
    if (!paymentTrendsRef.value) return;
    const ctx = paymentTrendsRef.value.getContext('2d');
    const data = [850000, 920000, 880000, 950000, 1020000, 980000];
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Pagos Realizados',
                data: data,
                borderColor: '#4682B4',
                backgroundColor: 'rgba(70,130,180,0.08)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4682B4',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => 'Pagos: ' + formatCurrency(ctx.parsed.y) } }
            },
            scales: {
                y: { beginAtZero: false, ticks: { callback: value => formatCurrency(value), font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
};

const getStatusClass = (estado) => {
    switch (estado) {
        case 'completado':
        case 'pagado':
            return 'bg-green-100 text-green-800';
        case 'pendiente':
            return 'bg-yellow-100 text-yellow-800';
        case 'proceso':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const createStackedBarChart = () => {
    if (!stackedBarChartRef.value) return;
    const ctx = stackedBarChartRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [
                { label: 'Pagos Aprobados', data: [500000, 600000, 700000, 800000, 900000, 1000000], backgroundColor: '#5B8FF9', borderRadius: 3 },
                { label: 'Pagos Pendientes', data: [200000, 300000, 400000, 500000, 600000, 700000], backgroundColor: '#7EC8E3', borderRadius: 3 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, usePointStyle: true } } },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { callback: value => formatCurrency(value), font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
};

const createAreaChart = () => {
    if (!areaChartRef.value) return;
    const ctx = areaChartRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Beneficiarios Activos',
                data: [1200, 1300, 1400, 1500, 1600, 1700],
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3B82F6'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false } } }
        }
    });
};

onMounted(() => {
    createDisabilityChart();
    createGenderDistribution();
    createRegionalDistribution();
    createPaymentTrends();
    createStackedBarChart();
    createAreaChart();
    createDisabilityByDistrict();
});
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex h-screen -ml-1 bg-gray-200 font-roboto">
        <!-- Sidebar de navegación -->
        <Sidebar />

        <!-- Contenedor principal -->
        <div class="flex-1 flex flex-col overflow-hidden">


            <!-- Header -->
            <Header class="mb-0" />
            <!-- ═══════════════════════════════════════════
                    BARRA SUPERIOR: Título + Filtros Globales
                ═══════════════════════════════════════════ -->
            <!-- <pre class="text-xs">{{ debugEstados }}</pre> -->
            <div
                class="bg-white border-b border-gray-200 sm:px-6 mt-1 mr-1 pl-2 py-3 rounded-lg sticky top-0 z-10 shadow-sm">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h1 class="text-base font-bold text-gray-800 leading-tight">Panel de Control</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Unidad Municipal de Atención a Personas con
                            Discapacidad</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="text-xs font-medium text-gray-400 uppercase tracking-wider hidden sm:block">Filtrar</span>
                        <div class="h-4 w-px bg-gray-200 hidden sm:block"></div>
                        <div class="flex items-center gap-1.5">
                            <label class="text-xs text-gray-500">Gestión</label>
                            <select v-model="gestionSeleccionada"
                                class="text-xs border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-300 font-medium">
                                <option v-for="g in gestiones" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <label class="text-xs text-gray-500">Mes</label>
                            <select v-model="mesSeleccionado"
                                class="text-xs border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-300 font-medium">
                                <option value="all">Todos</option>
                                <option v-for="item in mesesFiltrados" :key="item.MES" :value="item.MES">
                                    {{ getMonthNameFromNumber(item.MES) }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1 overflow-x-hidden rounded-t-lg overflow-y-auto">
                <div class="py-3 px-1  pl-0 sm:p-5 space-y-5">
                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 1: KPIs PRINCIPALES (filtrados)
                    ═══════════════════════════════════════════ -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">
                            Resumen operativo
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 sm:gap-3 gap-2">

                            <!-- Habilitados -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Habilitados
                                        </p>
                                        <div class="bg-blue-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="user" class-name="text-blue-600" :size="30"
                                                :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">{{ totalHabilitadosKPI }}</p>
                                    <p class="text-xs text-blue-500 font-medium mt-1">
                                        {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                            `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                                    </p>
                                </div>
                            </div>

                            <!-- Personas Pagadas -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Pagos<br class="hidden sm:block"> Realizados
                                        </p>
                                        <div class="bg-indigo-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="cash" fill="none" stroke="currentColor"
                                                stroke-width="2" class-name="text-indigo-600" :size="30" :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">{{ personasPagadas }}</p>
                                    <p class="text-xs text-indigo-500 font-medium mt-1">
                                        {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                            `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                                    </p>
                                </div>
                            </div>

                            <!-- Monto Pagado -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Monto Pagado
                                        </p>
                                        <div class="bg-emerald-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="dollar" fill="none" stroke="currentColor"
                                                stroke-width="2" class-name="text-emerald-600" :size="30"
                                                :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">
                                        Bs. {{ formatAmount(montoPagado) }}
                                    </p>
                                    <p class="text-xs text-emerald-600 font-medium mt-1">
                                        {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                            `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                                    </p>
                                </div>
                            </div>

                            <!-- Total Bajas -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-red-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Total Bajas
                                        </p>
                                        <div class="bg-red-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="circleMinus" class-name="text-red-500"
                                                :size="30" :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">{{ totalBajas }}</p>
                                    <p class="text-xs text-red-500 font-medium mt-1">
                                        {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                            `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                                    </p>
                                </div>
                            </div>

                            <!-- Personas Registradas -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-sky-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Personas<br class="hidden sm:block"> Registradas
                                        </p>
                                        <div class="bg-sky-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="users" class-name="text-sky-500" :size="30"
                                                :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">{{ totalPersonasRegistradas
                                    }}</p>
                                    <p class="text-xs text-sky-500 font-medium mt-1">
                                        {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                            `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </section>

                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 2: KPIs GLOBALES (estáticos)
                    ═══════════════════════════════════════════ -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">
                            Indicadores globales del padrón</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 sm:gap-3 gap-2">

                            <!-- Total Registros -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-sky-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start ">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Total<br class="hidden sm:block"> Registros
                                        </p>
                                        <div class="bg-sky-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="user" class-name="text-sky-500" :size="30"
                                                :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">{{ totalPersonas }}</p>
                                    <p class="text-xs text-sky-500 font-medium mt-1">Personas en padrón</p>
                                </div>
                            </div>

                            <!-- Total Tutores -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-blue-800 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start ">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Total<br class="hidden sm:block"> Tutores
                                        </p>
                                        <div class="bg-blue-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="users" class-name="text-blue-800" :size="30"
                                                :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">{{ totalTutores }}</p>
                                    <p class="text-xs font-medium mt-1" style="color: #1E3F66">Tutores registrados</p>
                                </div>
                            </div>

                            <!-- Monto Total Pagado -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500 rounded-l-xl"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start ">
                                        <p class="text-xs text-emerald-600 font-medium mt-1">
                                            Año fiscal dic {{ gestionSeleccionada - 1 }} – nov {{ gestionSeleccionada }}
                                        </p>
                                        <div class="bg-emerald-50 p-1 pb-0 rounded-lg">
                                            <Icon :icon-button="true" name="dollar" fill="none" stroke="currentColor"
                                                stroke-width="2" class-name="text-emerald-600" :size="30"
                                                :height="30" />
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900">Bs. {{ formatAmount(page.props.totalPago) }}</p>
                                    <p class="text-xs text-emerald-600 font-medium mt-1">Acumulado histórico</p>
                                </div>
                            </div>

                            <!-- Sin Carnet -->
                            <div
                                class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full rounded-l-xl"
                                    :class="personasSinCarnet > 0 ? 'bg-red-500' : 'bg-emerald-500'"></div>
                                <div class="pl-2">
                                    <div class="flex justify-between items-start ">
                                        <p
                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide leading-tight">
                                            Sin Carnet<br class="hidden sm:block"> Registrado
                                        </p>

                                        <div class="p-1 pb-0 rounded-lg"
                                            :class="personasSinCarnet > 0 ? 'bg-red-50' : 'bg-emerald-50'">
                                            <svg v-if="personasSinCarnet > 0" xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                            </svg>
                                            <svg v-else xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-xl font-bold"
                                        :class="personasSinCarnet > 0 ? 'text-red-600' : 'text-emerald-600'">{{
                                            personasSinCarnet }}</p>
                                    <p class="text-xs font-medium mt-1"
                                        :class="personasSinCarnet > 0 ? 'text-red-500' : 'text-emerald-600'">
                                        {{ personasSinCarnet > 0 ? 'Personas sin carnet' : 'Todos con carnet' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 3: GRÁFICAS DE COMPOSICIÓN
                    ═══════════════════════════════════════════ -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">
                            Composición del padrón</p>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                            <!-- Tipos de Discapacidad (doughnut) -->
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700">Tipos de Discapacidad</h3>
                                    <span
                                        class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">Padrón
                                        total</span>
                                </div>
                                <canvas ref="disabilityChartRef" class="w-full"></canvas>
                            </div>

                            <!-- Distribución por Estado (doughnut) -->
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700">Estado del Beneficiario</h3>
                                    <span
                                        class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">Padrón
                                        total</span>
                                </div>
                                <canvas ref="genderDistributionRef" class="w-full"></canvas>
                            </div>

                            <!-- Resumen Mensual (card-list) -->
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700">Meses de Pago</h3>
                                    <select v-model="gestionSeleccionada"
                                        class="text-xs border border-gray-200 rounded-md px-2 py-1 text-gray-600 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-200 cursor-pointer">
                                        <option v-for="g in gestiones" :key="g" :value="g">{{ g }}</option>
                                    </select>
                                </div>

                                <div class="flex-1 space-y-1.5 overflow-y-auto max-h-56 pr-0.5">
                                    <div v-if="mesesFiltrados.length === 0"
                                        class="flex flex-col items-center justify-center py-6 text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mb-1.5 text-gray-300"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-xs">Sin datos disponibles</p>
                                    </div>
                                    <div v-for="(item, index) in mesesFiltrados" :key="index"
                                        class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-blue-50 transition-colors border border-gray-100 hover:border-blue-100">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-2 h-2 rounded-full shrink-0"
                                                style="background-color: #5B8FF9"></div>
                                            <div>
                                                <p class="text-xs font-semibold text-gray-800">{{
                                                    getMonthNameFromNumber(item.MES) }}</p>
                                                <p class="text-xs text-gray-400">
                                                    Hab: <span class="text-blue-600 font-medium">{{
                                                        item.CANTIDAD_HABILITADOS }}</span>
                                                    &nbsp;·&nbsp;
                                                    Pagos: <span class="text-indigo-500 font-medium">{{
                                                        item.CANTIDAD_PAGOS }}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-bold text-gray-800">Bs. {{
                                                item.TOTAL.toLocaleString() }}</p>
                                            <p class="text-xs text-gray-400">c/u Bs. {{ item.MONTO.toLocaleString() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer totales -->
                                <div
                                    class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-xs text-center">
                                    <div class="bg-blue-50 rounded-lg py-2">
                                        <p class="text-gray-400 text-xs">Habilitados</p>
                                        <p class="font-bold text-blue-700 text-sm">{{ totalHabilitados }}</p>
                                    </div>
                                    <div class="bg-indigo-50 rounded-lg py-2">
                                        <p class="text-gray-400 text-xs">Pagos</p>
                                        <p class="font-bold text-indigo-600 text-sm">{{ totalPagos }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg py-2 border border-gray-100">
                                        <p class="text-gray-400 text-xs">Total Bs.</p>
                                        <p class="font-bold text-gray-700 text-sm">{{ (totalGeneral / 1000).toFixed(0)
                                            }}K</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 4: DISTRIBUCIÓN POR DISTRITO
                    ═══════════════════════════════════════════ -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">
                            Distribución territorial</p>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                            <!-- Discapacidad por Distrito (horizontal stacked) -->
                            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700">Tipos de Discapacidad por Distrito</h3>
                                    <span
                                        class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">Todos
                                        los distritos</span>
                                </div>
                                <div style="height: 340px; position: relative;">
                                    <canvas ref="disabilityByDistrictRef" style="position:absolute;inset:0;"></canvas>
                                </div>
                            </div>

                            <!-- Tabla resumen por Distrito -->
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700">Beneficiarios por Distrito</h3>
                                    <span
                                        class="text-xs text-gray-500 bg-blue-200 px-2 py-0.5 rounded-md border border-gray-100">Ranking</span>
                                </div>
                                <div class="overflow-auto max-h-80">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left text-gray-400 font-semibold uppercase tracking-wide">
                                                <th class="pb-2 pr-2">#</th>
                                                <th class="pb-2 pr-2">Distrito</th>
                                                <th class="pb-2 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <tr v-for="(item, i) in discapacidadPorDistrito" :key="i"
                                                class="hover:bg-gray-50">
                                                <td class="py-2 pr-2 text-gray-400 font-medium">{{ i + 1 }}</td>
                                                <td class="py-2 pr-2 font-semibold text-gray-700">{{ item.distrito }}
                                                </td>
                                                <td class="py-2 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <div
                                                            class="flex-1 max-w-16 bg-gray-100 rounded-full h-1.5 hidden sm:block">
                                                            <div class="h-1.5 rounded-full"
                                                                style="background-color: #5B8FF9"
                                                                :style="{ width: `${Math.min(100, ((item.fisica + item.auditiva + item.intelectual + item.mental_psiquica) / 200) * 100)}%` }">
                                                            </div>
                                                        </div>
                                                        <span class="font-bold text-gray-800">{{ item.fisica +
                                                            item.auditiva + item.intelectual + item.mental_psiquica
                                                            }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 5: TABLA DETALLE MENSUAL
                    ═══════════════════════════════════════════ -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">Detalle
                            mensual de gestión</p>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                            <!-- Header tabla -->
                            <div
                                class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gray-50/60">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-700">Gestión {{ gestionSeleccionada }}</h3>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-full font-medium bg-blue-50 text-blue-700 border border-blue-100">{{
                                            mesesFiltrados.length }} meses</span>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <span>Habilitados: <strong class="text-gray-800">{{
                                        totalHabilitados.toLocaleString() }}</strong></span>
                                    <span class="text-gray-200">|</span>
                                    <span>Pagos: <strong class="text-gray-800">{{ totalPagos.toLocaleString()
                                            }}</strong></span>
                                    <span class="text-gray-200">|</span>
                                    <span>Total: <strong class="text-gray-800">Bs. {{ totalGeneral.toLocaleString()
                                            }}</strong></span>
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr
                                            class="text-left text-gray-400 font-semibold uppercase tracking-wide text-xs border-b border-gray-100">
                                            <th class="px-5 py-3">Mes</th>
                                            <th class="px-4 py-3">Habilitados</th>
                                            <th class="px-4 py-3">Cantidad Pagos</th>
                                            <th class="px-4 py-3">Monto Unitario</th>
                                            <th class="px-4 py-3">Total Pagado</th>
                                            <th class="px-4 py-3">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-if="mesesFiltrados.length === 0">
                                            <td colspan="6" class="text-center text-gray-400 py-10 text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-8 w-8 mx-auto mb-2 text-gray-200" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Sin datos para la gestión seleccionada
                                            </td>
                                        </tr>
                                        <tr v-for="(item, index) in mesesFiltrados" :key="index"
                                            class="hover:bg-blue-50/40 transition-colors">
                                            <td class="px-5 py-3 font-semibold text-gray-800">{{
                                                getMonthNameFromNumber(item.MES) }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{
                                                item.CANTIDAD_HABILITADOS.toLocaleString() }}</td>
                                            <td class="px-4 py-3 font-medium" style="color: #4A7BA7">{{
                                                item.CANTIDAD_PAGOS.toLocaleString() }}</td>
                                            <td class="px-4 py-3 text-gray-600">Bs. {{ item.MONTO.toLocaleString() }}
                                            </td>
                                            <td class="px-4 py-3 font-bold text-gray-800">Bs. {{
                                                item.TOTAL.toLocaleString() }}</td>
                                            <td class="px-4 py-3">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                                    :class="item.ESTADO === 'completado' || item.ESTADO === 'pagado'
                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                                                        : item.ESTADO === 'pendiente'
                                                            ? 'bg-yellow-50 text-yellow-700 border border-yellow-100'
                                                            : 'bg-gray-100 text-gray-500 border border-gray-200'">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="item.ESTADO === 'completado' || item.ESTADO === 'pagado'
                                                        ? 'bg-emerald-500'
                                                        : item.ESTADO === 'pendiente'
                                                            ? 'bg-yellow-500'
                                                            : 'bg-gray-400'"></span>
                                                    {{ item.ESTADO ?? 'Sin estado' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 6: BENEFICIARIOS POR DISTRITO (bar)
                        + TENDENCIA DE PAGOS
                    ═══════════════════════════════════════════ -->
                    <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">Análisis
                            de pagos</p>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                            <!-- Beneficiarios por Distrito (bar chart) -->
                            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold text-gray-700">Beneficiarios por Distrito</h3>
                                    <span
                                        class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">Padrón
                                        total</span>
                                </div>
                                <canvas ref="regionalDistributionRef" class="w-full"></canvas>
                            </div>

                            <!-- Tendencia de Pagos (line chart) -->
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-sm font-bold text-gray-700">Tendencia de Pagos</h3>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-full font-semibold bg-red-50 text-red-600 border border-red-100">-3.9%</span>
                                </div>
                                <p class="text-xs text-gray-400 mb-4">Últimos 6 meses · referencial</p>
                                <canvas ref="paymentTrendsRef" class="w-full"></canvas>
                                <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-center">
                                    <div>
                                        <p class="text-xs text-gray-400">Este mes</p>
                                        <p class="text-sm font-bold text-gray-800">980K</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">Promedio</p>
                                        <p class="text-sm font-bold text-gray-800">933K</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400">Variación</p>
                                        <p class="text-sm font-bold text-red-600">-3.9%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ═══════════════════════════════════════════
                        SECCIÓN 7: PROGRESOS + PAGOS APROBADOS/PENDIENTES
                    ═══════════════════════════════════════════ -->
                    <!--  <section>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">
                            Seguimiento operativo</p>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4"> -->

                    <!-- Progreso Mensual -->
                    <!-- <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-sm font-bold text-gray-700">Progreso Mensual</h3>
                                    <span
                                        class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">Gestión
                                        actual</span>
                                </div>
                                <div class="space-y-5">
                                    <div>
                                        <div class="flex justify-between items-center mb-1.5">
                                            <span class="text-xs font-medium text-gray-600">Beneficiarios nuevos</span>
                                            <span class="text-xs font-bold text-gray-800">85%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="h-2 rounded-full"
                                                style="width: 85%; background: linear-gradient(90deg, #4682B4, #5D9CDB)">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center mb-1.5">
                                            <span class="text-xs font-medium text-gray-600">Pagos procesados</span>
                                            <span class="text-xs font-bold text-gray-800">92%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="h-2 rounded-full"
                                                style="width: 92%; background: linear-gradient(90deg, #5D9B9B, #7BB5B5)">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center mb-1.5">
                                            <span class="text-xs font-medium text-gray-600">Notificaciones
                                                enviadas</span>
                                            <span class="text-xs font-bold text-gray-800">78%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="h-2 rounded-full"
                                                style="width: 78%; background: linear-gradient(90deg, #7EC8E3, #B0E0E6)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Promedio general</span>
                                    <span class="text-xl font-bold text-gray-800">85%</span>
                                </div>
                            </div> -->
                        <!-- Pagos Aprobados vs Pendientes (stacked bar) -->
                        <!-- <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="text-sm font-bold text-gray-700">Pagos Aprobados vs Pendientes</h3>
                                        <span
                                            class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100">Últimos
                                            6 meses</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mb-4">Datos referenciales</p>
                                    <canvas ref="stackedBarChartRef" class="w-full"></canvas>
                                </div> -->
                        <!--  </div>
                    </section> -->

                    <!-- Espaciado inferior -->
                    <div class="h-1"></div>
                </div>
            </main>
        </div>
    </div>
</template>
