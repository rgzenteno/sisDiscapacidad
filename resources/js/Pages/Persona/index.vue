<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';

// Componentes
import ModalImportaciones from '@/components/ModalImportaciones.vue';
import ModalEstadoBene from '@/components/ModalEstadoBene.vue';
import ModalEstados from '@/components/ModalEstados.vue';
import ModalCarnet from '@/components/ModalCarnet.vue';
import ModalTutor from '@/components/ModalTutor.vue';
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Mensajes from '@/components/Mensajes.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';
import Form from '@/components/Form/Form.vue';

// Utilidades
import { can } from '@/lib/can';
import ModalConfirmacion from '@/components/ModalConfirmacion.vue';
import Modal from '@/components/Modal.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const tutor = computed(() => page.props.tutor);
const filters = computed(() => page.props.filters || {});
const persona = computed(() => page.props.persona);
const distrito = computed(() => page.props.distrito);
const discapacidad = computed(() => page.props.discapacidad);
const selectedTutorName = computed(() => page.props.selectedTutorName);

// Opciones para selects
const distritosOptions = computed(() => {
    if (!distrito.value) return [];
    return distrito.value.map(d => ({
        text: d.distrito,
        value: d.distrito
    }));
});

const discapacidadOptions = computed(() => {
    if (!discapacidad.value) return [];
    return discapacidad.value.map(d => ({
        text: d.discapacidad,
        value: d.discapacidad
    }));
});

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const showModalCarnetEdit = ref(false);
const showModalImportar = ref(false);
const showModal = ref(false);
const showModalEstado = ref(false);
const showModalCarnet = ref(false);
const mostrarModalConfirmacion = ref(false);
const showModalWaringDiscapacidad = ref(false);
const showModalWaringDistrito = ref(false);
const ModalE = ref(false);

// ============================================================================
// REFS - ESTADO DE FORMULARIOS
// ============================================================================
const formCreate = ref(false);
const formEdit = ref(false);
const formCreateTutor = ref(false);
const formCreateCarnet = ref(false);
const formCreateEstado = ref('');
const formCreateOption = ref(false);
const formCreateOptionDis = ref(false);
const asignateTutor = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedItem = ref(null);
const selectedId = ref(null);
const selectedNombre = ref(null);
const beneficiarioEncontrado = ref(null);
const fechaNacimiento = ref(null);
const tipoEstado = ref('');
const selectedEstadoData = ref(null);
const mensajes = ref([]);

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

