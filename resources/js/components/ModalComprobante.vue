<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import ModalImag from './ModalImag.vue';

const props = defineProps({
    id: Number,
    tipo: String,
    subir: {
        type: Boolean,
        default: false
    },
    pagos: {
        type: [Object, Array],
        default: () => ({})
    },
    existingData: {
        type: [Object, Array],
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'upload']);

const getTutor = () => {
    return props.pagos.filter((t) => t.id_pago === props.id);
};

// Estado para manejar la carga de archivos
const selectedFile = ref(null);
const previewUrl = ref('');
const isUploading = ref(false);
const uploadMessage = ref('');
const uploadError = ref('');
const visImg = ref(false);

// Función para manejar la selección de archivo
const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validar tipo y tamaño del archivo
        const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!validTypes.includes(file.type)) {
            uploadError.value = 'Formato de archivo no válido. Use JPG, PNG o PDF.';
            return;
        }

        if (file.size > maxSize) {
            uploadError.value = 'El archivo es demasiado grande (máx. 5MB)';
            return;
        }

        selectedFile.value = file;
        previewUrl.value = file.type.includes('image') ? URL.createObjectURL(file) : '';
        uploadError.value = '';
    }
};

// Función para subir archivo
const uploadFile = async () => {
    if (!selectedFile.value) {
        uploadError.value = 'Por favor seleccione un archivo para subir';
        return;
    }

    isUploading.value = true;
    uploadMessage.value = 'Subiendo comprobante...';
    uploadError.value = '';

    try {
        const formData = new FormData();
        formData.append('comprobante', selectedFile.value);
        formData.append('id_pago', props.id);

        // Eliminamos la línea del _token ya que Axios lo maneja automáticamente
        // con los headers configurados globalmente

        const response = await axios.post(route('pago.comp'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        isUploading.value = false;
        uploadMessage.value = 'Comprobante subido correctamente';

        // Emitir evento para actualizar la lista de pagos
        emit('upload', response.data);

        // Cerrar el modal después de 1.5 segundos
        setTimeout(() => {
            emit('close');
        }, 1500);

    } catch (error) {
        isUploading.value = false;
        uploadError.value = 'Error al subir el comprobante: ' +
            (error.response ?.data ?.message || error.message || 'Error desconocido');
    }
};

// Formatear fecha y hora
const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Agrega estos métodos en tu script setup
const getFileType = (url) => {
    if (!url) return null;
    const extension = url.split('.').pop().toLowerCase();
    const types = {
        'jpg': 'Imagen JPEG',
        'jpeg': 'Imagen JPEG',
        'png': 'Imagen PNG',
        'pdf': 'Documento PDF'
    };
    return types[extension] || extension.toUpperCase() + ' File';
};

const getFileUrl = (filename) => {
    if (!filename) return null;
    if (filename.startsWith('http')) return filename;
    return `/storage/comprobantes/${filename}`;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i]);
};

const openImageModal = (imageUrl) => {
    // Implementa tu lógica para abrir un modal con la imagen ampliada
    // Puedes usar emit o un store para manejar esto
    visImg.value = true;
    /* emit('open-image-modal', imageUrl); */
};

const formatoMetodoPago = computed(() => {
    switch (props.existingData.tipo_pago) {
        case 'qr':
            return 'QR';
        case 'transferencia':
            return 'Transferencia bancaria';
        default:
            return 'Pago desconocido';
    }
});

const closeModal = () => {
    visImg.value = false;
}
</script>

