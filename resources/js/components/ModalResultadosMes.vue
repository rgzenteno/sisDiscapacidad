<template>
    <Modal v-if="modelValue && datosValidos" :showHeader="true" :showFooter="false" maxWidth="max-w-2xl"
        @close="$emit('close')">
        <template #icon>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                <Icon :icon-button="true" name="fileCheck" class-name="w-5 h-5 text-white" :size="20" />
            </div>
        </template>
        <template #label1>Resultados del Procesamiento</template>
        <template #label2>Resumen de personas procesadas </template>

        <!-- Body -->
        <div class="py-0 px-1 overflow-y-auto flex-1 space-y-5">
            <!-- Encabezado de éxito -->
            <div class="bg-gradient-to-r from-blue-500 to-teal-600 rounded-xl p-5 text-white">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 pb-0 bg-white/20 rounded-lg">
                        <Icon :icon-button="true" name="checkCircle" class-name="text-white" :size="20" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">¡Mes cargado correctamente!</h3>
                        <p class="text-emerald-100 text-sm">
                            Se registró el mes de
                            <strong class="text-white">{{
                                nombreMesTexto
                            }}</strong>
                            en el sistema.
                        </p>
                    </div>
                </div>
                <p class="text-sm text-emerald-100 leading-relaxed">
                    <!-- Habilitados (siempre visible) -->
                    <span>
                        Se habilitaron
                        <strong class="text-white">{{ datos.habilitados?.length || 0 }}</strong>
                        beneficiarios
                    </span>

                    <!-- Insertados (opcional) -->
                    <span v-if="datos.insertados?.length">
                        {{ datos.depurados?.length ? ',' : ' y' }}
                        se agregaron
                        <strong class="text-white">{{ datos.insertados.length }}</strong>
                        nuevos beneficiarios
                    </span>

                    <!-- Depurados (opcional) -->
                    <span v-if="datos.depurados?.length">
                        {{ datos.insertados?.length ? ' y' : ' y' }}
                        se actualizó el estado de
                        <strong class="text-white">{{ datos.depurados.length }}</strong>
                        beneficiarios
                    </span>

                    <!-- Punto final siempre -->
                    <span>.</span>
                </p>
            </div>

            <!-- Resumen de contadores -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <div
                    class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 py-2 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center gap-1 mb-1">
                        <Icon :icon-button="true" name="users" class-name="text-blue-600" :size="20" />
                        <span
                            class="text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Total</span>
                    </div>
                    <p class="text-xl font-bold text-blue-900 dark:text-blue-200">
                        {{ datos.total_procesado || 0 }}
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                        procesados
                    </p>
                </div>

                <div
                    class="bg-gradient-to-br from-cyan-50 to-sky-100 dark:from-cyan-900/20 dark:to-sky-800/20 p-4 py-2 rounded-xl border border-cyan-200 dark:border-cyan-700">
                    <div class="flex items-center gap-1 mb-1">
                        <Icon :icon-button="true" name="badgeCheck" class-name="text-cyan-600" :size="20" />
                        <span
                            class="text-xs font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wide">Habilitados</span>
                    </div>
                    <p class="text-xl font-bold text-cyan-900 dark:text-cyan-200">
                        {{ datos.habilitados?.length || 0 }}
                    </p>
                    <p class="text-xs text-cyan-600 dark:text-cyan-400 mt-0.5">
                        activos para pago
                    </p>
                </div>

                <div
                    class="bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-800/20 p-4 py-2 rounded-xl border border-amber-200 dark:border-amber-700">
                    <div class="flex items-center gap-1 mb-1">
                        <Icon :icon-button="true" name="bajaDefinitiva" class-name="text-amber-600"
                            :viewBox="'0 0 20 20'" :size="20" />
                        <span
                            class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">Bajas</span>
                    </div>
                    <p class="text-xl font-bold text-amber-900 dark:text-amber-200">
                        {{ totalBajas }}
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                        omitidos
                    </p>
                </div>

                <div
                    class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-800/20 p-4 py-2 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="flex items-center gap-1 mb-1">
                        <Icon :icon-button="true" name="userAdd" class-name="text-green-600" :size="20" />
                        <span
                            class="text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wide">Nuevos</span>
                    </div>
                    <p class="text-xl font-bold text-green-900 dark:text-green-200">
                        {{ datos.insertados?.length || 0 }}
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">
                        beneficiarios
                    </p>
                </div>
            </div>

            <!-- Buscador global -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                    </svg>
                </div>
                <input v-model="busqueda" type="text" placeholder="Buscar por CI o nombre en todos los registros..."
                    class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-gray-200 dark:placeholder-gray-500 transition-all" />
                <div v-if="busqueda" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <button @click="busqueda = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Sin resultados de búsqueda -->
            <div v-if="busqueda && totalResultadosBusqueda === 0"
                class="text-center py-8 text-gray-400 dark:text-gray-500">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                </svg>
                <p class="text-sm">
                    No se encontraron resultados para <strong>"{{ busqueda }}"</strong>
                </p>
            </div>

            <div class="space-y-3">
                <!-- Nuevos / Insertados -->
                <div v-if="
                    insertadosFiltrados.length > 0 ||
                    (datos.insertados?.length > 0 && !busqueda)
                " class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarInsertados = !mostrarInsertados"
                        class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 px-4 py-1 cursor-pointer hover:from-green-100 hover:to-emerald-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 pb-0 bg-green-100 dark:bg-green-800/50 rounded-lg">
                                    <Icon :icon-button="true" name="userAdd" class-name="text-green-600" :size="20" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-green-900 dark:text-green-200">
                                        Nuevos Beneficiarios
                                    </h4>
                                    <p class="text-xs text-green-700 dark:text-green-400">
                                        Registros agregados por primera vez
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-800/50 px-3 py-1 rounded-full">
                                    {{ busqueda ? insertadosFiltrados.length + " de " : ""
                                    }}{{ datos.insertados?.length || 0 }}
                                    registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarInsertados }"
                                    class="w-6 h-6 text-green-600 dark:text-green-400 transition-transform" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarInsertados"
                        class="bg-gradient-to-b from-green-50/30 to-white dark:from-green-900/10 dark:to-gray-800">
                        <div class="px-4 pt-3 pb-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Estas personas fueron creadas en el sistema por primera vez al
                                cargar este mes.
                            </p>
                        </div>
                        <ul class="px-4 pb-3 space-y-2 max-h-64 overflow-y-auto">
                            <li v-for="(item, index) in busqueda
                                ? insertadosFiltrados
                                : datos.insertados" :key="item.ci || index"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-green-200 dark:hover:border-green-700 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                                    <Icon :icon-button="true" name="user" class-name="text-green-600" :size="20" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ item.nombre }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        CI: {{ item.ci }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-2 py-0.5 rounded-full flex-shrink-0">Nuevo</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Habilitados -->
                <div v-if="
                    habilitadosFiltrados.length > 0 ||
                    (datos.habilitados?.length > 0 && !busqueda)
                " class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarHabilitados = !mostrarHabilitados"
                        class="bg-gradient-to-r from-cyan-50 to-sky-50 dark:from-cyan-900/20 dark:to-sky-900/20 px-4 py-1 cursor-pointer hover:from-cyan-100 hover:to-sky-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 pb-0 bg-cyan-100 dark:bg-cyan-800/50 rounded-lg">
                                    <Icon :icon-button="true" name="badgeCheck" class-name="text-cyan-600" :size="20" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-cyan-900 dark:text-cyan-200">
                                        Personas Habilitadas
                                    </h4>
                                    <p class="text-xs text-cyan-700 dark:text-cyan-400">
                                        Beneficiarios activos para pago
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-cyan-700 dark:text-cyan-300 bg-cyan-100 dark:bg-cyan-800/50 px-3 py-1 rounded-full">
                                    {{ busqueda ? habilitadosFiltrados.length + " de " : ""
                                    }}{{ datos.habilitados?.length || 0 }}
                                    registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarHabilitados }"
                                    class="w-6 h-6 text-cyan-600 dark:text-cyan-400 transition-transform" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarHabilitados"
                        class="bg-gradient-to-b from-cyan-50/30 to-white dark:from-cyan-900/10 dark:to-gray-800">
                        <div class="px-4 pt-3 pb-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Lista completa de beneficiarios activos que recibirán pago este
                                mes.
                            </p>
                        </div>
                        <ul class="px-4 pb-3 space-y-2 max-h-64 overflow-y-auto">
                            <li v-for="(item, index) in busqueda
                                ? habilitadosFiltrados
                                : datos.habilitados" :key="item.ci || index"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-cyan-200 dark:hover:border-cyan-700 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-cyan-100 dark:bg-cyan-900/40 flex items-center justify-center flex-shrink-0">
                                    <Icon :icon-button="true" name="user" class-name="text-cyan-600" :size="20" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ item.nombre }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        CI: {{ item.ci }}
                                    </p>
                                </div>
                                <span v-if="item.fila"
                                    class="text-xs font-medium text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-2 py-0.5 rounded-full flex-shrink-0">Fila
                                    {{ item.fila }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Depurados (cambio de estado) -->
                <div v-if="
                    depuradosFiltrados.length > 0 ||
                    (datos.depurados?.length > 0 && !busqueda)
                " class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarDepurados = !mostrarDepurados"
                        class="bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 px-4 py-1 cursor-pointer hover:from-violet-100 hover:to-purple-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-violet-100 dark:bg-violet-800/50 rounded-lg">
                                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-violet-900 dark:text-violet-200">
                                        Cambios de Estado
                                    </h4>
                                    <p class="text-xs text-violet-700 dark:text-violet-400">
                                        Beneficiarios depurados automáticamente
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-violet-700 dark:text-violet-300 bg-violet-100 dark:bg-violet-800/50 px-3 py-1 rounded-full">
                                    {{ busqueda ? depuradosFiltrados.length + " de " : ""
                                    }}{{ datos.depurados?.length || 0 }}
                                    registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarDepurados }"
                                    class="w-6 h-6 text-violet-600 dark:text-violet-400 transition-transform"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarDepurados"
                        class="bg-gradient-to-b from-violet-50/30 to-white dark:from-violet-900/10 dark:to-gray-800">
                        <div class="px-4 pt-3 pb-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Estos beneficiarios no aparecieron en el PDF y su estado fue
                                cambiado a <strong>depurado</strong> automáticamente.
                            </p>
                        </div>
                        <ul class="px-4 pb-3 space-y-2 max-h-64 overflow-y-auto">
                            <li v-for="(item, index) in busqueda
                                ? depuradosFiltrados
                                : datos.depurados" :key="item.ci || index"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-violet-200 dark:hover:border-violet-700 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        CI: {{ item.ci }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-medium text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 px-2 py-0.5 rounded-full flex-shrink-0">Depurado</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bajas Temporales -->
                <div v-if="
                    bajasTemporalesFiltradas.length > 0 ||
                    (datos.bajas_temporales?.length > 0 && !busqueda)
                " class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarBajasTemporales = !mostrarBajasTemporales"
                        class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 px-4 py-1 cursor-pointer hover:from-yellow-100 hover:to-amber-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 pb-0 bg-yellow-100 dark:bg-yellow-800/50 rounded-lg">
                                    <Icon :icon-button="true" name="bajaTemporal" class-name="text-yellow-600"
                                        :viewBox="'0 0 20 20'" :size="20" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-yellow-900 dark:text-yellow-200">
                                        Bajas Temporales
                                    </h4>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-400">
                                        Omitidos — baja temporal vigente
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-800/50 px-3 py-1 rounded-full">
                                    {{ busqueda ? bajasTemporalesFiltradas.length + " de " : ""
                                    }}{{ datos.bajas_temporales?.length || 0 }} registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarBajasTemporales }"
                                    class="w-6 h-6 text-yellow-600 dark:text-yellow-400 transition-transform"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarBajasTemporales"
                        class="bg-gradient-to-b from-yellow-50/30 to-white dark:from-yellow-900/10 dark:to-gray-800">
                        <ul class="px-4 py-3 space-y-2 max-h-64 overflow-y-auto">
                            <li v-for="(item, index) in busqueda
                                ? bajasTemporalesFiltradas
                                : datos.bajas_temporales" :key="item.ci || index"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-yellow-200 dark:hover:border-yellow-700 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center flex-shrink-0">
                                    <Icon :icon-button="true" name="user" class-name="text-yellow-600" :size="20" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ item.nombre }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        CI: {{ item.ci }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-medium text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 px-2 py-0.5 rounded-full flex-shrink-0 whitespace-nowrap">Baja
                                    Temporal</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bajas Definitivas -->
                <div v-if="
                    bajasDefinitivasFiltradas.length > 0 ||
                    (datos.bajas_definitivas?.length > 0 && !busqueda)
                " class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div @click="mostrarBajasDefinitivas = !mostrarBajasDefinitivas"
                        class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 px-4 py-1 cursor-pointer hover:from-red-100 hover:to-rose-100 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 pb-0 bg-red-100 dark:bg-red-800/50 rounded-lg">
                                    <Icon :icon-button="true" name="bajaDefinitiva" class-name="text-red-600"
                                        :viewBox="'0 0 20 20'" :size="22" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900 dark:text-red-200">
                                        Bajas Definitivas
                                    </h4>
                                    <p class="text-xs text-red-700 dark:text-red-400">
                                        Omitidos — baja definitiva vigente
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-sm font-bold text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-800/50 px-3 py-1 rounded-full">
                                    {{ busqueda ? bajasDefinitivasFiltradas.length + " de " : ""
                                    }}{{ datos.bajas_definitivas?.length || 0 }} registros
                                </span>
                                <svg :class="{ 'rotate-180': mostrarBajasDefinitivas }"
                                    class="w-6 h-6 text-red-600 dark:text-red-400 transition-transform" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-show="mostrarBajasDefinitivas"
                        class="bg-gradient-to-b from-red-50/30 to-white dark:from-red-900/10 dark:to-gray-800">
                        <ul class="px-4 py-3 space-y-2 max-h-64 overflow-y-auto">
                            <li v-for="(item, index) in busqueda
                                ? bajasDefinitivasFiltradas
                                : datos.bajas_definitivas" :key="item.ci || index"
                                class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-red-200 dark:hover:border-red-700 transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                                    <Icon :icon-button="true" name="user" class-name="text-red-600" :size="22" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ item.nombre }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        CI: {{ item.ci }}
                                    </p>
                                </div>
                                <span
                                    class="text-xs font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded-full flex-shrink-0 whitespace-nowrap">Baja
                                    Definitiva</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <template #footer>
            <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center gap-3">
                    <Button @click="$emit('close')" :style="'px-12 py-2.5 rounded-xl border-2 border-gray-200'"
                        class="text-white bg-blue-600 hover:bg-blue-500">
                        Confirmar
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed } from "vue";

