<script setup>
// ============ INICIO IMPORTS ============ //
import { computed } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
import Dropdown from '@/components/Dropdown.vue';
import { can } from '@/lib/can';
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
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['update:modelValue', 'openFormOption']);
// ============ FIN EMITS ============ //

// ============ INICIO COMPUTED ============ //
// Texto de la opción seleccionada
const selectedOptionText = computed(() => {
    // Si no hay valor, retornar null para mostrar el placeholder
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }

    // Buscar la opción que coincida con el valor actual
    const selectedOption = props.field.options?.find(option => {
        // Convertir ambos valores a string para comparación
        const optionValue = String(option.value);
        const currentValue = String(props.modelValue);
        return optionValue === currentValue;
    });

    // Si encuentra la opción, retornar su texto, sino retornar el valor como string
    return selectedOption ? selectedOption.text : String(props.modelValue);
});

// Clases del botón según estado
const buttonClasses = computed(() => {
    const baseClasses = 'inline-flex items-center justify-between w-full gap-3 p-2 text-sm font-medium rounded-lg shadow-sm transition-all focus:ring-1 focus:outline-none border cursor-pointer';

    if (props.error && !props.modelValue) {
        return `${baseClasses} border-red-500 text-red-500 bg-red-50 hover:bg-red-100 focus:ring-red-300`;
    }

    if (props.modelValue && props.modelValue.toString().trim() !== '') {
        return `${baseClasses} border-gray-300 text-gray-800 bg-gray-50 hover:bg-gray-100 hover:border-blue-400 focus:ring-blue-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600`;
    }

    return `${baseClasses} border-gray-300 text-gray-500 bg-gray-50 hover:bg-gray-100 hover:border-blue-400 focus:ring-blue-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600`;
});
// ============ FIN COMPUTED ============ //

// ============ INICIO MÉTODOS ============ //
const selectOption = (value) => {
    emit('update:modelValue', value);
};
// ============ FIN MÉTODOS ============ //
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <div class="flex" v-show="field.label !== ''">
            <InputLabel :for="field.name" :value="field.label"
                class="block text-sm font-medium text-gray-900 dark:text-white" />
            <span :style="{ visibility: field.required === true ? 'visible' : 'hidden' }" class="ms-1 text-red-600">
                *
            </span>
        </div>

        <!-- Dropdown -->
        <Dropdown align="left" width="60">
            <template #trigger>
                <button type="button" :class="buttonClasses" :aria-required="field.required"
                    :aria-label="'Dropdown for ' + field.name">
                    <span class="truncate">
                        {{ selectedOptionText || `Seleccione ${field.placeholder}` }}
                    </span>
                    <svg class="w-2.5 h-2.5 ms-3 transition-transform duration-200 flex-shrink-0" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 4 4 4-4" />
                    </svg>
                </button>
            </template>

            <template #content>
                <div
                    class="w-full max-w-sm bg-white rounded-lg border border-gray-200 shadow-lg dark:bg-gray-800 dark:border-gray-700">
                    <!-- Lista scrolleable con altura limitada -->
                    <div class="max-h-48 overflow-y-auto">
                        <ul class="py-1">
                            <!-- Mensaje cuando no hay opciones -->
                            <li v-if="!field.options || field.options.length === 0" class="px-4 py-3">
                                <div class="flex items-center gap-3 text-slate-400 dark:text-gray-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <span class="text-sm">No hay opciones disponibles</span>
                                </div>
                            </li>

                            <!-- Opciones del select -->
                            <li v-for="option in field.options" :key="option.value">
                                <button type="button" @click="selectOption(option.value)"
                                    class="flex items-center justify-between w-full px-4 py-2 text-sm text-left hover:bg-gray-100 transition-colors duration-150 dark:hover:bg-gray-700"
                                    :class="modelValue && modelValue.toString() === option.value.toString()
                                        ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900 dark:text-blue-200'
                                        : 'text-gray-700 dark:text-gray-300'">
                                    <span>{{ option.text }}</span>
                                    <svg v-if="modelValue && modelValue.toString() === option.value.toString()"
                                        class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Botón para agregar nueva opción (fuera del scroll) -->
                    <div v-if="field.add && (can('distrito-superusuario') || can('discapacidad-superusuario'))"
                        class="border-t border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                        <button type="button" @click="emit('openFormOption')"
                            class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150 dark:text-blue-400 dark:hover:bg-gray-600 dark:hover:text-blue-300">
                            <div
                                class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center dark:bg-blue-900">
                                <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                </svg>
                            </div>
                            <span>Agregar nueva opción</span>
                        </button>
                    </div>
                </div>
            </template>
        </Dropdown>

        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
