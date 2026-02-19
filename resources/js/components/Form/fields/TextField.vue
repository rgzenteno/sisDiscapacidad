<script setup>
// ============ INICIO IMPORTS ============ //
import InputLabel from '@/components/InputLabel.vue';
import Input from '@/components/Input.vue';
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
    error: {
        type: String,
        default: ''
    },
    nombreFor: {
        type: String,
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

        <!-- Input -->
        <Input
            :input-type="field.type || field.typeInput"
            :model-value="modelValue"
            :customClass="field.nameStyle"
            :maxlength="field.range"
            :placeholder="'Ingrese ' + field.placeholder"
            :readonly="field.readonly"
            :errors="error"
            :autofocus="field.autofocus || false"
            @update:model-value="emit('update:modelValue', $event)"
        />
    </div>
</template>
