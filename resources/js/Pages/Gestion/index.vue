<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import ModalGestion from '@/components/ModalGestion.vue';
import RetroactivoMesCard from '@/components/RetroactivoMesCard.vue';
import Dropdown from '@/components/Dropdown.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';
import Form from '@/components/Form/Form.vue';

/**
 * Composables
 */
import { useReporteArqueoGeneralPDF } from '@/composables/useReporteArqueoGeneralPDF';
import { useReportePagadosPDF } from '@/composables/useReportePagadosPDF';
import { Preloader } from '@/composables/usePreloader';

/**
 * Utilidades
 */
import AppLayout from '@/Layouts/AppLayout.vue';
import { can } from '@/lib/can';

// ============================================================================
// INICIALIZACIÓN DE COMPOSABLES
// ============================================================================
const { generarReporte } = useReporteArqueoGeneralPDF();
const { generarReporte: generarReportePagados } = useReportePagadosPDF();

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const gestiones = computed(() => page.props.gestiones);
const gestion = computed(() => page.props.gestion);
const diciembreAnterior = computed(() => page.props.diciembre_anterior ?? null);
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
const montoPersonaActual = computed(() => page.props.montoPersonaActual);
const selectedYear = computed(() => getYearValue(filters.value?.año));
const gestion_para_mes = computed(() => page.props.gestion_para_mes ?? { id: null, año: null });
const ultimo_mes_creado = computed(() => page.props.ultimo_mes_creado ?? null);

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const showModal = ref(false);

// ============================================================================
// REFS - ESTADO DE FORMULARIOS
// ============================================================================
const formCreateMes = ref(false);
const formCreateGestion = ref(false);
const formEdit = ref(false);
const formEditMes = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedItem = ref(null);
const selectedId = ref(null);
const selectedGestion = ref(null);
const selectedMes = ref(null);
const selectedTienePagos = ref(false);
const mensajes = ref([]);
const retroactivoEstado = ref(null);
const toggleandoRetro = ref(false);
const cerrandoCaja = ref(false);

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

// ============================================================================
// REFS - ESTADO UI
// ============================================================================
const importando = ref(false);
const showMobileMenu = ref(false);

// ============================================================================
// PRELOADER — "Agregar Mes" (validación, verificaciones, inserción de datos)
// ============================================================================
// La subida es una única petición HTTP; como el backend no manda progreso
// intermedio, se van mostrando estos mensajes en secuencia mientras se
// espera la respuesta, para reflejar aproximadamente en qué etapa va.
const MENSAJES_CARGA_MES = [
    'Validando información del mes...',
    'Verificando registros existentes...',
    'Generando datos del período...',
    'Guardando cambios...',
];

let intervaloPreloaderMes = null;

const iniciarPreloaderMes = () => {
    let paso = 0;
    Preloader.show(MENSAJES_CARGA_MES[0]);
    intervaloPreloaderMes = setInterval(() => {
        paso = Math.min(paso + 1, MENSAJES_CARGA_MES.length - 1);
        Preloader.setMessage(MENSAJES_CARGA_MES[paso]);
    }, 1800);
};