// ============================================================================
// REFS - IMPORTACIÓN
// ============================================================================
const importando = ref(false);

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - TUTORES
// ============================================================================
const tutorFields = [
    {
        typeCi: 'ci',
        typeInput: 'cedula',
        name: 'ci_tutor',
        label: 'C.I.',
        type: 'number',
        required: false,
        placeholder: 'la cédula de identidad',
        readonly: false,
        range: 10,
        autofocus: true
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

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - PERSONAS
// ============================================================================
const personaFields = [
    {
        typeInput: 'text',
        name: 'nombre_persona',
        label: 'Nombre',
        type: 'text',
        required: true,
        placeholder: 'el nombre',
        nameStyle: true,
        readonly: false,
        autofocus: true
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

const personaFieldsEdit = [...personaFields];

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - CARNETS
// ============================================================================
const carnetFields = [
    {
        typeInput: 'id',
        name: 'id_persona',
        label: 'Beneficiario',
        type: 'text',
        readonly: true
    },
    {
        typeInput: 'codigo_carnet',
        name: 'doc',
        label: 'Carnet Discapacidad',
        placeholder: 'Se generará automáticamente',
        required: true,
        readonly: false
    },
    {
        typeInput: 'select',
        name: 'discapacidad',
        label: 'Tipo de Discapacidad',
        type: 'text',
        placeholder: 'la discapacidad',
        options: discapacidadOptions.value,
        required: true,
        readonly: false,
        add: true
    },
    {
        typeInput: 'text',
        name: 'fecha_emision',
        label: 'Fecha de Emisión',
        type: 'date',
        required: true,
        placeholder: 'la fecha de emisión',
        readonly: false
    },
    {
        typeInput: 'text',
        name: 'fecha_vencimiento',
        label: 'Fecha de Vencimiento',
        type: 'date',
        required: false,
        readonly: true,
        placeholder: 'Se calculará automáticamente'
    }
];

const carnetFieldsEdit = [...carnetFields];

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - ESTADOS
// ============================================================================
const estadoFields = [
    {
        name: 'id_persona',
        label: '',
        hidden: true
    },
    {
        name: 'id_estado',
        label: '',
        hidden: true
    },
    {
        typeInput: 'select',
        name: 'estado',
        label: 'Estado del Beneficiario',
        type: 'text',
        placeholder: 'el estado del beneficiario',
        options: [
            { text: 'Activo', value: 'activo' },
            { text: 'Baja Temporal', value: 'baja_temporal' },
            { text: 'Baja Definitiva', value: 'baja_definitiva' }
        ],
        required: true,
        readonly: false,
        add: false
    },
    {
        typeInput: 'text',
        name: 'fecha_inicio',
        label: 'Fecha de Inicio',
        type: 'date',
        required: true,
        placeholder: 'la fecha de inicio del estado',
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
        nameStyle: true,
        readonly: false,
        autofocus: true,
        nameStyle: 'uppercase'
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
        nameStyle: true,
        readonly: false,
        autofocus: true,
        nameStyle: 'uppercase'
    }
];

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Nombre Completo', field: 'nombre_completo', headerClass: '', cellClass: 'whitespace-nowrap' },
    { label: 'Distrito', field: 'distrito', headerClass: '', cellClass: 'whitespace-nowrap' },
    { label: 'Cedula de Identidad', field: 'ci_persona', headerClass: 'text-center whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Observación', field: 'observacion_persona', headerClass: 'text-center', cellClass: '' },
    { label: 'Estado', field: 'estado_actual', headerClass: 'text-center', cellClass: '' },
    { label: 'Carnet Dis.', field: 'carnet', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'Tutor', field: 'tutor', headerClass: 'text-center', cellClass: 'whitespace-nowrap' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center', cellClass: '' }
];

// ============================================================================
// CONFIGURACIÓN DE IMPORTACIÓN
// ============================================================================
const configPlanillaGeneral = {
    titulo: 'Importar base de datos de beneficiarios',
    subtitulo: 'Carga el excel con la información de los beneficiarios con el formato establecido.',
    icono: 'upload',
    columnasTabla: [
        { nombre: 'Nombre', obligatorio: true },
        { nombre: 'Apellido', obligatorio: true },
        { nombre: 'Distrito', obligatorio: true },
        { nombre: 'CI', obligatorio: true },
        { nombre: 'Observaciones', obligatorio: false }
    ],
    nombrePlantilla: 'plantillaBaseDeDatos.xlsx',
    urlPlantilla: '/plantilla/PlantillaImportacion.xlsx',
    textoBotonImportar: 'Importar Excel'
};

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
 * Cierra un mensaje específico
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
    mostrarMensaje('info', 'Registro encontrado', 'El tutor ya está registrado, presiona siguiente para vincular.');
};

const smsTutorEncontradoAsignar = () => {
    mostrarMensaje('info', 'Registro encontrado', 'El tutor ya está registrado, el beneficiario se vinculara con este tutor.');
};

const openMissingDataModal = () => {
    mostrarMensaje('advertencia', 'Datos faltantes', 'La fecha de nacimiento del beneficiario es necesaria para crear el carnet de discapacidad.');
};

const fechaInvalida = () => {
    mostrarMensaje('advertencia', 'Fecha incorrecta', 'La fecha ingresada no puede ser anterior al último estado registrado');
};

// ============================================================================
// FUNCIONES - TOOLTIP
// ============================================================================

/**
 * Muestra un tooltip en la posición del botón
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
 * Oculta el tooltip
 */
const hideTooltip = () => {
    showTooltipFlag.value = false;
    tooltipText.value = '';
};

// ============================================================================
// FUNCIONES - NAVEGACIÓN Y REPORTES
// ============================================================================

/**
 * Navega a una ruta específica con un ID codificado
 * @param {string} ruta - Nombre de la ruta
 * @param {number|string} id - ID a codificar y pasar como parámetro
 */
const getUrl = (ruta, id) => {
    const url = route(ruta, id); // ✅ UUID directo
    router.visit(url);
};

/**
 * Genera el informe de personas en PDF
 */
const generearInforme = () => {
    router.visit(route('persona.reporte'));
};

// ============================================================================
// FUNCIONES - FORMULARIOS CREACIÓN
// ============================================================================

/**
 * Abre el formulario de creación de beneficiario
 */
const openFormCreate = () => {
    formCreate.value = true;
};

/**
 * Maneja la adición exitosa de un beneficiario
 */
const handleAddBene = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    formCreate.value = false;
    router.reload({ only: ['personas', 'persona'] });
};

/**
 * Maneja la adición exitosa de un tutor
 */
const handleAddTutor = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    formCreateTutor.value = false;
    formCreate.value = true;
};

/**
 * Maneja la asignación de tutor
 */
const handleTutor = () => {
    asignateTutor.value = false;
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload({ only: ['personas', 'persona'] });
};

/**
 * Maneja el cambio de estado del beneficiario
 */
const handleCambioEstado = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload({ only: ['personas', 'persona'] });
    formCreateEstado.value = false;
};

/**
 * Maneja la adición exitosa de un carnet
 */
const addCarnet = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload({ only: ['personas', 'persona'] });
    formCreateCarnet.value = false;
};

/**
 * Maneja la adición de distrito/discapacidad
 */
const handleAddDis = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    formCreate.value = false;
    formEdit.value = false;
    formCreateCarnet.value = false;
    showModalCarnetEdit.value = false;
    formCreateOption.value = false;
    formCreateOptionDis.value = false;
    router.visit(route('persona.index'));
};

/**
 * Maneja cuando se encuentra un tutor
 */
const encontrado = () => {
    mostrarMensaje('correcto', 'Tutor Seleccionado', 'El tutor sera vinculado con este beneficiario.');
    formCreateTutor.value = false;
    formCreate.value = true;
};

