<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { can } from '@/lib/can';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
});

// Emits
const emit = defineEmits(['add', 'close', 'cambioEstado', 'addEstado']);

// Computed para obtener el historial completo desde props.data
const historialEstados = computed(() => {
    if (props.data.historial_completo && Array.isArray(props.data.historial_completo)) {
        return props.data.historial_completo.map(registro => ({
            ...registro,
            // Mapear campos para compatibilidad con el template
            desde: registro.fecha_inicio,
            hasta: registro.fecha_fin || 'Actual',
            usuario: registro.usuario_modificacion || 'Sistema',
            observacion: registro.observaciones
        }));
    }
    return [];
});

// Computed para obtener el último registro (estado actual)
const estadoActual = computed(() => {
    if (historialEstados.value.length > 0) {
        const ultimo = historialEstados.value[0]; // El primero porque viene ordenado DESC
        return {
            estado: ultimo.estado,
            desde: ultimo.fecha_inicio,
        };
    }
    return {
        estado: props.data.estado,
        desde: props.data.fecha_inicio,
    };
});

// Computed para formatear la fecha de updated_at
const ultimoCambio = computed(() => {
    if (props.data.updated_at) {
        return new Date(props.data.updated_at).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    }
    return 'Sin fecha'
})

// Métodos
const cerrarModal = () => {
    emit('close')
}

