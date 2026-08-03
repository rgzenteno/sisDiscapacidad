<script setup>

import { computed, onMounted, onUnmounted, ref } from 'vue'
import Icon from '@/components/Icon.vue';
import Button from '@/components/Button.vue';
import { can, hasRole } from '@/lib/can';

import Modal from "@/components/Modal.vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    mesesDisponibles: {
        type: Array,
        default: () => []
    },
});

console.log('datos:', props.data);

// Emits
const emit = defineEmits(['add', 'close', 'cambioEstado', 'addEstado', 'delete', 'editEstado', 'editObservacion', 'insertarIntermedio', 'agregarMesExtra']);

// Computed para obtener el historial completo desde props.data
const historialEstados = computed(() => {
    if (props.data.historial_completo && Array.isArray(props.data.historial_completo)) {
        return props.data.historial_completo.map((registro, index) => {

            const hasta = index === 0
                ? 'Actual'
                : registro.fecha_fin;

            return {
                ...registro,
                desde: registro.fecha_inicio,
                hasta,
                usuario: registro.usuario_modificacion || 'Sistema',
                observacion: registro.observaciones
            };
        });
    }
    return [];
});


/**
 * Meses registrados en el sistema (tabla mes) que quedan estrictamente
 * entre dos cards consecutivos de la línea de tiempo — son los meses
 * "disponibles" para insertar un estado intermedio ahí.
 * @param {Object} registroNuevo - card más reciente (menor índice)
 * @param {Object} registroViejo - card más antiguo (mayor índice)
 */
const mesesEntreCards = (registroNuevo, registroViejo) => {
    if (!registroNuevo || !registroViejo) return [];

    const [anioNuevo, mesNuevo] = String(registroNuevo.fecha_inicio).split('T')[0].split('-').map(Number);
    const [anioViejo, mesViejo] = String(registroViejo.fecha_inicio).split('T')[0].split('-').map(Number);

    return props.mesesDisponibles.filter(m => {
        const anio = Number(m.gestion);
        const mes = Number(m.mes);
        const despuesDelViejo = anio > anioViejo || (anio === anioViejo && mes > mesViejo);
        const antesDelNuevo = anio < anioNuevo || (anio === anioNuevo && mes < mesNuevo);
        // Un mes ya pagado siempre queda como "activo": no cuenta como hueco
        // disponible para insertar otro estado.
        const yaPagado = (props.data.meses_pagados ?? []).some(
            p => String(p.gestion) === String(anio) && Number(p.mes) === mes && !p.anulado
        );
        return despuesDelViejo && antesDelNuevo && !yaPagado;
    });
};

/**
 * Mes siguiente al último registrado en la tabla `mes` (aún no tiene fila
 * propia, todavía no se cargó su PDF). Es el único mes que un no-superusuario
 * puede gestionar.
 */
const mesExtra = computed(() => {
    if (!props.mesesDisponibles.length) return null;

    const ultimo = [...props.mesesDisponibles].sort((a, b) => {
        const av = Number(a.gestion) * 100 + Number(a.mes);
        const bv = Number(b.gestion) * 100 + Number(b.mes);
        return av - bv;
    }).pop();

    let mes = Number(ultimo.mes) + 1;
    let gestion = Number(ultimo.gestion);
    if (mes > 12) { mes = 1; gestion++; }

    return { gestion, mes };
});

/**
 * Si el estado más reciente del beneficiario ya empieza en el mes extra
 * (o después), ya está cubierto y no hace falta ofrecer el botón.
 */
const mesExtraDisponible = computed(() => {
    if (!mesExtra.value || !historialEstados.value.length) return false;

    const actual = historialEstados.value[0];
    const [anioActual, mesActual] = String(actual.fecha_inicio).split('T')[0].split('-').map(Number);

    if (anioActual > mesExtra.value.gestion) return false;
    if (anioActual === mesExtra.value.gestion && mesActual >= mesExtra.value.mes) return false;

    return true;
});

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


// Helpers de fecha por mes
const formatearMes = (fecha) => {
    if (!fecha || fecha === 'Actual') return 'En curso'

    let d

    if (fecha instanceof Date) {
        d = fecha
    } else {
        const limpio = fecha.split(' ')[0]
        d = new Date(limpio + 'T00:00:00')
    }

    const mes = d.toLocaleDateString('es-ES', { month: 'long' })
    const anio = d.getFullYear()

    return `${mes.charAt(0).toUpperCase() + mes.slice(1)} - ${anio}`
}

