<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { can } from '@/lib/can';

import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import Icon from '@/components/Icon.vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    processing: {
        type: Boolean,
        default: false
    },
});

//console.log('Props:', props.data);

const emit = defineEmits(['habilitar', 'add', 'close', 'cambioEstado', 'addEstado', 'sinDatos'])

const isModalOpen = ref(false)
const habilitado = computed(() => props.data.habilitado) // true, false
const estado = computed(() => props.data.estado_mes)
// Siempre arranca vacío: tanto al deshabilitar como al volver a habilitar se
// exige escribir una observación nueva (nunca se reutiliza la anterior).
const observacion = ref('')

// ============================================================================
// RETROACTIVO: switch para gestionar el retroactivo de este mismo mes
// ============================================================================
// Un beneficiario solo puede tener UN pago por mes: o el normal o el
// retroactivo, nunca ambos. Por eso el switch solo se ofrece cuando el mes
// normal todavía no fue tocado (ni habilitado ni pagado) — si ya está
// habilitado o pagado normalmente, no tiene sentido ofrecer el retroactivo.
// Si esta tarjeta YA es un retroactivo (es_retroactivo), no aplica: ya se
// gestiona directo, sin switch (ver conversación de diseño).
const puedeMostrarOpcionRetro = computed(() => {
    if (props.data.es_retroactivo) return false;
    if (!props.data.retro_habilitado_gestion) return false;
    if (Number(props.data.mes) > 10) return false;
    if (habilitado.value === true) return false;
    if (Number(props.data.pagado) === 1) return false;
    if (estado.value !== 'activo') return false;
    return true;
});
const retroMesCargado = computed(() => !!props.data.retro_mes_cargado);
const modoRetro = ref(false);

// ============================================================================
// MES NORMAL: switch inverso, para volver a gestionar el mes normal desde
// una tarjeta que YA es un retroactivo, si ese retroactivo está deshabilitado.
// Mientras el retro esté habilitado o pagado, no se ofrece (mismo principio
// de "un solo pago por mes" que bloquea el switch en sentido contrario).
// ============================================================================
const puedeMostrarOpcionNormal = computed(() =>
    !!props.data.es_retroactivo && habilitado.value === false
);
const modoNormal = ref(false);

// Estamos viendo/gestionando datos de retroactivo cuando: la tarjeta es
// nativamente normal y se activó el switch (creando uno nuevo), o la
// tarjeta es nativamente retro y NO se activó el switch de "ver normal".
const esTarjetaRetro = computed(() =>
    (!props.data.es_retroactivo && modoRetro.value) ||
    (props.data.es_retroactivo && !modoNormal.value)
);

// "Datos efectivos": resuelven a los del mes normal, del retro, o los
// nativos de la tarjeta, según qué lado se esté viendo en este momento.
const habilitadoEfectivo = computed(() => {
    if (!props.data.es_retroactivo && modoRetro.value) return null; // retro nuevo, aún no existe
    if (props.data.es_retroactivo && modoNormal.value) return props.data.normal_habilitado ?? null;
    return habilitado.value;
});
const pagadoEfectivo = computed(() => {
    if (!props.data.es_retroactivo && modoRetro.value) return null;
    if (props.data.es_retroactivo && modoNormal.value) return props.data.normal_pagado ?? null;
    return props.data.pagado;
});
const montoEfectivo = computed(() => {
    if (!props.data.es_retroactivo && modoRetro.value) return props.data.retro_monto;
    if (props.data.es_retroactivo && modoNormal.value) return props.data.normal_monto;
    return props.data.monto;
});
const observacionesEfectivo = computed(() => {
    if (!props.data.es_retroactivo && modoRetro.value) return null;
    if (props.data.es_retroactivo && modoNormal.value) return props.data.normal_observaciones_habilitado ?? null;
    return props.data.observaciones_habilitado;
});
const boletaEfectivo = computed(() => (props.data.es_retroactivo && modoNormal.value)
    ? props.data.normal_boleta
    : props.data.boleta
);
const fechaPagoEfectivo = computed(() => (props.data.es_retroactivo && modoNormal.value)
    ? props.data.normal_fecha_pago
    : props.data.fecha_pago
);

