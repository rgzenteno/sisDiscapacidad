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
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['update:modelValue']);
// ============ FIN EMITS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <div class="flex" v-show="field.label !== ''">
            <InputLabel
                :for="field.name"
                :value="field.label"
                class="block text-sm font-medium text-gray-900 dark:text-white"
            />
            <span
                :style="{ visibility: field.required === true ? 'visible' : 'hidden' }"
                class="ms-1 text-red-600"
            >
                *
            </span>
        </div>

        <!-- Textarea -->
        <textarea
            :id="field.name"
            :value="modelValue"
            @input="emit('update:modelValue', $event.target.value)"
            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500"
            :aria-required="field.required"
            :placeholder="'Ingrese ' + field.placeholder"
            :aria-label="'Ingresa ' + field.label + ' (máximo ' + field.maxLength + ' caracteres)'"
            :maxlength="field.maxLength"
        />

        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
