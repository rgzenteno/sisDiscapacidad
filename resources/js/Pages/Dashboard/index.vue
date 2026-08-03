<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, onMounted, ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';

/**
 * Componentes
 */
import AppLayout from '@/Layouts/AppLayout.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Icon from '@/components/Icon.vue';

/**
 * Composables y utilidades
 */
import { useColorSistema } from '@/composables/useColorSistema';
import { hexToRgb, generarEscala } from '@/utils/color';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const props = defineProps({ debugEstados: Array });

const page = usePage();

// Props principales
const totalHabilitados = computed(() => page.props.totalHabilitados);
const totalNoPagados = computed(() => page.props.totalNoPagados);
const montoPagado = computed(() => page.props.montoPagado);
const personasPagadas = computed(() => page.props.personasPagadas);
const totalPersonasRegistradas = computed(() => page.props.totalPersonasRegistradas);
const totalPersonas = computed(() => page.props.totalPersonas);
const totalBajaDef = computed(() => page.props.totalBajaDef);
const carnetVencido = computed(() => page.props.carnetVencido);
const totalTutores = computed(() => page.props.totalTutores);
const distribucionRegional = computed(() => page.props.distribucionRegional);
const discapacidadPorDistrito = computed(() => page.props.discapacidadPorDistrito);
const conteoEstados = computed(() => page.props.conteoEstados);
const conteoDiscapacidad = computed(() => page.props.conteoDiscapacidad);
const personasSinCarnet = computed(() => page.props.personasSinCarnet);
const registros = computed(() => page.props.registros);
const totalHabilitadosKPI = computed(() => page.props.totalHabilitados)
const resumenPorGestion = computed(() => page.props.resumenPorGestion ?? []);
const retroactivo = computed(() => page.props.retroactivo);

/**
 * Etiqueta compartida por las tres cards de retroactivos
 * (evita repetir el mismo ternario en cada card)
 */
const retroactivoLabel = computed(() =>
    retroactivo.value?.mesOriginal
        ? `Retroactivo ${getMonthNameFromNumber(retroactivo.value.mesOriginal)} ${gestionSeleccionada.value}`
        : `Retroactivo acumulado ${gestionSeleccionada.value}`
);

const maxTotalGestion = computed(() =>
    Math.max(...resumenPorGestion.value.map(i => Number(i.TOTAL_PAGADO)), 1)
);

/**
 * Retorna los datos KPI del mes o gestión seleccionada
 */
const datosFiltrados = computed(() => {
    if (mesSeleccionado.value === 'all') {
        return {
            beneficiarios: totalBeneficiarios.value,
            bajaTemp: totalBajaTemp.value,
            bajaDef: totalBajaDef.value,
            carnetVencido: carnetVencido.value,
            label: `Acumulado ${gestionSeleccionada.value}`
        };
    }
    const item = mesesFiltrados.value.find(i => i.MES === mesSeleccionado.value);
    return {
        beneficiarios: item?.CANTIDAD_HABILITADOS ?? 0,
        bajaTemp: item?.BAJA_TEMPORAL ?? '—',
        bajaDef: item?.BAJA_DEFINITIVA ?? '—',
        carnetVencido: item?.CARNET_VENCIDO ?? '—',
        label: `${getMonthNameFromNumber(mesSeleccionado.value)} ${gestionSeleccionada.value}`
    };
});

// Agrega estos computed que faltan
const totalBeneficiarios = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + (i.CANTIDAD_HABILITADOS ?? 0), 0)
);

const totalBajaTemp = computed(() => conteoEstados.value?.baja_temporal ?? 0);

// Corrige totalHabilitados de la tabla (suma de mesesFiltrados, no el KPI del servidor)
const totalHabilitadosTabla = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + (parseInt(i.CANTIDAD_HABILITADOS) || 0), 0)
);

/**
 * Lista de gestiones (años) únicas disponibles en los registros
 */
const gestiones = computed(() =>
    [...new Set(registros.value.map(i => i.GESTION))]
);

