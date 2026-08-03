<script setup>
import { onUnmounted, onMounted, ref, watch } from 'vue';
import Button from './Button.vue';
import FileValidation from './FileValidation.vue';
import { useExcelValidation } from '../composables/useExcelValidation';
import Modal from './Modal.vue';
import Icon from './Icon.vue';

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

    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-2xl" @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="upload" class-name="text-white" :size="20" />
            </div>
        </template>
        <template #label1>{{ titulo }}</template>
        <template #label2>{{ subtitulo }}</template>

        <!-- Body -->
        <div class="space-y-2.5">

            <!-- Info columnas obligatorias -->
            <div
                class="bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600/40 px-4 py-3">

                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2 px-1">
                        <span
                            class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide whitespace-nowrap">
                            Columnas requeridas
                        </span>
                        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700 w-4"></div>
                    </div>

                    <button v-if="mostrarDescargaPlantilla" @click="descargarPlantilla"
                        title="Descarga nuestra plantilla para asegurar que tu archivo tenga el formato correcto."
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700/40 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                        Descargar plantilla
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-600/40">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700">
                                <th v-for="(columna, index) in columnasTabla" :key="`header-${index}`"
                                    class="px-4 py-2.5 text-center font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap border-r border-gray-200 dark:border-gray-600 last:border-r-0">
                                    {{ columna.nombre }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white dark:bg-gray-800">
                                <td v-for="(columna, index) in columnasTabla" :key="`status-${index}`"
                                    class="text-center px-4 py-2 border-r border-gray-100 dark:border-gray-700 last:border-r-0">
                                    <span :class="[
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border',
                                        columna.obligatorio
                                            ? 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/20 dark:text-indigo-400 dark:border-indigo-700/40'
                                            : 'bg-gray-100 text-slate-500 border-gray-200 dark:bg-gray-700/40 dark:text-slate-400 dark:border-gray-600/40'
                                    ]">
                                        <span
                                            :class="['w-1.5 h-1.5 rounded-full', columna.obligatorio ? 'bg-indigo-500' : 'bg-slate-400']"></span>
                                        {{ columna.obligatorio ? 'Obligatorio' : 'Opcional' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dropzone -->
            <div v-if="!archivoImportar" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop" @click="fileInput.click()" :class="[
                    'rounded-xl border-2 border-dashed px-4 py-8 text-center transition-all duration-200 cursor-pointer',
                    dragging
                        ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/10'
                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/20'
                ]">
                <input ref="fileInput" type="file" @change="handleFileChange" :accept="formatosAceptados"
                    class="hidden" />
                <svg class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600 mb-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Arrastra tu archivo aquí o
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">selecciónalo</span>
                </p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                    {{ formatosTexto }} · Máx. {{ tamanoMaximo }}MB
                </p>
            </div>

            <!-- Preview archivo -->
            <FileValidation v-else :file-name="archivoImportar.name" :file="archivoImportar"
                :validation="fileValidation" @remove="limpiarArchivo" />

        </div>

        <!-- Footer -->
        <template #footer>
            <div class="sm:px-5 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center sm:justify-end gap-2">
                    <Button @click="$emit('close')"
                        :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border border-gray-200'"
                        class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                        {{ textoBotonCancelar }}
                    </Button>
                    <Button @click="importarArchivo"
                        :disabled="!archivoImportar || importando || !fileValidation.isValid"
                        :style="'py-3 px-4 sm:px-6 sm:py-2.5 rounded-xl'"
                        class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                        <svg v-if="importando" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span>{{ importando ? 'Importando...' : textoBotonImportar }}</span>
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
