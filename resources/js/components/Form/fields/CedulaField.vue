<script setup>
// ============ INICIO IMPORTS ============ //
import { computed } from 'vue';
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
    form: {
        type: Object,
        required: true
    },
    props: {
        type: Object,
        required: true
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['update:modelValue']);
// ============ FIN EMITS ============ //

// ============ INICIO COMPUTED ============ //
// Buscar el campo complemento
const complementField = computed(() => {
    return props.props.fields.find(field =>
        field.typeInput === 'comple' && field.typeCi === 'ci'
    );
});
// ============ FIN COMPUTED ============ //
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

        <!-- Cédula + Complemento -->
        <div class="relative">
            <div class="flex items-center">
                <!-- Campo Cédula -->
                <Input
                    :input-type="field.type"
                    class="flex-1"
                    :model-value="modelValue"
                    :customClass="field.nameStyle"
                    :maxlength="field.range"
                    :placeholder="'Ingrese ' + field.placeholder"
                    :errors="error && !modelValue"
                    :autofocus="field.autofocus || false"
                    @update:model-value="emit('update:modelValue', $event)"
                />

                <!-- Separador -->
                <span class="text-gray-400 mx-2">-</span>

                <!-- Campo Complemento -->
                <Input
                    v-if="complementField"
                    :input-type="complementField.type"
                    class="w-20"
                    :model-value="form[complementField.name]"
                    :customClass="complementField.nameStyle"
                    :maxlength="complementField.range"
                    placeholder="Ext"
                    :errors="form.errors[complementField.name] && !form[complementField.name]"
                    @update:model-value="form[complementField.name] = $event"
                />
            </div>
        </div>
    </div>
</template>