const formatearFecha = (fecha) => {
    if (fecha === 'Actual' || !fecha) return 'Actual'
    return new Date(fecha).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const getIconoEstado = (estado) => {
    if (!estado) return '●'
    switch (estado.toLowerCase()) {
        case 'activo':
            return 'h-2.5 w-2.5 rounded-full ring-2 ring-green-300 bg-green-500'
        case 'baja_temporal':
            return 'h-2.5 w-2.5 rounded-full ring-2 ring-yellow-300 bg-yellow-500'
        case 'baja_definitiva':
            return 'h-2.5 w-2.5 rounded-full ring-2 ring-red-300 bg-red-500'
        default:
            return '●'
    }
}

const getNombreEstado = (estado) => {
    if (!estado) return '●'
    switch (estado.toLowerCase()) {
        case 'activo':
            return 'Activo'
        case 'baja_temporal':
            return 'Baja Temporal'
        case 'baja_definitiva':
            return 'Baja Definitiva'
        default:
            return '●'
    }
}

const getEstadoBadgeClass = (estado) => {
    const baseClass = 'py-1 rounded-full font-medium '
    if (!estado) return baseClass + 'bg-gray-100 text-gray-800'

    switch (estado.toLowerCase()) {
        case 'activo':
            return baseClass + 'bg-green-100 text-green-800'
        case 'baja_temporal':
            return baseClass + 'bg-yellow-100 text-yellow-800'
        case 'baja_definitiva':
            return baseClass + 'bg-red-100 text-red-800'
        default:
            return baseClass + 'bg-gray-100 text-gray-800'
    }
}

const cambiarEstadoRapido = (estado) => {
    form.estado = estado
    setTimeout(() => {
        const textarea = document.querySelector('textarea')
        if (textarea) textarea.focus()
    }, 100)
}

// Formulario con useForm de Inertia
const form = useForm({
    id: props.data.id_estado || null,
    id_persona: props.data.id_persona || null,
    estado: '',
    fechaEfectiva: new Date().toISOString().split('T')[0],
    observacion: ''
});

// Submit principal que usa el form de Inertia
const submit = () => {
    // Validaciones...
    if (!form.estado) {
        alert('Por favor, seleccione un estado');
        return;
    }
    if (form.estado === estadoActual.value.estado) {
        alert('El estado seleccionado es el mismo que el actual.');
        return;
    }

    form.post(route('persona.estado'), {
        onSuccess: () => {
            form.reset();
            form.fechaEfectiva = new Date().toISOString().split('T')[0];

            // Emitir evento al padre para que maneje la actualización
            emit('cambioEstado', {
                success: true,
                mensaje: 'El estado se modifico correctamente.'
            });
        },
        onError: (errors) => {
            console.error('Error al guardar:', errors);
            emit('cambioEstado', {
                success: false,
                errors: errors
            });
        }
    });
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        cerrarModal();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted (() => document.removeEventListener('keydown', closeOnEscape));
</script>

<style scoped>
/* Scroll personalizado para el historial y aside */
.scroll-custom::-webkit-scrollbar {
    width: 6px;
}

.scroll-custom::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.scroll-custom::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.scroll-custom::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<template>
<!-- Overlay del modal -->
<div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-40 p-4" @click="cerrarModal">
    <!-- Modal container -->
    <div class="relative w-full max-w-2xl max-h-[95vh] bg-gradient-to-br from-white to-slate-50 rounded-3xl shadow-[0_10px_40px_rgba(15,23,42,0.06)] border border-gray-100 flex flex-col overflow-hidden" @click.stop>
        <!-- Header -->
        <div class="grid grid-cols-[1fr_auto] gap-6 px-6 py-4 border-b border-gray-100">
            <!-- Contenido principal -->
            <div class="min-w-0">
                <!-- Fila 1: Avatar, título y estado -->
                <div class="grid grid-cols-[auto_1fr_auto] gap-4 items-start mb-3">
                    <!-- Avatar -->
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <!-- Título y subtítulo -->
                    <div class="min-w-0">
                        <h2 class="text-2xl font-semibold text-slate-800 tracking-tight truncate">Gestión de Estados</h2>
                        <p class="text-sm text-slate-500 capitalize truncate">
                            {{ props.data.nombre_persona }} {{ props.data.apellido_persona }} •
                            {{ props.data.ci_persona }}<span v-if="props.data.complemento">-{{ props.data.complemento }}</span>
                        </p>
                    </div>

                    <!-- Estado actual -->
                    <div class="flex-shrink-0">
                        <span :class="getEstadoBadgeClass(estadoActual.estado) + ' inline-flex items-center gap-2 px-2 py-1 rounded-full text-sm font-medium shadow-sm whitespace-nowrap'">
                            <svg v-if="estadoActual.estado === 'activo'" class="w-4 h-4 text-green-500 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd" />
                            </svg>
                            <svg v-if="estadoActual.estado === 'baja_temporal'" class="w-4 h-4 text-orange-500 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd" />
                            </svg>
                            <svg v-if="estadoActual.estado === 'baja_definitiva'" class="w-4 h-4 text-red-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z" clip-rule="evenodd" />
                            </svg>
                            <span class="capitalize">{{ getNombreEstado(estadoActual.estado) || 'Sin estado' }}</span>
                        </span>
                    </div>
                </div>

                <!-- Fila 2: Información adicional -->
                <div class="flex flex-wrap gap-2 text-xs ms-2 text-slate-500">
                    <!-- Último cambio -->
                    <div class="inline-flex items-center gap-1 bg-white/60 px-2 py-1.5 rounded-full border border-gray-100">
                        <svg class="w-3 h-3 text-slate-400 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z" />
                        </svg>
                        <span>Último cambio: <strong class="text-slate-700">{{ ultimoCambio }}</strong></span>
                    </div>

                    <!-- Distrito -->
                    <div class="inline-flex items-center gap-2 bg-white/60 px-2 py-1.5 rounded-full border border-gray-100 shadow-sm">
                        <svg class="w-3 h-3 text-slate-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Distrito: <strong class="text-slate-700">{{ props.data.distrito || 'Sin distrito' }}</strong></span>
                    </div>

                    <!-- Estado del carnet -->
                    <div v-if="props.data.id_carnet" class="inline-flex items-center gap-2 bg-white/60 px-2 py-1.5 rounded-full border border-gray-100 shadow-sm">
                        <svg class="w-3 h-3 flex-shrink-0" :class="props.data.carnet_vigente ? 'text-green-500' : 'text-yellow-700'" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9c0 .5-.04 1-.1 1.5" />
                        </svg>
                        <span>Carnet: <strong :class="props.data.carnet_vigente ? 'text-green-700' : 'text-yellow-700'">{{ props.data.carnet_vigente ? 'Vigente' : 'Vencido' }}</strong></span>
                    </div>
                    <div v-else class="inline-flex items-center gap-2 bg-white/60 px-2 py-1.5 rounded-full border border-gray-100 shadow-sm">
                        <svg class="w-3 h-3 text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <span>Carnet: <strong class="text-red-500">Sin carnet</strong></span>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-start gap-3 flex-shrink-0 ms-3">
                <button type="button" @click="cerrarModal" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body con scroll individual -->
        <div class="flex-1 p-6 py-4 min-h-0">
            <div class=" h-full">

                <!-- Left: Timeline / historial -->
                <div class="xl:col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">Historial de Estados</h3>
                        <p class="text-sm text-slate-500">{{ historialEstados.length }} registros</p>
                    </div>

                    <div class="relative pl-6 flex flex-col flex-1 mx-auto w-10/12">
                        <!-- línea vertical del timeline -->
                        <div class="absolute left-3 top-2 bottom-2 w-px bg-gradient-to-b from-cyan-300 to-indigo-200"></div>

                        <!-- Historial con scroll -->
                        <div class="max-h-[50vh] space-y-1 overflow-y-auto pr-2 text-sm">
                            <template v-for="(registro, index) in historialEstados" :key="registro.id || index">
                                <div class="relative flex items-start gap-4">
                                    <!-- punto del timeline -->
                                    <div class="absolute left-[-6px] top-2">
                                        <span class="block w-3 h-3 rounded-full ring-2 ring-white shadow-sm" :class="{
                                            'bg-emerald-500': registro.estado === 'activo',
                                            'bg-yellow-400': registro.estado === 'baja_temporal',
                                            'bg-red-500': registro.estado === 'baja_definitiva',
                                            'bg-slate-300': !['activo', 'baja_temporal', 'bajatemporal', 'baja_definitiva', 'bajadefinitiva'].includes(registro.estado)
                                        }"></span>
                                    </div>

                                    <div class="w-full">
                                        <div class="bg-white border border-gray-100 rounded-xl p-3 shadow-sm hover:shadow-md transition">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-semibold text-slate-800 capitalize">{{ getNombreEstado(registro.estado) }}</span>
                                                        <span class="text-xs text-slate-400">
                                                            {{ formatearFecha(registro.desde) }}
                                                            <span v-if="registro.hasta !== 'Actual' && registro.hasta">→ {{ formatearFecha(registro.hasta) }}</span>
                                                            <span v-else>→ En curso</span>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- badge con color -->
                                                <div class="text-right">
                                                    <span :class="getEstadoBadgeClass(registro.estado) + ' inline-block text-xs font-semibold px-3 py-1 rounded-full'">
                                                        {{ getNombreEstado(registro.estado) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-xs text-slate-600">
                                                <p v-if="registro.observacion" class="line-clamp-2" :title="registro.observacion">
                                                    {{ registro.observacion }}
                                                </p>
                                                <p v-else class="text-slate-400">Sin observación</p>
                                            </div>
                                            <div class="flex items-center justify-between mt-2">
                                                <!-- Fecha de registro -->
                                                <div class="mt-2 pt-0 border-t border-gray-50 text-xs text-slate-400">
                                                    Registrado: {{ formatearFecha(registro.fecha_registro) }}
                                                </div>
                                                <div class="ml-3 text-xs text-slate-500">
                                                    por <strong class="text-slate-700 capitalize">{{ (registro.usuario || 'Sistema').split(' ').slice(0, 2).join(' ') }}</strong>
                                                </div>
                                            </div>

                                        </div>
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

                <!-- Right: Formulario y acciones con scroll individual -->
                <!-- <aside v-if="opeModal" class="xl:col-span-1 flex flex-col space-y-1 min-h-0">
                    <div class="flex-1 overflow-y-auto scroll-custom space-y-4">

                        <div class="grid grid-cols-3 gap-3">
                            <div class="flex flex-col items-center justify-center p-3 py-1 bg-white border border-gray-100 rounded-lg shadow-sm">
                                <div class="text-xs text-slate-400">Cambios</div>
                                <div class="text-lg font-semibold text-slate-800">{{ historialEstados.length }}</div>
                            </div>
                            <div class="flex flex-col items-center justify-center p-3 py-1 bg-white border border-gray-100 rounded-lg shadow-sm">
                                <div class="text-xs text-slate-400">Estado</div>
                                <div class="text-sm font-semibold text-slate-800 capitalize truncate">{{ getNombreEstado(estadoActual.estado) || 'Sin estado' }}</div>
                            </div>
                            <div class="flex flex-col items-center justify-center p-3 py-1 bg-white border border-gray-100 rounded-lg shadow-sm">
                                <div class="text-xs text-slate-400">Desde</div>
                                <div class="text-sm font-semibold text-slate-800">{{ formatearFecha(estadoActual.desde) }}</div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-800 mb-3">Cambiar Estado</h4>

                            <form @submit.prevent="submit" class="space-y-3">

                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                                    <select v-model="form.estado" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-200">
                                        <option value="">Seleccionar...</option>
                                        <option value="activo">Activo</option>
                                        <option value="baja_temporal">Baja temporal</option>
                                        <option value="baja_definitiva">Baja definitiva</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Fecha Inicio</label>
                                    <input type="date" v-model="form.fechaEfectiva" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-200" required />
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Observación</label>
                                    <textarea v-model="form.observacion" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-200 resize-none" placeholder="Motivo del cambio..."></textarea>
                                </div>

                                <button type="submit" :disabled="!form.estado || form.processing" :class="{ 'opacity-50 cursor-not-allowed': !form.estado || form.processing }" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-500 text-white font-medium shadow-md hover:shadow-lg transition">
                                    <svg v-if="form.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                        <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ form.processing ? 'Guardando...' : 'Guardar cambio' }}
                                </button>
                            </form>
                        </div>

                        <div class="space-y-2 flex">
                            <button @click="cambiarEstadoRapido('baja_temporal')" class="w-full px-1 py-2 text-sm rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-800 hover:bg-yellow-100 transition">
                                🟡 Baja temporal
                            </button>
                            <button @click="cambiarEstadoRapido('baja_definitiva')" class="w-full px-1 py-2 text-sm rounded-lg border border-red-200 bg-red-50 text-red-800 hover:bg-red-100 transition">
                                🔴 Baja definitiva
                            </button>
                            <button @click="cambiarEstadoRapido('activo')" class="w-full px-1 py-2 text-sm rounded-lg border border-green-200 bg-green-50 text-green-800 hover:bg-green-100 transition">
                                🟢 Reactivar
                            </button>
                        </div>
                    </div>
                </aside> -->
            </div>
        </div>
        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-white">
            <button v-if="can('estado-beneficiario')" @click="emit('addEstado', props.data)" class="px-5 py-2 rounded-lg text-sm text-white bg-blue-700 border border-gray-100 hover:bg-blue-600 transition">
                Cambiar Estado
            </button>
            <button @click="cerrarModal" class="px-5 py-2 rounded-lg text-sm text-slate-600 bg-white border border-gray-400 hover:bg-gray-50 transition">
                Cerrar
            </button>
        </div>
    </div>
</div>
</template>
