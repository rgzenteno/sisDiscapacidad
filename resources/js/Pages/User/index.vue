<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Componentes
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResetPasswordModal from '@/components/ResetPasswordModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ModalUser from '@/components/ModalUser.vue';
import Busqueda from '@/components/Busqueda.vue';
import Button from '@/components/Button.vue';
import Footer from '@/components/Footer.vue';
import Rutas from '@/components/Rutas.vue';
import Icon from '@/components/Icon.vue';
import Form from '@/components/Form/Form.vue';

/**
 * Utilidades
 */
import { can } from '@/lib/can';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const usuarios = computed(() => page.props.usuarios);
const filters = computed(() => page.props.filters || {});
const roles = computed(() => page.props.roles || []);

// ============================================================================
// FILTRO - SOLO HABILITADOS
// ============================================================================
const buscadorActual = ref(filters.value.buscador || '');
const soloHabilitados = ref(filters.value.soloHabilitados !== false);

/**
 * Alterna el filtro de "solo habilitados" e incluye/excluye a los usuarios
 * deshabilitados en el listado, preservando el texto de búsqueda actual.
 */
const toggleSoloHabilitados = () => {
    soloHabilitados.value = !soloHabilitados.value;
    router.get(route('usuario.index'), {
        buscador: buscadorActual.value,
        soloHabilitados: soloHabilitados.value ? 1 : 0
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['usuarios', 'filters']
    });
};

/**
 * Mapea los roles disponibles como opciones para el dropdown
 */
const rolesOptions = computed(() =>
    roles.value.map(role => ({
        text: role.name.toUpperCase(),
        value: role.name
    }))
);

/**
 * Agrupa los usuarios por su rol asignado
 */
const usuariosAgrupadosPorRol = computed(() => {
    if (!usuarios.value || !Array.isArray(usuarios.value)) return {};
    return usuarios.value.reduce((grupos, usuario) => {
        const rol = usuario.rol;
        if (!grupos[rol]) grupos[rol] = [];
        grupos[rol].push(usuario);
        return grupos;
    }, {});
});

// ============================================================================
// REFS - ESTADO DE MODALES
// ============================================================================
const showResetModal = ref(false);
const showModal = ref(false);

// ============================================================================
// REFS - ESTADO DE FORMULARIOS
// ============================================================================
const openForm = ref(false);
const openFormEdit = ref(false);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedItem = ref(null);
const selectedId = ref(null);
const selectedUserForReset = ref(null);
const mensajes = ref([]);

// ============================================================================
// REFS - TOOLTIP
// ============================================================================
const tooltipText = ref('');
const showTooltipFlag = ref(false);
const tooltipStyle = ref({});

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - USUARIOS
// ============================================================================

/**
 * Campos del formulario de creación de usuario
 */
const usuarioFields = computed(() => [
    {
        typeInput: 'text',
        name: 'nombre',
        label: 'Nombre',
        type: 'text',
        required: true,
        placeholder: 'el nombre del usuario',
        readonly: false,
        autofocus: false
    },
    {
        typeInput: 'text',
        name: 'apellido',
        label: 'Apellido',
        type: 'text',
        required: true,
        placeholder: 'el apellido del usuario',
        readonly: false
    },
    {
        typeInput: 'select',
        name: 'rol',
        label: 'Rol',
        type: 'select',
        placeholder: 'el rol del usuario',
        options: rolesOptions.value,
        required: true,
        readonly: false
    },
    {
        typeInput: 'letras-punto',
        name: 'cargo',
        label: 'Cargo',
        required: true,
        placeholder: 'el cargo del usuario',
        readonly: false,
        nameStyle: 'uppercase'
    },
    {
        name: 'email',
        label: 'Usuario',
        type: 'usuario',
        required: true,
        placeholder: 'el correo electrónico del usuario',
        readonly: false,
        nameStyle: 'lowercase'
    },
    {
        typeInput: 'text',
        name: 'password',
        label: 'Contraseña',
        type: 'password',
        required: true,
        placeholder: 'la contraseña del usuario',
        readonly: false,
        nameStyle: 'lowercase'
    },
    {
        typeInput: 'text',
        name: 'password_confirmation',
        label: 'Confirmar Contraseña',
        type: 'password',
        required: true,
        placeholder: 'otra vez la contraseña',
        readonly: false,
        nameStyle: 'lowercase'
    }
]);