// Configuración visual centralizada por estado: evita mantener el mismo
// switch/case de colores duplicado en el badge del header, el punto del
// timeline y el card (antes vivían por separado y se desincronizaban).
const ESTADOS_CONFIG = {
    activo: {
        label: 'Activo',
        icon: 'checkCircle',
        iconText: 'text-emerald-600',
        badge: 'bg-emerald-50 text-emerald-700',
        dot: 'bg-emerald-400',
        iconBg: 'bg-emerald-100',
        cardBg: 'bg-emerald-50/40',
        cardBorder: 'border-emerald-100',
    },
    baja_temporal: {
        label: 'Baja Temporal',
        icon: 'timeCircle',
        iconText: 'text-amber-600',
        badge: 'bg-amber-50 text-amber-700',
        dot: 'bg-amber-400',
        iconBg: 'bg-amber-100',
        cardBg: 'bg-amber-50/40',
        cardBorder: 'border-amber-100',
    },
    baja_definitiva: {
        label: 'Baja Definitiva',
        icon: 'circleMinus',
        iconText: 'text-rose-600',
        badge: 'bg-rose-50 text-rose-700',
        dot: 'bg-rose-400',
        iconBg: 'bg-rose-100',
        cardBg: 'bg-rose-50/40',
        cardBorder: 'border-rose-100',
    },
    depurado: {
        label: 'Depurado',
        icon: 'depurado',
        iconText: 'text-slate-500',
        badge: 'bg-slate-100 text-slate-600',
        dot: 'bg-slate-400',
        iconBg: 'bg-slate-200',
        cardBg: 'bg-slate-50',
        cardBorder: 'border-slate-200',
    },
    pagos_suspendidos: {
        label: 'Pagos Suspendidos',
        icon: 'cash',
        iconText: 'text-violet-600',
        badge: 'bg-violet-50 text-violet-700',
        dot: 'bg-violet-400',
        iconBg: 'bg-violet-100',
        cardBg: 'bg-violet-50/40',
        cardBorder: 'border-violet-100',
    },
};

const ESTADO_DEFAULT = {
    label: 'Sin estado',
    icon: 'exclamationCircle',
    iconText: 'text-slate-400',
    badge: 'bg-slate-100 text-slate-600',
    dot: 'bg-slate-300',
    iconBg: 'bg-slate-200',
    cardBg: 'bg-white',
    cardBorder: 'border-slate-100',
};

const getEstadoConfig = (estado) => (estado && ESTADOS_CONFIG[estado.toLowerCase()]) || ESTADO_DEFAULT;

const toTitleCase = (str) => {
    if (!str) return '';
    return str.toLowerCase().replace(/(?:^|\s)\S/g, c => c.toUpperCase());
};

const nombreDisplay = computed(() => {
    if (props.data.nombre_persona && props.data.apellido_persona) {
        return `${props.data.nombre_persona} ${props.data.apellido_persona}`;
    }
    return props.data.nombre_completo || 'Sin nombre';
});

const ciCompleto = computed(() => {
    let ci = props.data.ci_persona;
    if (props.data.complemento) {
        ci += `-${props.data.complemento}`;
    }
    return ci;
});

// Métodos
const cerrarModal = () => {
    emit('close')
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        cerrarModal();
    }
};

// Motivo expandible: solo uno a la vez, y se contrae al hacer clic en
// cualquier lugar que no sea el propio bloque expandible.
const motivoExpandidoId = ref(null);

const toggleMotivo = (id) => {
    motivoExpandidoId.value = motivoExpandidoId.value === id ? null : id;
};