const detenerPreloaderMes = () => {
    if (intervaloPreloaderMes) {
        clearInterval(intervaloPreloaderMes);
        intervaloPreloaderMes = null;
    }
    Preloader.hide();
};

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - GESTIÓN
// ============================================================================
const gestionFields = [
    {
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

const puedeAgregarMes = computed(() =>
    mes_actual_disponible.value !== 0 &&
    Number(gestion_para_mes.value.año) === Number(selectedYear.value) &&
    !añoSeleccionado.value?.caja_cerrada
);

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - EDITAR GESTIÓN
// ============================================================================
const gestionEditFields = [
    {
        typeInput: 'text',
        name: 'presupuesto_anual',
        label: 'Presupuesto Anual (Bs.)',
        type: 'number',
        required: true,
        placeholder: 'el presupuesto anual',
        readonly: false
    }
];

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - MES (EXCEL)
// ============================================================================
const mesFieldsExcel = [
    {
        name: 'mes',
        label: '',
        hidden: true
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
// CONFIGURACIÓN DE CAMPOS - MES (PDF)
// ============================================================================
const mesFieldsPdf = [
    {
        name: 'mes',
        label: '',
        hidden: true
    },
    {
        name: 'archivo_pdf',
        label: `Archivo PDF aprobados ${getMonthNameFromNumber(mes_actual_disponible.value)}`,
        typeInput: 'file_upload',
        placeholder: 'Arrastra tu archivo aquí o haz clic para seleccionar',
        acceptedTypes: '.pdf',
        maxSize: 5,
        required: true,
        requiredColumns: []
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
        name: 'id_gestion',
        label: '',
        hidden: true
    }
];

// Cambiar a mesFieldsExcel si se requiere importación por Excel
const mesFields = mesFieldsPdf;

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - EDITAR MES
// ============================================================================
const mesEditFields = computed(() => [
    {
        typeInput: selectedTienePagos.value ? 'texto' : 'number',
        name: 'monto',
        label: 'Monto - Persona (Bs.)',
        required: !selectedTienePagos.value,
        placeholder: 'el monto a pagar',
        readonly: false,
        message: 'Este mes ya tiene pagos registrados. El monto no puede modificarse.',
        messageType: 'warning'
    },
    {
        typeInput: 'text',
        name: 'presupuesto',
        label: '',
        type: 'number',
        required: false,
        placeholder: 'el presupuesto total',
        readonly: false,
        range: 20,
    }
]);

/**
 * Recarga el estado del proceso de retroactivos (siguiente posición
 * disponible, meses-retro ya creados) cada vez que cambia la gestión
 * seleccionada.
 */
const cargarEstadoRetroactivo = async () => {
    const idGestion = añoSeleccionado.value?.id_gestion;

    if (!idGestion || !tiene_meses.value) {
        retroactivoEstado.value = null;
        return;
    }

    try {
        const { data } = await axios.get(route('gestion.retroactivo.estado'), {
            params: { id_gestion: idGestion },
        });
        retroactivoEstado.value = data;
    } catch (e) {
        retroactivoEstado.value = null;
    }
};

watch(
    () => añoSeleccionado.value?.id_gestion,
    () => cargarEstadoRetroactivo(),
    { immediate: true }
);

/**
 * Activa/desactiva el apartado de carga de retroactivos para la gestión
 * seleccionada. Switch único por gestión (no por mes) — solo superusuario.
 */
const toggleRetroactivos = async () => {
    const idGestion = añoSeleccionado.value?.id_gestion;

    if (!idGestion || toggleandoRetro.value) return;

    toggleandoRetro.value = true;
    try {
        const { data } = await axios.post(route('gestion.retroactivo.toggle'), {
            id_gestion: idGestion,
        });
        page.props.añoSeleccionado.retroactivos_habilitado = data.retroactivos_habilitado;
        await cargarEstadoRetroactivo();
    } catch (e) {
        mostrarMensaje('error', 'No se pudo actualizar', 'Ocurrió un error al cambiar el estado de retroactivos. Intente nuevamente.');
    } finally {
        toggleandoRetro.value = false;
    }
};

/**
 * Cierra la caja de la gestión seleccionada: a partir de ese momento, ningún
 * pago/anulación/habilitado/retroactivo/edición de mes de esa gestión puede
 * volver a registrarse (ver Gestion::estaCerrada() en el backend). Acción
 * irreversible desde la UI — reservada a superusuario.
 */
const cerrarCaja = () => {
    const idGestion = añoSeleccionado.value?.id_gestion;

    if (!idGestion || cerrandoCaja.value || añoSeleccionado.value?.caja_cerrada) return;

    if (!window.confirm(
        `¿Cerrar la caja de la gestión ${añoSeleccionado.value.gestion}? ` +
        'Ya no se podrán registrar pagos, anulaciones, habilitados ni retroactivos de esta gestión.'
    )) {
        return;
    }

    cerrandoCaja.value = true;
    router.post(route('gestion.cerrarCaja', idGestion), {}, {
        preserveScroll: true,
        onSuccess: () => {
            mostrarMensaje('correcto', 'Caja cerrada', `La gestión ${añoSeleccionado.value.gestion} quedó cerrada.`);
        },
        onError: () => {
            mostrarMensaje('error', 'No se pudo cerrar', 'Ocurrió un error al cerrar la caja. Intente nuevamente.');
        },
        onFinish: () => {
            cerrandoCaja.value = false;
        },
    });
};

/**
 * Lista de cards a mostrar: antepone el card de diciembre de la gestión
 * anterior (si existe) a los meses reales de la gestión seleccionada. Es
 * puramente visual — el card de diciembre conserva su id_gestion real (el
 * de la gestión anterior), no se reasigna a la gestión actual.
 */
const gestionConDiciembreAnterior = computed(() => {
    if (!diciembreAnterior.value) return gestion.value;
    return [{ ...diciembreAnterior.value, esDiciembreAnterior: true }, ...gestion.value];
});

// ============================================================================
// FUNCIONES - UTILIDADES
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
};

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
    const index = parseInt(monthNumber, 10) - 1;
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
    const date = new Date();
    return {
        month: date.getMonth() + 1,
        year: date.getFullYear()
    };
};

/**
 * Verifica si un mes es el último mes creado en todo el sistema
 * (sin importar la gestión/año que se esté visualizando)
 * @param {number} year - Año del item a verificar
 * @param {number} month - Mes del item a verificar
 * @returns {boolean} true si es el último mes creado globalmente
 */
const verifyDate = (year, month) => {
    if (!ultimo_mes_creado.value) return false;
    return ultimo_mes_creado.value.gestion === year && ultimo_mes_creado.value.mes === month;
};

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
const existe = () => {
    mostrarMensaje('advertencia', 'Mes existente', 'El mes seleccionado ya existe.');
};

const mensajeDelante = () => {
    mostrarMensaje('error', 'Mes inválido', 'No se pueden registrar meses posteriores al actual.');
};

// ============================================================================
// WATCHERS
// ============================================================================
// Resultado de cargar un PDF de mes: ya no abre un modal aparte (el detalle
// completo se revisa ANTES de subir, en el modal de vista previa) — acá solo
// se confirma que se guardó, y si quedaron observaciones (ej. CI repetido
// con nombre distinto) se avisa que están documentadas en la Bitácora.
watch(
    () => page.props.flash?.resultadosMes,
    (datos) => {
        if (!datos) return;

        const pendientes = (datos.errores?.length || 0) + (datos.observaciones?.length || 0);

        if (pendientes > 0) {
            mostrarMensaje(
                'advertencia',
                'Mes cargado con observaciones',
                `Se habilitaron ${datos.habilitados?.length || 0} beneficiarios. Quedaron ${pendientes} caso(s) que requieren revisión manual — consultá la Bitácora para el detalle.`
            );
        } else {
            mostrarMensaje(
                'correcto',
                'Mes cargado correctamente',
                `Se habilitaron ${datos.habilitados?.length || 0} beneficiarios.`
            );
        }
    },
    { immediate: true, deep: true }
);

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
 * Oculta el tooltip activo
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
    });
};

const primerAñoRegistrado = computed(() => {
    if (!años_registrados.value || años_registrados.value.length === 0) return null;
    return Math.min(...años_registrados.value.map(Number));
});

const puedeGenerarReporte = computed(() =>
    existe_gestion.value &&
    tiene_meses.value &&
    gestion.value.length > 0 &&
    primerAñoRegistrado.value !== null &&
    Number(selectedYear.value) > primerAñoRegistrado.value
);

