<template>
    <AuthenticatedLayout>
        <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Roles de Usuario</h1>
                <Rutas label1="Inicio" label3="Roles de Usuario" class="sm:text-xs" />
            </div>

            <!-- ============================================================================ -->
            <!-- BARRA DE HERRAMIENTAS -->
            <!-- ============================================================================ -->
            <div class="flex justify-between p-2 sm:p-4 sm:pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <Busqueda :initial-value="filters.buscador" name="rol" only="roles" :data="roles"
                    ruta-busqueda="roles.index" />
                <Button v-if="can('agregar-roles')" id="btn-agregar" @click.prevent="openRol()"
                    @mouseenter="showTooltip('Agregar', 'btn-agregar')" @mouseleave="hideTooltip"
                    :style="'px-2 py-2 pb-1 rounded-full border-none'"
                    class="shrink-0 self-center bg-gray-200 relative overflow-hidden group">
                    <!-- Efecto de fondo desde el centro -->
                    <span
                        class="absolute inset-0 bg-[rgb(var(--brand-500))] rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                    <!-- Icono -->
                    <span class="relative z-10">
                        <Icon :icon-button="true" name="gridPlus"
                            class-name="text-gray-600 group-hover:text-white transition-colors duration-500" :size="32"
                            :height="32" />
                    </span>
                </Button>
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
            <DataTable :data="roles.data" :columns="tableColumns" row-key="id"
                empty-message="No se encontraron datos. ¡Agregue beneficiarios para continuar!">
                <!-- Slot personalizado para cada fila -->
                <template #row="{ item }">

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
                            class="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-1 w-40 bg-gradient-to-r from-[rgb(var(--brand-50))] to-indigo-50 hover:from-[rgb(var(--brand-100))] hover:to-indigo-100 dark:from-[rgba(var(--brand-900),0.2)] dark:to-indigo-900/20 dark:hover:from-[rgba(var(--brand-900),0.3)] dark:hover:to-indigo-900/30 rounded-lg border border-[rgb(var(--brand-200))] dark:border-[rgb(var(--brand-700))] transition-all duration-200 hover:shadow-md group">

                            <!-- Icono de escudo/seguridad -->
                            <svg class="w-4 h-4 text-[rgb(var(--brand-600))] dark:text-[rgb(var(--brand-400))] group-hover:scale-110 transition-transform duration-200"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>

                            <!-- Badge con número -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-semibold text-[rgb(var(--brand-700))] dark:text-[rgb(var(--brand-300))]">
                                    {{ item.permissions.length }}
                                </span>
                                <span class="text-xs font-medium text-[rgb(var(--brand-600))] dark:text-[rgb(var(--brand-400))]">
                                    {{ item.permissions.length === 1 ? 'permiso' : 'permisos' }}
                                </span>
                            </div>

                            <!-- Icono de expandir -->
                            <svg class="w-4 h-4 text-[rgb(var(--brand-500))] dark:text-[rgb(var(--brand-400))] group-hover:translate-x-0.5 transition-transform duration-200"
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

            <!-- ============================================================================ -->
            <!-- FOOTER Y PAGINACIÓN -->
            <!-- ============================================================================ -->
            <Paginacion v-if="roles?.last_page > 10" :links="roles.links" :from="roles.from" :to="roles.to"
                :total="roles.total" />
            <Footer />

            <!-- ============================================================================ -->
            <!-- FORMULARIOS - ROLES -->
            <!-- ============================================================================ -->

            <!-- Formulario: Crear Roles -->
            <Form v-if="formCreateRol" :fields="fieldRoles" :permissions="permissions" submit-route="roles.store"
                @add="handleAddRol" @sinDatos="sinDatos" @cancel="closeForm">
                <template #icon>
                    <Icon :icon-button="true" name="chalkBoard" class-name="text-white" :size="22" />
                </template>
                <template #label1>
                    Agregar Rol
                </template>
                <template #label2>
                    Registre un nuevo rol
                </template>
            </Form>

            <!-- Formulario: Editar Roles -->
            <Form v-if="formEditRol" :fields="fieldRoles" boton-name="Guardar" :permissions="permissions"
                :idFor="selectedId" :existing-data="selectedItem || {}" :edit-mode="true" submit-route="roles.update"
                @add="handleEdit" @sinDatos="sinDatos" @cancel="closeForm" @close="closeForm">
                <template #icon>
                    <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M5 8a4 4 0 1 1 7.796 1.263l-2.533 2.534A4 4 0 0 1 5 8Zm4.06 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2.172a2.999 2.999 0 0 1-.114-1.588l.674-3.372a3 3 0 0 1 .82-1.533L9.06 13Zm9.032-5a2.907 2.907 0 0 0-2.056.852L9.967 14.92a1 1 0 0 0-.273.51l-.675 3.373a1 1 0 0 0 1.177 1.177l3.372-.675a1 1 0 0 0 .511-.273l6.07-6.07a2.91 2.91 0 0 0-.944-4.742A2.907 2.907 0 0 0 18.092 8Z"
                            clip-rule="evenodd" />
                    </svg>
                </template>
                <template #label1>
                    Editar Rol
                </template>
                <template #label2>
                    Actualiza los datos del rol
                </template>
            </Form>

            <!-- ============================================================================ -->
            <!-- MODALES -->
            <!-- ============================================================================ -->

            <!-- Modal: Ver Roles Asignados -->
            <Transition name="fade">
                <Modal v-if="selectedRole" :showHeader="true" :showFooter="false" maxWidth="max-w-xl"
                    @close="selectedRole = false">
                    <template #icon>
                        <Icon :icon-button="true" name="chalkBoard" class-name="text-white" :size="22" />
                    </template>
                    <template #label1><span v-if="selectedRole.name === 'superUsuario'">
                            Super Usuario
                        </span>
                        <span v-else class="capitalize">
                            {{ selectedRole.name }}
                        </span></template>
                    <template #label2>
                        <p class="text-[rgb(var(--brand-600))] dark:text-gray-400 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span class="font-semibold">{{ selectedRole.permissions.length }}</span>
                            {{ selectedRole.permissions.length === 1
                                ? 'permiso asignado' : 'permisos asignados' }}
                        </p>
                    </template>

                    <!-- Contenido del Modal -->
                    <div class="">
                        <!-- Grid de grupos de permisos (padre-hijo) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-3">
                            <!-- Cada grupo (padre + hijos) -->
                            <div v-for="(group, index) in permissionsHierarchyModal" :key="index"
                                class="border rounded-lg overflow-hidden border-[rgb(var(--brand-400))] bg-gradient-to-r from-[rgb(var(--brand-50))] to-indigo-50 dark:from-[rgba(var(--brand-900),0.2)] dark:to-indigo-900/20 dark:border-[rgb(var(--brand-400))] flex flex-col">

                                <!-- Header (Padre) -->
                                <div v-if="group.parent"
                                    class="px-4 py-3 bg-[rgba(var(--brand-100),0.5)] dark:bg-[rgba(var(--brand-900),0.3)] flex items-center gap-3">
                                    <div
                                        class="w-5 h-5 rounded bg-[rgb(var(--brand-600))] dark:bg-[rgb(var(--brand-500))] flex items-center justify-center flex-shrink-0">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>

                                    <h3
                                        class="uppercase text-sm font-semibold text-[rgb(var(--brand-700))] dark:text-[rgb(var(--brand-300))] truncate">
                                        {{ group.parent.text }}
                                    </h3>
                                </div>

                                <!-- Permisos hijos -->
                                <div v-if="group.children.length > 0"
                                    class="px-3 py-2 space-y-1 flex-1 overflow-y-auto max-h-64 bg-white dark:bg-gray-800">
                                    <div v-for="child in group.children" :key="child.value"
                                        class="flex items-center gap-2.5 py-2 px-3 rounded-md bg-[rgb(var(--brand-50))] dark:bg-[rgba(var(--brand-900),0.2)]">
                                        <div
                                            class="w-4 h-4 rounded bg-[rgb(var(--brand-500))] dark:bg-[rgb(var(--brand-600))] flex items-center justify-center flex-shrink-0">
                                            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>

                                        <span
                                            class="text-sm text-[rgb(var(--brand-900))] dark:text-[rgb(var(--brand-100))] flex-1 leading-tight font-medium">
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

                    <!-- Footer -->
                    <template #footer>
                        <div class="border-t border-gray-100 dark:border-gray-700/50 py-5">
                            <div class="flex justify-center gap-3">
                                <Button @click="selectedRole = false"
                                    :style="'py-3 px-10 sm:px-12 sm:py-2.5 rounded-xl border'"
                                    class="text-white bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]">
                                    Aceptar
                                </Button>
                            </div>
                        </div>
                    </template>
                </Modal>
            </Transition>

        </AppLayout>
    </AuthenticatedLayout>
