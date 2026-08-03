<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import ModalResultadosImportacion from '@/components/ModalResultadosImportacion.vue';
import ModalImportaciones from '@/components/ModalImportaciones.vue';
import ModalEstados from '@/components/ModalEstados.vue';
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Busqueda from '@/components/Busqueda.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';
import Form from '@/components/Form/Form.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';
import AppLayout from '@/Layouts/AppLayout.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const tutor = computed(() => page.props.tutor);
const general = computed(() => page.props.general);
const filters = computed(() => page.props.filters || {});
const distrito = computed(() => page.props.distrito);
const discapacidad = computed(() => page.props.discapacidad);

// Opciones para selects
const distritosOptions = computed(() => {
    if (!distrito.value) return [];
    return distrito.value.map(d => ({
        text: d.distrito,
        value: d.distrito
    }));
});

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const showModalImportar = ref(false);
const showModalHabilitar = ref(false);
const showModalPendiente = ref(false);
const showModalRegistrado = ref(false);
const showModalCarnetEdit = ref(false);
const showModalCarnet = ref(false);
const ModalE = ref(false);
const openModal = ref(false);

// ============================================================================
// REFS - ESTADO DE FORMULARIOS
// ============================================================================
const formCreate = ref(false);
const formEdit = ref(false);
const formCreateTutor = ref(false);
const formCreateCarnet = ref(false);
const formCreateOption = ref(false);
const formCreateOptionDis = ref(false);
const createTutor = ref(false);
const esPropioTutor = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedItem = ref(null);
const selectedId = ref(null);
const tipoEstado = ref('');
const mensajes = ref([]);
const modalData = ref({ tipo: null, registradoId: null });

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

// ============================================================================
// REFS - IMPORTACIÓN
// ============================================================================
const resultadoImportacion = ref(null);
const mostrarModalResultados = ref(false);
const mostrarModal = ref(false);
const importando = ref(false);

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - TUTORES
// ============================================================================
const tutorFields = computed(() => [
    {
        typeInput: 'propio_check',
        name: 'es_propio',
        label: '',
        hidden: selectedItem.value?.ya_es_propio ?? false,
        onPropioChange: (val) => { esPropioTutor.value = !!val; }
    },
    {
        typeCi: 'ci',
        typeInput: 'cedula',
        name: 'ci_tutor',
        label: 'C.I.',
        type: 'number',
        required: !esPropioTutor.value,
        placeholder: 'la cédula de identidad',
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        range: 10,
        nameStyle: true,
        autofocus: true,
        opaco: esPropioTutor.value
    },
    {
        typeCi: 'ci',
        typeInput: 'comple',
        name: 'complemento_tutor',
        label: 'Complemento C.I.',
        type: 'complemento',
        required: false,
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        hidden: true,
        opaco: esPropioTutor.value
    },
    {
        typeInput: 'text',
        name: 'nombre_tutor',
        label: 'Nombre',
        type: 'text',
        required: !esPropioTutor.value,
        placeholder: 'el nombre',
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        nameStyle: true,
        range: 30,
        opaco: esPropioTutor.value
    },
    {
        typeInput: 'text',
        name: 'apellido_tutor',
        label: 'Apellidos',
        type: 'text',
        required: !esPropioTutor.value,
        placeholder: 'el apellido',
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        nameStyle: true,
        range: 30,
        opaco: esPropioTutor.value
    },
    {
        typeInput: 'text',
        name: 'telefono',
        label: 'Celular',
        type: 'number',
        required: false,
        placeholder: 'el número de teléfono',
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        range: 10,
        opaco: esPropioTutor.value
    },
    {
        typeInput: 'text',
        name: 'email',
        label: 'Correo Electronico',
        type: 'email',
        required: false,
        placeholder: 'el correo electronico',
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        nameStyle: false,
        range: 40,
        opaco: esPropioTutor.value
    },
    {
        typeInput: 'direccion',
        name: 'direccion',
        label: 'Dirección',
        required: false,
        placeholder: 'la dirección',
        readonly: esPropioTutor.value,
        disabled: esPropioTutor.value,
        nameStyle: false,
        range: 200,
        opaco: esPropioTutor.value
    }
]);

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - PERSONAS
// ============================================================================
const personaFieldsEdit = [
    {
        typeInput: 'text',
        name: 'nombre_persona',
        label: 'Nombre',
        type: 'text',
        required: true,
        placeholder: 'el nombre',
        nameStyle: true,
        readonly: false
    },
    {
        typeInput: 'text',
        name: 'apellido_persona',
        label: 'Apellidos',
        type: 'text',
        required: true,
        placeholder: 'el apellido',
        nameStyle: true,
        readonly: false
    },
    {
        typeInput: 'select',
        name: 'distrito',
        label: 'Distrito',
        placeholder: 'el distrito',
        options: distritosOptions.value,
        required: true,
        readonly: false,
        add: true
    },
    {
        typeCi: 'ci',
        typeInput: 'cedula',
        name: 'ci_persona',
        label: 'C.I. Beneficiario',
        type: 'number',
        required: true,
        placeholder: 'la cédula de identidad',
        readonly: false
    },
    {
        typeCi: 'ci',
        typeInput: 'comple',
        name: 'complemento',
        label: 'Complemento C.I.',
        type: 'complemento',
        required: false,
        readonly: false,
        hidden: true
    },
    {
        typeInput: 'text',
        name: 'fecha_nacimiento',
        label: 'Fecha de Nacimiento',
        type: 'date',
        placeholder: 'la fecha de nacimiento',
        required: true
    },
    {
        typeInput: 'text',
        name: 'observacion_persona',
        label: 'Observaciones',
        type: 'text',
        required: false,
        placeholder: 'observaciones adicionales',
        nameStyle: false,
        readonly: false
    },
    {
        typeInput: 'textarea',
        name: 'documento_respaldo',
        label: 'Documento de respaldo',
        type: 'text',
        required: false,
        placeholder: 'los documentos de respaldo',
        nameStyle: false,
        readonly: false
    }
];

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - OPCIONES (DISTRITO Y DISCAPACIDAD)
// ============================================================================
const DistritoFields = [
    {
        typeInput: 'text',
        name: 'distrito',
        label: 'Distrito',
        type: 'distrito',
        required: true,
        placeholder: 'el distrito',
        nameStyle: 'uppercase',
        readonly: false
    }
];

