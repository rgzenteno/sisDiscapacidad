<script setup>
import { onMounted, onUnmounted } from 'vue';
import { can } from '@/lib/can.ts';

import Modal from "@/components/Modal.vue";
import Button from "@/components/Button.vue";
import Icon from "@/components/Icon.vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
});

//console.log(props.data);
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

const formatDateTime = (dateStr) => {
    if (!dateStr) return 'No disponible'
    const date = new Date(dateStr)
    return date.toLocaleDateString('es-BO', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const emit = defineEmits(['close', 'changeTutor']);

const openWhatsApp = (whatsapp) => {
    const phoneNumber = whatsapp;
    const message = encodeURIComponent('Hola, tengo una consulta');
    const url = `https://wa.me/${phoneNumber}?text=${message}`;
    window.open(url, '_blank');
};

const openEmail = (email) => {
    const recipient = email;
    const subject = encodeURIComponent('Consulta');
    const body = encodeURIComponent('Hola, tengo una consulta');
    const url = `mailto:${recipient}?subject=${subject}&body=${body}`;
    window.open(url, '_blank');
};
const openGoogleMaps = (direccion) => {
    const address = encodeURIComponent(direccion);
    const url = `https://www.google.com/maps/search/?api=1&query=${address}`;
    window.open(url, '_blank');
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
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-md" @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="users" class-name="text-white" :size="20" />
            </div>
        </template>
        <template #label1>Tutor del beneficiario</template>
        <template #label2>Datos de contacto y registro del tutor asignado</template>

        <!-- Modal Body -->
        <div class="space-y-2.5">
            <!-- Avatar + Info del BENEFICIARIO -->
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
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">
                        {{ toTitleCase(props.data.nombre_persona) }}
                        {{ toTitleCase(props.data.apellido_persona) }}
                    </p>
                    <p class="text-xs text-indigo-500 dark:text-indigo-300">
                        CI: {{ props.data.ci_persona }}
                    </p>
                </div>
            </div>

            <!-- Separador: Detalles del tutor -->
            <div class="flex items-center gap-2 my-6">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
                <div class="flex items-center justify-center">
                    <span class="text-sm text-slate-400">Detalles del tutor</span>
                </div>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                </div>
            </div>

            <!-- CASO: tutor propio -->
            <template v-if="props.data.es_propio">
                <div
                    class="flex flex-col items-center gap-3 bg-[rgb(var(--brand-50))] dark:bg-[rgba(var(--brand-900),0.2)] border border-[rgb(var(--brand-200))] dark:border-[rgba(var(--brand-700),0.4)] rounded-xl px-4 py-6 text-center">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center">
                        <Icon :icon-button="true" name="users" class-name="text-gray-600" :size="32" :height="32" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Tutor Propio</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs">
                            Este beneficiario actúa como su propio tutor. Los datos de contacto del tutor no están
                            disponibles.
                        </p>
                    </div>
                </div>
            </template>

            <!-- CASO: tutor normal -->
            <template v-else>
                <div
                    class="flex items-center gap-3 bg-[rgb(var(--brand-50))] dark:bg-[rgba(var(--brand-900),0.2)] border border-[rgb(var(--brand-200))] dark:border-[rgba(var(--brand-700),0.4)] rounded-xl px-4 py-3">
                    <div
                        class="w-11 h-11 rounded-full bg-gradient-to-br from-[rgb(var(--brand-500))] to-teal-400 flex items-center justify-center shadow-md flex-shrink-0">
                        <span class="text-white font-bold text-sm uppercase">
                            {{ getInitials(props.data.tutor?.nombre_tutor, props.data.tutor?.apellido_tutor) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Tutor responsable</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                            {{ toTitleCase(props.data.tutor?.nombre_tutor) }}
                            {{ toTitleCase(props.data.tutor?.apellido_tutor) }}
                        </p>
                        <p class="text-xs text-[rgb(var(--brand-500))] dark:text-[rgb(var(--brand-300))]">
                            CI: {{ props.data.tutor?.ci_tutor }}
                        </p>
                    </div>
                </div>

                <!-- Métricas: tutorados totales y activos -->
                <!-- <div class="grid grid-cols-2 gap-2.5">
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1">Tutorados</p>
                    <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                        {{ props.data.total_tutorados ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">en total</p>
                </div>
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-1">Activos</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ props.data.tutorados_activos ?? 0 }}
                    </p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">tutorados activos</p>
                </div>
            </div> -->

                <!-- Contacto: teléfono y email -->
                <div class="grid grid-cols-2 gap-2.5">

                    <!-- Teléfono -->
                    <div
                        class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                        <p class="text-xs text-slate-500 mb-1.5">Teléfono</p>
                        <div class="flex items-center justify-between gap-2">
                            <span v-if="props.data.tutor?.telefono"
                                class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">
                                {{ props.data.tutor.telefono }}
                            </span>
                            <span v-else class="text-xs text-red-400 italic">no disponible</span>
                            <button v-if="props.data.tutor?.telefono" @click="openWhatsApp(props.data.tutor.telefono)"
                                class="text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-md p-0.5 transition-colors flex-shrink-0"
                                title="Abrir WhatsApp">
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" fill="none">
                                    <path fill="currentColor" fill-rule="evenodd"
                                        d="M12 4a8 8 0 0 0-6.895 12.06l.569.718-.697 2.359 2.32-.648.379.243A8 8 0 1 0 12 4ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.96 9.96 0 0 1-5.016-1.347l-4.948 1.382 1.426-4.829-.006-.007-.033-.055A9.958 9.958 0 0 1 2 12Z"
                                        clip-rule="evenodd" />
                                    <path fill="currentColor"
                                        d="M16.735 13.492c-.038-.018-1.497-.736-1.756-.83a1.008 1.008 0 0 0-.34-.075c-.196 0-.362.098-.49.291-.146.217-.587.732-.723.886-.018.02-.042.045-.057.045-.013 0-.239-.093-.307-.123-1.564-.68-2.751-2.313-2.914-2.589-.023-.04-.024-.057-.024-.057.005-.021.058-.074.085-.101.08-.079.166-.182.249-.283l.117-.14c.121-.14.175-.25.237-.375l.033-.066a.68.68 0 0 0-.02-.64c-.034-.069-.65-1.555-.715-1.711-.158-.377-.366-.552-.655-.552-.027 0 0 0-.112.005-.137.005-.883.104-1.213.311-.35.22-.94.924-.94 2.16 0 1.112.705 2.162 1.008 2.561l.041.06c1.161 1.695 2.608 2.951 4.074 3.537 1.412.564 2.081.63 2.461.63.16 0 .288-.013.4-.024l.072-.007c.488-.043 1.56-.599 1.804-1.276.192-.534.243-1.117.115-1.329-.088-.144-.239-.216-.43-.308Z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Email -->
                    <div
                        class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                        <p class="text-xs text-slate-500 mb-1.5">Email</p>
                        <div class="flex items-center justify-between gap-2">
                            <span v-if="props.data.tutor?.email"
                                class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">
                                {{ props.data.tutor.email }}
                            </span>
                            <span v-else class="text-xs text-red-400 italic">no disponible</span>
                            <button v-if="props.data.tutor?.email" @click="openEmail(props.data.tutor.email)"
                                class="text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md p-0.5 transition-colors flex-shrink-0"
                                title="Enviar email">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 6.223-8-6.222V6h16zM4 18V9.044l7.386 5.745a.994.994 0 0 0 1.228 0L20 9.044 20.002 18H4z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dirección -->
                <div
                    class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500">Dirección</p>
                            <span v-if="props.data.tutor?.direccion"
                                class="text-xs font-semibold text-slate-700 dark:text-slate-200 capitalize truncate block">
                                {{ props.data.tutor.direccion }}
                            </span>
                            <span v-else class="text-xs text-red-400 italic">no disponible</span>
                        </div>
                    </div>
                    <button v-if="props.data.tutor?.direccion" @click="openGoogleMaps(props.data.tutor.direccion)"
                        class="text-[rgb(var(--brand-500))] hover:bg-[rgb(var(--brand-100))] dark:hover:bg-[rgba(var(--brand-900),0.3)] rounded-md p-0.5 transition-colors flex-shrink-0 ml-2"
                        title="Abrir en Google Maps">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                </div>

                <!-- Fecha de registro -->
                <div
                    class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <p class="text-xs text-slate-500">Fecha de registro</p>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ formatDateTime(props.data.tutor?.fecha_registro) }}
                        </p>
                    </div>
                </div>

                <!-- Cambio de tutor (opcional) -->
                <div v-if="props.data.tutor_anterior"
                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl px-4 py-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mb-1">Cambio de tutor</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
                                Este tutorado fue reasignado. El tutor anterior fue:
                            </p>
                            <div
                                class="flex items-center gap-2.5 bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-amber-100 dark:border-amber-800/40">
                                <div
                                    class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 flex-shrink-0 uppercase">
                                    {{ getInitials(props.data.tutor_anterior?.nombre_tutor,
                                        props.data.tutor_anterior?.apellido_tutor) }}
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-slate-700 dark:text-slate-200 capitalize truncate">
                                        {{ toTitleCase(props.data.tutor_anterior?.nombre_tutor) }}
                                        {{ toTitleCase(props.data.tutor_anterior?.apellido_tutor) }}
                                    </p>
                                    <p class="text-xs text-slate-400">Tutor anterior</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="sm:px-5 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center sm:justify-end gap-2">
                    <Button @click="$emit('close')"
                        :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                        class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                        Aceptar
                    </Button>
                    <Button v-if="can('tutorados-tutor')" @click="$emit('changeTutor')"
                        :style="'py-3 px-4 sm:px-6 sm:py-2.5 rounded-xl'"
                        class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                        <span>Cambiar de Tutor</span>
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
