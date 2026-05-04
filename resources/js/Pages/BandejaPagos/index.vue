<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';

// Componentes
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Mensajes from '@/components/Mensajes.vue';
import Sidebar from '@/components/Sidebar.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';
import { useReportePagosIndividualPDF } from '@/composables/useReportePagosIndividualPDF';
import { useReporteResumenPDF } from '@/composables/useReporteResumenPDF';
import { useReporteArqueoCajaPDF } from '@/composables/useReporteArqueoCajaPDF';

const { generarReporte: generarPDFIndividual } = useReportePagosIndividualPDF();
const { generarReporte: generarPDFResumen } = useReporteResumenPDF();

// Utilidades
import { can } from '@/lib/can';
import Dropdown from '@/components/Dropdown.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();
const cargando = ref(false);

const misPagosTodos = computed(() => page.props.misPagosTodos ?? []);
const resumenGeneral = computed(() => page.props.resumenGeneral);

// ============================================================================
// DATOS ESTÁTICOS
// ============================================================================

const gestiones = computed(() => page.props.gestiones || []);
const mesesNumeros = computed(() => page.props.mesesNumeros || []);

const mesesMap = [
    { text: 'Enero', value: '01' },
    { text: 'Febrero', value: '02' },
    { text: 'Marzo', value: '03' },
    { text: 'Abril', value: '04' },
    { text: 'Mayo', value: '05' },
    { text: 'Junio', value: '06' },
    { text: 'Julio', value: '07' },
    { text: 'Agosto', value: '08' },
    { text: 'Septiembre', value: '09' },
    { text: 'Octubre', value: '10' },
    { text: 'Noviembre', value: '11' },
    { text: 'Diciembre', value: '12' },
];

const gestionesOptions = computed(() =>
    gestiones.value.map(g => ({ text: g.anio, value: String(g.anio) }))
);

const mesesOptions = computed(() =>
    mesesNumeros.value.map(n =>
        mesesMap.find(m => m.value === String(n).padStart(2, '0'))
    ).filter(Boolean)
);

// ============================================================================
// REFS - FILTROS
// ============================================================================
const filters = computed(() => page.props.filters || {});
const filtroGestion = ref(filters.value.gestion || '');
const filtroMes = ref(filters.value.mes || '');
const filtroDesde = ref(filters.value.fecha_desde || '');
const filtroHasta = ref(filters.value.fecha_hasta || '');
const buscado = ref(!!filters.value.mes || !!filters.value.fecha_desde);

// ============================================================================
// COMPUTED - EXCLUSIVIDAD ENTRE BLOQUES
// ============================================================================
const rangoActivo = computed(() => !!filtroDesde.value);
const gestionMesActivo = computed(() => !!filtroGestion.value || !!filtroMes.value);

// ============================================================================
// REFS - TAB ACTIVO (solo admin)
// ============================================================================
// Inicializar tabActivo desde la URL
const tabActivo = ref(new URLSearchParams(window.location.search).get('tab') || 'resumen');

// Modificar cambiarTab para guardar en URL sin navegar
function cambiarTab(tab) {
    tabActivo.value = tab;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url.toString());
}

const hayFiltrosActivos = computed(() =>
    !!filtroGestion.value || !!filtroMes.value || !!filtroDesde.value
);

// ============================================================================
// FUNCIONES - FILTROS CON EXCLUSIVIDAD
// ============================================================================

