<script setup>
// ============ INICIO PROPS ============ //
const props = defineProps({
    field: {
        type: Object,
        required: true
    }
});
// ============ FIN PROPS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Textos informativos -->
        <p
            v-for="(texto, index) in field.textos"
            :key="index"
            class="block text-sm font-medium text-gray-900 dark:text-white text-justify"
        >
            {{ texto }}
        </p>
    </div>
</template>
