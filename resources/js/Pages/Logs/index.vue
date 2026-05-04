<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { usePage, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import Busqueda from '@/components/Busqueda.vue';
import DataTable from '@/components/DataTable.vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Icon from '@/components/Icon.vue';
import ModalLogs from '@/components/ModalLogs.vue';
import Paginacion from '@/components/Paginacion.vue';
import Rutas from '@/components/Rutas.vue';
import Sidebar from '@/components/Sidebar.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({ logs: Object });

// ============================================================================
// ESTADO - PÁGINA E INERTIA
// ============================================================================
const page = usePage();
const filters = computed(() => page.props.filters ?? {});
const search = computed(() => page.props.filters?.buscador ?? '');

// ============================================================================
// ESTADO - MODAL
// ============================================================================
const showModal = ref(false);
const dataLog = ref('');
const idLog = ref('');

const modalLogs = (id, log) => {
    idLog.value = id;
    dataLog.value = log;
    showModal.value = true;
};

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Acción', field: 'properties.action', headerClass: 'text-center', cellClass: 'text-center whitespace-nowrap' },
    { label: 'Usuario', field: 'causer', headerClass: '', cellClass: 'whitespace-nowrap' },
    { label: 'Fecha y Hora', field: 'created_at', headerClass: 'whitespace-nowrap', cellClass: 'whitespace-nowrap' },
    { label: 'Módulo', field: 'properties.module', headerClass: '', cellClass: 'whitespace-nowrap' },
    { label: 'Descripción', field: 'description', headerClass: '', cellClass: '' },
];

// ============================================================================
// HELPERS - FORMATO
// ============================================================================

// Convierte texto a Title Case (ej: "JUAN PEREZ" → "Juan Perez")
const toTitleCase = (str) => {
    if (!str) return '';
    return str.toLowerCase().replace(/(?:^|\s)\S/g, c => c.toUpperCase());
};

const formatDate = (date) => new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric',
});

const formatTime = (date) => new Date(date).toLocaleTimeString('es-ES', {
    hour: '2-digit', minute: '2-digit', second: '2-digit',
});

// Extrae el nombre del modelo desde el namespace completo (ej: "App\Models\Persona" → "Persona")
const parseSubjectType = (subjectType) => subjectType?.split('\\').pop() ?? null;

// Genera iniciales desde nombre y apellido (ej: "Juan Perez" → "JP")
const getInitials = (nombre, apellido) =>
    `${(nombre || '')[0] ?? ''}${(apellido || '')[0] ?? ''}`.toUpperCase() || '?';

// ============================================================================
// CONFIGURACIÓN DE ACCIONES - COLORES E ÍCONOS
// ============================================================================

// Color de fondo al hacer hover según la acción del registro
const hoverBg = {
    'crear': '#f0fdf4',
    'actualizar': '#eff6ff',
    'eliminar': '#fef2f2',
    'delete': '#fff7ed',
    'habilitado': '#f0fdfa',
    'deshabilitado': '#f8fafc',
    'importar': '#faf5ff',
    'pago realizado': '#fffbeb',
    'reporte imprimido': '#fdf4ff',
    'agregar': '#f0fdf4',
    'ver': '#eef2ff',
    'error': '#fef2f2',
    'reset contraseña': '#f5f3ff',
    'login': '#f0f9ff',
    'logout': '#fff7ed',
    'session-expired': '#fefce8',
    'boleta_descargada': '#f0fdf4',
};

const hoveredId = ref(null);

