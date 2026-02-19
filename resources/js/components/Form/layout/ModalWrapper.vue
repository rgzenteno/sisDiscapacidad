<script setup>
// ============ INICIO PROPS ============ //
const props = defineProps({
    maxWidth: {
        type: String,
        default: 'max-w-md'
    }
});
// ============ FIN PROPS ============ //
</script>

<template>
    <div :class="[
        'relative w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl transform transition-all duration-300 my-8',
        maxWidth
    ]">
        <slot />
    </div>
</template>
