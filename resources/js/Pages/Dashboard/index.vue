<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';

// Page Data
const page = usePage();
const dashboardData = computed(() => page.props);

// Computed Properties
const totalPersonas = computed(() => page.props.totalPersonas);
const totalPago = computed(() => {
    const amount = Number(page.props.totalPago) || 0;
    return amount >= 1e6 ? `${(amount / 1e6).toFixed(1)} M` :
        amount >= 1e3 ? `${(amount / 1e3).toFixed(1)} K` :
        `${amount.toFixed(2)}`;
});
const totalTutores = computed(() => page.props.totalTutores);
const totalHabilitado = computed(() => page.props.totalHabilitado);
const distribucionRegional = computed(() => page.props.distribucionRegional);

const discapacidadPorDistrito = computed(() => page.props.discapacidadPorDistrito);
const conteoEstados = computed(() => page.props.conteoEstados);
const conteoDiscapacidad = computed(() => page.props.conteoDiscapacidad);
const personasSinCarnet = computed(() => page.props.personasSinCarnet);
const notifiSinEnviar = computed(() => page.props.notifiSinEnviar);
const gestiones = computed(() => page.props.gestiones)

const notificacionTutor = computed(() => page.props.notificacionTutor);
const gestion = computed(() => page.props.gestion);

// Chart References
const disabilityChartRef = ref(null);
const paymentsChartRef = ref(null);
const genderDistributionRef = ref(null);
const disabilityByDistrictRef = ref(null);
const ageProgressionRef = ref(null);
const regionalDistributionRef = ref(null);
const paymentTrendsRef = ref(null);
const stackedBarChartRef = ref(null);
const areaChartRef = ref(null);
const radarChartRef = ref(null);

// Formatting Functions
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB'
    }).format(value);
};

function getMonthNameFromDate(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Convertir a número entero
    const index = parseInt(monthNumber, 10) - 1;

    // Validar que esté en rango 0–11
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

// Contadores para el resumen basados en ESTADO
const contarCompletados = computed(() => {
    return gestiones.value.filter(item => item.ESTADO === 1).length;
});

const contarEnProceso = computed(() => {
    return gestiones.value.filter(item => item.ESTADO === 2).length;
});

const contarPendientes = computed(() => {
    return gestiones.value.filter(item => item.ESTADO === 3).length;
});

// Chart Creation Functions
const createDisabilityChart = () => {
    if (!disabilityChartRef.value) return;

    // Destruir gráfico anterior si existe
    if (disabilityChartRef.value.chart) {
        disabilityChartRef.value.chart.destroy();
    }

    const ctx = disabilityChartRef.value.getContext('2d');

    // Datos limpios del backend
    const chartData = {
        fisica: conteoDiscapacidad.value ?.fisica || 0,
        auditiva: conteoDiscapacidad.value ?.auditiva || 0,
        intelectual: conteoDiscapacidad.value ?.intelectual || 0,
        mental_psiquica: conteoDiscapacidad.value ?.mental_psiquica || 0
    };

    disabilityChartRef.value.chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['FISICA-MOTORA', 'AUDITIVA', 'INTELECTUAL', 'MENTAL-PSIQUICA'],
            datasets: [{
                data: [
                    chartData.fisica,
                    chartData.auditiva,
                    chartData.intelectual,
                    chartData.mental_psiquica
                ],
                backgroundColor: [
                    '#5B8FF9', // Azul corporativo
                    '#7EC8E3', // Azul cielo claro
                    '#003366', // Azul oscuro
                    '#708090' // Gris pizarra
                ],
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#333',
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    bodyFont: {
                        family: "'Inter', sans-serif",
                        size: 12
                    },
                    boxPadding: 6,
                    displayColors: false
                }
            },
            animation: {
                animateScale: true,
                duration: 800
            }
        }
    });
};

