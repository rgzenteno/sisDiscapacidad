<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Seccion from '@/components/Seccion.vue';
import Footer from '@/components/Footer.vue';
import Mensajes from '@/components/Mensajes.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const page = usePage();
const mensajeState = ref({
    show: false,
    tipo: '',
    contenido: []
});

const form = useForm({
    archivo: null
});

const resultadosImportacion = ref(null);

// Lista de mensajes
const mensajes = ref([]);

// Mostrar mensaje
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(), // ID único
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

// Eliminar mensaje cuando hijo emite @close
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

const asignarArchivo = (event) => {
    const files = event.target.files;
    if (files.length > 0) {
        form.archivo = files[0];
    }
};

// referencia para el input
const archivoInput = ref(null);

const abrirSelectorArchivo = () => {
    if (archivoInput.value) {
        archivoInput.value.click();
    }
};

const limpiarArchivo = () => {
    form.archivo = null;
    if (archivoInput.value) {
        archivoInput.value.value = '';
    }
    // Limpiar resultados cuando se quita el archivo
    resultadosImportacion.value = null;
};

// Watcher para capturar los resultados de importación
watch(
    () => page.props.importResults,
    (newResults) => {
        if (newResults) {
            resultadosImportacion.value = newResults;

            if (newResults.type === 'success') {
                mostrarMensaje(
                    'correcto',
                    'Importación Exitosa',
                    `Se procesaron ${newResults.total} registros. ${newResults.insertados} insertados correctamente.`
                );
            } else if (newResults.type === 'error') {
                if (newResults.message) {
                    // Error general
                    mostrarMensaje(
                        'advertencia',
                        'Error de Importación',
                        newResults.message
                    );
                } else {
                    // Errores específicos
                    mostrarMensaje(
                        'advertencia',
                        'Importación con Errores',
                        `Se procesaron ${newResults.total} registros. ${newResults.insertados} insertados, ${newResults.errores?.length || 0} errores encontrados.`
                    );
                }
            }
        }
    }, { immediate: true, deep: true }
);

// También verificar al montar el componente
onMounted(() => {

    if (page.props.importResults) {
        resultadosImportacion.value = page.props.importResults;
    }
});

const importar = () => {
    if (!form.archivo) {
        mostrarMensaje('advertencia', 'Error', 'Por favor seleccione un archivo');
        return;
    }

    // Limpiar resultados anteriores
    resultadosImportacion.value = null;

    form.post(route('persona.importar.store'), {
        onStart: () => {
            mostrarMensaje('correcto', 'Procesando', 'Importando archivo...');
        },
        onSuccess: (page) => {
            console.log('Respuesta exitosa:', page.props); // Debug
            // Los resultados se capturarán automáticamente por el watcher
        },
        onError: (errors) => {
            console.log('Errores:', errors); // Debug
            mostrarMensaje('advertencia', 'Error', Object.values(errors)[0] || 'Error al procesar el archivo');
        },
        onFinish: () => {
            // Limpiar el archivo después de procesar
            form.archivo = null;
            if (archivoInput.value) {
                archivoInput.value.value = '';
            }
        }
    });
};

// Computed para estadísticas más legibles
const estadisticas = computed(() => {
    if (!resultadosImportacion.value) return null;

    const resultado = resultadosImportacion.value;
    return {
        total: resultado.total || 0,
        insertados: resultado.insertados || 0,
        actualizados: resultado.actualizados || 0,
        duplicados: resultado.duplicados || 0,
        errores: resultado.errores ?.length || 0,
        tieneErrores: (resultado.errores ?.length || 0) > 0,
        esExitoso: resultado.insertados === resultado.total,
        esParcial: resultado.insertados > 0 && resultado.insertados < resultado.total,
        esFallido: resultado.insertados === 0
    };
});

// Watcher para el mensaje (se ejecuta solo cuando cambia)
watch(resultadosImportacion, (nuevoResultado) => {
    if (nuevoResultado) {
        mostrarMensaje('info', 'Terminado', 'Verifique los resultados de importacion.');
    }
}, { immediate: false });

const onDragOver = (event) => {
    event.preventDefault();

};

const onDragLeave = (event) => {
    event.preventDefault();
};

