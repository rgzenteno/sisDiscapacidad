<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

// Componentes
import Sidebar from '@/components/Sidebar.vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import ModalGestion from '@/components/ModalGestion.vue';
import Form from '@/components/Form/Form.vue';
import Rutas from '@/components/Rutas.vue';
import Dropdown from '@/components/Dropdown.vue';
import Reporte from './reporte.vue';
import Button from '@/components/Button.vue';
import ModalResultadosMes from '@/components/ModalResultadosMes.vue';
import { watch } from 'vue';

// Utilidades
import { can } from '@/lib/can';
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const gestiones = computed(() => page.props.gestiones);
const gestion = computed(() => page.props.gestion);

const filters = computed(() => page.props.filters);
const años_registrados = computed(() => page.props.años_registrados);
const presupuestosAnuales = computed(() => page.props.presupuestosAnuales);
const total_personas_validas = computed(() => page.props.total_personas_validas);
const mes_actual_disponible = computed(() => page.props.mes_actual_disponible);
const añoSeleccionado = computed(() => page.props.añoSeleccionado);
const año_actual = computed(() => page.props.año_actual);
const sumaPresupuestoMensual = computed(() => page.props.sumaPresupuestoMensual);
const existe_gestion = computed(() => page.props.existe_gestion);
const btnAgregar = computed(() => page.props.btnAgregar);
const gestionData = computed(() => page.props.gestionData);
const tiene_meses = computed(() => page.props.tiene_meses);

// ============================================================================
// REFS - ESTADO DE FORMULARIOS
// ============================================================================
const formCreateMes = ref(false);
const formCreateGestion = ref(false);
const formEdit = ref(false);
const formEditMes = ref(false);
const showModal = ref(false);
const reportes = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedItem = ref(null);
const selectedId = ref(null);
const selectedMes = ref(null);
const mensajes = ref([]);
const mostrarModalResultadosMes = ref(false);
const resultadosMes = ref(null);

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - GESTIÓN
// ============================================================================
const gestionFields = [{
    name: 'gestion',
    label: '',
    hidden: true
},
{
    typeInput: 'text',
    name: 'presupuesto_anual',
    label: 'Presupuesto Anual (Bs.)',
    type: 'number',
    required: true,
    placeholder: 'el monto a pagar',
    readonly: false
}
];

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

/**
 * Obtiene el valor numérico del año desde diferentes formatos
 * @param {number|Object} yearData - Año como número o objeto con propiedad 'gestion'
 * @returns {number} Año como número
 */
const getYearValue = (yearData) => {
    if (typeof yearData === 'object' && yearData?.gestion) {
        return yearData.gestion;
    }
    return yearData || new Date().getFullYear();
}

const selectedYear = computed(() => getYearValue(filters.value?.año));

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - MES
// ============================================================================
const mesFields = [{
    name: 'mes',
    label: '',
    hidden: true
},
{
    typeInput: 'text',
    name: 'monto',
    label: 'Monto - Persona (Bs.)',
    type: 'number',
    required: true,
    placeholder: 'el monto a pagar',
    readonly: false
},
{
    name: 'presupuesto',
    label: '',
    type: 'number',
    required: false,
    placeholder: 'el presupuesto total',
    readonly: false
},
{
    name: 'archivo_excel',
    label: `Archivo Excel aprobados ${getMonthNameFromNumber(mes_actual_disponible.value)}`,
    typeInput: 'file_upload',
    placeholder: 'Selecciona un archivo Excel',
    acceptedTypes: '.xlsx,.xls',
    maxSize: 5,
    required: true,
    requiredColumns: [
        'N°',
        'C.I.',
        'APELLIDOS Y NOMBRES. P.C.D.',
        'GRADO DE DISCAPACIDAD',
        'MONTO A PAGAR (BS.)',
        'OBSERVACIONES'
    ]
},
{
    name: 'id_gestion',
    label: '',
    hidden: true
}
];

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - EDITGESTIÓN
// ============================================================================
const gestionEditFields = [{
    typeInput: 'text',
    name: 'presupuesto_anual',
    label: 'Presupuesto Anual (Bs.)',
    type: 'number',
    required: true,
    placeholder: 'el presupuesto anual',
    readonly: false
}];

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - EDITMES
// ============================================================================
const mesEditFields = [{
    typeInput: 'text',
    name: 'monto',
    label: 'Monto - Persona (Bs.)',
    type: 'number',
    required: true,
    placeholder: 'el monto a pagar',
    readonly: false
},
{
    name: 'presupuesto',
    label: '',
    type: 'number',
    required: false,
    placeholder: 'el presupuesto total',
    readonly: false
}
];

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
const existe = () => {
    mostrarMensaje('advertencia', 'Mes existente', 'El mes seleccionado ya existe.');
}

