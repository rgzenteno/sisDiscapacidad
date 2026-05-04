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
});

//console.log('Props:', props.data);

const emit = defineEmits(['habilitar', 'add', 'close', 'cambioEstado', 'addEstado', 'sinDatos'])

const isModalOpen = ref(false)
const habilitado = computed(() => props.data.habilitado) // true, false
const estado = computed(() => props.data.estado_mes)
const observacion = ref(props.data.observaciones_habilitado || '')


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

const getStatusColor = (status) => {
    const colors = {
        activo: 'text-green-600',
        baja_temporal: 'text-yellow-600',
        baja_definitiva: 'text-red-600'
    }
    return colors[status] || 'text-gray-600'
}

const getIconBgColor = (status) => {
    const colors = {
        activo: 'bg-gradient-to-br from-green-400 to-emerald-300',
        baja_temporal: 'bg-gradient-to-br from-yellow-400 to-amber-300',
        baja_definitiva: 'bg-gradient-to-br from-red-400 to-rose-300'
    }
    return colors[status] || 'bg-gradient-to-br from-gray-500 to-gray-400'
}

const getModalBodyColor = (status, enabled) => {
    if (!enabled) return 'bg-gray-50 dark:bg-gray-800'

    const colors = {
        activo: 'bg-green-50 dark:bg-green-900/10',
        baja_temporal: 'bg-yellow-50 dark:bg-yellow-900/10',
        baja_definitiva: 'bg-red-50 dark:bg-red-900/10'
    }
    return colors[status] || 'bg-gray-50 dark:bg-gray-800'
}

const getStatusIndicator = (status) => {
    const colors = {
        activo: 'bg-green-500',
        baja_temporal: 'bg-yellow-500',
        baja_definitiva: 'bg-red-500'
    }
    return colors[status] || 'bg-gray-500'
}

const getStatusBadgeColor = (status) => {
    const colors = {
        activo: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        baja_temporal: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        baja_definitiva: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStadoBadgeColor = (status) => {
    const colorStado = {
        activo: 'mt-2 p-2 bg-green-50 border border-green-200 rounded-lg',
        baja_temporal: 'mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg',
        baja_definitiva: 'mt-2 p-2 bg-red-50 border border-red-200 rounded-lg',
        depurado: 'mt-2 p-2 bg-gray-50 border border-gray-200 rounded-lg'
    }
    return colorStado[status] || 'bg-gray-100 text-gray-800'
}

const isDisabled = computed(() => {
    if (!habilitado.value) {
        return !can('habilitar-habilitar') || estado.value !== 'activo' || carnetEstado.value === 'vencido'
    }
    return !can('deshabilitar-habilitar') || estado.value === 'baja_temporal' || estado.value === 'baja_definitiva' || props.data.pagado === 1
})

const actionButtonColor = computed(() => {
    if (isDisabled.value) return 'bg-gray-400 hover:bg-gray-400 cursor-not-allowed'
    return habilitado.value ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'
})

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
    };
    return colors[estado] ?? '';
};

const handleAction = () => {
    // Si no tiene id_habilitado, es una nueva habilitación
    if (!props.data.id_habilitado) {
        /* if (observacion.value.trim() === '') {
            alert('Debe ingresar una observación')
            return
        } */
        emit('habilitar', props.data)
        return
    }

    // Si ya tiene id_habilitado, es un cambio de estado
    // Solo requerir observación al DESHABILITAR
    if (habilitado.value && observacion.value.trim() === '') {
        emit('sinDatos');
        return
    }

    if (!habilitado.value && estado.value === 'baja_definitiva') {
        alert('No se puede habilitar el pago para beneficiarios con baja definitiva')
        return
    }

    // Emitir el cambio de estado
    emit('cambioEstado', {
        id_habilitado: props.data.id_habilitado,
        habilitado: habilitado.value ? 0 : 1,
        observacion: habilitado.value ? observacion.value.trim() : ''
    })
}

