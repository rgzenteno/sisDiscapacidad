<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { can } from '@/lib/can';
import Modal from "@/components/Modal.vue";
import Button from "@/components/Button.vue";
import Icon from "@/components/Icon.vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
});

// Emits
const emit = defineEmits(['edit', 'close']);

const getCurrentDate = (data) => {
    if (!data) return 'Indefinido';
    const [year, month, day] = data.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());
    return dateStr;
};

const esVigente = ref(false);

watch(() => props.data.carnet?.fecha_vencimiento, (nuevaFecha) => {
    if (!props.data.carnet?.fecha_emision) {
        esVigente.value = true;
        return;
    }
    const fechaVenc = new Date(nuevaFecha);
    const fechaActual = new Date();
    esVigente.value = fechaVenc >= fechaActual;
}, { immediate: true });

// ─── Helpers ────────────────────────────────────────────────────────────────

const getInitials = (nombre, apellido) => {
    const n = nombre?.charAt(0)?.toUpperCase() ?? ''
    const a = apellido?.charAt(0)?.toUpperCase() ?? ''
    return `${n}${a}` || '??'
}

const toTitleCase = str =>
    str
        ? str
            .toLocaleLowerCase('es')
            .replace(/(^|\s)\S/g, l => l.toLocaleUpperCase('es'))
        : ''

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>
<template>
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-md" @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="profileCard" class-name="text-white" :size="20" />
            </div>
        </template>
        <template #label1>Carnet de Discapacidad</template>
        <template #label2>Información del carnet seleccionado</template>


        <!-- Body -->
        <div class="space-y-2.5">
            <!-- Avatar + nombre del beneficiario -->
            <div
                class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/40 rounded-xl px-4 py-3">
                <div
                    class="w-11 h-11 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-gray-400 flex items-center justify-center shadow-md flex-shrink-0">
                    <span class="text-white font-bold text-sm uppercase">
                        {{ getInitials(props.data.nombre_persona, props.data.apellido_persona) }}
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Beneficiario</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                        {{ toTitleCase(props.data.nombre_persona) }}
                        {{ toTitleCase(props.data.apellido_persona) }}
                    </p>
                    <p class="text-xs text-indigo-500 dark:text-indigo-300">
                        CI: {{ props.data.ci_persona }}
                    </p>
                </div>
            </div>

            <!-- Separador: Estado -->
            <div class="flex items-center gap-2 my-6">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
                <div class="flex items-center justify-center">
                    <span class="text-sm text-slate-400">Detalles del Carnet</span>
                </div>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
            </div>

            <!-- Número de carnet de discapacidad -->
            <div
                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-xs text-slate-500">
                        <span class="hidden sm:inline">N° Carnet de discapacidad</span>
                        <span class="sm:hidden">N° Carnet</span>
                    </p>
                </div>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200 tracking-wide">
                    # {{ props.data.carnet?.doc }}
                </span>
            </div>

            <!-- Badge de estado + tipo discapacidad -->
            <div class="grid grid-cols-2 gap-2.5">
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1">
                        <span class="hidden sm:inline">Estado del documento</span>
                        <span class="sm:hidden">Estado</span>
                    </p>
                    <div :class="[
                        'flex items-center gap-1.5 text-sm font-bold',
                        esVigente ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                    ]">
                        <span class="relative flex h-2 w-2 flex-shrink-0">
                            <span
                                :class="['animate-ping absolute inline-flex h-full w-full rounded-full opacity-75', esVigente ? 'bg-emerald-400' : 'bg-rose-400']"></span>
                            <span
                                :class="['relative inline-flex rounded-full h-2 w-2', esVigente ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                        </span>
                        {{ esVigente ? 'Vigente' : 'Vencido' }}
                    </div>
                </div>
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1">
                        <span class="hidden sm:inline">Tipo de discapacidad</span>
                        <span class="sm:hidden">Discapacidad</span>
                    </p>
                    <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase truncate">
                        {{ props.data.carnet?.discapacidad || 'No registrado' }}
                    </p>
                </div>
            </div>

            <!-- Fechas emisión / vencimiento -->
            <template v-if="props.data.carnet?.fecha_emision">
                <div class="grid grid-cols-2 gap-2.5">
                    <div
                        class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                        <p class="text-xs text-slate-500 mb-1">
                            <span class="hidden sm:inline">Fecha de emisión</span>
                            <span class="sm:hidden">Emisión</span>
                        </p>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ getCurrentDate(props.data.carnet?.fecha_emision) }}
                        </p>
                    </div>
                    <div
                        class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                        <p class="text-xs text-slate-500 mb-1">
                            <span class="hidden sm:inline">Fecha de vencimiento</span>
                            <span class="sm:hidden">Vencimiento</span>
                        </p>
                        <p
                            :class="['text-sm font-semibold', esVigente ? 'text-slate-700 dark:text-slate-200' : 'text-rose-500 dark:text-rose-400']">
                            {{ getCurrentDate(props.data.carnet?.fecha_vencimiento) }}
                        </p>
                    </div>
                </div>
            </template>

            <!-- Vigencia indefinida -->
            <div v-else
                class="flex items-center justify-center gap-2 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/40 rounded-xl px-4 py-3">
                <span class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">
                    Vigencia indefinida
                </span>
            </div>

            <!-- Nota informativa -->
            <div
                class="flex items-start gap-3 bg-[rgb(var(--brand-50))] dark:bg-[rgba(var(--brand-900),0.2)] border border-[rgb(var(--brand-200))] dark:border-[rgba(var(--brand-700),0.4)] rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-[rgb(var(--brand-500))] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-[rgb(var(--brand-700))] dark:text-[rgb(var(--brand-400))] leading-relaxed">
                    Este carnet es válido en todo el territorio
                    nacional y otorga los beneficios establecidos por ley.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center gap-3">
                    <Button @click="$emit('close')"
                        :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                        class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                        Aceptar
                    </Button>
                    <Button v-if="can('editar-carnet')" type="button"
                        @click="$emit('edit', props.data, props.data.carnet?.id_carnet, props.data.nombre_persona, props.data.apellido_persona, props.data.ci_persona)"
                        class="flex items-center gap-2 text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))] py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl">
                        <Icon :icon-button="true" name="modify" fill="none" stroke="currentColor" stroke-width="2"
                            class-name="text-white" :size="20" />
                        <span>Modificar</span>
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
