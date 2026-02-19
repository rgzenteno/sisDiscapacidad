<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    style: {
        type: String,
        default: 'px-6 py-2.5 rounded-xl'
    },
    color: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <button :type="type" :class="style"
        class="border border-slate-300 text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700">
        <slot />
    </button>
</template>
