<script setup>
    import PrimaryButton from './PrimaryButton.vue';
    import SecondaryButton from './SecondaryButton.vue';
    const props = defineProps({
        id: Number,
        tipo: String
    });

    const emit = defineEmits(['close', 'editar', 'confirmar']); // Emite un evento para cerrar el modal
</script>

<template>
    <!-- Modal Background -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-50 px-4">
        <!-- Modal Content -->
        <div
            class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md transform transition-all duration-300">
            <!-- Close Button -->
            <button type="button" @click="$emit('close')"
                class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>

            <div class="p-8 text-center">
                <!-- Icono de Advertencia -->
                <div
                    class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40 shadow-inner mb-4">
                    <svg class="w-12 h-12 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd"/>
                    </svg>
                </div>

                <!-- Título -->
                <h2 class="text-xl font-semibold text-red-600 dark:text-red-400 mb-3">
                    Confirmar Pago
                </h2>

                <!-- Texto -->
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-2">
                    ¿Confirma que el beneficiario recibió su pago?
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    <strong>Esta acción no se puede deshacer.</strong> El registro quedará permanente en el sistema.
                </p>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 flex gap-3 justify-center">
                <button type="button" @click="$emit('close')"
                    class="px-6 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg shadow hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition">
                    Cancelar
                </button>
                <button type="submit" @click="$emit('confirmar', props.id, props.tipo)"
                    class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 transition">
                    Confirmar Pago
                </button>
            </div>
        </div>
    </div>
</template>
