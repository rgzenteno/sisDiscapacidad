<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/Components/Footer.vue';
import Mensajes from '@/Components/Mensajes.vue';
import Rutas from '@/Components/Rutas.vue';
import { computed, onMounted, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import { router } from '@inertiajs/vue3';
import ModalHabilitar from '@/Components/ModalHabilitar.vue';
import { onUnmounted } from 'vue'; // Agregar este import

// Obtener las props de la página
const page = usePage();
const persona = ref(page.props.persona);
const datosPersona = computed(() => page.props.datosPersona);
const habilited = computed(() => page.props.habilited);
const datosHabilitado = computed(() => page.props.datosHabilitado);
const año_actual = computed(() => page.props.año_actual);
const existe_gestion = computed(() => page.props.existe_gestion);
const añoSeleccionado = computed(() => page.props.añoSeleccionado);
const gestiones = computed(() => page.props.gestiones);
const filters = computed(() => page.props.filters);
const tiene_meses = computed(() => page.props.tiene_meses);

console.log('datos:', datosPersona.value);

const observationsMap = ref({});
const openModalHabilitar = ref(false);
const selectedItem = ref(null);
const showPersonaModal = ref(false);

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

//Fin Mensajes

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
const formatDateTime = (date) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('es', {
        day: 'numeric',
        month: 'long'
    }).format(new Date(date));
}

// Función para abrir el modal
const openPersonaModal = () => {
    showPersonaModal.value = true;
};

// Función para cerrar el modal
const closePersonaModal = () => {
    showPersonaModal.value = false;
};

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

