<script setup>
// ============ INICIO IMPORTS ============ //
import InputLabel from '@/components/InputLabel.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number],
        default: ''
    },
    props: {
        type: Object,
        required: true
    },
    nombreFor: {
        type: String,
        default: ''
    }
});
// ============ FIN PROPS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <div class="flex" v-show="field.label !== ''">
            <InputLabel :for="field.name" :value="field.label"
                class="block text-sm font-medium text-gray-900 dark:text-white" />
        </div>

        <!-- Input de solo lectura para id_persona -->
        <input v-if="field.typeInput === 'id'" :value="nombreFor"
            class="w-full cursor-not-allowed capitalize font-bold bg-blue-100 border border-blue-400 text-blue-500 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500"
            readonly />

        <!-- Input de solo lectura para verificar (fecha_vencimiento, etc) -->
        <input v-else-if="field.typeInput === 'verificar'" type="text" :id="field.name" :value="modelValue"
            :placeholder="field.placeholder || 'Ingrese ' + field.placeholder"
            class="w-full cursor-not-allowed bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500"
            readonly disabled />
    </div>
</template>
