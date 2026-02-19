<script setup>
// ============ INICIO IMPORTS ============ //
import { computed, ref, watch } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { can } from '@/lib/can';

import ModalErrorFormato from '@/components/ModalErrorFormato.vue';
import ModalTutor from '@/components/ModalTutor.vue';
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Mensajes from '@/components/Mensajes.vue';
import Sidebar from '@/components/Sidebar.vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Form from '@/components/Form/Form.vue';
import Rutas from '@/components/Rutas.vue';
import ModalEstadoBene from '@/components/ModalEstadoBene.vue';
import ModalEstados from '@/components/ModalEstados.vue';
import ModalImportaciones from '@/components/ModalImportaciones.vue';
import ModalResultadosImportacion from '@/components/ModalResultadosImportacion.vue';
import Button from '@/components/Button.vue';
import Icon from '@/components/Icon.vue';
import DataTable from '@/components/DataTable.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS Y COMPUTED ============ //
const page = usePage();
const general = computed(() => page.props.general);
const filters = computed(() => page.props.filters || {});
const tutor = computed(() => page.props.tutor);
const distrito = computed(() => page.props.distrito);
const discapacidad = computed(() => page.props.discapacidad);

const distritosOptions = computed(() => {
    if (!distrito.value) return [];
    return distrito.value.map(d => ({
        text: d.distrito,
        value: d.distrito
    }));
});
// ============ FIN PROPS Y COMPUTED ============ //

// ============ INICIO REFS - MODALES ============ //
const showModalImportar = ref(false);
const showModalHabilitar = ref(false);
const showModalPendiente = ref(false);
const showModalRegistrado = ref(false);
const showModalCarnetEdit = ref(false);
const showModal = ref(false);
const showModalEstado = ref(false);
const showModalCarnet = ref(false);
const ModalE = ref(false);
const openModal = ref(false);
// ============ FIN REFS - MODALES ============ //

// ============ INICIO REFS - FORMULARIOS ============ //
const formCreate = ref(false);
const formEdit = ref(false);
const formCreateTutor = ref(false);
const formCreateCarnet = ref(false);
const formCreateEstado = ref('');
const formCreateOption = ref(false);
const formCreateOptionDis = ref(false);
const createTutor = ref(false);
// ============ FIN REFS - FORMULARIOS ============ //

// ============ INICIO REFS - IMPORTACIÓN ============ //

const resultadoImportacion = ref(null);
const mostrarModalResultados = ref(false);
const errorFormatoData = ref(null);
const mostrarInsertados = ref(false);
const mostrarDuplicados = ref(false);
const mostrarErrores = ref(false);
// ============ FIN REFS - IMPORTACIÓN ============ //

// ============ INICIO REFS - DATOS ============ //
const selectedItem = ref(null);
const selectedId = ref(null);
const tipoEstado = ref('');
const selectedEstadoData = ref(null);
const mensajes = ref([]);
const modalData = ref({
    tipo: null,
    registradoId: null
});
// ============ FIN REFS - DATOS ============ //

// ============ INICIO CONFIGURACIÓN DE CAMPOS ============ //
const tutorFields = [{
    typeCi: 'ci',
    typeInput: 'cedula',
    name: 'ci_tutor',
    label: 'C.I.',
    type: 'number',
    required: false,
    placeholder: 'la cédula de identidad',
    readonly: false,
    range: 10,
},
{
    typeCi: 'ci',
    typeInput: 'comple',
    name: 'complemento_tutor',
    label: 'Complemento C.I.',
    type: 'complemento',
    required: false,
    readonly: false,
    hidden: true
},
{
    typeInput: 'text',
    name: 'nombre_tutor',
    label: 'Nombre',
    type: 'text',
    required: true,
    placeholder: 'el nombre',
    readonly: false,
    nameStyle: true,
    range: 30,
},
{
    typeInput: 'text',
    name: 'apellido_tutor',
    label: 'Apellidos',
    type: 'text',
    required: true,
    placeholder: 'el apellido',
    readonly: false,
    nameStyle: true,
    range: 30,
},
{
    typeInput: 'text',
    name: 'telefono',
    label: 'Celular',
    type: 'number',
    required: false,
    placeholder: 'el número de teléfono',
    readonly: false,
    range: 10,
},
{
    typeInput: 'text',
    name: 'email',
    label: 'Correo Electronico',
    type: 'email',
    required: false,
    placeholder: 'el correo electronico',
    readonly: false,
    nameStyle: false,
    range: 40,
},
{
    typeInput: 'text',
    name: 'direccion',
    label: 'Dirección',
    type: 'text',
    required: false,
    placeholder: 'la dirección',
    readonly: false,
    nameStyle: false,
    range: 49,
}
];

