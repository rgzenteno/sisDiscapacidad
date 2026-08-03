<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    style: {
    type: String,
    default: 'px-4 sm:px-6 sm:py-2.5 rounded-lg border border-slate-300'
},
    color: {
        type: String,
        default: ''
    }
});

// Los botones circulares (rounded-full — íconos de acción sueltos) dejan el
// anillo de foco activo después de hacer clic, incluso con un modal abierto
// encima, y se ve como un borde gris enorme comparado con el ícono. En los
// botones normales (rectangulares) el anillo sí sirve como indicador de
// foco, así que solo se saca para los circulares.
const sinAnilloFoco = computed(() => props.style.includes('rounded-full'));
</script>

<template>
    <button :type="type" :class="[style, sinAnilloFoco ? 'focus:outline-none focus:ring-0' : 'focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700']"
        class="text-sm font-semibold transition-all duration-200">
        <slot />
    </button>
</template>
