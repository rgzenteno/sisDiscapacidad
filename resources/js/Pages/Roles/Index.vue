<template>
    <AuthenticatedLayout>

        <Head title="UMADIS" />
        <div class="flex h-screen bg-gray-200 font-roboto">
            <Sidebar />
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
                    <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                        @close="cerrarMensaje" />
                </div>
                <Header class="mb-0" />

                <Form v-if="formCreateRol" :fields="fieldRoles" :permissions="permissions" submit-route="roles.store"
                    @add="handleAddRol" @sinDatos="sinDatos" @cancel="closeForm">
                    <template #icon>
                        <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                                clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template #label1>
                        Agregar Rol
                    </template>
                    <template #label2>
                        Registre un nuevo rol
                    </template>
                </Form>

                <Form v-if="formEditRol" :fields="fieldRoles" boton-name="Guardar" :permissions="permissions"
                    :idFor="selectedId" :existing-data="selectedItem || {}" :edit-mode="true"
                    submit-route="roles.update" @add="handleEdit" @sinDatos="sinDatos" @cancel="closeForm"
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
                        Editar rol
                    </template>
                    <template #label2>
                        Actualiza los datos del rol
                    </template>
                </Form>

                <div class="py-2">
                    <div class="px-5 py-1 flex justify-between">
                        <h1 class="font-semibold text-2xl">Roles</h1>
                        <Rutas label1="Inicio" label3="Roles" />
                    </div>
                </div>
                <div class="flex justify-between p-4 pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                    <Busqueda :initial-value="filters.buscador" name="rol" only="roles" :data="roles"
                        ruta-busqueda="roles.index" />
                    <Button v-if="can('agregar-roles')" id="btn-agregar" @click.prevent="openRol()"
                        @mouseenter="showTooltip('Agregar', 'btn-agregar')" @mouseleave="hideTooltip"
                        :style="'px-2 py-2 pb-1 rounded-full border-none'"
                        class="shrink-0 self-center bg-gray-200 relative overflow-hidden group">
                        <!-- Efecto de fondo desde el centro -->
                        <span
                            class="absolute inset-0 bg-blue-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                        <!-- Icono -->
                        <span class="relative z-10">
                            <Icon :icon-button="true" name="gridPlus"
                                class-name="text-gray-600 group-hover:text-white transition-colors duration-500"
                                :size="32" :height="32" />
                        </span>
                    </Button>
                    <!-- Tooltip -->
                    <div v-if="showTooltipFlag" ref="tooltipRef"
                        class="fixed z-50 px-3 py-1.5 text-xs text-white bg-gray-800 rounded-lg shadow-lg pointer-events-none whitespace-nowrap"
                        :style="tooltipStyle">
                        {{ tooltipText }}
                    </div>
                </div>

                <!-- Uso del componente de tabla para roles -->
                <DataTable :data="roles.data" :columns="tableColumns" row-key="id"
                    empty-message="No se encontraron datos. ¡Agregue beneficiarios para continuar!">
                    <!-- Slot personalizado para cada fila -->
                    <template #row="{ item }">
                        <!-- Columna: id -->
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-gray-100 uppercase">
                                {{ item.id }}
                            </div>
                        </td>

                        <!-- Columna: Nombre -->
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="text-gray-700 capitalize dark:text-gray-300">
                                <span v-if="item.name === 'superUsuario'">Super Usuario</span>
                                <span v-else>{{ item.name }}</span>
                            </div>
                        </td>

                        <!-- Columna: Nombre -->
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <div class="text-gray-700 dark:text-gray-300">
                                {{ item.hierarchy_level }}
                            </div>
                        </td>

                        <!-- Columna: Permisos -->
                        <td class="px-3 py-1 text-center whitespace-nowrap">
                            <div @click="showPermissions(item)"
                                class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-1 w-40 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 rounded-lg border border-blue-200 dark:border-blue-700 transition-all duration-200 hover:shadow-md group">

                                <!-- Icono de escudo/seguridad -->
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-200"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>

                                <!-- Badge con número -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">
                                        {{ item.permissions.length }}
                                    </span>
                                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                                        {{ item.permissions.length === 1 ? 'permiso' : 'permisos' }}
                                    </span>
                                </div>

                                <!-- Icono de expandir -->
                                <svg class="w-4 h-4 text-blue-500 dark:text-blue-400 group-hover:translate-x-0.5 transition-transform duration-200"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </td>

                        <!-- Columna: Acciones -->
                        <td class="px-3 py-1.5">
                            <ul v-if="item.name !== 'superUsuario' && item.hierarchy_level < userHierarchyLevel"
                                class="flex justify-center items-center space-x-1">
                                <!-- Edit Rol Action -->
                                <Icon v-if="can('editar-roles')" @click.prevent="openEditRol(item, item.id)" name="edit"
                                    class-name="text-gray-800" />

                                <!-- Botón eliminar comentado -->
                                <!--
                                    <li class="flex items-center space-x-1">
                                    <a @click.prevent="(item.carnet?.id_carnet && item.tutor?.id_tutor)" title="eliminar" class="text-gray-800 cursor-pointer action-link">
                                        <svg class="w-6 h-6 text-red-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    </li>
                                    -->
                            </ul>
                        </td>
                    </template>

                    <!-- Slot personalizado para estado vacío -->
                    <template #empty>
                        <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-lg py-2">No se encontraron datos. ¡Agregue roles para
                            continuar!</span>
                    </template>
                </DataTable>

                <!-- Modal -->
                <div v-if="selectedRole"
                    class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-40 p-4 animate-fadeIn"
                    @click="selectedRole = null">
                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-2xl w-full mx-4 flex flex-col max-h-[85vh]"
                        @click.stop>
                        <!-- Header del Modal -->
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 dark:bg-gray-700/50 rounded-t-3xl">
                            <div class="flex items-center gap-4">
                                <!-- Avatar -->
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd"
                                            d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <!-- Título y subtítulo -->
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-2xl font-semibold text-slate-800 dark:text-gray-100 truncate">
                                        <span v-if="selectedRole.name === 'superUsuario'">
                                            Super Usuario
                                        </span>
                                        <span v-else>
                                            {{ selectedRole.name }}
                                        </span>
                                    </h2>
                                    <p class="text-blue-600 dark:text-gray-400 text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <span class="font-semibold">{{ selectedRole.permissions.length }}</span>
                                        {{ selectedRole.permissions.length === 1 ? 'permiso asignado' : 'permisos asignados' }}
                                    </p>
                                </div>

                                <!-- Botón cerrar -->
                                <button type="button" @click="selectedRole = null"
                                    class="p-2 rounded-full bg-white dark:bg-gray-700 shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Contenido del Modal -->
                        <div class="p-6 overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-900">
                            <!-- Grid de grupos de permisos (padre-hijo) -->
                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-3">
                                <!-- Cada grupo (padre + hijos) -->
                                <div v-for="(group, index) in permissionsHierarchyModal" :key="index"
                                    class="border rounded-lg overflow-hidden border-blue-400 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 dark:border-blue-400 flex flex-col">

                                    <!-- Header (Padre) -->
                                    <div v-if="group.parent"
                                        class="px-4 py-3 bg-blue-100/50 dark:bg-blue-900/30 flex items-center gap-3">
                                        <div
                                            class="w-5 h-5 rounded bg-blue-600 dark:bg-blue-500 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>

                                        <h3
                                            class="uppercase text-sm font-semibold text-blue-700 dark:text-blue-300 truncate">
                                            {{ group.parent.text }}
                                        </h3>
                                    </div>

                                    <!-- Permisos hijos -->
                                    <div v-if="group.children.length > 0"
                                        class="px-3 py-2 space-y-1 flex-1 overflow-y-auto max-h-64 bg-white dark:bg-gray-800">
                                        <div v-for="child in group.children" :key="child.value"
                                            class="flex items-center gap-2.5 py-2 px-3 rounded-md bg-blue-50 dark:bg-blue-900/20">
                                            <div
                                                class="w-4 h-4 rounded bg-blue-500 dark:bg-blue-600 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>

                                            <span
                                                class="text-sm text-blue-900 dark:text-blue-100 flex-1 leading-tight font-medium">
                                                {{ formatPermissionText(child.text) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Si solo es padre sin hijos -->
                                    <div v-else
                                        class="px-4 py-3 bg-white dark:bg-gray-800 text-sm text-gray-500 dark:text-gray-400 italic">
                                        Sin permisos específicos
                                    </div>
                                </div>
                            </div>

                            <!-- Mensaje si no hay permisos -->
                            <div v-if="!selectedRole.permissions || selectedRole.permissions.length === 0"
                                class="flex flex-col items-center justify-center py-12 text-center">
                                <div
                                    class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.732 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 font-medium">
                                    No hay permisos asignados a este rol
                                </p>
                            </div>
                        </div>

                        <!-- Footer del Modal -->
                        <div
                            class="px-6 py-4 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 rounded-b-3xl dark:border-gray-700 flex items-center justify-end">
                            <button @click="selectedRole = null"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 shadow-sm hover:shadow-md">
                                Aceptar
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <Paginacion v-if="roles?.last_page > 10" :links="roles.links" :from="roles.from" :to="roles.to"
                        :total="roles.total" />
                    <Footer />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { computed, ref } from "vue";
import { can } from '@/lib/can';
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Mensajes from '@/components/Mensajes.vue';
import Sidebar from '@/components/Sidebar.vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Form from '@/components/Form/Form.vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DataTable from '@/components/DataTable.vue';
import Button from '@/components/Button.vue';
import Icon from '@/components/Icon.vue';

const props = defineProps({
    roles: Object,
    permissions: Array,
    userHierarchyLevel: Number
});

// Agregar después de las props/emits
const permissionsHierarchyModal = computed(() => {
    if (!selectedRole.value || !selectedRole.value.permissions || selectedRole.value.permissions.length === 0) {
        return [];
    }

    const groups = {};

    // Identificar padres e hijos
    selectedRole.value.permissions.forEach(permission => {
        const parts = permission.name.split('-');

        if (parts.length === 1) {
            // Es un padre (ej: "general", "beneficiario")
            if (!groups[permission.name]) {
                groups[permission.name] = {
                    parent: { value: permission.name, text: permission.name },
                    children: []
                };
            } else {
                groups[permission.name].parent = { value: permission.name, text: permission.name };
            }
        } else {
            // Es un hijo (ej: "importar-general", "crear-beneficiario")
            const parentName = parts[parts.length - 1]; // "general", "beneficiario"

            if (!groups[parentName]) {
                groups[parentName] = {
                    parent: null,
                    children: []
                };
            }
            groups[parentName].children.push({ value: permission.name, text: permission.name });
        }
    });

    // Filtrar grupos que tienen padre
    return Object.entries(groups)
        .filter(([key, group]) => group.parent)
        .map(([key, group]) => group);
});

// Función para formatear texto (si no la tienes ya)
const formatPermissionText = (text) => {
    if (!text) return '';
    const cleaned = text.split('-')[0];
    return cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
};

const filters = computed(() => page.props.filters || {});

//console.log('Props Roles:', props.roles);

const selectedRole = ref(null)
const selectedItem = ref(null);
const selectedId = ref(null);

const showPermissions = (role) => {
    selectedRole.value = role
}

/* console.log('Roles:', props.roles);
console.log('Permissions:', props.permissions); */

const sinDatos = () => {
    console.log('Sin datos');
};

const page = usePage();
const formCreateRol = ref(false);
const formEditRol = ref(false);
const mensajes = ref([]);

onMounted(() => {
    // Asignar opciones de jerarquía
    const hierarchyField = fieldRoles.find(f => f.name === 'hierarchy_level');
    hierarchyField.options = hierarchyOptions.value;
});

const fieldRoles = [{
    typeInput: 'text',
    name: 'name',
    label: 'Nombre del Rol',
    type: 'text',
    required: true,
    placeholder: 'el nombre del rol',
    readonly: false,
    nameStyle: false,
    range: 50,
    autofocus: true
},
{
    typeInput: 'select',
    name: 'hierarchy_level',
    label: 'Nivel de Jerarquía',
    required: true,
    options: [],
    placeholder: 'Seleccione el nivel'
},
{
    typeInput: 'checkbox_permissions',
    name: 'permissions',
    label: 'Permisos',
    required: true,
    options: [],
}
];

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

// En tu script/data/computed
const userHierarchyLevel = ref(props.userHierarchyLevel || 0);

// Generar opciones dinámicamente
const hierarchyOptions = computed(() => {
    const options = [];
    const maxLevel = userHierarchyLevel.value - 1;

    // Cambia el for para ir de mayor a menor
    for (let i = maxLevel; i >= 1; i--) {  // 👈 Cambia aquí
        options.push({
            value: i,
            text: `Nivel ${i}`
        });
    }

    return options;
});

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

const mensajeExisteDisca = () => {
    mostrarMensaje('info', 'Registro existente', 'La discapacidad ingresada ya está registrado.');
};

const mensajeExisteDis = () => {
    mostrarMensaje('info', 'Registro existente', 'El distrito ingresado ya está registrado.');
};

const smsTutorEncontrado = () => {
    mostrarMensaje('info', 'Registro encontrado', 'El tutor ya está registrado, solo presiona siguiente.');
};

const openMissingDataModal = () => {
    mostrarMensaje('advertencia', 'Datos faltantes', 'La fecha de nacimiento del beneficiario es necesaria para crear el carnet de discapacidad.');
};

const fechaInvalida = () => {
    mostrarMensaje('advertencia', 'Fecha incorrecta', 'La fecha ingresada no puede ser anterior al último estado registrado');
};
// ============ FIN FUNCIONES MENSAJES ============ //

// Definición de columnas para la tabla de roles
const tableColumns = [
    { label: 'id', field: 'id', headerClass: 'tracking-wider px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Nombre', field: 'name', headerClass: 'tracking-wider px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Nivel de Jerarquia', field: 'hierarchy_level', headerClass: 'text-center tracking-wider px-3', cellClass: 'text-center whitespace-nowrap' },
    { label: 'Permisos', field: 'permissions', headerClass: 'tracking-wider text-center px-3 whitespace-nowrap', cellClass: 'text-center whitespace-nowrap' },
    { label: 'Acciones', field: 'acciones', headerClass: 'tracking-wider text-center px-3', cellClass: '' }
];

const openEditRol = (item, idRol) => {
    selectedId.value = idRol;
    selectedItem.value = { ...item };
    formEditRol.value = true;
};

const handleAddRol = () => {
    formCreateRol.value = false;
};

const handleEdit = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    formEditRol.value = false;
};

const openRol = () => {
    formCreateRol.value = true;
};

const closeForm = () => {
    formCreateRol.value = false;
    formEditRol.value = false;
};

// ============ INICIO LIFECYCLE HOOKS ============ //
const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        selectedRole.value = null;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
// ============ FIN LIFECYCLE HOOKS ============ //
</script>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}

.animate-slideUp {
    animation: slideUp 0.3s ease-out;
}
</style>