// Muestra el mes correcto en el título: si esta tarjeta YA es un
// mes-retro, `data.mes` es un código interno (101-112, no calendario) —
// hay que usar `mes_original` para el nombre real del mes.
const nombreMesTarjeta = computed(() =>
    getMonthNameFromNumber(props.data.es_retroactivo ? props.data.mes_original : props.data.mes)
);


function getMonthNameFromNumber(monthNumber) {
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

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';

    const [datePart, timePart] = dateTimeString.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour, minute] = timePart.split(':');

    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return `${dateStr} ${hour}:${minute}`;
};

const getStatusLabel = (status) => {
    const labels = {
        activo: 'Activo',
        baja_temporal: 'Baja Temporal',
        baja_definitiva: 'Baja Definitiva',
        pagos_suspendidos: 'Pago Suspendido',
        depurado: 'Depurado'
    }
    return labels[status] || status
}

const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
}

const getIconBgColor = (status) => {
    const colors = {
        activo: 'bg-gradient-to-br from-green-400 to-emerald-300',
        baja_temporal: 'bg-gradient-to-br from-yellow-400 to-amber-300',
        baja_definitiva: 'bg-gradient-to-br from-red-400 to-rose-300',
        depurado: 'bg-gradient-to-br from-gray-400 to-slate-300',
        pagos_suspendidos: 'bg-gradient-to-br from-purple-400 to-violet-300'
    }
    return colors[status] || 'bg-gradient-to-br from-gray-500 to-gray-400'
}

const getModalBodyColor = (status, enabled) => {
    if (!enabled) return 'bg-gray-50 dark:bg-gray-800'

    const colors = {
        activo: 'bg-green-50 dark:bg-green-900/10',
        baja_temporal: 'bg-yellow-50 dark:bg-yellow-900/10',
        baja_definitiva: 'bg-red-50 dark:bg-red-900/10',
        depurado: 'bg-gray-50 dark:bg-gray-900/10',
        pagos_suspendidos: 'bg-purple-50 dark:bg-purple-900/10'
    }
    return colors[status] || 'bg-gray-50 dark:bg-gray-800'
}