/**
 * Registros filtrados por la gestión actualmente seleccionada
 */
const mesesFiltrados = computed(() =>
    gestionSeleccionada.value
        ? registros.value.filter(i => i.GESTION === gestionSeleccionada.value)
        : []
);

/**
 * Total de pagos acumulados en los meses filtrados
 */
const totalPagos = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + (parseInt(i.CANTIDAD_PAGOS) || 0), 0)
);

/**
 * Monto total acumulado en los meses filtrados
 */
const totalGeneral = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + (parseFloat(i.TOTAL) || 0), 0)
);

// ============================================================================
// REFS - ESTADO UI
// ============================================================================
const gestionSeleccionada = ref(page.props.gestionActual);
const mesSeleccionado = ref('all');

// ============================================================================
// REFS - GRÁFICAS
// ============================================================================
const disabilityChartRef = ref(null);
const genderDistributionRef = ref(null);
const disabilityByDistrictRef = ref(null);
const regionalDistributionRef = ref(null);
const paymentTrendsRef = ref(null);
const stackedBarChartRef = ref(null);
const areaChartRef = ref(null);

// ============================================================================
// WATCHERS
// ============================================================================

/**
 * Cuando cambia la gestión, resetea el mes a 'all'.
 * El watcher de mesSeleccionado se encargará de hacer la petición.
 */
watch(gestionSeleccionada, (gestion) => {
    if (mesSeleccionado.value === 'all') {
        // El mes no va a cambiar, así que disparamos el router directamente
        router.get(
            route('dashboard'),
            { gestion, mes: 'all' },
            {
                preserveState: true,
                preserveScroll: true,
                only: [
                    'totalHabilitados',
                    'totalNoPagados',
                    'montoPagado',
                    'personasPagadas',
                    'totalPersonasRegistradas',
                    'totalPago',
                    'retroactivo',
                    'retroactivosHabilitado'
                ]
            }
        );
    } else {
        // El mes cambiará a 'all' y su watcher se encargará del router
        mesSeleccionado.value = 'all';
    }
});

/**
 * Cuando cambia el mes (incluyendo el reset a 'all' del watcher anterior),
 * dispara la petición con los valores actuales.
 */
watch(mesSeleccionado, (mes) => {
    router.get(
        route('dashboard'),
        { gestion: gestionSeleccionada.value, mes },
        {
            preserveState: true,
            preserveScroll: true,
            only: [
                'totalHabilitados',
                'totalNoPagados',
                'montoPagado',
                'personasPagadas',
                'totalPersonasRegistradas',
                'totalPago',
                'retroactivo',
                'retroactivosHabilitado'
            ]
        }
    );
});

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Formatea un valor numérico como moneda boliviana (BOB)
 * @param {number} value - Monto a formatear
 * @returns {string} Monto formateado con símbolo de moneda
 */
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB'
    }).format(value);
};

/**
 * Formatea un número con dos decimales según la localización indicada
 * @param {number} value - Monto a formatear
 * @param {string} locale - Código de localización (por defecto 'es-BO')
 * @returns {string} Monto formateado
 */
