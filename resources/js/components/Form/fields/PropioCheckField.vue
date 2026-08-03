<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [Boolean, Number, String], default: 0 },
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue']);

// ============================================================================
// COMPUTED
// ============================================================================
const isChecked = computed(() => props.modelValue == 1);

const toggle = () => {
    const val = isChecked.value ? 0 : 1;
    emit('update:modelValue', val);
    if (typeof props.field.onPropioChange === 'function') {
        props.field.onPropioChange(val);
    }
};
</script>

<template>
    <div class="mb-1">
        <div class="flex items-center gap-0.5 mb-1">
            <label
                class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                Tutor
            </label>
        </div>
        <div @click="toggle"
            class="flex items-center gap-3 w-full bg-gray-50 border shadow-sm text-sm rounded-xl px-1 py-2.5 cursor-pointer select-none transition-colors"
            :class="isChecked
                ? 'border-emerald-400 hover:border-emerald-500 dark:bg-gray-600 dark:border-emerald-500'
                : 'border-gray-400 hover:border-[rgb(var(--brand-400))] dark:bg-gray-600 dark:border-gray-500'">

            <!-- Toggle switch -->
            <div class="relative flex-shrink-0 w-10 h-[20px] rounded-full transition-colors duration-200"
                :class="isChecked ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-500'">
                <div class="absolute top-[3px] left-[3px] w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                    :class="isChecked ? 'translate-x-[18px]' : 'translate-x-0'" />
            </div>

            <!-- Texto -->
            <span class="text-sm transition-colors"
                :class="isChecked ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-gray-400 dark:text-gray-400'">
                {{ isChecked ? 'El beneficiario es su propio tutor' : 'El beneficiario tiene un tutor diferente' }}
            </span>
        </div>
    </div>
</template>