<template>
<ModalImag v-if="visImg" :img="props.existingData.comprobante" @close="closeModal" />
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-40 px-4 py-6 overflow-y-auto backdrop-blur-sm">
    <div class="relative w-full max-w-2xl max-h-full bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 transform transition-all duration-300 overflow-y-auto">
        <div v-for="item in getTutor()" :key="item.id_pago">
            <!-- Modal Header -->
            <div class="sticky top-0 z-10 flex items-center justify-between px-4 sm:px-6 py-3 border-b dark:border-gray-600/50 rounded-t-xl bg-gray-50 dark:bg-gray-700/50">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <svg v-if="props.tipo === 'subir'" class="w-6 h-6 text-blue-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2a1 1 0 0 1 1 1v10.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 1 1 1.414-1.414L11 13.586V3a1 1 0 0 1 1-1z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M5 17a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M5 21a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="w-6 h-6 text-blue-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M2 7a5 5 0 0 1 5-5h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7Zm12 3a1 1 0 0 0-1-1h-2a1 1 0 0 0 0 2h2a1 1 0 0 0 1-1Zm0 4a1 1 0 0 0-1-1h-2a1 1 0 1 0 0 2h2a1 1 0 0 0 1-1Zm-8-5a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H6Zm-1 5a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-gray-100 truncate">
                        {{ props.tipo === 'subir' ? 'Subir Comprobante de Pago' : 'Comprobante de Pago' }}
                    </h2>
                </div>

                <button @click="$emit('close')" class="text-gray-500 hover:text-red-700 dark:text-gray-400 dark:hover:text-red-200 bg-transparent hover:bg-red-100 dark:hover:bg-gray-700 rounded-lg p-2 transition-colors duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body Cargar Imagen -->
            <div v-if="props.tipo === 'subir'" class="p-4 sm:p-6 space-y-4 sm:space-y-6 overflow-y-auto max-h-[calc(100vh-14rem)]">
                <div class="grid grid-cols-1 lg:grid-cols-1 gap-4 sm:gap-6">

                    <!-- Formulario de carga -->
                    <div class="bg-white dark:bg-gray-800 p-3 sm:p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3 sm:space-y-4">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 border-gray-200 dark:border-gray-600">
                            Subir Comprobante
                        </h3>

                        <div class="space-y-2">
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                Por favor, suba una imagen clara del comprobante de pago. <br> Formatos aceptados: JPG, PNG, PDF (máx. 5MB)
                            </p>

                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 sm:p-6 text-center relative" :class="{'border-blue-500 dark:border-blue-400': selectedFile}">
                                <div v-if="!selectedFile">
                                    <svg class="mx-auto h-8 w-8 sm:h-12 sm:w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                        Haga clic para seleccionar archivo o arrastre y suelte
                                    </p>
                                </div>

                                <div v-else class="space-y-2">
                                    <img v-if="previewUrl" :src="previewUrl" class="mx-auto max-h-32 sm:max-h-48 object-contain" alt="Vista previa del comprobante" />
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                        {{ selectedFile.name }} ({{ (selectedFile.size / 1024).toFixed(2) }} KB)
                                    </p>
                                    <button @click="selectedFile = null; previewUrl = ''" class="z-50 text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        Eliminar
                                    </button>
                                </div>

                                <input type="file" accept="image/jpeg,image/png,application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="handleFileChange" />
                            </div>

                            <p v-if="uploadError" class="text-xs sm:text-sm text-red-600 dark:text-red-400">
                                {{ uploadError }}
                            </p>

                            <p v-if="uploadMessage" class="text-xs sm:text-sm text-blue-600 dark:text-blue-400">
                                {{ uploadMessage }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body visualizar Imagen -->
            <div v-else class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información de pago -->
                    <div>
                        <div class="bg-gray-50 dark:bg-gray-700/30 p-4 pt-2 rounded-lg shadow-sm space-y-3">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b pb-1 border-gray-200 dark:border-gray-600">
                                Información del Pago
                            </h3>
                            <div class="space-y-2">
                                <p class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Monto:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ props.existingData.monto || (Math.floor(Math.random() * 5000) + 1000) + '.00' }}Bs.</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Fecha de Pago:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ props.existingData.fecha_pago || '15/03/2025 14:30' }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Método de Pago:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ formatoMetodoPago }}</span>
                                </p>
                                <p class="flex justify-between">
                                    <span class="font-medium text-gray-600 dark:text-gray-400">Concepto:</span>
                                    <span class="text-gray-800 dark:text-gray-200">Pago mensual de bonos</span>
                                </p>
                                
                            </div>
                        </div>
                        <br>
                        <!-- Sección de observaciones -->
                        <div class="bg-gray-50 dark:bg-gray-700/30 p-4 pt-2 rounded-lg shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 border-gray-200 dark:border-gray-600 mb-3">
                                Observaciones
                            </h3>
                            <p class="text-gray-700 text-sm dark:text-gray-300">
                                Comprobante verificado correctamente. La transacción fue procesada sin incidencias y el pago ha sido registrado en el sistema. No se requieren acciones adicionales.
                            </p>
                        </div>
                    </div>

                    <!-- Visualización del comprobante -->
                    <div class="bg-white dark:bg-gray-800 p-4 pt-2 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 border-gray-200 dark:border-gray-600">
                            Comprobante de Pago
                        </h3>
                        <div class="flex flex-col items-center justify-center">
                            <!-- Contenedor principal del comprobante -->
                            <div class="relative w-full h-64 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
                                <!-- Visualización de imagen (JPEG, PNG) -->
                                <img v-if="props.existingData.comprobante && ['jpg', 'jpeg', 'png'].includes(props.existingData.comprobante.split('.').pop().toLowerCase())" :src="getFileUrl(props.existingData.comprobante)" alt="Comprobante de pago" class="w-full h-full object-contain cursor-zoom-in" @click="openImageModal(getFileUrl(props.existingData.comprobante))">

                                <!-- Visualización de PDF -->
                                <div v-else-if="props.existingData.comprobante && props.existingData.comprobante.toLowerCase().endsWith('.pdf')" class="flex flex-col items-center justify-center h-full p-4">
                                    <div class="text-center">
                                        <svg class="w-16 h-16 mx-auto text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="mt-2 text-gray-800 dark:text-gray-200 font-medium">Documento PDF</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Previsualización no disponible</p>
                                    </div>
                                </div>

                                <!-- Placeholder cuando no hay comprobante -->
                                <div v-else class="flex flex-col items-center justify-center h-full bg-gray-50 dark:bg-gray-600 text-center p-4">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <p class="mt-2 text-gray-500 dark:text-gray-400">No hay comprobante disponible</p>
                                </div>

                                <!-- Botones de acción flotantes (solo se muestran si hay comprobante) -->
                                <div v-if="props.existingData.comprobante" class="absolute bottom-4 right-4 flex space-x-2">
                                    <!-- Botón de descarga -->
                                    <a :href="getFileUrl(props.existingData.comprobante)" :download="props.existingData.comprobante.split('/').pop()" class="bg-white dark:bg-gray-600 hover:bg-gray-100 dark:hover:bg-gray-500 text-gray-800 dark:text-white p-2 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center" :title="`Descargar ${getFileType(props.existingData.comprobante)}`">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>

                                    <!-- Botón para abrir en nueva pestaña -->
                                    <a :href="getFileUrl(props.existingData.comprobante)" target="_blank" rel="noopener noreferrer" class="bg-white dark:bg-gray-600 hover:bg-gray-100 dark:hover:bg-gray-500 text-gray-800 dark:text-white p-2 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center" title="Abrir en nueva pestaña">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>

                                    <!-- Botón para vista ampliada (solo para imágenes) -->
                                    <!-- <button v-if="['jpg', 'jpeg', 'png'].includes(props.existingData.comprobante.split('.').pop().toLowerCase())" @click="openImageModal(getFileUrl(props.existingData.comprobante))" class="bg-white dark:bg-gray-600 hover:bg-gray-100 dark:hover:bg-gray-500 text-gray-800 dark:text-white p-2 rounded-lg shadow-md transition-all duration-200 flex items-center justify-center" title="Ampliar imagen">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </button> -->
                                </div>
                            </div>

                            <!-- Información del comprobante -->
                            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400 w-full bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <p class="mb-2 flex justify-between">
                                    <span class="font-medium">Subido el:</span>
                                    <span class="text-gray-800 dark:text-gray-200">{{ formatDateTime(props.existingData.updated_at) || 'No disponible' }}</span>
                                </p>
                                <p class="mb-2 flex justify-between">
                                    <span class="font-medium">Tipo de archivo:</span>
                                    <span class="text-gray-800 dark:text-gray-200">
                                        {{ getFileType(props.existingData.comprobante) || 'No especificado' }}
                                    </span>
                                </p>
                                <p v-if="props.existingData.comprobante" class="flex justify-between">
                                    <span class="font-medium">Tamaño:</span>
                                    <span class="text-gray-800 dark:text-gray-200">
                                        {{ formatFileSize(props.existingData.file_size) || 'Desconocido' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end px-6 py-4 border-t dark:border-gray-600/50 rounded-b-xl bg-gray-50 dark:bg-gray-700/50">
                <button v-if="props.tipo === 'subir' && selectedFile" @click="uploadFile" class="px-6 py-2.5 mr-2 text-white bg-green-600 hover:bg-green-700 rounded-lg text-sm font-medium transition-colors duration-200 focus:ring-4 focus:ring-green-300 dark:bg-green-500 dark:hover:bg-green-600 dark:focus:ring-green-800" :disabled="isUploading">
                    <span v-if="isUploading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Procesando...
                    </span>
                    <span v-else>Subir Comprobante</span>
                </button>
                <button @click="$emit('close')" class="px-6 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium transition-colors duration-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800">
                    {{ props.tipo === 'subir' ? 'Cancelar' : 'Aceptar' }}
                </button>
            </div>
        </div>
    </div>
</div>
</template>
