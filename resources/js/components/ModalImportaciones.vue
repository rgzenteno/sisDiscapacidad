<script setup>
import { onUnmounted, onMounted, ref, watch } from 'vue';
import Button from './Button.vue';
import FileValidation from './FileValidation.vue';
import { useExcelValidation } from '../composables/useExcelValidation';

const props = defineProps({
    // Configuración del encabezado
    titulo: {
        type: String,
        default: 'Importar Archivo'
    },
    subtitulo: {
        type: String,
        default: 'Sube tu archivo para continuar'
    },
    icono: {
        type: String,
        default: 'upload', // 'upload', 'file', 'import'
        validator: (value) => ['upload', 'file', 'import'].includes(value)
    },

    // Configuración del archivo
    formatosAceptados: {
        type: String,
        default: '.xlsx,.xls,.csv'
    },
    formatosTexto: {
        type: String,
        default: 'XLSX, XLS o CSV'
    },
    tamanoMaximo: {
        type: Number,
        default: 10 // en MB
    },

    // Configuración de las columnas esperadas (para la tabla visual)
    columnasTabla: {
        type: Array,
        default: () => [
            { nombre: 'Nombre', obligatorio: true },
            { nombre: 'Apellido', obligatorio: true },
            { nombre: 'Distrito', obligatorio: true },
            { nombre: 'CI', obligatorio: true },
            { nombre: 'CI Tutor', obligatorio: false }
        ]
    },

    // Configuración de las columnas esperadas (para la lista)
    columnasEsperadas: {
        type: Array,
        default: () => [
            { nombre: 'Nº', descripcion: 'Número correlativo' },
            { nombre: 'DOCUMENTO DE IDENTIDAD', descripcion: 'CI del postulante' },
            { nombre: 'APELLIDOS + NOMBRES', descripcion: 'Se separará automáticamente con IA 🤖' }
        ]
    },

    // Configuración de la plantilla
    nombrePlantilla: {
        type: String,
        default: 'plantilla.xlsx'
    },
    urlPlantilla: {
        type: String,
        default: null
    },
    mostrarDescargaPlantilla: {
        type: Boolean,
        default: true
    },

    // Configuración de botones
    textoBotonImportar: {
        type: String,
        default: '🚀 Importar'
    },
    textoBotonCancelar: {
        type: String,
        default: 'Cancelar'
    },

    // Estados
    importando: {
        type: Boolean,
        default: false
    }
});

const archivoImportar = ref(null);
const dragging = ref(false);
const fileInput = ref(null);

const emit = defineEmits(['importar', 'close', 'descargar-plantilla']);

// ============ INICIO COMPOSABLES ============ //
const { fileValidation, validateExcelColumns } = useExcelValidation();
// ============ FIN COMPOSABLES ============ //