</template>

<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

/**
 * Componentes
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Paginacion from '@/components/Paginacion.vue';
import DataTable from '@/components/DataTable.vue';
import Busqueda from '@/components/Busqueda.vue';
import Footer from '@/components/Footer.vue';
import Button from '@/components/Button.vue';
import Rutas from '@/components/Rutas.vue';
import Modal from '@/components/Modal.vue';
import Icon from '@/components/Icon.vue';
import Form from '@/components/Form/Form.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';
import AppLayout from '@/Layouts/AppLayout.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const props = defineProps({
    roles: Object,
    permissions: Array,
    userHierarchyLevel: Number
});

const page = usePage();

// Props principales
const filters = computed(() => page.props.filters || {});

/**
 * Agrupa los permisos del rol seleccionado por categoría padre/hijo
 */
const permissionsHierarchyModal = computed(() => {
    if (!selectedRole.value?.permissions?.length) return [];

    const groups = {};

    selectedRole.value.permissions.forEach(permission => {
        const parts = permission.name.split('-');

        if (parts.length === 1) {
            if (!groups[permission.name]) {
                groups[permission.name] = {
                    parent: { value: permission.name, text: permission.name },
                    children: []
                };
            } else {
                groups[permission.name].parent = { value: permission.name, text: permission.name };
            }
        } else {
            const parentName = parts[parts.length - 1];
            if (!groups[parentName]) {
                groups[parentName] = { parent: null, children: [] };
            }
            groups[parentName].children.push({ value: permission.name, text: permission.name });
        }
    });

    return Object.entries(groups)
        .filter(([, group]) => group.parent)
        .map(([, group]) => group);
});

