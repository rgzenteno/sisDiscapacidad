<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';

/**
 * Componentes
 */
import Dropdown from '@/components/Dropdown.vue';
import Icon from '@/components/Icon.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';

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
    }
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue', 'openFormOption']);

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Retorna el texto de la opción seleccionada o null si no hay valor
 */
const selectedOptionText = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }

    const selectedOption = props.field.options?.find(option => {
        const optionValue = String(option.value);
        const currentValue = String(props.modelValue);
        return optionValue === currentValue;
    });

    return selectedOption ? selectedOption.text : String(props.modelValue);
});

/**
 * Texto de búsqueda — solo se usa cuando `field.searchable` está activo
 * (ver PresupuestoController/Presupuesto/Index.vue para el primer caso de
 * uso: elegir un cajero de una lista larga sin scrollear a ciegas). No
 * afecta a los selects existentes que no pasan `searchable: true`.
 */
const search = ref('');

const filteredOptions = computed(() => {
    if (!props.field.searchable || !search.value.trim()) return props.field.options ?? [];

    const term = search.value.trim().toLowerCase();
    return (props.field.options ?? []).filter(option =>
        String(option.text).toLowerCase().includes(term)
    );
});

/**
 * Retorna las clases CSS del botón según su estado (error, con valor, vacío)
 */
const buttonClasses = computed(() => {
    const base = 'inline-flex items-center justify-between w-full gap-3 px-4 py-2 text-sm font-medium rounded-xl border transition-colors duration-200 shadow-sm cursor-pointer outline-none bg-gray-50 dark:bg-gray-700/40 text-slate-400 dark:text-slate-200';

    if (props.error && !props.modelValue) {
        return `${base} border-rose-400 dark:border-rose-500 text-rose-500 dark:text-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500/20`;
    }

    if (props.modelValue?.toString().trim()) {
        return `${base} border-gray-400 dark:border-gray-600/40 hover:border-[rgb(var(--brand-300))] dark:hover:border-[rgb(var(--brand-600))] focus:border-[rgb(var(--brand-400))] focus:ring-0 focus:ring-gray-100 text-slate-700`;
    }

    return `${base} border-gray-400 dark:border-gray-600/40 hover:border-[rgb(var(--brand-300))] dark:hover:border-[rgb(var(--brand-600))] focus:border-[rgb(var(--brand-400))] focus:ring-0 focus:ring-gray-100 text-slate-400 dark:text-slate-500`;
});

// ============================================================================
// FUNCIONES
// ============================================================================

/**
 * Emite el valor seleccionado del dropdown al componente padre
 * @param {string|number} value - Valor de la opción seleccionada
 */
const selectOption = (value) => {
    emit('update:modelValue', value);
    search.value = '';
};
</script>

<template>
    <div class="flex flex-col flex-1 min-w-0 relative">

        <!-- Dropdown -->
        <Dropdown align="left" width="60" :match-trigger-width="!!field.matchTriggerWidth">
            <template #trigger="{ open }">
                <button type="button" :class="buttonClasses" :aria-required="field.required"
                    :aria-label="'Dropdown for ' + field.name">
                    <span class="flex-1 text-left truncate"
                        :class="props.error && !props.modelValue ? 'text-rose-400' : ''">
                        {{ selectedOptionText || `Seleccione ${field.placeholder}` }}
                    </span>

                    <Icon :icon-button="true" name="angleDown"
                        :class-name="`transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'} ${props.error && !props.modelValue ? 'text-rose-400' : 'text-slate-400'}`"
                        fill="none" stroke="currentColor" stroke-width="2" :size="17" />
                </button>
            </template>

            <template #content>
                <div class="shadow-xl overflow-hidden">
                    <div v-if="field.searchable" class="p-2 border-b border-gray-100 dark:border-gray-600">
                        <input v-model="search" type="text" placeholder="Buscar..."
                            class="w-full text-sm rounded-lg px-3 py-1.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 outline-none focus:border-[rgb(var(--brand-400))]"
                            @click.stop />
                    </div>
                    <ul class="py-1.5 max-h-60 overflow-y-auto">
                        <li v-if="!field.options || field.options.length === 0" class="px-4 py-3">
                            <div class="flex items-center gap-3 text-slate-400 dark:text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <span class="text-sm">No hay opciones disponibles</span>
                            </div>
                        </li>

                        <li v-else-if="field.searchable && filteredOptions.length === 0" class="px-4 py-3">
                            <span class="text-sm text-slate-400 dark:text-slate-500">Sin resultados para "{{ search }}"</span>
                        </li>

                        <li v-for="option in filteredOptions" :key="option.value">
                            <button type="button" @click="selectOption(option.value)"
                                class="flex items-center justify-between w-full px-4 py-2 text-sm text-left transition-colors duration-150"
                                :class="modelValue && modelValue.toString() === option.value.toString()
                                    ? 'bg-[rgb(var(--brand-50))] text-[rgb(var(--brand-700))] font-semibold dark:bg-[rgba(var(--brand-900),0.3)] dark:text-[rgb(var(--brand-300))]'
                                    : 'text-slate-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                <span>{{ option.text }}</span>
                                <svg v-if="modelValue && modelValue.toString() === option.value.toString()"
                                    class="w-4 h-4 text-[rgb(var(--brand-700))] dark:text-[rgb(var(--brand-400))] flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </li>
                    </ul>
                </div>

                <div v-if="field.add && (can('distrito-superusuario') || can('discapacidad-superusuario'))"
                    class="flex justify-center items-center border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40 rounded-b-xl">
                    <button type="button" @click="emit('openFormOption')"
                        class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm font-semibold text-[rgb(var(--brand-500))] hover:text-[rgb(var(--brand-600))] transition-colors duration-150">
                        <Icon :icon-button="true" name="circlePlus" class-name="text-[rgb(var(--brand-500))]" :size="20" />
                        <span>Agregar nueva opción</span>
                    </button>
                </div>
            </template>
        </Dropdown>

        <!-- Error -->
        <!-- <p v-if="error" class="mt-1 text-xs text-rose-500 dark:text-rose-400">{{ error }}</p> -->
    </div>
</template>