const iconos = {
    upload: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />`,
    file: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`,
    import: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />`
};

// ============ INICIO WATCHERS ============ //
// Watch para validar columnas del Excel cuando se carga un archivo
watch(() => archivoImportar.value, async (newFile) => {
    if (newFile && props.columnasTabla && props.columnasTabla.length > 0) {
        const validation = await validateExcelColumns(newFile, props.columnasTabla);
        fileValidation.value = validation;
    } else if (newFile) {
        fileValidation.value = {
            isValid: true,
            message: '✓ Archivo cargado correctamente',
            missingColumns: [],
            foundColumns: []
        };
    } else {
        // Resetear validación cuando no hay archivo
        fileValidation.value = {
            isValid: false,
            message: '',
            missingColumns: [],
            foundColumns: []
        };
    }
}, { immediate: true });
// ============ FIN WATCHERS ============ //

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file && validarTamano(file)) {
        archivoImportar.value = file;
    }
};

const handleDrop = (event) => {
    dragging.value = false;
    const files = event.dataTransfer.files;
    if (files.length > 0 && validarTamano(files[0])) {
        archivoImportar.value = files[0];
    }
};

const validarTamano = (file) => {
    const tamanoMB = file.size / (1024 * 1024);
    if (tamanoMB > props.tamanoMaximo) {
        alert(`El archivo es demasiado grande. Máximo ${props.tamanoMaximo}MB`);
        return false;
    }
    return true;
};

const importarArchivo = () => {
    if (archivoImportar.value && fileValidation.value.isValid) {
        emit('importar', archivoImportar.value, limpiarArchivo);
    }
};

const descargarPlantilla = () => {
    emit('descargar-plantilla', props.nombrePlantilla);
};

const limpiarArchivo = () => {
    archivoImportar.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    fileValidation.value = {
        isValid: false,
        message: '',
        missingColumns: [],
        foundColumns: []
    };
};


const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-4 z-40">
        <div
            class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl animate-[slideUp_0.3s_ease-out] transform transition-all duration-300">
            <!-- Header -->
            <div
                class="grid grid-cols-[1fr_auto] gap-6 px-6 py-3 border-b border-gray-100 bg-gray-50 dark:bg-gray-700/50 rounded-t-3xl">
                <div class="min-w-0">
                    <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                        <!-- Avatar con ícono dinámico -->
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                v-html="iconos[icono]">
                            </svg>
                        </div>

                        <!-- Título y subtítulo dinámicos -->
                        <div class="min-w-0">
                            <h2 class="text-2xl font-semibold text-slate-800 dark:text-gray-100 truncate">
                                {{ titulo }}
                            </h2>
                            <p class="text-sm text-slate-500 truncate">
                                {{ subtitulo }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botón cerrar -->
                <div class="flex items-start gap-3 flex-shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <!-- Tabla de columnas obligatorias -->
                <div class="text-sm text-blue-800 border border-blue-500 rounded-lg p-4 bg-blue-50 shadow-md">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-600 mt-0.5" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm1.018 8.828a2.34 2.34 0 0 0-2.373 2.13v.008a2.32 2.32 0 0 0 2.06 2.497l.535.059a.993.993 0 0 0 .136.006.272.272 0 0 1 .263.367l-.008.02a.377.377 0 0 1-.018.044.49.49 0 0 1-.078.02 1.689 1.689 0 0 1-.297.021h-1.13a1 1 0 1 0 0 2h1.13c.417 0 .892-.05 1.324-.279.47-.248.78-.648.953-1.134a2.272 2.272 0 0 0-2.115-3.06l-.478-.052a.32.32 0 0 1-.285-.341.34.34 0 0 1 .344-.306l.94.02a1 1 0 1 0 .043-2l-.943-.02h-.003Zm7.933 1.482a1 1 0 1 0-1.902-.62l-.57 1.747-.522-1.726a1 1 0 0 0-1.914.578l1.443 4.773a1 1 0 0 0 1.908.021l1.557-4.773Zm-13.762.88a.647.647 0 0 1 .458-.19h1.018a1 1 0 1 0 0-2H6.647A2.647 2.647 0 0 0 4 13.647v1.706A2.647 2.647 0 0 0 6.647 18h1.018a1 1 0 1 0 0-2H6.647A.647.647 0 0 1 6 15.353v-1.706c0-.172.068-.336.19-.457Z"
                                    clip-rule="evenodd" />
                            </svg>

                            <span class="font-semibold">Columnas obligatorias:</span>
                        </div>
                        <button v-if="mostrarDescargaPlantilla" @click="descargarPlantilla"
                            title="Descarga nuestra plantilla para asegurar que tu archivo tenga el formato correcto."
                            class="text-xs px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-md transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path>
                                <path fill-rule="evenodd"
                                    d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span>Descargar plantilla Excel</span>
                            <svg class="w-4 h-4 opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z">
                                </path>
                                <path
                                    d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z">
                                </path>
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-x-auto border border-blue-400 rounded-md bg-white shadow-sm">
                        <div
                            :class="`grid grid-cols-${columnasTabla.length} text-xs font-medium text-center whitespace-nowrap`">
                            <!-- Headers -->
                            <div v-for="(columna, index) in columnasTabla" :key="`header-${index}`"
                                class="whitespace-nowrap p-2 bg-blue-600 text-white border border-blue-500">
                                {{ columna.nombre }}
                            </div>

                            <!-- Obligatorio/Opcional -->
                            <div v-for="(columna, index) in columnasTabla" :key="`status-${index}`" :class="[
                                'p-2 border border-blue-300',
                                columna.obligatorio ? 'bg-blue-100' : 'bg-blue-100 text-gray-600'
                            ]">
                                {{ columna.obligatorio ? 'Obligatorio' : 'Opcional' }}
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Zona de Drop / Preview del archivo -->
                <div>
                    <!-- Dropzone (solo se muestra si NO hay archivo) -->
                    <div v-if="!archivoImportar" @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false" @drop.prevent="handleDrop" @click="fileInput.click()"
                        :class="[
                            'border-2 border-dashed rounded-lg p-8 text-center transition cursor-pointer',
                            dragging ? 'border-green-500 bg-green-50' : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
                        ]">
                        <input ref="fileInput" type="file" @change="handleFileChange" :accept="formatosAceptados"
                            class="hidden" />

                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">
                            Arrastra tu archivo aquí o
                            <span class="text-green-600 hover:text-green-700 font-medium">
                                selecciónalo
                            </span>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ formatosTexto }} (Máx. {{ tamanoMaximo }}MB)
                        </p>
                    </div>

                    <!-- Preview del archivo (solo se muestra si HAY archivo) -->
                    <FileValidation v-else :file-name="archivoImportar.name" :file="archivoImportar"
                        :validation="fileValidation" @remove="limpiarArchivo" />
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t rounded-b-3xl bg-gray-50">
                <Button @click="$emit('close')" class="text-slate-700 bg-white hover:bg-slate-100">
                    {{ textoBotonCancelar }}
                </Button>
                <Button @click="importarArchivo" :disabled="!archivoImportar || importando || !fileValidation.isValid"
                    class="text-white bg-blue-600 hover:bg-blue-500 disabled:bg-gray-300">
                    <div class="flex items-center gap-2">
                        <svg v-if="importando" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>{{ importando ? 'Importando...' : textoBotonImportar }}</span>
                    </div>
                </Button>
            </div>
        </div>
    </div>
</template>
