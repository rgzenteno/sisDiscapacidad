<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, onMounted, ref, onUnmounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';
import Mensajes from '@/components/Mensajes.vue';
import Rutas from '@/components/Rutas.vue';
import Dropdown from '@/components/Dropdown.vue';
import Icon from '@/components/Icon.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';
import ModalHabilitar from '@/components/ModalHabilitar.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

/**
 * Props principales
 */
const filters = computed(() => page.props.filters);
const gestiones = computed(() => page.props.gestiones);
const habilited = computed(() => page.props.habilited);
const tiene_meses = computed(() => page.props.tiene_meses);
const datosPersona = computed(() => page.props.datosPersona);
const existe_gestion = computed(() => page.props.existe_gestion);
const datosHabilitado = computed(() => page.props.datosHabilitado);

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const showPersonaModal = ref(false);
const openModalHabilitar = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const mensajes = ref([]);
const selectedItem = ref(null);
const observationsMap = ref({});

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


//Funciones Extras
function getMonthNameFromDate(monthNumber) {
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

// Opción 2: Formato más personalizado
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';

    const [datePart, timePart] = dateTimeString.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour, minute] = timePart.split(':');

    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return `${dateStr} ${hour}:${minute}`;
};

const getCurrentDate = (data) => {
    if (!data) return 'N/A';

    // Parsear la fecha manualmente para evitar problemas de zona horaria
    const [year, month, day] = data.split('-').map(Number);
    const fecha = new Date(year, month - 1, day);

    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return dateStr;
};

// ============================================================================
// FUNCIONES - FORMULARIOS CREACIÓN
// ============================================================================

/**
 * Abre el modal de datos del beneficiario
 */
const openPersonaModal = () => {
    showPersonaModal.value = true;
};

// Función para cerrar el modal
const closePersonaModal = () => {
    showPersonaModal.value = false;
};

const getYearValue = (yearData) => {
    if (typeof yearData === 'object' && yearData?.gestion) {
        return yearData.gestion;
    }
    return yearData;
}

const getNombreEstado = (estado) => {
    if (!estado) return '●'
    switch (estado.toLowerCase()) {
        case 'activo':
            return 'Activo'
        case 'baja_temporal':
            return 'Baja Temporal'
        case 'baja_definitiva':
            return 'Baja Definitiva'
        case 'depurado':
            return 'Depurado'
        default:
            return '●'
    }
}

const getColorEstado = (estado) => {
    if (!estado) return '●'
    switch (estado.toLowerCase()) {
        case 'activo':
            return 'bg-emerald-100 text-emerald-700 border border-emerald-200'
        case 'baja_temporal':
            return 'bg-amber-100 text-amber-700 border border-amber-200'
        case 'baja_definitiva':
            return 'bg-red-100 text-red-700 border border-red-200'
        case 'depurado':
            return 'bg-gray-100 text-gray-700 border border-gray-200'
        default:
            return '●'
    }
}

const getCircleEstado = (estado) => {
    if (!estado) return '●'
    switch (estado.toLowerCase()) {
        case 'activo':
            return 'bg-emerald-500'
        case 'baja_temporal':
            return 'bg-amber-500'
        case 'baja_definitiva':
            return 'bg-red-500'
        case 'depurado':
            return 'bg-gray-500'
        default:
            return '●'
    }
}


// ✅ Actualizar la función para obtener el año seleccionado
const selectedYear = ref(getYearValue(filters.value?.año))

