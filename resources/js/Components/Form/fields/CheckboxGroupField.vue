<script setup>
// ============ INICIO IMPORTS ============ //
import { watch } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    form: {
        type: Object,
        required: true
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO WATCHERS ============ //
// Inicializar valores de checkboxes si no existen
watch(() => props.form, (newForm) => {
    if (props.field.options) {
        props.field.options.forEach(option => {
            if (newForm[option.value] === undefined) {
                // Para checkbox_pago, efectivo es 1 por defecto
                if (props.field.typeInput === 'checkbox_pago' && option.value === 'efectivo') {
                    newForm[option.value] = 1;
                } else {
                    newForm[option.value] = 0;
                }
            }
        });
    }
}, { immediate: true, deep: true });
// ============ FIN WATCHERS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <div class="flex mb-2" v-show="field.label !== ''">
            <InputLabel :for="field.name" :value="field.label"
                class="block text-sm font-medium text-gray-900 dark:text-white" />
            <span :style="{ visibility: field.required === true ? 'visible' : 'hidden' }" class="ms-1 text-red-600">
                *
            </span>
        </div>

        <!-- Checkboxes Grid -->
        <div class="grid grid-cols-1 gap-2">
            <label v-for="option in field.options" :key="option.value"
                class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                <input type="checkbox" :value="option.value" v-model="form[option.value]" :true-value="1"
                    :false-value="0"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                <span class="text-sm text-gray-900 dark:text-gray-100">
                    {{ option.text }}
                </span>
            </label>
        </div>
    </div>
</template>