/**
 * Campos del formulario de edición de usuario
 */
const usuarioEditFields = computed(() => [
    {
        typeInput: 'text',
        name: 'nombre',
        label: 'Nombre',
        type: 'text',
        required: true,
        placeholder: 'el nombre del usuario',
        readonly: false
    },
    {
        typeInput: 'text',
        name: 'apellido',
        label: 'Apellido',
        type: 'text',
        required: true,
        placeholder: 'el apellido del usuario',
        readonly: false
    },
    {
        typeInput: 'select',
        name: 'rol',
        label: 'Rol',
        type: 'select',
        options: rolesOptions.value,
        required: true,
        readonly: false
    },
    {
        typeInput: 'letras-punto',
        name: 'cargo',
        label: 'Cargo',
        required: true,
        placeholder: 'el cargo del usuario',
        readonly: false,
        nameStyle: 'uppercase'
    },
    {
        typeInput: 'select',
        name: 'habilitado',
        label: 'Estado de la cuenta',
        type: 'select',
        options: [
            { text: 'Habilitado', value: 1 },
            { text: 'Deshabilitado', value: 0 }
        ],
        required: true,
        readonly: false
    }
]);

// ============================================================================
// DATOS ESTÁTICOS
// ============================================================================

/**
 * Paleta de colores para los avatares y tarjetas de usuario por rol
 */
const colorPalette = [
    {
        avatar: 'from-violet-500 to-purple-400',
        bg: 'bg-violet-50 dark:bg-violet-900/20',
        border: 'border-violet-200 dark:border-violet-700/40',
        text: 'text-violet-500 dark:text-violet-300',
        badge: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border-violet-200 dark:border-violet-700',
    },
    {
        avatar: 'from-blue-500 to-cyan-400',
        bg: 'bg-blue-50 dark:bg-blue-900/20',
        border: 'border-blue-200 dark:border-blue-700/40',
        text: 'text-blue-500 dark:text-blue-300',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-700',
    },
    {
        avatar: 'from-emerald-500 to-teal-400',
        bg: 'bg-emerald-50 dark:bg-emerald-900/20',
        border: 'border-emerald-200 dark:border-emerald-700/40',
        text: 'text-emerald-600 dark:text-emerald-300',
        badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700',
    },
    {
        avatar: 'from-rose-500 to-pink-400',
        bg: 'bg-rose-50 dark:bg-rose-900/20',
        border: 'border-rose-200 dark:border-rose-700/40',
        text: 'text-rose-500 dark:text-rose-300',
        badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border-rose-200 dark:border-rose-700',
    },
    {
        avatar: 'from-amber-500 to-orange-400',
        bg: 'bg-amber-50 dark:bg-amber-900/20',
        border: 'border-amber-200 dark:border-amber-700/40',
        text: 'text-amber-600 dark:text-amber-300',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-700',
    },
    {
        avatar: 'from-indigo-500 to-sky-400',
        bg: 'bg-indigo-50 dark:bg-indigo-900/20',
        border: 'border-indigo-200 dark:border-indigo-700/40',
        text: 'text-indigo-500 dark:text-indigo-300',
        badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700',
    },
];

// ============================================================================
// FUNCIONES - UTILIDADES
// ============================================================================

/**
 * Genera las iniciales de un usuario a partir de su nombre y apellido
 * @param {string} nombre - Nombre del usuario
 * @param {string} apellido - Apellido del usuario
 * @returns {string} Iniciales en mayúsculas
 */