const isSoloAceptar = computed(() =>
    Number(props.data.pagado) === 1 || estado.value !== 'activo'
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
            <div :class="getIconBgColor(estado)" class="w-10 h-10 rounded-xl flex items-center justify-center ">
                <Icon :icon-button="true" name="calendarMontSolid" class-name="w-5 h-5 text-white" :size="17" />
            </div>
        </template>
        <template #label1>{{ getMonthNameFromNumber(props.data.mes) }} - {{ props.data.gestion }}</template>
        <template #label2>
            Mes de pago del beneficiario
        </template>

        <!-- Body -->
        <div>
            <!-- Estado del beneficiario -->
            <div class="mb-3">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm">Información del Mes</h4>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Estado:</span>
                        <div :class="getStatusBadgeColor(estado)"
                            class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">
                            {{ getStatusLabel(estado) }}
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Mes:</span>
                        <div :class="habilitado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            class="px-2.5 py-1 rounded-full text-xs font-semibold">
                            {{ habilitado ? 'Habilitado' : habilitado === false ?
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

            <div class="py-2 space-y-3">
                <!-- Resumen del mes -->
                <div v-if="!props.data.pagado">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2 text-xs uppercase tracking-wide">
                        Detalle del mes
                    </h4>
                    <!-- Informacion: Habilitar Mes -->
                    <div v-if="habilitado === null && estado === 'activo'">
                        <div
                            class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-xl p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Monto a cobrar:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                    Bs. {{ formatCurrency(props.data.monto) }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Vigencia carnet:</span>
                                <div :class="{
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': carnetEstado === 'indefinido',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': carnetEstado === 'vencido',
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': carnetEstado === 'vigente',
                                }" class="px-2.5 py-1 rounded-full text-xs font-semibold">
                                    {{ carnetEstado === 'indefinido' ? 'Indefinido' : carnetEstado === 'vencido' ?
                                        'Vencido' :
                                        'Vigente' }}
                                </div>
                            </div>

                        </div>
                        <!-- Aviso como banner destacado -->
                        <div class="rounded-xl flex flex-col items-center justify-center pt-3 gap-1.5">
                            <div
                                class="p-3 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-3 ">
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-blue-800 dark:text-blue-300">Antes de continuar</p>
                                    <p class="text-xs text-blue-700 dark:text-blue-400">
                                        Al habilitar este mes quedará disponible para pago. Verificá que los datos del
                                        beneficiario sean
                                        correctos antes de proceder.
                                    </p>
                                    <p v-if="carnetEstado === 'vencido'"
                                        class="text-xs font-semibold text-red-600 dark:text-red-400">
                                        El carnet del beneficiario está vencido. No es posible habilitar el mes.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!props.data.observaciones_habilitado && estado !== 'activo'"
                        :class="getModalBodyColor(estado, habilitado)" class=" rounded-xl p-3 space-y-2">
                        <!-- Ícono grande -->
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-sm mx-auto" :class="{
                            'bg-yellow-100 dark:bg-yellow-900/20': estado === 'baja_temporal',
                            'bg-red-100 dark:bg-red-900/20': estado === 'baja_definitiva',
                            'bg-gray-100 dark:bg-gray-700': estado === 'depurado',
                        }">
                            <svg class="w-6 h-6  block shrink-0" :class="{
                                'text-yellow-500': estado === 'baja_temporal',
                                'text-red-500': estado === 'baja_definitiva',
                                'text-gray-400': estado === 'depurado',
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
                            }">
                                Mes no disponible
                            </p>
                            <p class="text-sm" :class="{
                                'text-yellow-600 dark:text-yellow-400': estado === 'baja_temporal',
                                'text-red-600 dark:text-red-400': estado === 'baja_definitiva',
                                'text-gray-500 dark:text-gray-400': estado === 'depurado',
                            }">
                                <span v-if="estado === 'baja_temporal'">Este mes se encuentra en <strong>Baja
                                        Temporal</strong>.
                                    No es posible habilitar el mes hasta que su estado sea regularizado.</span>
                                <span v-else-if="estado === 'baja_definitiva'">Este mes tiene una <strong>Baja
                                        Definitiva</strong>. El mes de pago no puede ser habilitado bajo ninguna
                                    circunstancia.</span>
                                <span v-else-if="estado === 'depurado'">Este mes ha sido <strong>Depurado</strong> del
                                    sistema.
                                    No es posible realizar ninguna acción sobre este mes.</span>
                            </p>
                        </div>
                    </div>

                    <!-- Campo de observación -->
                    <div v-if="habilitado === false && estado !== 'activo'">
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
                                    Mes deshabilitado.<br>No se puede activar.
                                </span>

                            </template>
                        </div>
                        <div :class="getStadoBadgeColor(estado)">
                            <p :class="getStatusTextColor(estado, 800)">
                                <span>Observación:</span>
                            </p>
                            <p class="text-xs mt-1" :class="getStatusTextColor(estado)">El mes fue deshabilitado por que su estado es
                                <span :class="getStatusTextColor(estado, 800)">{{ getStatusLabel(estado) }}</span>
                            </p>
                        </div>
                    </div>

                    <div v-if="habilitado === false && estado === 'activo'">
                        <div class="bg-gray-50 rounded-xl flex flex-col items-center justify-center py-3 gap-1.5">
                            <!-- Estado activo: se puede reactivar -->
                            <div
                                class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                </svg>
                            </div>
                            <span
                                class="text-xs text-amber-500 dark:text-amber-400 font-medium text-center leading-tight">
                                Mes deshabilitado.<br>Actívalo para registrar el pago.
                            </span>
                        </div>

                        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-xs text-yellow-800">
                                <span class="font-semibold">Observación:</span> {{
                                    props.data.observaciones_habilitado
                                }}
                            </p>
                            <p class="text-xs text-yellow-600 mt-1">Al habilitar el mes, esta observación será
                                eliminada.</p>
                        </div>
                    </div>
                </div>

                <!-- Informacion: Ya pagado / No pagado -->
                <div v-if="estado === 'activo' && habilitado === true">
                    <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm">Información del Pago
                    </h4>
                    <!-- Ya Pagado -->
                    <div v-if="props.data.pagado === 1">
                        <div class="bg-blue-50 dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Pago:</span>
                                    <span
                                        class="bg-green-200 text-green-800 dark:bg-green-900/30 dark:text-green-300 px-2.5 py-1 rounded-full text-xs font-semibold">
                                        Pagado
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Monto:</span>
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400 text-sm">
                                        {{ formatCurrency(props.data.monto) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Nº Boleta:</span>
                                    <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                        {{ props.data.boleta }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300 text-sm">Fecha Pago:</span>
                                    <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                        {{ formatDateTime(props.data.fecha_pago) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- No pagado -->
                    <div v-else>
                        <div class="bg-blue-50 dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                            <div class="flex flex-col items-center justify-center py-3 gap-1.5">
                                <div
                                    class="w-8 h-8 rounded-full bg-green-300 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
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
                                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all resize-none text-sm"
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
                    <Button @click="$emit('close')" :style="'px-5 sm:px-12 py-2.5 rounded-xl border-2 border-gray-200'"
                        :class="isSoloAceptar ? 'text-white bg-blue-600 hover:bg-blue-500' : 'text-gray-700 hover:bg-gray-200'">
                        {{ isSoloAceptar ? 'Aceptar' : 'Cancelar' }}
                    </Button>

                    <div v-if="props.data.pagado === 0 && estado === 'activo'">
                        <Button v-if="can('habilitar-habilitar') || can('deshabilitar-habilitar')" @click="handleAction"
                            :style="'px-5 sm:px-12 py-2.5 rounded-xl border-2 border-gray-200'" class="text-white bg-blue-600 hover:bg-blue-500">
                            {{ !props.data.id_habilitado ? 'Habilitar' : habilitado ? 'Deshabilitar' : 'Habilitar' }}
                            Mes
                        </Button>
                    </div>
                </div>
            </div>
        </template>
    </Modal>
</template>
