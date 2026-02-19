<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { can } from '@/lib/can';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
});

//console.log('Props en ModalHabilitar:', props.data);

const emit = defineEmits(['habilitar', 'add', 'close', 'cambioEstado', 'addEstado', 'sinDatos'])

const isModalOpen = ref(false)
const habilitado = ref(props.data.habilitado) // true, false
const estado = ref(props.data.estado_mes) // activo, baja_temporal, baja_definitiva
const observacion = ref(props.data.observaciones_habilitado || '')

const pagoInfo = {
    mes: 'Septiembre 2024',
    monto: 'Bs. 1,000',
    fechaLimite: '30/09/2024'
}

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

const getStatusLabel = (status) => {
    const labels = {
        activo: 'Activo',
        baja_temporal: 'Baja Temporal',
        baja_definitiva: 'Baja Definitiva'
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
        activo: 'bg-gradient-to-br from-green-500 to-emerald-400',
        baja_temporal: 'bg-gradient-to-br from-yellow-500 to-amber-400',
        baja_definitiva: 'bg-gradient-to-br from-red-500 to-rose-400'
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

const getDisabledState = () => {
    // Si va a habilitar (botón dice "Habilitar")
    if (!habilitado.value) {
        // Verifica el permiso de habilitar Y las condiciones originales
        return !can('habilitar-habilitar') || estado.value !== 'activo' || isCarnetVencido();
    }
    // Si va a deshabilitar (botón dice "Deshabilitar")
    else {
        // Verifica el permiso de deshabilitar
        return !can('deshabilitar-habilitar');
    }
};

const getActionButtonColor = () => {
    if (!habilitado.value && (estado.value !== 'activo' || isCarnetVencido())) {
        return 'bg-gray-400 text-white cursor-not-allowed'
    }

    return habilitado.value ?
        'bg-red-600 hover:bg-red-700 text-white' :
        'bg-green-600 hover:bg-green-700 text-white'
}

const isCarnetVencido = () => {
    if (!props.data.fecha_vencimiento) return false;

    const fechaVencimiento = new Date(props.data.fecha_vencimiento);

    // Crear fecha del mes de pago (primer día del mes)
    const fechaPago = new Date(props.data.gestion, props.data.mes - 1, 1);

    // Comparar: si la fecha de pago es después del vencimiento, está vencido
    return fechaPago > fechaVencimiento;
}

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
        observacion: habilitado.value ? observacion.value.trim() : '' // Limpiar al habilitar
    })
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close')
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>
<template>
    <!-- Modal -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4 z-40">
        <div
            class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div
                class="relative px-5 pt-3 pb-2 border-b border-gray-100 dark:border-gray-600/50 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 flex-shrink-0">
                <!-- Botón cerrar -->
                <button type="button" @click="$emit('close')"
                    class="absolute top-3 right-3 p-1.5 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>

                <!-- Contenido del header -->
                <div class="pr-10">
                    <div class="flex items-center gap-3 mb-2">
                        <!-- Avatar / Ícono -->
                        <div :class="getIconBgColor(estado)"
                            class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md ring-1 ring-white/20 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Título y subtítulo -->
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">
                                Gestión de Pago
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-blue-300">
                                Bono Discapacidad - {{ getMonthNameFromNumber(props.data.mes) }} {{ props.data.gestion
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Body con scroll -->
            <div :class="getModalBodyColor(estado, habilitado)" class="flex-1 overflow-y-auto">
                <div class="p-5 pt-2">
                    <!-- Estado del beneficiario -->
                    <div class="mb-3">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-2 flex items-center gap-2 text-sm">
                            <div :class="getStatusIndicator(estado)" class="w-2.5 h-2.5 rounded-full"></div>
                            Estado del Beneficiario
                        </h4>
                        <div class="bg-white/50 dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Estado actual:</span>
                                <div :class="getStatusBadgeColor(estado)"
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">
                                    {{ getStatusLabel(estado) }}
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Pago
                                    habilitado:</span>
                                <div :class="!props.data.id_habilitado ? 'bg-gray-100 text-gray-800' : habilitado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold">
                                    {{ !props.data.id_habilitado ? 'Sin Habilitar' : habilitado ? 'Habilitado' :
                                        'Deshabilitado' }}
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">Estado carnet:</span>
                                <div :class="isCarnetVencido() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                    class="px-2.5 py-1 rounded-full text-xs font-semibold">
                                    {{ isCarnetVencido() ? 'Vencido' : 'Activo' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del pago -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-2 text-sm">Información del Pago</h4>
                        <div class="bg-white/50 dark:bg-gray-700/50 rounded-xl p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Mes de pago:</span>
                                <span class="font-semibold text-gray-800 dark:text-white text-sm">{{
                                    getMonthNameFromNumber(props.data.mes) }} {{ props.data.gestion }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Monto:</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400 text-sm">{{
                                    formatCurrency(props.data.monto) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Fecha Habilitado:</span>
                                <span v-if="props.data.fecha_habilitado"
                                    class="font-semibold text-gray-800 dark:text-white text-sm">{{
                                        props.data.fecha_habilitado }}</span>
                                <span v-else>--</span>
                            </div>
                        </div>
                    </div>

                    <!-- Campo de observación -->
                    <div class="mb-5">
                        <label for="observacion"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Observación <span v-if="props.data.id_habilitado" class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <textarea id="observacion" v-model="observacion" rows="3"
                                class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-all resize-none text-sm"
                                :placeholder="habilitado ? 'Ingrese el motivo para deshabilitar el pago...' : 'Ingrese observación para habilitar...'"></textarea>
                        </div>
                        <div v-if="props.data.observaciones_habilitado && !habilitado"
                            class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-xs text-yellow-800">
                                <span class="font-semibold">Observación anterior:</span> {{
                                    props.data.observaciones_habilitado }}
                            </p>
                            <p class="text-xs text-yellow-600 mt-1">
                                Al habilitar, esta observación será eliminada.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer con botones -->
            <div
                class="px-5 py-3 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-600/50 flex-shrink-0 rounded-b-3xl">
                <div class="flex gap-2">
                    <button @click="$emit('close')"
                        class="flex-1 px-4 py-2 border-2 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-medium text-sm">
                        Cancelar
                    </button>
                    <button v-if="can('habilitar-habilitar') || can('deshabilitar-habilitar')" @click="handleAction"
                        :disabled="getDisabledState()" :class="getActionButtonColor()"
                        class="flex-1 px-4 py-2 rounded-xl transition-all font-medium disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                        {{ !props.data.id_habilitado ? 'Habilitar' : habilitado ? 'Deshabilitar' : 'Habilitar' }} Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
