<script setup>
// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    field: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="w-full">
        <p
            v-for="(texto, index) in field.textos"
            :key="index"
            class="block text-sm font-medium text-gray-900 dark:text-white text-justify"
        >
            {{ texto }}
        </p>
    </div>
</template>