const DiscapacidadFields = [
    {
        typeInput: 'text',
        name: 'discapacidad',
        label: 'Discapacidad',
        type: 'discapacidad',
        required: true,
        placeholder: 'la discapacidad',
        nameStyle: 'uppercase',
        readonly: false
    }
];

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Nº', field: 'numero', headerClass: 'text-center px-2', cellClass: 'whitespace-nowrap' },
    { label: 'C.I.', field: 'ci_persona', headerClass: 'px-2 whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Apellidos + Nombres', field: 'nombre_completo', headerClass: 'px-1 whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Tutor(a)', field: 'tutor', headerClass: 'px-1', cellClass: '' },
    { label: 'Documento de Respaldo', field: 'documento_respaldo', headerClass: 'px-1', cellClass: '' },
    { label: 'Estado', field: 'tipo_registro', headerClass: 'text-center px-1', cellClass: '' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center px-3', cellClass: '' }
];

// ============================================================================
// CONFIGURACIÓN DE IMPORTACIÓN
// ============================================================================
const configPlanillaGeneral = {
    titulo: 'Importar planilla general',
    subtitulo: 'Carga la planilla descargada desde la plataforma Eustaquio Moto Méndez.',
    icono: 'upload',
    columnasTabla: [
        { nombre: 'Documento de Identidad', obligatorio: true },
        { nombre: 'Apellidos + Nombres', obligatorio: true },
        { nombre: 'Tutor(a)', obligatorio: false },
        { nombre: 'Documento de Respaldo', obligatorio: false }
    ],
    nombrePlantilla: 'plantilla_general.xlsx',
    urlPlantilla: '/plantilla/PlantillaGeneral.xlsx',
    textoBotonImportar: 'Importar Excel'
};

// ============================================================================
// WATCHERS
// ============================================================================
watch(() => page.props.flash, (newFlash) => { }, { deep: true, immediate: true });

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Separa un nombre completo en nombre y apellido
 * @param {string} nombreCompleto - Nombre completo a separar
 * @returns {Object} Objeto con propiedades nombre y apellido
 */
