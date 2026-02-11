<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
<button :type="type" class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 bg-white dark:bg-gray-700 hover:bg-slate-50 dark:hover:bg-gray-600 text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-slate-200 dark:focus:ring-slate-700">
    <slot />
</button>
</template>
