<script setup>

// ============================================================================
// PROPS
// ============================================================================
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

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue']);

</script>

<template>
    <div class="w-full">
        <!-- Input Time -->
        <input
            :type="field.type"
            :id="field.name"
            :value="modelValue"
            @input="emit('update:modelValue', $event.target.value)"
            :placeholder="'Ingrese ' + field.placeholder"
            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500"
            :readonly="field.readonly"
        />

        <!-- Error Message -->
        <!-- <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p> -->
    </div>
</template>
