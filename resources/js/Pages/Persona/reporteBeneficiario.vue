<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import Rutas from '@/components/Rutas.vue';
import Dropdown from '@/components/Dropdown.vue';
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Icon from '@/components/Icon.vue';
import Button from '@/components/Button.vue';

/**
 * Utilidades
 */
import { useReporteBeneficiarioPDF } from '@/composables/useReporteBeneficiarioPDF';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const { generarReporte } = useReporteBeneficiarioPDF();

const resultados = computed(() => page.props.resultados);
const resultadosReporte = computed(() => page.props.resultadosReporte);
const gestiones = computed(() => page.props.gestiones);
const mesesNumeros = computed(() => page.props.mesesNumeros);
const filters = computed(() => page.props.filters);
const filtrosAplicados = computed(() => !!selectedYear.value && !!selectedMonth.value);

console.log(resultadosReporte.value);

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


const selectedYear = computed(() => filters.value?.gestion || null);
const selectedMonth = computed(() => filters.value?.mes ? parseInt(filters.value.mes) : null);
const selectedMonthLabel = computed(() => {
    if (!selectedMonth.value) return '';
    const mesesMap = [
        { value: 1, label: 'Enero' }, { value: 2, label: 'Febrero' },
        { value: 3, label: 'Marzo' }, { value: 4, label: 'Abril' },
        { value: 5, label: 'Mayo' }, { value: 6, label: 'Junio' },
        { value: 7, label: 'Julio' }, { value: 8, label: 'Agosto' },
        { value: 9, label: 'Septiembre' }, { value: 10, label: 'Octubre' },
        { value: 11, label: 'Noviembre' }, { value: 12, label: 'Diciembre' },
    ];
    return mesesMap.find(m => m.value === selectedMonth.value)?.label || '';
});

const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
};
// Script actualizado


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

