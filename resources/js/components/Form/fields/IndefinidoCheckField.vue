<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [Boolean, Number, String], default: 0 },
});

const emit = defineEmits(['update:modelValue']);

const isChecked = ref(props.modelValue == 1);

watch(() => props.modelValue, (val) => {
    isChecked.value = val == 1;
});

const toggle = () => {
    isChecked.value = !isChecked.value;
    const val = isChecked.value ? 1 : 0;
    emit('update:modelValue', val);

    if (typeof props.field.onIndefinidoChange === 'function') {
        props.field.onIndefinidoChange(val);
    }
};
</script>

<template>
    <div @click="toggle"
        class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors select-none">
        <span class="text-sm font-medium transition-colors"
            :class="isChecked ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100'">
            Con fecha
        </span>

        <div class="relative flex-shrink-0 w-10 h-[22px] rounded-full transition-colors duration-200"
            :class="isChecked ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
            <div class="absolute top-[3px] left-[3px] w-4 h-4 bg-white rounded-full transition-transform duration-200"
                :class="isChecked ? 'translate-x-[18px]' : 'translate-x-0'" />
        </div>

        <span class="text-sm font-medium transition-colors"
            :class="isChecked ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500'">
            Indefinido
        </span>
    </div>
</template>
