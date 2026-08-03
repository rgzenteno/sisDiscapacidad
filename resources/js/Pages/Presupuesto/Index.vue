<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Busqueda from '@/components/Busqueda.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Rutas from '@/components/Rutas.vue';
import Modal from '@/components/Modal.vue';
import Icon from '@/components/Icon.vue';
import Dropdown from '@/components/Dropdown.vue';
import Form from '@/components/Form/Form.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

import { can } from '@/lib/can';
import { formatCurrency } from '@/components/Form/utils/formatters.js';
import { useReporteArqueoCajaPDF } from '@/composables/useReporteArqueoCajaPDF';

// ============================================================================
// PROPS DE LA PÁGINA
// ============================================================================
const page = usePage();

const mesesMap = [
    { text: 'Enero', value: 1 }, { text: 'Febrero', value: 2 }, { text: 'Marzo', value: 3 },
    { text: 'Abril', value: 4 }, { text: 'Mayo', value: 5 }, { text: 'Junio', value: 6 },
    { text: 'Julio', value: 7 }, { text: 'Agosto', value: 8 }, { text: 'Septiembre', value: 9 },
    { text: 'Octubre', value: 10 }, { text: 'Noviembre', value: 11 }, { text: 'Diciembre', value: 12 },
];

const gestiones = computed(() => page.props.gestiones || []);
const mesesNumeros = computed(() => page.props.mesesNumeros || []);
const usuarios = computed(() => page.props.usuarios || []);

// Antes de elegir gestión+mes el backend manda una collection vacía ([]) en
// vez del paginador de Laravel (que serializa como { data, links, ... }) —
// se normalizan acá los dos casos para no repetir el chequeo en el template.
const presupuestos = computed(() => {
    const raw = page.props.presupuestos;
    return Array.isArray(raw) ? { data: raw } : (raw ?? { data: [] });
});
const presupuestosData = computed(() => presupuestos.value.data ?? []);

const gestionesOptions = computed(() =>
    gestiones.value.map(g => ({ text: String(g.anio), value: String(g.anio) }))
);
const mesesOptions = computed(() =>
    mesesNumeros.value.map(n => mesesMap.find(m => m.value === Number(n))).filter(Boolean)
);

// ============================================================================
// FILTROS — Gestión + Mes
// ============================================================================
const filters = computed(() => page.props.filters || {});
const filtroGestion = ref(filters.value.gestion ? String(filters.value.gestion) : '');
const filtroMes = ref(filters.value.mes ? Number(filters.value.mes) : '');
const cargando = ref(false);

// ============================================================================
// TOOLTIP — mismo patrón que Roles/Index.vue
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

const showTooltip = (text, buttonId) => {
    const button = document.getElementById(buttonId);
    if (button) {
        const rect = button.getBoundingClientRect();
        tooltipStyle.value = {
            left: `${rect.left + rect.width / 2}px`,
            top: `${rect.top - 10}px`,
            transform: 'translateX(-50%) translateY(-100%)'
        };
    }
    tooltipText.value = text;
    showTooltipFlag.value = true;
};

const hideTooltip = () => {
    showTooltipFlag.value = false;
    tooltipText.value = '';
};

const mesLabel = computed(() => mesesMap.find(m => m.value === filtroMes.value)?.text ?? 'Mes');
const montoMes = computed(() => Number(page.props.montoMes ?? 0));

// ============================================================================
// REPORTE — Arqueo de Caja por usuario (mismo reporte que BandejaPagos
// "Cerrar Caja" / "Resumen General", pero disparado desde esta tabla).
// ============================================================================
const fechaArchivoBO = () =>
    new Date()
        .toLocaleString('sv-SE', { timeZone: 'America/La_Paz', hour12: false })
        .replace(/ /, '_')
        .replace(/\..+/, '');

/** Fila sobre la que se está generando el arqueo — se setea justo antes de
 * llamar a generarArqueoPDF(), que la lee vía closure (getData). */
const usuarioParaArqueo = ref(null);

