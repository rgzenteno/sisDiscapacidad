<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Componentes
 */
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Rutas from '@/components/Rutas.vue';
import Modal from '@/components/Modal.vue';
import Icon from '@/components/Icon.vue';
import Input from '@/components/Input.vue';

/**
 * Composables de reportes PDF
 */
import { useReportePagosIndividualPDF } from '@/composables/useReportePagosIndividualPDF';
import { useReporteResumenPDF } from '@/composables/useReporteResumenPDF';
import { useReporteArqueoCajaPDF } from '@/composables/useReporteArqueoCajaPDF';
import { useReportePagosTodosPDF } from '@/composables/useReportePagosTodosPDF';
import { useReporteNoPagadosPDF } from '@/composables/useReporteNoPagadosPDF';
import { useReporteBajasPDF } from '@/composables/useReporteBajasPDF';

/**
 * Utilidades
 */
import { can } from '@/lib/can';
import Dropdown from '@/components/Dropdown.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// ============================================================================
// INICIALIZACIÓN DE COMPOSABLES
// ============================================================================
const { generarReporte: generarPDFIndividual } = useReportePagosIndividualPDF();
const { generarReporte: generarPDFResumen } = useReporteResumenPDF();
const { generarReporte: generarPDFTodos } = useReportePagosTodosPDF();
const { generarReporte: generarPDFArqueo } = useReporteArqueoCajaPDF(
    () => ({
        cantidad_total_pagos: cantidadMisPagos.value,
        cantidad_anulados: cantidadAnuladosMisPagos.value,
        monto_pago: page.props.montoMes ?? 0,
        total_pagado: montoTotalIndividual.value,
        presupuesto_asignado: page.props.misPresupuestoAsignado ?? 0,
        gestion: filtroGestion.value,
        mes: filtroMes.value,
    }),
    () => `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`,
    () => page.props.auth.user.cargo
);
/**
 * Mismo reporte de Arqueo de Caja que generarPDFArqueo, pero para el
 * usuario seleccionado en el modal de "Resumen General" (no el usuario
 * logueado) — usa siempre el mes completo (pagosDelModal, sin los filtros
 * de nombre/C.I. del modal), porque un arqueo de caja es un cierre del mes,
 * no un subconjunto filtrado.
 */
const { generarReporte: generarPDFArqueoUsuario } = useReporteArqueoCajaPDF(
    () => {
        const pagos = pagosDelModal.value;
        const pagados = pagos.filter(p => parseFloat(p.pago) !== 0);
        return {
            cantidad_total_pagos: pagos.length,
            cantidad_anulados: pagos.length - pagados.length,
            monto_pago: page.props.montoMes ?? 0,
            total_pagado: pagados.reduce((s, p) => s + (parseFloat(p.monto) || 0), 0),
            presupuesto_asignado: usuarioModalActivo.value?.presupuesto_asignado ?? 0,
            gestion: filtroGestion.value,
            mes: filtroMes.value,
        };
    },
    () => `${usuarioModalActivo.value?.nombre ?? ''} ${usuarioModalActivo.value?.apellido ?? ''}`,
    () => usuarioModalActivo.value?.cargo
);
const { generarReporte: generarPDFNoPagados } = useReporteNoPagadosPDF();
const { generarReporte: generarPDFBajas } = useReporteBajasPDF();

/**
 * Mismo timestamp de nombre de archivo que usan los composables PDF
 * (fechaArchivo() en cada useReporte*PDF.js) — para que el Excel siga el
 * mismo patrón de nombre que su PDF equivalente en vez de Date.now().
 */
const fechaArchivoBO = () =>
    new Date()
        .toLocaleString('sv-SE', { timeZone: 'America/La_Paz', hour12: false })
        .replace(/ /, '_')
        .replace(/\..+/, '');

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// ============================================================================
// DATOS ESTÁTICOS
// ============================================================================

const gestiones = computed(() => page.props.gestiones || []);
const mesesNumeros = computed(() => page.props.mesesNumeros || []);
const pagosTodosExport = computed(() => page.props.pagosTodosExport ?? []);
const noPagadosExport = computed(() => page.props.noPagadosExport ?? []);
const bajasExport = computed(() => page.props.bajasExport ?? []);

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

// La gestión seleccionada tiene que tener el apartado de retroactivos
// habilitado (el switch de Gestion/index.vue) para que tenga sentido
// mostrar el toggle "Retroactivo" acá — si no, nunca hay nada que filtrar.
const retroactivosHabilitadoGestion = computed(() =>
    !!gestiones.value.find(g => String(g.anio) === filtroGestion.value)?.retroactivos_habilitado
);

const mesesOptions = computed(() =>
    mesesNumeros.value.map(n =>
        mesesMap.find(m => m.value === String(n).padStart(2, '0'))
    ).filter(Boolean)
);

// ============================================================================
// REFS - ESTADO UI
// ============================================================================
const cargando = ref(false);
const isOpen = ref(false)
const dropdownReporteAbierto = ref(false);

const misPagosTodos = computed(() => page.props.misPagosTodos ?? []);
const resumenGeneral = computed(() => page.props.resumenGeneral);

// ============================================================================
// REFS - FILTROS
// ============================================================================
const filters = computed(() => page.props.filters || {});
const filtroGestion = ref(filters.value.gestion || '');
const filtroMes = ref(filters.value.mes || '');
const filtroVerRetro = ref(!!filters.value.ver_retro);
const mesRetroDisponible = computed(() => page.props.mesRetroDisponible ?? false);
// Los retroactivos solo se cargan de enero (01) a octubre (10) — noviembre y
// diciembre quedan fuera del alcance, así que el toggle no debe ofrecerse ahí.
const mesEnRangoRetro = computed(() => {
    const n = Number(filtroMes.value);
    return n >= 1 && n <= 10;
});
const filtroDesde = ref(filters.value.fecha_desde || '');
const filtroHasta = ref(filters.value.fecha_hasta || '');
const filtroDistrito = ref(filters.value.distrito || '');
const filtroEstadoBaja = ref(filters.value.estado_baja || '');
/** Switch del tab Total Pagados — filtra tanto la tabla en pantalla como el
 * PDF/Excel exportado (mismo dataset, pagosTodos/pagosTodosExport ya vienen
 * filtrados del backend). Arranca en "no incluir" para coincidir con las
 * tarjetas resumen (Total Pagos / Monto Total), que ya excluyen anulados. */
const filtroIncluirAnulados = ref(!!filters.value.incluir_anulados);
const distritos = computed(() => page.props.distritos || []);
const modalAnular = ref(false);
const modalReactivar = ref(false);
const pagoSeleccionado = ref(null);
const procesandoPago = ref(false);
const observacionAnulacion = ref('');
const buscado = ref(!!filters.value.mes || !!filters.value.fecha_desde);

// ============================================================================
// MODAL — Pagos por Usuario (Resumen General)
// ============================================================================
const modalUsuarioPagos = ref(false);
const usuarioModalActivo = ref(null);
// Un solo input busca por nombre O C.I. a la vez (en vez de dos campos
// separados) — más rápido para buscar sin tener que decidir en cuál
// escribir.
const busqNombreCI = ref('');
const busqDistrito = ref('');

/** Todos los pagos del usuario seleccionado en el modal */
const pagosDelModal = computed(() =>
    usuarioModalActivo.value
        ? (pagosPorUsuario.value[usuarioModalActivo.value.id] ?? [])
        : []
);

/** Pagos filtrados por los buscadores del modal (nombre/C.I. combinado + distrito) */
const pagosFiltradosModal = computed(() => {
    return pagosDelModal.value.filter(p => {
        const nombre = `${p.apellido_persona ?? ''} ${p.nombre_persona ?? ''}`.toLowerCase();
        const ci = String(p.ci_persona ?? '').toLowerCase();
        const distrito = String(p.distrito ?? '').toLowerCase();

        if (busqNombreCI.value) {
            const termino = busqNombreCI.value.toLowerCase();
            if (!nombre.includes(termino) && !ci.includes(termino)) return false;
        }
        if (busqDistrito.value && !distrito.includes(busqDistrito.value.toLowerCase())) return false;
        return true;
    });
});

/** Monto total de los registros filtrados */
const montoFiltradoModal = computed(() =>
    pagosFiltradosModal.value
        .filter(p => parseFloat(p.pago) !== 0)
        .reduce((s, p) => s + (parseFloat(p.monto) || 0), 0)
);

const anuladosFiltradosModal = computed(() =>
    pagosFiltradosModal.value.filter(p => parseFloat(p.pago) === 0).length
);

/** Monto de un pago individual — es el mismo para todos los pagos del mes,
 * así que se muestra una sola vez en la card en vez de repetirlo por fila. */
const montoPorPagoModal = computed(() =>
    pagosDelModal.value.find(p => parseFloat(p.pago) !== 0)?.monto ?? 0
);

/** Presupuesto restante del usuario del modal — sigue al total pagado
 * FILTRADO (montoFiltradoModal), igual que la card "Total Pagado": si se
 * busca por nombre/C.I., el restante debe reflejar ese subconjunto, no el
 * total sin filtrar. El asignado en cambio nunca cambia por los buscadores. */
const presupuestoRestanteModal = computed(() => {
    const asignado = Number(usuarioModalActivo.value?.presupuesto_asignado ?? 0);
    return asignado - montoFiltradoModal.value;
});

/** Abre el modal y limpia los filtros */
function abrirModalUsuario(item) {
    usuarioModalActivo.value = item;
    busqNombreCI.value = '';
    busqDistrito.value = '';
    modalUsuarioPagos.value = true;
}

/** Genera PDF con los datos actualmente filtrados en el modal */
function generarPDFModalFiltrado() {
    if (!usuarioModalActivo.value) return;
    const item = usuarioModalActivo.value;
    generarPDFIndividual(
        pagosFiltradosModal.value,
        filtroGestion.value,
        filtroMes.value,
        `${item.nombre} ${item.apellido}`,
        'ResumenUsuario',
        filtroDesde.value,
        filtroHasta.value,
    );
}

