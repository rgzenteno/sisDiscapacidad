<script setup>
// ============ INICIO IMPORTS ============ //
import { ref, watch } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
import { useFileUpload } from '../composables/useFileUpload';
import { useExcelValidation } from '../composables/useExcelValidation';
import { usePdfValidation } from '../composables/usePdfValidation';
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
    },
    form: { // ✅ AGREGAR ESTA PROP
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
const { pdfValidation, validatePdfStructure } = usePdfValidation();
// ============ FIN COMPOSABLES ============ //

// ============ INICIO WATCHERS ============ //
watch(() => selectedFile.value, async (newFile) => {
    if (!newFile) {
        // ✅ LIMPIAR VALIDACIÓN SI SE REMUEVE ARCHIVO
        props.form.archivo_validacion = null;
        props.form.gestion_extraida = null;
        props.form.registros_extraidos = null;
        return;
    }

    // ✅ FEEDBACK INMEDIATO antes de procesar
    fileValidation.value = {
        isValid: null,
        message: 'Validando archivo...',
        missingFields: [],
        foundFields: [],
    };

    const fileExtension = '.' + newFile.name.split('.').pop().toLowerCase();

    // ============ VALIDAR PDF ============ //
    if (fileExtension === '.pdf') {
        const expectedGestion = props.props?.data?.mes && props.props?.data?.id
            ? {
                mes: props.props.data.mes,
                año: props.props.data.año
            }
            : null;

        const validation = await validatePdfStructure(newFile, expectedGestion);

        fileValidation.value = validation;

        // ✅ GUARDAR VALIDACIÓN EN EL FORM
        props.form.archivo_validacion = validation;
        props.form.gestion_extraida = validation.extractedData?.gestion
            ? JSON.stringify(validation.extractedData.gestion)
            : null;
        props.form.registros_extraidos = validation.extractedData?.registros?.length
            ? JSON.stringify(validation.extractedData.registros)
            : null;

        return;
    }

    // ============ VALIDAR EXCEL ============ //
    if (['.xlsx', '.xls', '.csv'].includes(fileExtension)) {
        if (props.field.requiredColumns && props.field.requiredColumns.length > 0) {
            const validation = await validateExcelColumns(newFile, props.field.requiredColumns);
            fileValidation.value = validation;

            // ✅ GUARDAR VALIDACIÓN EN EL FORM
            props.form.archivo_validacion = validation;
        } else {
            fileValidation.value = {
                isValid: true,
                message: '✓ Archivo cargado correctamente',
                missingColumns: [],
                foundColumns: []
            };
            props.form.archivo_validacion = fileValidation.value;
        }
        return;
    }

    // Archivo no soportado
    fileValidation.value = {
        isValid: false,
        message: 'Tipo de archivo no soportado',
        missingColumns: [],
        foundColumns: []
    };
    props.form.archivo_validacion = fileValidation.value;
}, { immediate: true });
// ============ FIN WATCHERS ============ //
</script>

<template>
    <div class="w-full col-span-full">
        <!-- Label -->
        <div class="flex mb-1" v-show="field.label !== ''">
            <InputLabel :for="field.name" :value="field.label"
                class="block text-sm font-medium text-gray-900 dark:text-white" />
            <span :style="{ visibility: field.required === true ? 'visible' : 'hidden' }" class="ms-1 text-red-600">
                *
            </span>
        </div>

        <!-- Dropzone o Preview -->
        <FileDropzone v-if="!selectedFile" :field="field" :is-dragging="isDragging" :error="error"
            @click="triggerFileInput(field.name)" @drop="handleFileDrop($event, field.name)"
            @dragover="isDragging = true" @dragleave="isDragging = false" />

        <FilePreview v-else :file-name="previewFileName" :file="selectedFile" :validation="fileValidation"
            @remove="removeFile(field.name)" />

        <!-- Validación del archivo -->
        <!-- <FileValidation v-if="selectedFile && fileValidation.message" :validation="fileValidation" /> -->

        <!-- Input file oculto -->
        <input :id="`file-input-${field.name}`" type="file" :accept="field.acceptedTypes || props.acceptedFileTypes"
            @change="handleFileSelect($event, field.name)" class="hidden" />

        <!-- Mensaje de error -->
        <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </div>
</template>