function formatAmount(value, locale = 'es-BO') {
    const amount = Number(value) || 0;
    return amount.toLocaleString(locale, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Obtiene el nombre del mes desde su número
 * @param {number|string} monthNumber - Número del mes (1-12)
 * @returns {string} Nombre del mes en español
 */
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

/**
 * Retorna las clases CSS de badge según el estado de un registro
 * @param {string} estado - Estado del registro (completado, pendiente, proceso)
 * @returns {string} Clases Tailwind para el badge
 */
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

// ============================================================================
// FUNCIONES - GRÁFICAS
// ============================================================================

/**
 * Paleta de un solo tono (claro a oscuro) derivada del color del sistema,
 * usada en los gráficos de esta página — cada categoría sigue teniendo un
 * color distinto, pero todos vienen del mismo hex configurado en
 * Configuración en vez de estar fijos.
 */
const { colorBase } = useColorSistema();
const escalaBrand = generarEscala(hexToRgb(colorBase.value));
const brand = (parada) => `rgb(${escalaBrand[parada]})`;
const brandAlpha = (parada, alpha) => `rgba(${escalaBrand[parada]}, ${alpha})`;

// Misma correspondencia categoría → tono en el doughnut y en las barras
// apiladas de "Tipos de Discapacidad", para que un tipo se vea siempre del
// mismo color en ambos gráficos.
const tonosDiscapacidad = { fisica: 300, auditiva: 500, intelectual: 700, mental: 900 };

/**
 * Crea el gráfico de dona de distribución por tipo de discapacidad
 */
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
                backgroundColor: [
                    brand(tonosDiscapacidad.fisica),
                    brand(tonosDiscapacidad.auditiva),
                    brand(tonosDiscapacidad.intelectual),
                    brand(tonosDiscapacidad.mental),
                ],
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

/**
 * Crea el gráfico de dona de distribución por estado del beneficiario
 */
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
                backgroundColor: [brand(400), brand(600), brand(900)],
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 14, usePointStyle: true, pointStyle: 'rectRounded', font: { size: 11 } }
                }
            }
        }
    });
};

/**
 * Crea el gráfico de barras de distribución de beneficiarios por distrito
 */
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
                backgroundColor: [800, 600, 400, 300, 700, 800, 400, 800, 600, 400, 300, 700].map(brand),
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

/**
 * Crea el gráfico de barras apiladas de discapacidad por distrito
 */
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
                { label: 'Física-Motora', data: fisicaData, backgroundColor: brand(tonosDiscapacidad.fisica), borderRadius: 2 },
                { label: 'Auditiva', data: auditivaData, backgroundColor: brand(tonosDiscapacidad.auditiva), borderRadius: 2 },
                { label: 'Intelectual', data: intelectualData, backgroundColor: brand(tonosDiscapacidad.intelectual), borderRadius: 2 },
                { label: 'Mental-Psíquica', data: mentalData, backgroundColor: brand(tonosDiscapacidad.mental), borderRadius: 2 }
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

/**
 * Crea el gráfico de línea de tendencias de pagos mensuales
 */
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
                borderColor: brand(500),
                backgroundColor: brandAlpha(500, 0.08),
                fill: true,
                tension: 0.4,
                pointBackgroundColor: brand(500),
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

/**
 * Crea el gráfico de barras apiladas de pagos aprobados vs pendientes
 */
const createStackedBarChart = () => {
    if (!stackedBarChartRef.value) return;
    const ctx = stackedBarChartRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [
                { label: 'Pagos Aprobados', data: [500000, 600000, 700000, 800000, 900000, 1000000], backgroundColor: brand(600), borderRadius: 3 },
                { label: 'Pagos Pendientes', data: [200000, 300000, 400000, 500000, 600000, 700000], backgroundColor: brand(300), borderRadius: 3 }
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

/**
 * Crea el gráfico de área de evolución de beneficiarios activos
 */
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
                borderColor: brand(500),
                backgroundColor: brandAlpha(500, 0.1),
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: brand(500)
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.04)' } },
                x: { grid: { display: false } }
            }
        }
    });
};