const mensajeDelante = () => {
    mostrarMensaje('error', 'Mes inválido', 'No se pueden registrar meses posteriores al actual.');
}

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
// FUNCIONES - NAVEGACIÓN Y FILTRADO
// ============================================================================

/**
 * Selecciona una gestión y actualiza la vista
 * @param {number|Object} year - Año a seleccionar
 */
const selectGestion = (year) => {
    const yearValue = getYearValue(year);
    router.get(route('gestion.index'), {
        año: yearValue,
        buscador: filters.value?.buscador || ''
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: false
    })
}

/**
 * Obtiene el nombre del mes desde su número
 * @param {number|string} monthNumber - Número del mes (1-12)
 * @returns {string} Nombre del mes en español
 */
function getMonthNameFromNumber(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Convertir a número entero
    const index = parseInt(monthNumber, 10) - 1;

    // Validar que esté en rango 0–11
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

/**
 * Obtiene el mes y año actuales del sistema
 * @returns {Object} Objeto con propiedades month y year
 */
const getCurrentMonthYear = () => {
    const date = new Date()
    return {
        month: date.getMonth() + 1, // getMonth() devuelve 0-11, sumamos 1
        year: date.getFullYear()
    }
}

/**
 * Verifica si una fecha corresponde al mes y año actual
 * @param {number} year - Año a verificar
 * @param {number} month - Mes a verificar
 * @returns {boolean} true si coincide con la fecha actual
 */
const verifyDate = (year, month) => {
    const current = getCurrentMonthYear()

    if (year && month && current.month === month && current.year === year) {
        return true
    } else {
        return false
    }
}

/**
 * Formatea un monto como moneda boliviana
 * @param {number} amount - Monto a formatear
 * @returns {string} Monto formateado con 2 decimales
 */
const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
};

/**
 * Abre el formulario para crear una nueva gestión
 */
const openFormGestion = () => {
    formCreateGestion.value = true;
}

/**
 * Abre el formulario para crear un nuevo mes
 */
const openFormCreate = () => {
    formCreateMes.value = true;
}

/**
 * Abre el formulario para editar la gestión actual
 */
const openFormEdit = () => {
    selectedId.value = añoSeleccionado.value.id_gestion;
    selectedItem.value = añoSeleccionado.value;
    formEdit.value = true;
}

// ============================================================================
// FUNCIONES - REPORTES
// ============================================================================

/**
 * Formulario para registrar logs de reportes
 */
const Log = useForm({
    descripcion: 'Impresión de reporte de pagos anuales'
});

/**
 * Genera y descarga el informe de pagos anuales
 */
const generearInforme = () => {
    reportes.value = true;
    Log.get(route('pago.reporteLog'));
}

// ============================================================================
// FUNCIONES - CALLBACKS
// ============================================================================

/**
 * Maneja la acción exitosa de agregar un registro
 */
const handleAdd = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload();
    formCreateGestion.value = false;
    formCreateMes.value = false;
}

/**
 * Maneja la acción exitosa de actualizar un registro
 */
const handleUpdate = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'Se ha editado correctamente.');
    router.reload();
    formEditMes.value = false;
    formEdit.value = false;
}

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Abre el modal con datos de la gestion
 * @param {Object} datos - Datos la gestion
 */