// Función para seleccionar un año del dropdown
const selectGestion = (year) => {
    const yearValue = getYearValue(year);
    selectedYear.value = yearValue;
    router.get(route('habilitado.show', { id: page.props.idPersona }), {
        año: yearValue,
        buscador: filters.value?.buscador || ''
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}
//Fin Funciones Extras

//Habilitar Nuevo mes

// Inicializar el formulario con un objeto para almacenar las observaciones por `id_habilitado`
const form = useForm({
    id_habilitado: '',
    habilitado: 0,
    observaciones_habilitado: '',
    id_persona: '',
    id_gestion: '',
    id_mes: '',
});

const unhabilitatedObservations = ref({});

// En el onMounted o cuando recibas los datos
onMounted(() => {
    // Inicializar las observaciones
    datosHabilitado.value.forEach(item => {
        if (!item.habilitado) {
            unhabilitatedObservations.value[item.id_gestion] = '';
        }
    });
});

const submit = (item) => {

    console.log('Datos a enviar para habilitar:', item);

    // Preparar el formulario
    form.id_persona = item.id_persona;
    form.id_gestion = item.id_gestion;
    form.id_mes = item.id_mes;

    form.post(route('habilitado.store'), {
        onSuccess: () => {
            openModalHabilitar.value = false;
            console.log('Habilitación exitosa');
            mostrarMensaje('correcto', 'Habilitación exitosa', 'Se ha habilitado correctamente.');
            form.reset();
        },
        onError: (error) => {
            console.log('Error al registrar:', error);
            mostrarMensaje('advertencia', 'Algo inesperado ocurrió', 'Ha ocurrido un error inesperado. Intente nuevamente.');
        }
    });
};
//Fin Mes habiliado

//Inicio Editar Habilitado
const inputErrors = ref({});

// Función para validar la observación
const validateObservation = (idHabilitado, observation, habilitado) => {
    // Solo valida si el elemento está deshabilitado (habilitado = 1)
    if (habilitado === 1) {
        if (!observation || observation.trim() === '' || observation === 'ninguna') {
            inputErrors.value[idHabilitado] = true;
            return false;
        }
    }

    inputErrors.value[idHabilitado] = false;
    return true;
};

const getHabilitadosFiltrados = (idHabilitado) => {
    return habilited.value.filter(h => h.id_habilitado === idHabilitado);
}

const getObservationForItem = (itemId) => {

    // Primero, verifica el mapa de observaciones
    if (observationsMap.value[itemId] !== undefined) {
        /* console.log('Observación encontrada en el mapa:', observationsMap.value[itemId]); */
        return observationsMap.value[itemId];
    }

    // Si no está en el mapa, obtén los datos filtrados
    const habilitados = getHabilitadosFiltrados(itemId);
    /* console.log('Habilitados filtrados:', habilitados); */

    if (habilitados.length > 0) {
        // Guarda la observación en el mapa
        observationsMap.value[itemId] = habilitados[0].observaciones_habilitado || '';
        /* console.log('Nueva observación guardada en el mapa:', observationsMap.value[itemId]); */
        return observationsMap.value[itemId];
    }

    /* console.log('No se encontró observación, retornando vacío'); */
    return '';
};

const setObservationForItem = (itemId, observation) => {
    /* console.log('Estableciendo observación:', {
        itemId,
        observation,
        previousValue: observationsMap.value[itemId]
    }); */

    observationsMap.value[itemId] = observation;
    /* console.log('Mapa de observaciones actualizado:', observationsMap.value); */

    // Validar la observación mientras se escribe
    const isValid = validateObservation(itemId, observation);
    /* console.log('Resultado de validación:', isValid); */
};

const toggleHabilitado = (habilitado, idHabilitado) => {
    /* console.log('Toggle Habilitado iniciado:', {
        habilitado,
        idHabilitado,
        currentObservation: observationsMap.value[idHabilitado]
    }); */
    let currentObservation = null;
    // Si está habilitado (1) y queremos deshabilitar (0)
    if (habilitado === 1) {
        const newObservation = observationsMap.value[idHabilitado] || '';

        // Validar la observación solo cuando vamos a deshabilitar
        if (!validateObservation(idHabilitado, newObservation, habilitado)) {
            mostrarMensaje('error', 'Error al Deshabilitar', 'Debe agregar una observación válida para deshabilitar.');
            return;
        }

        form.id_habilitado = idHabilitado;
        form.habilitado = 0;
        form.observaciones_habilitado = newObservation; // Usar la nueva observación

    } else {
        // Obtener la observación actual del mapa o del elemento
        const currentObservation = observationsMap.value[idHabilitado] || getObservationForItem(idHabilitado);
        // Al habilitar, mantener la observación anterior si no se ha modificado
        form.id_habilitado = idHabilitado;
        form.habilitado = 1;
        form.observaciones_habilitado = currentObservation;
        //alert(idHabilitado, currentObservation);
    }

    console.log(idHabilitado, form.habilitado, form.observaciones_habilitado);

    form.post(route('habilitado.edit', idHabilitado), {
        onSuccess: () => {
            // Actualizar el mapa de observaciones con el valor correcto
            if (form.habilitado === 1) {
                // Si estamos habilitando, mantener la observación anterior
                observationsMap.value[idHabilitado] = currentObservation;
                mostrarMensaje('correcto', 'Modificación exitosa', 'Se ha habilitado correctamente.');
            } else {
                // Si estamos deshabilitando, usar la nueva observación
                observationsMap.value[idHabilitado] = form.observaciones_habilitado;
                mostrarMensaje('correcto', 'Modificación exitosa', 'Se ha deshabilitado correctamente.');
            }

            inputErrors.value[idHabilitado] = false;
        },
        onError: (error) => {
            mostrarMensaje('advertencia', 'Algo inesperado ocurrió', 'Ha ocurrido un error inesperado. Intente nuevamente.');
        }
    });
};

const getInitials = (nombre, apellido) =>
    `${(nombre || '')[0] ?? ''}${(apellido || '')[0] ?? ''}`.toUpperCase() || '?';

const openModal = (item) => {
    selectedItem.value = {
        ...item,
    };
    openModalHabilitar.value = true;
};

const resumenMeses = computed(() => {
    const meses = datosHabilitado.value ?? []
    return {
        habilitados: meses.filter(m => m.habilitado === true).length,
        deshabilitados: meses.filter(m => m.habilitado === false).length,
        bajaTemp: meses.filter(m => m.estado_mes === 'baja_temporal').length,
        sinHabilitar: meses.filter(m => m.habilitado === null).length,
    }
})

const handleCambioEstado = (data) => {
    form.id_habilitado = data.id_habilitado
    form.habilitado = data.habilitado
    form.observaciones_habilitado = data.observacion

    form.post(route('habilitado.edit', data.id_habilitado), {
        onSuccess: () => {
            openModalHabilitar.value = false
            mostrarMensaje('correcto', 'Modificación exitosa',
                data.habilitado === 1 ? 'Se ha habilitado correctamente.' : 'Se ha deshabilitado correctamente.')
            form.reset()
        },
        onError: (error) => {
            console.log('Error al modificar:', error)
            mostrarMensaje('advertencia', 'Algo inesperado ocurrió', 'Ha ocurrido un error inesperado. Intente nuevamente.')
        }
    })
}

//Fin Editar Habiltiado

// Cerrar con tecla ESC
const handleEscape = (e) => {
    if (e.key === 'Escape' && showPersonaModal.value) {
        closePersonaModal();
    }
};

// Agregar listener cuando se monta el componente
onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

// Limpiar el listener cuando se desmonta
onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});
</script>