const onDrop = (event) => {
    event.preventDefault();

    const files = event.dataTransfer.files;
    if (files.length > 0) {
        form.archivo = files[0];
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        resultadosImportacion.value = null;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col">
        <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido" @close="cerrarMensaje" />
        </div>
        <Header class="mb-0" />

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Importar Beneficiarios</h1>
                <Rutas label1="Inicio" label2="Beneficiario" label3="Importar Beneficiarios" />
            </div>
        </div>

        <main class="flex-1 overflow-x-hidden overflow-y-auto m-1 mt-0 ml-0 rounded">
            <div class="pt-1 mx-auto">

                <!-- Modal de resultados de importación -->
                <div v-if="estadisticas" class="fixed inset-0 bg-black/60 flex items-center justify-center z-40 px-4 py-2 overflow-y-auto backdrop-blur-sm">
                    <div class="relative w-full max-w-2xl max-h-[90vh] my-4 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200/30 dark:border-gray-700/30 transform transition-all duration-300 overflow-y-auto">
                        <!-- Modal Header -->
                        <div class="grid grid-cols-[1fr_auto] gap-6 px-4 pt-4 border-b border-gray-100 dark:border-gray-600/50  bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 sticky top-0 z-10">
                            <!-- Contenido principal -->
                            <div class="min-w-0">
                                <!-- Fila 1: Avatar (opcional), título y subtítulo -->
                                <div class="grid grid-cols-[auto_1fr] gap-4 items-center mb-2">
                                    <!-- Avatar / Ícono -->
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                                        <slot name="icon">
                                            <!-- Fallback si no mandas nada desde el padre -->
                                            <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5h7.586l-.293.293a1 1 0 0 0 1.414 1.414l2-2a1 1 0 0 0 0-1.414l-2-2a1 1 0 0 0-1.414 1.414l.293.293H4V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd" />
                                            </svg>
                                        </slot>
                                    </div>

                                    <!-- Título y subtítulo -->
                                    <div class="min-w-0">
                                        <h2 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight truncate">
                                            <slot name="label1">Resultados de Importación</slot>
                                        </h2>
                                        <p class="text-sm text-slate-500 dark:text-blue-300 truncate">
                                            <slot name="label2">Resumen del proceso</slot>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Acciones -->
                            <div class="flex items-start gap-3 flex-shrink-0">
                                <button type="button" @click="resultadosImportacion = null" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6">
                            <!-- Estado general de la importación -->
                            <div class="mb-5 p-4 rounded-lg border-2 shadow-sm" :class="{
                                        'bg-gradient-to-r from-emerald-50 to-green-50 border-emerald-200 dark:from-emerald-900/20 dark:to-green-900/20 dark:border-emerald-700/50': estadisticas.esExitoso,
                                        'bg-gradient-to-r from-amber-50 to-yellow-50 border-amber-200 dark:from-amber-900/20 dark:to-yellow-900/20 dark:border-amber-700/50': estadisticas.esParcial,
                                        'bg-gradient-to-r from-red-50 to-rose-50 border-red-200 dark:from-red-900/20 dark:to-rose-900/20 dark:border-red-700/50': estadisticas.esFallido
                                    }">
                                <div class="flex items-center mb-3">
                                    <div class="p-2 rounded-lg mr-3" :class="{
                                                'bg-emerald-100 dark:bg-emerald-800/50': estadisticas.esExitoso,
                                                'bg-amber-100 dark:bg-amber-800/50': estadisticas.esParcial,
                                                'bg-red-100 dark:bg-red-800/50': estadisticas.esFallido
                                            }">
                                        <i class="text-base" :class="{
                                                    'fas fa-check-circle text-emerald-600 dark:text-emerald-400': estadisticas.esExitoso,
                                                    'fas fa-exclamation-triangle text-amber-600 dark:text-amber-400': estadisticas.esParcial,
                                                    'fas fa-times-circle text-red-600 dark:text-red-400': estadisticas.esFallido
                                                }"></i>
                                    </div>
                                    <h3 class="text-base font-semibold" :class="{
                                                'text-emerald-800 dark:text-emerald-200': estadisticas.esExitoso,
                                                'text-amber-800 dark:text-amber-200': estadisticas.esParcial,
                                                'text-red-800 dark:text-red-200': estadisticas.esFallido
                                            }">
                                        <span v-if="estadisticas.esExitoso">Importación Completada Exitosamente</span>
                                        <span v-else-if="estadisticas.esParcial">Importación Completada con Observaciones</span>
                                        <span v-else>Importación Fallida</span>
                                    </h3>
                                </div>

                                <!-- Estadísticas en tarjetas -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Total</p>
                                            <i class="fas fa-database text-gray-400 text-xs"></i>
                                        </div>
                                        <p class="text-xl font-bold text-gray-800 dark:text-gray-200">
                                            {{ estadisticas.total }}
                                        </p>
                                    </div>

                                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Insertados</p>
                                            <i class="fas fa-check text-emerald-500 text-xs"></i>
                                        </div>
                                        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ estadisticas.insertados }}
                                        </p>
                                    </div>

                                    <!-- Después de la tarjeta "Insertados" -->
