<script setup>
import { onMounted, onUnmounted } from 'vue';
import Modal from "@/components/Modal.vue";
import Button from "@/components/Button.vue";
import Icon from "@/components/Icon.vue";

const props = defineProps({
    user: Number,
    data: {
        type: Object,
        default: () => []
    },
});

const emit = defineEmits(['close']);


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

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';

    const [datePart, timePart] = dateTimeString.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour, minute] = timePart.split(':');

    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);

    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return `${dateStr} - ${hour}:${minute}`;
};

const getStatusInfo = (estado) => {
    if (estado === 1) {
        return {
            text: 'Conectado',
            class: 'bg-green-50 text-green-700 border-green-300 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
            dotClass: 'bg-green-500 dark:bg-green-400'
        };
    } else {
        return {
            text: 'Desconectado',
            class: 'bg-red-50 text-red-700 border-red-300 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
            dotClass: 'bg-red-500 dark:bg-red-400'
        };
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

</script>

<template>
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-xl" @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="addresBook" class-name="w-5 h-5 text-white" :size="20" />
            </div>
        </template>
        <template #label1>Detalles del Usuario</template>
        <template #label2>Información completa del perfil</template>

        <!-- Modal Body -->
        <div class="space-y-1.5">

            <!-- Avatar + Info del usuario -->
            <div
                class="flex items-center gap-3 bg-[rgb(var(--brand-50))] dark:bg-[rgba(var(--brand-900),0.2)] border border-[rgb(var(--brand-200))] dark:border-[rgba(var(--brand-700),0.4)] rounded-xl px-4 py-3">
                <div
                    class="w-11 h-11 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-indigo-400 flex items-center justify-center shadow-md flex-shrink-0">
                    <span class="text-white font-bold text-sm uppercase">
                        {{ getInitials(props.data.nombre, props.data.apellido) }}
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Usuario del sistema</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                        {{ toTitleCase(props.data.nombre) }} {{ toTitleCase(props.data.apellido) }}
                    </p>
                    <p class="text-xs text-[rgb(var(--brand-500))] dark:text-[rgb(var(--brand-300))] truncate">{{ props.data.email }}</p>
                </div>
            </div>

            <!-- Separador: Información profesional -->
            <div class="flex items-center gap-2 my-6">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
                <div class="flex items-center justify-center">
                    <span class="text-sm text-slate-400">Información Profesional</span>
                </div>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
            </div>

            <!-- Rol y Cargo -->
            <div class="grid grid-cols-2 gap-2.5">
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1.5">Rol</p>
                    <span
                        class="inline-flex items-center uppercase px-2.5 py-1 rounded-full text-xs font-semibold bg-[rgb(var(--brand-100))] text-[rgb(var(--brand-800))] dark:bg-[rgba(var(--brand-900),0.3)] dark:text-[rgb(var(--brand-300))] border border-[rgb(var(--brand-300))] dark:border-[rgb(var(--brand-700))]">
                        <span v-if="props.data.rol === 'superUsuario'">Super Usuario</span>
                        <span v-else>{{ props.data.rol }}</span>
                    </span>
                </div>
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1.5">Cargo</p>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 capitalize truncate"
                        :title="props.data.cargo?.length > 20 ? props.data.cargo : null">
                        {{ props.data.cargo }}
                    </p>
                </div>
            </div>

            <!-- Estado y Firma digital -->
            <div class="grid grid-cols-2 gap-2.5">
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1.5">Estado</p>
                    <div :class="['inline-flex items-center gap-1.5 text-sm font-bold',
                        getStatusInfo(props.data.estado ?? 1).textClass]">
                        <p class="text-gray-500 dark:text-gray-400 truncate">
                            <span v-if="props.data.habilitado === true" class="text-green-600 dark:text-green-400">
                                Cuenta habilitada
                            </span>
                            <span v-else class="text-red-600 dark:text-red-400">
                                Cuenta deshabilitada
                            </span>
                        </p>
                    </div>
                </div>
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1.5">Firma digital</p>
                    <div v-if="props.data.digital_signature"
                        class="flex items-center gap-1.5 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        Registrada
                    </div>
                    <div v-else
                        class="flex items-center gap-1.5 text-sm font-semibold text-rose-500 dark:text-rose-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        No registrada
                    </div>
                </div>
            </div>

            <!-- Separador: Cuenta -->
            <div class="flex items-center gap-2 my-6">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
                <div class="flex items-center justify-center">
                    <span class="text-sm text-slate-400">Información de la Cuenta</span>
                </div>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
            </div>

            <!-- Email verificado -->
            <div
                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">
                            <span class="hidden sm:inline">Usuario</span>
                            <span class="sm:hidden">Verificado</span>
                        </p>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">
                            {{ props.data.email }}
                        </p>
                    </div>
                </div>
                <span v-if="props.data.email_verified_at"
                    class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex-shrink-0 ml-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">Verificado</span>
                </span>
                <span v-else
                    class="flex items-center gap-1 text-xs font-semibold text-rose-500 dark:text-rose-400 flex-shrink-0 ml-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">Sin verificar</span>
                </span>
            </div>

            <!-- Fechas creado / actualizado -->
            <div class="grid grid-cols-2 gap-2.5">
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1">
                        <span class="hidden sm:inline">Fecha de creación</span>
                        <span class="sm:hidden">Creado</span>
                    </p>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ formatDateTime(props.data.created_at) }}
                    </p>
                </div>
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1">
                        <span class="hidden sm:inline">Última actualización</span>
                        <span class="sm:hidden">Actualizado</span>
                    </p>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ formatDateTime(props.data.updated_at) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center gap-3">
                    <Button @click="$emit('close')" :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border'"
                        class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                        Aceptar
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