<style>
@keyframes shimmer {

    0%,
    100% {
        transform: translateX(-100%);
    }

    50% {
        transform: translateX(100%);
    }
}
</style>
<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex h-screen -ml-1 bg-gray-200 font-roboto">
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
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Habilitar Meses</h1>
                <Rutas label1="Inicio" label2="Beneficiarios" label3="Habilitar Meses" class="sm:text-xs" />
            </div>

            <Transition name="fade">
                <ModalHabilitar v-if="openModalHabilitar" :data="selectedItem || {}" @habilitar="submit"
                    @cambioEstado="handleCambioEstado"
                    @sinDatos="mostrarMensaje('error', 'Error al Deshabilitar', 'Debe agregar una observación válida para deshabilitar.')"
                    @close="openModalHabilitar = false" />
            </Transition>

            <!-- Header Principal -->
            <div class="bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="py-4">
                        <!-- Título y Selector de Gestión -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-0 ">
                            <div class="flex items-center gap-3">
                                <div v-if="selectedYear" class="flex items-center">
                                    <h1
                                        class="text-3xl p-1.5 font-bold text-slate-800 text-center tracking-tight bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                                        GESTIÓN
                                    </h1>
                                    <Dropdown align="left" width="60">
                                        <template #trigger="{ open }">
                                            <button
                                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow"
                                                type="button">
                                                <span class="text-xl font-semibold text-slate-700">
                                                    {{ selectedYear }}
                                                </span>
                                                <svg class="w-4 h-4 text-gray-500 transition-transform flex-shrink-0"
                                                    :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <div
                                                class="w-64 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl overflow-hidden shadow-xl border border-slate-200">
                                                <!-- Lista scrolleable -->
                                                <div
                                                    class="max-h-60 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-transparent">
                                                    <ul class="py-2">
                                                        <li v-if="!gestiones || gestiones.length === 0"
                                                            class="px-4 py-3">
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
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <!-- Información del Beneficiario (Solo en Desktop) - Versión Mini -->
                            <div v-if="datosPersona && datosPersona.length > 0" class="hidden lg:block relative">
                                <div v-for="persona in datosPersona" :key="persona.id_persona" @click="openPersonaModal"
                                    class="border border-gray-200 rounded-lg px-4 py-1 shadow-sm cursor-pointer hover:shadow-md hover:border-gray-300 transition-all duration-200">

                                    <div class="flex items-center gap-3">
                                        <!-- Avatar iniciales -->
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <span class="text-xs font-medium text-blue-700 uppercase">
                                                {{ getInitials(
    persona.nombre_persona || persona.nombre_completo?.split(' ')[0],
    persona.apellido_persona || persona.nombre_completo?.split(' ')[1] || ''
) }}
                                            </span>
                                        </div>
                                        <!-- Nombre -->
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-[10px] text-gray-500 font-medium uppercase tracking-wide leading-none">
                                                Beneficiario
                                            </p>
                                            <p
                                                class="text-[13px] font-semibold text-gray-900 capitalize truncate mt-0.5 leading-tight">
                                                {{ persona.apellido_persona && persona.nombre_persona
    ? `${persona.apellido_persona} ${persona.nombre_persona}`
    : persona.nombre_completo }}
                                            </p>
                                        </div>

                                        <!-- Estado + CI -->
                                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                :class="getColorEstado(persona.estado)">
                                                <span class="w-1.5 h-1.5 rounded-full"
                                                    :class="getCircleEstado(persona.estado)">
                                                </span>
                                                <span class="ms-1">{{ getNombreEstado(persona.estado) }}</span>
                                            </span>
                                            <span class="text-[10px] text-gray-500">CI:
                                                <span class="text-gray-900">{{ persona.ci_persona }}</span>
                                            </span>
                                        </div>

                                        <!-- Chevron -->
                                        <svg class="w-3.5 h-3.5 text-gray-500 transition-transform flex-shrink-0"
                                            :class="showPersonaModal ? 'rotate-180' : ''" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>


                                <!-- Modal Expandido -->
                                <Transition enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-1">
                                    <div v-if="showPersonaModal" class="absolute top-full right-0 mt-2 z-50"
                                        @click.stop>
                                        <div v-for="persona in datosPersona" :key="'modal-' + persona.id_persona"
                                            class="bg-white w-96 border border-gray-200 rounded-xl shadow-xl overflow-hidden">

                                            <!-- Header -->
                                            <div
                                                class="flex items-center justify-between gap-3 px-3.5 py-3 border-b border-gray-100">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div
                                                        class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-xs font-medium text-blue-700 uppercase">
                                                            {{ getInitials(
    persona.nombre_persona || persona.nombre_completo?.split(' ')[0],
    persona.apellido_persona || persona.nombre_completo?.split(' ')[1] || ''
) }}
                                                        </span>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p
                                                            class="text-[13px] font-semibold text-gray-900 uppercase truncate leading-tight">
                                                            {{ persona.apellido_persona && persona.nombre_persona
    ? `${persona.apellido_persona} ${persona.nombre_persona}`
    : persona.nombre_completo }}
                                                        </p>
                                                        <p class="text-[11px] text-gray-500">CI: {{ persona.ci_persona
                                                            }}</p>
                                                    </div>
                                                </div>
                                                <button @click="closePersonaModal"
                                                    class="w-[26px] h-[26px] rounded-full border border-gray-200 flex items-center justify-center hover:bg-red-50 hover:border-red-200 transition-colors flex-shrink-0"
                                                    title="Cerrar (ESC)">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="p-3.5 space-y-2.5">

                                                <!-- Beneficiario -->
                                                <div class="border border-gray-100 rounded-lg overflow-hidden">
                                                    <div class="bg-gray-50 px-2.5 py-1.5 border-b border-gray-100">
                                                        <h3
                                                            class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">
                                                            Beneficiario</h3>
                                                    </div>
                                                    <div class="px-2.5 py-2">
                                                        <div
                                                            class="grid grid-cols-[84px_1fr] gap-x-2 gap-y-1.5 text-xs">
                                                            <span class="text-gray-400">Registro</span>
                                                            <span class="font-medium text-gray-800">{{
                                                                getCurrentDate(persona.fecha_registro?.split('T')[0])
                                                                }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Estado -->
                                                <div class="border border-gray-100 rounded-lg overflow-hidden">
                                                    <div class="bg-gray-50 px-2.5 py-1.5 border-b border-gray-100">
                                                        <h3
                                                            class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">
                                                            Estado actual
                                                        </h3>
                                                    </div>
                                                    <div class="px-2.5 py-2 space-y-2">
                                                        <div class="flex items-center justify-between">
                                                            <span
                                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                                                                :class="getColorEstado(persona.estado)">
                                                                <span class="w-1.5 h-1.5 rounded-full"
                                                                    :class="getCircleEstado(persona.estado)"></span>
                                                                {{ getNombreEstado(persona.estado) }}
                                                            </span>
                                                            <span v-if="persona.fecha_inicio"
                                                                class="text-[11px] text-gray-400">
                                                                Desde: {{
                                                                    getCurrentDate(persona.fecha_inicio?.split('T')[0]) }}
                                                            </span>
                                                        </div>
                                                        <div class="h-px bg-gray-100"></div>
                                                        <div class="grid grid-cols-[84px_1fr] gap-x-2 text-xs">
                                                            <span class="text-gray-400">Observación</span>
                                                            <span v-if="persona.observaciones"
                                                                class="text-gray-700 break-words">{{
                                                                    persona.observaciones }}</span>
                                                            <span v-else class="text-gray-400 italic">Sin
                                                                observaciones</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Resumen meses gestión -->
                                                <div class="border border-gray-100 rounded-lg overflow-hidden">
                                                    <div class="bg-gray-50 px-2.5 py-1.5 border-b border-gray-100">
                                                        <h3
                                                            class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">
                                                            Gestión {{ selectedYear }} — meses
                                                        </h3>
                                                    </div>
                                                    <div class="px-2.5 py-2">
                                                        <div class="flex flex-wrap gap-1.5">
                                                            <span v-if="resumenMeses.habilitados > 0"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                <span class="text-[13px] font-semibold">{{
                                                                    resumenMeses.habilitados }}</span>
                                                                Habilitados
                                                            </span>
                                                            <span v-if="resumenMeses.deshabilitados > 0"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                                                                <span class="text-[13px] font-semibold">{{
                                                                    resumenMeses.deshabilitados }}</span>
                                                                Deshabilitados
                                                            </span>
                                                            <span v-if="resumenMeses.bajaTemp > 0"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                                <span class="text-[13px] font-semibold">{{
                                                                    resumenMeses.bajaTemp }}</span> Baja
                                                                temporal
                                                            </span>
                                                            <span v-if="resumenMeses.sinHabilitar > 0"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                                                <span class="text-[13px] font-semibold">{{
                                                                    resumenMeses.sinHabilitar }}</span> Sin
                                                                habilitar
                                                            </span>
                                                            <span v-if="datosHabilitado.length === 0"
                                                                class="text-xs text-gray-400 italic">Sin meses
                                                                registrados</span>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Backdrop transparente para cerrar al hacer clic afuera -->
                            <Transition enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0" enter-to-class="opacity-100"
                                leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                                <div v-if="showPersonaModal" @click="closePersonaModal" class="fixed inset-0 z-40">
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal: Grid de Meses -->
            <main
                class="flex-1 overflow-x-hidden overflow-y-auto border-b-2 border-x-2 bg-white rounded-b-lg px-2 pt-3 mr-1">
                <!-- Grid de Cards optimizado para 1-12 items -->
                <div v-if="existe_gestion && tiene_meses && datosHabilitado.length > 0">
                    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
                        <div v-for="item in datosHabilitado" :key="item.id_habilitado" @click="openModal(item)"
                            class="group cursor-pointer h-[125px] rounded-2xl border-2 border-gray-100 hover:border-transparent
                                hover:shadow-xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1 relative z-0 bg-white" :class="{
                                    'hover:bg-emerald-500': item.estado_mes === 'activo',
                                    'hover:bg-amber-500': item.estado_mes === 'baja_temporal',
                                    'hover:bg-red-500': item.estado_mes === 'baja_definitiva',
                                    'hover:bg-gray-500': item.estado_mes === 'depurado',
                                    'hover:bg-gray-100': !item.estado_mes
                                }">
                            <!-- Círculo decorativo esquina -->
                            <div class="absolute h-[5em] w-[5em] -top-[2.5em] -right-[2.5em] rounded-full
                                    opacity-100 group-hover:opacity-0 transition-opacity duration-500 pointer-events-none"
                                :class="{
                                    'bg-emerald-300': item.estado_mes === 'activo',
                                    'bg-amber-300': item.estado_mes === 'baja_temporal',
                                    'bg-red-300': item.estado_mes === 'baja_definitiva',
                                    'bg-gray-200': item.estado_mes === 'depurado',
                                    'bg-gray-50': !item.estado_mes
                                }"></div>

                            <!-- ═══════════════════════════════════ -->
                            <!--  ESTADO BASE (visible sin hover)   -->
                            <!-- ═══════════════════════════════════ -->
                            <div class="absolute inset-0 p-4 flex flex-col justify-between
                                    opacity-100 group-hover:opacity-0 translate-y-0 group-hover:-translate-y-2
                                    transition-all duration-400 pointer-events-none z-10">

                                <!-- Fila superior: Mes + ícono de pago -->
                                <div class="flex items-start justify-between">
                                    <h1 class="font-bold text-2xl leading-tight text-gray-800">
                                        {{ getMonthNameFromDate(item.mes) }}
                                    </h1>

                                    <!-- Ícono de pago (solo si está habilitado) -->
                                    <span v-if="item.habilitado == 1"
                                        class="flex items-center justify-center w-7 h-7 rounded-full mt-0.5" :class="{
                                            'bg-emerald-100 ': item.pagado == 1,
                                            'bg-gray-100': item.pagado != 1
                                        }">
                                        <svg class="w-6 h-6 "
                                            :class="item.pagado == 1 ? 'text-emerald-600' : 'text-gray-500'" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>

                                <!-- Fila inferior: Badge de estado -->
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full" :class="{
                                        'bg-emerald-100 text-emerald-700': item.habilitado === true,
                                        'bg-red-100 text-red-700': item.habilitado === false,
                                        'bg-gray-100 text-gray-500': item.habilitado === null
                                    }">
                                        {{ item.habilitado === true ? 'Habilitado' : item.habilitado === false ?
                                            'Deshabilitado' :
                                            'Sin habilitar' }}
                                    </span>

                                    <!-- Dot de estado del mes -->
                                    <span class="flex items-center gap-1 text-[11px] text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block" :class="{
                                            'bg-emerald-400': item.estado_mes === 'activo',
                                            'bg-amber-400': item.estado_mes === 'baja_temporal',
                                            'bg-red-400': item.estado_mes === 'baja_definitiva',
                                            'bg-gray-400': item.estado_mes === 'depurado',
                                            'bg-gray-200': !item.estado_mes
                                        }"></span>
                                        {{ item.estado_mes ? item.estado_mes.replace('_', ' ') : 'Sin estado' }}
                                    </span>
                                </div>
                            </div>

                            <!-- ═══════════════════════════════════ -->
                            <!--  ESTADO HOVER (visible al pasar)   -->
                            <!-- ═══════════════════════════════════ -->
                            <div class="absolute inset-0 p-4 pt-3 flex flex-col justify-between
                                    opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0
                                    transition-all duration-500 pointer-events-none z-10">

                                <!-- Título mes en hover -->
                                <h1 class="font-bold text-xl text-white leading-tight">
                                    {{ getMonthNameFromDate(item.mes) }}
                                </h1>

                                <!-- Detalles en hover -->
                                <div class="space-y-1.5">

                                    <!-- Fecha de habilitación -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] text-white/70 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                                <line x1="16" y1="2" x2="16" y2="6" />
                                                <line x1="8" y1="2" x2="8" y2="6" />
                                                <line x1="3" y1="10" x2="21" y2="10" />
                                            </svg>
                                            Habilitado
                                        </span>
                                        <span class="text-[11px] font-semibold text-white">
                                            {{ formatDateTime(item.fecha_habilitado) }}
                                        </span>
                                    </div>

                                    <!-- Fecha de pago (solo si pagado) -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] text-white/70 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="1" y="4" width="22" height="16" rx="2" />
                                                <line x1="1" y1="10" x2="23" y2="10" />
                                            </svg>
                                            Fecha pago
                                        </span>
                                        <span v-if="item.habilitado == 1 && item.pagado == 1"
                                            class="text-[11px] font-semibold text-white">
                                            {{ formatDateTime(item.fecha_pago) }}
                                        </span>
                                        <span v-else class="text-[11px] text-white/60">Pago N/A</span>
                                    </div>

                                    <div class="w-full h-px bg-white/20"></div>

                                    <!-- Estado + pago -->
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20 text-white">
                                            {{ item.habilitado === true ? 'Habilitado' : item.habilitado === false ?
                                                'Deshabilitado'
                                                : 'Sin habilitar' }}
                                        </span>

                                        <span v-if="item.habilitado == 1"
                                            class="flex items-center gap-1 text-[11px] font-bold "
                                            :class="item.pagado == 1 ? 'text-white' : 'text-red-600'">
                                            <svg v-if="item.pagado == 1" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <!--  <svg v-if="item.pagado == 1" xmlns="http://www.w3.org/2000/svg"
                                                class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg> -->
                                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10" />
                                                <polyline points="12 6 12 12 16 14" />
                                            </svg>
                                            {{ item.pagado == 1 ? 'Pagado' : 'Pendiente' }}
                                        </span>
                                        <span v-else class="text-[11px] text-white/60">Pago N/A</span>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="flex flex-col items-center justify-center min-h-[400px] text-center">
                    <!-- Icono dinámico según el estado -->
                    <div class="bg-white rounded-full p-6 ">
                        <!-- Sin gestión - Icono de calendario con X -->
                        <Icon v-if="!existe_gestion" :icon-button="true" name="calendarMontSolid"
                            class-name="text-gray-400 dark:text-gray-500" :size="64" :height="64" />

                        <!-- Sin meses - Icono de reloj/calendario vacío -->
                        <svg v-else-if="!tiene_meses" class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>

                        <!-- Sin datos - Icono de carpeta vacía -->
                        <svg v-else class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                        <span v-if="!existe_gestion">No hay gestiones registradas</span>
                        <span v-else-if="!tiene_meses">Sin meses registrados</span>
                        <span v-else>Sin datos disponibles</span>
                    </h3>

                    <p class="text-gray-500 max-w-md">
                        <span v-if="!existe_gestion">Debe crear una gestión en el sistema para comenzar a
                            trabajar</span>
                        <span v-else-if="!tiene_meses">Agregue meses a la gestión {{ selectedYear }} para habilitar
                            beneficiarios</span>
                        <span v-else>No se encontraron registros para mostrar</span>
                    </p>
                </div>
            </main>
            <div class="mt-1">
                <Footer />
            </div>
        </div>
    </div>
</template>
