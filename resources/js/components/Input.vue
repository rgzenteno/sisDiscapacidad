<script setup>
import flatpickr from 'flatpickr';
import 'flatpickr/dist/l10n/es.js';
import { onMounted, ref, watch, nextTick } from 'vue';

const selectedDate = ref(null);
const datepicker = ref(null);
const hasError = ref(false);

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: ''
    },
    placeholder: {
        type: String,
        default: "Seleccionar fecha"
    },
    inputType: {
        type: String,
        default: 'text',
        validator: (value) => ['text', 'number', 'email', 'date', 'password', 'complemento', 'distrito', 'discapacidad', 'usuario', 'letras-punto'].includes(value)
    },
    errors: {
        type: [String, Object, Boolean],
        default: false
    },
    focusColor: {
        type: String,
        default: 'blue'
    },
    customClass: {
        type: [String, Boolean],
        default: 'capitalize'
    },
    readonly: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    autofocus: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

let flatpickrInstance = null;

onMounted(() => {
    if (props.autofocus && datepicker.value && props.inputType !== 'date') {
        nextTick(() => {
            datepicker.value?.focus();
        });
    }

    if (datepicker.value && props.inputType === 'date') {
        flatpickrInstance = flatpickr(datepicker.value, {
            locale: 'es',
            dateFormat: "Y-m-d",          // Formato interno (para el modelValue)
            altInput: true,                // Habilita el input alternativo
            altFormat: "d/m/Y",           // Formato que ve el usuario (DD/MM/YYYY)
            defaultDate: props.modelValue || null,
            static: false,
            appendTo: document.body,
            onChange: (selectedDates, dateStr) => {
                selectedDate.value = dateStr;
                emit('update:modelValue', dateStr);
            }
        });
    }
});

watch(() => props.modelValue, (newValue) => {
    if (flatpickrInstance && newValue) {
        flatpickrInstance.setDate(newValue, true);
    }
});

const handleInput = (e) => {
    let newValue = e.target.value;

    if (props.readonly || props.disabled) {
        return;
    }

    if (props.inputType === 'text') {
        newValue = newValue.replace(/[^a-zA-ZáéíóúñÁÉÍÓÚÑ\s]/g, '');
    } else if (props.inputType === 'number') {
        newValue = newValue.replace(/[^0-9]/g, '');
    } else if (props.inputType === 'email') {
        newValue = newValue.replace(/[^a-zA-Z0-9@._-]/g, '');
    } else if (props.inputType === 'usuario') {
        newValue = newValue.replace(/[^a-zA-Z0-9._-]/g, '');
    } else if (props.inputType === 'complemento') {
        newValue = newValue.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 2);
    } else if (props.inputType === 'distrito' || props.inputType === 'discapacidad') {
        newValue = newValue.replace(/[^a-zA-Z0-9\-\s]/g, '');
    } else if (props.inputType === 'letras-punto') {
        newValue = newValue.replace(/[^a-zA-ZáéíóúñÁÉÍÓÚÑ0-9\s\.\-]/g, '');
    }
    else if (props.inputType === 'date') {
        return;
    }

    emit('update:modelValue', newValue);
    e.target.value = newValue;
};
</script>

<template>
    <div class="mb-1">
        <div class="relative">
            <input ref="datepicker" :type="inputType === 'password' ? 'password' : 'text'" :value="modelValue"
                @input="handleInput" :readonly="readonly" :disabled="disabled" :class="[
                    'placeholder-no-uppercase',
                    errors ? 'border-red-500 text-red-500 placeholder-red-600' : 'border-gray-300 hover:border-blue-400',
                    `focus:border-${focusColor}-500 focus:ring-${focusColor}-500`,
                    'w-full bg-gray-50 border shadow-sm text-gray-900 text-sm rounded-lg p-2',
                    'dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white',
                    typeof customClass === 'string' ? customClass : (customClass === true ? 'capitalize' : ''),
                    readonly || disabled ? 'bg-gray-100 cursor-not-allowed text-gray-600 dark:bg-gray-500' : ''
                ]" :placeholder="placeholder" />
        </div>
    </div>
</template>