const handleClickFueraDeMotivo = (e) => {
    if (!e.target.closest('.motivo-expandible')) {
        motivoExpandidoId.value = null;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

onMounted(() => document.addEventListener('mousedown', handleClickFueraDeMotivo));
onUnmounted(() => document.removeEventListener('mousedown', handleClickFueraDeMotivo));
</script>

<template>
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-xl" @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="user" class-name="text-white" :size="20" />
            </div>
        </template>
        <template #label1>
            Gestión de Estados
        </template>
        <template #label2> {{ toTitleCase(nombreDisplay) }} • {{ ciCompleto }}</template>

        <template #badge>
            <div class="hidden sm:block">
                <span
                    :class="[getEstadoConfig(props.data.estado_actual.estado).badge, 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold shadow-sm whitespace-nowrap']">
                    <Icon :icon-button="true" :name="getEstadoConfig(props.data.estado_actual.estado).icon"
                        :class-name="getEstadoConfig(props.data.estado_actual.estado).iconText" :size="16" />
                    {{ getEstadoConfig(props.data.estado_actual.estado).label }}
                </span>
            </div>
        </template>

        <!-- Body -->
        <div>

            <!-- Timeline / historial -->
            <div class="">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="sm:text-lg font-semibold text-slate-800">Historial de Estados</h3>
                    <p class="text-sm text-slate-500">{{ historialEstados.length }} estados</p>
                </div>

                <div class="relative pl-2 sm:pl-6 flex flex-col items flex-1 ">
                    <!-- línea vertical del timeline -->
                    <div
                        class="absolute left-0 sm:left-3 top-2 bottom-2 w-px bg-gradient-to-b from-cyan-300 to-indigo-200">
                    </div>

                    <!-- Historial con scroll -->
                    <div class="max-h-[50vh] space-y-1 overflow-y-auto sm:pr-2 text-sm ">
                        <!-- Botón "+": mes siguiente al último registrado en el sistema (aún no tiene fila en `mes`) -->
                        <div v-if="mesExtraDisponible && can('agregar-estado')" class="relative flex items-start gap-4">
                            <div class="absolute left-[-6px] top-1/2 -translate-y-1/2">
                                <span class="block w-3 h-3 rounded-full ring-2 ring-white bg-emerald-400"></span>
                            </div>
                            <div class="w-full">
                                <button @click="emit('agregarMesExtra', mesExtra)"
                                    title="Agregar estado para el mes siguiente al último registrado"
                                    class="w-full flex items-center justify-center py-2 rounded-xl border border-dashed border-emerald-300 text-emerald-500 hover:bg-emerald-50 hover:border-emerald-400 transition-colors">
                                    <Icon :icon-button="true" name="circlePlus" class-name="" :size="18" />
                                </button>
                            </div>
                        </div>

                        <template v-for="(registro, index) in historialEstados" :key="registro.id || index">
                            <div class="relative flex items-start gap-4">
                                <!-- punto del timeline -->
                                <div class="absolute left-[-6px] top-4">
                                    <span class="block w-3 h-3 rounded-full ring-2 ring-white"
                                        :class="getEstadoConfig(registro.estado).dot"></span>
                                </div>

                                <div class="w-full">
                                    <div class="rounded-xl border px-3.5 py-2.5 transition-colors duration-200"
                                        :class="[getEstadoConfig(registro.estado).cardBg, getEstadoConfig(registro.estado).cardBorder]">

                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="flex items-center justify-center w-7 h-7 rounded-full flex-shrink-0"
                                                    :class="getEstadoConfig(registro.estado).iconBg">
                                                    <Icon :icon-button="true" :name="getEstadoConfig(registro.estado).icon"
                                                        :class-name="getEstadoConfig(registro.estado).iconText" :size="14" />
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-700 leading-tight truncate">
                                                        {{ getEstadoConfig(registro.estado).label }}
                                                    </p>
                                                    <p class="text-[11px] text-slate-400 truncate">
                                                        {{ formatearMes(registro.desde) }}
                                                        <span v-if="registro.hasta === 'Actual'"
                                                            class="text-emerald-600 font-medium">→ En curso</span>
                                                        <span v-else-if="registro.hasta && formatearMes(registro.hasta) !== formatearMes(registro.desde)">→ {{ formatearMes(registro.hasta) }}</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Acciones MOBILE -->
                                            <div class="flex items-center gap-0.5 flex-shrink-0 sm:hidden">
                                                <button
                                                    v-if="registro.puede_gestionar && index < historialEstados.length - 1 && can('eliminar-estado')"
                                                    @click="emit('delete', registro.id)" title="Eliminar registro"
                                                    class="flex items-center p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                                    <Icon :icon-button="true" name="depurado" class-name=""
                                                        :size="15" />
                                                </button>
                                                <button
                                                    v-if="registro.puede_gestionar && can('agregar-estado') && !props.data.meses_pagados?.length"
                                                    @click="emit('editEstado', { ...registro, estado_anterior: historialEstados[index + 1]?.estado, es_primer_estado: index === historialEstados.length - 1 })"
                                                    title="Editar estado"
                                                    class="flex items-center p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                    <Icon :icon-button="true" name="edit" class-name=""
                                                        :size="15" />
                                                </button>
                                                <!-- Estado no gestionable (histórico/cerrado): solo se permite
                                                     corregir el texto de la observación, nada más. -->
                                                <button
                                                    v-if="!registro.puede_gestionar && can('agregar-estado')"
                                                    @click="emit('editObservacion', registro)"
                                                    title="Editar solo la observación"
                                                    class="flex items-center p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                    <Icon :icon-button="true" name="clipboard" class-name=""
                                                        :size="15" />
                                                </button>
                                            </div>

                                            <!-- Acciones DESKTOP -->
                                            <div class="hidden sm:flex items-center gap-0.5 flex-shrink-0">
                                                <button
                                                    v-if="registro.puede_gestionar && index < historialEstados.length - 1 && can('eliminar-estado')"
                                                    @click="emit('delete', registro.id)" title="Eliminar registro"
                                                    class="flex items-center p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                                    <Icon :icon-button="true" name="depurado" class-name=""
                                                        :size="15" />
                                                </button>
                                                <button v-if="registro.puede_gestionar && can('agregar-estado')"
                                                    @click="emit('editEstado', { ...registro, estado_anterior: historialEstados[index + 1]?.estado, es_primer_estado: index === historialEstados.length - 1 })"
                                                    title="Editar estado"
                                                    class="flex items-center p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                    <Icon :icon-button="true" name="edit" class-name=""
                                                        :size="15" />
                                                </button>
                                                <!-- Estado no gestionable (histórico/cerrado): solo se permite
                                                     corregir el texto de la observación, nada más. -->
                                                <button
                                                    v-if="!registro.puede_gestionar && can('agregar-estado')"
                                                    @click="emit('editObservacion', registro)"
                                                    title="Editar solo la observación"
                                                    class="flex items-center p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                                    <Icon :icon-button="true" name="clipboard" class-name=""
                                                        :size="15" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Meta + toggle de motivo: el motivo permanece oculto hasta
                                             que se presiona "ver más", para que el card no crezca de
                                             entrada. -->
                                        <div class="mt-1.5 flex items-center justify-between gap-2 motivo-expandible">
                                            <span class="text-[11px] text-slate-400 truncate">
                                                {{ formatDateTime(registro.fecha_registro) }} · <span
                                                    class="capitalize">{{ toTitleCase((registro.usuario ||
                                                        'Sistema').split(' ').slice(0, 2).join(' ')) }}</span>
                                            </span>
                                            <button @click="toggleMotivo(registro.id)"
                                                class="text-[11px] text-[rgb(var(--brand-600))] hover:text-[rgb(var(--brand-700))] hover:underline font-medium whitespace-nowrap flex-shrink-0">
                                                {{ motivoExpandidoId === registro.id ? 'ver menos' : 'ver más' }}
                                            </button>
                                        </div>

                                        <div v-if="motivoExpandidoId === registro.id"
                                            class="mt-2 text-xs text-slate-500 bg-slate-50 rounded-lg px-2.5 py-2 motivo-expandible">
                                            <span v-if="registro.observacion" class="block mb-1 text-slate-400">
                                                {{ registro.observacion }}
                                            </span>
                                            <span class="font-medium text-slate-500">Motivo: </span>{{ registro.motivo || 'Sin motivo' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón "+": aparece si hay meses de la tabla `mes` sin card propio entre este registro y el siguiente (más antiguo). Insertar intermedio es exclusivo de superusuario. -->
                            <div v-if="index < historialEstados.length - 1
                                && hasRole('superUsuario')
                                && mesesEntreCards(registro, historialEstados[index + 1]).length"
                                class="relative flex items-start gap-4">
                                <div class="absolute left-[-6px] top-1/2 -translate-y-1/2">
                                    <span class="block w-3 h-3 rounded-full ring-2 ring-white bg-slate-300"></span>
                                </div>
                                <div class="w-full">
                                    <button
                                        @click="emit('insertarIntermedio', { base: historialEstados[index + 1], siguiente: registro })"
                                        title="Insertar estado en un mes disponible entre estos dos"
                                        class="w-full flex items-center justify-center py-2 rounded-xl border border-dashed border-slate-300 text-slate-400 hover:bg-slate-50 hover:border-slate-400 transition-colors">
                                        <Icon :icon-button="true" name="circlePlus" class-name="" :size="18" />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- fallback si no hay registros -->
                        <div v-if="!historialEstados.length" class="text-center text-slate-400 py-6 text-sm">
                            No hay historial para mostrar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="sm:px-6 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center sm:justify-end gap-3">
                    <Button @click="$emit('close')" :style="'py-2.5 px-8 rounded-xl'"
                        class="bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))] text-white text-sm font-medium transition-colors">
                        Cerrar
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