// Configuración visual (badge) por tipo de acción
const actionConfig = {
    'crear': { label: 'Crear', icon: 'M12 4v16m8-8H4', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-200', dot: 'bg-emerald-400' },
    'actualizar': { label: 'Actualizar', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', classes: 'bg-blue-50 text-blue-700 ring-blue-200', dot: 'bg-blue-400' },
    'eliminar': { label: 'Eliminar', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', classes: 'bg-red-50 text-red-700 ring-red-200', dot: 'bg-red-400' },
    'delete': { label: 'Eliminar', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', classes: 'bg-orange-50 text-orange-700 ring-orange-200', dot: 'bg-orange-400' },
    'habilitado': { label: 'Habilitar', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', classes: 'bg-cyan-50 text-cyan-800 ring-cyan-600', dot: 'bg-cyan-800' },
    'deshabilitado': { label: 'Deshabilitar', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', classes: 'bg-rose-50 text-rose-700 ring-rose-200', dot: 'bg-rose-400' },
    'importar': { label: 'Importar', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12', classes: 'bg-purple-50 text-purple-700 ring-purple-200', dot: 'bg-purple-400' },
    'pago realizado': { label: 'Pago', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', classes: 'bg-amber-50 text-amber-700 ring-amber-200', dot: 'bg-amber-400' },
    'reporte imprimido': { label: 'Reporte', icon: 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z', classes: 'bg-pink-50 text-pink-700 ring-pink-200', dot: 'bg-pink-400' },
    'agregar': { label: 'Agregar', icon: 'M12 4v16m8-8H4', classes: 'bg-green-50 text-green-700 ring-green-200', dot: 'bg-green-400' },
    'ver': { label: 'Ver', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7z', classes: 'bg-indigo-50 text-indigo-700 ring-indigo-200', dot: 'bg-indigo-400' },
    'error': { label: 'Error', icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', classes: 'bg-red-50 text-red-700 ring-red-200', dot: 'bg-red-500' },
    'reset contraseña': {
        label: 'Reset',
        icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        classes: 'bg-violet-50 text-violet-700 ring-violet-200',
        dot: 'bg-violet-400'
    },
    'login': { label: 'Login', icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1', classes: 'bg-sky-50 text-sky-700 ring-sky-200', dot: 'bg-sky-400' },
    'logout': { label: 'Logout', icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1', classes: 'bg-orange-50 text-orange-700 ring-orange-200', dot: 'bg-orange-400' },
    'session-expired': { label: 'Expirado', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', classes: 'bg-yellow-50 text-yellow-700 ring-yellow-200', dot: 'bg-yellow-400' },
    'boleta_descargada': {
        label: 'Boleta',
        icon: 'M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z',
        classes: 'bg-teal-50 text-teal-700 ring-teal-200',
        dot: 'bg-teal-400'
    },
};

// Agrega esto en ModalLogs.vue y en el index de logs
const moduleLabels = {
    'BandejaPago': 'Bandeja de Pago',
    'Tutor': 'Tutores',
    'Persona': 'Beneficiarios',
    'Carnet': 'Carnet Disc.',
    'Usuario': 'Usuarios',
    'Rol': 'Roles',
    'Discapacidad': 'Discapacidades',
    'Distrito': 'Distritos',
    'Gestion': 'Gestión',
    'Mes': 'Meses',
    'HistorialEstados': 'Historial Estados',
    'Autenticación': 'Autenticación',
};

const getModuleLabel = (module) => moduleLabels[module] ?? module;

// Retorna config de acción o fallback genérico si no existe
const getActionConfig = (action) =>
    actionConfig[action] ?? { label: action, classes: 'bg-gray-100 text-gray-600 ring-gray-200', dot: 'bg-gray-400' };

// Retorna el tipo del subject solo si es diferente al módulo (evita duplicar info)
const getSubjectLabel = (item) => {
    const type = parseSubjectType(item.subject_type);
    if (!type || type === item.properties?.module) return null;
    return type;
};
</script>

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

            <!-- Header -->
            <Header class="mb-0" />

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Registro de Actividad</h1>
                <Rutas label1="Inicio" label3="Registro Actividad" class="sm:text-xs" />
            </div>

            <!-- ============================================================================ -->
            <!-- BARRA DE HERRAMIENTAS -->
            <!-- ============================================================================ -->
            <div class="flex justify-between p-2 sm:p-4 sm:pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <!-- Buscador -->
                <Busqueda :initial-value="filters.buscador" name="log" only="logs" :data="logs"
                    ruta-busqueda="log.index" />
            </div>

            <!-- ============================================================================ -->
            <!-- TABLA DE DATOS -->
            <!-- ============================================================================ -->
            <DataTable :data="logs.data" :columns="tableColumns" row-key="id"
                empty-message="No hay registros de actividad." :on-row-click="(item) => modalLogs(item.id, item)"
                :row-hover-style="(item) => ({
                    backgroundColor: hoveredId === item.id
                        ? (hoverBg[item.properties.action] ?? '#f9fafb')
                        : ''
                })" @row-hover="(item) => hoveredId = item?.id ?? null">
                <template #row="{ item }">

                    <!-- 1. Acción (primero) -->
                    <td class="px-4 py-2.5 text-center whitespace-nowrap">
                        <span
                            class="inline-flex items-center justify-center gap-1.5 w-28 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset capitalize"
                            :class="getActionConfig(item.properties.action).classes">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    :d="getActionConfig(item.properties.action).icon" />
                            </svg>
                            {{ getActionConfig(item.properties.action).label }}
                        </span>
                    </td>

                    <!-- 2. Usuario -->
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div v-if="item.causer" class="flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-bold shrink-0">
                                {{ getInitials(item.causer.nombre, item.causer.apellido) }}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-800 leading-tight">
                                    {{ toTitleCase(item.causer.nombre) }} {{ toTitleCase(item.causer.apellido)
                                    }}
                                </p>
                                <p class="text-xs uppercase text-gray-400 leading-tight">
                                    {{ item.causer.cargo || item.causer.email || '—' }}
                                </p>
                            </div>
                        </div>
                        <span v-else class="text-xs text-gray-400 italic">Usuario eliminado</span>
                    </td>

                    <!-- 3. Fecha y Hora -->
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <p class="text-xs font-semibold text-gray-700">{{ formatDate(item.created_at) }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ formatTime(item.created_at) }}</p>
                    </td>

                    <!-- 4. Módulo -->
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <p class="text-xs font-semibold text-gray-700">
                            {{ getModuleLabel(item.properties.module) }}
                        </p>
                        <p v-if="getSubjectLabel(item)" class="text-xs text-gray-400 leading-tight">
                            {{ getSubjectLabel(item) }}
                        </p>
                    </td>

                    <!-- 5. Descripción -->
                    <td class="px-4 py-2.5">
                        <p class="text-xs text-gray-600 truncate max-w-xs" :title="item.description">
                            {{ item.description }}
                        </p>
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
                                {{ search ? 'Sin resultados' : 'Sin actividad registrada' }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ search
                                    ? `No se encontraron registros para la busqueda".`
                                    : 'Las acciones realizadas aparecerán aquí.'
                                }}
                            </p>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- ============================================================================ -->
            <!-- FOOTER Y PAGINACIÓN -->
            <!-- ============================================================================ -->
            <div :class="logs.data.length <= 15 ? 'mt-0.5' : 'mt-0'">
                <Paginacion v-if="logs.last_page > 1" :links="logs.links" :from="logs.from" :to="logs.to"
                    :total="logs.total" />
                <Footer />
            </div>
        </div>

        <!-- Modal: Visualizar Logs -->
        <Transition name="fade">
            <ModalLogs v-if="showModal" :data="dataLog" @close="showModal = false" />
        </Transition>
    </div>
</template>
