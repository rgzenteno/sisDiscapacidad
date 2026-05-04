<script setup>
import { ref, onMounted, computed } from "vue";

const props = defineProps({
    id: { type: [Number, String], required: true },
    tipo: String,
    contenido: { type: Array, default: () => [] },
});

const emit = defineEmits(["close"]);
const isVisible = ref(false);

onMounted(() => {
    // Mostrar con delay de entrada
    setTimeout(() => (isVisible.value = true), 200);

    // Auto cerrar en 5s
    setTimeout(() => cerrarMensaje(), 7000);
});

const tipoConfig = computed(() => ({
    correcto:    { bg: 'bg-green-50',  border: 'border-green-200',  iconBg: 'bg-green-100',  iconColor: 'text-green-600',  textColor: 'text-green-800',  path: 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' },
    info:        { bg: 'bg-blue-50',   border: 'border-blue-200',   iconBg: 'bg-blue-100',   iconColor: 'text-blue-700',   textColor: 'text-blue-900',   path: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' },
    advertencia: { bg: 'bg-amber-50',  border: 'border-amber-200',  iconBg: 'bg-amber-100',  iconColor: 'text-amber-600',  textColor: 'text-amber-800',  path: 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' },
    error:       { bg: 'bg-red-50',    border: 'border-red-200',    iconBg: 'bg-red-100',    iconColor: 'text-red-600',    textColor: 'text-red-800',    path: 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' },
}[props.tipo]))

const cerrarMensaje = () => {
    isVisible.value = false;
    setTimeout(() => {
        emit("close", props.id);
    }, 1000);
};
</script>

<template>
<div class="fixed top-2 right-0 left-0 sm:left-auto sm:right-4 flex flex-col gap-2 z-50 px-2 sm:px-0 sm:w-72">
<Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="-translate-y-4 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="-translate-y-4 opacity-0"
>
    <div v-show="isVisible" class="w-full">
        <div
    v-if="tipoConfig"
    class="w-full rounded-lg border p-3 shadow-sm"
    :class="[tipoConfig.bg, tipoConfig.border]"
>
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 flex-1 min-w-0">
            <div class="flex-shrink-0 w-6 h-6 sm:w-5 sm:h-5 rounded-full flex items-center justify-center" :class="tipoConfig.iconBg">
                <svg class="w-5 h-5 sm:w-3 sm:h-3" :class="tipoConfig.iconColor" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" :d="tipoConfig.path" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-xs leading-tight" :class="tipoConfig.textColor">
                    {{ contenido[0]?.header }}
                </p>
                <p class="text-black text-[11px] leading-relaxed mt-1" v-if="contenido[0]?.text">
                    {{ contenido[0]?.text }}
                </p>
            </div>
        </div>
        <button @click="cerrarMensaje" class="flex-shrink-0 ml-2 text-red-400 hover:text-red-600 p-1.5 sm:p-1 rounded transition-colors self-center">
            <svg class="w-5 h-5 sm:w-3 sm:h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</div>
    </div>
</Transition>
</div>
</template>