const personaFieldsEdit = [{
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

const DistritoFields = [{
    typeInput: 'text',
    name: 'distrito',
    label: 'Distrito',
    type: 'distrito',
    required: true,
    placeholder: 'el distrito',
    nameStyle: true,
    readonly: false
}];

const DiscapacidadFields = [{
    typeInput: 'text',
    name: 'discapacidad',
    label: 'Discapacidad',
    type: 'discapacidad',
    required: true,
    placeholder: 'la discapacidad',
    nameStyle: true,
    readonly: false
}];
// ============ FIN CONFIGURACIÓN DE CAMPOS ============ //

// ============ INICIO WATCHERS ============ //
watch(() => page.props.flash, (newFlash) => { }, { deep: true, immediate: true });
// ============ FIN WATCHERS ============ //

// ============ INICIO FUNCIONES UTILIDADES ============ //
const separarNombre = (nombreCompleto) => {
    if (!nombreCompleto) return { nombre: '', apellido: '' };

    const partes = nombreCompleto.trim().split(' ');
    const total = partes.length;

    // Si solo hay una palabra
    if (total <= 1) {
        return { nombre: nombreCompleto, apellido: '' };
    }

    // Si hay exactamente 2 palabras
    if (total === 2) {
        return { nombre: partes[1], apellido: partes[0] };
    }

    // Si hay 3 palabras
    if (total === 3) {
        const ultimaPalabra = partes[2];

        // Si la última palabra es corta (<=3), probablemente sea sufijo (Jr, III, etc)
        if (ultimaPalabra.length <= 3) {
            return {
                nombre: partes[1] + ' ' + partes[2],
                apellido: partes[0]
            };
        }

        // Caso normal: 1 nombre, 2 apellidos
        return {
            nombre: partes[2],
            apellido: partes.slice(0, 2).join(' ')
        };
    }

    // Si hay 4 o más palabras
    const ultimaPalabra = partes[partes.length - 1];

    // Si la última palabra es corta (<=3), probablemente sea sufijo
    if (ultimaPalabra.length <= 3) {
        return {
            nombre: partes.slice(-2).join(' '), // últimas 2 palabras
            apellido: partes.slice(0, -2).join(' ').slice(0, 2) // máximo 2 apellidos
        };
    }

    // Caso normal: tomar las últimas 2 palabras como nombre y las primeras 2 como apellido
    return {
        nombre: partes.slice(-2).join(' '), // últimas 2 palabras como nombre
        apellido: partes.slice(0, 2).join(' ') // primeras 2 palabras como apellido
    };
};
// ============ FIN FUNCIONES UTILIDADES ============ //

// ============ INICIO FUNCIONES MENSAJES ============ //
const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

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
// ============ FIN FUNCIONES MENSAJES ============ //

// ============ INICIO FUNCIONES IMPORTACIÓN ============ //
const openModalImportar = () => {
    showModalImportar.value = true;
};

const cerrarModalResultados = () => {
    mostrarModalResultados.value = false;
    resultadoImportacion.value = null;
};
// ============ FIN FUNCIONES IMPORTACIÓN ============ //

// ============ INICIO FUNCIONES FORMULARIOS ============ //
const handleCambioEstado = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload();
    formCreateEstado.value = false;
};

const handleEdit = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    formEdit.value = false;
};

const handleAddDis = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    formCreateOption.value = false;
    formCreateOptionDis.value = false;
    router.visit(route('persona.index'));
};
// ============ FIN FUNCIONES FORMULARIOS ============ //

// ============ INICIO FUNCIONES MODALES ============ //
const openOption = () => {
    formCreate.value = false;
    formCreateOption.value = true;
    router.reload();
};

const openFormEstado = (data) => {
    selectedEstadoData.value = data;
    showModalEstado.value = false;
    formCreateEstado.value = true;
};

const closeModal = () => {
    ModalE.value = false;
};

