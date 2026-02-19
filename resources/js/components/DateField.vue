<script setup>
import flatpickr from 'flatpickr';
import 'flatpickr/dist/l10n/es.js';
import { onMounted, ref } from 'vue';

const selectedDate = ref(null);
const datepicker = ref(null);

onMounted(() => {
    if (datepicker.value) {
        flatpickr(datepicker.value, {
            locale: 'es',
            theme: 'dark',
            onChange: (selectedDates, dateStr) => {
                selectedDate.value = dateStr;
            }
        });
    }
});

const props = defineProps({
    id: {
        type: String,
        required: true
    },
    label: {
        type: String,
        required: false
    },
    modelValue: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: "Seleccionar fecha"
    },
    type: {
        type: String,
        default: "date"
    },
    typeInput: {
        type: String,
        default: "date"
    }
});

const emit = defineEmits(['update:modelValue']);

// Función para manejar el cambio de valor
const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>

<template>
    <div class="mb-1">
        <div class="relative">
            <!-- <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                <svg class="w-3.5 h-3.5 text-gray-700 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                </svg>
            </div> -->
            <input v-if="typeInput === 'date'" ref="datepicker" :id="id" :type="type" :value="modelValue" @input="updateValue" class="cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 placeholder-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 block w-full ps-10 p-2 dark:bg-gray-700 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" :placeholder="placeholder" />
        </div>
    </div>
</template>