/**
 * Genera las opciones de nivel de jerarquía disponibles para el usuario actual
 */
const hierarchyOptions = computed(() => {
    const options = [];
    const maxLevel = userHierarchyLevel.value - 1;
    for (let i = maxLevel; i >= 1; i--) {
        options.push({ value: i, text: `Nivel ${i}` });
    }
    return options;
});

// ============================================================================
// REFS - ESTADO DE FORMULARIOS
// ============================================================================
const formCreateRol = ref(false);
const formEditRol = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedRole = ref(null);
const selectedItem = ref(null);
const selectedId = ref(null);
const mensajes = ref([]);
const userHierarchyLevel = ref(props.userHierarchyLevel || 0);

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - ROLES
// ============================================================================
const fieldRoles = [
    {
        typeInput: 'text',
        name: 'name',
        label: 'Nombre del Rol',
        type: 'text',
        required: true,
        placeholder: 'el nombre del rol',
        readonly: false,
        nameStyle: false,
        range: 50,
        autofocus: false
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

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Nombre', field: 'name', headerClass: 'tracking-wider px-3', cellClass: 'whitespace-nowrap' },
    { label: 'Nivel de Jerarquia', field: 'hierarchy_level', headerClass: 'text-center tracking-wider px-3', cellClass: 'text-center whitespace-nowrap' },
    { label: 'Permisos', field: 'permissions', headerClass: 'tracking-wider text-center px-3 whitespace-nowrap', cellClass: 'text-center whitespace-nowrap' },
    { label: 'Acciones', field: 'acciones', headerClass: 'tracking-wider text-center px-3', cellClass: '' }
];

// ============================================================================
// WATCHERS / LIFECYCLE
// ============================================================================
onMounted(() => {
    // Inyectar opciones de jerarquía en el campo del formulario
    const hierarchyField = fieldRoles.find(f => f.name === 'hierarchy_level');
    hierarchyField.options = hierarchyOptions.value;

    // Cerrar modal de permisos con tecla ESC
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
});

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Extrae y formatea el texto visible de un permiso eliminando su prefijo de acción
 * @param {string} text - Nombre completo del permiso
 * @returns {string} Nombre del permiso capitalizado
 */
const formatPermissionText = (text) => {
    if (!text) return '';
    const cleaned = text.split('-')[0];
    return cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
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
const sinDatos = () => {
    mostrarMensaje('error', 'Error de validación', 'Por favor, complete todos los campos obligatorios antes de enviar el formulario.');
};

// ============================================================================
// FUNCIONES - TOOLTIP
// ============================================================================

/**
 * Muestra el tooltip posicionado encima del botón indicado
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
// FUNCIONES - FORMULARIOS
// ============================================================================

/**
 * Abre el formulario de creación de rol
 */
const openRol = () => {
    formCreateRol.value = true;
};

/**
 * Abre el formulario de edición del rol seleccionado
 * @param {Object} item - Datos del rol
 * @param {number|string} idRol - ID del rol
 */
const openEditRol = (item, idRol) => {
    selectedId.value = idRol;
    selectedItem.value = { ...item };
    formEditRol.value = true;
};

/**
 * Maneja la creación exitosa de un rol
 */
const handleAddRol = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    formCreateRol.value = false;
};

/**
 * Maneja la edición exitosa de un rol
 */
const handleEdit = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    formEditRol.value = false;
};

/**
 * Cierra todos los formularios activos
 */
const closeForm = () => {
    formCreateRol.value = false;
    formEditRol.value = false;
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Muestra el panel de permisos del rol seleccionado
 * @param {Object} role - Datos del rol con sus permisos
 */
const showPermissions = (role) => {
    selectedRole.value = role;
};

/**
 * Cierra el modal de permisos al presionar la tecla ESC
 * @param {KeyboardEvent} e - Evento de teclado
 */
const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        selectedRole.value = null;
    }
};
</script>
