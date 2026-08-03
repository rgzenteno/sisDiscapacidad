<script setup>
import { ref, onUnmounted, onMounted, computed } from "vue";

import Modal from "@/components/Modal.vue";
import Button from "@/components/Button.vue";
import Icon from "@/components/Icon.vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    planilla_cargada: {
        type: Boolean,
        default: true,
    },
    fecha_carga_planilla: {
        type: String,
        default: null,
    },
    nombreUsuario: {
        type: String,
        default: "",
    },
    ultimo_mes_creado: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "cargar-planilla", "recargar-planilla"]);

/* ------------------------------------------------------------------ */
/* Helpers de formato                                                  */
/* ------------------------------------------------------------------ */

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("es-BO", {
        style: "decimal",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount) || 0);
};

const formatInt = (value) => {
    return new Intl.NumberFormat("es-BO").format(Number(value) || 0);
};

const formatPercent = (value, decimals = 2) => {
    return (Number(value) || 0).toFixed(decimals).replace(".", ",");
};

/* ------------------------------------------------------------------ */
/* Fechas / etiquetas                                                  */
/* ------------------------------------------------------------------ */

const MESES = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre",
];

function getFromDate(monthNumber) {
    const index = parseInt(monthNumber, 10) - 1;
    return index >= 0 && index < 12 ? MESES[index] : "Mes inválido";
}

function getMonthDateRange(monthNumber, year = new Date().getFullYear()) {
    const startDate = new Date(year, monthNumber - 1, 1);
    const endDate = new Date(year, monthNumber, 0);

    const format = (date) => {
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        return `${day}/${month}/${date.getFullYear()}`;
    };

    return `Del ${format(startDate)} al ${format(endDate)}`;
}

const esUltimoMes = computed(() => {
    if (!props.ultimo_mes_creado || !props.data?.gestion || !props.data?.mes) return false;
    return (
        props.ultimo_mes_creado.gestion === props.data.gestion &&
        props.ultimo_mes_creado.mes === props.data.mes
    );
});

/* ------------------------------------------------------------------ */
/* Métricas                                                            */
/* ------------------------------------------------------------------ */

const presupuestoMensual = computed(() => Number(props.data.presupuesto) || 0);
const montoTotalPagado = computed(() => Number(props.data.total_pagado) || 0);
const saldoRestante = computed(() => presupuestoMensual.value - montoTotalPagado.value);

const porcentajeEjecucion = computed(() => {
    if (!presupuestoMensual.value) return 0;
    return (montoTotalPagado.value / presupuestoMensual.value) * 100;
});

const porcentajePendienteEjecucion = computed(() => {
    return Math.max(0, 100 - porcentajeEjecucion.value);
});

const getTotalBeneficiarios = computed(() => {
    return (
        Number(props.data.cantidad_habilitadas || 0) +
        Number(props.data.personas_baja_temporal || 0) +
        Number(props.data.personas_baja_definitiva || 0)
    );
});

const calculatePercentage = (value) => {
    const total = getTotalBeneficiarios.value;
    if (total === 0) return "0,0";
    return formatPercent((Number(value || 0) / total) * 100, 1);
};

const hayAnulados = computed(() => Number(props.data.cantidad_pagos_anulados) > 0);
const hayPendientes = computed(() => Number(props.data.cantidad_no_pagados) > 0);

/* ------------------------------------------------------------------ */
/* Ciclo de vida                                                       */
/* ------------------------------------------------------------------ */

const closeOnEscape = (e) => {
    if (e.key === "Escape") emit("close");
};

onMounted(() => document.addEventListener("keydown", closeOnEscape));
onUnmounted(() => document.removeEventListener("keydown", closeOnEscape));
</script>