const separarNombre = (nombreCompleto) => {
    if (!nombreCompleto) return { nombre: '', apellido: '' };
    if (nombreCompleto.trim().toLowerCase() === 'propio') return { nombre: '', apellido: '' };

    const partes = nombreCompleto.trim().split(' ');
    const total = partes.length;

    if (total <= 1) {
        return { nombre: nombreCompleto, apellido: '' };
    }

    if (total === 2) {
        return { nombre: partes[1], apellido: partes[0] };
    }

    if (total === 3) {
        const ultimaPalabra = partes[2];

        if (ultimaPalabra.length <= 3) {
            return {
                nombre: partes[1] + ' ' + partes[2],
                apellido: partes[0]
            };
        }

        return {
            nombre: partes[2],
            apellido: partes.slice(0, 2).join(' ')
        };
    }

    const ultimaPalabra = partes[partes.length - 1];

    if (ultimaPalabra.length <= 3) {
        return {
            nombre: partes.slice(-2).join(' '),
            apellido: partes.slice(0, -2).join(' ').slice(0, 2)
        };
    }

    return {
        nombre: partes.slice(-2).join(' '),
        apellido: partes.slice(0, 2).join(' ')
    };
};

// ============================================================================
// FUNCIONES - MENSAJES
// ============================================================================

/**
 * Muestra un mensaje en la interfaz
 * @param {string} tipo - Tipo de mensaje (error, correcto, info, advertencia)
 * @param {string} titulo - Título del mensaje
 * @param {string} texto - Contenido del mensaje
 */
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

/**
 * Cierra un mensaje específico por su ID
 * @param {number} id - ID del mensaje a cerrar
 */
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// Mensajes predefinidos
const sinDatos = () => {
    mostrarMensaje('error', 'Error de validación', 'Por favor, complete todos los campos obligatorios antes de enviar el formulario.');
};

const mensajeExisteDisca = () => {
    mostrarMensaje('info', 'Registro existente', 'La discapacidad ingresada ya está registrado.');
};

const mensajeExisteDis = () => {
    mostrarMensaje('info', 'Registro existente', 'El distrito ingresado ya está registrado.');
};

const smsTutorEncontrado = () => {
    mostrarMensaje('info', 'Registro encontrado', 'El tutor ya está registrado, solo presiona siguiente.');
};

// ============================================================================
// FUNCIONES - TOOLTIP
// ============================================================================

/**
 * Muestra el tooltip posicionado encima del botón indicado
 * @param {string} text - Texto del tooltip
 * @param {string} buttonId - ID del botón donde mostrar el tooltip
 */
const showTooltip = (text, buttonId) => {
    const button = document.getElementById(buttonId);
    if (button) {
        const rect = button.getBoundingClientRect();
        tooltipStyle.value = {
            left: `${rect.left + rect.width / 2}px`,
            top: `${rect.top - 10}px`,
            transform: 'translateX(-50%) translateY(-100%)'
        };
    }
    tooltipText.value = text;
    showTooltipFlag.value = true;
};

/**
 * Oculta el tooltip activo
 */
const hideTooltip = () => {
    showTooltipFlag.value = false;
    tooltipText.value = '';
};

// ============================================================================
// FUNCIONES - FORMULARIOS EDICIÓN
// ============================================================================

/**
 * Maneja la edición exitosa de un beneficiario pendiente
 */
const handleEdit = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    formEdit.value = false;
};

/**
 * Maneja la adición exitosa de distrito o discapacidad
 */
const handleAddDis = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    formCreateOption.value = false;
    formCreateOptionDis.value = false;
    router.visit(route('persona.index'));
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Abre el formulario de creación de opción y cierra el formulario principal
 */
const openOption = () => {
    formCreate.value = false;
    formCreateOption.value = true;
    router.reload();
};

/**
 * Cierra el modal de estados
 */
const closeModal = () => {
    ModalE.value = false;
};

/**
 * Cierra todos los formularios y modales activos
 */
const closeForm = () => {
    esPropioTutor.value = false;
    showModalCarnetEdit.value = false;
    formCreateOption.value = false;
    formCreateOptionDis.value = false;
    formCreateCarnet.value = false;
    showModalCarnet.value = false;
    formCreateTutor.value = false;
    createTutor.value = false;
    formCreate.value = false;
    openModal.value = false;
    formEdit.value = false;
};

/**
 * Abre el flujo de registro pendiente: asigna tutor o va directo a edición según corresponda
 * @param {number|string} registradoId - ID del beneficiario pendiente
 */