const { generarReporte: generarArqueoPDF } = useReporteArqueoCajaPDF(
    () => {
        const item = usuarioParaArqueo.value;
        return {
            cantidad_total_pagos: Number(item?.cantidad_total_pagos ?? 0),
            cantidad_anulados: Number(item?.cantidad_anulados ?? 0),
            monto_pago: montoMes.value,
            total_pagado: Number(item?.pagado ?? 0),
            presupuesto_asignado: Number(item?.monto_asignado ?? 0),
            gestion: filtroGestion.value,
            mes: filtroMes.value,
        };
    },
    () => `${usuarioParaArqueo.value?.nombre ?? ''} ${usuarioParaArqueo.value?.apellido ?? ''}`,
    () => usuarioParaArqueo.value?.cargo
);

function generarArqueoDeUsuario(item) {
    usuarioParaArqueo.value = item;
    generarArqueoPDF();
}

async function exportarExcelArqueoDeUsuario(item) {
    const response = await axios.post(route('bandeja.arqueo.excel'), {
        datos: {
            cantidad_total_pagos: Number(item.cantidad_total_pagos ?? 0),
            cantidad_anulados: Number(item.cantidad_anulados ?? 0),
            monto_pago: montoMes.value,
            total_pagado: Number(item.pagado ?? 0),
            presupuesto_asignado: Number(item.monto_asignado ?? 0),
            gestion: filtroGestion.value,
            mes: filtroMes.value,
        },
        usuario: `${item.nombre} ${item.apellido}`,
        cargo: item.cargo,
    }, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = `Arqueo_Caja-${fechaArchivoBO()}.xlsx`;
    link.click();
}

function seleccionarGestion(valor) {
    filtroGestion.value = valor;
    filtroMes.value = '';
    cargando.value = true;

    router.get(route('presupuesto.index'), { gestion_gestion: valor }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

function seleccionarMes(valor) {
    filtroMes.value = valor;
    cargando.value = true;

    router.get(route('presupuesto.index'), {
        gestion_gestion: filtroGestion.value,
        mes: valor,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

// ============================================================================
// COLUMNAS TABLA
// ============================================================================
const columnas = [
    { key: 'nro', label: 'N°', headerClass: 'text-center' },
    { key: 'usuario', label: 'Usuario' },
    { key: 'asignado', label: 'Presupuesto Asignado', headerClass: 'whitespace-nowrap text-center' },
    { key: 'pagado', label: 'Pagado', headerClass: 'whitespace-nowrap text-center' },
    { key: 'restante', label: 'Restante', headerClass: 'whitespace-nowrap text-center' },
    { key: 'acciones', label: 'Acciones', headerClass: 'text-center' },
];

function restante(item) {
    return Number(item.monto_asignado) - Number(item.pagado);
}

// ============================================================================
// FORMULARIOS - ALTA / EDICIÓN (componente Form compartido)
// ============================================================================
const formCrear = ref(false);
const formEditar = ref(false);
const selectedId = ref(null);
const selectedItem = ref(null);

// El buscador del select (SelectField.vue `searchable: true`) evita tener
// que scrollear a ciegas la lista completa de cajeros para elegir uno.
const fieldsCrear = [
    {
        typeInput: 'select',
        name: 'id_usuario',
        label: 'Usuario',
        required: true,
        placeholder: 'un usuario',
        searchable: true,
        matchTriggerWidth: true,
        options: [],
    },
    {
        typeInput: 'number',
        name: 'monto_asignado',
        label: 'Monto Asignado (Bs)',
        required: true,
        placeholder: 'el monto a asignar',
    },
    // Campos ocultos: useFormSubmit.js solo agrega form.mes/form.gestion al
    // payload si esas claves ya existen en el form inicial — mismo patrón
    // que mesFieldsPdf en Gestion/index.vue (name + hidden: true), si no
    // el store() llega sin gestión/mes y el backend lo rechaza (422).
    { name: 'mes', hidden: true },
    { name: 'gestion', hidden: true },
];

// El backend ya excluye del prop `usuarios` a quien ya tiene presupuesto
// este mes+gestión (PresupuestoController::index) — acá solo se refleja esa
// lista en las opciones del select. Reactivo (no una asignación única) para
// que, tras un alta, la lista quede al día la próxima vez que se abra el
// formulario (mismo `fieldsCrear` no se recrea entre reloads).
watch(usuarios, (lista) => {
    fieldsCrear[0].options = lista.map(u => ({ value: u.id, text: `${u.nombre} ${u.apellido}`.toUpperCase() }));
}, { immediate: true });

// En edición no se reasigna usuario/mes/gestión (solo el monto) — para eso
// se elimina el registro y se crea uno nuevo, ver PresupuestoController::update().
// El usuario no se vuelve a mostrar como campo (typeInput "verificar" trae
// hardcodeado el bloque "Beneficiario/CI" de ReadOnlyField.vue, pensado para
// el flujo de Persona/Carnet) — se muestra como subtítulo del modal en su lugar.
const fieldsEditar = [
    {
        typeInput: 'number',
        name: 'monto_asignado',
        label: 'Monto Asignado (Bs)',
        required: true,
        placeholder: 'el monto a asignar',
    },
];

function abrirCrear() {
    formCrear.value = true;
}

function abrirEditar(item) {
    selectedId.value = item.id;
    selectedItem.value = {
        monto_asignado: item.monto_asignado,
        usuario_nombre: `${item.nombre} ${item.apellido}`.toUpperCase(),
    };
    formEditar.value = true;
}

function closeForm() {
    formCrear.value = false;
    formEditar.value = false;
}

function handleAdd() {
    closeForm();
    router.reload({ preserveScroll: true });
}

function handleEdit() {
    closeForm();
    router.reload({ preserveScroll: true });
}

function sinDatos() {
    // El propio Form ya marca en rojo los campos requeridos que faltan.
}

// ============================================================================
// MODAL — Eliminar
// ============================================================================
const modalEliminar = ref(false);
const itemAEliminar = ref(null);
const procesandoEliminar = ref(false);

function abrirEliminar(item) {
    itemAEliminar.value = item;
    modalEliminar.value = true;
}

function cerrarEliminar() {
    modalEliminar.value = false;
    itemAEliminar.value = null;
}

function confirmarEliminar() {
    if (procesandoEliminar.value || !itemAEliminar.value) return;
    procesandoEliminar.value = true;

    router.delete(route('presupuesto.destroy', itemAEliminar.value.id), {
        preserveScroll: true,
        onFinish: () => {
            procesandoEliminar.value = false;
            cerrarEliminar();
        },
    });
}
</script>

<template>
    <AppLayout>

        <!-- ENCABEZADO -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Presupuesto por Usuario</h1>
            <Rutas label1="Inicio" label3="Presupuesto" class="sm:text-xs" />
        </div>

        <!-- FILTROS + BOTÓN AGREGAR -->
        <div class="p-2 sm:p-3 bg-gray-50 border-x-2 border-t-2 border-gray-200 rounded-t-lg mr-1 shadow-sm">
                <div class="flex flex-wrap items-end gap-3">

                    <!-- Gestión -->
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <label class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Gestión</label>
                        <Dropdown align="left" width="48">
                            <template #trigger="{ open }">
                                <button
                                    class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-32 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                    :class="filtroGestion ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]' : 'text-slate-400'"
                                    type="button">
                                    <span class="flex-1 text-left truncate">{{ filtroGestion || 'Gestión' }}</span>
                                    <Icon :icon-button="true" name="angleDown"
                                        :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                        fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                                </button>
                            </template>
                            <template #content>
                                <div class="shadow-xl overflow-hidden">
                                    <ul class="py-1.5 max-h-60 overflow-y-auto">
                                        <li v-if="!gestionesOptions.length" class="px-4 py-3 text-sm text-rose-400">
                                            No hay gestiones disponibles
                                        </li>
                                        <li v-for="g in gestionesOptions" :key="g.value">
                                            <a href="#" @click.prevent="seleccionarGestion(g.value)"
                                                class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                :class="filtroGestion === g.value
                                                    ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold'
                                                    : 'text-slate-700'">
                                                {{ g.text }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Mes -->
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <label class="text-[10px] font-bold uppercase tracking-widest pl-1"
                            :class="filtroGestion ? 'text-[rgb(var(--brand-400))]' : 'text-gray-300'">Mes</label>
                        <Dropdown align="left" width="48">
                            <template #trigger="{ open }">
                                <button
                                    class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-32 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm"
                                    :class="filtroMes
                                        ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]'
                                        : filtroGestion ? 'text-gray-600' : 'text-slate-400 cursor-not-allowed opacity-60'"
                                    type="button" :disabled="!filtroGestion">
                                    <span class="flex-1 text-left truncate">{{ mesLabel }}</span>
                                    <Icon :icon-button="true" name="angleDown"
                                        :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                        fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                                </button>
                            </template>
                            <template #content>
                                <div class="shadow-xl overflow-hidden">
                                    <ul class="py-1.5 max-h-60 overflow-y-auto">
                                        <li v-for="m in mesesOptions" :key="m.value">
                                            <a href="#" @click.prevent="seleccionarMes(m.value)"
                                                class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                :class="filtroMes === m.value
                                                    ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold'
                                                    : 'text-slate-700'">
                                                {{ m.text }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Buscador + Botón Agregar (mismo patrón que Roles/Index.vue) -->
                    <template v-if="filtroGestion && filtroMes">
                        <Busqueda class="mt-3" :initial-value="filters.buscador" name="usuario o monto" only="presupuestos"
                            :data="presupuestos" ruta-busqueda="presupuesto.index"
                            :extra-params="{ gestion_gestion: filtroGestion, mes: filtroMes }" />
                        <Button v-if="can('agregar-presupuesto')" id="btn-agregar-presupuesto" @click.prevent="abrirCrear"
                            @mouseenter="showTooltip('Agregar', 'btn-agregar-presupuesto')" @mouseleave="hideTooltip"
                            :style="'px-2 py-2 pb-1 rounded-full border-none'"
                            class="shrink-0 self-center bg-gray-200 relative overflow-hidden group ml-auto">
                            <span
                                class="absolute inset-0 bg-[rgb(var(--brand-500))] rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                            <span class="relative z-10">
                                <Icon :icon-button="true" name="cash"
                                    class-name="text-gray-600 group-hover:text-white transition-colors duration-500"
                                    :size="32" :height="32" />
                            </span>
                        </Button>
                        <div v-if="showTooltipFlag" class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                            :style="tooltipStyle">
                            {{ tooltipText }}
                        </div>
                    </template>
                </div>
            </div>

        <!-- TABLA -->
        <DataTable :data="presupuestosData" :columns="columnas" row-key="id"
            empty-message="No se asignó presupuesto a ningún usuario para este mes todavía.">
            <template #row="{ item, index }">
                    <td class="px-3 py-2 text-center whitespace-nowrap">{{ index + 1 }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <span class="text-gray-700 uppercase">{{ item.nombre }} {{ item.apellido }}</span>
                    </td>
                    <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">
                        {{ formatCurrency(item.monto_asignado) }} Bs
                    </td>
                    <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">
                        {{ formatCurrency(item.pagado) }} Bs
                    </td>
                    <td class="px-3 py-2 text-center whitespace-nowrap font-semibold"
                        :class="restante(item) < 0 ? 'text-red-600' : 'text-emerald-700'">
                        {{ formatCurrency(restante(item)) }} Bs
                    </td>
                    <td class="px-3 py-1.5">
                        <ul class="flex justify-center items-center space-x-2">
                            <li class="cursor-pointer">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <Icon name="filePDF" class-name="text-red-600" :title="'Arqueo de Caja'" />
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden py-1">
                                            <a href="#" @click.prevent="generarArqueoDeUsuario(item)"
                                                class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                                <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                                Arqueo de Caja PDF
                                            </a>
                                            <a href="#" @click.prevent="exportarExcelArqueoDeUsuario(item)"
                                                class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                                <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                                Arqueo de Caja Excel
                                            </a>
                                        </div>
                                    </template>
                                </Dropdown>
                            </li>
                            <Icon v-if="can('editar-presupuesto')" @click.prevent="abrirEditar(item)" name="edit"
                                class-name="text-gray-800 cursor-pointer" />
                            <li v-if="can('eliminar-presupuesto')" @click.prevent="abrirEliminar(item)"
                                class="cursor-pointer text-red-600">
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </li>
                        </ul>
                    </td>
            </template>
            <template #empty>
                <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-lg py-2">
                    {{ filtroGestion && filtroMes
                        ? 'No se asignó presupuesto a ningún usuario para este mes todavía.'
                        : 'Selecciona una gestión y un mes para ver los presupuestos asignados.' }}
                </span>
            </template>
        </DataTable>

        <Paginacion v-if="presupuestos.last_page > 1" :links="presupuestos.links" :from="presupuestos.from"
            :to="presupuestos.to" :total="presupuestos.total" />
        <Footer />

        <!-- FORMULARIO: Asignar Presupuesto -->
        <Form v-if="formCrear" :fields="fieldsCrear" :data="{ tipo: 'gestion', gestion: filtroGestion, mes: filtroMes }"
            submit-route="presupuesto.store" @add="handleAdd" @sinDatos="sinDatos" @cancel="closeForm">
            <template #icon>
                <Icon :icon-button="true" name="cash" class-name="text-white" :size="20" />
            </template>
            <template #label1>Asignar Presupuesto</template>
            <template #label2>{{ mesLabel }} — {{ filtroGestion }}</template>
        </Form>

        <!-- FORMULARIO: Editar Presupuesto -->
        <Form v-if="formEditar" :fields="fieldsEditar" boton-name="Guardar" :idFor="selectedId"
            :existing-data="selectedItem || {}" :edit-mode="true" submit-route="presupuesto.update"
            @add="handleEdit" @sinDatos="sinDatos" @cancel="closeForm" @close="closeForm">
            <template #icon>
                <Icon :icon-button="true" name="edit" class-name="text-white" :size="20" />
            </template>
            <template #label1>Editar Presupuesto</template>
            <template #label2>{{ selectedItem?.usuario_nombre }} · {{ mesLabel }} — {{ filtroGestion }}</template>
        </Form>

        <!-- MODAL: Eliminar Presupuesto -->
        <Transition name="fade">
            <Modal v-if="modalEliminar" :showHeader="false" :showFooter="false" maxWidth="max-w-md" @close="cerrarEliminar">
                <div class="py-4 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-red-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="warning" class-name="text-red-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="50" :height="50" />
                    </div>
                    <!-- Título -->
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Eliminar Presupuesto
                    </h2>

                    <!-- Mensaje principal -->
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        ¿Seguro que desea <span class="font-semibold text-red-600">eliminar</span> el presupuesto
                        asignado a
                        <span class="font-semibold uppercase">{{ itemAEliminar?.nombre }} {{ itemAEliminar?.apellido }}</span>?
                    </p>

                    <!-- Advertencia -->
                    <p class="text-xs mt-2 text-red-500 dark:text-red-400">
                        Esta acción es irreversible.
                    </p>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="cerrarEliminar"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cancelar
                            </Button>
                            <Button @click="confirmarEliminar" :disabled="procesandoEliminar"
                                :style="'items-center py-3 px-5 sm:px-12 sm:py-2.5 rounded-xl border relative w-36'"
                                :class="procesandoEliminar ? 'opacity-60 cursor-not-allowed bg-red-400' : 'bg-red-600 hover:bg-red-500'"
                                class="text-white">
                                <span v-if="procesandoEliminar"
                                    class="absolute inset-0 flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Eliminando...
                                </span>
                                <span v-else class="flex items-center justify-center">Eliminar</span>
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>
    </AppLayout>
</template>