const closeForm = () => {
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

    // 👇 Validar si tiene id_tutor (número o null)
    if (!dataGeneral.id_tutor) {
        // NO tiene tutor asignado → mostrar form de tutor
        formCreateTutor.value = true;
    } else {
        // SÍ tiene tutor asignado → ir directo a editar beneficiario
        openModalEditBeneficiary();
    }
};

const omitir = () => {
    mostrarMensaje('advertencia', 'Registro sin tutor', 'El beneficiario será registrado sin tutor asignado.');
    formCreateTutor.value = false;
    openModalEditBeneficiary();
};

/* mostrarMensaje('advertencia', 'Registro sin tutor', 'El beneficiario será registrado sin tutor asignado.'); */
const openModalEditBeneficiary = () => {
    formCreateTutor.value = false;

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

const openModalBeneficiary = (id, tipo) => {
    modalData.value = {
        tipo: tipo,
        registradoId: id
    };
    showModalRegistrado.value = true;
};

const cerrarModalesHabilitar = () => {
    showModalImportar.value = false;
    showModalHabilitar.value = false;
    showModalPendiente.value = false;
    showModalRegistrado.value = false;
    selectedItem.value = null;
};
// ============ FIN FUNCIONES MODALES ============ //

// ============ INICIO FUNCIONES TOOLTIP ============ //
const tooltipText = ref('')
const showTooltipFlag = ref(false)
const tooltipStyle = ref({})

const showTooltip = (text, buttonId) => {
    const button = document.getElementById(buttonId)
    if (button) {
        const rect = button.getBoundingClientRect()

        tooltipStyle.value = {
            left: `${rect.left + rect.width / 2}px`,
            top: `${rect.top - 10}px`,
            transform: 'translateX(-50%) translateY(-100%)'
        }
    }

    tooltipText.value = text
    showTooltipFlag.value = true
}

const hideTooltip = () => {
    showTooltipFlag.value = false
    tooltipText.value = ''
}
// ============ FIN FUNCIONES TOOLTIP ============ //

// Definición de columnas para la tabla de general
const tableColumns = [
    { label: 'Nº', field: 'numero', headerClass: 'text-center px-2', cellClass: 'whitespace-nowrap' },
    { label: 'Documento de Identidad', field: 'ci_persona', headerClass: 'px-2 whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Apellidos + Nombres', field: 'nombre_completo', headerClass: 'px-1 whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Tutor(a)', field: 'tutor', headerClass: 'px-1', cellClass: '' },
    { label: 'Documento de Respaldo', field: 'documento_respaldo', headerClass: 'px-1', cellClass: '' },
    { label: 'Estado', field: 'tipo_registro', headerClass: 'text-center px-1', cellClass: '' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center px-3', cellClass: '' }
];

const mostrarModal = ref(false);
const importando = ref(false);

// Configuración para Planilla General
const configPlanillaGeneral = {
    titulo: 'Importar planilla general',
    subtitulo: 'Carga la planilla descargada desde la plataforma Eustaquio Moto Méndez.',
    icono: 'upload',
    columnasTabla: [
        { nombre: 'Documento de Identidad', obligatorio: true },
        { nombre: 'Apellidos + Nombres', obligatorio: true },
        { nombre: 'Tutor(a)', obligatorio: true },
        { nombre: 'Documento de Respaldo', obligatorio: true }
    ],
    nombrePlantilla: 'plantilla_general.xlsx',
    urlPlantilla: '/plantilla/PlantillaGeneral.xlsx', // ✅ Corregido
    textoBotonImportar: 'Importar Excel'
};

// Manejador de importación - ahora recibe una función para limpiar
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

            // Limpia el archivo del modal
            limpiarArchivo();
        },
        onError: (errors) => {
            mostrarMensaje('error', 'Error', 'Hubo un problema al importar');
        },
        onFinish: () => {
            importando.value = false;
        }
    });
};

const handleDescargarPlantilla = (nombrePlantilla) => {
    const link = document.createElement('a');
    link.href = configPlanillaGeneral.urlPlantilla;
    link.download = nombrePlantilla;
    link.click();
};
</script>

