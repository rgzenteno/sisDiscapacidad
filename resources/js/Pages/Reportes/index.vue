<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';

// Componentes
import DataTable from '@/components/DataTable.vue';
import Mensajes from '@/components/Mensajes.vue';
import Sidebar from '@/components/Sidebar.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// TODO: Reemplazar con datos reales desde el controlador
// const filters = computed(() => page.props.filters || {});
// const arqueos = computed(() => page.props.arqueos);
// const totales = computed(() => page.props.totales); // { totalCajeros, totalMonto }

// ============================================================================
// DATOS ESTÁTICOS (reemplazar por props del controlador)
// ============================================================================

const gestionesOptions = [
    { text: '2024', value: '2024' },
    { text: '2025', value: '2025' },
    { text: '2026', value: '2026' },
];

const mesesOptions = [
    { text: 'Enero',      value: '01' },
    { text: 'Febrero',    value: '02' },
    { text: 'Marzo',      value: '03' },
    { text: 'Abril',      value: '04' },
    { text: 'Mayo',       value: '05' },
    { text: 'Junio',      value: '06' },
    { text: 'Julio',      value: '07' },
    { text: 'Agosto',     value: '08' },
    { text: 'Septiembre', value: '09' },
    { text: 'Octubre',    value: '10' },
    { text: 'Noviembre',  value: '11' },
    { text: 'Diciembre',  value: '12' },
];

// TODO: reemplazar por arqueos.value (prop del controlador)
const arqueosMock = [
    { id: 1, nombre_usuario: 'LIMBER ZAMORA PAREDES',         total_pagado: { cantidad: 100, monto: 25000  }, total_no_pagado: { cantidad: 60,  monto: 15000 }, total_general: { cantidad: 160, monto: 40000  } },
    { id: 2, nombre_usuario: 'LENNY DUVEIZA LEDEZMA ALVAREZ', total_pagado: { cantidad: 120, monto: 30000  }, total_no_pagado: { cantidad: 57,  monto: 14250 }, total_general: { cantidad: 177, monto: 44250  } },
    { id: 3, nombre_usuario: 'SEYDI GUZMAN MONTAÑO',          total_pagado: { cantidad: 130, monto: 32500  }, total_no_pagado: { cantidad: 15,  monto: 3750  }, total_general: { cantidad: 145, monto: 36250  } },
    { id: 4, nombre_usuario: 'ARNALDO ANDRÉ GUZMÁN MIRANDA',  total_pagado: { cantidad: 100, monto: 25000  }, total_no_pagado: { cantidad: 42,  monto: 10500 }, total_general: { cantidad: 142, monto: 35500  } },
    { id: 5, nombre_usuario: 'JHANET LLANOS OÑA',             total_pagado: { cantidad: 180, monto: 45000  }, total_no_pagado: { cantidad: 11,  monto: 2750  }, total_general: { cantidad: 191, monto: 47750  } },
    { id: 6, nombre_usuario: 'CINTHIA ALVAREZ JIMENEZ',       total_pagado: { cantidad: 400, monto: 100000 }, total_no_pagado: { cantidad: 41,  monto: 10250 }, total_general: { cantidad: 441, monto: 110250 } },
    { id: 7, nombre_usuario: 'EDWIN AGREDA CLAROS',           total_pagado: { cantidad: 8,   monto: 2000   }, total_no_pagado: { cantidad: 2,   monto: 500   }, total_general: { cantidad: 10,  monto: 2500   } },
    { id: 8, nombre_usuario: 'WENDI AGUILAR INTURIAS',        total_pagado: { cantidad: 140, monto: 35000  }, total_no_pagado: { cantidad: 11,  monto: 2750  }, total_general: { cantidad: 151, monto: 37750  } },
];

const columnasTabla = [
    { key: 'nro',             label: 'N°'                  },
    { key: 'nombre_usuario',  label: 'Usuario Responsable' },
    { key: 'total_pagado',    label: 'Total Pagado'        },
    { key: 'total_no_pagado', label: 'Total No Pagado'     },
    { key: 'total_general',   label: 'Total General'       },
    { key: 'acciones',        label: 'Acciones'            },
];

