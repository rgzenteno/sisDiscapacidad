<script setup>
const props = defineProps({
    numero: {
        type: [Number, Array],
        default: 0,
    },
});
</script>

<template>
<div class="w-full flex flex-col items-center justify-center">
    <div :class="[
        'w-full', 
        'bg-transparent px-0 py-0',
        'rounded-lg'
    ]">
        <slot />
    </div>
</div>
</template>