const getStatusBadgeColor = (status) => {
    const colors = {
        activo: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        baja_temporal: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        baja_definitiva: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        depurado: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
        pagos_suspendidos: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStadoBadgeColor = (status) => {
    const colorStado = {
        activo: 'mt-2 p-2 bg-green-50 border border-green-200 rounded-lg',
        baja_temporal: 'mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg',
        baja_definitiva: 'mt-2 p-2 bg-red-50 border border-red-200 rounded-lg',
        depurado: 'mt-2 p-2 bg-gray-50 border border-gray-200 rounded-lg',
        pagos_suspendidos: 'mt-2 p-2 bg-purple-50 border border-purple-200 rounded-lg'
    }
    return colorStado[status] || 'bg-gray-100 text-gray-800'
}


// Reemplaza la función isCarnetVencido() por esto:
const carnetEstado = computed(() => {
    if (!props.data.fecha_emision) return 'indefinido';
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const vencimiento = new Date(props.data.fecha_vencimiento);
    return vencimiento < hoy ? 'vencido' : 'vigente';
});

const getStatusTextColor = (estado, shade = 600) => {
    const colors = {
        baja_temporal: `text-yellow-${shade}`,
        baja_definitiva: shade === 600 ? 'text-red-600' : 'text-red-800 text-xs font-bold',
        depurado: shade === 600 ? 'text-gray-600' : 'text-gray-800 text-xs font-bold',
        pagos_suspendidos: shade === 600 ? 'text-purple-600' : 'text-purple-800 text-xs font-bold',
    };
    return colors[estado] ?? '';
};

const handleAction = () => {
    if (props.processing) return

    // Cualquier acción (habilitar por primera vez, deshabilitar, volver a
    // habilitar) exige una observación que justifique el cambio. Solo se
    // está deshabilitando cuando el mes YA está habilitado; en cualquier
    // otro caso (crear, retro, volver a habilitar) es una acción de habilitar.
    if (observacion.value.trim() === '') {
        emit('sinDatos', habilitadoEfectivo.value === true ? 'deshabilitar' : 'habilitar');
        return
    }

    // Creando un retroactivo nuevo (tarjeta nativamente normal + switch activado)
    if (modoRetro.value) {
        emit('habilitar', {
            ...props.data,
            id_mes: props.data.retro_id_mes,
            monto: props.data.retro_monto,
            es_retroactivo: true,
            observaciones_habilitado: observacion.value.trim(),
        })
        return
    }

    // Gestionando el mes normal desde una tarjeta nativamente retro (switch
    // "ver mes normal" activado, solo posible si el retro está deshabilitado)
    if (modoNormal.value) {
        if (!props.data.normal_id_habilitado) {
            // El mes normal nunca tuvo habilitado: crear uno nuevo
            emit('habilitar', {
                ...props.data,
                id_mes: props.data.normal_id_mes,
                monto: props.data.normal_monto,
                es_retroactivo: false,
                observaciones_habilitado: observacion.value.trim(),
            })
            return
        }

        // El mes normal ya tenía un habilitado (deshabilitado antes): togglear
        emit('cambioEstado', {
            id_habilitado: props.data.normal_id_habilitado,
            habilitado: habilitadoEfectivo.value ? 0 : 1,
            observacion: observacion.value.trim()
        })
        return
    }

    if (!props.data.id_habilitado) {
        emit('habilitar', {
            ...props.data,
            observaciones_habilitado: observacion.value.trim(),
        })
        return
    }

    if (!habilitado.value && estado.value === 'baja_definitiva') {
        alert('No se puede habilitar el pago para beneficiarios con baja definitiva')
        return
    }

    emit('cambioEstado', {
        id_habilitado: props.data.id_habilitado,
        habilitado: habilitado.value ? 0 : 1,
        observacion: observacion.value.trim()
    })
}

// Solo se oculta el botón de acción cuando el mes está habilitado Y pagado
// (nada que hacer desde acá — anular/reactivar el pago se maneja en la
// sección de Pagos). Si está deshabilitado pero igual queda un pago vigente
// colgando (caso de un pago duplicado histórico donde se anuló la fila
// duplicada pero la real seguía activa), debe poder reactivarse desde acá.
const isSoloAceptar = computed(() =>
    (habilitadoEfectivo.value === true && Number(pagadoEfectivo.value) === 1) || estado.value !== 'activo'
)

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close')
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>
<template>
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-lg" @close="$emit('close')">
        <template #icon>
            <div :class="esTarjetaRetro ? 'bg-gradient-to-br from-amber-400 to-yellow-300' : getIconBgColor(estado)"
                class="w-10 h-10 rounded-xl flex items-center justify-center ">
                <Icon :icon-button="true" name="calendarMontSolid" class-name="w-5 h-5 text-white" :size="17" />
            </div>
        </template>
        <template #label1>
            {{ esTarjetaRetro ? `Retroactivo ${nombreMesTarjeta}` : `${nombreMesTarjeta} - ${props.data.gestion}` }}
        </template>
        <template #label2>
            {{ modoRetro ? 'Habilitar retroactivo del beneficiario' : modoNormal ? 'Gestionar el mes normal del beneficiario' : 'Mes de pago del beneficiario' }}
        </template>

        <!-- Body -->
        <div>
            <!-- Estado del beneficiario -->
            <div class="mb-3">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm">Información del Mes</h4>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                    <!-- <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Estado:</span>
                        <div :class="getStatusBadgeColor(estado)"
                            class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">
                            {{ getStatusLabel(estado) }}
                        </div>
                    </div> -->
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Mes:</span>
                        <div :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': habilitadoEfectivo === true,
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': habilitadoEfectivo === false,
                            'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200': habilitadoEfectivo === null,
                        }" class="px-2.5 py-1 rounded-full text-xs font-semibold">
                            {{ habilitadoEfectivo === true ? 'Habilitado' : habilitadoEfectivo === false ?
                                'Deshabilitado' : 'Pendiente de habilitación'
                            }}
                        </div>
                    </div>
                    <div v-if="props.data.fecha_habilitado" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-300 text-sm">Fecha Habilitado:</span>
                        <span class="font-semibold text-gray-800 dark:text-white text-sm">
                            {{ formatDateTime(props.data.fecha_habilitado) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Switch/aviso: gestionar el retroactivo de este mismo mes -->
            <div v-if="puedeMostrarOpcionRetro" class="mb-3">
                <label v-if="retroMesCargado"
                    class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl border cursor-pointer select-none transition-colors"
                    :class="modoRetro ? 'bg-amber-50 border-amber-300 dark:bg-amber-900/10 dark:border-amber-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-700/40 dark:border-gray-600/40'">
                    <span class="text-sm font-semibold"
                        :class="modoRetro ? 'text-amber-700 dark:text-amber-400' : 'text-gray-600 dark:text-gray-300'">
                        Retroactivo
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 flex-shrink-0"
                        :class="modoRetro ? 'bg-amber-500' : 'bg-gray-300'">
                        <input type="checkbox" class="sr-only" v-model="modoRetro" />
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                            :class="modoRetro ? 'translate-x-4' : 'translate-x-1'"></span>
                    </span>
                </label>
                <div v-else
                    class="flex items-start gap-2 px-3 py-2 rounded-xl border border-amber-200 dark:border-amber-700/40 bg-amber-50 dark:bg-amber-900/10 text-xs text-amber-700 dark:text-amber-400">
                    <Icon :icon-button="true" name="alertTriangle" class-name="text-amber-500 flex-shrink-0 mt-0.5"
                        fill="none" stroke="currentColor" stroke-width="2" :size="14" />
                    <span>Para habilitar el <strong>Retroactivo</strong> de {{ getMonthNameFromNumber(props.data.mes)
                        }}, primero debe cargarse su Excel desde la vista de Gestión.</span>
                </div>
            </div>

            <!-- Switch: esta tarjeta YA es un retroactivo deshabilitado — permite
                 volver a gestionar el mes normal correspondiente -->
            <div v-if="puedeMostrarOpcionNormal" class="mb-3">
                <label
                    class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl border cursor-pointer select-none transition-colors"
                    :class="modoNormal ? 'bg-[rgb(var(--brand-50))] border-[rgb(var(--brand-300))] dark:bg-[rgba(var(--brand-900),0.1)] dark:border-[rgba(var(--brand-700),1)]' : 'bg-gray-50 border-gray-200 dark:bg-gray-700/40 dark:border-gray-600/40'">
                    <span class="text-sm font-semibold"
                        :class="modoNormal ? 'text-[rgb(var(--brand-700))] dark:text-[rgb(var(--brand-400))]' : 'text-gray-600 dark:text-gray-300'">
                        Mes normal
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 flex-shrink-0"
                        :class="modoNormal ? 'bg-[rgb(var(--brand-500))]' : 'bg-gray-300'">
                        <input type="checkbox" class="sr-only" v-model="modoNormal" />
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                            :class="modoNormal ? 'translate-x-4' : 'translate-x-1'"></span>
                    </span>
                </label>
            </div>

            <div class="py-2 space-y-3">
                <!-- Resumen del mes: si ya está habilitado no hay nada que mostrar
                     acá (todo el contenido de este bloque es para null/false), y
                     el título quedaba huérfano seguido de "Información del Pago".
                     Se gatea solo por habilitadoEfectivo (no por pagadoEfectivo):
                     un mes deshabilitado con un pago vigente colgando (caso de un
                     duplicado histórico donde se anuló la fila duplicada pero la
                     real seguía activa) también debe entrar acá para poder
                     reactivarse, aunque pagadoEfectivo diga 1. -->
                <div v-if="habilitadoEfectivo !== true">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2 text-xs uppercase tracking-wide">
                        Detalle del mes
                    </h4>
                    <!-- Informacion: Habilitar Mes / Retroactivo / Mes normal -->
                    <div v-if="habilitadoEfectivo === null && estado === 'activo'">
                        <!-- Aviso como banner destacado -->
                        <div class="rounded-xl flex flex-col items-center justify-center pt-3 gap-1.5">
                            <div class="p-3 rounded-xl flex items-start gap-3 border"
                                :class="modoRetro
                                    ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800'
                                    : 'bg-[rgb(var(--brand-50))] dark:bg-[rgba(var(--brand-900),0.1)] border-[rgb(var(--brand-200))] dark:border-[rgb(var(--brand-800))]'">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" :class="modoRetro ? 'text-amber-500' : 'text-[rgb(var(--brand-500))]'"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold"
                                        :class="modoRetro ? 'text-amber-800 dark:text-amber-300' : 'text-[rgb(var(--brand-800))] dark:text-[rgb(var(--brand-300))]'">
                                        Antes de continuar</p>
                                    <p class="text-xs" :class="modoRetro ? 'text-amber-700 dark:text-amber-400' : 'text-[rgb(var(--brand-700))] dark:text-[rgb(var(--brand-400))]'">
                                        Al habilitar {{ modoRetro ? 'este retroactivo' : modoNormal ? 'el mes normal' : 'este mes' }}
                                        quedará disponible para pago. Verificá que los datos del beneficiario sean correctos antes de proceder.
                                    </p>
                                    <p v-if="carnetEstado === 'vencido'"
                                        class="text-xs font-semibold text-red-600 dark:text-red-400">
                                        El carnet del beneficiario está vencido. No es posible habilitar {{ modoRetro ? 'el retroactivo' : modoNormal ? 'el mes normal' : 'el mes' }}.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Observación obligatoria -->
                        <div class="mt-3">
                            <label for="observacion-nuevo"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mt-0">
                                Observación <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <textarea id="observacion-nuevo" v-model="observacion" rows="2"
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-500),0.5)] focus:border-[rgb(var(--brand-500))] dark:bg-gray-700 dark:text-white transition-all resize-none text-sm"
                                    :placeholder="modoRetro ? 'Ingrese el motivo para habilitar el retroactivo...' : modoNormal ? 'Ingrese el motivo para habilitar el mes normal...' : 'Ingrese el motivo para habilitar el mes...'"></textarea>
                            </div>
                        </div>
                    </div>

                    <div v-if="!modoRetro && !modoNormal && ((estado === 'pagos_suspendidos') || (!props.data.observaciones_habilitado && estado !== 'activo'))"
                        :class="getModalBodyColor(estado, habilitado)" class="rounded-xl p-3 space-y-2">

                        <!-- Ícono grande -->
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-sm mx-auto" :class="{
                            'bg-yellow-100 dark:bg-yellow-900/20': estado === 'baja_temporal',
                            'bg-red-100 dark:bg-red-900/20': estado === 'baja_definitiva',
                            'bg-gray-100 dark:bg-gray-700': estado === 'depurado',
                            'bg-purple-100 dark:bg-purple-700': estado === 'pagos_suspendidos',
                        }">
                            <svg class="w-6 h-6 block shrink-0" :class="{
                                'text-yellow-500': estado === 'baja_temporal',
                                'text-red-500': estado === 'baja_definitiva',
                                'text-gray-400': estado === 'depurado',
                                'text-purple-400': estado === 'pagos_suspendidos',
                            }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Título -->
                        <div class="text-center">
                            <p class="text-base font-bold mb-1" :class="{
                                'text-yellow-700 dark:text-yellow-300': estado === 'baja_temporal',
                                'text-red-700 dark:text-red-300': estado === 'baja_definitiva',
                                'text-gray-600 dark:text-gray-300': estado === 'depurado',
                                'text-purple-600 dark:text-purple-300': estado === 'pagos_suspendidos',
                            }">
                                Mes no disponible
                            </p>
                            <p class="text-sm" :class="{
                                'text-yellow-600 dark:text-yellow-400': estado === 'baja_temporal',
                                'text-red-600 dark:text-red-400': estado === 'baja_definitiva',
                                'text-gray-500 dark:text-gray-400': estado === 'depurado',
                                'text-purple-500 dark:text-purple-400': estado === 'pagos_suspendidos',
                            }">
                                <span v-if="estado === 'baja_temporal'">Este mes se encuentra en <strong>Baja
                                        Temporal</strong>.
                                    No es posible habilitar el mes hasta que su estado sea regularizado.</span>
                                <span v-else-if="estado === 'baja_definitiva'">Este mes tiene una <strong>Baja
                                        Definitiva</strong>. El mes de pago no puede ser habilitado bajo ninguna
                                    circunstancia.</span>
                                <span v-else-if="estado === 'depurado'">Este mes ha sido <strong>Depurado</strong> del
                                    sistema. No es posible realizar ninguna acción sobre este mes.</span>
                                <span v-else-if="estado === 'pagos_suspendidos'">Este mes tiene los <strong>Pagos
                                        Suspendidos</strong>. El mes no generará cobro
                                    mientras la suspensión esté vigente.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Campo de observación -->
                    <div v-if="!modoRetro && habilitadoEfectivo === false && estado !== 'activo'">
                        <div :class="getStadoBadgeColor(estado)"
                            class="rounded-xl flex flex-col items-center justify-center py-3 gap-1.5">
                            <!-- Estado que impide reactivar -->
                            <template v-if="['baja_temporal', 'baja_definitiva', 'depurado'].includes(estado)">
                                <div class="p-3 dark:bg-red-900/30 flex items-center justify-center">
                                    <!-- Icono: Baja Temporal -->
                                    <Icon v-if="estado === 'baja_temporal'" :icon-button="true" name="timeCircle"
                                        class-name="text-yellow-800 pt-1" :size="32" :height="32" />
                                    <!-- Icono: Baja Definitiva -->
                                    <Icon v-if="estado === 'baja_definitiva'" :icon-button="true" name="circleMinus"
                                        class-name="text-red-600 pt-1" :size="32" :height="32" />
                                    <!-- Icono: Depurado -->
                                    <Icon v-if="estado === 'depurado'" :icon-button="true" name="depurado"
                                        class-name="text-gray-500 pt-1" :size="32" :height="32" />
                                </div>
                                <span :class="getStatusTextColor(estado)"
                                    class="text-xs font-medium text-center leading-tight">
                                    Mes deshabilitado.
                                </span>

                            </template>
                        </div>
                        <div :class="getStadoBadgeColor(estado)">
                            <p :class="getStatusTextColor(estado, 800)">
                                <span>Observación:</span>
                            </p>
                            <p class="text-xs mt-1" :class="getStatusTextColor(estado)">El mes fue deshabilitado por que
                                su
                                estado es
                                <span :class="getStatusTextColor(estado, 800)">{{ getStatusLabel(estado) }}</span>
                            </p>
                        </div>
                    </div>

                    <div v-if="!modoRetro && habilitadoEfectivo === false && estado === 'activo'">
                        <div class="rounded-xl flex flex-col items-center justify-center py-3 gap-2"
                            :class="pagadoEfectivo === 0 ? 'bg-red-50 dark:bg-red-900/10' : 'bg-gray-50 dark:bg-gray-700/40'">
                            <!-- Estado activo: se puede reactivar -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                :class="pagadoEfectivo === 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-amber-100 dark:bg-amber-900/30'">
                                <svg class="w-4 h-4" :class="pagadoEfectivo === 0 ? 'text-red-500' : 'text-amber-400'"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                </svg>
                            </div>

                            <!-- Pago anulado: bien visible, no como texto chico -->
                            <span v-if="pagadoEfectivo === 0"
                                class="px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                Pago Anulado
                            </span>

                            <span class="text-xs font-medium text-center leading-tight"
                                :class="pagadoEfectivo === 0 ? 'text-red-500 dark:text-red-400' : 'text-amber-500 dark:text-amber-400'">
                                <template v-if="pagadoEfectivo === 0">
                                    Mes deshabilitado.
                                </template>
                                <template v-else>
                                    Mes deshabilitado.<br>Actívalo para registrar el pago.
                                </template>
                            </span>
                        </div>

                        <div v-if="observacionesEfectivo"
                            class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-xs text-yellow-800">
                                <span class="font-semibold">Motivo Deshabilitado:</span> {{ observacionesEfectivo }}
                            </p>
                        </div>

                        <div class="mt-3">
                            <label for="observacion-habilitar"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mt-0">
                                Observación <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <textarea id="observacion-habilitar" v-model="observacion" rows="2"
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-500),0.5)] focus:border-[rgb(var(--brand-500))] dark:bg-gray-700 dark:text-white transition-all resize-none text-sm"
                                    :placeholder="modoNormal ? 'Ingrese el motivo para volver a habilitar el mes normal...' : 'Ingrese el motivo para volver a habilitar el mes...'"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informacion: Ya pagado / No pagado -->
                <div v-if="estado === 'activo' && habilitadoEfectivo === true">
                    <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm">Información del Pago
                    </h4>

                    <!-- Observación con la que se habilitó este mes: antes solo se
                         mostraba del lado deshabilitado, ahora se exige y se muestra
                         en ambos estados. -->
                    <div v-if="observacionesEfectivo" class="mb-2 p-2 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-xs text-green-800">
                            <span class="font-semibold">Motivo Habilitado:</span> {{ observacionesEfectivo }}
                        </p>
                    </div>

                    <!-- Ya Pagado -->
                    <div v-if="pagadoEfectivo === 1">
                        <div class="rounded-xl p-3 space-y-2"
                            :class="esTarjetaRetro ? 'bg-amber-50 dark:bg-amber-900/10' : 'bg-[rgb(var(--brand-50))] dark:bg-gray-700/50'">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Pago:</span>
                                    <span :class="esTarjetaRetro
                                        ? 'bg-amber-200 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                        : 'bg-green-200 text-green-800 dark:bg-green-900/30 dark:text-green-300'"
                                        class="px-2.5 py-1 rounded-full text-xs font-semibold">
                                        {{ esTarjetaRetro ? 'Retroactivo Pagado' : 'Pagado' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Monto:</span>
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400 text-sm">
                                        {{ formatCurrency(montoEfectivo) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Nº Boleta:</span>
                                    <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                        {{ boletaEfectivo }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Fecha Pago:</span>
                                    <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                        {{ formatDateTime(fechaPagoEfectivo) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- No pagado -->
                    <div v-else>
                        <div class="bg-[rgb(var(--brand-50))] dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Monto:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                    Bs. {{ formatCurrency(montoEfectivo) }}
                                </span>
                            </div>
                            <div class="flex flex-col items-center justify-center py-3 gap-1.5">
                                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                    <Icon :icon-button="true" name="dollar" class-name="text-gray-500" fill="none"
                                        stroke="currentColor" stroke-width="2" :size="23" />
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">Sin pago
                                    registrado</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="observacion"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mt-0">
                                Observación <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <textarea id="observacion" v-model="observacion" rows="2"
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-500),0.5)] focus:border-[rgb(var(--brand-500))] dark:bg-gray-700 dark:text-white transition-all resize-none text-sm"
                                    placeholder="Ingrese el motivo para deshabilitar el pago..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center gap-3">
                    <Button @click="$emit('close')" :disabled="processing"
                        :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border'"
                        :class="isSoloAceptar ? 'text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]' : 'text-slate-700 bg-slate-100 hover:bg-slate-200 border-gray-200'">
                        Aceptar
                    </Button>

                    <div v-if="!isSoloAceptar">
                        <Button v-if="can('habilitar-habilitar') || can('deshabilitar-habilitar')" @click="handleAction"
                            :disabled="processing" :style="'py-3 px-5 sm:px-12 sm:py-2.5 rounded-xl'"
                            class="text-white relative flex items-center justify-center min-w-[160px]" :class="processing
                                ? 'bg-[rgb(var(--brand-400))] cursor-not-allowed'
                                : modoRetro
                                    ? 'bg-amber-500 hover:bg-amber-400'
                                    : esTarjetaRetro
                                        ? 'bg-amber-700 hover:bg-amber-600'
                                        : 'bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]'">

                            <!-- Texto real: SIEMPRE está en el DOM, solo se hace invisible -->
                            <!-- Esto es lo que le da tamaño estable al botón -->
                            <span class="whitespace-nowrap" :class="processing ? 'invisible' : ''">
                                {{ modoRetro
                                    ? 'Habilitar Retroactivo'
                                    : modoNormal
                                        ? `${habilitadoEfectivo ? 'Deshabilitar' : 'Habilitar'} Mes Normal`
                                        : esTarjetaRetro
                                            ? `${habilitadoEfectivo ? 'Deshabilitar' : 'Habilitar'} Retroactivo`
                                            : `${!props.data.id_habilitado ? 'Habilitar' : habilitadoEfectivo ? 'Deshabilitar' : 'Habilitar'} Mes` }}
                            </span>

                            <!-- Spinner superpuesto, no afecta el tamaño del botón -->
                            <span v-if="processing" class="absolute inset-0 flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                </svg>
                                Procesando...
                            </span>
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </Modal>
</template>