// ============================================================================
// COMPUTED - TOTALES GENERALES (fila pie de tabla)
// ============================================================================
const totalPagadoCantidad   = computed(() => arqueosMock.reduce((a, r) => a + r.total_pagado.cantidad,    0));
const totalPagadoMonto      = computed(() => arqueosMock.reduce((a, r) => a + r.total_pagado.monto,       0));
const totalNoPagadoCantidad = computed(() => arqueosMock.reduce((a, r) => a + r.total_no_pagado.cantidad, 0));
const totalNoPagadoMonto    = computed(() => arqueosMock.reduce((a, r) => a + r.total_no_pagado.monto,    0));
const totalGeneralCantidad  = computed(() => arqueosMock.reduce((a, r) => a + r.total_general.cantidad,   0));
const totalGeneralMonto     = computed(() => arqueosMock.reduce((a, r) => a + r.total_general.monto,      0));

// ============================================================================
// REFS - FILTROS
// ============================================================================
const filtroGestion    = ref('2026');
const filtroMes        = ref('02');
const filtroFechaDesde = ref('');
const filtroFechaHasta = ref('');

// ============================================================================
// LÓGICA DE EXCLUSIVIDAD ENTRE FILTROS
// Cuando el usuario escribe en el rango → se limpian gestión y mes
// Cuando el usuario toca gestión o mes  → se limpia el rango
// ============================================================================
function onGestionMesChange() {
    filtroFechaDesde.value = '';
    filtroFechaHasta.value = '';
}

function onRangoChange() {
    filtroGestion.value = '';
    filtroMes.value     = '';
}

// ============================================================================
// COMPUTED - indica qué bloque está activo para deshabilitar el otro
// ============================================================================
const rangoActivo       = computed(() => !!filtroFechaDesde.value || !!filtroFechaHasta.value);
const gestionMesActivo  = computed(() => !!filtroGestion.value    || !!filtroMes.value);

// ============================================================================
// REFS - MENSAJES
// ============================================================================
const mensajes = ref([]);

// ============================================================================
// FUNCIONES - ACCIONES
// ============================================================================
function aplicarFiltros() {
    // TODO: router.get(route('reportes.index'), {
    //     gestion:      filtroGestion.value,
    //     mes:          filtroMes.value,
    //     fecha_desde:  filtroFechaDesde.value,
    //     fecha_hasta:  filtroFechaHasta.value,
    // }, { preserveState: true, only: ['arqueos', 'totales', 'filters'] });
    console.log('Filtrar:', { gestion: filtroGestion.value, mes: filtroMes.value, desde: filtroFechaDesde.value, hasta: filtroFechaHasta.value });
}

function generarPDF(cajero) {
    // TODO: window.open(route('reportes.arqueo.pdf', {
    //     id:           cajero.id,
    //     gestion:      filtroGestion.value,
    //     mes:          filtroMes.value,
    //     fecha_desde:  filtroFechaDesde.value,
    //     fecha_hasta:  filtroFechaHasta.value,
    // }), '_blank');
    console.log('Generando PDF para:', cajero.nombre_usuario);
}

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText     = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle    = ref({});

