// ============ INICIO IMPORTS ============ //
import { ref } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para manejar la carga de archivos
 * @param {Object} props - Props del componente
 * @param {Function} emit - Función emit
 * @returns {Object} - Métodos y estados para manejo de archivos
 */
export function useFileUpload(props, emit) {

    // ============ INICIO REFS ============ //
    const selectedFile = ref(null);
    const previewFileName = ref(null);
    const isDragging = ref(false);
    // ============ FIN REFS ============ //

    // ============ INICIO MÉTODOS ============ //
    /**
     * Abre el input file al hacer click en la dropzone
     */
    const triggerFileInput = (fieldName) => {
        const inputElement = document.getElementById(`file-input-${fieldName}`);
        if (inputElement) {
            inputElement.click();
        }
    };

    /**
     * Maneja la selección de archivo desde el input
     */
    const handleFileSelect = (event, fieldName) => {
        const file = event.target.files[0];
        if (file) {
            processFile(file, fieldName);
        }
    };

    /**
     * Maneja el drop de archivo
     */
    const handleFileDrop = (event, fieldName) => {
        isDragging.value = false;
        const file = event.dataTransfer.files[0];
        if (file) {
            processFile(file, fieldName);
        }
    };

    /**
     * Procesa y valida el archivo seleccionado
     */
    const processFile = (file, fieldName) => {
        // Obtener el field para validar extensiones permitidas
        const field = props.field;
        const allowedTypes = field?.acceptedTypes || props.props?.acceptedFileTypes || '.xlsx,.xls,.csv';
        const maxSize = field?.maxSize || props.props?.maxFileSize || 5;

        // Validar tipo de archivo
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        const allowedExtensions = allowedTypes.split(',').map(ext => ext.trim());

        if (!allowedExtensions.includes(fileExtension)) {
            alert(`Tipo de archivo no permitido. Use: ${allowedTypes}`);
            return;
        }

        // Validar tamaño (convertir MB a bytes)
        if (file.size > maxSize * 1024 * 1024) {
            alert(`El archivo es demasiado grande. Máximo ${maxSize}MB.`);
            return;
        }

        // Guardar archivo
        selectedFile.value = file;
        previewFileName.value = file.name;

        // Emitir actualización
        emit('update:modelValue', file);
    };

    /**
     * Elimina el archivo seleccionado
     */
    const removeFile = (fieldName) => {
        selectedFile.value = null;
        previewFileName.value = null;

        // Emitir actualización
        emit('update:modelValue', null);

        // Limpiar el input file
        const inputElement = document.getElementById(`file-input-${fieldName}`);
        if (inputElement) {
            inputElement.value = '';
        }
    };
    // ============ FIN MÉTODOS ============ //

    return {
        selectedFile,
        previewFileName,
        isDragging,
        processFile,
        removeFile,
        triggerFileInput,
        handleFileSelect,
        handleFileDrop
    };
}