<template>
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-3xl"
        :fondoIcon="esUltimoMes ? 'bg-[rgb(var(--brand-200))]' : 'bg-gray-200'" @close="$emit('close')">

        <!-- ============================= HEADER (sin cambios) ============================= -->
        <template #icon>
            <Icon :icon-button="true" name="calendarMonth"
                :class-name="esUltimoMes ? 'text-[rgb(var(--brand-600))]' : 'text-gray-500'" :size="20" />
        </template>
        <template #label1>
            <div class="flex items-center gap-2">
                <span
                    :class="esUltimoMes ? 'text-[rgb(var(--brand-700))] dark:text-white' : 'text-gray-500 dark:text-white'">
                    {{ getFromDate(props.data.mes) }}
                </span>
                <div :class="{
                    'px-2 py-0.5 rounded-2xl text-xs font-bold border': true,
                    'bg-green-100 text-green-800 border-green-200': props.data.cantidad_habilitadas === props.data.cantidad_total_pagos,
                    'bg-blue-100 text-blue-800 border-blue-200': esUltimoMes && props.data.cantidad_habilitadas !== props.data.cantidad_total_pagos,
                    'bg-yellow-100 text-yellow-800 border-yellow-200': !esUltimoMes && props.data.cantidad_habilitadas !== props.data.cantidad_total_pagos
                }">
                    {{ props.data.cantidad_habilitadas === props.data.cantidad_total_pagos ? 'Completo' : esUltimoMes ?
                        'En Proceso' : 'Pagos Pendientes' }}
                </div>
            </div>
        </template>
        <template #label2>{{ getMonthDateRange(props.data.mes, props.data.gestion) }}</template>

        <!-- ================================== BODY ================================== -->
        <div class="space-y-2">

            <!-- [0] Monto por beneficiario -->
            <div
                class="flex items-center justify-center gap-2 rounded-lg border border-[#1E3A8A]/15 bg-[#1E3A8A]/5 px-3 py-2 dark:border-[#1E3A8A]/40 dark:bg-[#1E3A8A]/20">
                <Icon :icon-button="true" name="cash" class-name="text-[#1E3A8A] dark:text-indigo-300"
                    :size="16" />
                <span class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-300">
                    Monto por beneficiario
                </span>
                <span class="text-sm font-bold text-[#1E3A8A] dark:text-indigo-300">
                    Bs {{ formatCurrency(props.data.monto) }}
                </span>
            </div>

            <!-- [1]-[3] Grid 2x3 de indicadores -->
            <div class="grid grid-cols-2 gap-2">

                <!-- [1A] Habilitadas -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-medium leading-tight text-slate-500 dark:text-slate-400">
                            Personas Beneficiarias Habilitadas
                        </p>
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-indigo-50 dark:bg-indigo-500/10">
                            <Icon :icon-button="true" name="usersGroup"
                                class-name="text-indigo-600 dark:text-indigo-400" :size="14" />
                        </span>
                    </div>
                    <p class="mt-1 text-xl font-bold leading-none tracking-tight text-slate-800 dark:text-slate-100">
                        {{ formatInt(props.data.cantidad_habilitadas) }}
                    </p>
                    <p class="mt-auto pt-1.5 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                        {{ formatPercent(props.data.porcentaje_habilitado) }}% del total
                    </p>
                </div>

                <!-- [1B] Presupuesto mensual -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-medium leading-tight text-slate-500 dark:text-slate-400">
                            Presupuesto Mensual Asignado
                        </p>
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-indigo-50 dark:bg-indigo-500/10">
                            <Icon :icon-button="true" name="invoiceLines"
                                class-name="text-indigo-600 dark:text-indigo-400" :size="14" />
                        </span>
                    </div>
                    <p class="mt-1 text-xl font-bold leading-none tracking-tight text-slate-800 dark:text-slate-100">
                        <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Bs</span>
                        {{ formatCurrency(presupuestoMensual) }}
                    </p>
                    <p class="mt-auto pt-1.5 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                        de Bs {{ formatCurrency(props.data.presupuesto_anual) }} anual
                    </p>
                </div>

                <!-- [2A] Con pago realizado -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-medium leading-tight text-slate-500 dark:text-slate-400">
                            Personas Beneficiarias con Pago Realizado
                        </p>
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-emerald-50 dark:bg-emerald-500/10">
                            <Icon :icon-button="true" name="checkCircle"
                                class-name="text-emerald-600 dark:text-emerald-400" :size="14" />
                        </span>
                    </div>
                    <p class="mt-1 text-xl font-bold leading-none tracking-tight text-slate-800 dark:text-slate-100">
                        {{ formatInt(props.data.cantidad_total_pagos) }}
                    </p>
                    <p class="mt-auto pt-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ formatPercent(props.data.porcentaje_pagado) }}% de habilitados
                    </p>
                </div>

                <!-- [2B] Monto total ejecutado -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-medium leading-tight text-slate-500 dark:text-slate-400">
                            Monto Total Ejecutado en Beneficios
                        </p>
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-emerald-50 dark:bg-emerald-500/10">
                            <Icon :icon-button="true" name="cash"
                                class-name="text-emerald-600 dark:text-emerald-400" :size="14" />
                        </span>
                    </div>
                    <p class="mt-1 text-xl font-bold leading-none tracking-tight text-slate-800 dark:text-slate-100">
                        <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Bs</span>
                        {{ formatCurrency(montoTotalPagado) }}
                    </p>
                    <p class="mt-auto pt-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ formatPercent(porcentajeEjecucion) }}% del presupuesto mensual
                    </p>
                </div>

                <!-- [3A] Pendientes de pago -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-medium leading-tight text-slate-500 dark:text-slate-400">
                            Personas Beneficiarias Pendientes de Pago
                        </p>
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-50 dark:bg-amber-500/10">
                            <Icon :icon-button="true" name="alertTriangle"
                                class-name="text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                stroke-width="2" :size="14" />
                        </span>
                    </div>
                    <p class="mt-1 text-xl font-bold leading-none tracking-tight text-slate-800 dark:text-slate-100">
                        {{ formatInt(props.data.cantidad_no_pagados) }}
                    </p>
                    <p class="mt-auto pt-1.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                        de {{ formatInt(props.data.cantidad_habilitadas) }} habilitados
                    </p>
                </div>

                <!-- [3B] Saldo presupuestario disponible -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[11px] font-medium leading-tight text-slate-500 dark:text-slate-400">
                            Saldo Presupuestario Disponible
                        </p>
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-50 dark:bg-amber-500/10">
                            <Icon :icon-button="true" name="wallet" fill="none" stroke="currentColor"
                                class-name="text-amber-600 dark:text-amber-400" :size="14" />
                        </span>
                    </div>
                    <p class="mt-1 text-xl font-bold leading-none tracking-tight text-slate-800 dark:text-slate-100">
                        <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Bs</span>
                        {{ formatCurrency(saldoRestante) }}
                    </p>
                    <p class="mt-auto pt-1.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400">
                        {{ formatPercent(porcentajePendienteEjecucion) }}% sin ejecutar
                    </p>
                </div>

            </div>

            <!-- Separador -->
            <div class="flex items-center gap-2 my-6">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
                <div class="flex items-center justify-center">
                    <span class="text-sm text-slate-400">Detalles del mes</span>
                </div>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
            </div>

            <!-- [4]-[5] Ejecución / Estado de beneficiarios -->
            <div class="grid grid-cols-2 gap-2">

                <!-- [4] Porcentaje de ejecución -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <p
                        class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Porcentaje de ejecución
                    </p>
                    <div class="space-y-2.5">
                        <div>
                            <div class="mb-1 flex items-baseline justify-between gap-2">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">Ejecución del pago</span>
                                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatPercent(porcentajeEjecucion) }}%
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600"
                                role="progressbar" :aria-valuenow="Number(porcentajeEjecucion.toFixed(2))"
                                aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                                    :style="`width: ${Math.min(100, porcentajeEjecucion)}%`"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1 flex items-baseline justify-between gap-2">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">Pendiente de
                                    ejecución</span>
                                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                    {{ formatPercent(porcentajePendienteEjecucion) }}%
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600"
                                role="progressbar" :aria-valuenow="Number(porcentajePendienteEjecucion.toFixed(2))"
                                aria-valuemin="0" aria-valuemax="100">
                                <div class="h-full rounded-full bg-amber-500 transition-all duration-300"
                                    :style="`width: ${Math.min(100, porcentajePendienteEjecucion)}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- [5] Estado de beneficiarios -->
                <div
                    class="flex h-full flex-col rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/40">
                    <p
                        class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Estado de beneficiarios
                    </p>
                    <div class="space-y-1.5">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Activos
                            </span>
                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200">
                                {{ formatInt(props.data.cantidad_habilitadas) }}
                                <span class="font-medium text-slate-400 dark:text-slate-500">
                                    ({{ calculatePercentage(props.data.cantidad_habilitadas) }}%)
                                </span>
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Baja temporal
                            </span>
                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200">
                                {{ formatInt(props.data.personas_baja_temporal) }}
                                <span class="font-medium text-slate-400 dark:text-slate-500">
                                    ({{ calculatePercentage(props.data.personas_baja_temporal) }}%)
                                </span>
                            </span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Baja definitiva
                            </span>
                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200">
                                {{ formatInt(props.data.personas_baja_definitiva) }}
                                <span class="font-medium text-slate-400 dark:text-slate-500">
                                    ({{ calculatePercentage(props.data.personas_baja_definitiva) }}%)
                                </span>
                            </span>
                        </div>
                        <div
                            class="mt-auto flex items-baseline justify-between gap-2 border-t border-gray-200 pt-1.5 dark:border-gray-600">
                            <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300">Total</span>
                            <span class="text-[11px] font-bold text-slate-800 dark:text-slate-100">
                                {{ formatInt(getTotalBeneficiarios) }}
                                <span class="font-medium text-slate-400 dark:text-slate-500">(100%)</span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- [7] Resumen general -->
            <div
                class="flex items-center gap-2.5 rounded-lg border border-[#1E3A8A]/15 bg-[#1E3A8A]/5 px-3 py-2.5 dark:border-[#1E3A8A]/40 dark:bg-[#1E3A8A]/20">
                <Icon :icon-button="true" name="chartPie" class-name="shrink-0 text-[#1E3A8A] dark:text-indigo-300"
                    :size="40" :height="40" />
                <div class="space-y-0.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-300">
                        Resumen general
                    </p>
                    <p class="text-[11px] leading-relaxed text-slate-600 dark:text-slate-300">
                        Se ha ejecutado el
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                            {{ formatPercent(porcentajeEjecucion) }}%
                        </span>
                        del presupuesto asignado al programa.
                        <br>
                        <template v-if="hayPendientes">
                            Quedan
                            <span class="font-bold text-amber-600 dark:text-amber-400">
                                {{ formatInt(props.data.cantidad_no_pagados) }}
                            </span>
                            personas beneficiarias pendientes de pago.
                        </template>
                        <template v-else>
                            Todas las personas beneficiarias habilitadas recibieron su pago.
                        </template>
                        <template v-if="hayAnulados">
                            Se registraron
                            <span class="font-bold text-rose-600 dark:text-rose-400">
                                {{ formatInt(props.data.cantidad_pagos_anulados) }}
                            </span>
                            boletas anuladas en el mes.
                        </template>
                    </p>
                </div>
            </div>

        </div>

        <!-- ================================= FOOTER ================================= -->
        <template #footer>
            <div class="border-t border-gray-100 py-4 dark:border-gray-700/50 sm:px-5">
                <div class="flex justify-center">
                    <Button @click="$emit('close')" :style="'py-2.5 px-14 rounded-xl border'"
                        class="border-[rgb(var(--brand-600))] bg-[rgb(var(--brand-600))] font-semibold text-white shadow-sm transition-colors hover:bg-[rgb(var(--brand-700))] hover:border-[rgb(var(--brand-700))]">
                        Aceptar
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
