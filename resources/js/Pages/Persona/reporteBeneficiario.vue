<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Dropdown from '@/components/Dropdown.vue';
import Button from '@/components/Button.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';

/**
 * Composables
 */
import { useReporteBeneficiarioPDF } from '@/composables/useReporteBeneficiarioPDF';
import AppLayout from '@/Layouts/AppLayout.vue';

// ============================================================================
// INICIALIZACIÓN DE COMPOSABLES
// ============================================================================
const { generarReporte } = useReporteBeneficiarioPDF();

// ============================================================================
// DATOS ESTÁTICOS
// ============================================================================
const mesesMap = [
    { value: 1,  label: 'Enero' },
    { value: 2,  label: 'Febrero' },
    { value: 3,  label: 'Marzo' },
    { value: 4,  label: 'Abril' },
    { value: 5,  label: 'Mayo' },
    { value: 6,  label: 'Junio' },
    { value: 7,  label: 'Julio' },
    { value: 8,  label: 'Agosto' },
    { value: 9,  label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' },
];

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const resultados = computed(() => page.props.resultados);
const resultadosReporte = computed(() => page.props.resultadosReporte);
const gestiones = computed(() => page.props.gestiones);
const mesesNumeros = computed(() => page.props.mesesNumeros);
const filters = computed(() => page.props.filters);

// Filtros activos
const selectedYear = computed(() => filters.value?.gestion || null);
const selectedMonth = computed(() => filters.value?.mes ? parseInt(filters.value.mes) : null);
const filtrosAplicados = computed(() => !!selectedYear.value && !!selectedMonth.value);

// Switch "Retroactivo": solo tiene sentido si la gestión seleccionada tiene el
// apartado de retroactivos habilitado y el mes está dentro del rango que
// realmente se carga (enero a octubre) — mismo criterio que BandejaPagos/index.vue.
const filtroVerRetro = computed(() => !!filters.value?.ver_retro);
const mesRetroDisponible = computed(() => page.props.mesRetroDisponible ?? false);
const retroactivosHabilitadoGestion = computed(() =>
    !!gestiones.value?.find(g => String(g.anio) === String(selectedYear.value))?.retroactivos_habilitado
);
const mesEnRangoRetro = computed(() => {
    const n = Number(selectedMonth.value);
    return n >= 1 && n <= 10;
});

/**
 * Etiqueta legible del mes seleccionado actualmente
 */
const selectedMonthLabel = computed(() => {
    if (!selectedMonth.value) return '';
    return mesesMap.find(m => m.value === selectedMonth.value)?.label || '';
});

/**
 * Lista de meses disponibles para el dropdown, derivada de los meses con datos
 */
const mesesDropdown = computed(() =>
    mesesNumeros.value.map(n => mesesMap.find(m => m.value === n))
);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const mensajes = ref([]);

// ============================================================================
// REFS - FORMULARIOS
// ============================================================================

/**
 * Formulario para registrar el log del reporte generado
 */
const Log = useForm({
    gestion: '',
    mes: '',
    total_beneficiarios: '',
});

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Nº', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'C.I.', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'Apellidos y nombres P.C.D.' },
    { label: 'Grado de discapacidad', headerClass: 'text-center whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Monto a Pagar (bs.)', headerClass: 'text-center whitespace-nowrap', cellClass: '' },
    { label: 'Observaciones', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
];

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Formatea un monto como moneda boliviana con 2 decimales
 * @param {number} amount - Monto a formatear
 * @returns {string} Monto formateado
 */
const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
};

/**
 * Convierte un texto a mayúsculas respetando conectores en minúsculas
 * @param {string} texto - Texto a formatear
 * @returns {string} Texto capitalizado
 */
const capitalizarNombre = (texto) => {
    if (!texto) return '';
    const conectores = ['de', 'del', 'de los', 'de las', 'la', 'las', 'los', 'y', 'e'];
    return texto.toUpperCase().split(' ').map((palabra) => {
        if (conectores.includes(palabra.toLowerCase())) return palabra.toLowerCase();
        return palabra;
    }).join(' ');
};

// ============================================================================
// FUNCIONES - MENSAJES
// ============================================================================