const createGenderDistribution = () => {
    if (!genderDistributionRef.value) return;

    // Obtener los datos de conteoEstados desde las props
    const estadosData = conteoEstados.value;

    const ctx = genderDistributionRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Activos', 'Baja Temporal', 'Baja Definitiva'],
            datasets: [{
                // Mapear los valores del objeto al array en el orden correcto
                data: [
                    estadosData.activos || 0,
                    estadosData.baja_temporal || 0,
                    estadosData.baja_definitiva || 0
                ],
                backgroundColor: [
                    '#4682B4', // Azul acero (medio, versátil)
                    '#B0E0E6', // Azul polvo (claro y suave)
                    '#1E3F66', // Azul oscuro profundo (elegante)
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
};

const createRegionalDistribution = () => {
    if (!regionalDistributionRef.value) return;

    // Obtener los datos de conteoEstados desde las props
    const distribucionData = distribucionRegional.value;

    const ctx = regionalDistributionRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'AGUIRRE', 'CHIÑATA', 'LAVA LAVA', 'UCUCHI', 'PALCA'],
            datasets: [{
                data: [
                    distribucionData.d1 || 0,
                    distribucionData.d2 || 0,
                    distribucionData.d3 || 0,
                    distribucionData.d4 || 0,
                    distribucionData.d5 || 0,
                    distribucionData.d6 || 0,
                    distribucionData.d7 || 0,
                    distribucionData.aguirre || 0,
                    distribucionData.chinata || 0,
                    distribucionData.lava_lava || 0,
                    distribucionData.ucuchi || 0,
                    distribucionData.palca || 0
                ],
                backgroundColor: [
                    '#003366', // D-1 - Azul oscuro (más oscuro)
                    '#4A7BA7', // D-2 - Azul intermedio
                    '#5B8FF9', // D-3 - Azul corporativo
                    '#7EC8E3', // D-4 - Azul cielo claro
                    '#708090', // D-5 - Gris pizarra
                    '#003366', // D-6 - Azul oscuro
                    '#5B8FF9', // D-7 - Azul corporativo
                    '#003366', // AGUIRRE - Azul oscuro
                    '#4A7BA7', // CHIÑATA - Azul intermedio
                    '#5B8FF9', // LAVA LAVA - Azul corporativo
                    '#7EC8E3', // UCUCHI - Azul cielo claro
                    '#708090' // PALCA - Gris pizarra
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return '( ' + context.parsed.y + ' ) Beneficiarios registrados';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        font: {
                            weight: 'bold',
                            size: 12
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: {
                            weight: 'bold',
                            size: 12
                        }
                    }
                }
            }
        }
    });
};

const createDisabilityByDistrict = () => {
    if (!disabilityByDistrictRef.value) return;

    // Destruir gráfico anterior si existe
    if (disabilityByDistrictRef.value.chart) {
        disabilityByDistrictRef.value.chart.destroy();
    }

    // Ordenar distritos en el orden deseado
    const ordenDistritos = ['D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'AGUIRRE', 'CHIÑATA', 'LAVA LAVA', 'UCUCHI', 'PALCA'];

    // Crear un mapa para acceso rápido a los datos
    const dataMap = {};
    discapacidadPorDistrito.value.forEach(item => {
        dataMap[item.distrito] = item;
    });

    // Extraer datos en el orden correcto
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
                {
                    label: 'Física-Motora',
                    data: fisicaData,
                    backgroundColor: '#5B8FF9'
                },
                {
                    label: 'Auditiva',
                    data: auditivaData,
                    backgroundColor: '#7EC8E3'
                },
                {
                    label: 'Intelectual',
                    data: intelectualData,
                    backgroundColor: '#003366'
                },
                {
                    label: 'Mental-Psíquica',
                    data: mentalData,
                    backgroundColor: '#708090'
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.2,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 11
                        },
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.dataset.label + ': ' + context.parsed.x + ' personas';
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        font: {
                            weight: 'bold',
                            size: 10
                        }
                    }
                },
                y: {
                    stacked: true,
                    ticks: {
                        font: {
                            weight: 'bold',
                            size: 10
                        }
                    }
                }
            }
        }
    });
};

