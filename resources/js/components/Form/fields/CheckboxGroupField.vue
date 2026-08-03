<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { watch } from 'vue';

// ============================================================================
// PROPS
// ============================================================================
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

// ============================================================================
// WATCHERS
// ============================================================================
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
</script>

<template>
    <div class="w-full">

        <!-- Checkboxes Grid -->
        <div class="grid grid-cols-1 gap-2">
            <label v-for="option in field.options" :key="option.value"
                class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                <input type="checkbox" :value="option.value" v-model="form[option.value]" :true-value="1"
                    :false-value="0"
                    class="w-4 h-4 text-[rgb(var(--brand-600))] bg-gray-100 border-gray-300 rounded focus:ring-[rgb(var(--brand-500))] dark:focus:ring-[rgb(var(--brand-600))] dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                <span class="text-sm text-gray-900 dark:text-gray-100">
                    {{ option.text }}
                </span>
            </label>
        </div>
    </div>
</template>