// ============================================================================
// FUNCIONES - REPORTES
// ============================================================================

/**
 * Formulario para registrar logs de reportes generados
 */
const Log = useForm({
    gestion: '',
    total_meses: '',
    tipo: 'arqueo_general',
});

/**
 * Arma los datos por mes (ciclo completo, con diciembre "prestado" de la
 * gestión anterior si corresponde) — lo usan tanto el PDF como el Excel de
 * Arqueo General, para que ambos muestren exactamente el mismo modelo.
 */
const prepararDatosArqueoGeneral = async () => {
    let diciembrePrestado = null;
    try {
        const { data } = await axios.get(route('gestion.diciembrePrestado', selectedYear.value));
        diciembrePrestado = data;
    } catch (e) {
        diciembrePrestado = null; // si no existe la gestión anterior, simplemente no se agrega
    }

    const mesesCompletos = diciembrePrestado
        ? [diciembrePrestado, ...gestion.value]
        : [...gestion.value];

    return mesesCompletos.map(item => ({
        ...item,
        presupuesto_mes: item.monto,
        total_pagado_contexto: item.total_pagado ?? 0,
        cantidad_habilitadas: item.cantidad_habilitadas ?? 0,
        cantidad_total_pagos: item.cantidad_total_pagos ?? 0,
        cantidad_no_pagados: item.cantidad_no_pagados ?? 0,
    }));
};

const registrarLogArqueoGeneral = () => {
    Log.gestion = selectedYear.value;
    Log.total_meses = gestion.value.length;
    Log.get(route('pago.reporteLog'));
};

/**
 * Genera y descarga el informe de arqueo general de pagos anuales, en PDF
 */
const generarInforme = async () => {
    registrarLogArqueoGeneral();
    const nombreUsuario = `${page.props.auth.user.nombre} ${page.props.auth.user.apellido}`;
    const datosParaPDF = await prepararDatosArqueoGeneral();
    generarReporte(datosParaPDF, nombreUsuario, 'ArqueoGeneral', selectedYear.value);
};

/**
 * Genera el mismo informe de arqueo general, pero en Excel — mismo modelo
 * que el PDF (ReporteArqueoGeneralExport.php reproduce las mismas columnas
 * y totales que useReporteArqueoGeneralPDF.js).
 */
const generarInformeExcel = async () => {
    registrarLogArqueoGeneral();
    const datos = await prepararDatosArqueoGeneral();

    const response = await axios.post(route('gestion.reporteArqueoGeneral.excel'), {
        gestion: selectedYear.value,
        datos,
    }, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    // Mismo patrón de nombre que el PDF (fechaArchivo() en useReporteArqueoGeneralPDF.js).
    const fechaArchivo = new Date().toLocaleString('sv-SE', { timeZone: 'America/La_Paz', hour12: false }).replace(/ /, '_').replace(/\..+/, '');
    link.download = `Reporte_ArqueoGeneral-${fechaArchivo}.xlsx`;
    link.click();
};

// ============================================================================
// FUNCIONES - FORMULARIOS
// ============================================================================

/**
 * Abre el formulario para crear una nueva gestión
 */
const openFormGestion = () => {
    formCreateGestion.value = true;
};

/**
 * Abre el formulario para crear un nuevo mes
 */
const openFormCreate = () => {
    formCreateMes.value = true;
};

/**
 * Abre el formulario para editar la gestión actual
 */
const openFormEdit = () => {
    selectedId.value = añoSeleccionado.value.id_gestion;
    selectedItem.value = añoSeleccionado.value;
    formEdit.value = true;
};

/**
 * Maneja la acción exitosa de agregar un registro
 */
const handleAdd = () => {
    // Agregar Mes ya muestra su propio mensaje (con el detalle real de
    // habilitados/observaciones) vía el watcher de flash.resultadosMes —
    // evita mostrar dos mensajes para la misma acción.
    if (!formCreateMes.value) {
        mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    }
    router.reload();
    formCreateGestion.value = false;
    formCreateMes.value = false;
};

/**
 * Maneja la acción exitosa de actualizar un registro
 */
const handleUpdate = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'Se ha editado correctamente.');
    router.reload();
    formEditMes.value = false;
    formEdit.value = false;
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Cache en memoria de personasPorMes(id_mes) — evita repetir el fetch si el
 * usuario genera el PDF y después el Excel del mismo mes, o abre el mismo
 * reporte dos veces. Se pierde al recargar la página, que es exactamente lo
 * que queremos (los datos pueden cambiar entre cargas).
 */
const cachePersonasPorMes = new Map();

const fetchPersonasPorMes = async (idMes) => {
    if (!idMes) return [];
    if (cachePersonasPorMes.has(idMes)) {
        return cachePersonasPorMes.get(idMes);
    }
    const { data } = await axios.get(route('gestion.mes.personas', idMes));
    cachePersonasPorMes.set(idMes, data);
    return data;
};

/**
 * Abre el modal con los datos del mes seleccionado. El modal en sí solo
 * muestra conteos agregados (ya vienen en `item`), no la lista de personas
 * — esa se pide aparte, on-demand, solo al generar un reporte (ver
 * datosReporteMes()).
 * @param {Object} item - Datos del mes
 */
const openModalMes = (item) => {
    selectedItem.value = { ...item };
    showModal.value = true;
};

/**
 * Formulario para registrar el log de generación de reporte
 */
const LogReporte = useForm({
    gestion: '',
    mes: '',
    total_pagados: '',
    total_no_pagados: '',
    monto_asignado: '',
    tipo: '',
});