function showTooltip(text, btnId) {
    const el = document.getElementById(btnId);
    if (!el) return;
    const rect = el.getBoundingClientRect();
    tooltipText.value     = text;
    showTooltipFlag.value = true;
    tooltipStyle.value    = {
        top:       `${rect.bottom + 6}px`,
        left:      `${rect.left + rect.width / 2}px`,
        transform: 'translateX(-50%)',
    };
}
function hideTooltip() { showTooltipFlag.value = false; }
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->
    <Head title="UMADIS - Reportes" />

    <div class="flex h-screen bg-gray-200 font-roboto">
        <Sidebar />

        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- ============================================================================ -->
            <!-- MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes
                v-for="m in mensajes"
                :key="m.id"
                :id="m.id"
                :tipo="m.tipo"
                :contenido="m.contenido"
            />

            <Header class="mb-0" />

            <!-- ============================================================================ -->
            <!-- ENCABEZADO -->
            <!-- ============================================================================ -->
            <div class="px-5 py-3 flex justify-between">
                <h1 class="font-semibold text-2xl">Reportes</h1>
                <Rutas label1="Inicio" label3="Reportes" />
            </div>

            <!-- ============================================================================ -->
            <!-- FILTROS + TOTALES EN LA MISMA BARRA -->
            <!-- ============================================================================ -->
            <div class="flex flex-wrap items-end gap-3 p-4 pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">

                <!-- ── Bloque Gestión + Mes ── -->
                <div class="flex items-end gap-2"
                    :class="rangoActivo ? 'opacity-40 pointer-events-none select-none' : ''">

                    <!-- Gestión -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Gestión</label>
                        <select
                            v-model="filtroGestion"
                            @change="onGestionMesChange"
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                            <option value="">-- Seleccionar --</option>
                            <option v-for="g in gestionesOptions" :key="g.value" :value="g.value">{{ g.text }}</option>
                        </select>
                    </div>

                    <!-- Mes -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Mes</label>
                        <select
                            v-model="filtroMes"
                            @change="onGestionMesChange"
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                            <option value="">-- Seleccionar --</option>
                            <option v-for="m in mesesOptions" :key="m.value" :value="m.value">{{ m.text }}</option>
                        </select>
                    </div>
                </div>

                <!-- ── Separador O ── -->
                <div class="flex flex-col items-center self-end pb-1.5">
                    <span class="text-xs font-bold text-gray-400 bg-gray-200 rounded-full w-6 h-6 flex items-center justify-center">
                        O
                    </span>
                </div>

                <!-- ── Bloque Rango de Fechas ── -->
                <div class="flex items-end gap-2"
                    :class="gestionMesActivo ? 'opacity-40 pointer-events-none select-none' : ''">

                    <!-- Desde -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Desde</label>
                        <input
                            type="date"
                            v-model="filtroFechaDesde"
                            @change="onRangoChange"
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
                    </div>

                    <!-- Hasta -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Hasta</label>
                        <input
                            type="date"
                            v-model="filtroFechaHasta"
                            @change="onRangoChange"
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        />
                    </div>
                </div>

                <!-- ── Separador visual ── -->
                <div class="self-stretch w-px bg-gray-300 mx-1"></div>

                <!-- ── Botón Buscar ── -->
                <Button
                    id="btn-buscar"
                    @click.prevent="aplicarFiltros()"
                    @mouseenter="showTooltip('Buscar', 'btn-buscar')"
                    @mouseleave="hideTooltip"
                    :style="'px-3 py-2 rounded-md border-none'"
                    class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium flex items-center gap-2 transition-colors duration-200 self-end"
                >
                    <Icon :icon-button="true" name="search" class-name="text-white" :size="16" />
                    Buscar
                </Button>

                <!-- ── Separador visual ── -->
                <div class="self-stretch w-px bg-gray-300 mx-1"></div>

                <!-- ── Total Cajeros ── -->
                <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2 self-end">
                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="18" />
                    <div>
                        <p class="text-xs text-blue-700 uppercase tracking-wide font-semibold">Total Cajeros</p>
                        <!-- TODO: reemplazar arqueosMock.length por totales.value.totalCajeros -->
                        <p class="text-sm font-bold text-blue-700">{{ arqueosMock.length }}</p>
                    </div>
                </div>

                <!-- ── Monto Total General ── -->
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-lg px-4 py-2 self-end">
                    <Icon :icon-button="true" name="clipboardList" class-name="text-green-600" :size="18" />
                    <div>
                        <p class="text-xs text-green-700 uppercase tracking-wide font-semibold">Monto Total</p>
                        <!-- TODO: reemplazar totalGeneralMonto por totales.value.totalMonto -->
                        <p class="text-sm font-bold text-green-700">{{ totalGeneralMonto.toLocaleString('es-BO') }} Bs</p>
                    </div>
                </div>

                <!-- Tooltip -->
                <div
                    v-if="showTooltipFlag"
                    class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                    :style="tooltipStyle"
                >
                    {{ tooltipText }}
                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- TABLA DE ARQUEOS POR CAJERO -->
            <!-- ============================================================================ -->
            <DataTable
                :data="arqueosMock"
                :columns="columnasTabla"
                row-key="id"
                empty-message="No se encontraron datos para los filtros seleccionados."
            >
                <template #row="{ item, index }">

                    <!-- N° -->
                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span class="text-gray-500 text-sm">{{ index + 1 }}</span>
                    </td>

                    <!-- Usuario Responsable -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <Icon :icon-button="true" name="user" class-name="text-gray-400" :size="16" />
                            <span class="font-medium text-gray-900 uppercase">{{ item.nombre_usuario }}</span>
                        </div>
                    </td>

                    <!-- Total Pagado -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="inline-flex items-center gap-1 text-xs text-green-700 font-semibold">
                                <Icon :icon-button="true" name="checkCircle" class-name="text-green-500" :size="13" />
                                {{ item.total_pagado.cantidad }} beneficiarios
                            </span>
                            <span class="text-xs text-green-600 font-bold pl-4">
                                {{ item.total_pagado.monto.toLocaleString('es-BO') }} Bs
                            </span>
                        </div>
                    </td>

                    <!-- Total No Pagado -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="inline-flex items-center gap-1 text-xs text-red-600 font-semibold">
                                <Icon :icon-button="true" name="circleMinus" class-name="text-red-500" :size="13" />
                                {{ item.total_no_pagado.cantidad }} beneficiarios
                            </span>
                            <span class="text-xs text-red-500 font-bold pl-4">
                                {{ item.total_no_pagado.monto.toLocaleString('es-BO') }} Bs
                            </span>
                        </div>
                    </td>

                    <!-- Total General -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-700 font-semibold">
                                {{ item.total_general.cantidad }} beneficiarios
                            </span>
                            <span class="text-xs text-gray-800 font-bold">
                                {{ item.total_general.monto.toLocaleString('es-BO') }} Bs
                            </span>
                        </div>
                    </td>

                    <!-- Acciones: PDF individual -->
                    <td class="px-3 py-2 text-center">
                        <div
                            @click.prevent="generarPDF(item)"
                            class="inline-flex items-center gap-1.5 cursor-pointer rounded-full p-1.5 bg-gray-100 hover:bg-red-500 transition-colors duration-300 group"
                            :title="`Generar PDF de ${item.nombre_usuario}`"
                        >
                            <Icon
                                :icon-button="true"
                                name="filePDF"
                                fill="currentColor"
                                class-name="text-gray-500 group-hover:text-white transition-colors duration-300"
                            />
                        </div>
                    </td>

                </template>

                <!-- Fila totales al pie -->
                <template #footer>
                    <tr class="bg-gray-100 border-t-2 border-gray-300 font-bold text-sm">
                        <td colspan="2" class="px-3 py-2 text-right text-gray-700 uppercase">Total General</td>

                        <td class="px-3 py-2">
                            <div class="flex flex-col">
                                <span class="text-xs text-green-700 font-bold">{{ totalPagadoCantidad.toLocaleString('es-BO') }} beneficiarios</span>
                                <span class="text-xs text-green-600 font-bold">{{ totalPagadoMonto.toLocaleString('es-BO') }} Bs</span>
                            </div>
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex flex-col">
                                <span class="text-xs text-red-600 font-bold">{{ totalNoPagadoCantidad.toLocaleString('es-BO') }} beneficiarios</span>
                                <span class="text-xs text-red-500 font-bold">{{ totalNoPagadoMonto.toLocaleString('es-BO') }} Bs</span>
                            </div>
                        </td>

                        <td class="px-3 py-2">
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-700 font-bold">{{ totalGeneralCantidad.toLocaleString('es-BO') }} beneficiarios</span>
                                <span class="text-xs text-gray-800 font-bold">{{ totalGeneralMonto.toLocaleString('es-BO') }} Bs</span>
                            </div>
                        </td>

                        <td></td>
                    </tr>
                </template>

                <!-- Estado vacío -->
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">
                        <div class="mb-6">
                            <Icon :icon-button="true" name="clipboardList" class-name="text-gray-400" :size="64" :height="64" />
                        </div>
                        <div class="text-center space-y-2 max-w-md">
                            <h3 class="text-xl font-semibold text-gray-800">No se encontraron datos</h3>
                            <p class="text-sm text-gray-500">Seleccione los filtros y presione Buscar.</p>
                        </div>
                    </div>
                </template>

            </DataTable>

            <!-- ============================================================================ -->
            <!-- FOOTER -->
            <!-- ============================================================================ -->
            <div>
                <!-- TODO: <Paginacion v-if="arqueos?.last_page > 1" :links="arqueos.links" :from="arqueos.from" :to="arqueos.to" :total="arqueos.total" /> -->
                <Footer />
            </div>

        </div>
    </div>
</template>
