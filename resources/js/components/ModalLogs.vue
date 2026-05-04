<script setup>
import Modal from '@/components/Modal.vue';

const props = defineProps({
    data: Object
});

const emit = defineEmits(['close']);

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const actionConfig = {
    'crear': { label: 'Crear', icon: 'M12 4v16m8-8H4', fondoIcon: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-600 dark:text-emerald-400', badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' },
    'actualizar': { label: 'Actualizar', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', fondoIcon: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-600 dark:text-blue-400', badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' },
    'eliminar': { label: 'Eliminar', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', fondoIcon: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-700 dark:text-red-400', badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
    'habilitado': { label: 'habilitado', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', fondoIcon: 'bg-teal-50 dark:bg-teal-900/20', text: 'text-teal-600 dark:text-teal-400', badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300' },
    'deshabilitado': { label: 'deshabilitado', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', fondoIcon: 'bg-rose-50 dark:bg-rose-900/20', text: 'text-rose-700 dark:text-rose-400', badge: 'bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' },
    'importar': { label: 'Importar', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12', fondoIcon: 'bg-purple-50 dark:bg-purple-900/20', text: 'text-purple-700 dark:text-purple-400', badge: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' },
    'pago realizado': { label: 'Pago realizado', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', fondoIcon: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-600 dark:text-amber-400', badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' },
    'reporte imprimido': { label: 'Reporte imprimido', icon: 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z', fondoIcon: 'bg-pink-50 dark:bg-pink-900/20', text: 'text-pink-600 dark:text-pink-400', badge: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300' },
    'ver': { label: 'Ver', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7z', fondoIcon: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-600 dark:text-indigo-400', badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' },
    'agregar': { label: 'Agregar', icon: 'M12 4v16m8-8H4', fondoIcon: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-600 dark:text-green-400', badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
    'reset contraseña': { label: 'Resetear Contraseña', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', fondoIcon: 'bg-violet-50 dark:bg-violet-900/20', text: 'text-violet-600 dark:text-violet-400', badge: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300' },
    'login': { label: 'Login', icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1', fondoIcon: 'bg-sky-50 dark:bg-sky-900/20', text: 'text-sky-600 dark:text-sky-400', badge: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' },
    'logout': { label: 'Logout', icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1', fondoIcon: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-600 dark:text-orange-400', badge: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' },
    'session-expired': { label: 'Sesión Expirada', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', fondoIcon: 'bg-yellow-50 dark:bg-yellow-900/20', text: 'text-yellow-600 dark:text-yellow-400', badge: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' },
    'boleta_descargada': {
        label: 'Boleta Descargada',
        icon: 'M8 3a2 2 0 0 0-2 2v3h12V5a2 2 0 0 0-2-2H8Zm-3 7a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h1v-4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v4h1a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H5Zm4 11a1 1 0 0 1-1-1v-4h8v4a1 1 0 0 1-1 1H9Z',
        fondoIcon: 'bg-teal-50 dark:bg-teal-900/20',
        text: 'text-teal-600 dark:text-teal-400',
        badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'
    },
};

// Agrega esto en ModalLogs.vue y en el index de logs
const moduleLabels = {
    'BandejaPago': 'Bandeja de Pago',
    'Tutor': 'Tutores',
    'Persona': 'Beneficiarios',
    'Carnet': 'Carnets',
    'Usuario': 'Usuarios',
    'Rol': 'Roles',
    'Discapacidad': 'Discapacidades',
    'Distrito': 'Distritos',
    'Gestion': 'Gestión',
    'Mes': 'Meses',
    'HistorialEstados': 'Historial Estados',
    'Reset': 'Resetear Contraseña',
    'Autenticación': 'Autenticación',
};

const getModuleLabel = (module) => moduleLabels[module] ?? module;

const getConfig = (action) => actionConfig[action] || {
    label: action,
    icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    fondoIcon: 'bg-gray-50 dark:bg-gray-700/30',
    text: 'text-gray-600 dark:text-gray-400',
    badge: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
};

const getInitials = (nombre, apellido) => {
    if (!nombre && !apellido) return '?';
    return `${(nombre || '')[0] || ''}${(apellido || '')[0] || ''}`.toUpperCase();
};

const FIELD_ORDER_BY_MODULE = {
    'Beneficiario': ['beneficiario', 'tutor asignado', 'c.i.', 'c.i. tutor', 'fecha nacimiento', 'distrito'],
    'Carnet': ['beneficiario', 'c.i.', 'c.i. disc.', 'discapacidad', 'fecha emision', 'fecha vencimiento'],
    'Tutor': ['nombre completo', 'c.i.', 'complemento', 'teléfono', 'dirección', 'email'],
    'Usuario': ['nombre', 'rol', 'cargo', 'email'],
    'Gestion': ['gestion', 'fecha inicio', 'fecha fin', 'estado'],
    'Mes': ['mes', 'gestion', 'monto', 'estado'],
    'HistorialEstados': ['beneficiario', 'estado', 'fecha inicio', 'fecha fin', 'observaciones', 'registrado por'],
    'Rol': ['rol', 'nivel jerarquía', 'permisos'],
    // agregás los que necesites...
};

const FIELD_EXCLUDE_BY_MODULE = {
    'Tutor': ['id_tutor', 'nombre_completo', 'created_at', 'updated_at'],
    'Persona': ['id_persona', 'created_at', 'updated_at'],
    'Carnet': ['id_carnet', 'created_at', 'updated_at'],
    'Usuario': ['id', 'created_at', 'updated_at'],
    // agregás los que necesites...
};

const getOrderedEntries = (obj) => {
    if (!obj) return [];
    const module = props.data.properties.module;
    const order = FIELD_ORDER_BY_MODULE[module] ?? [];
    const exclude = FIELD_EXCLUDE_BY_MODULE[module] ?? [];  // ← nuevo

    const allEntries = Object.entries(obj).filter(([key]) => !exclude.includes(key));  // ← filtrar primero

    if (order.length === 0) return allEntries;

    const known = order
        .filter(key => key in obj && !exclude.includes(key))
        .map(key => [key, obj[key]]);

    const rest = allEntries.filter(([key]) => !order.includes(key));

    return [...known, ...rest];
};

const groupPermissions = (permisosString) => {
    if (!permisosString) return {};
    const lista = permisosString.split(', ').map(p => p.trim());
    const grupos = {};

    lista.forEach(permiso => {
        if (permiso.includes('-')) {
            // ← el padre es el sufijo: "agregar-gestion" → padre: "gestion", hijo: "agregar"
            const partes = permiso.split('-');
            const padre = partes.slice(1).join('-');
            const hijo = partes[0];
            if (!grupos[padre]) grupos[padre] = [];
            grupos[padre].push(hijo);
        } else {
            // es padre sin hijos
            if (!grupos[permiso]) grupos[permiso] = [];
        }
    });

    return grupos;
};

const getPermissionDiff = (anteriorString, nuevoString) => {
    if (!anteriorString && !nuevoString) return { eliminados: {}, agregados: {} };

    const anterior = anteriorString ? anteriorString.split(', ').map(p => p.trim()) : [];
    const nuevo = nuevoString ? nuevoString.split(', ').map(p => p.trim()) : [];

    const eliminados = anterior.filter(p => !nuevo.includes(p));
    const agregados = nuevo.filter(p => !anterior.includes(p));

    return {
        eliminados: groupPermissions(eliminados.join(', ')),
        agregados: groupPermissions(agregados.join(', ')),
    };
};


</script>

<template>
    <Modal :showFooter="false" maxWidth="max-w-xl" @close="emit('close')"
        :fondoIcon="getConfig(props.data.properties.action).badge">
        <template #label1>Registro de Actividad</template>
        <template #label2>ID #{{ props.data.id }}</template>
        <template #icon>
            <svg class="w-5 h-5" :class="getConfig(props.data.properties.action).text" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    :d="getConfig(props.data.properties.action).icon" />
            </svg>
        </template>

        <!-- Body scrollable -->
        <div class="overflow-y-auto flex-1 px-0 py-1 space-y-4">
            <!-- {{ props.data }} -->
            <!-- Fecha y hora + módulo/acción -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Fecha -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1">Fecha</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ formatDate(props.data.created_at) }}
                    </p>
                </div>
                <!-- Hora -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1">Hora</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ formatTime(props.data.created_at) }}
                    </p>
                </div>
                <!-- Módulo -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1">Módulo</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 capitalize">
                        {{ getModuleLabel(props.data.properties.module) }}
                    </p>
                </div>
                <!-- Acción -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1">Acción</p>
                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full capitalize"
                        :class="getConfig(props.data.properties.action).badge">
                        {{ getConfig(props.data.properties.action).label }}
                    </span>
                </div>
            </div>

            <!-- Descripción -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-1.5">Descripción</p>
                <p class="text-sm font-semibold p-1 rounded-md" :class="getConfig(props.data.properties.action).badge">
                    {{ props.data.description }}
                </p>
            </div>

            <!-- Realizado por -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 mb-2">Realizado por</p>
                <div v-if="props.data.causer" class="flex items-center gap-3">
                    <!-- Avatar con iniciales -->
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-gray-600 text-xs font-bold shrink-0"
                        :class="getConfig(props.data.properties.action).text.replace('text-', 'bg-').replace(' dark:text-', ' dark:bg-').split(' ')[0]"
                        style="background: currentColor">
                        <span class="text-white">
                            {{ getInitials(props.data.causer.nombre, props.data.causer.apellido) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 capitalize">
                            {{ props.data.causer.nombre }} {{ props.data.causer.apellido }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 capitalize">
                            {{ props.data.causer.rol || props.data.causer.email || '—' }}
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 italic">Usuario eliminado</p>
            </div>

            <!-- Detalles según tipo de acción -->

            <!-- CREAR -->
            <template v-if="props.data.properties.action === 'crear' && props.data.properties.details?.after">
                <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/30 overflow-hidden">
                    <div class="px-4 py-2.5" :class="getConfig(props.data.properties.action).badge">
                        <p class="text-xs font-semibold uppercase tracking-wide">
                            Datos registrados
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <template v-for="[key, value] in getOrderedEntries(props.data.properties.details.after)"
                            :key="key">

                            <!-- Permisos agrupados -->
                            <div v-if="key === 'permisos'" class="col-span-2 bg-white dark:bg-gray-900 px-4 py-3">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2 capitalize">{{ key }}</p>
                                <div class="flex flex-wrap gap-x-4 gap-y-3">
                                    <div v-for="(hijos, padre) in groupPermissions(value)" :key="padre">
                                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 capitalize mb-1">{{
                                            padre }}</p>
                                        <div v-if="hijos.length" class="flex flex-wrap gap-1 pl-2">
                                            <span v-for="hijo in hijos" :key="hijo"
                                                class="text-xs bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded-full capitalize">
                                                {{ hijo }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campo normal -->
                            <div v-else class="bg-white dark:bg-gray-900 px-4 py-3">
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5 capitalize">{{ key }}</p>
                                <p class="text-sm text-gray-800 dark:text-gray-200 break-words font-medium">{{ value ??
                                    '—' }}</p>
                            </div>

                        </template>
                    </div>
                </div>
            </template>

            <!-- BOLETA DESCARGADA -->
            <template v-if="props.data.properties.action === 'boleta_descargada'">
                <div class="rounded-xl border border-teal-100 dark:border-teal-900/30 overflow-hidden">
                    <div class="bg-teal-50 dark:bg-teal-900/20 px-4 py-2.5">
                        <p class="text-xs font-semibold text-teal-700 dark:text-teal-400 uppercase tracking-wide">
                            Detalles de descarga
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div v-if="props.data.properties.gestion" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Gestión</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.gestion }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.mes" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Mes</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.mes }}
                            </p>
                        </div>

                        <!-- ✅ ESTADO ANULADO -->
                        <div class="bg-white dark:bg-gray-900 px-4 py-3 col-span-2">
                            <p class="text-xs text-gray-400 mb-0.5">Estado</p>
                            <template v-if="props.data.properties.anulado">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-600">
                                    Anulado
                                </span>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ props.data.properties.anulado_en }}
                                </p>
                            </template>
                            <template v-else>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-teal-100 text-teal-600">
                                    Válido
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ACTUALIZAR -->
            <template v-if="props.data.properties.action === 'actualizar' && props.data.properties.details?.modified">
                <div class="rounded-xl border border-blue-100 dark:border-blue-900/30 overflow-hidden">
                    <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2.5">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                            Campos modificados
                        </p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/30">
                        <div v-for="(value, key) in props.data.properties.details.modified" :key="key"
                            class="px-4 py-3 bg-white dark:bg-gray-900">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize mb-2">{{ key }}
                            </p>

                            <template v-if="key === 'permisos'">
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Eliminados -->
                                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                        <p class="text-xs text-red-400 mb-2 font-semibold">Eliminados</p>
                                        <template
                                            v-if="Object.keys(getPermissionDiff(props.data.properties.details.before[key], props.data.properties.details.after[key]).eliminados).length">
                                            <div class="space-y-2">
                                                <div v-for="(hijos, padre) in getPermissionDiff(props.data.properties.details.before[key], props.data.properties.details.after[key]).eliminados"
                                                    :key="padre">
                                                    <p
                                                        class="text-xs font-bold text-red-700 dark:text-red-300 capitalize mb-1">
                                                        {{ padre }}</p>
                                                    <div v-if="hijos.length" class="flex flex-wrap gap-1 pl-2">
                                                        <span v-for="hijo in hijos" :key="hijo"
                                                            class="text-xs bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300 px-2 py-0.5 rounded-full capitalize">
                                                            {{ hijo }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <p v-else class="text-xs text-red-300 italic">Sin eliminados</p>
                                    </div>

                                    <!-- Agregados -->
                                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                                        <p class="text-xs text-emerald-400 mb-2 font-semibold">Agregados</p>
                                        <template
                                            v-if="Object.keys(getPermissionDiff(props.data.properties.details.before[key], props.data.properties.details.after[key]).agregados).length">
                                            <div class="space-y-2">
                                                <div v-for="(hijos, padre) in getPermissionDiff(props.data.properties.details.before[key], props.data.properties.details.after[key]).agregados"
                                                    :key="padre">
                                                    <p
                                                        class="text-xs font-bold text-emerald-700 dark:text-emerald-300 capitalize mb-1">
                                                        {{ padre }}</p>
                                                    <div v-if="hijos.length" class="flex flex-wrap gap-1 pl-2">
                                                        <span v-for="hijo in hijos" :key="hijo"
                                                            class="text-xs bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 px-2 py-0.5 rounded-full capitalize">
                                                            {{ hijo }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <p v-else class="text-xs text-emerald-300 italic">Sin agregados</p>
                                    </div>
                                </div>
                            </template>

                            <!-- Campos normales -->
                            <template v-else>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">
                                        <p class="text-xs text-red-400 mb-0.5">Anterior</p>
                                        <p class="text-sm text-red-700 dark:text-red-300 font-medium">
                                            {{ props.data.properties.details.before[key] ?? '—' }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                    <div class="flex-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg px-3 py-2">
                                        <p class="text-xs text-emerald-400 mb-0.5">Nuevo</p>
                                        <p class="text-sm text-emerald-700 dark:text-emerald-300 font-medium">
                                            {{ props.data.properties.details.after[key] ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>
                </div>
            </template>

            <!-- ELIMINAR -->
            <template v-if="props.data.properties.action === 'eliminar' && props.data.properties.details?.before">
                <div class="rounded-xl border border-red-100 dark:border-red-900/30 overflow-hidden">
                    <div class="bg-red-50 dark:bg-red-900/20 px-4 py-2.5">
                        <p class="text-xs font-semibold text-red-700 dark:text-red-400 uppercase tracking-wide">
                            Datos eliminados
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div v-for="[key, value] in getOrderedEntries(props.data.properties.details.before)" :key="key"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5 capitalize">{{ key }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-200 break-words font-medium">{{ value ?? '—'
                            }}</p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- IMPORTAR -->
            <template v-if="props.data.properties.action === 'importar' && props.data.properties.details">

                <!-- Resumen de habilitación masiva -->
                <div class="rounded-xl border border-purple-100 dark:border-purple-900/30 overflow-hidden">
                    <div class="bg-purple-50 dark:bg-purple-900/20 px-4 py-2.5">
                        <p class="text-xs font-semibold text-purple-700 dark:text-purple-400 uppercase tracking-wide">
                            Resumen de habilitación
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div v-if="props.data.properties.details.mes" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Mes</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.details.mes }}
                            </p>
                        </div>

                        <div v-if="props.data.properties.details.beneficiarios_habilitados != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Beneficiarios habilitados</p>
                            <p class="text-sm font-bold text-emerald-600">
                                {{ props.data.properties.details.beneficiarios_habilitados }}
                            </p>
                        </div>

                        <div v-if="props.data.properties.details.monto != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Monto por beneficiario</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                Bs. {{ Number(props.data.properties.details.monto).toLocaleString('es-BO', {
                                    minimumFractionDigits: 2
                                }) }}
                            </p>
                        </div>

                        <div v-if="props.data.properties.details.presupuesto != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Presupuesto mensual</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                Bs. {{ Number(props.data.properties.details.presupuesto).toLocaleString('es-BO', {
                                    minimumFractionDigits: 2
                                }) }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- //{{ props.data.properties.details }} -->
                <!-- Resumen avanzado -->
                <div v-if="
                    props.data.properties.details.registros_depurados != null ||
                    props.data.properties.details.bajas_temporales_omitidas != null ||
                    props.data.properties.details.bajas_definitivas_omitidas != null
                " class="rounded-xl border border-indigo-100 dark:border-indigo-900/30 overflow-hidden">

                    <div class="bg-indigo-50 dark:bg-indigo-900/20 px-4 py-2.5">
                        <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wide">
                            Resumen avanzado
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-px bg-gray-100 dark:bg-gray-700/30">

                        <!-- Depurados -->
                        <div v-if="props.data.properties.details.registros_depurados != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Registros depurados</p>
                            <p class="text-sm font-bold text-blue-600">
                                {{ props.data.properties.details.registros_depurados }}
                            </p>
                        </div>

                        <!-- Bajas temporales omitidas -->
                        <div v-if="props.data.properties.details.bajas_temporales_omitidas != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Bajas temporales omitidas</p>
                            <p class="text-sm font-bold text-orange-500">
                                {{ props.data.properties.details.bajas_temporales_omitidas }}
                            </p>
                        </div>

                        <!-- Bajas definitivas omitidas -->
                        <div v-if="props.data.properties.details.bajas_definitivas_omitidas != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Bajas definitivas omitidas</p>
                            <p class="text-sm font-bold text-red-500">
                                {{ props.data.properties.details.bajas_definitivas_omitidas }}
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Detalle del archivo -->
                <div class="rounded-xl border border-gray-100 dark:border-gray-700/30 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-800/50 px-4 py-2.5">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                            Detalle del archivo
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Total</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.details.total_records }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Procesados</p>
                            <p class="text-sm font-bold text-emerald-600">
                                {{ props.data.properties.details.successful }}
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Fallidos</p>
                            <p class="text-sm font-bold"
                                :class="props.data.properties.details.failed > 0 ? 'text-red-500' : 'text-gray-400'">
                                {{ props.data.properties.details.failed }}
                            </p>
                        </div>
                    </div>

                    <!-- Archivo -->
                    <div class="bg-white dark:bg-gray-900 px-4 py-3 border-t border-gray-100 dark:border-gray-700/30">
                        <p class="text-xs text-gray-400 mb-0.5">Archivo</p>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                            {{ props.data.properties.details.filename }}
                        </p>
                    </div>

                    <!-- Fecha de importación -->
                    <div v-if="props.data.properties.details.import_date"
                        class="bg-white dark:bg-gray-900 px-4 py-3 border-t border-gray-100 dark:border-gray-700/30">
                        <p class="text-xs text-gray-400 mb-0.5">Fecha de importación</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ props.data.properties.details.import_date }}
                        </p>
                    </div>

                    <!-- Info técnica -->
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30 border-t">
                        <div v-if="props.data.properties.ip" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">IP</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ props.data.properties.ip }}
                            </p>
                        </div>

                        <div v-if="props.data.properties.environment" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Entorno</p>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">
                                {{ props.data.properties.environment }}
                            </p>
                        </div>
                    </div>

                    <!-- Errores -->
                    <div v-if="props.data.properties.details.errors?.length"
                        class="bg-white dark:bg-gray-900 px-4 py-3 border-t border-red-100 dark:border-red-900/20">
                        <p class="text-xs text-red-500 font-medium mb-2">Errores encontrados</p>
                        <ul class="space-y-1">
                            <li v-for="(error, i) in props.data.properties.details.errors" :key="i"
                                class="text-xs text-red-600 dark:text-red-400 flex items-start gap-1.5">
                                <span class="mt-0.5 shrink-0">•</span>{{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </template>

            <!-- DEBUG TEMPORAL - quitar después -->
            <!-- <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-auto max-h-60">
    {{ JSON.stringify(props.data.properties, null, 2) }}
</pre> -->

            <!-- HABILITADO / DESHABILITADO / PAGO -->
            <template
                v-if="['habilitado', 'deshabilitado', 'pago realizado'].includes(props.data.properties.action) && props.data.properties.detalles">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700/30 overflow-hidden">
                    <div class="px-4 py-2.5" :class="getConfig(props.data.properties.action).fondoIcon">
                        <p class="text-xs font-semibold uppercase tracking-wide"
                            :class="getConfig(props.data.properties.action).text">
                            Detalles
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div v-if="props.data.properties.detalles.beneficiario"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Beneficiario</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{
                                props.data.properties.detalles.beneficiario }}</p>
                        </div>
                        <div v-if="props.data.properties.detalles.mes" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Periodo</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{
                                props.data.properties.detalles.mes }}</p>
                        </div>
                        <div v-if="props.data.properties.detalles.ci" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Cédula de Identidad</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.detalles.ci || 'Sin cédula de identidad' }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.detalles.monto != null"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Monto</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                Bs. {{ Number(props.data.properties.detalles.monto).toLocaleString('es-BO', {
                                    minimumFractionDigits: 2
                                }) }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.detalles.numero_boleta"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">N° Boleta</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{
                                props.data.properties.detalles.numero_boleta }}</p>
                        </div>
                        <div v-if="props.data.properties.detalles.tipo_pago"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Tipo de Pago</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 capitalize">{{
                                props.data.properties.detalles.tipo_pago }}</p>
                        </div>
                        <div v-if="props.data.properties.detalles.estado" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Estado</p>
                            <p class="text-sm font-medium dark:text-gray-200 capitalize"
                                :class="props.data.properties.detalles.estado === 'Habilitado' ? 'text-teal-600' : 'text-slate-600'">
                                {{ props.data.properties.detalles.estado }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.detalles.observacion"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Observación</p>
                            <p class="text-sm font-medium  dark:text-gray-200">
                                <span v-if="props.data.properties.detalles.observacion" class="text-gray-800">
                                    {{ props.data.properties.detalles.observacion }}
                                </span>
                                <span v-else class="text-red-500 italic">sin observacion</span>
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- REPORTE IMPRIMIDO -->
            <template
                v-if="props.data.properties.action === 'reporte imprimido' && props.data.properties.report_details">
                <div class="rounded-xl border border-pink-100 dark:border-pink-900/30 overflow-hidden">
                    <div class="bg-pink-50 dark:bg-pink-900/20 px-4 py-2.5">
                        <p class="text-xs font-semibold text-pink-700 dark:text-pink-400 uppercase tracking-wide">
                            Detalles del reporte
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div v-if="props.data.properties.report_details.name"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Nombre</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.report_details.name }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.anulado"
                            class="bg-white dark:bg-gray-900 px-4 py-3 col-span-2">
                            <p class="text-xs text-gray-400 mb-0.5">Estado</p>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-600">
                                Anulado
                            </span>
                            <p class="text-xs text-gray-400 mt-1">{{ props.data.properties.anulado_en }}</p>
                        </div>
                        <div v-else class="bg-white dark:bg-gray-900 px-4 py-3 col-span-2">
                            <p class="text-xs text-gray-400 mb-0.5">Estado</p>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-teal-100 text-teal-600">
                                Válido
                            </span>
                        </div>
                        <div v-if="props.data.properties.report_details.format"
                            class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Formato</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 uppercase">
                                {{ props.data.properties.report_details.format }}
                            </p>
                        </div>
                        <!-- ✅ Parámetros del reporte -->
                        <template v-if="props.data.properties.report_details.parameters">
                            <div v-for="(value, key) in props.data.properties.report_details.parameters" :key="key"
                                class="bg-white dark:bg-gray-900 px-4 py-3">
                                <p class="text-xs text-gray-400 mb-0.5 capitalize">{{ key }}</p>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ value ?? '—' }}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- INFORMACIÓN DE AUDITORÍA -->
            <template v-if="props.data.properties.ip">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700/30 overflow-hidden">
                    <div class="px-4 py-2.5" :class="getConfig(props.data.properties.action).badge">
                        <p class="text-xs font-semibold uppercase tracking-wide">
                            Información de Auditoría
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-px bg-gray-100 dark:bg-gray-700/30">
                        <div v-if="props.data.properties.ip" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Dirección IP</p>
                            <p class="text-sm font-mono font-medium text-gray-800 dark:text-gray-200">
                                {{ props.data.properties.ip }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.environment" class="bg-white dark:bg-gray-900 px-4 py-3">
                            <p class="text-xs text-gray-400 mb-0.5">Entorno</p>
                            <p class="text-sm font-semibold capitalize bg-white"
                                :class="getConfig(props.data.properties.action).text">
                                {{ props.data.properties.environment }}
                            </p>
                        </div>
                        <div v-if="props.data.properties.user_agent"
                            class="bg-white dark:bg-gray-900 px-4 py-3 col-span-2">
                            <p class="text-xs text-gray-400 mb-0.5">Navegador / Dispositivo</p>
                            <p class="text-xs font-mono text-gray-600 dark:text-gray-400 break-all leading-relaxed">
                                {{ props.data.properties.user_agent }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <template #footer>
            <div class="px-5 py-3.5 border-t border-gray-100 dark:border-gray-700/50 flex justify-end">
                <button @click="emit('close')"
                    class="px-12 py-2.5 rounded-xl text-sm font-medium text-white bg-blue-600 hover:bg-blue-500">
                    Cerrar
                </button>
            </div>
        </template>
    </Modal>
</template>