<div v-if="estadisticas.actualizados > 0" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
    <div class="flex items-center justify-between mb-1">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Actualizados</p>
        <i class="fas fa-sync text-amber-500 text-xs"></i>
    </div>
    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">
        {{ estadisticas.actualizados }}
    </p>
</div>

                                    <div v-if="estadisticas.duplicados > 0" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Duplicados</p>
                                            <i class="fas fa-copy text-blue-500 text-xs"></i>
                                        </div>
                                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                            {{ estadisticas.duplicados }}
                                        </p>
                                    </div>

                                    <div v-if="estadisticas.errores > 0" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm p-3 rounded-lg shadow-sm border border-white/50 dark:border-gray-700/50">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Errores</p>
                                            <i class="fas fa-exclamation-triangle text-red-500 text-xs"></i>
                                        </div>
                                        <p class="text-xl font-bold text-red-600 dark:text-red-400">
                                            {{ estadisticas.errores }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de errores detallada -->
                            <div v-if="estadisticas.tieneErrores && resultadosImportacion.errores && resultadosImportacion.errores.length > 0" class="mb-5">
                                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                                    <div class="bg-red-50 dark:bg-red-900/20 px-4 py-3 border-b border-red-200 dark:border-red-800/50">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-sm"></i>
                                                <h4 class="text-sm font-semibold text-red-800 dark:text-red-200">Errores Detallados</h4>
                                            </div>
                                            <span class="text-xs text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-800/50 px-2 py-1 rounded-full font-medium">
                                                {{ estadisticas.errores }} error{{ estadisticas.errores !== 1 ? 'es' : '' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="p-4 max-h-48 overflow-y-auto">
                                        <ul class="space-y-2">
                                            <li v-for="(error, index) in resultadosImportacion.errores" :key="index" class="flex items-start gap-2 p-2 bg-red-25 dark:bg-red-900/10 rounded border-l-3 border-red-300 dark:border-red-700">
                                                <i class="fas fa-times-circle text-red-500 text-xs mt-1 flex-shrink-0"></i>
                                                <span class="text-xs text-red-700 dark:text-red-300">{{ error }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Mensaje informativo (no de error) -->
                            <div v-if="resultadosImportacion.message" class="mb-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg overflow-hidden shadow-sm">
                                    <div class="p-4">
                                        <div class="flex items-start gap-3">
                                            <div class="p-2 bg-blue-100 dark:bg-blue-800/50 rounded-lg">
                                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-sm"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-1">Resumen del Proceso</h4>
                                                <p class="text-sm text-blue-700 dark:text-blue-300 leading-relaxed">{{ resultadosImportacion.message }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 rounded-b-xl bg-gradient-to-r from-slate-50 to-gray-50 dark:from-gray-800 dark:to-gray-700/50 sticky bottom-0">
                            <button @click="resultadosImportacion = null" class="px-6 py-2.5 text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-lg text-sm font-medium transition-all duration-200 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i class="fas fa-check mr-2"></i>
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Guía de importación -->
                <div class="mb-1 bg-blue-50 px-6 md:px-10 md:p-3 rounded-lg border border-blue-200 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                        <!-- Requisitos de formato -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl px-6 py-2 border border-blue-100 shadow-sm">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm2-2a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm0 3a1 1 0 1 0 0 2h3a1 1 0 1 0 0-2h-3Zm-6 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6Zm8 1v1h-2v-1h2Zm0 3h-2v1h2v-1Zm-4-3v1H9v-1h2Zm0 3H9v1h2v-1Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Requisitos del archivo</h3>
                            </div>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3 text-sm text-gray-700">
                                    <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span>Formatos aceptados: Excel (.xlsx, .xls) y CSV</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm text-gray-700">
                                    <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span>Tamaño máximo: 10MB</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm text-gray-700">
                                    <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span>La primera fila debe contener encabezados</span>
                                </li>
                                <li class="flex items-start gap-3 text-sm text-gray-700">
                                    <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                        <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span>Los CI con complemento (ej: 1234567-1A) se procesarán automáticamente</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Descarga de plantilla -->
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl px-6 py-2 border border-emerald-100 shadow-sm">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800">Plantilla de ejemplo</h3>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Descarga nuestra plantilla para asegurar que tu archivo tenga el formato correcto.
                            </p>
                            <a href="/plantilla/Plantilla_de_Importacion_UMADIS.xlsx" class="inline-flex items-center gap-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium px-6 py-3 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5" download="Plantilla_de_Importacion_UMADIS.xlsx">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path>
                                    <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Descargar plantilla Excel</span>
                                <svg class="w-4 h-4 opacity-75" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"></path>
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"></path>
                                </svg>
                            </a>
                            <div class="mt-3 text-xs text-gray-500 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Archivo seguro y verificado</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de columnas obligatorias -->
                    <!-- <div class="text-sm text-blue-800 border border-blue-500 rounded-lg p-4 bg-blue-50 shadow-md">
                        <div class="flex items-start gap-2 mb-3">
                            <i class="fas fa-table text-xs text-blue-600"></i>
                            <span class="font-semibold">Columnas obligatorias y opcionales:</span>
                        </div>
                        <div class="overflow-x-auto border border-blue-400 rounded-md bg-white shadow-sm">
                            <div class="grid grid-cols-5 text-xs font-medium text-center">

                                <div class="p-2 bg-blue-600 text-white border border-blue-500">Nombre</div>
                                <div class="p-2 bg-blue-600 text-white border border-blue-500">Apellido</div>
                                <div class="p-2 bg-blue-600 text-white border border-blue-500">Distrito</div>
                                <div class="p-2 bg-blue-600 text-white border border-blue-500">CI</div>
                                <div class="p-2 bg-blue-600 text-white border border-blue-500">CI Tutor</div>

                                <div class="p-2 bg-blue-100 border border-blue-300">Obligatorio</div>
                                <div class="p-2 bg-blue-100 border border-blue-300">Obligatorio</div>
                                <div class="p-2 bg-blue-100 border border-blue-300">Obligatorio</div>
                                <div class="p-2 bg-blue-100 border border-blue-300">Obligatorio</div>
                                <div class="p-2 bg-blue-100 border border-blue-300 text-gray-600">Opcional</div>
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- Área de carga -->
                <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg border border-gray-200 p-5">
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-2 transition-all duration-300 hover:border-blue-400 hover:bg-blue-50/20">
                        <div @click="abrirSelectorArchivo" class="cursor-pointer flex flex-col items-center">
                            <!-- Input de archivo -->
                            <input type="file" ref="archivoInput" accept=".xlsx, .xls, .csv" @change="asignarArchivo" class="hidden" />

                            <!-- Vista previa del archivo -->
                            <div v-if="form.archivo" class="w-full">
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-xl border border-blue-200 flex items-center justify-between shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path>
                                                <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">{{ form.archivo.name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ (form.archivo.size / 1024 / 1024).toFixed(2) }} MB
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="limpiarArchivo" class="w-8 h-8 bg-red-100 hover:bg-red-200 rounded-lg flex items-center justify-center transition-colors">
                                        <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Área de drop -->
                            <div v-if="!form.archivo" class="text-center cursor-pointer" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave" @drop.prevent="onDrop">
                                <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 011 1v1a1 1 0 01-1 1H4a1 1 0 01-1-1v-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-600 mb-2">
                                    Arrastre y suelte su archivo aquí o
                                    <button @click="abrirSelectorArchivo" class="text-blue-600 hover:text-blue-700 font-medium underline transition-colors">
                                        seleccione un archivo
                                    </button>
                                </p>
                                <p class="text-xs text-gray-500">
                                    Formatos soportados: XLSX, XLS o CSV
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex gap-3 mt-8">
                        <button type="button" @click="importar" :disabled="!form.archivo || form.processing" class="flex items-center gap-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium px-6 py-3 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                            <div v-if="form.processing" class="w-5 h-5">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <span>{{ form.processing ? 'Procesando archivo...' : 'Importar Archivo' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </main>
        <Footer />
    </div>
</div>
</template>