<template>

    <Head title="UMADIS" />
    <div class="flex h-screen bg-gray-200 font-roboto">
        <Sidebar />
        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
                <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                    @close="cerrarMensaje" />
            </div>
            <Header class="mb-0" />

            <Transition name="fade">
                <Form v-if="formEdit" :fields="personaFieldsEdit" :distritos="distrito" :idFor="selectedId"
                    :existing-data="selectedItem || {}" :edit-mode="true" submit-route="general.editRegistro"
                    @add="handleEdit" @openFormOption="openOption" @sinDatos="sinDatos" @cancel="closeForm"
                    @close="closeForm">
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


            <Transition name="fade">
                <Form v-if="formCreateTutor" :fields="tutorFields" :botonOmitir="true" :idFor="selectedId"
                    :tutores="tutor" :existing-data="selectedItem || {}" submit-route="tutor.store"
                    @add="openModalEditBeneficiary" @tutorEncontradoSms="smsTutorEncontrado" @sinDatos="sinDatos"
                    @cancel="closeForm" @omitir="omitir">
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

            <Transition name="fade">
                <Form v-if="formCreateOption" :distritos="distrito" :fields="DistritoFields"
                    submit-route="dropdown.store" @add="handleAddDis" @encontrado="mensajeExisteDis" @cancel="closeForm"
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
                        Agregar Distrito
                    </template>
                    <template #label2>
                        Registre un nuevo distrito
                    </template>
                </Form>
            </Transition>

            <Transition name="fade">
                <Form v-if="formCreateOptionDis" :discapacidad="discapacidad" :fields="DiscapacidadFields"
                    submit-route="dropdown.addDis" @add="handleAddDis" @encontrado="mensajeExisteDisca"
                    @cancel="closeForm" @close="closeForm">
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

            <Transition name="fade">
                <ModalEstados v-if="ModalE" :tipo="tipoEstado" @close="closeModal" />
            </Transition>

            <Transition name="fade">
                <ModalTutor v-if="showModal" :data="selectedItem" :tutor="tutor" @close="showModal = false" />
            </Transition>

            <Transition name="fade">
                <ModalEstadoBene v-if="showModalEstado" :data="selectedItem" @add="handleCambioEstado"
                    @addEstado="openFormEstado" @close="showModalEstado = false" />
            </Transition>

            <Transition name="fade">
                <!-- Modal Error de Formato -->
                <ModalErrorFormato v-if="errorFormatoData" :error-data="errorFormatoData"
                    @close="errorFormatoData = null" />
            </Transition>

            <Transition name="fade">
                <!-- Modal: Ya es beneficiario -->
                <ModalEstados v-if="showModalRegistrado" :tipo="modalData.tipo" :registrado-id="modalData.registradoId"
                    @continuar="openPendingModal" @close="cerrarModalesHabilitar" />
            </Transition>

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

            <Transition name="fade">
                <ModalResultadosImportacion v-model="mostrarModalResultados"
                    :resultadoImportacion="resultadoImportacion || {}" @update:modelValue="cerrarModalResultados" />
            </Transition>

            <div class="py-2">
                <div class="px-5 py-1 flex justify-between">
                    <h1 class="font-semibold text-2xl">Registro General</h1>
                    <Rutas label1="Inicio" label3="Registro General" />
                </div>
            </div>
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

            <!-- Uso del componente de tabla para general -->
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
                    <td class="px-3 py-1.5 whitespace-nowrap">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ item.ci_persona }}
                            <span v-if="item.complemento !== null">-{{ item.complemento }}</span>
                        </div>
                    </td>

                    <!-- Columna: Apellidos + Nombres -->
                    <td class="px-1 py-1.5 whitespace-nowrap">
                        <div class="font-medium text-gray-900 dark:text-gray-100 uppercase">
                            {{ item.nombre_completo }}
                        </div>
                    </td>

                    <!-- Columna: Tutor(a) -->
                    <td class="px-1 py-1.5">
                        <div class="text-gray-700 dark:text-gray-300 whitespace-nowrap uppercase">
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
                    </td>

                    <!-- Columna: Documento de Respaldo -->
                    <td class="px-1 py-1.5">
                        <div class="text-gray-700 dark:text-gray-300 truncate max-w-xs"
                            :title="item.documento_respaldo">
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
                            <Icon :icon-button="true" name="user" class-name="text-gray-400 dark:text-gray-500"
                                :size="64" :height="64" />
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
            <div :class="general.data.length <= 15 ? 'mt-0.5' : 'mt-0'">
                <Paginacion v-if="general?.last_page > 1" :links="general.links" :from="general.from" :to="general.to"
                    :total="general.total" />
                <Footer />
            </div>
        </div>
    </div>
</template>
