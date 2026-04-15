<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';
import Icon from '@/components/Icon.vue';

// Page Data
const page = usePage();
const dashboardData = computed(() => page.props);

// Computed Properties
const totalPersonas = computed(() => page.props.totalPersonas);
const totalBeneficiarios = computed(() => page.props.totalBeneficiarios);
const totalBajaTemp = computed(() => page.props.totalBajaTemp);
const totalBajaDef = computed(() => page.props.totalBajaDef);
const carnetVencido = computed(() => page.props.carnetVencido);

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
const registros = computed(() => page.props.registros);

const notificacionTutor = computed(() => page.props.notificacionTutor);
const gestion = computed(() => page.props.gestion);
const gestionActual = computed(() => page.props.gestionActual);

console.log('gestion', gestion.value)
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

// Gestión seleccionada en el dropdown
// Trae la gestión actual desde las props de Inertia

// Inicializa con la gestión actual en lugar de cadena vacía
// El valor inicial SIEMPRE es la gestión actual, nunca vacío
// ✅ Solo esto, directo desde page.props
const gestionSeleccionada = ref(page.props.gestionActual)

const gestiones = computed(() =>
    [...new Set(registros.value.map(i => i.GESTION))]
)

const mesesFiltrados = computed(() =>
    gestionSeleccionada.value
        ? registros.value.filter(i => i.GESTION === gestionSeleccionada.value)
        : []
)

const totalHabilitados = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + i.CANTIDAD_HABILITADOS, 0)
)

const totalPagos = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + i.CANTIDAD_PAGOS, 0)
)