/**
 * Resuelve los datos de un card para el reporte: la lista de personas del
 * mes normal, y si ese mes ya tiene su retroactivo cargado (solo aplica
 * enero-octubre), también la lista y el monto del retroactivo. Pide ambas
 * listas on-demand (ver fetchPersonasPorMes) en vez de leerlas de un prop
 * precargado con todos los meses de la gestión.
 * @param {Object} item - Datos del mes (card)
 */
const datosReporteMes = async (item) => {
    const mesReal = item.esDiciembreAnterior ? 12 : item.mes;

    const retroCorrespondiente = mesReal <= 10
        ? retroactivoEstado.value?.retros_creados?.find((r) => r.mes_original === mesReal) ?? null
        : null;
    const montoRetro = retroCorrespondiente?.monto ?? 0;

    Preloader.show('Cargando datos del mes...');
    try {
        const [datosNormal, datosRetro] = await Promise.all([
            fetchPersonasPorMes(item.id_mes),
            retroCorrespondiente ? fetchPersonasPorMes(retroCorrespondiente.id_mes) : Promise.resolve(null),
        ]);

        return { datosNormal, datosRetro, montoRetro };
    } finally {
        Preloader.hide();
    }
};

/**
 * Registra el log de generación de reporte para el mes indicado.
 * @param {Object} item - Datos del mes (card)
 */
const registrarLogReporte = (item) => {
    LogReporte.gestion = item.gestion;
    LogReporte.mes = getMonthNameFromNumber(item.mes);
    LogReporte.total_pagados = item.cantidad_total_pagos;
    LogReporte.total_no_pagados = item.cantidad_no_pagados;
    LogReporte.monto_asignado = item.presupuesto;
    LogReporte.tipo = 'arqueo_caja';
    LogReporte.get(route('pago.reporteLog'));
};

/**
 * Genera el PDF del mes: primero la planilla normal, y si ese mes ya tiene
 * su retroactivo cargado, agrega debajo (página aparte) la planilla del
 * retroactivo correspondiente — un solo archivo, ambos separados.
 * @param {Object} item - Datos del mes (card)
 */
const generarReporteMes = async (item) => {
    const { datosNormal, datosRetro, montoRetro } = await datosReporteMes(item);
    registrarLogReporte(item);
    generarReportePagados(datosNormal, item.gestion, item.mes, 'Pagados', item.monto, datosRetro, montoRetro);
};

/**
 * Genera el mismo reporte que generarReporteMes(), pero en Excel: una hoja
 * para el mes normal y, si corresponde, otra hoja aparte para el retro.
 * @param {Object} item - Datos del mes (card)
 */
const generarReporteExcelMes = async (item) => {
    const { datosNormal, datosRetro, montoRetro } = await datosReporteMes(item);
    registrarLogReporte(item);

    const response = await axios.post(route('gestion.reporteMes.excel'), {
        gestion: item.gestion,
        mes: item.mes,
        monto: item.monto,
        datos: datosNormal,
        datos_retro: datosRetro,
        monto_retro: montoRetro,
    }, { responseType: 'blob' });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    // Mismo patrón de nombre que el PDF (fechaArchivo() en useReportePagadosPDF.js).
    const fechaArchivo = new Date().toLocaleString('sv-SE', { timeZone: 'America/La_Paz', hour12: false }).replace(/ /, '_').replace(/\..+/, '');
    link.download = `Reporte_Pagados-${fechaArchivo}.xlsx`;
    link.click();
};

/**
 * Abre el formulario de edición del mes seleccionado
 * @param {number|string} idMes - ID del mes
 * @param {number} monto - Monto actual del mes
 * @param {number} presupuesto - Presupuesto actual del mes
 * @param {number} mes - Número del mes
 * @param {number} gestion - Año de la gestión
 * @param {number} cantidadPagos - Cantidad de pagos registrados en el mes
 */
const openEditMes = (idMes, monto, presupuesto, mes, gestion, cantidadPagos) => {
    showModal.value = false;
    selectedItem.value = {
        monto: monto,
        presupuesto: presupuesto
    };
    selectedId.value = idMes;
    selectedMes.value = mes;
    selectedGestion.value = gestion;
    selectedTienePagos.value = cantidadPagos > 0;
    formEditMes.value = true;
};

/**
 * Cierra todos los formularios y modales activos
 */
const closeForm = () => {
    formEdit.value = false;
    formCreateMes.value = false;
    formEditMes.value = false;
    formCreateGestion.value = false;
    showModal.value = false;
};

// ============================================================================
// REFS Y FUNCIONES - MENÚ MÓVIL
// ============================================================================
const closeMobileMenu = () => {
    showMobileMenu.value = false;
};
</script>

