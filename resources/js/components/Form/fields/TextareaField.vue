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

        <!-- Textarea -->
        <textarea :id="field.name" :value="modelValue" @input="emit('update:modelValue', $event.target.value)"
            class="w-full text-sm rounded-xl px-4 py-2.5 border transition-colors duration-200 outline-none bg-gray-50 dark:bg-gray-700/40 text-slate-700 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 border-gray-400 dark:border-gray-600/40 hover:border-[rgb(var(--brand-300))] dark:hover:border-[rgb(var(--brand-600))] focus:border-[rgb(var(--brand-400))] focus:ring-0 focus:ring-gray-100"
            :aria-required="field.required" :placeholder="'Ingrese ' + field.placeholder"
            :aria-label="'Ingresa ' + field.label + ' (máximo ' + field.maxLength + ' caracteres)'"
            :maxlength="field.maxLength" />

        <!-- Error Message -->
        <!-- <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p> -->
    </div>
</template>