// ============================================================================
// LIFECYCLE
// ============================================================================
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
    <AppLayout>

        <!-- ═══════════════════════════════════════════
                    BARRA SUPERIOR: Título + Filtros Globales
                ═══════════════════════════════════════════ -->
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
                            class="text-xs border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand-300))] font-medium">
                            <option v-for="g in gestiones" :key="g" :value="g">{{ g }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs text-gray-500">Mes</label>
                        <select v-model="mesSeleccionado"
                            class="text-xs border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand-300))] font-medium">
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
                    <div class="flex items-center gap-2 px-1 mb-2.5">
                        <span
                            class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">Resumen
                            operativo</span>
                        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3">

                        <!-- Habilitados -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Habilitados</p>
                                <Icon :icon-button="true" name="user" class-name="text-blue-500" :size="18"
                                    :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ totalHabilitadosKPI
                                }}</p>
                            <p class="text-xs font-semibold text-blue-500 dark:text-blue-400 mt-1">
                                {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                    `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                            </p>
                        </div>

                        <!-- Pagos Realizados -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Pagos realizados</p>
                                <Icon :icon-button="true" name="cash" fill="none" stroke="currentColor" stroke-width="2"
                                    class-name="text-indigo-500" :size="18" :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ personasPagadas }}
                            </p>
                            <p class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 mt-1">
                                {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                    `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                            </p>
                        </div>

                        <!-- Monto Pagado -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Monto pagado</p>
                                <Icon :icon-button="true" name="dollar" fill="none" stroke="currentColor"
                                    stroke-width="2" class-name="text-emerald-500" :size="18" :height="18" />
                            </div>
                            <p class="text-xl font-bold text-slate-700 dark:text-slate-200">Bs. {{
                                formatAmount(montoPagado) }}</p>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                                {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                    `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                            </p>
                        </div>

                        <!-- No Pagados -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">No pagados</p>
                                <Icon :icon-button="true" name="alertTriangle" class-name="text-rose-500" :size="18"
                                    :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ totalNoPagados }}</p>
                            <p class="text-xs font-semibold text-rose-500 dark:text-rose-400 mt-1">
                                {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                    `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                            </p>
                        </div>

                        <!-- Personas Registradas -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Personas registradas</p>
                                <Icon :icon-button="true" name="users" class-name="text-sky-500" :size="18"
                                    :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{
                                totalPersonasRegistradas }}</p>
                            <p class="text-xs font-semibold text-sky-500 dark:text-sky-400 mt-1">
                                {{ mesSeleccionado === 'all' ? `Gestión ${gestionSeleccionada}` :
                                    `${getMonthNameFromNumber(mesSeleccionado)} ${gestionSeleccionada}` }}
                            </p>
                        </div>

                    </div>
                </section>

                <!-- ═══════════════════════════════════════════
                        SECCIÓN 1B: RETROACTIVOS (solo si la gestión los
                        tiene habilitados y hay datos cargados para el
                        mes/gestión seleccionado)
                    ═══════════════════════════════════════════ -->
                <section v-if="retroactivo?.activo">
                    <div class="flex items-center gap-2 px-1 mb-2.5">
                        <span
                            class="text-xs font-semibold text-amber-500 dark:text-amber-400 uppercase tracking-wide whitespace-nowrap">Retroactivos</span>
                        <div class="flex-1 h-px bg-amber-200 dark:bg-amber-900/40"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">

                        <!-- Habilitados retroactivo -->
                        <div
                            class="bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-amber-700 dark:text-amber-400">Habilitados retroactivo</p>
                                <Icon :icon-button="true" name="user" class-name="text-amber-500" :size="18"
                                    :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-amber-900 dark:text-amber-200">{{
                                retroactivo.totalHabilitados }}</p>
                            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1">
                                {{ retroactivoLabel }}
                            </p>
                        </div>

                        <!-- Pagos retroactivo -->
                        <div
                            class="bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-amber-700 dark:text-amber-400">Pagos retroactivo realizados</p>
                                <Icon :icon-button="true" name="cash" fill="none" stroke="currentColor"
                                    stroke-width="2" class-name="text-amber-500" :size="18" :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-amber-900 dark:text-amber-200">{{
                                retroactivo.personasPagadas }}</p>
                            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1">
                                {{ retroactivoLabel }}
                            </p>
                        </div>

                        <!-- Monto retroactivo -->
                        <div
                            class="bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-amber-700 dark:text-amber-400">Monto retroactivo pagado</p>
                                <Icon :icon-button="true" name="dollar" fill="none" stroke="currentColor"
                                    stroke-width="2" class-name="text-amber-500" :size="18" :height="18" />
                            </div>
                            <p class="text-xl font-bold text-amber-900 dark:text-amber-200">Bs. {{
                                formatAmount(retroactivo.montoPagado) }}</p>
                            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1">
                                {{ retroactivoLabel }}
                            </p>
                        </div>

                    </div>
                </section>

                <!-- ═══════════════════════════════════════════
                        SECCIÓN 2: KPIs GLOBALES (estáticos)
                    ═══════════════════════════════════════════ -->
                <section>
                    <div class="flex items-center gap-2 px-1 mb-2.5">
                        <span
                            class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">Indicadores
                            globales del padrón</span>
                        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">

                        <!-- Total Registros -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Total registros</p>
                                <Icon :icon-button="true" name="user" class-name="text-sky-500" :size="18"
                                    :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ totalPersonas }}</p>
                            <p class="text-xs font-semibold text-sky-500 dark:text-sky-400 mt-1">Personas en padrón
                            </p>
                        </div>

                        <!-- Total Tutores -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Total tutores</p>
                                <Icon :icon-button="true" name="users" class-name="text-blue-500" :size="18"
                                    :height="18" />
                            </div>
                            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ totalTutores }}</p>
                            <p class="text-xs font-semibold text-blue-500 dark:text-blue-400 mt-1">Tutores
                                registrados</p>
                        </div>

                        <!-- Monto Total Histórico -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Dic {{ gestionSeleccionada - 1 }} – Nov {{ gestionSeleccionada }}
                                </p>
                                <Icon :icon-button="true" name="dollar" fill="none" stroke="currentColor"
                                    stroke-width="2" class-name="text-emerald-500" :size="18" :height="18" />
                            </div>
                            <p class="text-xl font-bold text-slate-700 dark:text-slate-200">Bs. {{
                                formatAmount(page.props.totalPago) }}</p>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">Acumulado
                                histórico</p>
                        </div>

                        <!-- Sin Carnet -->
                        <div
                            class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400">Sin carnet registrado</p>
                                <svg v-if="personasSinCarnet > 0" class="w-4 h-4 text-rose-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <svg v-else class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-2xl font-bold mt-1"
                                :class="personasSinCarnet > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                                {{ personasSinCarnet }}
                            </p>
                            <p class="text-xs font-semibold mt-1"
                                :class="personasSinCarnet > 0 ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'">
                                {{ personasSinCarnet > 0 ? 'Personas sin carnet' : 'Todos con carnet' }}
                            </p>
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

                        <!-- Composicion del Padron -->
                        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2.5 px-0.5">
                                Detalle mensual de gestión</p>
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                                <!-- Header tabla -->
                                <div
                                    class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 bg-gray-50/60">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-gray-700">Gestión {{ gestionSeleccionada }}
                                        </h3>
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full font-medium bg-[rgb(var(--brand-50))] text-[rgb(var(--brand-700))] border border-[rgb(var(--brand-100))]">{{
                                                mesesFiltrados.length }} meses</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span>Habilitados: <strong class="text-gray-800">{{
                                            totalHabilitadosTabla.toLocaleString() }}</strong></span>
                                        <span class="text-gray-200">|</span>
                                        <span>Pagos: <strong class="text-gray-800">{{ totalPagos.toLocaleString()
                                                }}</strong></span>
                                        <span class="text-gray-200">|</span>
                                        <span>Total: <strong class="text-gray-800">Bs. {{ formatAmount(totalGeneral)
                                        }}</strong></span>
                                    </div>
                                </div>

                                <!-- Tabla -->
                                <div class="overflow-x-auto max-h-80">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr
                                                class="text-left text-gray-400 font-semibold uppercase tracking-wide text-xs border-b border-gray-100">
                                                <th class="px-5 py-3">Mes</th>
                                                <th class="px-4 py-3">Habilitados</th>
                                                <th class="px-4 py-3">Cantidad Pagos</th>
                                                <th class="px-4 py-3">No Pagados</th>
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
                                                class="hover:bg-[rgba(var(--brand-50),0.4)] transition-colors">
                                                <td class="px-5 py-3 font-semibold text-gray-800">{{
                                                    getMonthNameFromNumber(item.MES) }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{
                                                    item.CANTIDAD_HABILITADOS.toLocaleString() }}</td>
                                                <td class="px-4 py-3 font-medium" style="color: rgb(var(--brand-500))">{{
                                                    item.CANTIDAD_PAGOS.toLocaleString() }}</td>
                                                <td class="px-4 py-3 font-medium"
                                                    :class="item.NO_PAGADOS > 0 ? 'text-rose-600' : 'text-gray-600'">
                                                    {{ item.NO_PAGADOS.toLocaleString() }}
                                                </td>
                                                <td class="px-4 py-3 font-bold text-gray-800">Bs. {{
                                                    formatAmount(item.TOTAL) }}</td>
                                                <td class="px-4 py-3">
                                                    <span
                                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                                        :class="item.NO_PAGADOS === 0
                                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                                                            : 'bg-yellow-50 text-yellow-700 border border-yellow-100'">
                                                        <span class="w-1.5 h-1.5 rounded-full" :class="item.NO_PAGADOS === 0
                                                            ? 'bg-emerald-500'
                                                            : 'bg-yellow-500'"></span>
                                                        {{ item.NO_PAGADOS === 0 ? 'Completo' : 'Pendiente' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <!-- Resumen Mensual (card-list) -->
                        <!-- <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col">
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
                                    class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-[rgb(var(--brand-50))] transition-colors border border-gray-100 hover:border-[rgb(var(--brand-100))]">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2 h-2 rounded-full shrink-0" style="background-color: #5B8FF9">
                                        </div>
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


                            <div class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-xs text-center">
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
                    </div> -->
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
                    </div>
                </section>

                <!-- ═══════════════════════════════════════════
                        SECCIÓN 5: BENEFICIARIOS POR DISTRITO (bar)
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

                        <!-- ═══════════════════════════════════════════
        SECCIÓN: RESUMEN DE PAGOS POR GESTIÓN
    ═══════════════════════════════════════════ -->


                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-bold text-gray-700">Pagos por gestión</h3>
                                <span
                                    class="text-xs text-gray-500 bg-[rgb(var(--brand-100))] px-2 py-0.5 rounded-md border border-[rgb(var(--brand-200))]">
                                    Histórico
                                </span>
                            </div>

                            <!-- Lista de gestiones -->
                            <div class="flex-1 space-y-1.5 overflow-y-auto max-h-80 pr-0.5">
                                <div v-if="resumenPorGestion.length === 0"
                                    class="flex flex-col items-center justify-center py-6 text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mb-1.5 text-gray-300"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-xs">Sin datos disponibles</p>
                                </div>

                                <div v-for="(item, index) in resumenPorGestion" :key="index"
                                    class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-[rgb(var(--brand-50))] transition-colors border border-gray-100 hover:border-[rgb(var(--brand-100))]">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2 h-2 rounded-full shrink-0" style="background-color: #5B8FF9">
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-gray-800">Gestión {{ item.GESTION }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                Pagos: <span class="text-indigo-500 font-medium">{{
                                                    item.CANTIDAD_PAGOS.toLocaleString() }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-bold text-gray-800">Bs. {{
                                            formatAmount(item.TOTAL_PAGADO) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Totales -->
                            <div class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-xs text-center">
                                <div class="bg-indigo-50 rounded-lg py-2">
                                    <p class="text-gray-400 text-xs">Total pagos</p>
                                    <p class="font-bold text-indigo-600 text-sm">
                                        {{resumenPorGestion.reduce((s, i) => s + i.CANTIDAD_PAGOS, 0).toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 rounded-lg py-2 border border-gray-100">
                                    <p class="text-gray-400 text-xs">Total histórico Bs.</p>
                                    <p class="font-bold text-gray-700 text-sm">
                                        Bs. {{formatAmount(resumenPorGestion.reduce((s, i) => s +
                                            Number(i.TOTAL_PAGADO), 0))}}
                                    </p>
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
    </AppLayout>
</template>
