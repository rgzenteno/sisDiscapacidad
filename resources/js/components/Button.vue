<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    style: {
    type: String,
    default: 'px-4 py-2 sm:px-6 sm:py-2.5 rounded-xl border border-slate-300'
},
    color: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <button :type="type" :class="style"
        class="text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700">
        <slot />
    </button>
</template>