/**
 * Omite la asignación de tutor
 */
const omitir = () => {
    mostrarMensaje('advertencia', 'Registro sin tutor', 'El beneficiario será registrado sin tutor asignado.');
    formCreateTutor.value = false;
    openFormCreate();
};

// ============================================================================
// FUNCIONES - FORMULARIOS EDICIÓN
// ============================================================================

/**
 * Abre el formulario de edición de persona
 * @param {Object} item - Datos de la persona
 * @param {number|string} idPersona - ID de la persona
 */
const openEditPersona = (item, idPersona) => {
    if (!item) {
        mostrarMensaje('error', 'Error', 'No se encontró el beneficiario');
        return;
    }

    const debeSepararNombre = item.tipo_registro === 'pendiente' &&
        (!item.nombre_persona || !item.apellido_persona);

    let nombreSeparado = {
        nombre: item.nombre_persona || '',
        apellido: item.apellido_persona || ''
    };

    if (debeSepararNombre && item.nombre_completo) {
        nombreSeparado = separarNombre(item.nombre_completo);
    }

    selectedItem.value = {
        id_persona: item.id_persona,
        ci_persona: item.ci_persona,
        complemento: item.complemento,
        nombre_persona: nombreSeparado.nombre,
        apellido_persona: nombreSeparado.apellido,
        distrito: item.distrito,
        fecha_nacimiento: item.fecha_nacimiento,
        observacion_persona: item.observacion_persona,
        documento_respaldo: item.documento_respaldo,
    };

    selectedId.value = idPersona;
    formEdit.value = true;
};

/**
 * Maneja la edición exitosa de una persona
 */
const handleEdit = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload({ only: ['personas', 'persona'] });
    formEdit.value = false;
};

/**
 * Abre el formulario de edición de carnet
 * @param {Object} item - Datos del carnet
 * @param {number|string} idCarnet - ID del carnet
 * @param {string} nombrePersona - Nombre de la persona
 */
const openEditCarnet = (item, idCarnet, nombrePersona) => {

    selectedId.value = idCarnet;
    selectedItem.value = { ...item };
    selectedNombre.value = nombrePersona;

    showModalCarnet.value = false;
    showModalCarnetEdit.value = true;
};

/**
 * Maneja la edición exitosa del carnet
 */
const handleEditCarnet = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información del carnet se actualizó correctamente.');
    showModalCarnetEdit.value = false;
};

/**
 * Cancela la edición del carnet y vuelve al modal de visualización
 */