<template>
    <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Gestiones</h1>
            <Rutas label1="Inicio" label3="Gestiones" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- BARRA DE HERRAMIENTAS -->
        <!-- ============================================================================ -->
        <div class="px-4 py-4 border-b bg-white border-t mr-1 border-x border-gray-300 rounded-t-xl shadow-sm">

            <!-- Fila principal: dropdown + botones de acción -->
            <div class="flex items-center justify-between gap-3">

                <!-- Izquierda: Crear Gestión o Dropdown de año -->
                <div class="flex items-center gap-2 min-w-0">
                    <div v-if="!gestionData && can('agregar-gestion')">
                        <button @click="openFormGestion()"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-700))] focus:ring-4 focus:ring-[rgb(var(--brand-300))] rounded-lg transition-colors duration-150 shadow-sm">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                            </svg>
                            <span class="hidden sm:inline">Crear Gestión</span>
                            <span class="sm:hidden">Crear</span>
                        </button>
                    </div>
                    <div v-else class="flex flex-wrap items-center gap-2">
                        <h1
                            class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent whitespace-nowrap">
                            GESTIÓN
                        </h1>
                        <Dropdown align="left" width="60">
                            <template #trigger="{ open }">
                                <button
                                    class="relative inline-flex items-center gap-2 px-2 py-2 sm:px-4 sm:py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow"
                                    type="button">
                                    <span class="text-lg sm:text-xl font-semibold text-slate-700">
                                        {{ selectedYear || 'Seleccionar' }}
                                    </span>
                                    <Icon :icon-button="true" name="angleDown"
                                        :class-name="`text-gray-400 transition-transform duration-300 ${open ? 'rotate-180' : 'rotate-0'}`"
                                        fill="none" stroke="currentColor" stroke-width="2" :size="17"
                                        @click="isOpen = !isOpen" />
                                    <span v-if="btnAgregar === false && can('agregar-gestion')"
                                        class="absolute top-1 right-1 w-2 h-2 rounded-full bg-orange-400 z-20">
                                        <span
                                            class="absolute inset-0 w-2 h-2 bg-orange-500 rounded-full opacity-75 animate-ping"></span>
                                    </span>
                                </button>
                            </template>
                            <template #content>
                                <div class="shadow-xl overflow-hidden">
                                    <ul class="py-1.5 max-h-60 overflow-y-auto">

                                        <!-- Crear la gestión del año actual del sistema si aún no existe -->
                                        <li v-if="btnAgregar === false && can('agregar-gestion')">
                                            <a href="#" @click.prevent="openFormGestion()"
                                                class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-blue-50 duration-150 group text-blue-600 font-medium border-b border-gray-100">
                                                <span class="flex items-center leading-none gap-1">
                                                    <Icon :icon-button="true" name="calendarPlus"
                                                        class-name="text-blue-500 block" fill="none"
                                                        stroke="currentColor" stroke-width="1" :size="18" />
                                                    Crear gestión {{ año_actual.añoActualSistema }}
                                                </span>
                                            </a>
                                        </li>

                                        <li v-if="!gestiones || gestiones.length === 0" class="px-4 py-3">
                                            <div class="flex items-center gap-3 text-rose-400">
                                                <Icon :icon-button="true" name="alertTriangle"
                                                    class-name="text-rose-400" fill="none" stroke="currentColor"
                                                    stroke-width="2" :size="20" />
                                                <span class="text-sm">No hay datos disponibles</span>
                                            </div>
                                        </li>
                                        <li v-for="year in gestiones" :key="year.gestion">
                                            <a href="#" @click.prevent="selectGestion(year)"
                                                class="flex items-center justify-between px-3 py-2 text-sm transition-colors hover:bg-blue-50 duration-150 group"
                                                :class="selectedYear && selectedYear.toString() === year.gestion.toString()
                                                    ? 'bg-blue-100 text-blue-800 font-semibold border-r-4 border-blue-500'
                                                    : 'text-slate-700 hover:text-blue-700'">
                                                <span class="flex items-center leading-none gap-1">
                                                    <Icon :icon-button="true" name="calendar"
                                                        class-name="text-slate-400 block" fill="none"
                                                        stroke="currentColor" stroke-width="1" :size="18" />
                                                    {{ year.gestion }}
                                                </span>
                                                <Icon
                                                    v-if="selectedYear && selectedYear.toString() === year.gestion.toString()"
                                                    :icon-button="true" name="check" class-name="text-blue-700"
                                                    :viewBox="'0 0 20 20'" :size="17" />
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </Dropdown>

                        <!-- Cierre de caja de la gestión: irreversible desde la UI, solo superusuario -->
                        <span v-if="añoSeleccionado?.caja_cerrada"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rose-300 bg-rose-50 text-rose-700 text-xs font-semibold shadow-sm"
                            title="Esta gestión ya no admite pagos, anulaciones, habilitados ni retroactivos.">
                            <Icon :icon-button="true" name="key" class-name="text-rose-500" :size="16" />
                            <span class="hidden sm:inline">Caja cerrada</span>
                        </span>
                        <button v-else-if="can('superusuario') && existe_gestion" type="button" @click="cerrarCaja"
                            :disabled="cerrandoCaja"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-rose-300 bg-white text-rose-600 hover:bg-rose-50 text-xs font-semibold shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                            title="Cerrar la caja de esta gestión (acción irreversible desde la interfaz)">
                            <Icon :icon-button="true" name="key" class-name="text-rose-500" :size="16" />
                            <span class="hidden sm:inline">Cerrar caja</span>
                        </button>

                        <!-- Switch: habilitar/deshabilitar apartado de retroactivos (solo superusuario) -->
                        <button v-if="can('superusuario')" type="button" @click="toggleRetroactivos"
                            :disabled="toggleandoRetro || añoSeleccionado?.caja_cerrada"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="añoSeleccionado?.retroactivos_habilitado
                                ? 'bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100'
                                : 'bg-gray-50 border-gray-300 text-gray-500 hover:bg-gray-100'" :title="añoSeleccionado?.retroactivos_habilitado
                                    ? 'Retroactivos habilitados para esta gestión — clic para deshabilitar'
                                    : 'Retroactivos deshabilitados para esta gestión — clic para habilitar'">
                            <span
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 flex-shrink-0"
                                :class="añoSeleccionado?.retroactivos_habilitado ? 'bg-amber-500' : 'bg-gray-300'">
                                <span
                                    class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                                    :class="añoSeleccionado?.retroactivos_habilitado ? 'translate-x-4' : 'translate-x-1'"></span>
                            </span>
                            <span class="text-xs font-semibold hidden sm:inline whitespace-nowrap">Retroactivos</span>
                        </button>
                    </div>
                </div>


                <!-- Fila secundaria: presupuestos (solo si existe gestión) -->
                <div class="hidden sm:contents">
                    <div v-if="existe_gestion" class="flex flex-wrap gap-2 justify-center sm:justify-start">
                        <!-- Presupuesto Inicial -->
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm font-medium shadow-sm">
                            <Icon :icon-button="true" name="cash" class-name="text-emerald-500" :size="20" />
                            <div>
                                <div
                                    class="text-xs text-emerald-600 font-semibold uppercase tracking-wide leading-none">
                                    Inicial
                                </div>
                                <span class="text-sm font-bold text-emerald-800">Bs. {{
                                    formatCurrency(añoSeleccionado.presupuesto_anual || 0) }}</span>
                            </div>
                        </div>

                        <!-- Presupuesto Utilizado -->
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-rose-50 to-red-50 border border-rose-200 text-rose-700 rounded-lg text-sm font-medium shadow-sm">
                            <Icon :icon-button="true" name="bajaTemporal" :viewBox="'0 0 20 20'"
                                class-name="text-rose-500" :size="20" />
                            <div>
                                <div class="text-xs text-rose-600 font-semibold uppercase tracking-wide leading-none">
                                    Utilizado
                                </div>
                                <span class="text-sm font-bold text-rose-800">Bs. {{
                                    formatCurrency(sumaPresupuestoMensual || 0)
                                    }}</span>
                            </div>
                        </div>

                        <!-- Presupuesto Disponible -->
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 text-sky-700 rounded-lg text-sm font-medium shadow-sm">
                            <Icon :icon-button="true" name="checkCircle" class-name="text-sky-500" :size="20" />
                            <div>
                                <div class="text-xs text-sky-600 font-semibold uppercase tracking-wide leading-none">
                                    Disponible
                                </div>
                                <span class="text-sm font-bold text-sky-800">Bs. {{
                                    formatCurrency((añoSeleccionado.presupuesto_anual || 0) -
                                        (sumaPresupuestoMensual ||
                                            0))
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Derecha: Botones de acción -->
                <div class="flex items-center gap-1.5 flex-shrink-0 relative">
                    <!-- MÓVIL: Botón "+" con dropdown -->
                    <div class="relative sm:hidden">
                        <Button @click.prevent="showMobileMenu = !showMobileMenu"
                            :style="'px-2 py-2 pb-0.5 rounded-full border-none'"
                            class="shrink-0 self-center bg-gray-200 relative overflow-hidden group">
                            <span
                                class="absolute inset-0 bg-[rgb(var(--brand-500))] rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                            <span class="relative z-10">
                                <Icon :icon-button="true" name="circlePlus"
                                    class-name="text-gray-600 group-hover:text-white transition-colors duration-500"
                                    :size="32" :height="31" />
                            </span>
                        </Button>

                        <Transition enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 -translate-y-3 scale-95"
                            enter-to-class="opacity-100 translate-y-0 scale-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 translate-y-0 scale-100"
                            leave-to-class="opacity-0 -translate-y-3 scale-95">
                            <div v-if="showMobileMenu"
                                class="absolute right-0 top-full mt-2 flex flex-col gap-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-2 z-50">

                                <Button v-if="can('agregar-mes') && puedeAgregarMes"
                                    @click.prevent="openFormCreate(); closeMobileMenu()"
                                    :style="'px-3 py-3 pb-2 rounded-full border-none'"
                                    class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                                    <span
                                        class="absolute inset-0 bg-[rgb(var(--brand-500))] rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                                    <span class="relative z-10">
                                        <Icon :icon-button="true" name="calendarPlus" fill="currentColor"
                                            class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                                    </span>
                                </Button>

                                <Button v-if="can('editar-gestion') && existe_gestion && !añoSeleccionado?.caja_cerrada ? true : false"
                                    @click.prevent="openFormEdit(); closeMobileMenu()"
                                    :style="'px-3 py-3 pb-2 rounded-full border-none'"
                                    class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                                    <span
                                        class="absolute inset-0 bg-gray-600 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                                    <span class="relative z-10">
                                        <Icon :icon-button="true" name="calendarEdit" fill="currentColor"
                                            class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                                    </span>
                                </Button>

                                <Dropdown v-if="can('reporte-gestion') && puedeGenerarReporte" align="left" width="48">
                                    <template #trigger>
                                        <Button id="btn-reporte" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                                            class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                                            <span
                                                class="absolute inset-0 bg-red-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                                            <span class="relative z-10">
                                                <Icon :icon-button="true" name="circlePlus" fill="currentColor"
                                                    class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                                            </span>
                                        </Button>
                                    </template>
                                    <template #content>
                                        <ul class="py-1.5 text-sm">
                                            <li>
                                                <a href="#" @click.prevent="generarInforme(); closeMobileMenu()"
                                                    class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <Icon :icon-button="true" name="filePDF" class-name="text-red-500"
                                                        :size="16" />
                                                    Reporte PDF
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" @click.prevent="generarInformeExcel(); closeMobileMenu()"
                                                    class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <Icon :icon-button="true" name="fileExcel"
                                                        class-name="text-emerald-600" :size="16" />
                                                    Reporte Excel
                                                </a>
                                            </li>
                                        </ul>
                                    </template>
                                </Dropdown>
                            </div>
                        </Transition>
                    </div>

                    <!-- DESKTOP sm+: Botones normales -->
                    <template class="hidden sm:contents">
                        <Button v-if="can('agregar-mes') && puedeAgregarMes" id="btn-agregar"
                            @click.prevent="openFormCreate()" @mouseenter="showTooltip('Agregar', 'btn-agregar')"
                            @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                            class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                            <span
                                class="absolute inset-0 bg-[rgb(var(--brand-500))] rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                            <span class="relative z-10">
                                <Icon :icon-button="true" name="calendarPlus" fill="currentColor"
                                    class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                            </span>
                        </Button>

                        <Button v-if="can('editar-gestion') && existe_gestion && !añoSeleccionado?.caja_cerrada ? true : false" id="btn-editar"
                            @click.prevent="openFormEdit()" @mouseenter="showTooltip('Editar', 'btn-editar')"
                            @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                            class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                            <span
                                class="absolute inset-0 bg-gray-600 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                            <span class="relative z-10">
                                <Icon :icon-button="true" name="calendarEdit" fill="currentColor"
                                    class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                            </span>
                        </Button>

                        <Dropdown v-if="can('reporte-gestion') && puedeGenerarReporte" align="right" width="48">
                            <template #trigger>
                                <Button id="btn-reporte" @mouseenter="showTooltip('Reporte Anual', 'btn-reporte')"
                                    @mouseleave="hideTooltip" :style="'px-3 py-3 pb-2 rounded-full border-none'"
                                    class="bg-gray-200 shrink-0 self-center relative overflow-hidden group">
                                    <span
                                        class="absolute inset-0 bg-red-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>
                                    <span class="relative z-10">
                                        <Icon :icon-button="true" name="circlePlus" fill="currentColor"
                                            class-name="text-gray-600 group-hover:text-white transition-colors duration-500" />
                                    </span>
                                </Button>
                            </template>
                            <template #content>
                                <ul class="py-1.5 text-sm">
                                    <li>
                                        <a href="#" @click.prevent="generarInforme()"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <Icon :icon-button="true" name="filePDF" class-name="text-red-500"
                                                :size="16" />
                                            Reporte PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" @click.prevent="generarInformeExcel()"
                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <Icon :icon-button="true" name="fileExcel" class-name="text-emerald-600"
                                                :size="16" />
                                            Reporte Excel
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </Dropdown>
                    </template>

                    <!-- Tooltip (solo desktop, ya no hace falta en móvil) -->
                    <div v-if="showTooltipFlag" ref="tooltipRef"
                        class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                        :style="tooltipStyle">
                        {{ tooltipText }}
                    </div>
                </div>
            </div>

            <!-- Fila secundaria: presupuestos (solo si existe gestión) -->
            <!-- MÓVIL: Botón "+" con dropdown -->
            <div v-if="existe_gestion" class="relative sm:hidden mt-3 pt-3 border-t border-gray-100">
                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                    <!-- Presupuesto Inicial -->
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm font-medium shadow-sm">
                        <Icon :icon-button="true" name="cash" class-name="text-emerald-500" :size="17" />
                        <div>
                            <div class="text-xs text-emerald-600 font-semibold uppercase tracking-wide leading-none">
                                Inicial
                            </div>
                            <span class="text-xs font-bold text-emerald-800">Bs. {{
                                formatCurrency(añoSeleccionado.presupuesto_anual || 0) }}</span>
                        </div>
                    </div>

                    <!-- Presupuesto Utilizado -->
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-rose-50 to-red-50 border border-rose-200 text-rose-700 rounded-lg text-sm font-medium shadow-sm">
                        <Icon :icon-button="true" name="bajaTemporal" :viewBox="'0 0 20 20'" class-name="text-rose-500"
                            :size="17" />
                        <div>
                            <div class="text-xs text-rose-600 font-semibold uppercase tracking-wide leading-none">
                                Utilizado
                            </div>
                            <span class="text-xs font-bold text-rose-800">Bs. {{
                                formatCurrency(sumaPresupuestoMensual || 0)
                                }}</span>
                        </div>
                    </div>

                    <!-- Presupuesto Disponible -->
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 text-sky-700 rounded-lg text-sm font-medium shadow-sm">
                        <Icon :icon-button="true" name="checkCircle" class-name="text-sky-500" :size="17" />
                        <div>
                            <div class="text-xs text-sky-600 font-semibold uppercase tracking-wide leading-none">
                                Disponible
                            </div>
                            <span class="text-xs font-bold text-sky-800">Bs. {{
                                formatCurrency((añoSeleccionado.presupuesto_anual || 0) - (sumaPresupuestoMensual ||
                                    0))
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================================ -->
        <!-- DATOS GENERALES -->
        <!-- ============================================================================ -->
        <main
            class="flex-1 overflow-x-hidden bg-white overflow-y-auto border-b border-x border-gray-300 rounded-b-lg mr-1">
            <div v-if="existe_gestion && gestionConDiciembreAnterior.length > 0" class="p-2 mb-1 h-full">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    <div v-for="item in gestionConDiciembreAnterior" :key="`${item.gestion}-${item.id_mes}`"
                        class="w-full">
                        <form @submit.prevent="submit" @click.prevent="openModalMes(item)"
                            class="cursor-pointer bg-white dark:bg-gray-800 rounded-xl border border-gray-300 dark:border-gray-700 p-3 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200"
                            :class="[retroactivoEstado?.habilitado ? 'h-auto' : 'h-32', item.esDiciembreAnterior ? 'border-dashed' : '']">
                            <div class="flex flex-col h-full justify-between">

                                <!-- Header -->
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-bold text-slate-700 dark:text-slate-200">
                                        {{ getMonthNameFromNumber(item.mes) }} {{ item.esDiciembreAnterior ?
                                            item.gestion : ''
                                        }}
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <!-- Badge de estado -->
                                        <span :class="[
                                            'px-2.5 py-1 rounded-full text-xs font-semibold border',
                                            item.cantidad_habilitadas === item.cantidad_total_pagos
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-700/40'
                                                : verifyDate(item.gestion, item.mes)
                                                    ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-700/40'
                                                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-700/40'
                                        ]">
                                            {{ item.cantidad_habilitadas === item.cantidad_total_pagos ? 'Completo'
                                                : verifyDate(item.gestion, item.mes) ? 'En proceso'
                                                    : 'Pendiente' }}
                                        </span>
                                        <!-- Menú de acciones: reporte PDF, reporte Excel, editar -->
                                        <Dropdown v-if="can('reporte-mes') || can('editar-mes')" align="right"
                                            width="48" @click.stop>
                                            <template #trigger>
                                                <button type="button"
                                                    class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                    <Icon :icon-button="true" name="dotsVertical"
                                                        class-name="text-slate-400" :size="18" />
                                                </button>
                                            </template>
                                            <template #content>
                                                <ul class="py-1.5 text-sm">
                                                    <li v-if="can('reporte-mes')">
                                                        <a href="#" @click.prevent="generarReporteMes(item)"
                                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                            <Icon :icon-button="true" name="filePDF"
                                                                class-name="text-red-500" :size="16" />
                                                            Reporte PDF
                                                        </a>
                                                    </li>
                                                    <li v-if="can('reporte-mes')">
                                                        <a href="#" @click.prevent="generarReporteExcelMes(item)"
                                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                            <Icon :icon-button="true" name="fileExcel"
                                                                class-name="text-emerald-600" :size="16" />
                                                            Reporte Excel
                                                        </a>
                                                    </li>
                                                    <li v-if="can('editar-mes') && !añoSeleccionado?.caja_cerrada">
                                                        <a href="#" @click.prevent="openEditMes(
                                                            item.id_mes, item.monto, item.presupuesto,
                                                            item.mes, item.gestion, item.cantidad_total_pagos
                                                        )"
                                                            class="flex items-center gap-2 px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                            <Icon :icon-button="true" name="calendarEdit"
                                                                class-name="text-slate-400" :size="16" />
                                                            Editar mes
                                                        </a>
                                                    </li>
                                                </ul>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </div>

                                <!-- Separador -->
                                <div class="h-px bg-gray-200 dark:bg-gray-700"></div>

                                <!-- Montos -->
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-500 dark:text-slate-400">Monto /
                                            persona</span>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                            Bs. {{ formatCurrency(item.monto) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-slate-500 dark:text-slate-400">Presupuesto</span>
                                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                            Bs. {{ formatCurrency(item.presupuesto) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Franja de retroactivo: siempre una línea (mismo alto en todos los
                                     cards), separada del mes normal, con su propio modal de detalle/carga
                                     independiente del modal del mes. Diciembre y noviembre quedan "Sin
                                     alcance" porque no reciben retroactivo. -->
                                <RetroactivoMesCard v-if="retroactivoEstado?.habilitado"
                                    :id-gestion="añoSeleccionado.id_gestion" :gestion="añoSeleccionado.gestion"
                                    :posicion="item.esDiciembreAnterior ? 1 : item.mes + 1"
                                    :fuera-de-alcance="item.esDiciembreAnterior || item.mes > 10"
                                    :caja-cerrada="añoSeleccionado?.caja_cerrada"
                                    :estado="retroactivoEstado" @created="cargarEstadoRetroactivo"
                                    @updated="cargarEstadoRetroactivo" />

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
            <Form v-if="formEdit" :fields="gestionEditFields" boton-name="Guardar" :existing-data="selectedItem || {}"
                :idFor="selectedId" :edit-mode="true" submit-route="gestion.update" @add="handleUpdate"
                @cancel="closeForm">
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
            <Form v-if="formCreateMes"
                :data="{ tipo: 'gestion', id: gestion_para_mes.id, mes: mes_actual_disponible, año: gestion_para_mes.año }"
                :fields="mesFields" :dataGestion="años_registrados" :presupuestosAnuales="presupuestosAnuales"
                :montoPersonaActual="montoPersonaActual"
                :personasValidas="total_personas_validas" :cisPersonasActivas="page.props.cis_personas_activas ?? []"
                :cisTodasPersonas="page.props.cis_todas_personas ?? []" submit-label="Registrar nuevo Mes"
                submit-route="gestion.addMes" importing-text="Importando archivo..."
                @importingStart="importando = true; iniciarPreloaderMes()"
                @importingEnd="importando = false; detenerPreloaderMes()" :importing="importando"
                boton-name="Agregar" @add="handleAdd"
                @mesExiste="existe" @mesDelante="mensajeDelante" @cancel="closeForm">
                <template #icon>
                    <Icon :icon-button="true" name="calendarPlus" class-name="text-white" />
                </template>
                <template #label1>
                    Agregar Mes - {{ getMonthNameFromNumber(mes_actual_disponible) }} {{ gestion_para_mes.año }}
                </template>
                <template #label2>
                    Complete la información para este mes
                </template>
            </Form>
        </Transition>

        <!-- Formulario: Editar mes -->
        <Transition name="fade">
            <Form v-if="formEditMes" :fields="mesEditFields" boton-name="Guardar" :existing-data="selectedItem || {}"
                :idFor="selectedId" :personasValidas="total_personas_validas" :edit-mode="true"
                submit-route="gestion.updateMes" @add="handleUpdate" @cancel="closeForm">
                <template #icon>
                    <Icon :icon-button="true" name="calendarEdit" class-name="text-white" />
                </template>
                <template #label1>
                    Editar <span class="hidden sm:contents">Mes de</span> {{ getMonthNameFromNumber(selectedMes) }}
                    - {{ selectedGestion }}
                </template>
                <template #label2>
                    Modifique el monto y presupuesto
                </template>
            </Form>
        </Transition>

        <!-- ============================================================================ -->
        <!-- MODALES -->
        <!-- ============================================================================ -->

        <!-- Modal: Visualizar datos mes -->
        <Transition name="fade">
            <ModalGestion v-if="showModal"
                :nombre-usuario="`${$page.props.auth.user.nombre} ${$page.props.auth.user.apellido}`"
                :ultimo_mes_creado="ultimo_mes_creado" :user="selectedId" :data="selectedItem" @close="closeForm" />
        </Transition>
    </AppLayout>
</template>
