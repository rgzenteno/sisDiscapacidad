<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
/**
 * Componentes
 */
import Input from '@/components/Input.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number],
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    nombreFor: {
        type: String,
    }
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue']);

</script>

<template>
    <div class="w-full">
        <!-- Input -->
        <Input :input-type="field.type || field.typeInput" :message="field.message || ''"
            :message-type="field.messageType || 'warning'" :model-value="modelValue" :customClass="field.nameStyle"
            :maxlength="field.range" :placeholder="'Ingrese ' + field.placeholder" :readonly="field.readonly"
            :errors="error" :autofocus="field.autofocus || false"
            @update:model-value="emit('update:modelValue', $event)" />
        <!-- Mensaje de error -->
        <!-- <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ error }}
        </p> -->
    </div>
</template>