const getYearValue = (yearData) => {
    if (typeof yearData === 'object' && yearData?.gestion) {
        return yearData.gestion;
    }
    return yearData || new Date().getFullYear();
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
    router.get(route('habilitado.show'), {
        año: yearValue, // 🔑 siempre será un número
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

const openModal = (item) => {
    selectedItem.value = {
        ...item,
    };
    openModalHabilitar.value = true;
};

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

    <Head title="UMADIS" />
    <div class="flex h-screen bg-gray-200 font-roboto">
        <Sidebar />
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mensajes de notificación -->
            <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
                <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                    @close="cerrarMensaje" />
            </div>
            <Header class="mb-0" />
            <ModalHabilitar v-if="openModalHabilitar" :data="selectedItem || {}" @habilitar="submit"
                @cambioEstado="handleCambioEstado"
                @sinDatos="mostrarMensaje('error', 'Error al Deshabilitar', 'Debe agregar una observación válida para deshabilitar.')"
                @close="openModalHabilitar = false" />

            <div class="py-2">
                <div
                    class="px-2 py-1 flex flex-col-reverse items-start justify-between sm:px-5 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="font-semibold text-2xl">Habilitar Meses</h1>
                    <Rutas label1="Inicio" label2="Beneficiarios" label3="Habilitar Meses" />
                </div>
            </div>


            <!-- Header Principal -->
            <div class="bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="py-4">
                        <!-- Título y Selector de Gestión -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-0 ">
                            <div class="flex items-center gap-3">
                                <h1
                                    class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent">
                                    GESTIÓN
                                </h1>

                                <!-- Dropdown de Años -->
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
                                        </button>
                                    </template>

                                    <template #content>
                                        <div
                                            class="w-64 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden">
                                            <div class="max-h-60 overflow-y-auto">
                                                <ul class="py-1">
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
                                                    <li v-for="year in gestiones" :key="year">
                                                        <a href="#" @click.prevent="selectGestion(year)"
                                                            class="flex items-center justify-between px-4 py-2.5 text-sm hover:bg-slate-50 transition-colors"
                                                            :class="selectedYear.toString() === year.gestion.toString()
                                                                ? 'bg-slate-100 text-slate-900 font-semibold'
                                                                : 'text-slate-600 hover:text-slate-900'">
                                                            <span>{{ year.gestion }}</span>
                                                            <svg v-if="selectedYear.toString() === year.gestion.toString()"
                                                                class="w-5 h-5 text-slate-700" fill="currentColor"
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

                            <!-- Información del Beneficiario (Solo en Desktop) - Versión Mini -->
                            <div v-if="datosPersona && datosPersona.length > 0" class="hidden lg:block relative">
                                <div v-for="persona in datosPersona" :key="persona.id_persona" @click="openPersonaModal"
                                    class="bg-gradient-to-r from-slate-50 to-gray-50 border border-gray-200 rounded-lg px-4 py-2 shadow-sm cursor-pointer hover:shadow-md hover:border-gray-300 transition-all duration-200">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-1">
                                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">
                                                Beneficiario
                                            </p>
                                            <h4 class="text-sm font-bold text-gray-900 capitalize">
                                                {{ persona.nombre_persona }} {{ persona.apellido_persona }}
                                            </h4>
                                        </div>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium"
                                            :class="[
                                                persona.estado === 'activo' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                                                    : persona.estado === 'baja_temporal' ? 'bg-amber-100 text-amber-700 border border-amber-200'
                                                        : 'bg-red-100 text-red-700 border border-red-200'
                                            ]">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="[
                                                persona.estado === 'activo' ? 'bg-emerald-500'
                                                    : persona.estado === 'baja_temporal' ? 'bg-amber-500'
                                                        : 'bg-red-500'
                                            ]"></span>
                                            {{ getNombreEstado(persona.estado) }}
                                        </span>

                                        <!-- Indicador de clic -->
                                        <svg class="w-4 h-4 text-gray-400 transition-transform"
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
                                            class="bg-gradient-to-br from-white to-gray-50 w-96 border border-gray-200 rounded-xl p-4 shadow-2xl">

                                            <!-- Botón cerrar -->
                                            <button @click="closePersonaModal"
                                                class="absolute top-3 right-3 p-1 rounded-lg bg-red-200 hover:bg-red-400 transition-colors"
                                                title="Cerrar (ESC)">
                                                <svg class="w-4 h-4 text-red-500 hover:text-red-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>

                                            <!-- Header: Nombre y Estado -->
                                            <div
                                                class="flex items-start justify-between gap-3 mb-3 pb-3 border-b border-gray-100">
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold mb-1">
                                                        Beneficiario
                                                    </p>
                                                    <h4 class="text-sm font-bold text-gray-900 capitalize truncate">
                                                        {{ persona.nombre_persona }} {{ persona.apellido_persona }}
                                                    </h4>
                                                </div>
                                            </div>

                                            <!-- Información de fechas -->
                                            <div class="grid grid-cols-2 gap-2 mb-3">
                                                <!-- Fecha de Registro -->
                                                <div class="bg-white rounded-lg p-2 border border-gray-100">
                                                    <div class="flex items-center gap-1.5 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        <span
                                                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Registro</span>
                                                    </div>
                                                    <p class="text-xs font-bold text-gray-800">{{ persona.fecha_registro?.split('T')[0] }}</p>
                                                </div>

                                                <!-- Fecha de Inicio Estado -->
                                                <div class="bg-white rounded-lg p-2 border border-gray-100">
                                                    <div class="flex items-center gap-1.5 mb-1">
                                                        <svg class="w-3.5 h-3.5 text-purple-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span
                                                            class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Estado
                                                            Desde</span>
                                                    </div>
                                                    <p class="text-xs font-bold text-gray-800">{{
                                                        persona.fecha_inicio?.split('T')[0]  }}</p>
                                                </div>
                                            </div>

                                            <!-- Observaciones (si existen) -->
                                            <div v-if="persona.observaciones"
                                                class="bg-amber-50 border border-amber-100 rounded-lg p-2.5 mt-3">
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"
                                                        fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    <div class="flex-1 min-w-0">
                                                        <p
                                                            class="text-[10px] font-semibold text-amber-700 uppercase tracking-wide mb-0.5">
                                                            Observaciones
                                                        </p>
                                                        <p class="text-xs text-amber-900 leading-relaxed">{{
                                                            persona.observaciones }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Indicador visual inferior -->
                                            <div class="mt-3 pt-2 border-t border-gray-100">
                                                <div
                                                    class="flex items-center justify-between text-[10px] text-gray-400">
                                                    <span class="flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                                        ID: {{ persona.id_persona.substring(0, 8) }}...
                                                    </span>
                                                    <span class="font-medium">{{ selectedYear }}</span>
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
                    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                        <div v-for="item in datosHabilitado" :key="item.id_habilitado" @click="openModal(item)" class="group cursor-pointer h-[130px] bg-white rounded-2xl border-2 border-gray-200 hover:border-gray-400
    hover:shadow-lg transition-all duration-700 overflow-hidden transform hover:-translate-y-1 relative p-4 z-0">

                            <!-- Círculo animado de fondo -->
                            <div class="circle absolute h-[5em] w-[5em] -top-[2.5em] -right-[2.5em] rounded-full group-hover:scale-[900%] duration-700 z-[-1]"
                                :class="{
                                    'bg-emerald-500': item.estado_mes === 'activo' || item.habilitado === 1,
                                    'bg-amber-500': item.estado_mes === 'baja_temporal',
                                    'bg-red-500': item.estado_mes === 'baja_definitiva'
                                }">
                            </div>

                            <!-- Título (Mes) -->
                            <h1
                                class="z-20 font-bold text-2xl group-hover:text-white group-hover:-translate-y-2 hover:-mt-10 duration-700 mb-1">
                                {{ getMonthNameFromDate(item.mes) }}
                            </h1>

                            <!-- Contenido -->
                            <div class="space-y-0.5 relative z-10">
                                <!-- Fecha de habilitación (solo visible en hover) -->
                                <div
                                    class="flex items-center justify-between opacity-0 group-hover:opacity-100 duration-700 max-h-0 group-hover:max-h-10 overflow-hidden transition-all">
                                    <span class="text-xs font-semibold text-white/90">Fecha Habilitado</span>
                                    <span class="text-xs font-bold text-white">
                                        {{ item.fecha_habilitado || 'N/A' }}
                                    </span>
                                </div>
                                <!-- Estado de pago (solo visible en hover) -->
                                <div
                                    class="flex items-center justify-between opacity-0 group-hover:opacity-100 duration-700 max-h-0 group-hover:max-h-10 overflow-hidden transition-all">
                                    <span class="text-xs font-semibold text-white/90">Pago</span>
                                    <span v-if="item.habilitado == 1"
                                        class="text-xs font-bold px-2 py-1 rounded bg-white/20 text-white">
                                        {{ item.pagado == 1 ? 'Pagado' : 'Pendiente' }}
                                    </span>
                                    <span v-else class="text-xs font-bold text-white">
                                        N/A
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between translate-y-6 group-hover:translate-y-0 transition-transform duration-700">
                                    <span
                                        class="text-xs font-semibold text-gray-600 group-hover:text-white/90 duration-700">Estado</span>
                                    <span
                                        class="text-xs font-bold px-2 py-1 rounded group-hover:bg-white/20 group-hover:text-white duration-700"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': item.habilitado == 1,
                                            'bg-red-100 text-red-700': item.habilitado != 1
                                        }">
                                        {{ item.habilitado == 1 ? 'Habilitado' : 'Deshabilitado' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-else class="flex flex-col items-center justify-center min-h-[400px] text-center">
                    <div class="bg-white rounded-full p-6 shadow-lg mb-4">
                        <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                        <span v-if="!existe_gestion">No existe gestión para {{ selectedYear }}</span>
                        <span v-else-if="!tiene_meses">Sin meses disponibles</span>
                        <span v-else>No se encontraron datos</span>
                    </h3>

                    <p class="text-gray-500 max-w-md">
                        <span v-if="!existe_gestion">Cree una gestión primero para comenzar a trabajar</span>
                        <span v-else-if="!tiene_meses">Agregue meses a la gestión {{ selectedYear }} para
                            continuar</span>
                        <span v-else>Agregue datos para comenzar a visualizar información</span>
                    </p>
                </div>
            </main>
            <div class="mt-1">
                <Footer />
            </div>
        </div>
    </div>
</template>