const openModalMes = (item) => {
    selectedItem.value = { ...item };
    showModal.value = true;
}

/**
 * Abre el formulario de edicion del mes
 * @param {number|string} idMes - ID del mes
 * @param {string} item - Nombre del mes
 * @param {number} mes - mes
 */
const openEditMes = (idMes, monto, presupuesto) => {
    showModal.value = false;
    selectedItem.value = {
        monto: monto,
        presupuesto: presupuesto
    };
    selectedId.value = idMes;
    formEditMes.value = true;
}

/**
 * Cierra todos los formularios y modales
 */
const closeForm = () => {
    formEdit.value = false;
    formCreateMes.value = false;
    formEditMes.value = false;
    formCreateGestion.value = false;
    showModal.value = false;
}

// Función cerrar
const cerrarModalResultadosMes = () => {
    mostrarModalResultadosMes.value = false;
    resultadosMes.value = null;
};

// Watcher
watch(
    () => page.props.flash?.resultadosMes,
    (newValue) => {
        if (newValue) {
            resultadosMes.value = newValue;
            mostrarModalResultadosMes.value = true;
        }
    },
    { immediate: true, deep: true } // Añade deep: true
);
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
            <!-- FORMULARIOS - GESTIÓN -->
            <!-- ============================================================================ -->

            <!-- Formulario: Crear gestión -->
            <Transition name="fade">
                <Form v-if="formCreateGestion" :fields="gestionFields"
                    :data="{ tipo: 'gestion', gestion: año_actual.añoActualSistema }" :dataGestion="años_registrados"
                    submit-label="Registrar nuevo Mes" submit-route="gestion.store" @add="handleAdd" @mesExiste="existe"
                    @mesDelante="mensajeDelante" @cancel="closeForm">
                    <template #icon>
                        <Icon :icon-button="true" name="calendarPlus" class-name="text-white" />
                    </template>
                    <template #label1>
                        Agregar Gestión {{ año_actual.añoActualSistema }}
                    </template>
                    <template #label2>
                        Complete la información para esta gestión
                    </template>
                </Form>
            </Transition>

            <!-- Formulario: Editar gestión -->
            <Transition name="fade">
                <Form v-if="formEdit" :fields="gestionEditFields" boton-name="Guardar"
                    :existing-data="selectedItem || {}" :idFor="selectedId" :edit-mode="true"
                    submit-route="gestion.update" @add="handleUpdate" @cancel="closeForm">
                    <template #icon>
                        <Icon :icon-button="true" name="calendarEdit" class-name="text-white" />
                    </template>
                    <template #label1>
                        Editar gestión {{ selectedYear }}
                    </template>
                    <template #label2>
                        Edite el presupuesto para esta gestión
                    </template>
                </Form>
            </Transition>

            <!-- ============================================================================ -->
            <!-- FORMULARIOS - GESTIÓN -->
            <!-- ============================================================================ -->

            <!-- Formulario: Crear mes -->
            <Transition name="fade">
                <Form v-if="formCreateMes" :data="{ tipo: 'gestion', id: año_actual.id, mes: mes_actual_disponible }"
                    :fields="mesFields" :dataGestion="años_registrados" :presupuestosAnuales="presupuestosAnuales"
                    :personasValidas="total_personas_validas" submit-label="Registrar nuevo Mes"
                    submit-route="gestion.addMes" :importing="importando" importing-text="Importando archivo..."
                    boton-name="Agregar" @add="handleAdd" @mesExiste="existe" @mesDelante="mensajeDelante"
                    @cancel="closeForm">
                    <template #icon>
                        <Icon :icon-button="true" name="calendarPlus" class-name="text-white" />
                    </template>
                    <template #label1>
                        Agregar Mes - {{ getMonthNameFromNumber(mes_actual_disponible) }} {{ año_actual.añoActualSistema
                        }}
                    </template>
                    <template #label2>
                        Complete la información para este mes
                    </template>
                </Form>
            </Transition>

            <!-- Formulario: Editar mes -->
            <Transition name="fade">
                <Form v-if="formEditMes" :fields="mesEditFields" boton-name="Guardar"
                    :existing-data="selectedItem || {}" :idFor="selectedId" :personasValidas="total_personas_validas"
                    :edit-mode="true" submit-route="gestion.updateMes" @add="handleUpdate" @cancel="closeForm">
                    <template #icon>
                        <Icon :icon-button="true" name="calendarEdit" class-name="text-white" />
                    </template>
                    <template #label1>
                        Editar Mes de {{ getMonthNameFromNumber(selectedMes) }} - {{ año_actual.añoActualSistema }}
                    </template>
                    <template #label2>
                        Modifique monto y presupuesto del mes
                    </template>
                </Form>
            </Transition>

            <!-- ============================================================================ -->
            <!-- MODALES -->
            <!-- ============================================================================ -->

            <!-- Modal: Visualizar datos mes -->
            <Transition name="fade">
                <ModalGestion v-if="showModal" :user="selectedId" :data="selectedItem" @close="closeForm" />
            </Transition>

            <Reporte v-if="reportes" :datos="gestion" :gestion="gestion.gestion" :download="true" tipoR="Gestión"
                :tipo="false" style="display: none;" />

            <ModalResultadosMes v-model="mostrarModalResultadosMes" :datos="resultadosMes || {}"
                @close="cerrarModalResultadosMes" />

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-5 py-3 flex justify-between">
                <h1 class="font-semibold text-2xl">Gestiones</h1>
                <Rutas label1="Inicio" label3="Gestiones" />
            </div>

            <!-- ============================================================================ -->
            <!-- BARRA DE HERRAMIENTAS -->
            <!-- ============================================================================ -->
            <div
                class="grid grid-cols-4 items-center gap-6 px-4 py-4 border-b bg-white border-t mr-1 border-x border-gray-300 rounded-t-xl shadow-sm">
                <!-- Columna Izquierda: Dropdown de Años -->

                <div v-if="!gestionData && can('agregar-gestion')" class="flex justify-center lg:col-span-1">
                    <button @click="openFormGestion()"
                        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 rounded-lg transition-colors duration-150 shadow-sm dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                            </svg>
                        </div>
                        <span>Crear Gestión</span>
                    </button>
                </div>
                <div v-else class="flex justify-self-center lg:col-span-1">
                    <h1
                        class="text-3xl p-1.5 font-bold text-slate-800 text-center tracking-tight bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                        GESTIÓN
                    </h1>
                    <Dropdown align="left" width="60">
                        <template #trigger>
                            <button
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow"
                                type="button">
                                <span class="text-xl font-semibold text-slate-700">
                                    {{ selectedYear || 'Seleccionar' }}
                                </span>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                                <span
                                    v-if="btnAgregar === false && can('agregar-gestion')"
                                    class="absolute top-1 right-1 w-2 h-2 rounded-full bg-orange-400 z-20">
                                    <span
                                        class="absolute inset-0 w-2 h-2 bg-orange-500 rounded-full opacity-75 animate-ping"></span>
                                </span>
                            </button>
                        </template>

                        <template #content>
                            <div
                                class="w-64 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl overflow-hidden shadow-xl border border-slate-200">
                                <!-- Lista scrolleable -->
                                <div
                                    class="max-h-60 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-transparent">
                                    <ul class="py-2">
                                        <li v-if="!gestiones || gestiones.length === 0" class="px-4 py-3">
                                            <div class="flex items-center gap-3 text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                </svg>
                                                <span class="text-sm">No hay datos disponibles</span>
                                            </div>
                                        </li>
                                        <li v-for="year in gestiones" :key="year.gestion">
                                            <a href="#" @click.prevent="selectGestion(year)"
                                                class="flex items-center justify-between px-4 py-3 text-sm hover:bg-blue-50 transition-all duration-150 group"
                                                :class="selectedYear.toString() === year.gestion.toString()
                                                    ? 'bg-blue-100 text-blue-800 font-semibold border-r-4 border-blue-500'
                                                    : 'text-slate-700 hover:text-blue-700'">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-500 transition-colors"
                                                        fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    {{ year.gestion }}
                                                </span>
                                                <svg v-if="selectedYear.toString() === year.gestion.toString()"
                                                    class="w-5 h-5 text-blue-600" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <!-- Botón para agregar nueva gestión (fuera del scroll) -->
                                <div v-if="btnAgregar === false && can('agregar-gestion')"
                                    class="border-t border-slate-200 p-3 bg-white">
                                    <button @click="openFormGestion()"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md active:scale-95">
                                        <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                            </svg>
                                        </div>
                                        <span>Agregar Gestión</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </Dropdown>
                </div>

                <!-- Columna Central: Información Principal -->
                <div class="justify-self-center text-center lg:col-span-2">
                    <div v-if="existe_gestion" class="space-y-1">
                        <div class="flex flex-wrap justify-center gap-2">
                            <!-- Presupuesto Inicial -->
                            <div
                                class="inline-flex items-center gap-2 px-3 py-0.5 bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm font-medium shadow-sm ">
                                <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="text-left">
                                    <div class="text-xs text-emerald-600 font-semibold uppercase tracking-wide">Inicial
                                    </div>
                                    <span class="text-sm font-bold text-emerald-800">
                                        Bs. {{ formatCurrency(añoSeleccionado.presupuesto_anual || 0) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Presupuesto Utilizado -->
                            <div
                                class="inline-flex items-center gap-2 px-3 py-0.5 bg-gradient-to-r from-rose-50 to-red-50 border border-rose-200 text-rose-700 rounded-lg text-sm font-medium shadow-sm ">
                                <svg class="w-4 h-4 flex-shrink-0 text-rose-500" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="text-left">
                                    <div class="text-xs text-rose-600 font-semibold uppercase tracking-wide">Utilizado
                                    </div>
                                    <span class="text-sm font-bold text-rose-800">
                                        Bs. {{ formatCurrency(sumaPresupuestoMensual || 0) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Presupuesto Disponible -->
                            <div
                                class="inline-flex items-center gap-2 px-3 py-0.5 bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 text-sky-700 rounded-lg text-sm font-medium shadow-sm ">
                                <svg class="w-4 h-4 flex-shrink-0 text-sky-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="text-left">
                                    <div class="text-xs text-sky-600 font-semibold uppercase tracking-wide">Disponible
                                    </div>
                                    <span class="text-sm font-bold text-sky-800">
                                        Bs. {{ formatCurrency((añoSeleccionado.presupuesto_anual || 0) -
                                            (sumaPresupuestoMensual ||
                                                0)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Columna Derecha: Sección Autenticada -->
                <!-- Botones de acción -->
                <div class="justify-self-end lg:col-span-1 relative flex gap-2">
                    <!-- Botón: Agregar -->
                    <Button
                        v-if="can('agregar-mes') && año_actual.id === null ? false : (mes_actual_disponible !== 0 ? true : false)"
                        id="btn-agregar" @click.prevent="openFormCreate()"
                        @mouseenter="showTooltip('Agregar', 'btn-agregar')" @mouseleave="hideTooltip"
                        :style="'px-3 py-3 pb-2 rounded-full border-none'"
                        class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                        <!-- Efecto de fondo desde el centro -->
                        <span
                            class="absolute inset-0 bg-blue-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                        <!-- Icono -->
                        <span class="relative z-10">
                            <Icon :icon-button="true" name="calendarPlus" fill="currentColor" :ripple="true" ripple-color="bg-orange-700"
                                class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                        </span>
                    </Button>

                    <!-- Botón: Editar -->
                    <Button v-if="can('editar-gestion') && existe_gestion ? true : false" id="btn-editar"
                        @click.prevent="openFormEdit()" @mouseenter="showTooltip('Editar', 'btn-editar')"
                        @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                        class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                        <!-- Efecto de fondo desde el centro -->
                        <span
                            class="absolute inset-0 bg-gray-600 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                        <!-- Icono -->
                        <span class="relative z-10">
                            <Icon :icon-button="true" name="calendarEdit" fill="currentColor"
                                class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                        </span>
                    </Button>

                    <!-- Botón: Generar PDF -->
                    <Button v-if="can('reporte-gestion') && existe_gestion && tiene_meses && gestion.length > 0"
                        id="btn-reporte" @click.prevent="generearInforme()"
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
            <main
                class="flex-1 overflow-x-hidden bg-white overflow-y-auto border-b border-x border-gray-300 rounded-b-lg mr-1">
                <div v-if="existe_gestion && tiene_meses && gestion.length > 0" class="p-2 h-full">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                        <div v-for="item in gestion" :key="item.id_mes" class="w-full">
                            <form @submit.prevent="submit" @click.prevent="openModalMes(item)"
                                class="cursor-pointer dark:bg-gray-800 rounded-xl border p-3 hover:border-gray-300 border-gray-400 dark:border-gray-700 dark:hover:border-gray-600 transition-all duration-200 h-32">
                                <div class="flex flex-col h-full justify-between">
                                    <!-- Header -->
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            {{ getMonthNameFromNumber(item.mes) }}
                                        </h3>

                                        <div class="flex items-center space-x-2">
                                            <!-- Status Badge with Animation -->
                                            <div class="relative">
                                                <div :class="{
                                                    'px-4 py-1.5 rounded-lg text-xs font-medium shadow-sm text-center relative overflow-hidden': true,
                                                    'bg-green-100 text-green-800 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-600': item.cantidad_habilitadas === item.cantidad_total_pagos,
                                                    'bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-600': verifyDate(item.gestion, item.mes) && item.cantidad_habilitadas !== item.cantidad_total_pagos,
                                                    'bg-yellow-100 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-600': !verifyDate(item.gestion, item.mes) && item.cantidad_habilitadas !== item.cantidad_total_pagos
                                                }">
                                                    <span class="relative z-10">
                                                        {{
                                                            item.cantidad_habilitadas === item.cantidad_total_pagos ?
                                                                'Completo' :
                                                                verifyDate(item.gestion, item.mes) ? 'En Proceso' :
                                                                    'Pendiente'
                                                        }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Edit Button -->
                                            <div v-if="can('editar-mes')" class="pb-0">
                                                <Icon
                                                    @click.stop.prevent="openEditMes(item.id_mes, item.monto, item.presupuesto)"
                                                    name="calendarEdit" class-name="text-gray-500" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Monto - Persona:</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-200">Bs. {{
                                                formatCurrency(item.monto) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Presupuesto:</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-200">Bs. {{
                                                formatCurrency(item.presupuesto) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center w-full py-12 px-4 mt-10">
                    <!-- Icono -->
                    <div class="mb-6">
                        <Icon :icon-button="true" name="calendarMontSolid" class-name="text-gray-400 dark:text-gray-500"
                            :size="64" :height="64" />
                    </div>

                    <!-- Textos -->
                    <div class="text-center space-y-2 max-w-md">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            <template v-if="!gestionData">
                                No hay gestión registrada
                            </template>
                            <template v-else-if="!tiene_meses">
                                Sin meses registrados
                            </template>
                            <template v-else>
                                No se encontraron datos
                            </template>
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <template v-if="!gestionData">
                                No se ha registrado una gestión para el año {{ selectedYear }}. Por favor, cree una
                                nueva gestión para comenzar.
                            </template>
                            <template v-else-if="!tiene_meses">
                                La gestión {{ selectedYear }} no tiene meses registrados. Agregue meses para visualizar
                                la información.
                            </template>
                            <template v-else>
                                Agregue meses para comenzar a visualizar la información aquí.
                            </template>
                        </p>
                    </div>
                </div>
            </main>

            <!-- ============================================================================ -->
            <!-- FOOTER Y PAGINACIÓN -->
            <!-- ============================================================================ -->
            <div class="mt-1">
                <Footer />
            </div>
        </div>
    </div>
</template>
