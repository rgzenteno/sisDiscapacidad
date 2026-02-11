<script setup>
// ============ INICIO IMPORTS ============ //
import { ref, watch } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
import { useFileUpload } from '../composables/useFileUpload';
import { useExcelValidation } from '../composables/useExcelValidation';
import FileDropzone from './sub-components/FileDropzone.vue';
import FilePreview from './sub-components/FilePreview.vue';
import FileValidation from './sub-components/FileValidation.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [File, null],
        default: null
    },
    error: {
        type: String,
        default: ''
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

// ============ INICIO COMPOSABLES ============ //
const {
    selectedFile,
    previewFileName,
    isDragging,
    processFile,
    removeFile,
    triggerFileInput,
    handleFileSelect,
    handleFileDrop
} = useFileUpload(props, emit);

const { fileValidation, validateExcelColumns } = useExcelValidation();
// ============ FIN COMPOSABLES ============ //

// ============ INICIO WATCHERS ============ //
// Watch para validar columnas del Excel cuando se carga un archivo
watch(() => selectedFile.value, async (newFile) => {
    if (newFile && props.field.requiredColumns && props.field.requiredColumns.length > 0) {
        const validation = await validateExcelColumns(newFile, props.field.requiredColumns);
        fileValidation.value = validation;
    } else if (newFile) {
        fileValidation.value = {
            isValid: true,
            message: '✓ Archivo cargado correctamente',
            missingColumns: [],
            foundColumns: []
        };
    }
}, { immediate: true });
// ============ FIN WATCHERS ============ //
</script>

<template>
    <div class="w-full col-span-full">
        <!-- Label -->
        <div class="flex mb-2" v-show="field.label !== ''">
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

        <!-- Dropzone o Preview -->
        <FileDropzone
            v-if="!selectedFile"
            :field="field"
            :is-dragging="isDragging"
            :error="error"
            @click="triggerFileInput(field.name)"
            @drop="handleFileDrop($event, field.name)"
            @dragover="isDragging = true"
            @dragleave="isDragging = false"
        />

        <FilePreview
            v-else
            :file-name="previewFileName"
            :file="selectedFile"
            :validation="fileValidation"
            @remove="removeFile(field.name)"
        />

        <!-- Validación del archivo -->
        <FileValidation
            v-if="selectedFile && fileValidation.message"
            :validation="fileValidation"
        />

        <!-- Input file oculto -->
        <input
            :id="`file-input-${field.name}`"
            type="file"
            :accept="field.acceptedTypes || props.acceptedFileTypes"
            @change="handleFileSelect($event, field.name)"
            class="hidden"
        />

        <!-- Mensaje de error -->
        <p v-if="error" class="mt-2 flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                {{ error }}
            </span>
        </p>
    </div>
</template>