const createPaymentTrends = () => {
    if (!paymentTrendsRef.value) return;

    const ctx = paymentTrendsRef.value.getContext('2d');
    const data = [850000, 920000, 880000, 950000, 1020000, 980000];

    // Calcular métricas adicionales
    const currentMonth = data[data.length - 1];
    const previousMonth = data[data.length - 2];
    const percentageChange = ((currentMonth - previousMonth) / previousMonth * 100).toFixed(1);
    const average = data.reduce((a, b) => a + b, 0) / data.length;

    // Variables con datos ficticios para mostrar
    const currentMonthDisplay = 980000;
    const averageDisplay = 933333;
    const percentageChangeDisplay = -3.9;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Pagos Realizados',
                data: data,
                borderColor: '#4682B4',
                backgroundColor: 'rgba(70, 130, 180, 0.1)',
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
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return 'Pagos: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => formatCurrency(value)
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
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

const getStatusText = (estado) => {
    switch (estado) {
        case 'completado':
            return 'Completado';
        case 'pagado':
            return 'Pagado';
        case 'pendiente':
            return 'Pendiente';
        case 'proceso':
            return 'En Proceso';
        default:
            return 'N/A';
    }
};

const getPendingCount = () => {
    return gestion.value.filter(item =>
        item.estado === 'pendiente' || item.estado === 'proceso'
    ).length;
};

const createStackedBarChart = () => {
    if (!stackedBarChartRef.value) return;

    const ctx = stackedBarChartRef.value.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                    label: 'Pagos Aprobados',
                    data: [500000, 600000, 700000, 800000, 900000, 1000000],
                    backgroundColor: '#10B981'
                },
                {
                    label: 'Pagos Pendientes',
                    data: [200000, 300000, 400000, 500000, 600000, 700000],
                    backgroundColor: '#F59E0B'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: value => formatCurrency(value)
                    }
                }
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
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
};