const getInitials = (nombre, apellido) => {
    return `${nombre.charAt(0)}${apellido.charAt(0)}`.toUpperCase();
};

/**
 * Extrae el primer nombre de un texto con posibles espacios
 * @param {string} value - Nombre completo o parcial
 * @returns {string} Primer nombre capitalizado
 */
const getFirstName = (value) => {
    if (!value) return '';
    const first = value.split(' ')[0].toLowerCase();
    return first.charAt(0).toUpperCase() + first.slice(1);
};

/**
 * Formatea una cadena de fecha y hora en formato legible
 * @param {string} dateTimeString - Fecha en formato 'YYYY-MM-DD HH:MM:SS'
 * @returns {string} Fecha formateada con hora o 'N/A' si no hay valor
 */
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';
    const [datePart, timePart] = dateTimeString.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour, minute] = timePart.split(':');
    const fecha = new Date(year, month - 1, day);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = fecha.toLocaleDateString('es-ES', options);
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());
    return `${dateStr} - ${hour}:${minute}`;
};

/**
 * Retorna el objeto de color de la paleta correspondiente al rol indicado
 * @param {string} rol - Nombre del rol
 * @returns {Object} Objeto con clases Tailwind para avatar, bg, border, text y badge
 */
const getColorForRole = (rol) => {
    if (!rol) return colorPalette[0];
    let hash = 0;
    for (let i = 0; i < rol.length; i++) {
        hash = rol.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colorPalette[Math.abs(hash) % colorPalette.length];
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
// FUNCIONES - FORMULARIOS CREACIÓN
// ============================================================================

/**
 * Abre el formulario de creación de usuario
 */
const openFormCreate = () => {
    openForm.value = true;
};

/**
 * Maneja el registro exitoso de un nuevo usuario
 */
const addUser = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload();
    openForm.value = false;
    openFormEdit.value = false;
};

// ============================================================================
// FUNCIONES - FORMULARIOS EDICIÓN
// ============================================================================

/**
 * Abre el formulario de edición del usuario seleccionado
 * @param {Object} item - Datos del usuario
 * @param {number|string} idPersona - ID del usuario
 */
const openEditUsuario = (item, idPersona) => {
    selectedId.value = idPersona;
    selectedItem.value = {
        ...item,
        habilitado: item.habilitado ? 1 : 0
    };
    openFormEdit.value = true;
};

/**
 * Maneja la edición exitosa del usuario
 */
const handleUpdate = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    openFormEdit.value = false;
    openForm.value = false;
};

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================

/**
 * Abre el modal de detalle del usuario seleccionado
 * @param {Object} item - Datos del usuario
 * @param {number|string} idUser - ID del usuario
 */
const openModalUser = (item, idUser) => {
    selectedId.value = idUser;
    selectedItem.value = { ...item };
    showModal.value = true;
};

/**
 * Abre el modal de restablecimiento de contraseña para el usuario indicado
 * @param {Object} user - Datos del usuario
 */
const openResetPassword = (user) => {
    selectedUserForReset.value = user;
    showResetModal.value = true;
};

/**
 * Cierra el modal de restablecimiento de contraseña y limpia el usuario seleccionado
 */
const closeResetModal = () => {
    showResetModal.value = false;
    selectedUserForReset.value = null;
};

/**
 * Cierra todos los formularios y modales activos
 */
const closeForm = () => {
    openForm.value = false;
    openFormEdit.value = false;
    showModal.value = false;
};
</script>

