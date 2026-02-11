<template>
    <div class="fixed inset-0 bg-slate-900/55 flex items-center justify-center z-40 px-4 py-6 overflow-y-auto backdrop-blur-sm">
        <div class="relative w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 transform transition-all duration-300"
             :class="temporaryPassword ? 'max-w-2xl' : 'max-w-xl'">
            <!-- Modal Header -->

            <div class="grid grid-cols-[1fr_auto] gap-6 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-br from-slate-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-t-3xl">
                <!-- Contenido principal -->
                <div class="min-w-0">
                    <!-- Fila 1: Avatar/ícono y título -->
                    <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                        <!-- Avatar -->
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg ring-2 ring-indigo-100 dark:ring-indigo-900 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>

                        <!-- Título y subtítulo -->
                        <div class="min-w-0">
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-gray-100 truncate">
                                Resetear Contraseña
                            </h2>
                            <p class="text-sm text-slate-600 dark:text-gray-400 truncate">
                                Esta acción generará una nueva contraseña temporal
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex items-start gap-3 flex-shrink-0">
                    <button type="button" @click="handleClose" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- Antes de resetear -->
                <div v-if="!temporaryPassword">
                    <div class="bg-slate-50 dark:bg-gray-900/50 rounded-xl p-5 mb-6 border border-slate-200 dark:border-gray-700">
                        <p class="text-slate-700 dark:text-gray-300 leading-relaxed">
                            ¿Estás seguro de resetear la contraseña de <strong class="text-slate-900 dark:text-white capitalize">{{ user?.nombre }} {{ user?.apellido }}</strong>?
                        </p>
                    </div>
                    <div class="flex items-start gap-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            Se generará una contraseña temporal que deberás proporcionar al usuario. El usuario deberá cambiarla en su primer inicio de sesión.
                        </p>
                    </div>
                </div>

                <!-- Después de resetear -->
                <div v-else class="bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-300 dark:border-amber-700 rounded-xl p-5">
                    <div class="flex items-start gap-3 mb-4">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-base text-amber-900 dark:text-amber-100 font-semibold mb-1">
                                ¡Contraseña generada exitosamente!
                            </p>
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                Copia esta contraseña ahora. No podrás verla nuevamente.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-amber-200 dark:border-amber-800 shadow-sm mb-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Usuario</p>
                        <p class="font-semibold text-lg text-gray-900 dark:text-white mb-4 capitalize">
                            {{ user?.nombre }} {{ user?.apellido }}
                        </p>

                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Contraseña temporal</p>
                        <div class="flex items-center gap-3 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-900 dark:to-gray-950 p-4 rounded-lg border-2 border-slate-300 dark:border-slate-700">
                            <p class="font-mono font-bold text-xl text-indigo-700 dark:text-indigo-400 flex-1 select-all break-all">
                                {{ temporaryPassword }}
                            </p>
                            <button
                                @click="copyPassword"
                                type="button"
                                class="p-2.5 hover:bg-slate-200 dark:hover:bg-gray-800 rounded-lg transition-colors flex-shrink-0"
                                :title="copied ? 'Copiado!' : 'Copiar contraseña'">
                                <svg v-if="!copied" class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg v-else class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-start gap-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-xs text-blue-800 dark:text-blue-200">
                            El usuario deberá cambiar esta contraseña en su primer inicio de sesión por seguridad.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 rounded-b-3xl bg-gradient-to-br from-slate-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
                <!-- Antes de resetear -->
                <template v-if="!temporaryPassword">
                    <button
                        @click="handleClose"
                        type="button"
                        class="px-6 py-2.5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 bg-white dark:bg-gray-700 hover:bg-slate-50 dark:hover:bg-gray-600 rounded-xl text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700">
                        Cancelar
                    </button>
                    <button
                        @click="confirmReset"
                        type="button"
                        :disabled="processing"
                        class="px-6 py-2.5 text-white bg-gradient-to-r from-blue-600 to-blue-600 hover:from-blue-700 hover:to-blue-700 rounded-xl text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-indigo-500/30">
                        <span v-if="processing">Procesando...</span>
                        <span v-else>Confirmar Reset</span>
                    </button>
                </template>

                <!-- Después de resetear -->
                <template v-else>
                    <button
                        @click="handleClose"
                        type="button"
                        class="px-6 py-2.5 text-white bg-gradient-to-r from-blue-600 to-blue-600 hover:from-blue-700 hover:to-blue-700 rounded-xl text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-800 shadow-lg shadow-indigo-500/30">
                        Entendido
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const page = usePage();
const temporaryPassword = ref(null);
const processing = ref(false);
const copied = ref(false);

// Reset state when modal closes or user changes
watch(() => props.show, (newValue) => {
    if (!newValue) {
        resetState();
    }
});

watch(() => props.user, () => {
    resetState();
});

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
                console.log('Flash recibido:', flash);

                if (flash && flash.temporary_password) {
                    temporaryPassword.value = flash.temporary_password;
                } else {
                    console.error('No se recibió temporary_password');
                }
            },
            onError: (errors) => {
                processing.value = false;
                console.error('Error al resetear contraseña:', errors);
                alert('Error al resetear la contraseña. Por favor intenta de nuevo.');
            },
            onFinish: () => {
                processing.value = false;
            }
        }
    );
};

const copyPassword = async () => {
    try {
        await navigator.clipboard.writeText(temporaryPassword.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Error al copiar:', err);
        fallbackCopy();
    }
};

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
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        alert('No se pudo copiar la contraseña. Por favor cópiala manualmente.');
    }
    document.body.removeChild(textArea);
};

const resetState = () => {
    temporaryPassword.value = null;
    processing.value = false;
    copied.value = false;
};

const handleClose = () => {
    emit('close');
};
</script>