const tableColumns = [
    { label: 'Nº', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'C.I.', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'Apellidos y nombres P.C.D.' },
    { label: 'Grado de discapacidad', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'Monto a Pagar (bs.)', headerClass: 'text-center whitespace-nowrap', cellClass: '' },
    { label: 'Observaciones', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
]

const capitalizarNombre = (texto) => {
    if (!texto) return '';
    const conectores = ['de', 'del', 'de los', 'de las', 'la', 'las', 'los', 'y', 'e'];
    return texto.toUpperCase().split(' ').map((palabra) => {
        if (conectores.includes(palabra.toLowerCase())) return palabra.toLowerCase();
        return palabra;
    }).join(' ');
};

// Cuando seleccionas una gestión
const selectGestion = (year) => {
    if (!selectedMonth.value) {
        mostrarMensaje('info', 'Campos requeridos', 'Seleccione el mes para filtrar los registros.');
    }
    router.get(route('persona.reporte'), {
        gestion_gestion: year.anio
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

const Log = useForm({
    gestion: '',
    mes: '',
    total_beneficiarios: '',
});

const generearInforme = () => {
    if (!resultadosReporte.value?.length) {
        mostrarMensaje('info', 'Sin datos', 'No hay datos para generar el reporte.');
        return;
    }

    generarReporte(
        resultadosReporte.value,
        resultados.value.data[0].gestion,
        resultados.value.data[0].mes,
        'Gestión',
    );

    // ✅ Asignás los datos al form antes de enviarlo
    Log.gestion = selectedYear.value;
    Log.mes = selectedMonthLabel.value;
    Log.total_beneficiarios = resultados.value.total ?? resultadosReporte.value.length;

    Log.get(route('pago.reporteLog'), {
        onError: (errors) => console.error('Errores:', errors),
    });
};

// Cuando seleccionas un mes
const selectMes = (mes) => {
    if (!selectedYear.value) return;
    router.get(route('persona.reporte'), {
        gestion_gestion: selectedYear.value,
        mes: mes.value
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
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

            <!-- ============================================================================ -->
            <!-- SISTEMA DE MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                @close="cerrarMensaje" />

            <!-- Header -->
            <Header class="mb-0" />

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Informe Beneficiarios</h1>
                <Rutas label1="Inicio" label2="Beneficiarios" label3="Informe Beneficiarios" class="sm:text-xs" />
            </div>

            <div class="mx-0">
                <div class="p-1 sm:p-3 bg-white border-x-2 border-t-2 border-gray-200 rounded-t-lg mr-1 shadow-sm">

                    <!-- Fila principal: filtros + acciones -->
                    <div class="flex flex-wrap items-center gap-2 mt-2">

                        <!-- ── Bloque Gestión + Mes ── -->
                        <div
                            class="flex flex-wrap items-center gap-1.5 px-3 py-2 rounded-xl border border-blue-100 bg-blue-50/50 transition-all duration-200">

                            <!-- Gestión -->
                            <div class="flex flex-col gap-0.5">
                                <label
                                    class="text-[10px] font-bold text-blue-400 uppercase tracking-widest pl-1">Gestión
                                    <span class="text-red-400">*</span></label>
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold rounded-lg transition-all w-36 sm:w-40 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="selectedYear
                                                ? 'border-[#13326A] text-[#13326A]'
                                                : 'text-gray-600 hover:border-blue-300 hover:text-blue-700'"
                                            type="button">
                                            <span class="flex-1 text-left truncate">{{ selectedYear || 'Gestión'
                                                }}</span>
                                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
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
                                                <li v-for="year in gestiones" :key="year.anio">
                                                    <a href="#" @click.prevent="selectGestion(year)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-blue-50 duration-150 group"
                                                        :class="selectedYear && selectedYear.toString() === year.anio.toString()
                                                            ? 'text-slate-700'
                                                            : 'text-slate-700 hover:text-blue-700'">
                                                        <span class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            {{ year.anio }}
                                                        </span>
                                                        <svg v-if="selectedYear && selectedYear.toString() === year.anio.toString()"
                                                            class="w-4 h-4 text-[#13326A] shrink-0" fill="currentColor"
                                                            viewBox="0 0 20 20">
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
                                    :class="selectedYear ? 'text-blue-400' : 'text-gray-300'">
                                    Mes <span class="text-red-400">*</span>
                                </label>
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-semibold rounded-lg transition-all w-36 sm:w-40 bg-white border border-gray-200 shadow-sm"
                                            :class="selectedMonth
                                                ? 'border-[#13326A] text-[#13326A] hover:bg-gray-50 hover:shadow'
                                                : selectedYear && mesesDropdown.length > 0
                                                    ? 'text-gray-600 hover:border-blue-300 hover:text-blue-700 hover:bg-gray-50 hover:shadow cursor-pointer'
                                                    : 'text-gray-400 cursor-not-allowed opacity-60'" type="button"
                                            :disabled="!selectedYear">
                                            <span class="flex-1 text-left truncate">{{ selectedMonthLabel || 'Mes'
                                                }}</span>
                                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li v-for="mes in mesesDropdown" :key="mes.value">
                                                    <a href="#" @click.prevent="selectMes(mes)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-blue-50 duration-150 group"
                                                        :class="selectedMonth === mes.value
                                                            ? 'text-slate-700'
                                                            : 'text-slate-700 hover:text-blue-700'">
                                                        <span class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            {{ mes.label }}
                                                        </span>
                                                        <svg v-if="selectedMonth === mes.value"
                                                            class="w-4 h-4 text-[#13326A] shrink-0" fill="currentColor"
                                                            viewBox="0 0 20 20">
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

                        <!-- ── Spacer + Cards + Botón (al final del wrap) ── -->
                        <div class="flex items-center gap-2 ml-auto flex-wrap">

                            <!-- Cards de totales — solo si hay datos -->
                            <template v-if="resultados.data?.length > 0">
                                <!-- {{resultados.data}} -->
                                <!-- Total -->
                                <div
                                    class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                            Total</p>
                                        <p class="text-sm font-black text-emerald-700 leading-tight">
                                            {{ formatCurrency(resultados.data[0].monto_total) }}
                                            <span class="text-[10px] font-semibold">Bs</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- No pagar -->
                                <div
                                    class="flex items-center gap-2 bg-orange-50 border border-orange-200 rounded-xl px-3 py-2">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[9px] text-orange-500 uppercase tracking-widest font-bold leading-tight">
                                            No pagar</p>
                                        <p class="text-sm font-black text-orange-700 leading-tight">
                                            {{ formatCurrency(resultados.data[0].monto_bajas) }}
                                            <span class="text-[10px] font-semibold">Bs</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Pagar -->
                                <div
                                    class="flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-xl px-3 py-2">
                                    <div
                                        class="w-6 h-6 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-[9px] text-purple-500 uppercase tracking-widest font-bold leading-tight">
                                            Pagar</p>
                                        <p class="text-sm font-black text-purple-700 leading-tight">
                                            {{ formatCurrency(resultados.data[0].monto_activos) }}
                                            <span class="text-[10px] font-semibold">Bs</span>
                                        </p>
                                    </div>
                                </div>

                            </template>

                            <!-- Botón Informe -->
                            <Button v-if="resultados.data?.length > 0" id="btn-informe"
                                @click.prevent="generearInforme()" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                                class="bg-gray-100 shrink-0 relative overflow-hidden group">
                                <span
                                    class="absolute inset-0 bg-red-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                                <span class="relative z-10">
                                    <Icon :icon-button="true" name="filePDF" fill="currentColor"
                                        class-name="text-gray-500 group-hover:text-white transition-colors duration-500" />
                                </span>
                            </Button>

                        </div>
                    </div>
                </div>
            </div>
            <!-- datos: {{ resultados.data    }} -->
            <DataTable :data="resultados.data" :columns="tableColumns" row-key="id_habilitado" :row-class="(item) => ({
                'bg-white text-black hover:bg-gray-100': item.estado_periodo === 'activo',
                '!bg-[#002060] font-bold !text-[#00B0F0] hover:!bg-[#002045]': item.estado_periodo === 'baja_temporal',
                'bg-[#FFFF00] font-extrabold text-[#FF0000] hover:bg-[#FFFF00]': item.estado_periodo === 'baja_definitiva'
            })">

                <template #row="{ item, index }">

                    <!-- Columna: Nº -->
                    <td class="text-center px-1 py-1.5 whitespace-nowrap">
                        {{ (resultados.from || 1) + index }}
                    </td>

                    <!-- Columna: C.I. -->
                    <td class="text-center px-1 py-1.5 whitespace-nowrap">
                        {{ item.ci }}
                    </td>

                    <td class="pl-2 py-1.5 whitespace-nowrap">
                        <p v-if="item.nombre">
                            {{ capitalizarNombre(item.apellido) }} {{ capitalizarNombre(item.nombre) }}
                        </p>
                        <p v-else>
                            {{ capitalizarNombre(item.nombre_completo) }}
                        </p>
                    </td>

                    <!-- Columna: Grado de discapacidad -->
                    <td class="text-center px-1 py-1.5 whitespace-nowrap">
                        <span>GRAVE MUY GRAVE</span>
                    </td>

                    <!-- Columna: Monto a Pagar -->
                    <td class="text-center py-1.5 whitespace-nowrap">
                        {{ item.monto }}
                    </td>

                    <!-- Columna: Observaciones -->
                    <td class="text-center py-1.5 whitespace-nowrap">
                        <span v-if="item.estado_periodo !== 'activo'" class="uppercase">
                            {{ item.observaciones }}
                        </span>
                    </td>

                </template>

                <!-- Slot: Estado vacío -->
                <template #empty>
                    <div
                        class="flex flex-col items-center justify-center h-[60vh] w-full py-8 px-4 text-gray-700 dark:text-gray-300">
                        <div
                            class="mb-4 p-4 rounded-full bg-gray-100 dark:bg-gray-800 transition-all duration-300 hover:scale-105">
                            <svg class="w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>

                        <template v-if="filtrosAplicados">
                            <h3 class="text-xl font-semibold text-center mb-2">Sin resultados</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-center max-w-md mx-auto mb-6">
                                No se encontraron datos para la gestión y mes seleccionados.
                            </p>
                        </template>

                        <template v-else>
                            <h3 class="text-xl font-semibold text-center mb-2">Seleccione los filtros</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-center max-w-md mx-auto mb-6">
                                Elija una <span class="font-semibold text-[#13326A]">gestión</span> y un
                                <span class="font-semibold text-[#13326A]">mes</span> para visualizar los datos.
                            </p>
                        </template>

                    </div>
                </template>

            </DataTable>
            <div :class="resultados.length <= 100 ? 'mt-0.5' : 'mt-0'">
                <Paginacion v-if="resultados?.last_page > 1" :links="resultados.links" :from="resultados.from"
                    :to="resultados.to" :total="resultados.total" />
            </div>
        </div>
    </div>
</template>