<template>
    <AuthenticatedLayout>
        <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                <h1 class="font-semibold text-xl sm:text-2xl">Usuarios</h1>
                <Rutas label1="Inicio" label3="Usuarios" class="sm:text-xs" />
            </div>

            <!-- ============================================================================ -->
            <!-- BARRA DE HERRAMIENTAS -->
            <!-- ============================================================================ -->
            <div class="flex justify-between p-2 sm:p-4 sm:pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <Busqueda :initial-value="filters.buscador" name="usuario" only="usuarios" :data="usuarios"
                        ruta-busqueda="usuario.index" :extra-params="{ soloHabilitados: soloHabilitados ? 1 : 0 }"
                        @update:search="value => buscadorActual = value" />

                    <!-- Switch: solo habilitados / incluir deshabilitados -->
                    <button type="button" @click="toggleSoloHabilitados"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors duration-200 shadow-sm shrink-0"
                        :class="soloHabilitados
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-700 hover:bg-emerald-100'
                            : 'bg-gray-50 border-gray-300 text-gray-500 hover:bg-gray-100'"
                        :title="soloHabilitados
                            ? 'Mostrando solo usuarios habilitados — clic para incluir deshabilitados'
                            : 'Mostrando todos los usuarios — clic para filtrar solo habilitados'">
                        <span class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 flex-shrink-0"
                            :class="soloHabilitados ? 'bg-emerald-500' : 'bg-gray-300'">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                                :class="soloHabilitados ? 'translate-x-4' : 'translate-x-1'"></span>
                        </span>
                        <span class="text-xs font-semibold hidden sm:inline whitespace-nowrap">Solo habilitados</span>
                    </button>
                </div>
                <Button v-if="can('agregar-usuario')" id="btn-agregar" @click.prevent="openFormCreate()"
                    @mouseenter="showTooltip('Agregar', 'btn-agregar')" @mouseleave="hideTooltip"
                    :style="'px-2 py-2 pb-1 rounded-full border-none'"
                    class="shrink-0 self-center bg-gray-200 relative overflow-hidden group">
                    <!-- Efecto de fondo desde el centro -->
                    <span
                        class="absolute inset-0 bg-[rgb(var(--brand-500))] rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                    <!-- Icono -->
                    <span class="relative z-10">
                        <Icon :icon-button="true" name="userAdd"
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
            <!-- DATOS GENERALES -->
            <!-- ============================================================================ -->
            <main class="flex-1 overflow-y-auto bg-white border-b-2 border-x-2 rounded-b-lg mr-1">
                <!-- Contenedor vertical para secciones de roles -->
                <div class="p-1 sm:p-4 space-y-4">

                    <!-- Sección por cada rol -->
                    <div v-for="(usuariosDelRol, rolNombre) in usuariosAgrupadosPorRol" :key="rolNombre">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-black mb-2 capitalize">
                            <span v-if="rolNombre === 'superUsuario'">Super Usuario</span>
                            <span v-else>{{ rolNombre }}</span>
                        </h2>

                        <!-- Cards de usuarios con scroll horizontal -->
                        <div class="flex overflow-x-auto gap-3 pb-2">
                            <div v-for="item in usuariosDelRol" :key="item.id" :class="['flex-shrink-0 w-72 rounded-xl border p-4 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5',
                                getColorForRole(item.rol).bg,
                                getColorForRole(item.rol).border]">

                                <!-- Avatar + nombre + acciones -->
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="relative flex-shrink-0">
                                        <div :class="['w-11 h-11 rounded-full bg-gradient-to-br flex items-center justify-center shadow-md',
                                            getColorForRole(item.rol).avatar]">
                                            <span class="text-white font-bold text-sm uppercase">
                                                {{ getInitials(item.nombre, item.apellido) }}
                                            </span>
                                        </div>
                                        <span :class="['absolute top-0 right-0 w-2.5 h-2.5 rounded-full border border-white dark:border-gray-800',
                                            item.estado === 1 ? 'bg-emerald-500' : 'bg-rose-500']"
                                            :title="item.estado === 1 ? 'Activo' : 'Inactivo'">
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex justify-between items-center gap-1.5">
                                            <p
                                                class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                                                {{ getFirstName(item.nombre) }} {{ getFirstName(item.apellido) }}
                                            </p>
                                            <!-- Acciones -->
                                            <div class="flex -mt-5 flex-shrink-0">
                                                <button @click="openModalUser(item, item.id)"
                                                    class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-white/60 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                                    title="Ver detalles">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    v-if="$page.props.auth.user.id !== item.id && can('editar-usuario')"
                                                    @click="openEditUsuario(item, item.id)"
                                                    class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-white/60 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                                    title="Editar usuario">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    v-if="$page.props.auth.user.id !== item.id && can('restablecer-superusuario')"
                                                    @click="openResetPassword(item)"
                                                    class="p-1.5 text-rose-400 hover:text-rose-600 hover:bg-white/60 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                                    title="Resetear contraseña">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <p :class="['text-xs truncate uppercase', getColorForRole(item.rol).text]"
                                            :title="item.cargo?.length > 25 ? item.cargo : null">
                                            {{ item.cargo }}
                                        </p>
                                    </div>


                                </div>

                                <!-- Separador -->
                                <div class="h-px bg-gray-300 dark:bg-gray-700 mb-3"></div>

                                <!-- Fecha creación + estado cuenta -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ formatDateTime(item.created_at) }}
                                        </span>
                                    </div>
                                    <span
                                        :class="['text-xs font-semibold', item.habilitado ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400']">
                                        {{ item.habilitado ? '• Habilitado' : '• Deshabilitado' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- ============================================================================ -->
            <!-- FOOTER Y PAGINACIÓN -->
            <!-- ============================================================================ -->
            <Footer />

            <!-- ============================================================================ -->
            <!-- FORMULARIOS - USUARIOS -->
            <!-- ============================================================================ -->

            <!-- Formulario: Crear Usuario -->
            <Transition name="fade">
                <Form v-if="openForm" :fields="usuarioFields" label1="Agregar Usuario"
                    label2="Registre una nuevo usuario" submit-label="Agregar Usuario" submit-route="usuario.store"
                    @add="addUser" @cancel="closeForm" @close="closeForm">
                    <template #icon>
                        <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                                clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template #label1>
                        Agregar usuario
                    </template>
                    <template #label2>
                        Registre un nuevo usuario
                    </template>
                </Form>
            </Transition>

            <!-- Formulario: Editar Usuario -->
            <Transition name="fade">
                <Form v-if="openFormEdit" :fields="usuarioEditFields" boton-name="Guardar"
                    :existing-data="selectedItem || {}" :idFor="selectedId" :edit-mode="true" label1="Editar usuario"
                    label2="Actuliza los datos del usuario" rutalabel="Modifique los datos del usuario."
                    submit-label="Editar Usuario" submit-route="usuario.update" @add="handleUpdate" @cancel="closeForm">
                    <template #icon>
                        <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M5 8a4 4 0 1 1 7.796 1.263l-2.533 2.534A4 4 0 0 1 5 8Zm4.06 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h2.172a2.999 2.999 0 0 1-.114-1.588l.674-3.372a3 3 0 0 1 .82-1.533L9.06 13Zm9.032-5a2.907 2.907 0 0 0-2.056.852L9.967 14.92a1 1 0 0 0-.273.51l-.675 3.373a1 1 0 0 0 1.177 1.177l3.372-.675a1 1 0 0 0 .511-.273l6.07-6.07a2.91 2.91 0 0 0-.944-4.742A2.907 2.907 0 0 0 18.092 8Z"
                                clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template #label1>
                        Editar usuario
                    </template>
                    <template #label2>
                        Actualiza los datos del usuario
                    </template>
                </Form>
            </Transition>

            <!-- ============================================================================ -->
            <!-- MODALES -->
            <!-- ============================================================================ -->

            <!-- Modal: Modal Datos del Usuario -->
            <Transition name="fade">
                <ModalUser v-if="showModal" :user="selectedId" :data="selectedItem" @close="closeForm" />
            </Transition>

            <!-- Modal: Modal Resetear Contraseña Usuario -->
            <Transition name="fade">
                <ResetPasswordModal v-if="showResetModal" :user="selectedUserForReset" @close="closeResetModal" />
            </Transition>

        </AppLayout>
    </AuthenticatedLayout>
</template>
