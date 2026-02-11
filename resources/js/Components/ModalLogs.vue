<script setup>
const props = defineProps({
    data: Object
});

const emit = defineEmits(['close']);

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

// Función para obtener el color basado en la acción
const getActionColor = (action) => {
    const colors = {
        'crear': 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/20',
        'actualizar': 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/20',
        'eliminar': 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/20',
        'delete': 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/20',
        'habilitado': 'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/20',
        'deshabilitado': 'text-slate-600 bg-slate-100 dark:text-slate-400 dark:bg-slate-900/20',
        'importar': 'text-purple-600 bg-purple-100 dark:text-purple-400 dark:bg-purple-900/20',
        'error': 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/20',
        'ver': 'text-indigo-600 bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/20',
        'pago realizado': 'text-teal-600 bg-teal-100 dark:text-teal-400 dark:bg-teal-900/20',
        'reporte imprimido': 'text-pink-600 bg-pink-100 dark:text-pink-400 dark:bg-pink-900/20',
        'agregar': 'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/20'
    };

    return colors[action] || 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-900/20';
};

// Función para obtener clase CSS para la etiqueta de acción
const getActionColorClass = (action) => {
    const classes = {
        'crear': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'actualizar': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'eliminar': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        'delete': 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        'habilitado': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        'deshabilitado': 'bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-400',
        'importar': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        'error': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'ver': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        'pago realizado': 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
        'reporte imprimido': 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
        'agregar': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400'
    };

    return classes[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>

<template>
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 overflow-y-auto backdrop-blur-sm">
    <div class="relative w-full max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 my-8">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-4 border-b dark:border-gray-600/50">
            <div class="flex items-center space-x-3">
                <div :class="[getActionColor(props.data.properties.action), 'p-2 rounded-lg']">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 truncate">Registro de Actividad</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(props.data.created_at) }}</p>
                </div>
            </div>
            <button @click="$emit('close')" class="text-gray-500 hover:text-red-700 dark:text-gray-400 dark:hover:text-red-200 bg-transparent hover:bg-red-100 dark:hover:bg-gray-700 rounded-lg p-2 transition-colors duration-200">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-4 overflow-y-auto max-h-[calc(100vh-16rem)] space-y-4">
            <!-- Acción y Usuario -->
            <div class="bg-gray-50 dark:bg-gray-700/30 p-3 rounded-lg">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Descripción</h3>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.description }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Realizado por</h3>
                        <div class="flex items-center flex-wrap gap-2">
                            <span class="capitalize text-sm text-gray-900 dark:text-gray-100">
                                {{ props.data.causer?.nombre && props.data.causer?.apellido ? 
                                props.data.causer.nombre + ' ' + props.data.causer.apellido : 
                                'Usuario eliminado' }}
                            </span>
                            <span v-if="props.data.causer?.rol" class="capitalize px-2 py-1 text-xs font-medium rounded-full" :class="getActionColor(props.data.properties.action)">
                                {{ props.data.causer.rol }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles de la Acción -->
            <div class="bg-gray-50 dark:bg-gray-700/30 p-3 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Detalles de la Acción</h3>
                <div class="space-y-3">
                    <!-- Detalles de la Acción -->
                    <div class="bg-gray-50 dark:bg-gray-700/30 p-3 rounded-lg">
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Módulo</h4>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 capitalize">{{ props.data.properties.module }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Acción</h4>
                                    <p class="inline-block text-xs capitalize px-3 py-1 rounded-full font-medium" :class="getActionColorClass(props.data.properties.action)">
                                        {{ props.data.properties.action }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Datos Modificados -->
                    <div v-if="props.data.properties.details" class="mt-3">
                        <!-- Para Importación -->
                        <template v-if="props.data.properties.action === 'importar'">
                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                                Detalles de la Importación
                            </h4>
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Total Registros</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.details.total_records }}</span>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Exitosos</span>
                                        <span class="text-sm text-green-600 dark:text-green-400">{{ props.data.properties.details.successful }}</span>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Fallidos</span>
                                        <span class="text-sm text-red-600 dark:text-red-400">{{ props.data.properties.details.failed }}</span>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Archivo</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.details.filename }}</span>
                                    </div>
                                </div>

                                <!-- Errores -->
                                <div v-if="props.data.properties.details.errors && props.data.properties.details.errors.length > 0">
                                    <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Errores Encontrados</h4>
                                    <div class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li v-for="(error, index) in props.data.properties.details.errors" :key="index" class="text-sm text-red-600 dark:text-red-400">
                                                {{ error }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!-- Para Creación -->
                        <template v-if="props.data.properties.action === 'crear'">
                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                                Datos Registrados
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div v-for="(value, key) in props.data.properties.details.after" :key="key" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">{{ key }}</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100 break-words">{{ value }}</span>
                                </div>
                            </div>
                        </template>

                        <!-- Para Actualización -->
                        <template v-if="props.data.properties.action === 'actualizar'">
                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                                Campos Modificados
                            </h4>
                            <div class="grid grid-cols-1 gap-2">
                                <div v-for="(value, key) in props.data.properties.details.modified" :key="key" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">{{ key }}</span>
                                    <div class="grid grid-cols-2 gap-2 mt-1">
                                        <div class="text-sm">
                                            <span class="text-red-500 dark:text-red-400 text-xs">Anterior: </span>
                                            <span class="text-gray-900 dark:text-gray-100">
                                                {{ props.data.properties.details.before[key] }}
                                            </span>
                                        </div>

                                        <div class="text-sm">
                                            <span class="text-green-500 dark:text-green-400 text-xs">Nuevo: </span>
                                            <span class="text-gray-900 dark:text-gray-100">
                                                {{ props.data.properties.details.after[key] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Para Eliminación -->
                        <template v-if="props.data.properties.action === 'eliminar'">
                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                                Datos Eliminados
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div v-for="(value, key) in props.data.properties.details.before" :key="key" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">{{ key }}</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100 break-words">{{ value }}</span>
                                </div>
                            </div>
                        </template>

                    </div>
                    <!-- NUEVO: Para Habilitación/Deshabilitación -->
                    <template v-if="props.data.properties.action === 'habilitado' || props.data.properties.action === 'deshabilitado' || props.data.properties.action === 'pago realizado'">
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                            <span v-if="props.data.properties.action === 'habilitado'">Detalles de Habilitación</span>
                            <span v-else-if="props.data.properties.action === 'deshabilitado'">Detalles de Deshabilitación</span>
                            <span v-else>Detalles de Pago</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div v-if="props.data.properties.detalles && props.data.properties.detalles.mes" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Mes</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.detalles.mes }}</span>
                            </div>
                            <div v-if="props.data.properties.detalles && props.data.properties.detalles.beneficiario" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Beneficiario</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.detalles.beneficiario }}</span>
                            </div>
                            <div v-if="props.data.properties.detalles && props.data.properties.detalles.estado" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Estado</span>
                                <span class="text-sm" :class="{
                                            'text-green-600 dark:text-green-400': props.data.properties.detalles.estado === 'Habilitado',
                                            'text-red-600 dark:text-red-400': props.data.properties.detalles.estado === 'Deshabilitado'
                                        }">{{ props.data.properties.detalles.estado }}</span>
                            </div>
                            <div v-if="props.data.properties.detalles && props.data.properties.detalles.observacion" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Observación</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.detalles.observacion || 'Sin observaciones' }}</span>
                            </div>
                        </div>
                    </template>

                    <!-- Para reportes impresos -->
                    <template v-if="props.data.properties.action === 'reporte imprimido'">
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                            Detalles del Reporte
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div v-if="props.data.properties.report_details && props.data.properties.report_details.name" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Nombre del Reporte</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.report_details.name }}</span>
                            </div>
                            <div v-if="props.data.properties.report_details && props.data.properties.report_details.format" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Formato</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.report_details.format }}</span>
                            </div>

                            <!-- Mostrar parámetros del reporte si existen -->
                            <div v-if="props.data.properties.report_details && props.data.properties.report_details.parameters" class="bg-white dark:bg-gray-800 p-2 rounded-lg col-span-1 sm:col-span-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1">Parámetros</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="(value, key) in props.data.properties.report_details.parameters" :key="key" class="text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">{{ key }}:</span>
                                        <span class="text-gray-900 dark:text-gray-100 ml-1">{{ value }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Entidad relacionada si existe -->
                            <div v-if="props.data.properties.report_details && props.data.properties.report_details.related_entity" class="bg-white dark:bg-gray-800 p-2 rounded-lg">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">Entidad Relacionada</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ props.data.properties.report_details.related_entity.type }}
                                    (ID: {{ props.data.properties.report_details.related_entity.id }})
                                </span>
                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="bg-gray-50 dark:bg-gray-700/30 p-3 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Información del Sistema</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">IP</h4>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ props.data.properties.ip }}</p>
                    </div>
                    <div>
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ambiente</h4>
                        <p class="text-sm text-gray-900 dark:text-gray-100 capitalize">{{ props.data.properties.environment }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Navegador</h4>
                        <p class="text-sm text-gray-900 dark:text-gray-100 break-words">{{ props.data.properties.user_agent }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end p-4 border-t dark:border-gray-600/50">
            <button @click="$emit('close')" class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium transition-colors duration-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800">
                Cerrar
            </button>
        </div>
    </div>
</div>
</template>