const totalGeneral = computed(() =>
    mesesFiltrados.value.reduce((sum, i) => sum + i.TOTAL, 0)
)

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

    // Destruir gráfico anterior si existe
    if (disabilityChartRef.value.chart) {
        disabilityChartRef.value.chart.destroy();
    }

    const ctx = disabilityChartRef.value.getContext('2d');

    // Datos limpios del backend
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

                <!-- FILA 1 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-5">

                    <!-- Total Beneficiarios -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <!-- degradado sutil fondo -->
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent dark:from-blue-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Total Beneficiarios
                                </h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalBeneficiarios
                                    }}</p>
                                <p class="text-xs text-blue-500 font-medium mt-1">Beneficiarios activos</p>
                            </div>
                            <div class="bg-blue-500 px-2.5 pt-2 pb-0.5 rounded-lg shrink-0">
                                <Icon :icon-button="true" name="user" class-name="text-white" :size="20" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Baja Temporal -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-orange-50 to-transparent dark:from-orange-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Baja Temporal</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalBajaTemp }}</p>
                                <p class="text-xs text-orange-500 font-medium mt-1">Suspendidos temporalmente</p>
                            </div>
                            <div class="bg-orange-400 px-2.5 pt-2 pb-0.5 rounded-lg">
                                <Icon :icon-button="true" name="timeCircle" class-name="text-white" :size="20" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Baja Definitiva -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-red-50 to-transparent dark:from-red-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Baja Definitiva</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalBajaDef }}</p>
                                <p class="text-xs text-red-500 font-medium mt-1">Dados de baja</p>
                            </div>
                            <div class="bg-red-500 px-2.5 pt-2 pb-0.5 rounded-lg shrink-0">
                                <Icon :icon-button="true" name="circleMinus" class-name="text-white" :size="20" />
                            </div>
                        </div>
                    </div>

                    <!-- Carnet Discapacidad Vencido -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-yellow-50 to-transparent dark:from-yellow-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Carnet Dis. Vencido
                                </h3>
                                <p class="text-2xl font-bold text-yellow-600 mt-1">{{ carnetVencido }}</p>
                                <p class="text-xs text-yellow-500 font-medium mt-1">Requieren renovación</p>
                            </div>
                            <div class="bg-yellow-400 px-2.5 pt-2 pb-0.5 rounded-lg shrink-0">
                                <Icon :icon-button="true" name="profileCard" class-name="text-white" :size="20" />
                            </div>
                        </div>
                    </div>
                </div>


                <!-- FILA 2 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-5">

                    <!-- Total Registros -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-sky-50 to-transparent dark:from-sky-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Total Registros</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalPersonas }}</p>
                                <p class="text-xs text-sky-500 font-medium mt-1">Personas registradas</p>
                            </div>
                            <div class="bg-sky-500 px-2.5 pt-2 pb-0.5 rounded-lg shrink-0">
                                <Icon :icon-button="true" name="user" class-name="text-white" :size="20" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Tutores -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent dark:from-blue-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Total Tutores</h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ totalTutores }}</p>
                                <p class="text-xs text-blue-700 dark:text-blue-400 font-medium mt-1">Tutores registrados
                                </p>
                            </div>
                            <div class="bg-blue-700 px-2.5 pt-2 pb-0.5 rounded-lg shrink-0">
                                <Icon :icon-button="true" name="users" class-name="text-white" :size="20" />
                            </div>
                        </div>
                    </div>

                    <!-- Monto Total Pagado -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-green-50 to-transparent dark:from-green-900/10 dark:to-transparent pointer-events-none">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Monto Total Pagado
                                </h3>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 truncate">Bs. {{
                                    totalPago }}</p>
                                <p class="text-xs text-green-600 font-medium mt-1">Total acumulado</p>
                            </div>
                            <div class="bg-green-500 p-2.5 rounded-lg shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Carnet de Discapacidad sin carnet -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 py-4 border border-gray-200 dark:border-gray-700
                relative overflow-hidden transition-shadow hover:shadow-md">
                        <div class="absolute inset-0 pointer-events-none"
                            :class="personasSinCarnet > 0
                                ? 'bg-gradient-to-br from-red-50 to-transparent dark:from-red-900/10 dark:to-transparent'
                                : 'bg-gradient-to-br from-green-50 to-transparent dark:from-green-900/10 dark:to-transparent'">
                        </div>
                        <div class="relative flex justify-between items-start">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Carnet Discapacidad
                                </h3>
                                <p class="text-2xl font-bold mt-1"
                                    :class="personasSinCarnet > 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ personasSinCarnet }}
                                </p>
                                <p class="text-xs font-medium mt-1"
                                    :class="personasSinCarnet > 0 ? 'text-red-500' : 'text-green-600'">
                                    {{ personasSinCarnet > 0 ? 'Sin carnet registrado' : 'Todos con carnet' }}
                                </p>
                            </div>
                            <div class="p-2.5 rounded-lg shrink-0"
                                :class="personasSinCarnet > 0 ? 'bg-red-500' : 'bg-green-500'">
                                <svg v-if="personasSinCarnet > 0" xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Tipos de Discapacidad -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 dark:bg-gray-800">
                        <h3 class="text-gray-800 dark:text-gray-50 text-center font-semibold text-lg mb-4">Tipos de
                            Discapacidad</h3>
                        <canvas ref="disabilityChartRef" class="w-full"></canvas>
                    </div>

                    <!-- Distribución por estados -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 dark:bg-gray-800">
                        <h3 class="text-gray-800 dark:text-gray-50 text-center font-semibold text-lg mb-4">Distribución
                            por Estados</h3>
                        <canvas ref="genderDistributionRef" class="w-full"></canvas>
                    </div>
                    <!-- En tu template -->

                    <!-- Resumen Mensual -->
                    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-300 flex flex-col">

                        <!-- Header + Dropdown -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-800 font-semibold text-base">Meses de Pago</h3>
                            <select v-model="gestionSeleccionada"
                                class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 text-gray-600 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-200 cursor-pointer">
                                <option v-for="g in gestiones" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>

                        <!-- Lista de meses (crece) -->
                        <div class="flex-1 space-y-2 max-h-[500px] overflow-y-auto pr-1">

                            <!-- Sin resultados -->
                            <div v-if="mesesFiltrados.length === 0"
                                class="flex flex-col items-center justify-center py-8 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 text-gray-300" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs">Sin datos disponibles</p>
                            </div>

                            <!-- Fila por mes -->
                            <div v-for="(item, index) in mesesFiltrados" :key="index"
                                class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors border border-transparent hover:border-blue-100">

                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 p-2 rounded-lg shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" style="color: #1E3F66"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 leading-tight">{{
                                            getMonthNameFromNumber(item.MES) }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-gray-400">
                                                Hab: <span class="font-semibold text-blue-600">{{
                                                    item.CANTIDAD_HABILITADOS }}</span>
                                            </span>
                                            <span class="text-gray-200 text-xs">|</span>
                                            <span class="text-xs text-gray-400">
                                                Pagos: <span class="font-semibold text-indigo-500">{{
                                                    item.CANTIDAD_PAGOS }}</span>
                                            </span>
                                            <span class="text-gray-200 text-xs">|</span>
                                            <span class="text-xs text-gray-400">
                                                Bene: <span class="font-semibold text-indigo-500">{{
                                                    totalBeneficiarios }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 ml-2">
                                    <p class="text-sm font-bold text-gray-800">Bs. {{ item.TOTAL.toLocaleString() }}</p>
                                    <p class="text-xs text-gray-400">c/u Bs. {{ item.MONTO.toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer siempre visible -->
                        <div class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-xs text-center">
                            <div class="bg-blue-50 rounded-lg py-1.5">
                                <p class="text-gray-400">Habilitados</p>
                                <p class="font-bold text-blue-700">{{ totalHabilitados }}</p>
                            </div>
                            <div class="bg-indigo-50 rounded-lg py-1.5">
                                <p class="text-gray-400">Pagos</p>
                                <p class="font-bold text-indigo-600">{{ totalPagos }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg py-1.5 border border-gray-100">
                                <p class="text-gray-400">Total</p>
                                <p class="font-bold text-gray-700">Bs. {{ totalGeneral.toLocaleString() }}</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 dark:bg-gray-800">
                        <h3 class="text-gray-800 dark:text-gray-50 text-center font-semibold text-lg mb-4">Tipos de
                            Discapacidad por Distrito</h3>
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
                    <div
                        class="lg:col-span-1 bg-white rounded-xl shadow-sm p-6 border border-gray-300 h-full flex flex-col">
                        <h3 class="text-gray-800 text-center font-semibold text-lg mb-6">Progreso Mensual</h3>
                        <div class="space-y-6 flex-1 flex flex-col justify-center">

                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-700 font-medium">Beneficiarios nuevos</span>
                                    <span class="text-gray-900 font-bold text-lg">85%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 shadow-inner">
                                    <div class="bg-gradient-to-r from-[#4682B4] to-[#5D9CDB] h-3 rounded-full transition-all duration-500 ease-out relative"
                                        style="width: 85%">
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
                                    <div class="bg-gradient-to-r from-[#5D9B9B] to-[#7BB5B5] h-3 rounded-full transition-all duration-500 ease-out relative"
                                        style="width: 92%">
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
                                    <div class="bg-gradient-to-r from-[#B0E0E6] to-[#87CEEB] h-3 rounded-full transition-all duration-500 ease-out relative"
                                        style="width: 78%">
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