/** Igual que generarPDFModalFiltrado(), pero en Excel — mismos datos filtrados */
async function exportarExcelModalFiltrado() {
    if (!usuarioModalActivo.value) return;
    const item = usuarioModalActivo.value;
    const response = await axios.post(route('bandeja.individual.excel'), {
        datos: pagosFiltradosModal.value,
        gestion: filtroGestion.value,
        mes: filtroMes.value,
        usuario: `${item.nombre} ${item.apellido}`,
        fecha_desde: filtroDesde.value || undefined,
        fecha_hasta: filtroHasta.value || undefined,
    }, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = `Reporte_ResumenUsuario-${fechaArchivoBO()}.xlsx`;
    link.click();
}

/**
 * Genera el Arqueo de Caja del usuario seleccionado en el modal de Resumen
 * General — mismo reporte que "Cerrar Caja" en la pestaña "Mis Pagos", pero
 * para el cajero elegido en vez del usuario logueado.
 */
function generarArqueoUsuario() {
    if (!usuarioModalActivo.value) return;
    const item = usuarioModalActivo.value;
    registrarLog({
        tipo: 'individual',
        gestion: filtroGestion.value,
        mes: mesLabel.value,
        usuario: `${item.nombre} ${item.apellido}`,
        total_pagos: pagosDelModal.value.length,
    });
    generarPDFArqueoUsuario();
}

/** Igual que generarArqueoUsuario(), pero en Excel — mismos datos ya calculados para el PDF */
async function exportarExcelArqueoUsuario() {
    if (!usuarioModalActivo.value) return;
    const item = usuarioModalActivo.value;
    const pagos = pagosDelModal.value;
    const pagados = pagos.filter(p => parseFloat(p.pago) !== 0);

    registrarLog({
        tipo: 'individual',
        gestion: filtroGestion.value,
        mes: mesLabel.value,
        usuario: `${item.nombre} ${item.apellido}`,
        total_pagos: pagos.length,
    });

    const response = await axios.post(route('bandeja.arqueo.excel'), {
        datos: {
            cantidad_total_pagos: pagos.length,
            cantidad_anulados: pagos.length - pagados.length,
            monto_pago: page.props.montoMes ?? 0,
            total_pagado: pagados.reduce((s, p) => s + (parseFloat(p.monto) || 0), 0),
            presupuesto_asignado: item.presupuesto_asignado ?? 0,
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

function generarReporteNoPagados() {
    generarPDFNoPagados(
        noPagadosExport.value,
        filtroGestion.value,
        filtroMes.value,
        filtroDesde.value,
        filtroHasta.value,
        filtroDistrito.value,
        filtroVerRetro.value,
    );
}

function generarReporteBajas() {
    generarPDFBajas(
        bajasExport.value,
        filtroGestion.value,
        filtroMes.value,
        filtroEstadoBaja.value,
        filtroDistrito.value,
        filtroVerRetro.value,
    );
}

async function exportarExcelNoPagados() {
    const response = await axios.post(route('bandeja.noPagados.excel'), {
        datos: noPagadosExport.value,
        gestion: filtroGestion.value,
        mes: filtroMes.value,
        fecha_desde: filtroDesde.value || undefined,
        fecha_hasta: filtroHasta.value || undefined,
        distrito: filtroDistrito.value || undefined,
        ver_retro: filtroVerRetro.value ? 1 : undefined,
    }, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    // Mismo patrón de nombre que el PDF (useReporteNoPagadosPDF.js).
    link.download = `Reporte_NoPagados-${fechaArchivoBO()}.xlsx`;
    link.click();
}

async function exportarExcelBajas() {
    const response = await axios.post(route('bandeja.bajas.excel'), {
        datos: bajasExport.value,
        gestion: filtroGestion.value,
        mes: filtroMes.value,
        estado_baja: filtroEstadoBaja.value || undefined,
        distrito: filtroDistrito.value || undefined,
        ver_retro: filtroVerRetro.value ? 1 : undefined,
    }, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    // Mismo patrón de nombre que el PDF (useReporteBajasPDF.js).
    link.download = `Reporte_Bajas-${fechaArchivoBO()}.xlsx`;
    link.click();
}

// ============================================================================
// COMPUTED - EXCLUSIVIDAD ENTRE BLOQUES
// ============================================================================
const rangoActivo = computed(() => !!filtroDesde.value);
const gestionMesActivo = computed(() => !!filtroGestion.value || !!filtroMes.value);

// ============================================================================
// REFS - TAB ACTIVO (solo admin)
// ============================================================================
const defaultTab = can('bandeja-pago') ? 'resumen' : 'general';

// Se inicializa una sola vez. Nunca se toca desde afuera.
const tabActivo = ref((() => {
    const raw = page.props.filters?.tab
        || new URLSearchParams(window.location.search).get('tab')
        || defaultTab;
    return (!can('bandeja-pago') && raw === 'resumen') ? 'general' : raw;
})());

function cambiarTab(tab) {
    tabActivo.value = tab;
    // Distrito y estado de baja son filtros propios de cada tab: si no se
    // reinician acá, un valor elegido en un tab sigue viajando en las
    // siguientes peticiones (aunque su dropdown esté oculto) y termina
    // filtrando datos de un tab distinto al que lo seleccionó.
    filtroDistrito.value = '';
    filtroEstadoBaja.value = '';
    filtroIncluirAnulados.value = false;

    const params = { tab };
    if (filtroGestion.value) params.gestion_gestion = filtroGestion.value;
    if (filtroMes.value) params.mes = filtroMes.value;
    if (filtroDesde.value) params.fecha_desde = filtroDesde.value;
    if (filtroHasta.value) params.fecha_hasta = filtroHasta.value;
    if (filtroVerRetro.value) params.ver_retro = 1;

    router.get(route('bandeja.index'), params, {
        preserveState: true,
        preserveScroll: true,

    });
}

const hayFiltrosActivos = computed(() =>
    !!filtroGestion.value || !!filtroMes.value || !!filtroDesde.value || !!filtroDistrito.value || !!filtroEstadoBaja.value
);

function seleccionarDistrito(valor) {
    filtroDistrito.value = valor;
    cargando.value = true;

    const params = { tab: tabActivo.value, distrito: valor || undefined };
    if (filtroGestion.value) params.gestion_gestion = filtroGestion.value;
    if (filtroMes.value) params.mes = filtroMes.value;
    if (filtroDesde.value) params.fecha_desde = filtroDesde.value;
    if (filtroHasta.value) params.fecha_hasta = filtroHasta.value;
    if (filtroVerRetro.value) params.ver_retro = 1;
    if (filtroIncluirAnulados.value) params.incluir_anulados = 1;

    router.get(route('bandeja.index'), params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

/**
 * Switch "Anulados" del tab Total Pagados — muestra/oculta los pagos
 * anulados tanto en la tabla como en el PDF/Excel exportado (mismo
 * dataset, filtrado en el backend).
 */
function alternarIncluirAnulados(valor) {
    filtroIncluirAnulados.value = valor;
    cargando.value = true;

    const params = { tab: tabActivo.value, incluir_anulados: valor ? 1 : undefined };
    if (filtroGestion.value) params.gestion_gestion = filtroGestion.value;
    if (filtroMes.value) params.mes = filtroMes.value;
    if (filtroDesde.value) params.fecha_desde = filtroDesde.value;
    if (filtroHasta.value) params.fecha_hasta = filtroHasta.value;
    if (filtroVerRetro.value) params.ver_retro = 1;
    if (filtroDistrito.value) params.distrito = filtroDistrito.value;

    router.get(route('bandeja.index'), params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

function seleccionarEstadoBaja(valor) {
    filtroEstadoBaja.value = valor;
    cargando.value = true;

    const params = { tab: 'bajas', estado_baja: valor || undefined };
    if (filtroGestion.value) params.gestion_gestion = filtroGestion.value;
    if (filtroMes.value) params.mes = filtroMes.value;
    if (filtroVerRetro.value) params.ver_retro = 1;

    router.get(route('bandeja.index'), params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================
/**
 * Formatea un número como moneda en formato boliviano
 * @param {number} amount - Monto a formatear
 * @returns {string}
 */
const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat("es-BO", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount)}`;
};

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';

    const parts = dateTimeString.split(' ');
    const datePart = parts[0];
    const [year, month, day] = datePart.split('-').map(Number);

    const fecha = new Date(year, month - 1, day);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    if (parts.length > 1 && parts[1]) {
        const [hour, minute] = parts[1].split(':');
        return `${dateStr} - ${hour}:${minute}`;
    }

    return dateStr;
};

// ============================================================================
// FUNCIONES - FILTROS CON EXCLUSIVIDAD
// ============================================================================

/**
 * Aplica filtro por gestión y limpia rango de fechas
 */
function seleccionarGestion(valor) {
    filtroGestion.value = valor;
    filtroMes.value = '';
    filtroDesde.value = '';
    filtroHasta.value = '';
    filtroVerRetro.value = false;
    filtroDistrito.value = '';
    filtroEstadoBaja.value = '';
    filtroIncluirAnulados.value = false;
    buscado.value = false;
    cargando.value = true;

    router.get(route('bandeja.index'), {
        gestion_gestion: valor,
        tab: tabActivo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

/**
 * Limpia todos los filtros activos y recarga la vista
 */
function limpiarFiltros() {
    filtroGestion.value = '';
    filtroMes.value = '';
    filtroDesde.value = '';
    filtroHasta.value = '';
    filtroDistrito.value = '';
    filtroEstadoBaja.value = '';
    filtroVerRetro.value = false;
    filtroIncluirAnulados.value = false;
    buscado.value = false;
    cargando.value = true;

    router.get(route('bandeja.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

/**
 * Aplica filtro por mes y limpia rango de fechas
 */
function seleccionarMes(valor) {
    filtroMes.value = valor;
    filtroDesde.value = '';
    filtroHasta.value = '';
    buscado.value = true;
    cargando.value = true;

    if (filtroGestion.value && valor) {
        router.get(route('bandeja.index'), {
            gestion_gestion: filtroGestion.value,
            mes: valor,
            tab: tabActivo.value,
            ver_retro: filtroVerRetro.value ? 1 : undefined,
            distrito: filtroDistrito.value || undefined,
            incluir_anulados: filtroIncluirAnulados.value ? 1 : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { cargando.value = false },
        });
    }
}

/**
 * Alterna entre ver el mes normal o su mes-retro correspondiente. Solo
 * tiene efecto si ya hay gestión y mes seleccionados.
 */
function alternarVerRetro(valor) {
    filtroVerRetro.value = valor;
    if (!filtroGestion.value || !filtroMes.value) return;

    cargando.value = true;
    router.get(route('bandeja.index'), {
        gestion_gestion: filtroGestion.value,
        mes: filtroMes.value,
        tab: tabActivo.value,
        ver_retro: valor ? 1 : undefined,
        distrito: filtroDistrito.value || undefined,
        incluir_anulados: filtroIncluirAnulados.value ? 1 : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { cargando.value = false },
    });
}

/**
 * Aplica filtro por rango de fechas y limpia gestión y mes
 */
function onRangoChange() {
    filtroGestion.value = '';
    filtroMes.value = '';
    buscado.value = true;

    if (filtroDesde.value && filtroHasta.value) {
        cargando.value = true;

        const params = {
            fecha_desde: filtroDesde.value,
            fecha_hasta: filtroHasta.value,
            tab: tabActivo.value,
        };
        if (filtroDistrito.value) params.distrito = filtroDistrito.value;
        if (filtroIncluirAnulados.value) params.incluir_anulados = 1;

        router.get(route('bandeja.index'), params, {
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
const cantidadAnuladosMisPagos = computed(() =>
    misPagosTodos.value.filter(p => parseFloat(p.pago) === 0).length
);
const pagosPorUsuario = computed(() => page.props.pagosPorUsuario ?? {});
const resumenGeneralData = computed(() => resumenGeneral.value?.data ?? resumenGeneral.value ?? []);
const pagosTodos = computed(() => page.props.pagosTodos?.data ?? []);
const noPagados = computed(() => page.props.noPagados ?? []);
const cantidadNoPagados = computed(() => page.props.cantidadNoPagados ?? 0);
const bajas = computed(() => page.props.bajas ?? []);
const cantidadBajas = computed(() => page.props.cantidadBajas ?? 0);
const montoNoPagados = computed(() => page.props.montoNoPagados ?? 0);
const montoBajas = computed(() => page.props.montoBajas ?? 0);
const montoTotalGeneral = computed(() => page.props.montoTotalPagosTodos ?? 0);
const cantidadPagosValidos = computed(() => page.props.cantidadPagosTodosValidos ?? 0);
const totalPagosTodos = computed(() => page.props.pagosTodos?.total ?? 0);

const mesLabel = computed(() =>
    mesesMap.find(m => m.value === filtroMes.value)?.text ?? 'Sin seleccionar'
);

const hayDatosParaReporte = computed(() => {
    if (can('bandeja-pago') && tabActivo.value === 'resumen') {
        return resumenGeneral.value?.length > 0 && buscado.value;
    }
    if (tabActivo.value === 'mispagos') {
        return misPagosTodos.value?.length > 0;
    }
    return false;
});

// ============================================================================
// COLUMNAS DE TABLAS
// ============================================================================
const columnasIndividual = [
    { key: 'nro', label: 'N°', headerClass: 'text-center' },
    { key: 'nombre_boleta', label: 'N° Boleta', headerClass: 'text-center' },
    { key: 'nombre_completo', label: 'Nombre Completo' },
    { key: 'ci_persona', label: 'C.I.', headerClass: 'text-center' },
    { key: 'distrito', label: 'Distrito', headerClass: 'text-center' },
    { key: 'mes_pago', label: 'Mes de Pago', headerClass: 'whitespace-nowrap text-center', },
    { key: 'monto', label: 'Monto Pago (Bs)', headerClass: 'whitespace-nowrap text-center', },
    { key: 'fecha_pago', label: 'Fecha de Pago', headerClass: 'whitespace-nowrap text-center', },
];

const columnasGeneral = computed(() => {
    const cols = [
        { key: 'nro', label: 'N°', headerClass: 'text-center' },
        { key: 'numero_boleta', label: 'N° Boleta', headerClass: 'text-center' },
        { key: 'nombre_completo', label: 'Nombre Completo' },
        { key: 'ci_persona', label: 'C.I.', headerClass: 'text-center' },
        { key: 'distrito', label: 'Distrito', headerClass: 'text-center' },
        { key: 'monto', label: 'Monto Pagado', headerClass: 'whitespace-nowrap text-center' },
        { key: 'fecha_pago', label: 'Fecha de Pago', headerClass: 'whitespace-nowrap text-center' },
        { key: 'usuario_pagador', label: 'Usuario Pagador', headerClass: 'whitespace-nowrap text-center' },
    ];

    if (can('acciones-superusuario') || can('anular-pago')) {
        cols.push({ key: 'acciones', label: 'Acciones', headerClass: 'text-center' });
    }

    return cols;
});

const columnasNoPagados = [
    { key: 'nro', label: 'N°', headerClass: 'text-center' },
    { key: 'nombre_completo', label: 'Nombre Completo' },
    { key: 'ci_persona', label: 'C.I.', headerClass: '' },
    { key: 'distrito', label: 'Distrito', headerClass: 'text-center' },
    { key: 'observaciones_habilitado', label: 'Observaciones' },
    { key: 'fecha_habilitado', label: 'Fecha Habilitado', headerClass: 'whitespace-nowrap text-center' },
];

const columnasBajas = [
    { label: 'Nº', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'C.I.', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'Apellidos y nombres P.C.D.' },
    { label: 'Grado de discapacidad', headerClass: 'text-center whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Monto a Pagar (bs.)', headerClass: 'text-center whitespace-nowrap', cellClass: '' },
    { label: 'Observaciones', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
];

const columnasResumen = [
    { key: 'nro', label: 'N°', headerClass: 'text-center' },
    { key: 'nombre_usuario', label: 'Nombre del Usuario', headerClass: 'whitespace-nowrap' },
    { key: 'cantidad', label: 'Cantidad Beneficiarios', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Cant. Beneficiarios' },
    { key: 'monto_unitario', label: 'Monto Unitario (Bs)', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Monto Unitario' },
    { key: 'total', label: 'Total Monto (Bs)', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Total Monto' },
    { key: 'asignado', label: 'Presupuesto Asignado', headerClass: 'whitespace-nowrap text-center', mobileLabel: 'Asignado' },
    { key: 'restante', label: 'Restante', headerClass: 'whitespace-nowrap text-center' },
    /* { key: 'acciones', label: 'Acciones', headerClass: 'text-center' }, */
];

// ============================================================================
// REFS - MENSAJES
// ============================================================================
const mensajes = ref([]);

// ============================================================================
// FUNCIONES - ACCIONES
// ============================================================================

/**
 * Registra el log y genera el PDF de arqueo de caja del usuario actual
 */
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

/** Igual que generarArqueo(), pero en Excel — mismos datos ya calculados para el PDF */
async function exportarExcelArqueo() {
    registrarLog({
        tipo: 'individual',
        gestion: filtroGestion.value,
        mes: mesLabel.value,
        usuario: `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`,
        total_pagos: cantidadMisPagos.value,
    });

    const response = await axios.post(route('bandeja.arqueo.excel'), {
        datos: {
            cantidad_total_pagos: cantidadMisPagos.value,
            cantidad_anulados: cantidadAnuladosMisPagos.value,
            monto_pago: page.props.montoMes ?? 0,
            total_pagado: montoTotalIndividual.value,
            presupuesto_asignado: page.props.misPresupuestoAsignado ?? 0,
            gestion: filtroGestion.value,
            mes: filtroMes.value,
        },
        usuario: `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`,
        cargo: page.props.auth.user.cargo,
    }, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = `Arqueo_Caja-${fechaArchivoBO()}.xlsx`;
    link.click();
}

/**
 * Registra en el servidor un log de la generación de un reporte vía GET
 * @param {Object} params - Parámetros del log (tipo, gestión, mes, usuario, totales)
 */
function registrarLog(params) {
    fetch(route('bandeja.reporteLog') + '?' + new URLSearchParams(params).toString(), {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
}

/**
 * Genera el PDF individual de pagos para un usuario del resumen
 */
function generarPDFDeUsuario(item) {
    const datosPagos = pagosPorUsuario.value[item.id] ?? [];
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

/**
 * Abre el modal de confirmación para anular un pago
 */
function anularPago(idPago) {
    pagoSeleccionado.value = { id: idPago };
    observacionAnulacion.value = '';
    modalAnular.value = true;
}

/**
 * Confirma y ejecuta la anulación del pago seleccionado.
 */
async function confirmarAccionPago() {
    if (procesandoPago.value || !observacionAnulacion.value.trim()) return;
    procesandoPago.value = true;

    const { id } = pagoSeleccionado.value;
    try {
        await axios.patch(`/pago/pagos/${id}/anular`, {
            observaciones: observacionAnulacion.value.trim(),
        });
        modalAnular.value = false;
        router.reload({ preserveScroll: true });
    } catch (error) {
        modalAnular.value = false;
        alert(error.response?.data?.message ?? 'Error al anular el pago.');
    } finally {
        procesandoPago.value = false;
    }
}

function generarReporteTodos() {
    generarPDFTodos(
        pagosTodosExport.value,
        filtroGestion.value,
        filtroMes.value,
        filtroDesde.value,
        filtroHasta.value,
        filtroDistrito.value,
        filtroVerRetro.value,
    );
}

/** Igual que generarReporteTodos(), pero en Excel */
async function exportarExcelTotalPagados() {
    const response = await axios.post(route('bandeja.todos.excel'), {
        datos: pagosTodosExport.value,
        gestion: filtroGestion.value,
        mes: filtroMes.value,
        fecha_desde: filtroDesde.value || undefined,
        fecha_hasta: filtroHasta.value || undefined,
        distrito: filtroDistrito.value || undefined,
        ver_retro: filtroVerRetro.value ? 1 : undefined,
    }, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = `Reporte_TotalPagados-${fechaArchivoBO()}.xlsx`;
    link.click();
}

/**
 * Cierra el modal de confirmación y limpia el pago seleccionado
 */
function closeModal() {
    modalAnular.value = false;
    pagoSeleccionado.value = null;
    observacionAnulacion.value = '';
}

/**
 * Abre el modal de confirmación para reactivar un pago anulado
 */
function reactivarPago(idPago) {
    pagoSeleccionado.value = { id: idPago };
    modalReactivar.value = true;
}

/**
 * Confirma y ejecuta la reactivación del pago seleccionado
 */
async function confirmarReactivarPago() {
    if (procesandoPago.value) return;
    procesandoPago.value = true;

    const { id } = pagoSeleccionado.value;
    try {
        await axios.patch(`/pago/pagos/${id}/reactivar`);
        modalReactivar.value = false;
        router.reload({ preserveScroll: true });
    } catch (error) {
        modalReactivar.value = false;
        alert(error.response?.data?.message ?? 'Error al reactivar el pago.');
    } finally {
        procesandoPago.value = false;
    }
}

/**
 * Cierra el modal de confirmación de reactivación
 */
function closeModalReactivar() {
    modalReactivar.value = false;
    pagoSeleccionado.value = null;
}

/**
 * Genera el PDF de reporte según el tab activo y los filtros aplicados
 */
function generarReporte() {
    const filtrosBase = filtroDesde.value
        ? { fecha_desde: filtroDesde.value, fecha_hasta: filtroHasta.value }
        : { gestion: filtroGestion.value, mes: mesLabel.value };

    if (can('bandeja-pago') && tabActivo.value === 'resumen') {
        registrarLog({
            tipo: 'resumen',
            ...filtrosBase,
            total_beneficiarios: totalBeneficiarios.value,
            total_monto: totalMonto.value,
        });
        generarPDFResumen(
            resumenGeneralData.value,
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

/** Igual que generarReporte(), pero en Excel — misma distinción resumen/individual por tab */
async function generarReporteExcel() {
    const esResumen = can('bandeja-pago') && tabActivo.value === 'resumen';

    if (esResumen) {
        registrarLog({
            tipo: 'resumen',
            ...(filtroDesde.value
                ? { fecha_desde: filtroDesde.value, fecha_hasta: filtroHasta.value }
                : { gestion: filtroGestion.value, mes: mesLabel.value }),
            total_beneficiarios: totalBeneficiarios.value,
            total_monto: totalMonto.value,
        });

        const response = await axios.post(route('bandeja.resumen.excel'), {
            datos: resumenGeneralData.value,
            gestion: filtroGestion.value,
            mes: filtroMes.value,
            usuario: page.props.auth.user.name,
            fecha_desde: filtroDesde.value || undefined,
            fecha_hasta: filtroHasta.value || undefined,
        }, { responseType: 'blob' });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.download = `Reporte_ResumenGeneral-${fechaArchivoBO()}.xlsx`;
        link.click();
    } else {
        const nombreReporte = `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`;
        registrarLog({
            tipo: 'individual',
            ...(filtroDesde.value
                ? { fecha_desde: filtroDesde.value, fecha_hasta: filtroHasta.value }
                : { gestion: filtroGestion.value, mes: mesLabel.value }),
            usuario: nombreReporte,
            total_pagos: misPagosTodos.value?.length ?? 0,
        });

        const response = await axios.post(route('bandeja.individual.excel'), {
            datos: misPagosTodos.value,
            gestion: filtroGestion.value,
            mes: filtroMes.value,
            usuario: nombreReporte,
            fecha_desde: filtroDesde.value || undefined,
            fecha_hasta: filtroHasta.value || undefined,
        }, { responseType: 'blob' });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.download = `Reporte_MisPagos-${fechaArchivoBO()}.xlsx`;
        link.click();
    }
}

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

/**
 * Muestra el tooltip posicionado debajo del botón indicado
 */
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

/**
 * Oculta el tooltip activo
 */
function hideTooltip() { showTooltipFlag.value = false; }

const toTitleCase = (str) => {
    if (!str) return ''

    return str
        .toLocaleLowerCase('es')
        .split(' ')
        .map(word =>
            word.charAt(0).toLocaleUpperCase('es') + word.slice(1)
        )
        .join(' ')
}

</script>

<template>
    <AppLayout>

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Bandeja de Pagos</h1>
            <Rutas label1="Inicio" label3="Bandeja de Pagos" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- FILTROS + RESUMEN + BOTONES -->
        <!-- ============================================================================ -->
        <div class="mx-0">
            <div
                class="p-1 py-0 sm:p-3 sm:pt-1 bg-gray-50 border-x-2 border-t-2 border-gray-200 rounded-t-lg mr-1 shadow-sm">

                <!-- Fila principal: filtros + acciones -->
                <div class="sm:flex flex-wrap space-y-2 gap-2">
                    <!-- ── Bloque Gestión + Mes ── -->
                    <Transition name="filtro">
                        <div v-if="!rangoActivo"
                            class="flex flex-wrap sm:flex-nowrap items-center mt-2 gap-1.5 px-3 py-2 rounded-xl border border-purple-100 bg-purple-50/40 transition-all duration-200">

                            <!-- Gestión -->
                            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                <label
                                    class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Gestión</label>
                                <Dropdown align="left" width="48">
                                    <template #trigger="{ open }">
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-full sm:w-32 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="filtroGestion
                                                ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]'
                                                : 'text-slate-400'" type="button">
                                            <span class="flex-1 text-left truncate">{{ filtroGestion || 'Gestión'
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
                                                <li v-for="g in gestionesOptions" :key="g.value">
                                                    <a href="#" @click.prevent="seleccionarGestion(g.value)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150 group"
                                                        :class="filtroGestion === g.value
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        <span class="flex items-center leading-none gap-1">
                                                            <Icon :icon-button="true" name="calendar"
                                                                class-name="text-slate-400 block" fill="none"
                                                                stroke="currentColor" stroke-width="1" :size="18" />
                                                            {{ g.text }}
                                                        </span>
                                                        <Icon v-if="filtroGestion === g.value" :icon-button="true"
                                                            name="check" class-name="text-[rgb(var(--brand-700))]"
                                                            :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <div class="w-px h-8 mt-4 bg-[rgb(var(--brand-300))]"></div>

                            <!-- Mes -->
                            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                <label class="text-[10px] font-bold uppercase tracking-widest pl-1"
                                    :class="filtroGestion ? 'text-[rgb(var(--brand-400))]' : 'text-gray-300'">Mes</label>
                                <Dropdown align="right" width="48">
                                    <template #trigger="{ open }">
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-full sm:w-32 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="filtroMes
                                                ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))] hover:bg-gray-50 hover:shadow'
                                                : filtroGestion && mesesOptions.length > 0
                                                    ? 'text-gray-600 cursor-pointer'
                                                    : 'text-slate-400 cursor-not-allowed opacity-60'" type="button"
                                            :disabled="!filtroGestion">
                                            <span class="flex-1 text-left truncate">
                                                {{ mesLabel !== 'Sin seleccionar' ? mesLabel : 'Mes' }}
                                            </span>
                                            <Icon :icon-button="true" name="angleDown"
                                                :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                                fill="none" stroke="currentColor" stroke-width="2" :size="17"
                                                @click="isOpen = !isOpen" />
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li v-for="m in mesesOptions" :key="m.value">
                                                    <a href="#" @click.prevent="seleccionarMes(m.value)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150 group"
                                                        :class="filtroMes === m.value
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        <span class="flex items-center leading-none gap-1">
                                                            <Icon :icon-button="true" name="calendar"
                                                                class-name="text-slate-400 block" fill="none"
                                                                stroke="currentColor" stroke-width="1" :size="18" />
                                                            {{ m.text }}
                                                        </span>
                                                        <Icon v-if="filtroMes === m.value" :icon-button="true"
                                                            name="check" class-name="text-[rgb(var(--brand-700))]"
                                                            :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Retroactivo: switch aparte, no debajo del mes (solo enero-octubre) -->
                            <template v-if="filtroGestion && filtroMes && mesEnRangoRetro && retroactivosHabilitadoGestion">
                                <div class="w-px h-8 mt-4 bg-[rgb(var(--brand-300))]"></div>
                                <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                    <label class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Retroactivo</label>
                                    <button type="button" @click="alternarVerRetro(!filtroVerRetro)"
                                        class="inline-flex items-center gap-2 px-3 py-1 rounded-xl border transition-all w-full sm:w-32 shadow-sm hover:shadow"
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
                                    <p v-if="filtroVerRetro && !mesRetroDisponible && !cargando" class="text-[11px] text-amber-600">
                                        Todavía no se cargó el retroactivo de este mes.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </Transition>

                    <!-- ── Separador O — solo visible si ninguno está activo ── -->
                    <Transition name="filtro">
                        <div v-if="!hayFiltrosActivos"
                            class="flex items-center justify-center shrink-0 w-full lg:w-auto">
                            <span
                                class="text-[10px] font-black text-gray-500 bg-gray-200 rounded-full w-5 h-5 flex items-center justify-center">O</span>
                        </div>
                    </Transition>

                    <!-- ── Bloque Rango de Fechas ── -->
                    <Transition name="filtro">
                        <div v-if="!gestionMesActivo"
                            class="flex flex-wrap sm:flex-nowrap items-center mt-2 gap-1.5 px-3 py-2 rounded-xl border border-purple-100 bg-purple-50/40 transition-all duration-200">

                            <!-- Desde -->
                            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                <label
                                    class="text-[10px] font-bold text-purple-400 uppercase tracking-widest pl-1">Desde</label>
                                <div class="w-full sm:w-32 flex-1">
                                    <Input inputType="date" inputClass="py-1.5" v-model="filtroDesde"
                                        placeholder="Desde" focusColor="purple" @update:modelValue="onRangoChange" />
                                </div>
                            </div>

                            <div class="w-px h-8 mt-4 bg-purple-300"></div>

                            <!-- Hasta -->
                            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                <label
                                    class="text-[10px] font-bold text-purple-400 uppercase tracking-widest pl-1">Hasta</label>
                                <div class="w-full sm:w-32 flex-1">
                                    <Input inputType="date" inputClass="py-1.5" v-model="filtroHasta"
                                        placeholder="Hasta" focusColor="purple" @update:modelValue="onRangoChange" />
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <div v-if="buscado && (tabActivo === 'general' || tabActivo === 'mispagos')"
                        class="hidden sm:flex w-px h-8 self-center mt-2 bg-[rgb(var(--brand-300))]"></div>

                    <Transition name="filtro">
                        <div v-if="buscado && (tabActivo === 'general' || tabActivo === 'mispagos')"
                            class="flex flex-wrap sm:flex-nowrap items-center mt-2 gap-1.5 px-3 py-2 rounded-xl border border-purple-100 bg-purple-50/40 transition-all duration-200">
                            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                <label
                                    class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Distrito</label>
                                <Dropdown align="left" width="48">
                                    <template #trigger="{ open }">
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-full sm:w-32 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="filtroDistrito ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]' : 'text-slate-400'"
                                            type="button">
                                            <span class="flex-1 text-left truncate">{{ filtroDistrito || 'Distrito'
                                                }}</span>
                                            <Icon :icon-button="true" name="angleDown"
                                                :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                                fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li>
                                                    <a href="#" @click.prevent="seleccionarDistrito('')"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="!filtroDistrito ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]' : 'text-slate-500 italic'">
                                                        Todos
                                                    </a>
                                                </li>
                                                <li v-for="d in distritos" :key="d">
                                                    <a href="#" @click.prevent="seleccionarDistrito(d)"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="filtroDistrito === d
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        {{ d }}
                                                        <Icon v-if="filtroDistrito === d" :icon-button="true"
                                                            name="check" class-name="text-[rgb(var(--brand-700))]"
                                                            :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Anulados: muestra/oculta los pagos anulados en la tabla y en el PDF/Excel exportado -->
                            <template v-if="tabActivo === 'general'">
                                <div class="w-px h-8 mt-4 bg-purple-300"></div>
                                <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                    <label class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Mostrar</label>
                                    <button type="button" @click="alternarIncluirAnulados(!filtroIncluirAnulados)"
                                        class="inline-flex items-center gap-2 px-3 py-1 rounded-xl border transition-all w-full sm:w-32 shadow-sm hover:shadow"
                                        :class="filtroIncluirAnulados
                                            ? 'bg-red-50 border-red-300 text-red-700 hover:bg-red-100'
                                            : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'">
                                        <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 flex-shrink-0"
                                            :class="filtroIncluirAnulados ? 'bg-red-500' : 'bg-gray-300'">
                                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                                                :class="filtroIncluirAnulados ? 'translate-x-4' : 'translate-x-1'"></span>
                                        </span>
                                        <span class="text-sm font-semibold truncate">Anulados</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </Transition>

                    <Transition name="filtro">
                        <div v-if="tabActivo === 'bajas' && buscado"
                            class="flex flex-wrap sm:flex-nowrap items-center mt-2 gap-1.5 px-3 py-2 rounded-xl border border-purple-100 bg-purple-50/40 transition-all duration-200">
                            <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                <label class="text-[10px] font-bold text-[rgb(var(--brand-400))] uppercase tracking-widest pl-1">Tipo
                                    de Baja</label>
                                <Dropdown align="left" width="48">
                                    <template #trigger="{ open }">
                                        <button
                                            class="inline-flex items-center gap-2 px-3 py-1 text-sm font-semibold rounded-xl transition-all w-full sm:w-32 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="filtroEstadoBaja ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]' : 'text-slate-400'"
                                            type="button">
                                            <span class="flex-1 text-left truncate">
                                                {{ filtroEstadoBaja === 'baja_temporal' ? 'Baja Temporal'
                                                    : filtroEstadoBaja === 'baja_definitiva' ? 'Baja Definitiva' : 'Todos'
                                                }}
                                            </span>
                                            <Icon :icon-button="true" name="angleDown"
                                                :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                                fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li>
                                                    <a href="#" @click.prevent="seleccionarEstadoBaja('')"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="!filtroEstadoBaja ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]' : 'text-slate-500 italic'">
                                                        Todos
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" @click.prevent="seleccionarEstadoBaja('baja_temporal')"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="filtroEstadoBaja === 'baja_temporal'
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        Baja Temporal
                                                        <Icon v-if="filtroEstadoBaja === 'baja_temporal'"
                                                            :icon-button="true" name="check" class-name="text-[rgb(var(--brand-700))]"
                                                            :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>

                                                </li>
                                                <li>
                                                    <a href="#"
                                                        @click.prevent="seleccionarEstadoBaja('baja_definitiva')"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="filtroEstadoBaja === 'baja_definitiva'
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        Baja Definitiva
                                                        <Icon v-if="filtroEstadoBaja === 'baja_definitiva'"
                                                            :icon-button="true" name="check" class-name="text-[rgb(var(--brand-700))]"
                                                            :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </Transition>

                    <!-- ── Botón Limpiar ── -->
                    <button v-if="hayFiltrosActivos" @click="limpiarFiltros"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-500 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 hover:border-red-300 hover:text-red-700 transition-all duration-200 w-full lg:w-auto h-fit lg:self-end lg:mb-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>

                    <!-- ── Spacer + Cards + PDF (se van al final del wrap) ── -->
                    <div class="flex items-center gap-2 w-full lg:w-auto pb-1 sm:pb-0 lg:ml-auto flex-wrap">

                        <!-- Cards de totales -->
                        <!-- Admin · Tab Resumen General -->
                        <template v-if="can('bandeja-pago') && tabActivo === 'resumen'">
                            <div v-if="totalBeneficiarios > 0 && buscado"
                                class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Beneficiarios</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">
                                        {{ totalBeneficiarios }}</p>
                                </div>
                            </div>
                            <div v-if="totalMonto > 0 && buscado"
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
                                        {{ formatCurrency(totalMonto) }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                            <div v-if="cantidadNoPagados > 0 && buscado"
                                class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-red-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-red-500 uppercase tracking-widest font-bold leading-tight">
                                        No Pagados</p>
                                    <p class="text-sm font-black text-red-700 leading-tight">
                                        {{ cantidadNoPagados }}</p>
                                </div>
                            </div>
                        </template>

                        <!-- Tab Total Pagados — todos -->
                        <template v-else-if="tabActivo === 'general'">
                            <div v-if="totalPagosTodos > 0 && buscado"
                                class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Total Pagos</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">
                                        {{ cantidadPagosValidos }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="totalPagosTodos > 0 && buscado"
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
                                        {{ formatCurrency(montoTotalGeneral) }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Cajero o admin en Tab Mis Pagos -->
                        <template v-else-if="tabActivo === 'mispagos'">
                            <div v-if="cantidadMisPagos > 0 && buscado"
                                class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Beneficiarios</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">{{ cantidadMisPagos }}
                                    </p>
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
                                        {{ formatCurrency(montoTotalIndividual) }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template v-else-if="tabActivo === 'no_pagados'">
                            <div v-if="cantidadNoPagados > 0 && buscado"
                                class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-red-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-red-500 uppercase tracking-widest font-bold leading-tight">
                                        Total No Pagados</p>
                                    <p class="text-sm font-black text-red-700 leading-tight">
                                        {{ cantidadNoPagados }}</p>
                                </div>
                            </div>
                            <div v-if="montoNoPagados > 0 && buscado"
                                class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div
                                    class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="cash" class-name="text-emerald-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                        Monto No Pagado</p>
                                    <p class="text-sm font-black text-emerald-700 leading-tight">
                                        {{ formatCurrency(montoNoPagados) }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template v-else-if="tabActivo === 'bajas'">
                            <div v-if="cantidadBajas > 0 && buscado"
                                class="flex items-center gap-2 bg-orange-50 border border-orange-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div class="w-6 h-6 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="users" class-name="text-orange-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-orange-500 uppercase tracking-widest font-bold leading-tight">
                                        Total Bajas</p>
                                    <p class="text-sm font-black text-orange-700 leading-tight">
                                        {{ cantidadBajas }}</p>
                                </div>
                            </div>
                            <div v-if="montoBajas > 0 && buscado"
                                class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 flex-1 lg:flex-none">
                                <div
                                    class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="cash" class-name="text-emerald-600" :size="13" />
                                </div>
                                <div>
                                    <p
                                        class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                        Monto en Bajas</p>
                                    <p class="text-sm font-black text-emerald-700 leading-tight">
                                        {{ formatCurrency(montoBajas) }}
                                        <span class="text-[10px] font-semibold">Bs</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Botón Reporte (PDF/Excel) — Resumen General y Mis Pagos.
                             Mismo estilo que el botón de Total Pagados (círculo gris con
                             animación roja al hover, sin texto). -->
                        <Dropdown v-if="hayDatosParaReporte" align="right" width="48">
                            <template #trigger>
                                <Button id="btn-reporte" @mouseenter="showTooltip('Reporte', 'btn-reporte')"
                                    @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
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
                                <div class="shadow-xl overflow-hidden py-1">
                                    <a href="#" @click.prevent="generarReporte()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                        Reporte PDF
                                    </a>
                                    <a href="#" @click.prevent="generarReporteExcel()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                        Reporte Excel
                                    </a>
                                </div>
                            </template>
                        </Dropdown>

                        <!-- Botón Reporte (PDF/Excel) — Total Pagados -->
                        <Dropdown v-if="(page.props.pagosTodos?.total ?? 0) > 0 && buscado && tabActivo === 'general'"
                            align="right" width="48">
                            <template #trigger>
                                <Button id="btn-reporte-todos" @mouseenter="showTooltip('Reporte', 'btn-reporte-todos')"
                                    @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
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
                                <div class="shadow-xl overflow-hidden py-1">
                                    <a href="#" @click.prevent="generarReporteTodos()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                        Reporte PDF
                                    </a>
                                    <a href="#" @click.prevent="exportarExcelTotalPagados()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                        Reporte Excel
                                    </a>
                                </div>
                            </template>
                        </Dropdown>

                        <!-- Botón Cerrar Caja / Arqueo (PDF/Excel) — Mis Pagos.
                             Texto + color azul para distinguirlo de "Reporte" (rojo). -->
                        <Dropdown
                            v-if="cantidadMisPagos > 0 && buscado && filtroGestion && filtroMes && tabActivo === 'mispagos'"
                            align="right" width="48">
                            <template #trigger>
                                <Button id="btn-arqueo" @mouseenter="showTooltip('Reporte Caja', 'btn-arqueo')"
                                    @mouseleave="hideTooltip"
                                    :style="'flex items-center gap-1.5 px-3 py-2 rounded-full border-none'"
                                    class="bg-[rgb(var(--brand-50))] hover:bg-[rgb(var(--brand-100))] shrink-0 transition-colors">
                                    <Icon :icon-button="true" name="circlePlus" fill="currentColor" class-name="text-[rgb(var(--brand-600))]"
                                        :size="17" />
                                    <span class="text-xs font-semibold text-[rgb(var(--brand-700))]">Cerrar Caja</span>
                                </Button>
                            </template>
                            <template #content>
                                <div class="shadow-xl overflow-hidden py-1">
                                    <a href="#" @click.prevent="generarArqueo()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                        Reporte PDF
                                    </a>
                                    <a href="#" @click.prevent="exportarExcelArqueo()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                        Reporte Excel
                                    </a>
                                </div>
                            </template>
                        </Dropdown>
                        <Dropdown v-if="tabActivo === 'no_pagados' && cantidadNoPagados > 0" align="right" width="48">
                            <template #trigger>
                                <Button id="btn-reporte-no-pagados"
                                    @mouseenter="showTooltip('Reporte', 'btn-reporte-no-pagados')"
                                    @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
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
                                <div class="shadow-xl overflow-hidden py-1">
                                    <a href="#" @click.prevent="generarReporteNoPagados()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                        Reporte PDF
                                    </a>
                                    <a href="#" @click.prevent="exportarExcelNoPagados()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                        Reporte Excel
                                    </a>
                                </div>
                            </template>
                        </Dropdown>

                        <Dropdown v-if="tabActivo === 'bajas' && cantidadBajas > 0" align="right" width="48">
                            <template #trigger>
                                <Button id="btn-reporte-bajas"
                                    @mouseenter="showTooltip('Reporte', 'btn-reporte-bajas')"
                                    @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
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
                                <div class="shadow-xl overflow-hidden py-1">
                                    <a href="#" @click.prevent="generarReporteBajas()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                        Reporte PDF
                                    </a>
                                    <a href="#" @click.prevent="exportarExcelBajas()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                        <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                        Reporte Excel
                                    </a>
                                </div>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <!-- Tooltip -->
                <div v-if="showTooltipFlag"
                    class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                    :style="tooltipStyle">
                    {{ tooltipText }}
                </div>
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- TABS — solo visibles para el admin -->
        <!-- ============================================================================ -->

        <div v-if="buscado" class="flex border-x-2 border-gray-200 bg-white mr-1 overflow-x-auto min-w-0">
            <button v-if="can('bandeja-pago')" @click="cambiarTab('resumen')" :class="[
                'flex items-center gap-2 px-5 py-2 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200 whitespace-nowrap',
                tabActivo === 'resumen'
                    ? 'border-[rgb(var(--brand-500))] text-[rgb(var(--brand-600))] bg-[rgb(var(--brand-50))]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]">
                <Icon :icon-button="true" name="clipboardList" :size="16"
                    :class-name="tabActivo === 'resumen' ? 'text-[rgb(var(--brand-500))]' : 'text-gray-400'" />
                Resumen General
            </button>

            <button @click="cambiarTab('general')" :class="[
                'flex items-center gap-2 px-5 py-2 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200 whitespace-nowrap',
                tabActivo === 'general'
                    ? 'border-[rgb(var(--brand-500))] text-[rgb(var(--brand-600))] bg-[rgb(var(--brand-50))]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]">
                <Icon :icon-button="true" name="clipboardList" :size="16"
                    :class-name="tabActivo === 'general' ? 'text-[rgb(var(--brand-500))]' : 'text-gray-400'" />
                Total Pagados
            </button>
            <button v-if="can('bandeja-pago')" @click="cambiarTab('no_pagados')" :class="[
                'flex items-center gap-2 px-5 py-2 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200 whitespace-nowrap',
                tabActivo === 'no_pagados'
                    ? 'border-[rgb(var(--brand-500))] text-[rgb(var(--brand-600))] bg-[rgb(var(--brand-50))]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]">
                <Icon :icon-button="true" name="clipboardList" :size="16"
                    :class-name="tabActivo === 'no_pagados' ? 'text-[rgb(var(--brand-500))]' : 'text-gray-400'" />
                No Pagados
            </button>

            <button v-if="can('bandeja-pago') && !rangoActivo" @click="cambiarTab('bajas')" :class="[
                'flex items-center gap-2 px-5 py-2 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200 whitespace-nowrap',
                tabActivo === 'bajas'
                    ? 'border-[rgb(var(--brand-500))] text-[rgb(var(--brand-600))] bg-[rgb(var(--brand-50))]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]">
                <Icon :icon-button="true" name="clipboardList" :size="16"
                    :class-name="tabActivo === 'bajas' ? 'text-[rgb(var(--brand-500))]' : 'text-gray-400'" />
                Bajas
            </button>
            <button v-if="usuarioTienePagos" @click="cambiarTab('mispagos')" :class="[
                'flex items-center gap-2 px-5 py-2 sm:py-2.5 text-sm font-semibold border-b-2 transition-colors duration-200 whitespace-nowrap',
                tabActivo === 'mispagos'
                    ? 'border-[rgb(var(--brand-500))] text-[rgb(var(--brand-600))] bg-[rgb(var(--brand-50))]'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]">
                <Icon :icon-button="true" name="user" :size="16"
                    :class-name="tabActivo === 'mispagos' ? 'text-[rgb(var(--brand-500))]' : 'text-gray-400'" />
                Mis Pagos
            </button>
        </div>

        <!-- ============================================================================ -->
        <!-- TABLA: RESUMEN GENERAL (admin · tab resumen) -->
        <!-- ============================================================================ -->
        <template v-if="can('bandeja-pago') && tabActivo === 'resumen'">
            <DataTable :data="resumenGeneralData" :columns="columnasResumen" row-key="nro"
                :on-row-click="abrirModalUsuario"
                empty-message="No se encontraron datos para los filtros seleccionados.">
                <template #row="{ item, index }">

                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span class="text-sm">{{ (resumenGeneral?.from || 1) + index }}</span>
                    </td>

                    <td class="px-1 py-1 whitespace-nowrap">
                        <div class="font-medium text-gray-900 uppercase">{{ item.nombre }} {{ item.apellido }}</div>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))]">
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

                    <!-- Asignado/Restante: solo tienen sentido con filtro Gestión+Mes
                         (un presupuesto vive en un mes concreto) — con rango de fechas
                         item.presupuesto_asignado no viene del backend.
                         Number(...) porque DB::raw (COALESCE) vuelve como string ("0"),
                         que en JS es truthy — sin esto "Sin asignar" nunca se mostraba. -->
                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span v-if="Number(item.presupuesto_asignado) > 0" class="text-gray-700">
                            {{ Number(item.presupuesto_asignado).toLocaleString('es-BO') }} Bs
                        </span>
                        <span v-else-if="item.presupuesto_asignado !== null && item.presupuesto_asignado !== undefined"
                            class="text-gray-400 italic">Sin asignar</span>
                        <span v-else class="text-gray-300">—</span>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span v-if="Number(item.presupuesto_asignado) > 0"
                            class="font-semibold"
                            :class="(item.presupuesto_asignado - item.monto_total) < 0 ? 'text-red-600' : 'text-emerald-700'">
                            {{ Number(item.presupuesto_asignado - item.monto_total).toLocaleString('es-BO') }} Bs
                        </span>
                        <span v-else class="text-gray-300">—</span>
                    </td>

                    <!--  <td class="px-3 py-1">
                        <div class="flex justify-center items-center gap-1"
                            :title="`Pagos de ${toTitleCase(item.nombre)}`">
                            <Icon @click.prevent="generarPDFDeUsuario(item)" name="filePDF" fill="currentColor"
                                class-name="text-red-600 mx-1" :size="17" />
                        </div>
                    </td> -->
                </template>

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">

                        <template v-if="cargando">
                            <div class="mb-4">
                                <svg class="animate-spin w-12 h-12 text-[rgb(var(--brand-500))]" xmlns="http://www.w3.org/2000/svg"
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
                                    :stroke="buscado ? 'none' : 'currentColor'" class-name="text-gray-300" :size="64"
                                    :height="64" />
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
        <template v-else-if="tabActivo === 'mispagos'">
            <DataTable :data="misPagos.data ?? []" :columns="columnasIndividual" row-key="ci_persona"
                empty-message="No se encontraron pagos para los filtros seleccionados." :row-class="(item) => ({
                    'bg-red-300 font-extrabold text-red-700 hover:!bg-red-300': item.pago === 0,
                    'text-gray-900': item.pago != 0
                })">
                <template #row="{ item, index }">

                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span class=" text-sm">{{ (misPagos.from ?? 1) + index }}</span>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="uppercase">
                            {{ item.numero_boleta }}
                        </div>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="font-medium  uppercase">
                            {{ item.apellido_persona }} {{ item.nombre_persona }}
                        </div>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap">
                        <div>{{ item.ci_persona }}</div>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap">
                        <span v-if="item.distrito" class="">{{ item.distrito }}</span>
                        <span v-else class="text-red-500 italic text-xs">Sin datos</span>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="item.pago === 0 ? 'text-red-700' : 'text-blue-800 bg-blue-100'">
                            {{ String(item.mes).padStart(2, '0') }}/{{ item.gestion }}
                        </span>
                    </td>

                    <td class="px-3 py-1 font-bold whitespace-nowrap text-center"
                        :class="item.pago === 0 ? 'text-red-700' : 'text-green-600'">
                        <span v-if="item.pago != 0">{{ item.monto }} Bs</span>
                        <span v-else class="italic text-xs">!Anulado¡</span>
                    </td>

                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <div class="text-sm">{{ item.fecha_pago }}</div>
                    </td>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">
                        <div class="mb-4">
                            <Icon :icon-button="true" :name="buscado ? 'clipboardList' : 'calendarMont'"
                                :fill="buscado ? 'currentColor' : 'none'" :stroke="buscado ? 'none' : 'currentColor'"
                                class-name="text-gray-300" :size="64" :height="64" />
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
        <template v-else-if="tabActivo === 'general'">
            <DataTable :data="pagosTodos" :columns="columnasGeneral" row-key="numero_boleta"
                empty-message="No se encontraron pagos para los filtros seleccionados." :row-class="(item) => ({
                    'bg-red-300 font-extrabold text-red-700 hover:!bg-red-300': item.pago === 0,
                    'text-gray-900': item.pago != 0
                })">
                <template #row="{ item, index }">

                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span class="text-sm">
                            {{ (page.props.pagosTodos?.total ?? 0) - (((page.props.pagosTodos?.current_page ?? 1) - 1) *
                                (page.props.pagosTodos?.per_page ?? 0)) - index }}
                        </span>
                    </td>

                    <td class="px-1 py-1 whitespace-nowrap">
                        <div class="uppercase">{{ item.numero_boleta }}</div>
                    </td>

                    <td class="pl-1 py-1 whitespace-nowrap">
                        <div class="font-medium  uppercase">
                            {{ item.apellido_persona }} {{ item.nombre_persona }}
                        </div>
                    </td>

                    <td class="px-2 py-1 whitespace-nowrap">
                        <div>{{ item.ci_persona }}</div>
                    </td>

                    <td class="px-2 py-1 whitespace-nowrap">
                        <span v-if="item.distrito" class="">{{ item.distrito }}</span>
                        <span v-else class="text-red-500 italic text-xs">Sin datos</span>
                    </td>

                    <td class="px-2 py-1 font-bold whitespace-nowrap text-center"
                        :class="item.pago === 0 ? 'text-red-700' : 'text-green-600'">
                        <span v-if="item.pago != 0">{{ item.monto }} Bs</span>
                        <span v-else class="italic text-xs">!Anulado¡</span>
                    </td>

                    <td class="px-2 py-1 whitespace-nowrap">
                        <div class="text-sm">{{ formatDateTime(item.fecha_pago) }}</div>
                    </td>

                    <td class="px-2 py-1 whitespace-nowrap">
                        <div class=" capitalize text-sm">{{ item.usuario_pagador?.toLowerCase() }}
                        </div>
                    </td>

                    <td v-if="can('acciones-superusuario') || can('anular-pago')" class="px-3 py-1 whitespace-nowrap text-center">
                        <button v-if="item.pago !== 0 && can('anular-pago')" @click="anularPago(item.id_pago)"
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-all">
                            Anular pago
                        </button>
                        <div v-else-if="item.pago === 0 && can('acciones-superusuario')" class="flex flex-col items-center gap-1">
                            <span v-if="item.tiene_retro_valido"
                                class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg"
                                title="Ya tiene un pago retroactivo válido para este mismo mes.">
                                Pagado retroactivo
                            </span>
                            <button @click="reactivarPago(item.id_pago)"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-all">
                                Reactivar pago
                            </button>
                        </div>
                    </td>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">
                        <div class="mb-4">
                            <Icon :icon-button="true" :name="buscado ? 'clipboardList' : 'calendarMont'"
                                :fill="buscado ? 'currentColor' : 'none'" :stroke="buscado ? 'none' : 'currentColor'"
                                class-name="text-gray-300" :size="64" :height="64" />
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

        <!-- ============================================================================ -->
        <!-- TABLA: NO PAGADOS -->
        <!-- ============================================================================ -->
        <template v-else-if="tabActivo === 'no_pagados'">
            <DataTable :data="noPagados.data ?? []" :columns="columnasNoPagados" row-key="ci_persona"
                empty-message="No se encontraron beneficiarios no pagados para los filtros seleccionados.">
                <template #row="{ item, index }">
                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <span class="text-sm">{{ (noPagados.from ?? 1) + index }}</span>
                    </td>
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="font-medium text-gray-900 dark:text-gray-100 uppercase">
                            <p v-if="item.nombre_persona">{{ item.apellido_persona }} {{ item.nombre_persona }}</p>
                            <p v-else> {{ item.nombre_completo }}</p>
                        </div>
                    </td>
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div>{{ item.ci_persona }}</div>
                    </td>
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="text-gray-700 dark:text-gray-300">
                            <span v-if="item.distrito">{{ item.distrito }}</span>
                            <span v-else class="text-center text-red-500 italic text-xs">Sin datos</span>
                        </div>
                    </td>
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="text-gray-700 dark:text-gray-300">
                            <span v-if="!item.observaciones_habilitado"
                                class="block text-center italic text-red-500 text-xs">
                                ninguna
                            </span>
                            <span v-else class="text-xs line-clamp-1 cursor-help"
                                :title="item.observaciones_habilitado">
                                {{ item.observaciones_habilitado }}
                            </span>
                        </div>
                    </td>
                    <td class="px-3 py-1 whitespace-nowrap text-center">
                        <div class="text-sm">{{ formatDateTime(item.fecha_habilitado) }}</div>
                    </td>
                </template>
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">
                        <div class="text-center space-y-1 max-w-md">
                            <h3 class="text-lg font-semibold text-gray-700">Sin registros</h3>
                            <p class="text-sm text-gray-400">No hay beneficiarios pendientes de pago para los filtros
                                seleccionados.</p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </template>

        <!-- ============================================================================ -->
        <!-- TABLA: BAJAS -->
        <!-- ============================================================================ -->
        <template v-else-if="tabActivo === 'bajas'">
            <DataTable :data="bajas.data ?? []" :columns="columnasBajas" row-key="ci_persona">

                <template #row="{ item, index }">

                    <!-- Columna: Nº -->
                    <td class="text-center px-1 py-1.5 whitespace-nowrap">
                        <span class="text-sm">{{ (bajas.from || 1) + index }}</span>
                    </td>

                    <!-- Columna: C.I. -->
                    <td class="text-center px-1 py-1.5 whitespace-nowrap">
                        <div>{{ item.ci_persona }}</div>
                    </td>

                    <td class="pl-2 py-1.5 whitespace-nowrap">
                        <div class="font-medium text-gray-900 uppercase">
                            <p v-if="item.nombre">
                                {{ item.apellido }} {{ item.nombre }}
                            </p>
                            <p v-else>
                                {{ item.nombre_completo }}
                            </p>
                        </div>
                    </td>

                    <!-- Columna: Grado de discapacidad -->
                    <td class="text-center px-1 py-1.5 whitespace-nowrap">
                        <span>GRAVE MUY GRAVE</span>
                    </td>

                    <!-- Columna: Monto a Pagar -->
                    <td class="text-center py-1.5 whitespace-nowrap font-bold text-green-700">
                        {{ item.monto }}
                    </td>

                    <!-- Columna: Observaciones -->
                    <td class="text-center py-1.5 whitespace-nowrap">
                        <span class="uppercase text-sm" :class="item.estado === 'baja_definitiva'
                            ? 'text-red-700'
                            : item.estado === 'baja_temporal'
                                ? 'text-amber-600'
                                : ''">
                            {{ item.observaciones }}
                        </span>
                    </td>

                </template>

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">
                        <div class="text-center space-y-1 max-w-md">
                            <h3 class="text-lg font-semibold text-gray-700">Sin registros</h3>
                            <p class="text-sm text-gray-400">No hay bajas registradas para los filtros
                                seleccionados.
                            </p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </template>

        <!-- ============================================================================ -->
        <!-- FOOTER Y PAGINACIÓN -->
        <!-- ============================================================================ -->
        <div>
            <Paginacion v-if="tabActivo === 'mispagos' && misPagos?.last_page > 1" :links="misPagos.links"
                :from="misPagos.from" :to="misPagos.to" :total="misPagos.total" :tab="tabActivo" />
            <Paginacion v-if="tabActivo === 'general' && page.props.pagosTodos?.last_page > 1"
                :links="page.props.pagosTodos.links" :from="page.props.pagosTodos.from" :to="page.props.pagosTodos.to"
                :total="page.props.pagosTodos.total" :tab="tabActivo" />
            <Paginacion v-if="tabActivo === 'resumen' && resumenGeneral?.last_page > 1" :links="resumenGeneral.links"
                :from="resumenGeneral.from" :to="resumenGeneral.to" :total="resumenGeneral.total" :tab="tabActivo" />
            <Paginacion v-if="tabActivo === 'no_pagados' && noPagados?.last_page > 1" :links="noPagados.links"
                :from="noPagados.from" :to="noPagados.to" :total="noPagados.total" :tab="tabActivo" />
            <Paginacion v-if="tabActivo === 'bajas' && bajas?.last_page > 1" :links="bajas.links" :from="bajas.from"
                :to="bajas.to" :total="bajas.total" :tab="tabActivo" />
            <Footer />
        </div>

        <!-- ============================================================================ -->
        <!-- MODALES -->
        <!-- ============================================================================ -->

        <!-- ============================================================ -->
        <!-- MODAL: Detalle de Pagos por Usuario (Resumen General)        -->
        <!-- ============================================================ -->
        <Transition name="fade">
            <Modal v-if="modalUsuarioPagos" :showHeader="true" :showFooter="false" maxWidth="max-w-5xl"
                @close="modalUsuarioPagos = false">
                <!-- ── Ícono del header ── -->
                <template #icon>
                    <Icon :icon-button="true" name="user" class-name="text-white" :size="20" />
                </template>
                <template #label1>
                    {{ usuarioModalActivo?.nombre }} {{ usuarioModalActivo?.apellido }}
                </template>
                <template #label2>
                    Detalle de pagos registrados
                </template>

                <!-- ── Cuerpo del modal ── -->
                <div class="px-1">
                    <!-- Buscadores + card de totales — en mobile/tablet el card de
                         totales (datos) va arriba y los buscadores (filtros) debajo;
                         en desktop (lg:) vuelven al orden natural (filtros a la
                         izquierda, card a la derecha). -->
                    <div class="flex flex-col lg:flex-row lg:items-end gap-3 mb-3">
                        <div class="order-2 lg:order-none grid grid-cols-2 gap-2 flex-1">
                            <div class="flex flex-col gap-0.5">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">
                                    Nombre o C.I.
                                </label>
                                <input v-model="busqNombreCI" type="text" placeholder="Buscar nombre o C.I..."
                                    class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand-300))] bg-white" />
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-1">
                                    Distrito
                                </label>
                                <Dropdown align="left" width="48">
                                    <template #trigger="{ open }">
                                        <button
                                            class="inline-flex items-center gap-1 px-2 py-1 text-sm font-semibold rounded-xl transition-all w-full bg-white border border-gray-200 hover:bg-gray-50 shadow-sm hover:shadow"
                                            :class="busqDistrito ? 'border-[rgb(var(--brand-dark-rgb))] text-[rgb(var(--brand-dark-rgb))]' : 'text-slate-400'"
                                            type="button">
                                            <span class="flex-1 text-left truncate">{{ busqDistrito || 'Distrito' }}</span>
                                            <Icon :icon-button="true" name="angleDown"
                                                :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                                fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden">
                                            <ul class="py-1.5 max-h-60 overflow-y-auto">
                                                <li>
                                                    <a href="#" @click.prevent="busqDistrito = ''"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="!busqDistrito ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]' : 'text-slate-500 italic'">
                                                        Todos
                                                    </a>
                                                </li>
                                                <li v-for="d in distritos" :key="d">
                                                    <a href="#" @click.prevent="busqDistrito = d"
                                                        class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-[rgb(var(--brand-50))] duration-150"
                                                        :class="busqDistrito === d
                                                            ? 'bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] font-semibold border-r-4 border-[rgb(var(--brand-500))]'
                                                            : 'text-slate-700 hover:text-[rgb(var(--brand-700))]'">
                                                        {{ d }}
                                                        <Icon v-if="busqDistrito === d" :icon-button="true" name="check"
                                                            class-name="text-[rgb(var(--brand-700))]" :viewBox="'0 0 20 20'" :size="17" />
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Cards de totales: monto/pago, resultados, total pagado,
                             presupuesto asignado y restante — mismo patrón de card con
                             ícono que usa el resto de la vista (ver cards del toolbar
                             principal, líneas ~1357 en adelante). -->
                        <div class="order-1 lg:order-none flex flex-wrap gap-2">
                            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2">
                                <div class="w-6 h-6 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="cash" class-name="text-gray-500" :size="13" />
                                </div>
                                <div>
                                    <p class="text-[9px] text-gray-400 uppercase tracking-widest font-bold leading-tight">
                                        Monto/Pago</p>
                                    <p class="text-sm font-black text-gray-700 leading-tight">
                                        {{ formatCurrency(montoPorPagoModal) }} Bs
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="clipboardList" class-name="text-blue-600" :size="13" />
                                </div>
                                <div>
                                    <p class="text-[9px] text-blue-500 uppercase tracking-widest font-bold leading-tight">
                                        Resultados</p>
                                    <p class="text-sm font-black text-blue-700 leading-tight">
                                        {{ pagosFiltradosModal.length }}
                                        <span v-if="pagosFiltradosModal.length !== pagosDelModal.length"
                                            class="text-gray-400 font-medium text-xs">de {{ pagosDelModal.length }}</span>
                                        <span v-if="anuladosFiltradosModal > 0" class="text-red-500 font-semibold text-xs">
                                            ({{ anuladosFiltradosModal }} anul.)
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                                <div class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <Icon :icon-button="true" name="cash" class-name="text-emerald-600" :size="13" />
                                </div>
                                <div>
                                    <p class="text-[9px] text-emerald-500 uppercase tracking-widest font-bold leading-tight">
                                        Total Pagado</p>
                                    <p class="text-sm font-black text-emerald-700 leading-tight">
                                        {{ formatCurrency(montoFiltradoModal) }} Bs
                                    </p>
                                </div>
                            </div>

                            <!-- item.presupuesto_asignado viaja en resumenGeneralData desde
                                 PagoController (mismo COALESCE que la tabla de Resumen General) —
                                 solo tiene datos con filtro Gestión+Mes. -->
                            <template v-if="usuarioModalActivo?.presupuesto_asignado !== undefined">
                                <div class="flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-xl px-3 py-2">
                                    <div class="w-6 h-6 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                                        <Icon :icon-button="true" name="wallet" class-name="text-purple-600" :size="13" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-purple-500 uppercase tracking-widest font-bold leading-tight">
                                            Asignado</p>
                                        <p v-if="Number(usuarioModalActivo?.presupuesto_asignado) > 0"
                                            class="text-sm font-black text-purple-700 leading-tight">
                                            {{ formatCurrency(usuarioModalActivo.presupuesto_asignado) }} Bs
                                        </p>
                                        <p v-else class="text-sm font-bold text-gray-400 italic leading-tight">
                                            Sin asignar
                                        </p>
                                    </div>
                                </div>

                                <div v-if="Number(usuarioModalActivo?.presupuesto_asignado) > 0"
                                    class="flex items-center gap-2 rounded-xl px-3 py-2 border"
                                    :class="presupuestoRestanteModal < 0
                                        ? 'bg-red-50 border-red-200'
                                        : 'bg-emerald-50 border-emerald-200'">
                                    <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0"
                                        :class="presupuestoRestanteModal < 0 ? 'bg-red-100' : 'bg-emerald-100'">
                                        <Icon :icon-button="true" name="billCoin"
                                            :class-name="presupuestoRestanteModal < 0 ? 'text-red-600' : 'text-emerald-600'"
                                            :size="13" />
                                    </div>
                                    <div>
                                        <p class="text-[9px] uppercase tracking-widest font-bold leading-tight"
                                            :class="presupuestoRestanteModal < 0 ? 'text-red-500' : 'text-emerald-500'">
                                            Restante</p>
                                        <p class="text-sm font-black leading-tight"
                                            :class="presupuestoRestanteModal < 0 ? 'text-red-700' : 'text-emerald-700'">
                                            {{ formatCurrency(presupuestoRestanteModal) }} Bs
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Tabla de pagos -->
                    <div class="overflow-auto max-h-[45vh] rounded-xl border border-gray-200 shadow-sm">
                        <table class="w-full text-sm min-w-[600px]">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wide">
                                    <th class="px-3 py-2 text-center font-bold">N°</th>
                                    <th class="px-3 py-2 text-left font-bold">Nombre Completo</th>
                                    <th class="px-3 py-2 text-center font-bold">C.I.</th>
                                    <th class="px-3 py-2 text-center font-bold">Distrito</th>
                                    <th class="px-3 py-2 text-center font-bold">Boleta</th>
                                    <th class="px-3 py-2 text-center font-bold">Fecha Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="pagosFiltradosModal.length > 0">
                                    <tr v-for="(p, idx) in pagosFiltradosModal" :key="p.numero_boleta ?? idx" :class="[
                                        parseFloat(p.pago) === 0
                                            ? 'bg-red-100 text-red-700 font-bold'
                                            : idx % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                        'hover:bg-[rgb(var(--brand-50))] transition-colors'
                                    ]">
                                        <td class="px-3 py-1 text-center text-xs">
                                            {{ idx + 1 }}
                                        </td>
                                        <td class="px-3 py-1 whitespace-nowrap text-xs uppercase font-medium">
                                            {{ p.apellido_persona }} {{ p.nombre_persona }}
                                        </td>
                                        <td class="px-3 py-1 text-xs ">{{ p.ci_persona }}</td>
                                        <td class="px-3 py-1 text-xs text-center">
                                            <span v-if="p.distrito">{{ p.distrito }}</span>
                                            <span v-else class="text-red-400 italic text-xs">Sin datos</span>
                                        </td>
                                        <td class="px-3 py-1 text-xs whitespace-nowrap">{{ p.numero_boleta }}</td>
                                        <td class="px-3 py-1 text-xs">{{ formatDateTime(p.fecha_pago) }}</td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="py-10 text-center text-gray-400 text-sm">
                                        <Icon :icon-button="true" name="clipboardList" fill="currentColor"
                                            class-name="text-gray-200 mx-auto mb-2" :size="40" :height="40" />
                                        Sin resultados para los filtros aplicados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Footer del modal ── -->
                <template #footer>
                    <div class="sm:px-5 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center sm:justify-between gap-2">
                            <!-- Info filtro activo -->
                            <span class="hidden sm:flex text-xs text-gray-400 pl-1">
                                <template v-if="busqNombreCI || busqDistrito">
                                    El reporte se generará con
                                    <span class="font-semibold text-[rgb(var(--brand-600))]">{{ pagosFiltradosModal.length }}</span>
                                    registro{{ pagosFiltradosModal.length !== 1 ? 's' : '' }} filtrado{{
                                        pagosFiltradosModal.length !== 1 ? 's' : '' }}.
                                </template>
                                <template v-else>
                                    Se generará el reporte con todos los registros.
                                </template>
                            </span>

                            <div class="flex gap-2">
                                <!-- Cerrar -->
                                <Button @click="modalUsuarioPagos = false"
                                    :style="'py-2.5 px-8 rounded-xl border border-gray-200'"
                                    class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                    Cerrar
                                </Button>

                                <!-- Reporte (PDF/Excel) -->
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <Button :disabled="pagosFiltradosModal.length === 0"
                                            :style="'flex items-center gap-2 py-2.5 px-6 rounded-xl border'" :class="pagosFiltradosModal.length === 0
                                                ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                                : 'text-white bg-red-600 hover:bg-red-700'">
                                            <Icon :icon-button="true" name="circlePlus" class-name="text-white" :size="18" />
                                            Reporte
                                        </Button>
                                    </template>
                                    <template #content>
                                        <div class="shadow-xl overflow-hidden py-1">
                                            <a href="#" @click.prevent="generarPDFModalFiltrado()"
                                                class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                                <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                                Reporte PDF
                                            </a>
                                            <a href="#" @click.prevent="exportarExcelModalFiltrado()"
                                                class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                                <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                                Reporte Excel
                                            </a>

                                            <!-- Arqueo de Caja: cierre del mes completo del cajero, no
                                                 respeta los buscadores de nombre/C.I./distrito de arriba. -->
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <a href="#" @click.prevent="generarArqueoUsuario()"
                                                class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                                <Icon :icon-button="true" name="filePDF" class-name="text-red-500" :size="16" />
                                                Arqueo de Caja PDF
                                            </a>
                                            <a href="#" @click.prevent="exportarExcelArqueoUsuario()"
                                                class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-[rgb(var(--brand-50))]">
                                                <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600" :size="16" />
                                                Arqueo de Caja Excel
                                            </a>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal: Anular Pago de Beneficiario -->
        <Transition name="fade">
            <Modal v-if="modalAnular" :showHeader="false" :showFooter="false" maxWidth="max-w-md" @close="closeModal">
                <div class="py-4 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-red-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="warning" class-name="text-red-500" fill="none"
                            stroke="currentColor" stroke-width="2" :size="50" :height="50" />
                    </div>
                    <!-- Título -->
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Anular Pago
                    </h2>

                    <!-- Mensaje principal -->
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        ¿Seguro que desea <span class="text-red-600">anular</span> este pago?
                    </p>

                    <!-- Advertencia -->
                    <p class="text-xs mt-2 text-red-500 dark:text-red-400">
                        Esta acción afecta la parte contable del sistema. Puede reactivarse luego desde la pestaña
                        "Total Pagados".
                    </p>

                    <!-- Observación / motivo de la anulación -->
                    <div class="mt-4 text-left px-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                            Motivo de la anulación <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="observacionAnulacion" rows="3" maxlength="1000"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[rgb(var(--brand-500))] focus:border-[rgb(var(--brand-500))]"
                            placeholder="Explica por qué se anula este pago..."></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="closeModal"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cancelar
                            </Button>
                            <Button @click="confirmarAccionPago"
                                :disabled="procesandoPago || !observacionAnulacion.trim()"
                                :style="'items-center py-3 px-5 sm:px-12 sm:py-2.5 rounded-xl border relative w-36'"
                                :class="(procesandoPago || !observacionAnulacion.trim()) ? 'opacity-60 cursor-not-allowed bg-[rgb(var(--brand-400))]' : 'bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]'"
                                class="text-white">
                                <span v-if="procesandoPago"
                                    class="absolute inset-0 flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Procesando...
                                </span>
                                <span v-else class="flex items-center justify-center">Confirmar</span>
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>

        <!-- Modal: Reactivar Pago de Beneficiario -->
        <Transition name="fade">
            <Modal v-if="modalReactivar" :showHeader="false" :showFooter="false" maxWidth="max-w-md"
                @close="closeModalReactivar">
                <div class="py-4 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40 shadow-inner mb-4">
                        <Icon :icon-button="true" name="check" class-name="text-green-600" fill="none"
                            stroke="currentColor" stroke-width="2" :size="50" :height="50" />
                    </div>
                    <!-- Título -->
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Reactivar Pago
                    </h2>

                    <!-- Mensaje principal -->
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        ¿Seguro que desea <span class="text-green-600">reactivar</span> este pago?
                    </p>

                    <!-- Advertencia -->
                    <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
                        El pago volverá a contar como válido y el beneficiario quedará habilitado nuevamente para
                        este mes. Se eliminará el motivo de anulación registrado.
                    </p>
                </div>

                <!-- Footer -->
                <template #footer>
                    <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                        <div class="flex justify-center gap-3">
                            <Button @click="closeModalReactivar"
                                :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                                class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                                Cancelar
                            </Button>
                            <Button @click="confirmarReactivarPago" :disabled="procesandoPago"
                                :style="'items-center py-3 px-5 sm:px-12 sm:py-2.5 rounded-xl border relative w-36'"
                                :class="procesandoPago ? 'opacity-60 cursor-not-allowed bg-green-400' : 'bg-green-600 hover:bg-green-500'"
                                class="text-white">
                                <span v-if="procesandoPago"
                                    class="absolute inset-0 flex items-center justify-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Procesando...
                                </span>
                                <span v-else class="flex items-center justify-center">Confirmar</span>
                            </Button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Transition>
    </AppLayout>
</template>