const handleEditCancel = () => {
    showModalCarnetEdit.value = false;
    showModalCarnet.value = true;
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Abre el modal con datos del tutor
 * @param {Object} datos - Datos del tutor
 */
const ModalTutorDatos = (datos) => {
    selectedItem.value = { ...datos };
    showModal.value = true;
};

/**
 * Abre el modal de estados del beneficiario
 * @param {Object} datos - Datos del beneficiario
 */
const openModalEstado = (datos) => {
    selectedItem.value = { ...datos };
    showModalEstado.value = true;
};

/**
 * Abre el formulario de creación de carnet
 * @param {number|string} idPersona - ID de la persona
 * @param {string} nombrePersona - Nombre de la persona
 * @param {string} fecha - Fecha de nacimiento
 */
const openCreateCarnet = (idPersona, nombrePersona, fecha) => {
    selectedId.value = idPersona;
    selectedNombre.value = nombrePersona;
    fechaNacimiento.value = fecha;
    formCreateCarnet.value = true;
};

/**
 * Abre el modal de visualización de carnet
 * @param {Object} datos - Datos del carnet
 */
const getCarnetUrl = (datos) => {
    selectedItem.value = { ...datos };
    showModalCarnet.value = true;
};

/**
 * Abre el formulario de creación de tutor
 */
const openTutor = () => {
    formCreateTutor.value = true;
};

/**
 * Abre el formulario para asignar tutor a un registro
 * @param {number|string} idPersona - ID de la persona
 */
const openAsignateTutor = (idPersona) => {

    const registro = persona.value.data.find(p => p.id_persona === idPersona);

    if (!registro) {
        mostrarMensaje('error', 'Error', 'No se encontró el registro');
        return;
    }

    const tutorSeparado = separarNombre(registro.tutor_nombre);

    selectedItem.value = {
        nombre_tutor: tutorSeparado.nombre,
        apellido_tutor: tutorSeparado.apellido,
        tiene_tutor: !!registro.tutor_nombre
    };

    selectedId.value = idPersona;
    asignateTutor.value = true;
};

/**
 * Abre el modal de estados
 * @param {string} tipo - Tipo de estado
 */
const openModalE = (tipo) => {
    tipoEstado.value = tipo;
    ModalE.value = true;
};

/**
 * Cierra el modal de estados
 */
const closeModal = () => {
    ModalE.value = false;
};

/**
 * Abre el formulario de creación de estado
 * @param {Object} data - Datos del estado
 */
const openFormEstado = (data) => {
    selectedEstadoData.value = data;
    showModalEstado.value = false;
    formCreateEstado.value = true;
};

/**
 * Vuelve al modal de estado desde el formulario
 */
const abrirEstado = () => {
    formCreateEstado.value = false;
    showModalEstado.value = true;
};

const registroAEliminar = ref(null);

// Modificar la función eliminarRegistro
const eliminarRegistro = (id) => {
    if (!id) {
        mostrarMensaje('error', 'Error', 'No se puede eliminar este registro');
        return;
    }

    // Guardar el ID y mostrar el modal de confirmación
    registroAEliminar.value = id;
    mostrarModalConfirmacion.value = true;
}

// Nueva función para confirmar eliminación
const confirmarEliminacion = () => {
    router.delete(route('persona.estado.eliminar', registroAEliminar.value), {
        onSuccess: () => {
            mostrarModalConfirmacion.value = false;
            showModalEstado.value = false;
            registroAEliminar.value = null;
            mostrarMensaje('correcto', 'Eliminación exitosa', 'El registro se eliminó correctamente.');
            router.reload({ only: ['personas', 'persona'] });
        },
        onError: (errors) => {
            console.error('Error al eliminar:', errors);
            mostrarModalConfirmacion.value = false;
            registroAEliminar.value = null;
            mostrarMensaje('error', 'Error', 'No se pudo eliminar el registro');
        }
    });
}

// Nueva función para cancelar
const cancelarEliminacion = () => {
    mostrarModalConfirmacion.value = false;
    registroAEliminar.value = null;
}

/**
 * Cierra todos los formularios y modales
 */
const closeForm = () => {
    showModalCarnetEdit.value = false;
    formCreateOption.value = false;
    formCreateOptionDis.value = false;
    formCreateCarnet.value = false;
    showModalCarnet.value = false;
    formCreateTutor.value = false;
    createTutor.value = false;
    formCreate.value = false;
    formEdit.value = false;
};

// ============================================================================
// FUNCIONES - IMPORTACIÓN
// ============================================================================

/**
 * Abre el modal de importación
 */
const openModalImportar = () => {
    showModalImportar.value = true;
};

/**
 * Maneja la importación de archivos Excel
 * @param {File} archivo - Archivo a importar
 * @param {Function} limpiarArchivo - Función para limpiar el input de archivo
 */
const handleImportar = (archivo, limpiarArchivo) => {
    const formData = new FormData();
    formData.append('archivo', archivo);

    importando.value = true;

    router.post(route('persona.importar.store'), formData, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash?.errorFormato) {
                console.error('Error de formato:', page.props.flash.errorFormato);
            } else if (page.props.flash?.importResults) {
                showModalImportar.value = false;
            }
            limpiarArchivo();
        },
        onError: (errors) => {
            console.error('Error en importación:', errors);
            mostrarMensaje('error', 'Error', 'Hubo un problema al importar');
        },
        onFinish: () => {
            importando.value = false;
        }
    });
};

/**
 * Descarga la plantilla de importación
 * @param {string} nombrePlantilla - Nombre del archivo a descargar
 */
const handleDescargarPlantilla = (nombrePlantilla) => {
    const link = document.createElement('a');
    link.href = configPlanillaGeneral.urlPlantilla;
    link.download = nombrePlantilla;
    link.click();
};

/**
 * Maneja cuando se encuentra un beneficiario en la búsqueda
 * @param {string|number} ciPersona - CI de la persona encontrada
 */