const openPendingModal = (registradoId) => {
    showModalRegistrado.value = false;

    const dataGeneral = general.value.data.find(p => p.id_persona === registradoId);

    if (!dataGeneral) {
        mostrarMensaje('error', 'Error', 'No se encontró el registro');
        return;
    }

    const tutorSeparado = separarNombre(dataGeneral.tutor_nombre);

    selectedItem.value = {
        nombre_tutor: tutorSeparado.nombre,
        apellido_tutor: tutorSeparado.apellido,
        tiene_tutor: !!dataGeneral.tutor_nombre
    };

    selectedId.value = registradoId;

    // Validar si tiene tutor asignado
    if (!dataGeneral.id_tutor) {
        formCreateTutor.value = true;
    } else {
        openModalEditBeneficiary();
    }
};

/**
 * Omite la asignación de tutor y avanza directo a la edición del beneficiario
 */
const omitir = () => {
    esPropioTutor.value = false;
    mostrarMensaje('advertencia', 'Registro sin tutor', 'El beneficiario será registrado sin tutor asignado.');
    formCreateTutor.value = false;
    openModalEditBeneficiary();
};

/**
 * Abre el formulario de edición con los datos del beneficiario seleccionado
 */
const openModalEditBeneficiary = () => {
    formCreateTutor.value = false;
    esPropioTutor.value = false;

    const dataGeneral = general.value.data.find(p => p.id_persona === selectedId.value);

    if (!dataGeneral) {
        mostrarMensaje('error', 'Error', 'No se encontró el beneficiario');
        return;
    }

    const nombreSeparado = separarNombre(dataGeneral.nombre_completo);

    selectedItem.value = {
        id_persona: dataGeneral.id_persona,
        ci_persona: dataGeneral.ci_persona,
        complemento: dataGeneral.complemento,
        nombre_persona: nombreSeparado.nombre,
        apellido_persona: nombreSeparado.apellido,
        distrito: dataGeneral.distrito,
        fecha_nacimiento: dataGeneral.fecha_nacimiento,
        observacion_persona: dataGeneral.observacion_persona,
        documento_respaldo: dataGeneral.documento_respaldo,
    };

    formEdit.value = true;
};

/**
 * Abre el modal de beneficiario con el tipo e ID correspondiente
 * @param {number|string} id - ID del beneficiario
 * @param {string} tipo - Tipo de registro (pendiente, registrado, etc.)
 */
const openModalBeneficiary = (id, tipo) => {
    modalData.value = {
        tipo: tipo,
        registradoId: id
    };
    showModalRegistrado.value = true;
};

/**
 * Cierra todos los modales de habilitación y limpia el item seleccionado
 */
const cerrarModalesHabilitar = () => {
    showModalImportar.value = false;
    showModalHabilitar.value = false;
    showModalPendiente.value = false;
    showModalRegistrado.value = false;
    selectedItem.value = null;
};

// ============================================================================
// FUNCIONES - IMPORTACIÓN
// ============================================================================

/**
 * Cierra el modal de resultados y limpia los datos de importación
 */
const cerrarModalResultados = () => {
    mostrarModalResultados.value = false;
    resultadoImportacion.value = null;
};

/**
 * Maneja la importación de la planilla general desde un archivo Excel
 * @param {File} archivo - Archivo Excel a importar
 * @param {Function} limpiarArchivo - Función para limpiar el input de archivo
 */
const handleImportar = (archivo, limpiarArchivo) => {
    const formData = new FormData();
    formData.append('archivo', archivo);

    importando.value = true;

    router.post(route('general.importar.store'), formData, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash && page.props.flash.importResults) {
                resultadoImportacion.value = page.props.flash.importResults;
                mostrarModal.value = false;
                mostrarModalResultados.value = true;
            }
            limpiarArchivo();
        },
        onError: () => {
            mostrarMensaje('error', 'Error', 'Hubo un problema al importar');
        },
        onFinish: () => {
            importando.value = false;
        }
    });
};

/**
 * Elimina la sesión del tutor almacenada en el controlador
 */
const clearTutorSession = () => {
    axios.delete(route('persona.clearTutorSession'));
};

/**
 * Descarga la plantilla de importación de la planilla general
 * @param {string} nombrePlantilla - Nombre del archivo a descargar
 */
const handleDescargarPlantilla = (nombrePlantilla) => {
    const link = document.createElement('a');
    link.href = configPlanillaGeneral.urlPlantilla;
    link.download = nombrePlantilla;
    link.click();
};
</script>

