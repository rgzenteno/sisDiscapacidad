<template>
    <div class="grid grid-cols-[1fr_auto] gap-6 px-4 pt-4 border-b border-gray-100 dark:border-gray-600/50 rounded-t-3xl bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 sticky top-0 z-10">
        <div class="min-w-0">
            <div class="grid grid-cols-[auto_1fr] gap-4 items-center mb-2">
                <!-- Avatar / Ícono -->
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                    <slot name="icon">
                        <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd" />
                        </svg>
                    </slot>
                </div>

                <!-- Título y subtítulo -->
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight truncate">
                        <slot name="label1">Título por defecto</slot>
                    </h2>
                    <p class="text-sm text-slate-500 dark:text-blue-300 truncate">
                        <slot name="label2">Subtítulo por defecto</slot>
                    </p>
                </div>
            </div>
        </div>

        <!-- Botón Cerrar -->
        <div class="flex items-start gap-3 flex-shrink-0">
            <button
                type="button"
                @click="emit('close')"
                class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
// ============ INICIO EMITS ============ //
const emit = defineEmits(['close']);
// ============ FIN EMITS ============ //
</script>