import Modal from "@/components/Modal.vue";
import Button from "@/components/Button.vue";
import Icon from "@/components/Icon.vue";

const props = defineProps({
    modelValue: Boolean,
    datos: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["update:modelValue", "close"]);

const datosValidos = computed(() => {
    return props.datos && typeof props.datos === "object";
});

const totalBajas = computed(() => {
    return (
        (props.datos.bajas_temporales?.length || 0) +
        (props.datos.bajas_definitivas?.length || 0)
    );
});

const busqueda = ref("");
const mostrarInsertados = ref(false);
const mostrarDepurados = ref(false);
const mostrarHabilitados = ref(false);
const mostrarBajasTemporales = ref(false);
const mostrarBajasDefinitivas = ref(false);

const nombreMesTexto = computed(() => {
    const meses = {
        1: "Enero",
        2: "Febrero",
        3: "Marzo",
        4: "Abril",
        5: "Mayo",
        6: "Junio",
        7: "Julio",
        8: "Agosto",
        9: "Septiembre",
        10: "Octubre",
        11: "Noviembre",
        12: "Diciembre",
    };
    return meses[props.datos.mes] ?? props.datos.mes;
});

const insertadosFiltrados = computed(() => {
    if (!busqueda.value) return props.datos.insertados ?? [];
    const q = busqueda.value.toLowerCase();
    return (props.datos.insertados ?? []).filter(
        (i) => i.nombre?.toLowerCase().includes(q) || i.ci?.toString().includes(q)
    );
});

const habilitadosFiltrados = computed(() => {
    if (!busqueda.value) return props.datos.habilitados ?? [];
    const q = busqueda.value.toLowerCase();
    return (props.datos.habilitados ?? []).filter(
        (i) => i.nombre?.toLowerCase().includes(q) || i.ci?.toString().includes(q)
    );
});

const depuradosFiltrados = computed(() => {
    if (!busqueda.value) return props.datos.depurados ?? [];
    const q = busqueda.value.toLowerCase();
    return (props.datos.depurados ?? []).filter((i) =>
        i.ci?.toString().includes(q)
    );
});

const bajasTemporalesFiltradas = computed(() => {
    if (!busqueda.value) return props.datos.bajas_temporales ?? [];
    const q = busqueda.value.toLowerCase();
    return (props.datos.bajas_temporales ?? []).filter(
        (i) => i.nombre?.toLowerCase().includes(q) || i.ci?.toString().includes(q)
    );
});

const bajasDefinitivasFiltradas = computed(() => {
    if (!busqueda.value) return props.datos.bajas_definitivas ?? [];
    const q = busqueda.value.toLowerCase();
    return (props.datos.bajas_definitivas ?? []).filter(
        (i) => i.nombre?.toLowerCase().includes(q) || i.ci?.toString().includes(q)
    );
});

const totalResultadosBusqueda = computed(
    () =>
        insertadosFiltrados.value.length +
        habilitadosFiltrados.value.length +
        depuradosFiltrados.value.length +
        bajasTemporalesFiltradas.value.length +
        bajasDefinitivasFiltradas.value.length
);
</script>
