<template>

    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-md" @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="key" class-name="text-white" fill="none" stroke="currentColor"
                    stroke-width="2" :size="20" />
            </div>
        </template>
        <template #label1>Resetear Contraseña</template>
        <template #label2>Esta acción generará una contraseña temporal</template>

        <!-- Modal Body -->
        <div>
            <!-- Antes de resetear -->
            <div v-if="!temporaryPassword" class="space-y-3">

                <!-- Usuario objetivo -->
                <div
                    class="flex items-center gap-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-700/40 rounded-xl px-4 py-3">
                    <div
                        class="w-11 h-11 rounded-full bg-gradient-to-br from-violet-500 to-purple-400 flex items-center justify-center shadow-md flex-shrink-0">
                        <span class="text-white font-bold text-sm uppercase">
                            {{ getInitials(user?.nombre, user?.apellido) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Usuario del sistema</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                            {{ toTitleCase(user?.nombre) }} {{ toTitleCase(user?.apellido) }}
                        </p>
                        <p class="text-xs text-violet-500 uppercase dark:text-violet-300 truncate">{{ user?.cargo }}</p>
                    </div>
                </div>

                <!-- Advertencia -->
                <div
                    class="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl px-4 py-3">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                        Se generará una <strong>contraseña temporal</strong> que deberás proporcionar al usuario. Deberá
                        cambiarla en su primer inicio de sesión.
                    </p>
                </div>

            </div>

            <!-- Después de resetear -->
            <div v-else class="space-y-2.5">

                <!-- Usuario objetivo -->
                <div
                    class="flex items-center gap-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-700/40 rounded-xl px-4 py-3">
                    <div
                        class="w-11 h-11 rounded-full bg-gradient-to-br from-violet-500 to-purple-400 flex items-center justify-center shadow-md flex-shrink-0">
                        <span class="text-white font-bold text-sm uppercase">
                            {{ getInitials(user?.nombre, user?.apellido) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Contraseña generada para</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                            {{ toTitleCase(user?.nombre) }} {{ toTitleCase(user?.apellido) }}
                        </p>
                        <p class="text-xs text-violet-500 uppercase dark:text-violet-300 truncate">{{ user?.cargo }}</p>
                    </div>
                </div>

                <!-- Separador -->
                <div class="flex items-center gap-2 my-6">
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                    </div>
                    <div class="flex items-center justify-center">
                        <span class="text-sm text-slate-400">Contraseña Temporal</span>
                    </div>
                    <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
                    </div>
                </div>

                <!-- Contraseña -->
                <div
                    class="bg-gray-50 dark:bg-gray-700/40 rounded-xl px-4 py-3 border border-gray-200 dark:border-gray-600/40">
                    <p class="text-xs text-slate-500 mb-2">Copia esta contraseña ahora. No podrás verla nuevamente.</p>
                    <div
                        class="flex items-center gap-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-3">
                        <p
                            class="font-mono font-bold text-lg text-violet-700 dark:text-violet-400 flex-1 select-all break-all">
                            {{ temporaryPassword }}
                        </p>
                        <button @click="copyPassword" type="button"
                            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex-shrink-0"
                            :title="copied ? 'Copiado!' : 'Copiar contraseña'">
                            <svg v-if="!copied" class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Nota -->
                <div
                    class="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 rounded-xl px-4 py-3">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                        El usuario deberá cambiar esta contraseña en su <strong>primer inicio de sesión</strong> por
                        seguridad.
                    </p>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center gap-3">

                    <template v-if="!temporaryPassword">
                        <Button @click="handleClose" :style="'py-2.5 px-10 sm:px-12 rounded-xl border border-gray-200'"
                            class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                            Cancelar
                        </Button>

                        <Button @click="confirmReset" :disabled="processing"
                            :style="'whitespace-nowrap items-center py-2.5 px-5 sm:px-12 rounded-xl border relative w-36'"
                            :class="processing ? 'opacity-60 cursor-not-allowed bg-[rgb(var(--brand-400))]' : 'bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]'"
                            class="text-white">
                            <span v-if="processing" class="absolute inset-0 flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                </svg>
                                Procesando...
                            </span>
                            <span v-else class="flex items-center justify-center">Confirmar Reset</span>
                        </Button>
                    </template>

                    <!-- Después de resetear -->
                    <template v-else>
                        <Button @click="handleClose" :style="'py-2.5 px-10 sm:px-12 rounded-xl border border-gray-200'"
                            class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                            Entendido
                        </Button>
                    </template>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Componentes
 */
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Icon from '@/components/Icon.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';

// ============================================================================
// PROPS Y EMITS
// ============================================================================
const props = defineProps({
    user: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// ============================================================================
// REFS - ESTADO UI
// ============================================================================
const temporaryPassword = ref(null);
const processing = ref(false);
const copied = ref(false);

// ============================================================================
// WATCHERS
// ============================================================================

// Reinicia el estado cuando el modal se cierra
watch(() => props.show, (newValue) => {
    if (!newValue) resetState();
});

// Reinicia el estado cuando cambia el usuario
watch(() => props.user, () => {
    resetState();
});

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Genera las iniciales de un nombre y apellido
 * @param {string} nombre - Nombre de la persona
 * @param {string} apellido - Apellido de la persona
 * @returns {string} Iniciales en mayúsculas
 */
const getInitials = (nombre, apellido) => {
    const n = nombre?.charAt(0)?.toUpperCase() ?? '';
    const a = apellido?.charAt(0)?.toUpperCase() ?? '';
    return `${n}${a}` || '??';
};

/**
 * Convierte un texto a formato Title Case respetando el idioma español
 * @param {string} str - Texto a convertir
 * @returns {string} Texto en Title Case
 */
const toTitleCase = str =>
    str
        ? str
            .toLocaleLowerCase('es')
            .replace(/(^|\s)\S/g, l => l.toLocaleUpperCase('es'))
        : '';

// ============================================================================
// FUNCIONES - CONTRASEÑA
// ============================================================================

/**
 * Envía la solicitud de reseteo de contraseña para el usuario seleccionado
 */
const confirmReset = () => {
    if (!props.user?.id) return;

    processing.value = true;

    router.post(
        `/admin/users/${props.user.id}/reset-password`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['flash'],
            onSuccess: () => {
                processing.value = false;
                const flash = page.props.flash;
                if (flash?.temporary_password) {
                    temporaryPassword.value = flash.temporary_password;
                }
            },
            onError: (errors) => {
                processing.value = false;
                alert('Error al resetear la contraseña. Por favor intenta de nuevo.');
            },
            onFinish: () => {
                processing.value = false;
            }
        }
    );
};

/**
 * Copia la contraseña temporal al portapapeles usando la API nativa o el método alternativo
 */
const copyPassword = async () => {
    try {
        await navigator.clipboard.writeText(temporaryPassword.value);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        fallbackCopy();
    }
};

/**
 * Método alternativo para copiar al portapapeles usando execCommand
 */
const fallbackCopy = () => {
    const textArea = document.createElement('textarea');
    textArea.value = temporaryPassword.value;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        alert('No se pudo copiar la contraseña. Por favor cópiala manualmente.');
    }
    document.body.removeChild(textArea);
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Reinicia el estado interno del modal
 */
const resetState = () => {
    temporaryPassword.value = null;
    processing.value = false;
    copied.value = false;
};

/**
 * Emite el evento de cierre del modal
 */
const handleClose = () => {
    emit('close');
};
</script>
