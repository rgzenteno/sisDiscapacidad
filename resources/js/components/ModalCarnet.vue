<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { can } from '@/lib/can';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
});

//console.log(props.data);
// Emits
const emit = defineEmits(['edit', 'close']);

const getCurrentDate = (data) => {
    // Parsear la fecha manualmente para evitar problemas de zona horaria
    const [year, month, day] = data.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return dateStr;
};

const esVigente = ref(false);

watch(() => props.data.carnet?.fecha_vencimiento, (nuevaFecha) => {
    const fechaVenc = new Date(nuevaFecha);
    const fechaActual = new Date();

    esVigente.value = fechaVenc >= fechaActual;
}, { immediate: true }); // immediate: true ejecuta al montar el componente

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>
<template>
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4 z-40">
        <div
            class="bg-white rounded-3xl max-w-md w-full  shadow-2xl animate-[slideUp_0.3s_ease-out] transform transition-all duration-300">
            <!-- Header -->
            <div
                class="grid grid-cols-[1fr_auto] gap-6 px-6 py-3 border-b border-gray-100 bg-gray-50 dark:bg-gray-700/50 rounded-t-3xl">
                <!-- Contenido principal -->
                <div class="min-w-0">
                    <!-- Fila 1: Avatar/ícono y título -->
                    <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                        <!-- Avatar -->
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Título y subtítulo -->
                        <div class="min-w-0">
                            <h2 class="text-2xl font-semibold text-slate-800 dark:text-gray-100 truncate">
                                Carnet de Discapacidad
                            </h2>
                            <p class="text-sm text-slate-500 truncate">
                                Información del carnet seleccionado
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex items-start gap-3 flex-shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6">
                <!-- datos principales -->
                <div class="flex gap-4 mb-6">
                    <div class="flex flex-col items-center gap-2">
                        <div :class="[
                            'flex flex-col items-center justify-center px-2 py-1 rounded-3xl border shadow-sm',
                            esVigente ? 'bg-green-100 border-green-200' : 'bg-red-100 border-red-200'
                        ]">
                            <svg class="w-20 h-20 text-gray-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span :class="[
                                'text-sm font-semibold text-center',
                                esVigente ? 'text-green-700' : 'text-red-700'
                            ]">
                                {{ esVigente ? 'Vigente' : 'Vencido' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col justify-center gap-3">
                        <h2 class="capitalize text-xl font-bold text-slate-900 leading-tight">
                            {{ props.data.nombre_persona }} {{ props.data.apellido_persona }}</h2>
                        <div class="flex">
                            <div>
                                <span
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">C.I.</span>
                                <span class="text-sm font-semibold text-slate-800">{{ props.data.carnet?.doc }}</span>
                            </div>
                            <div class="ms-6">
                                <span
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">Tipo</span>
                                <span class="uppercase text-sm font-semibold text-slate-800">{{
                                    props.data.carnet?.discapacidad }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Divisor -->
                <div class="flex items-center gap-3 my-5">
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-slate-300 to-transparent"></div>
                    <div
                        class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center border border-slate-200">
                        <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M7 8h10M7 12h10M7 16h6" />
                        </svg>
                    </div>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-slate-300 to-transparent"></div>
                </div>

                <!-- Fechas -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div
                        class="bg-slate-50 border border-slate-200 rounded-xl p-3 hover:border-slate-300 hover:shadow-md transition-all">
                        <div class="flex gap-3">
                            <div
                                class="w-9 h-9 bg-blue-100 border border-blue-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <span
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">Emisión</span>
                                <span class="text-sm font-semibold text-slate-800 block mt-0.5">{{
                                    getCurrentDate(props.data.carnet?.fecha_emision) }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-slate-50 border border-slate-200 rounded-xl p-3 hover:border-slate-300 hover:shadow-md transition-all">
                        <div class="flex gap-3">
                            <div
                                class="w-9 h-9 bg-amber-100 border border-amber-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 6v6l4 2" />
                                </svg>
                            </div>
                            <div>
                                <span
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block">Vencimiento</span>
                                <span class="text-sm font-semibold text-slate-800 block mt-0.5">{{
                                    getCurrentDate(props.data.carnet?.fecha_vencimiento) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info adicional -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 flex gap-3">
                    <svg class="w-8 h-8 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <p class="text-xs text-blue-900 leading-relaxed">Este carnet es válido en todo el territorio
                        nacional y otorga los beneficios establecidos por ley.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-200 rounded-b-3xl flex justify-end gap-3 bg-slate-50">
                <button type="button" @click="$emit('close')"
                    class="px-5 py-2 bg-white border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-400 transition-all">
                    Aceptar
                </button>
                <button v-if="can('editar-carnet')" type="button"
                    @click="$emit('edit', props.data, props.data.carnet?.id_carnet, `${props.data.nombre_persona} ${props.data.apellido_persona}`)"
                    class="px-5 py-2 bg-blue-500 text-white rounded-lg text-sm font-semibold hover:bg-blue-600 hover:shadow-lg hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Modificar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