// Limpiar rango cuando se toca gestión/mes
function seleccionarGestion(valor) {
    filtroGestion.value = valor;
    filtroMes.value = '';
    filtroDesde.value = '';   // ← limpia rango
    filtroHasta.value = '';
    buscado.value = false;
    cargando.value = true;

    router.get(route('bandeja.index'), {
        gestion_gestion: valor,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

function limpiarFiltros() {
    filtroGestion.value = '';
    filtroMes.value = '';
    filtroDesde.value = '';
    filtroHasta.value = '';
    buscado.value = false;
    cargando.value = true;

    router.get(route('bandeja.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

function seleccionarMes(valor) {
    filtroMes.value = valor;
    filtroDesde.value = '';     // ← limpia rango
    filtroHasta.value = '';
    buscado.value = true;
    cargando.value = true;

    if (filtroGestion.value && valor) {
        router.get(route('bandeja.index'), {
            gestion_gestion: filtroGestion.value,
            mes: valor,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { cargando.value = false },
        });
    }
}

// Limpiar gestión/mes cuando se toca el rango
function onRangoChange() {
    filtroGestion.value = '';   // ← limpia gestión y mes
    filtroMes.value = '';
    buscado.value = true;

    if (filtroDesde.value && filtroHasta.value) {
        cargando.value = true;
        router.get(route('bandeja.index'), {
            fecha_desde: filtroDesde.value,
            fecha_hasta: filtroHasta.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { cargando.value = false },
        });
    }
}

// ============================================================================
// COMPUTED - DATOS TABLA
// ============================================================================
const misPagos = computed(() => page.props.misPagos ?? []);
const montoMisPagos = computed(() => page.props.montoTotalMisPagos ?? 0);
const usuarioTienePagos = computed(() => page.props.usuarioTienePagos ?? false);
const totalesResumen = computed(() => page.props.totalesResumen || {});
const totalBeneficiarios = computed(() => totalesResumen.value.total_beneficiarios ?? 0);
const totalMonto = computed(() => totalesResumen.value.total_monto ?? 0);
const montoTotalIndividual = computed(() => montoMisPagos.value);
const cantidadMisPagos = computed(() => page.props.cantidadMisPagos ?? 0);
const pagosPorUsuario = computed(() => page.props.pagosPorUsuario ?? {});
const resumenGeneralData = computed(() => resumenGeneral.value?.data ?? resumenGeneral.value ?? []);
const pagosTodos = computed(() => page.props.pagosTodos?.data ?? []);

const mesLabel = computed(() =>
    mesesMap.find(m => m.value === filtroMes.value)?.text ?? 'Sin seleccionar'
);

const hayDatosParaReporte = computed(() => {
    if (tabActivo.value === 'general') return false;

    if (can('acciones-superusuario') && tabActivo.value === 'resumen') {
        return resumenGeneral.value?.length > 0 && buscado.value;
    }
    return misPagosTodos.value?.length > 0;
});

// ============================================================================
// COLUMNAS DE TABLAS
// ============================================================================
const columnasIndividual = [
    { key: 'nro', label: 'Nro', headerClass: 'text-center' },
    { key: 'nombre_boleta', label: 'Nro Boleta' },
    { key: 'nombre_completo', label: 'Nombre Completo' },
    { key: 'ci_persona', label: 'C.I.' },
    { key: 'distrito', label: 'Distrito' },
    { key: 'mes_pago', label: 'Mes de Pago' },
    { key: 'monto', label: 'Monto Pago (Bs)' },
    { key: 'fecha_pago', label: 'Fecha de Pago' },
];

const columnasGeneral = [
    { key: 'nro', label: 'N°', headerClass: 'text-center' },
    { key: 'numero_boleta', label: 'N° Boleta', headerClass: 'text-center' },
    { key: 'nombre_completo', label: 'Nombre Completo' },
    { key: 'ci_persona', label: 'C.I.' },
    { key: 'distrito', label: 'Distrito' },
    { key: 'fecha_pago', label: 'Fecha de Pago' },
    { key: 'usuario_pagador', label: 'Usuario Pagador', headerClass: 'text-center' },
];

const columnasResumen = [
    { key: 'nro', label: 'N°', headerClass: 'text-center' },
    { key: 'nombre_usuario', label: 'Nombre Completo del Usuario'},
    { key: 'cantidad', label: 'Cantidad Beneficiarios', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Cant. Beneficiarios' },
    { key: 'monto_unitario', label: 'Monto Unitario (Bs)', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Monto Unitario' },
    { key: 'total', label: 'Total Monto (Bs)', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Total Monto' },
    { key: 'acciones', label: 'Acciones', headerClass: 'text-center' },
];

// ============================================================================
// REFS - MENSAJES
// ============================================================================
const mensajes = ref([]);

// ============================================================================
// FUNCIONES - ACCIONES
// ============================================================================
function aplicarFiltros() {
    if (filtroGestion.value && filtroMes.value) {
        router.get(route('bandeja.index'), {
            gestion_gestion: filtroGestion.value,
            mes: filtroMes.value,
        }, { preserveState: true, preserveScroll: true });
    }
}

const { generarReporte: generarPDFArqueo } = useReporteArqueoCajaPDF(
    () => ({
        cantidad_total_pagos: cantidadMisPagos.value,
        cantidad_no_pagados: 0,
        monto: misPagosTodos.value?.[0]?.monto ?? 0,
        presupuesto: montoTotalIndividual.value,  // ← computed correcto
        gestion: filtroGestion.value,
        mes: filtroMes.value,
    }),
    () => `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`
);

function generarArqueo() {
    registrarLog({
        tipo: 'individual',
        gestion: filtroGestion.value,
        mes: mesLabel.value,
        usuario: `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`,
        total_pagos: cantidadMisPagos.value,
    });
    generarPDFArqueo();
}

// Función helper — agrégala en el script
function registrarLog(params) {
    fetch(route('bandeja.reporteLog') + '?' + new URLSearchParams(params).toString(), {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
}

const montoTotalGeneral = computed(() =>
    pagosTodos.value.reduce((sum, p) => sum + (parseFloat(p.monto) || 0), 0)
);

function generarPDFDeUsuario(item) {
    // Tomar los pagos de este usuario desde el prop indexado por su id
    const datosPagos = pagosPorUsuario.value[item.id] ?? [];

    // Calcular su monto total real
    const montoTotal = datosPagos.reduce(
        (sum, p) => sum + (parseFloat(p.monto) || 0), 0
    );

    generarPDFIndividual(
        datosPagos,
        filtroGestion.value,
        filtroMes.value,
        `${item.nombre} ${item.apellido}`,
        'ResumenUsuario',
        filtroDesde.value,
        filtroHasta.value,
    );
}

function generarReporte() {
    const filtrosBase = filtroDesde.value
        ? { fecha_desde: filtroDesde.value, fecha_hasta: filtroHasta.value }
        : { gestion: filtroGestion.value, mes: mesLabel.value };

    if (can('acciones-superusuario') && tabActivo.value === 'resumen') {
        registrarLog({
            tipo: 'resumen',
            ...filtrosBase,
            total_beneficiarios: totalBeneficiarios.value,
            total_monto: totalMonto.value,
        });
        generarPDFResumen(
            resumenGeneral.value,
            filtroGestion.value,
            filtroMes.value,
            page.props.auth.user.name,
            filtroDesde.value,
            filtroHasta.value,
        );
    } else {
        const nombreReporte = `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`;
        registrarLog({
            tipo: 'individual',
            ...filtrosBase,
            usuario: nombreReporte,
            total_pagos: misPagosTodos.value?.length ?? 0,
        });
        generarPDFIndividual(
            misPagosTodos.value,
            filtroGestion.value,
            filtroMes.value,
            nombreReporte,
            'MisPagos',
            filtroDesde.value,
            filtroHasta.value,
        );
    }
}
// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

function showTooltip(text, btnId) {
    const el = document.getElementById(btnId);
    if (!el) return;
    const rect = el.getBoundingClientRect();
    tooltipText.value = text;
    showTooltipFlag.value = true;
    tooltipStyle.value = {
        top: `${rect.bottom + 6}px`,
        left: `${rect.left + rect.width / 2}px`,
        transform: 'translateX(-50%)',
    };
}
function hideTooltip() { showTooltipFlag.value = false; }
</script>

<style scoped>
.filtro-enter-active,
.filtro-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.filtro-enter-from,
.filtro-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
</style>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex h-screen -ml-1 bg-gray-200 font-roboto">
        <Sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Mensajes -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido" />

            <Header class="mb-0" />

            <!-- Encabezado -->
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Bandeja de Pagos</h1>
                <Rutas label1="Inicio" label3="Bandeja de Pagos" class="sm:text-xs" />
            </div>

            <!-- ============================================================================ -->
            <!-- FILTROS + RESUMEN + BOTONES -->
            <!-- ============================================================================ -->
            <div class="px-2 pb-2 sm:p-3 bg-white border-x-2 border-t-2 border-gray-200 rounded-t-lg mr-1 shadow-sm">

                <!-- Etiqueta lateral — se oculta en pantallas chicas -->
                <div class="hidden lg:flex items-center gap-1.5 shrink-0">
                    <div class="w-1 h-5 rounded-full bg-[#13326A]"></div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-tight">Filtrar
                        por</span>
                </div>
                <!-- Fila principal: filtros + acciones -->
                <div class="sm:flex flex-wrap space-y-2 gap-2 mt-2">
                    <!-- ── Bloque Gestión + Mes ── -->
                    <Transition name="filtro">
                        <div v-if="!rangoActivo"
                            class="flex flex-wrap items-center gap-1.5 px-3 py-2 rounded-xl border border-blue-100 bg-blue-50/50 transition-all duration-200">

                            <!-- Gestión -->
                            <div class="flex flex-col gap-0.5">
                                <label
                                    class="text-[10px] font-bold text-blue-400 uppercase tracking-widest pl-1">Gestión</label>
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold rounded-lg transition-all w-36 sm:w-40 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="filtroGestion
                                                ? 'border-[#13326A] text-[#13326A]'
                                                : 'text-gray-600 hover:border-blue-300 hover:text-blue-700'"
                                            type="button">
                                            <span class="flex-1 text-left truncate">{{ filtroGestion || 'Gestión'
                                                }}</span>
                                            <svg class="w-2.5 h-2.5 shrink-0 opacity-60"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                                <path stroke="currentColor" stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div
                                            class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li v-if="!gestiones || gestiones.length === 0" class="px-4 py-3">
                                                    <div class="flex items-center gap-3 text-slate-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                        <span class="text-sm">No hay datos disponibles</span>
                                                    </div>
                                                </li>
                                                <li v-for="g in gestionesOptions" :key="g.value">
                                                    <a href="#" @click.prevent="seleccionarGestion(g.value)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-blue-50 duration-150 group"
                                                        :class="filtroGestion === g.value
                                                            ? 'text-slate-700'
                                                            : 'text-slate-700 hover:text-blue-700'">
                                                        <span class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            {{ g.text }}</span>
                                                        <svg v-if="filtroGestion === g.value" class="w-3.5 h-3.5"
                                                            fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <div class="hidden sm:block w-px h-8 bg-blue-400"></div>

                            <!-- Mes -->
                            <div class="flex flex-col gap-0.5">
                                <label class="text-[10px] font-bold uppercase tracking-widest pl-1"
                                    :class="filtroGestion ? 'text-blue-400' : 'text-gray-300'">Mes</label>
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold rounded-lg transition-all w-36 sm:w-40 bg-white border border-gray-200 shadow-sm"
                                            :class="filtroMes
                                                ? 'border-[#13326A] text-[#13326A] hover:bg-gray-50 hover:shadow'
                                                : filtroGestion && mesesOptions.length > 0
                                                    ? 'text-gray-600 hover:border-blue-300 hover:text-blue-700 hover:bg-gray-50 hover:shadow cursor-pointer'
                                                    : 'text-gray-400 cursor-not-allowed opacity-60'" type="button"
                                            :disabled="!filtroGestion">
                                            <span class="flex-1 text-left truncate">
                                                {{ mesLabel !== 'Sin seleccionar' ? mesLabel : 'Mes' }}
                                            </span>
                                            <svg class="w-2.5 h-2.5 shrink-0 opacity-60"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                                <path stroke="currentColor" stroke-linecap="round"
                                                    stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div
                                            class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li v-for="m in mesesOptions" :key="m.value">
                                                    <a href="#" @click.prevent="seleccionarMes(m.value)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-blue-50 duration-150 group"
                                                        :class="filtroMes === m.value
                                                            ? 'text-slate-700'
                                                            : 'text-slate-700 hover:text-blue-700'">
                                                        <span class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            {{ m.text }}</span>
                                                        <svg v-if="filtroMes === m.value" class="w-3.5 h-3.5"
                                                            fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </Transition>

                    <!-- ── Separador O — solo visible si ninguno está activo ── -->
                    <Transition name="filtro">
                        <div v-if="!hayFiltrosActivos" class="flex items-center justify-center shrink-0 w-full lg:w-auto">
                            <span
                                class="text-[10px] font-black text-gray-400 bg-gray-100 rounded-full w-5 h-5 flex items-center justify-center">O</span>
                        </div>
                    </Transition>

                    <!-- ── Bloque Rango de Fechas ── -->
                    <Transition name="filtro">
                        <div v-if="!gestionMesActivo"
                            class="flex flex-wrap items-center gap-1.5 px-3 py-2 rounded-xl border border-purple-100 bg-purple-50/40 transition-all duration-200">

                            <!-- Desde -->
                            <div class="flex flex-col gap-0.5">
                                <label
                                    class="text-[10px] font-bold text-purple-400 uppercase tracking-widest pl-1">Desde</label>
                                <input type="date" v-model="filtroDesde" @change="onRangoChange"
                                    class="border rounded-lg px-2.5 py-1.5 text-sm font-medium bg-white focus:outline-none focus:ring-2 focus:ring-purple-300 transition-all w-36 sm:w-40"
                                    :class="filtroDesde ? 'border-purple-300 text-purple-700' : 'border-gray-200 text-gray-600 hover:border-purple-300'" />
                            </div>

                            <div class="hidden sm:block w-px h-8 bg-purple-100"></div>

                            <!-- Hasta -->
                            <div class="flex flex-col gap-0.5">
                                <label
                                    class="text-[10px] font-bold text-purple-400 uppercase tracking-widest pl-1">Hasta</label>
                                <input type="date" v-model="filtroHasta" @change="onRangoChange"
                                    class="border rounded-lg px-2.5 py-1.5 text-sm font-medium bg-white focus:outline-none focus:ring-2 focus:ring-purple-300 transition-all w-36 sm:w-40"
                                    :class="filtroHasta ? 'border-purple-300 text-purple-700' : 'border-gray-200 text-gray-600 hover:border-purple-300'" />
                            </div>
                        </div>
                    </Transition>

                    <!-- ── Botón Limpiar ── -->
                   <button v-if="hayFiltrosActivos" @click="limpiarFiltros"
    class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-500 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 hover:border-red-300 hover:text-red-700 transition-all duration-200 w-full lg:w-auto h-fit lg:self-end lg:mb-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span class="hidden sm:inline">Limpiar</span>
                    </button>

                    <!-- ── Spacer + Cards + PDF (se van al final del wrap) ── -->
                    <div class="flex items-center gap-2 w-full lg:w-auto lg:ml-auto flex-wrap">

                        <!-- Cards de totales -->
                        <!-- Admin · Tab Resumen General -->
                        <template v-if="can('acciones-superusuario') && tabActivo === 'resumen'">
                            <div v-if="totalBeneficiarios > 0 && buscado"
                                class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Beneficiarios</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">{{
                                        totalBeneficiarios.toLocaleString('es-BO') }}</p>
                                </div>
                            </div>
                            <div v-if="totalMonto > 0 && buscado"
                                class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div
                                    class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="clipboardList" class-name="text-emerald-600"
                                        :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                        Monto Total</p>
                                    <p class="text-sm font-black text-emerald-700 leading-tight">
                                        {{ totalMonto.toLocaleString('es-BO') }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Tab Total Pagados — todos -->
                        <template v-else-if="tabActivo === 'general'">
                           <div v-if="pagosTodos.length > 0 && buscado"
        class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Total Pagos</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">
                                        {{ pagosTodos.length.toLocaleString('es-BO') }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="pagosTodos.length > 0 && buscado"
                                class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div
                                    class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="cash" class-name="text-emerald-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                        Monto Total</p>
                                    <p class="text-sm font-black text-emerald-700 leading-tight">
                                        {{ montoTotalGeneral.toLocaleString('es-BO') }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Cajero o admin en Tab Mis Pagos -->
                        <template v-else>
                            <div v-if="cantidadMisPagos > 0 && buscado"
                                class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Beneficiarios</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">{{ cantidadMisPagos }}</p>
                                </div>
                            </div>
                            <div v-if="montoTotalIndividual > 0"
                                class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div
                                    class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="cash" class-name="text-emerald-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                        Monto Total</p>
                                    <p class="text-sm font-black text-emerald-700 leading-tight">
                                        {{ montoTotalIndividual.toLocaleString('es-BO') }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Botón PDF -->
                        <Button v-if="hayDatosParaReporte" id="btn-reporte" @click.prevent="generarReporte()"
                            @mouseenter="showTooltip('Generar PDF', 'btn-reporte')" @mouseleave="hideTooltip"
                            :style="'px-3 py-3 pb-2 rounded-full border-none'"
                            class="bg-gray-100 shrink-0 relative overflow-hidden group">
                            <span
                                class="absolute inset-0 bg-red-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                            <span class="relative z-10">
                                <Icon :icon-button="true" name="filePDF" fill="currentColor"
                                    class-name="text-gray-500 group-hover:text-white transition-colors duration-500" />
                            </span>
                        </Button>
                        <Button
                            v-if="cantidadMisPagos > 0 && buscado && filtroGestion && filtroMes && tabActivo === 'mispagos'"
                            id="btn-arqueo" @click.prevent="generarArqueo()"
                            @mouseenter="showTooltip('Arqueo de Caja', 'btn-arqueo')" @mouseleave="hideTooltip"
                            :style="'px-3 py-3 pb-2 rounded-full border-none'"
                            class="bg-gray-100 shrink-0 relative overflow-hidden group">
                            <span
                                class="absolute inset-0 bg-blue-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                            <span class="relative z-10">
                                <Icon :icon-button="true" name="filePDF" fill="currentColor"
                                    class-name="text-gray-500 group-hover:text-white transition-colors duration-500" />
                            </span>
                        </Button>
                    </div>
                </div>

                <!-- Tooltip -->
                <div v-if="showTooltipFlag"
                    class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                    :style="tooltipStyle">
                    {{ tooltipText }}
                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- TABS — solo visibles para el admin -->
            <!-- ============================================================================ -->
            <div class="flex border-x-2 border-gray-200 bg-white mr-1">

                <button v-if="can('acciones-superusuario')" @click="cambiarTab('resumen')" :class="[
                    'flex items-center gap-2 px-5 py-1.5 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200',
                    tabActivo === 'resumen'
                        ? 'border-blue-500 text-blue-600 bg-blue-50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                ]">
                    <Icon :icon-button="true" name="clipboardList" :size="16"
                        :class-name="tabActivo === 'resumen' ? 'text-blue-500' : 'text-gray-400'" />
                    Resumen General
                </button>

                <button @click="cambiarTab('general')" :class="[
                    'flex items-center gap-2 px-5 py-1.5 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200',
                    tabActivo === 'general'
                        ? 'border-blue-500 text-blue-600 bg-blue-50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                ]">
                    <Icon :icon-button="true" name="clipboardList" :size="16"
                        :class-name="tabActivo === 'general' ? 'text-blue-500' : 'text-gray-400'" />
                    Total Pagados
                </button>

                <button v-if="usuarioTienePagos" @click="cambiarTab('mispagos')" :class="[
                    'flex items-center gap-2 px-5 py-1.5 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200',
                    tabActivo === 'mispagos'
                        ? 'border-blue-500 text-blue-600 bg-blue-50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                ]">
                    <Icon :icon-button="true" name="user" :size="16"
                        :class-name="tabActivo === 'mispagos' ? 'text-blue-500' : 'text-gray-400'" />
                    Mis Pagos
                </button>

            </div>

            <!-- ============================================================================ -->
            <!-- TABLA: RESUMEN GENERAL (admin · tab resumen) -->
            <!-- ============================================================================ -->
            <template v-if="can('acciones-superusuario') && tabActivo === 'resumen'">
                <DataTable :data="resumenGeneralData" :columns="columnasResumen" row-key="nro"
                    empty-message="No se encontraron datos para los filtros seleccionados.">
                    <template #row="{ item, index }">

                        <td class="px-3 py-1 whitespace-nowrap text-center">
                            <span class="text-gray-900 text-sm">{{ (resumenGeneral?.from || 1) + index }}</span>
                        </td>

                        <td class="px-1 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900 uppercase">{{ item.nombre }} {{ item.apellido }}</div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap text-center">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ item.cantidad_pagos }}
                            </span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap text-center">
                            <span class="text-gray-700 font-medium">{{ item.monto }} Bs</span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap text-center">
                            <span class="font-semibold text-green-700">
                                {{ Number(item.monto_total).toLocaleString('es-BO') }} Bs
                            </span>
                        </td>

                        <td class="px-3 py-1.5">
                            <div class="flex justify-center items-center gap-1"
                                :title="`Generar PDF de ${item.nombre_usuario}`">
                                <Icon @click.prevent="generarPDFDeUsuario(item)" name="filePDF" fill="currentColor"
                                    class-name="text-gray-700" />
                            </div>
                        </td>
                    </template>

                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-12 px-4">

                            <template v-if="cargando">
                                <div class="mb-4">
                                    <svg class="animate-spin w-12 h-12 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-600">Cargando datos...</p>
                            </template>

                            <template v-else>
                                <div class="mb-4">
                                    <Icon :icon-button="true" :name="buscado ? 'clipboardList' : 'calendarMont'"
                                        :fill="buscado ? 'currentColor' : 'none'"
                                        :stroke="buscado ? 'none' : 'currentColor'" class-name="text-gray-300"
                                        :size="64" :height="64" />
                                </div>
                                <div class="text-center space-y-1 max-w-md">
                                    <h3 class="text-lg font-semibold text-gray-700">
                                        {{ buscado ? 'Sin registros' : filtroGestion ?
                                            'Seleccione un mes' : 'Seleccione una gestión y mes, o un rango de fechas' }}
                                    </h3>
                                    <p class="text-sm text-gray-400">
                                        {{
                                            buscado
                                                ? (filtroDesde && filtroHasta
                                                    ? `No se realizaron pagos entre ${filtroDesde} y ${filtroHasta}.`
                                                    : `No se realizaron pagos en ${mesLabel} de ${filtroGestion}.`)
                                                : filtroGestion
                                                    ? `Elija el mes para ver los pagos de ${filtroGestion}.`
                                                    : 'Elija el año y mes, o un rango de fechas para ver el resumen.'
                                        }}
                                    </p>
                                </div>
                            </template>

                        </div>
                    </template>
                </DataTable>
            </template>

            <!-- ============================================================================ -->
            <!-- TABLA: INDIVIDUAL — cajero normal o admin en tab "Mis Pagos" -->
            <!-- ============================================================================ -->
            <template
                v-else-if="tabActivo === 'mispagos' || (!can('acciones-superusuario') && tabActivo !== 'general')">
                <DataTable :data="misPagos.data ?? []" :columns="columnasIndividual" row-key="ci_persona"
                    empty-message="No se encontraron pagos para los filtros seleccionados.">
                    <template #row="{ item, index }">

                        <td class="px-3 py-1 whitespace-nowrap text-center">
                            <span class="text-gray-900 text-sm">{{ (misPagos.from ?? 1) + index }}</span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900 uppercase">
                                {{ item.numero_boleta }}
                            </div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900 uppercase">
                                {{ item.nombre_persona }} {{ item.apellido_persona }}
                            </div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ item.ci_persona }}</div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <span v-if="item.distrito" class="text-gray-700">{{ item.distrito }}</span>
                            <span v-else class="text-red-500 italic text-xs">Sin datos</span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ String(item.mes).padStart(2, '0') }}/{{ item.gestion }}
                            </span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <span class="font-semibold text-green-700">
                                {{ Number(item.monto).toLocaleString('es-BO') }} Bs
                            </span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="text-gray-600 text-sm">{{ item.fecha_pago }}</div>
                        </td>

                    </template>

                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-12 px-4">
                            <div class="mb-4">
                                <Icon :icon-button="true" :name="buscado ? 'clipboardList' : 'calendarMont'"
                                    :fill="buscado ? 'currentColor' : 'none'"
                                    :stroke="buscado ? 'none' : 'currentColor'" class-name="text-gray-300" :size="64"
                                    :height="64" />
                            </div>
                            <div class="text-center space-y-1 max-w-md">
                                <h3 class="text-lg font-semibold text-gray-700">
                                    {{ buscado ? 'Sin pagos registrados' : filtroGestion ? 'Seleccione un mes' :
                                        'Seleccione una gestión y mes' }}
                                </h3>
                                <p class="text-sm text-gray-400">
                                    {{
                                        buscado
                                            ? (filtroDesde && filtroHasta
                                                ? `No realizaste pagos entre ${filtroDesde} y ${filtroHasta}.`
                                                : `No realizaste pagos en ${mesLabel} de ${filtroGestion}.`)
                                            : filtroGestion
                                                ? `Elija el mes para ver tus pagos de ${filtroGestion}.`
                                                : 'Elija el año y mes, o un rango de fechas para ver tus pagos.'
                                    }}
                                </p>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </template>

            <!-- ============================================================================ -->
            <!-- TABLA: TOTAL PAGADOS — visible para todos -->
            <!-- ============================================================================ -->
            <template v-if="tabActivo === 'general'">
                <DataTable :data="pagosTodos" :columns="columnasGeneral" row-key="numero_boleta"
                    empty-message="No se encontraron pagos para los filtros seleccionados.">
                    <template #row="{ item, index }">

                        <td class="px-3 py-1 whitespace-nowrap text-center">
                            <span class="text-gray-900 text-sm">{{ index + 1 }}</span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900 uppercase">{{ item.numero_boleta }}</div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900 uppercase">
                                {{ item.nombre_persona }} {{ item.apellido_persona }}
                            </div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="font-medium text-gray-900">{{ item.ci_persona }}</div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <span v-if="item.distrito" class="text-gray-700">{{ item.distrito }}</span>
                            <span v-else class="text-red-500 italic text-xs">Sin datos</span>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="text-gray-600 text-sm">{{ item.fecha_pago }}</div>
                        </td>

                        <td class="px-3 py-1 whitespace-nowrap">
                            <div class="text-gray-700 capitalize text-sm">{{ item.usuario_pagador?.toLowerCase() }}
                            </div>
                        </td>

                    </template>

                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-12 px-4">
                            <div class="mb-4">
                                <Icon :icon-button="true" :name="buscado ? 'clipboardList' : 'calendarMont'"
                                    :fill="buscado ? 'currentColor' : 'none'"
                                    :stroke="buscado ? 'none' : 'currentColor'" class-name="text-gray-300" :size="64"
                                    :height="64" />
                            </div>
                            <div class="text-center space-y-1 max-w-md">
                                <h3 class="text-lg font-semibold text-gray-700">
                                    {{ buscado ? 'Sin pagos registrados' : filtroGestion ? 'Seleccione un mes' :
                                        'Seleccione una gestión y mes' }}
                                </h3>
                                <p class="text-sm text-gray-400">
                                    {{ buscado
                                        ? 'No se encontraron pagos para los filtros seleccionados.'
                                        : filtroGestion
                                            ? `Elija el mes para ver los pagos de ${filtroGestion}.`
                                            : 'Elija el año y mes, o un rango de fechas para ver los pagos.' }}
                                </p>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </template>

            <!-- Footer -->
            <div>
                <Paginacion v-if="tabActivo === 'mispagos' && misPagos?.last_page > 1" :links="misPagos.links"
                    :from="misPagos.from" :to="misPagos.to" :total="misPagos.total" />
                <Paginacion v-if="tabActivo === 'general' && page.props.pagosTodos?.last_page > 1"
                    :links="page.props.pagosTodos.links" :from="page.props.pagosTodos.from"
                    :to="page.props.pagosTodos.to" :total="page.props.pagosTodos.total" />
                <Paginacion v-if="tabActivo === 'resumen' && resumenGeneral?.last_page > 1"
                    :links="resumenGeneral.links" :from="resumenGeneral.from" :to="resumenGeneral.to"
                    :total="resumenGeneral.total" />
                <Footer />
            </div>

        </div>
    </div>
</template>