// Lifecycle Hooks
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
<Head title="UMADIS " />
<div class="flex h-screen bg-gray-200 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col">
        <Header class="mb-0" />
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 mt-2">
            <!-- Quick Stats Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-5">

                <!-- Total Beneficiarios -->
                <div class="bg-white rounded-xl shadow-sm p-6 py-4  border border-gray-300 dark:bg-gray-800">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-gray-700 dark:text-gray-50 text-sm font-medium">Total Beneficiarios</h3>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-500 mt-2">
                                {{ totalPersonas }} </p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-500 text-sm font-medium">+2.5%</span>
                                <span class="text-gray-500 text-sm ml-2">vs mes anterior</span>
                            </div>
                        </div>
                        <div class="bg-blue-200 dark:bg-blue-500 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-black" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Tutores -->
                <div class="bg-white rounded-xl shadow-sm p-6 py-4 border border-gray-300 dark:bg-gray-800">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-gray-700 dark:text-gray-50 text-sm font-medium">Total Tutores</h3>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-500 mt-2">{{ totalTutores }}</p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-500 text-sm font-medium">+1.2%</span>
                                <span class="text-gray-500 text-sm ml-2">vs mes anterior</span>
                            </div>
                        </div>
                        <div class="bg-blue-200 dark:bg-blue-500 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-blue-800 dark:text-black" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Monto Total -->
                <div class="bg-white rounded-xl shadow-sm p-6 py-4 border border-gray-300 dark:bg-gray-800">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-gray-700 dark:text-gray-50 text-sm font-medium">Monto Total Pagado</h3>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-500 mt-2">Bs. {{ totalPago }}</p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-500 text-sm font-medium">+4.3%</span>
                                <span class="text-gray-500 text-sm ml-2">vs mes anterior</span>
                            </div>
                        </div>
                        <div class="bg-green-200 dark:bg-green-500 p-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Beneficiarios sin Carnet -->
                <div class="bg-white rounded-xl shadow-sm p-6 py-4 border border-gray-300 dark:bg-gray-800">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-gray-700 dark:text-gray-50 text-sm font-medium">Carnet de Discapacidad</h3>
                            <p class="text-3xl font-bold mt-2" :class="personasSinCarnet > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ personasSinCarnet }}
                            </p>
                            <div class="flex items-center mt-2">
                                <span class="text-gray-500  text-sm">
                                    {{ personasSinCarnet > 0 ? 'Sin carnet registrado' : 'Todos con carnet' }}
                                </span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg" :class="personasSinCarnet > 0 ? 'bg-red-200 dark:bg-red-500' : 'bg-green-200 dark:bg-green-500'">
                            <!-- Icono cuando hay personas sin carnet -->
                            <svg v-if="personasSinCarnet > 0" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <!-- Icono cuando todos tienen carnet (check) -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Tipos de Discapacidad -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 dark:bg-gray-800">
                    <h3 class="text-gray-800 dark:text-gray-50 text-center font-semibold text-lg mb-4">Tipos de Discapacidad</h3>
                    <canvas ref="disabilityChartRef" class="w-full"></canvas>
                </div>

                <!-- Distribución por estados -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 dark:bg-gray-800">
                    <h3 class="text-gray-800 dark:text-gray-50 text-center font-semibold text-lg mb-4">Distribución por Estados</h3>
                    <canvas ref="genderDistributionRef" class="w-full"></canvas>
                </div>
                <!-- En tu template -->

                <!-- Resumen Mensual -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-gray-800 font-semibold text-lg">Meses de Pago</h3>
                    </div>

                    <div class="space-y-3 mt-6 max-h-[300px] overflow-y-auto">
                        <div v-for="(item, index) in gestiones" :key="index" class="flex items-center justify-between p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="bg-blue-100 p-3 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: #1E3F66" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ item.GESTION }} </h4>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium"><span class="text-blue-700">{{ item.CANTIDAD_PERSONAS.toLocaleString() }}</span></span> habilitados
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-gray-800">Bs. {{ item.TOTAL_PAGADO.toLocaleString() }}</p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <!-- Estado basado en el campo ESTADO: 0=Pendiente, 1=Completado, 2=En Proceso -->
                                    <span v-if="item.ESTADO === 1" class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Completado
                                    </span>
                                    <span v-else-if="item.ESTADO === 2" class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        En Proceso
                                    </span>
                                    <span v-else class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen rápido al final -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Completado: <span class="font-medium text-green-800">{{ contarCompletados }}</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">En Proceso: <span class="font-medium text-blue-400">{{ contarEnProceso }}</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Pendientes: <span class="font-medium text-yellow-400">{{ contarPendientes }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 dark:bg-gray-800">
                    <h3 class="text-gray-800 dark:text-gray-50 text-center font-semibold text-lg mb-4">Tipos de Discapacidad por Distrito</h3>
                    <canvas ref="disabilityByDistrictRef"></canvas>
                </div>
                <!-- Additional Charts Grid -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-gray-800 font-semibold text-lg">Tendencia de Pagos</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500">vs mes anterior</span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                -3.9%
                            </span>
                        </div>
                    </div>

                    <canvas ref="paymentTrendsRef" class="w-full h-48 mb-4"></canvas>

                    <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Este Mes</p>
                            <p class="text-lg font-semibold text-gray-800">Bs. 980,000</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Promedio</p>
                            <p class="text-lg font-semibold text-gray-800">Bs. 933,333</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Variación</p>
                            <p class="text-lg font-semibold text-red-600">
                                -3.9%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 mb-6">
                <!-- Distribución Regional -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-300">
                    <h3 class="text-gray-800 font-semibold text-lg mb-4">Beneficiarios por Distrito</h3>
                    <canvas ref="regionalDistributionRef" class="w-full h-0"></canvas>
                </div>
                <!-- Progreso de Gestión -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm p-6 border border-gray-300 h-full flex flex-col">
                    <h3 class="text-gray-800 text-center font-semibold text-lg mb-6">Progreso Mensual</h3>
                    <div class="space-y-6 flex-1 flex flex-col justify-center">

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700 font-medium">Beneficiarios nuevos</span>
                                <span class="text-gray-900 font-bold text-lg">85%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 shadow-inner">
                                <div class="bg-gradient-to-r from-[#4682B4] to-[#5D9CDB] h-3 rounded-full transition-all duration-500 ease-out relative" style="width: 85%">
                                    <div class="absolute inset-0 bg-white opacity-20 rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700 font-medium">Pagos procesados</span>
                                <span class="text-gray-900 font-bold text-lg">92%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 shadow-inner">
                                <div class="bg-gradient-to-r from-[#5D9B9B] to-[#7BB5B5] h-3 rounded-full transition-all duration-500 ease-out relative" style="width: 92%">
                                    <div class="absolute inset-0 bg-white opacity-20 rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700 font-medium">Notificaciones enviadas</span>
                                <span class="text-gray-900 font-bold text-lg">78%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 shadow-inner">
                                <div class="bg-gradient-to-r from-[#B0E0E6] to-[#87CEEB] h-3 rounded-full transition-all duration-500 ease-out relative" style="width: 78%">
                                    <div class="absolute inset-0 bg-white opacity-20 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Promedio general</p>
                            <p class="text-2xl font-bold text-gray-800">85%</p>
                        </div>
                    </div>
                </div>
            </div>


        </main>
    </div>
</div>
</template>