<template>
    <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Registro General</h1>
            <Rutas label1="Inicio" label3="Registro General" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- BARRA DE HERRAMIENTAS -->
        <!-- ============================================================================ -->
        <div class="flex justify-between p-4 pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
            <Busqueda :initial-value="filters.buscador" name="registro" only="general" :data="general"
                ruta-busqueda="general.index" />
            <div class="pr-3">
                <Button v-if="can('importar-general')" id="btn-importar" @click="mostrarModal = true"
                    @mouseenter="showTooltip('Importar', 'btn-importar')" @mouseleave="hideTooltip"
                    :style="'px-3 py-3 pb-2 rounded-full border-none'"
                    class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                    <!-- Efecto de fondo desde el centro -->
                    <span
                        class="absolute inset-0 bg-gray-600 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                    <!-- Icono -->
                    <span class="relative z-10">
                        <Icon :icon-button="true" name="fileImport"
                            class-name="text-gray-700 group-hover:text-white transition-colors duration-500" />
                    </span>
                </Button>
            </div>
            <!-- Tooltip -->
            <div v-if="showTooltipFlag" ref="tooltipRef"
                class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                :style="tooltipStyle">
                {{ tooltipText }}
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- TABLA DE DATOS -->
        <!-- ============================================================================ -->
        <DataTable :data="general.data" :columns="tableColumns" row-key="id_persona"
            empty-message="No se encontraron datos. ¡Agregue beneficiarios para continuar!">
            <!-- Slot personalizado para cada fila -->
            <template #row="{ item, index }">
                <!-- Columna: Nº -->
                <td class="px-1 py-1.5 whitespace-nowrap">
                    <div class="font-medium text-gray-900 text-center dark:text-gray-100">
                        {{ general.from + index }}
                    </div>
                </td>

                <!-- Columna: Documento de Identidad -->
                <td class="px-2 py-1.5 whitespace-nowrap">
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        {{ item.ci_persona }}
                        <span v-if="item.complemento !== null">-{{ item.complemento }}</span>
                    </div>
                </td>

                <!-- Columna: Apellidos + Nombres -->
                <td class="pl-3 py-1 whitespace-nowrap">
                    <div class="font-medium text-gray-900 dark:text-gray-100 uppercase">
                        <p v-if="item.nombre_persona">{{ item.apellido_persona }} {{ item.nombre_persona }}</p>
                        <p v-else> {{ item.nombre_completo }}</p>
                    </div>
                </td>

                <!-- Columna: Tutor(a) -->
                <td class="px-1 py-1.5">
                    <div class="dark:text-gray-300">
                        <div v-if="item.tutor_nombre === 'propio'" class="text-blue-500 italic font-bold">
                            <!-- Si tiene relación con tutor-->
                            Tutor Propio
                        </div>
                        <div v-else class="text-gray-700 uppercase whitespace-nowrap">
                            <!-- Si tiene relación con tutor-->
                            <span v-if="item.tutor">
                                {{ item.tutor.nombre_completo }}
                            </span>
                            <!-- Si no tiene relación pero tiene tutor_nombre en persona-->
                            <span v-else-if="item.tutor_nombre">
                                {{ item.tutor_nombre }}
                            </span>
                            <!-- Si no tiene ninguno-->
                            <span v-else class="block text-red-400 text-center italic capitalize text-xs">
                                Sin datos
                            </span>
                        </div>
                    </div>
                </td>

                <!-- Columna: Documento de Respaldo -->
                <td class="px-0 py-1.5">
                    <div class="text-gray-700 dark:text-gray-300 truncate max-w-xs" :title="item.documento_respaldo">
                        <span v-if="item.documento_respaldo">{{ item.documento_respaldo }}</span>
                        <span v-else class="block text-red-400 text-center italic text-xs">Sin datos</span>
                    </div>
                </td>

                <!-- Columna: Estado -->
                <td class="px-2 py-1.5">
                    <div class="inline-flex items-center gap-2">
                        <!-- PRIORIDAD: Estados reales (estado_actual.estado) -->
                        <span v-if="item.estado_actual?.estado === 'baja_temporal'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-gray-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                            <span class="text-xs font-medium text-gray-700">Baja Temporal</span>
                        </span>

                        <span v-else-if="item.estado_actual?.estado === 'baja_definitiva'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-gray-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-xs font-medium text-gray-700">Baja Definitiva</span>
                        </span>

                        <!-- SI NO tiene baja_temporal ni baja_definitiva, mostrar tipo_registro -->
                        <span v-else-if="item.tipo_registro === 'registrado'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-gray-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span class="text-xs font-medium text-gray-700">Registrado</span>
                        </span>

                        <span v-else-if="item.tipo_registro === 'beneficiario'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-gray-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-xs font-medium text-gray-700">Beneficiario</span>
                        </span>

                        <span v-else-if="item.tipo_registro === 'pendiente'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-gray-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-xs font-medium text-gray-700">Pendiente</span>
                        </span>

                        <span v-else
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-gray-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                            <span class="text-xs font-medium text-gray-500">Sin Estado</span>
                        </span>
                    </div>
                </td>

                <!-- Columna: Acciones -->
                <td class="px-3 py-1.5">
                    <div class="flex justify-center items-center gap-1.5">
                        <!-- PRIORIDAD: Estados reales (estado_actual.estado) - CLICKEABLES -->
                        <Icon v-if="item.estado_actual?.estado === 'baja_temporal'"
                            @click.prevent="openModalBeneficiary(null, 'baja_temporal')" name="timeCircle"
                            class-name="text-gray-600" title="Baja Temporal" />

                        <Icon v-else-if="item.estado_actual?.estado === 'baja_definitiva'"
                            @click.prevent="openModalBeneficiary(null, 'baja_definitiva')" name="circleMinus"
                            class-name="text-gray-600" title="Baja Definitiva" />

                        <!-- SI NO tiene baja_temporal ni baja_definitiva, mostrar tipo_registro - CLICKEABLES -->
                        <Icon v-else-if="item.tipo_registro === 'registrado' && can('agregar-general')"
                            @click.prevent="openModalBeneficiary(item.id_persona, 'registrado')" name="userAdd"
                            class-name="text-gray-600" title="Registrado - Habilitar" />

                        <Icon v-else-if="item.tipo_registro === 'beneficiario'"
                            @click.prevent="openModalBeneficiary(null, 'beneficiario')" name="checkCircle"
                            class-name="text-gray-600" title="Beneficiario" />

                        <Icon v-else-if="item.tipo_registro === 'pendiente' && can('pendiente-general')"
                            @click.prevent="openPendingModal(item.id_persona)" name="exclamationCircle"
                            class-name="text-gray-600" :ripple="true" ripple-color="bg-gray-700"
                            title="Pendiente - Registrar" />
                    </div>
                </td>
            </template>

            <!-- Slot personalizado para estado vacío (opcional) -->
            <template #empty>
                <div class="flex flex-col items-center justify-center py-12 px-4">
                    <!-- Icono -->
                    <div class="mb-6">
                        <Icon :icon-button="true" name="user" class-name="text-gray-400 dark:text-gray-500" :size="64"
                            :height="64" />
                    </div>

                    <!-- Textos -->
                    <div class="text-center space-y-2 max-w-md">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            No se encontraron datos
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Cargue datos para comenzar a visualizar información.
                        </p>
                    </div>
                </div>
            </template>
        </DataTable>

        <!-- ============================================================================ -->
        <!-- FOOTER Y PAGINACIÓN -->
        <!-- ============================================================================ -->
        <div :class="general.data.length <= 15 ? 'mt-0.5' : 'mt-0'">
            <Paginacion v-if="general?.last_page > 1" :links="general.links" :from="general.from" :to="general.to"
                :total="general.total" />
            <Footer />
        </div>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - BENEFICIARIOS -->
        <!-- ============================================================================ -->

        <!-- Formulario: Editar Beneficiario -->
        <Transition name="fade">
            <Form v-if="formEdit" :fields="personaFieldsEdit" :distritos="distrito" :idFor="selectedId"
                :existing-data="selectedItem || {}" :edit-mode="true" submit-route="general.editRegistro"
                @add="handleEdit" @openFormOption="openOption" @sinDatos="sinDatos"
                @cancel="() => { clearTutorSession(); closeForm(); }"
                @close="() => { clearTutorSession(); closeForm(); }">
                <template #icon>
                    <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M5 8a4 4 0 1 1 7.796 1.263l-2.533 2.534A4 4 0 0 1 5 8Zm4.06 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2.172a2.999 2.999 0 0 1-.114-1.588l.674-3.372a3 3 0 0 1 .82-1.533L9.06 13Zm9.032-5a2.907 2.907 0 0 0-2.056.852L9.967 14.92a1 1 0 0 0-.273.51l-.675 3.373a1 1 0 0 0 1.177 1.177l3.372-.675a1 1 0 0 0 .511-.273l6.07-6.07a2.91 2.91 0 0 0-.944-4.742A2.907 2.907 0 0 0 18.092 8Z"
                            clip-rule="evenodd" />
                    </svg>
                </template>
                <template #label1>
                    {{ selectedItem?.tipo_registro === 'pendiente' ? 'Completar registro' :
                        'Registrar como beneficiario' }}
                </template>
                <template #label2>
                    {{ selectedItem?.tipo_registro === 'pendiente' ? 'Complete los datos faltantes del registro' :
                        'Complete los datos para ser beneficiario' }}
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - TUTORES -->
        <!-- ============================================================================ -->

        <!-- Formulario: Crear Tutor -->
        <Transition name="fade">
            <Form v-if="formCreateTutor" :fields="tutorFields" :botonOmitir="true" :idFor="selectedId" :tutores="tutor"
                :existing-data="selectedItem || {}" submit-route="tutor.store" @add="openModalEditBeneficiary"
                @tutorEncontradoSms="smsTutorEncontrado" @sinDatos="sinDatos" @cancel="closeForm" @omitir="omitir">
                <template #icon>
                    <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                            clip-rule="evenodd" />
                    </svg>
                </template>
                <template #label1>
                    Agregar Tutor
                </template>
                <template #label2>
                    Registre un nuevo tutor
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - OPCIONES (DISTRITO Y DISCAPACIDAD) -->
        <!-- ============================================================================ -->

        <!-- Formulario: Agregar Distrito -->
        <Transition name="fade">
            <Form v-if="formCreateOption" :distritos="distrito" :fields="DistritoFields" submit-route="dropdown.store"
                @add="handleAddDis" @encontrado="mensajeExisteDis" @cancel="closeForm" @close="closeForm">
                <template #icon>
                    <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                            clip-rule="evenodd" />
                    </svg>
                </template>
                <template #label1>
                    Agregar Distrito
                </template>
                <template #label2>
                    Registre un nuevo distrito
                </template>
            </Form>
        </Transition>

        <!-- Formulario: Agregar Discapacidad -->
        <Transition name="fade">
            <Form v-if="formCreateOptionDis" :discapacidad="discapacidad" :fields="DiscapacidadFields"
                submit-route="dropdown.addDis" @add="handleAddDis" @encontrado="mensajeExisteDisca" @cancel="closeForm"
                @close="closeForm">
                <template #icon>
                    <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                            clip-rule="evenodd" />
                    </svg>
                </template>
                <template #label1>
                    Agregar Discapacidad
                </template>
                <template #label2>
                    Registre una nueva discapacidad
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- MODALES -->
        <!-- ============================================================================ -->

        <!-- Modal: Estados -->
        <Transition name="fade">
            <ModalEstados v-if="ModalE" :tipo="tipoEstado" @close="closeModal" />
        </Transition>

        <!-- Modal: Ya es beneficiario -->
        <Transition name="fade">
            <ModalEstados v-if="showModalRegistrado" :tipo="modalData.tipo" :registrado-id="modalData.registradoId"
                @continuar="openPendingModal" @close="cerrarModalesHabilitar" />
        </Transition>

        <!-- Modal: Importaciones -->
        <Transition name="fade">
            <ModalImportaciones v-if="mostrarModal" :titulo="configPlanillaGeneral.titulo"
                :subtitulo="configPlanillaGeneral.subtitulo" :icono="configPlanillaGeneral.icono"
                :columnasTabla="configPlanillaGeneral.columnasTabla"
                :nombrePlantilla="configPlanillaGeneral.nombrePlantilla"
                :urlPlantilla="configPlanillaGeneral.urlPlantilla"
                :textoBotonImportar="configPlanillaGeneral.textoBotonImportar" :importando="importando"
                @importar="handleImportar" @descargar-plantilla="handleDescargarPlantilla"
                @close="mostrarModal = false" />
        </Transition>

        <!-- Modal: Resultado Importaciones -->
        <Transition name="fade">
            <ModalResultadosImportacion v-model="mostrarModalResultados"
                :resultadoImportacion="resultadoImportacion || {}" @update:modelValue="cerrarModalResultados" />
        </Transition>
    </AppLayout>
</template>
