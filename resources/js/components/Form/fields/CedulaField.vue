<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed } from 'vue';

/**
 * Componentes
 */
import Input from '@/components/Input.vue';

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

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue']);

// ============================================================================
// COMPUTED - CAMPO COMPLEMENTO CARNET
// ============================================================================
const complementField = computed(() => {
    return props.props.fields.find(field =>
        field.typeInput === 'comple' && field.typeCi === 'ci'
    );
});

</script>

<template>
    <div class="w-full">

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