/**
 * Muestra un mensaje en la interfaz
 * @param {string} tipo - Tipo de mensaje (error, correcto, info, advertencia)
 * @param {string} titulo - Título del mensaje
 * @param {string} texto - Contenido del mensaje
 */
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

/**
 * Cierra un mensaje específico por su ID
 * @param {number} id - ID del mensaje a cerrar
 */
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// ============================================================================
// FUNCIONES - NAVEGACIÓN Y FILTRADO
// ============================================================================

/**
 * Filtra los registros por gestión seleccionada
 * @param {Object} year - Objeto de gestión con propiedad anio
 */
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
};

/**
 * Filtra los registros por mes seleccionado dentro de la gestión activa
 * @param {Object} mes - Objeto de mes con propiedad value
 */
const selectMes = (mes) => {
    if (!selectedYear.value) return;
    router.get(route('persona.reporte'), {
        gestion_gestion: selectedYear.value,
        mes: mes.value,
        ver_retro: filtroVerRetro.value ? 1 : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

/**
 * Alterna entre ver el mes normal o su mes-retro correspondiente. Solo
 * tiene efecto si ya hay gestión y mes seleccionados.
 */
const alternarVerRetro = (valor) => {
    if (!selectedYear.value || !selectedMonth.value) return;
    router.get(route('persona.reporte'), {
        gestion_gestion: selectedYear.value,
        mes: selectedMonth.value,
        ver_retro: valor ? 1 : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// ============================================================================
// FUNCIONES - REPORTES
// ============================================================================

/**
 * Genera el PDF del reporte de beneficiarios y registra el log en el servidor
 */
const generarInforme = () => {
    if (!resultadosReporte.value?.length) {
        mostrarMensaje('info', 'Sin datos', 'No hay datos para generar el reporte.');
        return;
    }

    generarReporte(
        resultadosReporte.value,
        resultados.value.data[0].gestion,
        resultados.value.data[0].mes,
        'Gestión',
        filtroVerRetro.value,
    );

    Log.gestion = selectedYear.value;
    Log.mes = selectedMonthLabel.value;
    Log.total_beneficiarios = resultados.value.total ?? resultadosReporte.value.length;

    Log.get(route('pago.reporteLog'));
};

/**
 * Igual que generarInforme(), pero en Excel — mismos datos, mismo modelo
 */
const generarInformeExcel = async () => {
    if (!resultadosReporte.value?.length) {
        mostrarMensaje('info', 'Sin datos', 'No hay datos para generar el reporte.');
        return;
    }

    const gestionData = resultados.value.data[0].gestion;
    const mesData = resultados.value.data[0].mes;

    const response = await axios.post(route('persona.reporte.excel'), {
        datos: resultadosReporte.value,
        gestion: gestionData,
        mes: mesData,
        es_retro: filtroVerRetro.value ? 1 : undefined,
    }, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    // Mismo patrón de nombre que el PDF (ver useReporteBeneficiarioPDF.js).
    const mesLabel = mesesMap.find(m => m.value === mesData)?.label?.toUpperCase() || 'MES';
    link.download = `0 PAGO DE BONO MES DE ${mesLabel} ${gestionData}${filtroVerRetro.value ? ' RETROACTIVO' : ''}.xlsx`;
    link.click();

    Log.gestion = selectedYear.value;
    Log.mes = selectedMonthLabel.value;
    Log.total_beneficiarios = resultados.value.total ?? resultadosReporte.value.length;

    Log.get(route('pago.reporteLog'));
};
</script>

<template>
    <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Informe Beneficiarios</h1>
            <Rutas label1="Inicio" label2="Beneficiarios" label3="Informe Beneficiarios" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- FILTROS + RESUMEN + BOTONES -->
        <!-- ============================================================================ -->
        <div class="mx-0">
            <div class="p-1 sm:p-3 bg-gray-50 border-x-2 border-t-2 border-gray-200 rounded-t-lg mr-1 shadow-sm">

                <!-- Fila principal: filtros + acciones -->
                <div class="sm:flex flex-wrap space-y-2 gap-2 mt-2">

                    <!-- ── Bloque Gestión + Mes ── -->
                    <div
                        class="flex flex-wrap items-center gap-1.5 px-3 py-2 rounded-xl border border-[rgb(var(--brand-100))] bg-[rgba(var(--brand-50),0.5)] transition-all duration-200">

                        <!-- Gestión -->
                        <div class="flex flex-col gap-0.5">
                            <label class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Gestión
                                <span class="text-red-400">*</span></label>
                            <Dropdown align="left" width="48">
                                <template #trigger="{ open }">
                                    <button
                                        class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-36 sm:w-40 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                        :class="selectedYear
                                            ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]'
                                            : 'text-gray-600'" type="button">
                                        <span class="flex-1 text-left truncate">{{ selectedYear || 'Gestión'
                                            }}</span>
                                        <Icon :icon-button="true" name="angleDown"
                                            :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                            fill="none" stroke="currentColor" stroke-width="2" :size="17"
                                            @click="isOpen = !isOpen" />
                                    </button>
                                </template>
                                <template #content>
                                    <div class="shadow-xl overflow-hidden">
                                        <ul class="py-1.5 max-h-60 overflow-y-auto">
                                            <li v-if="!gestiones || gestiones.length === 0" class="px-4 py-3">
                                                <div class="flex items-center gap-3 text-rose-400">
                                                    <Icon :icon-button="true" name="alertTriangle"
                                                        class-name="text-rose-400" fill="none" stroke="currentColor"
                                                        stroke-width="2" :size="20" />
                                                    <span class="text-sm">No hay datos disponibles</span>
                                                </div>
                                            </li>
                                            <li v-for="year in gestiones" :key="year.anio">
                                                <a href="#" @click.prevent="selectGestion(year)"
                                                    class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150 group"
                                                    :class="selectedYear && selectedYear.toString() === year.anio.toString()
                                                        ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                        : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                    <span class="flex items-center leading-none gap-1">
                                                        <Icon :icon-button="true" name="calendar"
                                                            class-name="text-slate-400 block" fill="none"
                                                            stroke="currentColor" stroke-width="1" :size="18" />
                                                        {{ year.anio }}
                                                    </span>
                                                    <Icon
                                                        v-if="selectedYear && selectedYear.toString() === year.anio.toString()"
                                                        :icon-button="true" name="check" class-name="text-[rgb(var(--brand-700))]"
                                                        :viewBox="'0 0 20 20'" :size="17" />
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="hidden sm:block w-px h-8 bg-[rgb(var(--brand-400))]"></div>

                        <!-- Mes -->
                        <div class="flex flex-col gap-0.5">
                            <label class="text-[10px] font-bold uppercase tracking-widest pl-1"
                                :class="selectedYear ? 'text-[rgb(var(--brand-400))]' : 'text-gray-300'">
                                Mes <span class="text-red-400">*</span>
                            </label>
                            <Dropdown align="left" width="48">
                                <template #trigger="{ open }">
                                    <button
                                        class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-36 sm:w-40 bg-white border border-gray-200 shadow-sm"
                                        :class="selectedMonth
                                            ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))] hover:bg-gray-50 hover:shadow'
                                            : selectedYear && mesesDropdown.length > 0
                                                ? 'text-gray-600 cursor-pointer'
                                                : 'text-gray-400 cursor-not-allowed opacity-60'" type="button"
                                        :disabled="!selectedYear">
                                        <span class="flex-1 text-left truncate">{{ selectedMonthLabel || 'Mes'
                                            }}</span>
                                        <Icon :icon-button="true" name="angleDown"
                                            :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                            fill="none" stroke="currentColor" stroke-width="2" :size="17"
                                            @click="isOpen = !isOpen" />
                                    </button>
                                </template>
                                <template #content>
                                    <div class="shadow-xl overflow-hidden">
                                        <ul class="py-1.5 max-h-60 overflow-y-auto">
                                            <li v-for="mes in mesesDropdown" :key="mes.value">
                                                <a href="#" @click.prevent="selectMes(mes)"
                                                    class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150 group"
                                                    :class="selectedMonth === mes.value
                                                        ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                        : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                    <span class="flex items-center leading-none gap-1">
                                                        <Icon :icon-button="true" name="calendar"
                                                            class-name="text-slate-400 block" fill="none"
                                                            stroke="currentColor" stroke-width="1" :size="18" />
                                                        {{ mes.label }}
                                                    </span>
                                                    <Icon v-if="selectedMonth === mes.value" :icon-button="true"
                                                        name="check" class-name="text-[rgb(var(--brand-700))]" :viewBox="'0 0 20 20'"
                                                        :size="17" />
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Retroactivo: switch aparte, solo si la gestión lo tiene habilitado y el mes está en rango (enero-octubre) -->
                        <template v-if="selectedYear && selectedMonth && mesEnRangoRetro && retroactivosHabilitadoGestion">
                            <div class="hidden sm:block w-px h-8 bg-[rgb(var(--brand-400))]"></div>
                            <div class="flex flex-col gap-0.5">
                                <label class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Retroactivo</label>
                                <button type="button" @click="alternarVerRetro(!filtroVerRetro)"
                                    class="inline-flex items-center gap-2 px-3 py-1 rounded-xl border transition-all w-36 sm:w-40 shadow-sm hover:shadow"
                                    :class="filtroVerRetro
                                        ? 'bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100'
                                        : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'">
                                    <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 flex-shrink-0"
                                        :class="filtroVerRetro ? 'bg-amber-500' : 'bg-gray-300'">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                                            :class="filtroVerRetro ? 'translate-x-4' : 'translate-x-1'"></span>
                                    </span>
                                    <span class="text-sm font-semibold truncate">Retroactivo</span>
                                </button>
                                <p v-if="filtroVerRetro && !mesRetroDisponible" class="text-[11px] text-amber-600">
                                    Todavía no se cargó el retroactivo de este mes.
                                </p>
                            </div>
                        </template>
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
                                <div class="w-6 h-6 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
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
                                <div class="w-6 h-6 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
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

                        <!-- Botón Informe: PDF / Excel -->
                        <Dropdown v-if="resultados.data?.length > 0" align="right" width="48">
                            <template #trigger>
                                <Button id="btn-informe" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                                    class="bg-gray-100 shrink-0 relative overflow-hidden group">
                                    <span
                                        class="absolute inset-0 bg-red-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                                    <span class="relative z-10">
                                        <Icon :icon-button="true" name="circlePlus" fill="currentColor"
                                            class-name="text-gray-500 group-hover:text-white transition-colors duration-500" />
                                    </span>
                                </Button>
                            </template>
                            <template #content>
                                <ul class="py-1.5 text-sm">
                                    <li>
                                        <a href="#" @click.prevent="generarInforme()"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <Icon :icon-button="true" name="filePDF" class-name="text-red-500"
                                                :size="16" />
                                            Reporte PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" @click.prevent="generarInformeExcel()"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600"
                                                :size="16" />
                                            Reporte Excel
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </Dropdown>

                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- TABLA DE DATOS -->
        <!-- ============================================================================ -->
        <DataTable :data="resultados.data" :columns="tableColumns" row-key="id_habilitado" :row-class="(item) => ({
                'bg-white text-black hover:bg-gray-100': item.estado_periodo === 'activo' || item.estado_periodo === 'pagos_suspendidos',
                '!bg-[#002060] font-bold !text-[#00B0F0] hover:!bg-[#002045]': item.estado_periodo === 'baja_temporal',
                '!bg-[#FFFF00] font-extrabold !text-[#FF0000] hover:!bg-[#FFFF00]': item.estado_periodo === 'baja_definitiva'
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
                            Elija una <span class="font-semibold text-[rgb(var(--brand-dark-rgb))]">gestión</span> y un
                            <span class="font-semibold text-[rgb(var(--brand-dark-rgb))]">mes</span> para visualizar los datos.
                        </p>
                    </template>
                </div>
            </template>
        </DataTable>

        <!-- ============================================================================ -->
        <!-- FOOTER Y PAGINACIÓN -->
        <!-- ============================================================================ -->
        <div :class="resultados.length <= 100 ? 'mt-0.5' : 'mt-0'">
            <Paginacion v-if="resultados?.last_page > 1" :links="resultados.links" :from="resultados.from"
                :to="resultados.to" :total="resultados.total" />
        </div>

    </AppLayout>
</template>