const encotrado = (ciPersona) => {
    const personaExistente = usePage().props.flash.persona_existente;
    const tipoRegistro = personaExistente?.tipo_registro;

    if (tipoRegistro === 'registrado') {
        mostrarMensaje('advertencia', 'Persona registrada', 'Esta persona está registrada pero no es beneficiario.');
    } else {
        mostrarMensaje('info', 'Beneficiario registrado', 'Esta persona ya se encuentra registrada en el sistema.');
        beneficiarioEncontrado.value = ciPersona;
    }

    formCreate.value = false;
};
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex h-screen bg-gray-200 font-roboto">
        <!-- Sidebar de navegación -->
        <Sidebar />

        <!-- Contenedor principal -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- ============================================================================ -->
            <!-- SISTEMA DE MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                @close="cerrarMensaje" />

            <!-- Header -->
            <Header class="mb-0" />

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-5 py-3 flex justify-between">
                <h1 class="font-semibold text-2xl">Beneficiarios</h1>
                <Rutas label1="Inicio" label3="Beneficiarios" />
            </div>

            <!-- ============================================================================ -->
            <!-- BARRA DE HERRAMIENTAS -->
            <!-- ============================================================================ -->
            <div class="flex justify-between p-4 pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <!-- Buscador -->
                <Busqueda :initial-value="beneficiarioEncontrado || filters.buscador" name="beneficiario" only="persona"
                    :data="persona" ruta-busqueda="persona.index" />

                <!-- Botones de acción -->
                <div class="flex gap-2">
                    <!-- Botón: Agregar -->
                    <Button v-if="can('agregar-beneficiario')" id="btn-agregar" @click.prevent="openTutor()"
                        @mouseenter="showTooltip('Agregar', 'btn-agregar')" @mouseleave="hideTooltip"
                        :style="'px-2 py-2 pb-1 rounded-full border-none'"
                        class="shrink-0 self-center bg-gray-200 relative overflow-hidden group">
                        <!-- Efecto de fondo desde el centro -->
                        <span
                            class="absolute inset-0 bg-blue-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                        <!-- Icono -->
                        <span class="relative z-10">
                            <Icon :icon-button="true" name="userAdd"
                                class-name="text-gray-600 group-hover:text-white transition-colors duration-500"
                                :size="32" :height="32" />
                        </span>
                    </Button>

                    <!-- Botón: Importar -->
                    <Button v-if="can('importar-beneficiario')" id="btn-importar" @click.prevent="openModalImportar()"
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

                    <!-- Botón: Generar PDF -->
                    <Button v-if="can('reporte-beneficiario')" id="btn-reporte" @click.prevent="generearInforme()"
                        @mouseenter="showTooltip('Generar PDF', 'btn-reporte')" @mouseleave="hideTooltip"
                        :style="'px-3 py-3 pb-2 rounded-full border-none'"
                        class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                        <!-- Efecto de fondo desde el centro -->
                        <span
                            class="absolute inset-0 bg-red-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                        <!-- Icono -->
                        <span class="relative z-10">
                            <Icon :icon-button="true" name="filePDF" fill="currentColor"
                                class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
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
            <DataTable :data="persona.data" :columns="tableColumns" row-key="id_persona"
                empty-message="No se encontraron datos. ¡Agregue beneficiarios para continuar!">
                <!-- Slot personalizado para cada fila -->
                <template #row="{ item }">

                    <!-- Columna: Nombre Completo -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="font-medium text-gray-900 dark:text-gray-100 uppercase">
                            {{ item.nombre_completo }}
                        </div>
                    </td>

                    <!-- Columna: Distrito -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="text-gray-700 dark:text-gray-300">
                            <span v-if="item.distrito">{{ item.distrito }}</span>
                            <span v-else class="block text-red-500 italic text-xs">Sin datos</span>
                        </div>
                    </td>

                    <!-- Columna: CI (Cédula de Identidad) -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ item.ci_persona }}
                            <span v-if="item.complemento !== null">- {{ item.complemento }}</span>
                        </div>
                    </td>

                    <!-- Columna: Observación -->
                    <td class="px-0 py-1">
                        <div class="text-gray-700 dark:text-gray-300">
                            <span v-if="!item.observacion_persona"
                                class="block text-center italic text-gray-500 text-xs">
                                ninguna
                            </span>
                            <span v-else class="text-xs line-clamp-1 cursor-help" :title="item.observacion_persona">
                                {{ item.observacion_persona }}
                            </span>
                        </div>
                    </td>

                    <!-- Columna: Estado -->
                    <td class="px-0 py-1">
                        <div v-if="item.estado_actual?.estado" @click.prevent="openModalEstado(item)"
                            class="cursor-pointer action-link flex items-center gap-1">
                            <p
                                class="relative inline-blockrounded-lg block px-0 py-2 text-gray-700 dark:text-gray-200 dark:hover:text-white">
                                <!-- Icono: Activo -->
                                <Icon v-if="item.estado_actual?.estado === 'activo'" :icon-button="true"
                                    name="checkCircle" class-name="text-green-500 pt-1" :size="17" />
                                <!-- Icono: Baja Temporal -->
                                <Icon v-if="item.estado_actual?.estado === 'baja_temporal'" :icon-button="true"
                                    name="timeCircle" class-name="text-orange-500 pt-1" :size="17" />
                                <!-- Icono: Baja Definitiva -->
                                <Icon v-if="item.estado_actual?.estado === 'baja_definitiva'" :icon-button="true"
                                    name="circleMinus" class-name="text-red-600 pt-1" :size="17" />
                                <!-- Icono: Sin Estado -->
                                <Icon v-if="!item.estado_actual?.estado" :icon-button="true" name="exclamationCircle"
                                    class-name="text-red-500 pt-1" :size="17" />
                            </p>
                            <span class="text-gray-400">|</span>
                            <div class="font-medium whitespace-nowrap"
                                :class="item.estado_actual?.estado === 'activo' ? 'text-green-600' : item.estado_actual?.estado === 'baja_definitiva' ? 'text-red-600' : 'text-orange-600'">
                                {{
                                    item.estado_actual?.estado === 'activo' ? 'Activo' :
                                        item.estado_actual?.estado === 'baja_temporal' ? 'Baja Temporal' :
                                            'Baja Definitiva'
                                }}
                            </div>
                        </div>
                    </td>

                    <!-- Columna: Carnet -->
                    <td class="px-3 py-1 whitespace-nowrap">
                        <div class="flex items-center">
                            <div @click.prevent="item.carnet?.id_carnet ? getCarnetUrl(item) : (can('carnet') ? (!item.fecha_nacimiento ? openMissingDataModal() : openCreateCarnet(item.id_persona, `${item.nombre_persona} ${item.apellido_persona}`, `${item.fecha_nacimiento}`)) : null)"
                                :class="[
                                    'flex items-center gap-1',
                                    item.carnet?.id_carnet ? 'cursor-pointer action-link' : (can('carnet') ? 'cursor-pointer action-link' : 'cursor-not-allowed'),
                                    !item.carnet?.id_carnet ? 'text-red-600 hover:text-red-800' : item.carnet_vigente === false ? 'text-yellow-400 hover:text-yellow-700' : 'text-gray-600 hover:text-gray-800'
                                ]">
                                <Icon :icon-button="true" name="profileCard" :size="19"
                                    :class-name="!item.carnet?.id_carnet ? 'text-red-500' : item.carnet_vigente === false ? 'text-yellow-500' : 'text-gray-600'" />
                                <span>|</span>
                                <div class="font-medium">
                                    <span v-if="item.carnet?.id_carnet">{{ item.carnet?.doc }}</span>
                                    <span v-else class="italic">Sin Carnet</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Columna: Tutor -->
                    <td class="px-1 py-1 whitespace-nowrap">
                        <div class="flex items-center">
                            <div @click.prevent="item.tutor?.id_tutor ? ModalTutorDatos(item) : (can('asignar-tutor') ? openAsignateTutor(item.id_persona) : null)"
                                :class="[
                                    'flex items-center gap-1',
                                    item.tutor?.id_tutor ? 'cursor-pointer action-link' : (can('asignar-tutor') ? 'cursor-pointer action-link' : 'cursor-not-allowed'),
                                    item.id_tutor ? 'text-gray-600 hover:text-gray-800' : 'text-red-500 hover:text-red-800'
                                ]">
                                <Icon :icon-button="true" name="users" :size="19"
                                    :class-name="item.id_tutor ? 'text-gray-600 hover:text-gray-800' : 'text-red-500 hover:text-red-800'" />
                                <span>|</span>
                                <div class="font-medium capitalize">
                                    <span v-if="item.tutor?.id_tutor">{{ item.tutor?.nombre_tutor }}</span>
                                    <span class="capitalize" v-else>no asignado</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Columna: Acciones -->
                    <td class="px-3 py-2">
                        <div class="flex justify-center items-center gap-1">
                            <!-- Acción: Editar -->
                            <div v-if="can('editar-beneficiario')">
                                <Icon v-if="item.tipo_registro === 'pendiente' &&
                                    item.estado_actual?.estado !== 'baja_definitiva' &&
                                    item.estado_actual?.estado !== 'baja_temporal'"
                                    @click.prevent="openEditPersona(item, item.id_persona)" name="userSettings"
                                    class-name="text-orange-600" :ripple="true" ripple-color="bg-orange-500"
                                    title="Registro pendiente" />
                                <Icon v-else @click.prevent="openEditPersona(item, item.id_persona)" name="userEdit"
                                    title="Editar" />
                            </div>

                            <!-- Acción: Baja Definitiva o Habilitar Meses -->
                            <div v-if="can('habilitar')">
                                <a v-if="item.estado_actual?.estado === 'baja_definitiva'"
                                    @click.prevent="openModalE(item.estado_actual?.estado)"
                                    class="cursor-pointer action-link">
                                    <Icon name="circleMinus" class-name="text-red-700" title="Baja definitiva" />
                                </a>
                                <a v-else
                                    @click.prevent="(item.carnet?.id_carnet && item.tutor?.id_tutor) ? getUrl('persona.show', item.id_persona) : null">
                                    <Icon name="calendarMont" fill="none" stroke="currentColor" stroke-width="2"
                                        :class-name="(item.carnet?.id_carnet && item.tutor?.id_tutor) ? 'text-gray-800 cursor-pointer action-link' : 'text-red-800 cursor-not-allowed'"
                                        :title="(item.carnet?.id_carnet && item.tutor?.id_tutor) ? 'Habilitar meses' : 'Datos faltantes'" />
                                </a>
                            </div>


                            <!-- Acción: Ver Meses Disponibles -->
                            <a v-if="can('pago')"
                                @click.prevent="(item.carnet?.id_carnet && item.tutor?.id_tutor) ? getUrl('persona.showHabilitado', item.carnet?.id_persona) : null">
                                <Icon name="clipboardList"
                                    :class-name="(item.carnet?.id_carnet && item.tutor?.id_tutor) ? 'text-gray-800 cursor-pointer action-link' : 'text-red-800 cursor-not-allowed'"
                                    :title="(item.carnet?.id_carnet && item.tutor?.id_tutor) ? 'Meses disponibles' : 'Datos faltantes'" />
                            </a>

                        </div>
                    </td>
                </template>

                <!-- Slot: Estado vacío -->
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
                                Agregue beneficiarios para continuar y visualizar la información aquí.
                            </p>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- ============================================================================ -->
            <!-- FOOTER Y PAGINACIÓN -->
            <!-- ============================================================================ -->
            <div :class="persona.data.length <= 15 ? 'mt-0.5' : 'mt-0'">
                <Paginacion v-if="persona?.last_page > 1" :links="persona.links" :from="persona.from" :to="persona.to"
                    :total="persona.total" />
                <Footer />
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - BENEFICIARIOS -->
        <!-- ============================================================================ -->

        <!-- Formulario: Crear Beneficiario -->
        <Transition name="fade">
            <Form v-if="formCreate" :fields="personaFields" :distritos="distrito" :nombreFor="String(selectedTutorName)"
                submit-route="persona.store" @add="handleAddBene" @openFormOption="showModalWaringDistrito = true"
                @sinDatos="sinDatos" @cancel="formCreate = false" @close="encotrado">
                <template #icon>
                    <Icon :icon-button="true" name="userAdd" class-name="text-white" />
                </template>
                <template #label1>
                    Agregar Beneficiario
                </template>
                <template #label2>
                    Registre un nuevo beneficiario
                </template>
            </Form>
        </Transition>

        <!-- Formulario: Editar Beneficiario -->
        <Transition name="fade">
            <Form v-if="formEdit" :fields="personaFieldsEdit" boton-name="Guardar" :distritos="distrito"
                :idFor="selectedId" :existing-data="selectedItem || {}" :edit-mode="true" submit-route="persona.update"
                @add="handleEdit" @openFormOption="showModalWaringDistrito = true" @sinDatos="sinDatos"
                @cancel="formEdit = false" @close="formEdit = false">
                <template #icon>
                    <Icon :icon-button="true" name="userEdit" class-name="text-white" />
                </template>
                <template #label1>
                    Editar beneficiario
                </template>
                <template #label2>
                    Actualiza los datos del beneficiario
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - TUTORES -->
        <!-- ============================================================================ -->

        <!-- Formulario: Crear Tutor -->
        <Transition name="fade">
            <Form v-if="formCreateTutor" :fields="tutorFields" :botonOmitir="true" :idFor="selectedId" :tutores="tutor"
                :addTutor="true" submit-route="tutor.store" @add="handleAddTutor" @sinDatos="sinDatos"
                @tutorEncontradoSms="smsTutorEncontrado" @encontrado="encontrado" @cancel="formCreateTutor = false"
                @omitir="omitir">
                <template #icon>
                    <Icon :icon-button="true" name="users" class-name="text-white" />
                </template>
                <template #label1>
                    Agregar Tutor
                </template>
                <template #label2>
                    Registre un nuevo tutor
                </template>
            </Form>
        </Transition>

        <!-- Formulario: Asignar Tutor -->
        <Transition name="fade">
            <Form v-if="asignateTutor" :fields="tutorFields" :idFor="selectedId" :addTutor="true" boton-name="Asignar"
                :existing-data="selectedItem || {}" :tutores="tutor" submit-route="tutor.store" keep-button-text
                @add="handleTutor" @tutorEncontradoSms="smsTutorEncontradoAsignar" @sinDatos="sinDatos"
                @cancel="asignateTutor = false">
                <template #icon>
                    <Icon :icon-button="true" name="users" class-name="text-white" />
                </template>
                <template #label1>
                    Asignar Tutor
                </template>
                <template #label2>
                    Asigne el tutor
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - CARNETS -->
        <!-- ============================================================================ -->

        <!-- Formulario: Crear Carnet -->
        <Transition name="fade">
            <Form v-if="formCreateCarnet" :fields="carnetFields" :discapacidad="discapacidad" :idFor="selectedId"
                :nombreFor="selectedNombre" :fechaNacimiento="fechaNacimiento" clave-foranea="id_persona"
                submit-route="carnet.store" @add="addCarnet" @openFormOption="showModalWaringDiscapacidad = true"
                @sinDatos="sinDatos" @cancel="formCreateCarnet = false">
                <template #icon>
                    <Icon :icon-button="true" name="profileCard" class-name="text-white" />
                </template>
                <template #label1>
                    Agregar Carnet
                </template>
                <template #label2>
                    Registre el carnet de discapacidad
                </template>
            </Form>
        </Transition>

        <!-- Formulario: Editar Carnet -->
        <Transition name="fade">
            <Form v-if="showModalCarnetEdit" :fields="carnetFieldsEdit" boton-name="Guardar"
                :discapacidad="discapacidad" :existing-data="selectedItem || {}" :nombreFor="selectedNombre"
                :idFor="selectedId" :edit-mode="true" submit-route="carnet.update" @add="handleEditCarnet"
                @openFormOption="showModalWaringDiscapacidad = true" @sinDatos="sinDatos" @cancel="handleEditCancel"
                @close="handleEditCancel">
                <template #icon>
                    <Icon :icon-button="true" name="userEdit" class-name="text-white" />
                </template>
                <template #label1>
                    Editar carnet discapacidad
                </template>
                <template #label2>
                    Actualiza los datos del carnet de discapacidad
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - ESTADOS -->
        <!-- ============================================================================ -->

        <!-- Formulario: Agregar Estado -->
        <Transition name="fade">
            <Form v-if="formCreateEstado" :fields="estadoFields" :data="selectedEstadoData"
                submit-route="persona.estado" @add="handleCambioEstado" @fechaInvalida="fechaInvalida"
                @sinDatos="sinDatos" @cancel="abrirEstado">
                <template #icon>
                    <Icon :icon-button="true" name="badgeCheck" class-name="text-white" />
                </template>
                <template #label1>
                    Agregar estado
                </template>
                <template #label2>
                    Registre un nuevo estado del beneficiario
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- FORMULARIOS - OPCIONES (DISTRITO Y DISCAPACIDAD) -->
        <!-- ============================================================================ -->

        <!-- Formulario: Agregar Distrito -->
        <Transition name="fade">
            <Form v-if="formCreateOption" :distritos="distrito" :fields="DistritoFields" submit-route="dropdown.store"
                @add="handleAddDis" @encontrado="mensajeExisteDis" @cancel="formCreateOption = false"
                @close="formCreateOption = false">
                <template #icon>
                    <Icon :icon-button="true" name="clipboard" class-name="text-white" />
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
                submit-route="dropdown.addDis" @add="handleAddDis" @encontrado="mensajeExisteDisca"
                @cancel="formCreateOptionDis = false" @close="formCreateOptionDis = false">
                <template #icon>
                    <Icon :icon-button="true" name="clipboard" class-name="text-white" />
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

        <!-- Modal: Advertencia Agregar Distrito -->
        <Transition name="fade">
            <Modal v-if="showModalWaringDistrito" :showHeader="false" :showFooter="false" maxWidth="max-w-md"
                @close="showModalWaringDistrito = false">
                <div class="p-4 px-3 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                        <svg class="w-12 h-12 text-yellow-500 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <!-- Título -->
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                        Agregar Distrito
                    </h2>

                    <!-- Texto -->
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Esta acción cerrará el formulario de registro y perderá los datos ingresados.
                    </p>
                </div>

                <!-- Footer centrado para modal pequeño -->
                <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                    <div class="flex justify-center gap-3">
                        <Button @click="showModalWaringDistrito = false" :style="'px-12 py-2.5 rounded-xl'"
                            class="text-slate-700 bg-white hover:bg-slate-100">
                            Cancelar
                        </Button>
                        <Button @click="formCreateOption = true; showModalWaringDistrito = false"
                            :style="'px-12 py-2.5 rounded-xl'" class="text-white bg-blue-600 hover:bg-blue-500">
                            Continuar
                        </Button>
                    </div>
                </div>
            </Modal>
        </Transition>

        <!-- Modal: Advertencia Agregar Discapacidad -->
        <Transition name="fade">
            <Modal v-if="showModalWaringDiscapacidad" :showHeader="false" :showFooter="false" maxWidth="max-w-md"
                @close="showModalWaringDiscapacidad = false">
                <div class="p-4 px-3 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                        <svg class="w-12 h-12 text-yellow-500 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <!-- Título -->
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                        Agregar Discapacidad
                    </h2>

                    <!-- Texto -->
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Esta acción cerrará el formulario de registro y perderá los datos ingresados.
                    </p>
                </div>

                <!-- Footer centrado para modal pequeño -->
                <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                    <div class="flex justify-center gap-3">
                        <Button @click="showModalWaringDiscapacidad = false" :style="'px-12 py-2.5 rounded-xl'"
                            class="text-slate-700 bg-white hover:bg-slate-100">
                            Cancelar
                        </Button>
                        <Button @click="formCreateOptionDis = true; showModalWaringDiscapacidad = false"
                            :style="'px-12 py-2.5 rounded-xl'" class="text-white bg-blue-600 hover:bg-blue-500">
                            Continuar
                        </Button>
                    </div>
                </div>
            </Modal>
        </Transition>


        <!-- Modal: Visualizar Carnet -->
        <Transition name="fade">
            <ModalCarnet v-if="showModalCarnet" :data="selectedItem" @edit="openEditCarnet"
                @close="showModalCarnet = false" />
        </Transition>

        <!-- Modal: Estados -->
        <Transition name="fade">
            <ModalEstados v-if="ModalE" :tipo="tipoEstado" @close="ModalE = false" />
        </Transition>

        <!-- Modal: Información del Tutor -->
        <Transition name="fade">
            <ModalTutor v-if="showModal" :data="selectedItem" :tutor="tutor" @close="showModal = false" />
        </Transition>

        <!-- Modal: Estados del Beneficiario -->
        <Transition name="fade">
            <ModalEstadoBene v-if="showModalEstado" :data="selectedItem" @add="handleCambioEstado"
                @addEstado="openFormEstado" @close="showModalEstado = false" @delete="eliminarRegistro" />
        </Transition>

        <!-- Modal: Confirmar Eliminacion de Estado -->
        <Transition name="fade">
            <ModalConfirmacion v-if="mostrarModalConfirmacion" tipo="eliminar" @confirmar="confirmarEliminacion"
                @close="cancelarEliminacion" />
        </Transition>

        <!-- Modal: Importación de Datos -->
        <Transition name="fade">
            <ModalImportaciones v-if="showModalImportar" v-bind="configPlanillaGeneral" :importando="importando"
                @importar="handleImportar" @close="showModalImportar = false"
                @descargar-plantilla="handleDescargarPlantilla" />
        </Transition>

    </div>
</template>
