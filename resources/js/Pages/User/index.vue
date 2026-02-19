<script setup>
import { can } from '@/lib/can';
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Seccion from '@/components/Seccion.vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import Form from '@/components/Form/Form.vue';
import ModalUser from '@/components/ModalUser.vue';
import ResetPasswordModal from '@/components/ResetPasswordModal.vue';

import {
    Head,
    router,
    usePage
} from '@inertiajs/vue3';
import {
    computed,
    ref
} from 'vue';
import Rutas from '@/components/Rutas.vue';
import Botones from '@/components/Botones.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/components/Button.vue';
import Icon from '@/components/Icon.vue';

const page = usePage();
const usuarios = computed(() => page.props.usuarios);
const filters = computed(() => page.props.filters || {});
const roles = computed(() => page.props.roles || []);
const notificacionTutor = computed(() => page.props.notificacionTutor);

//console.log(roles.value);

// ✅ Mapear los roles para el dropdown
const rolesOptions = computed(() => {
    return roles.value.map(role => ({
        text: role.name.toUpperCase(),
        value: role.name
    }));
});

/* console.log('Props completos:', page.props);
console.log('Auth:', page.props.auth);
console.log('User:', page.props.auth?.user); */

const showResetModal = ref(false);
const selectedUserForReset = ref(null);

const openResetPassword = (user) => {
    selectedUserForReset.value = user;
    showResetModal.value = true;
};

const closeResetModal = () => {
    showResetModal.value = false;
    selectedUserForReset.value = null;
};

const openForm = ref(false);
const selectedItem = ref(null);
const selectedId = ref(null);
const openFormEdit = ref(false);
const showModal = ref(false);
const tipo = ref('');
const contenido = ref('');
const showMensaje = ref(false);

const usuarioFields = computed(() => [{
    typeInput: 'text',
    name: 'nombre',
    label: 'Nombre',
    type: 'text',
    required: true,
    placeholder: 'el nombre del usuario',
    readonly: false,
    autofocus: true
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

// ✅ También para el formulario de edición
const usuarioEditFields = computed(() => [{
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

const usuariosAgrupadosPorRol = computed(() => {
    if (!usuarios.value || !usuarios.value.data || !Array.isArray(usuarios.value.data)) {
        return {};
    }

    return usuarios.value.data.reduce((grupos, usuario) => {
        const rol = usuario.rol;
        if (!grupos[rol]) {
            grupos[rol] = [];
        }
        grupos[rol].push(usuario);
        return grupos;
    }, {});
});

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

const openEditUsuario = (item, idPersona) => {
    selectedId.value = idPersona;
    // ✅ Convertir el valor booleano a número (1 o 0)
    selectedItem.value = {
        ...item,
        habilitado: item.habilitado ? 1 : 0  // Convierte true a 1, false a 0
    };
    openFormEdit.value = true;
}

const openFormCreate = () => {
    openForm.value = true;
}

const openModalUser = (item, idUser) => {
    selectedId.value = idUser;
    selectedItem.value = { ...item };
    showModal.value = true;
}

const closeForm = () => {
    openForm.value = false;
    openFormEdit.value = false;
    showModal.value = false;
}

const addUser = () => {
    mostrarMensaje('correcto', 'Registro exitoso', 'Los datos se registraron correctamente.');
    router.reload();
    openForm.value = false;
    openFormEdit.value = false;
}

const handleUpdate = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    openFormEdit.value = false;
    openForm.value = false;
}

// Funciones para los cards
const getInitials = (nombre, apellido) => {
    return `${nombre.charAt(0)}${apellido.charAt(0)}`.toUpperCase();
}

// Paleta de colores predefinidos
const colorPalette = [{
    border: 'border-orange-400',
    text: 'text-orange-600 bg-orange-50',
    avatar: 'from-orange-500 to-orange-600',
    hover: 'hover:text-orange-600 hover:bg-orange-50'
},
{
    border: 'border-blue-400',
    text: 'text-blue-600 bg-blue-50',
    avatar: 'from-blue-500 to-blue-600',
    hover: 'hover:text-blue-600 hover:bg-blue-50'
},
{
    border: 'border-purple-400',
    text: 'text-purple-600 bg-purple-50',
    avatar: 'from-purple-500 to-purple-600',
    hover: 'hover:text-purple-600 hover:bg-purple-50'
},
{
    border: 'border-green-400',
    text: 'text-green-600 bg-green-50',
    avatar: 'from-green-500 to-green-600',
    hover: 'hover:text-green-600 hover:bg-green-50'
},
{
    border: 'border-pink-400',
    text: 'text-pink-600 bg-pink-50',
    avatar: 'from-pink-500 to-pink-600',
    hover: 'hover:text-pink-600 hover:bg-pink-50'
},
{
    border: 'border-indigo-400',
    text: 'text-indigo-600 bg-indigo-50',
    avatar: 'from-indigo-500 to-indigo-600',
    hover: 'hover:text-indigo-600 hover:bg-indigo-50'
},
{
    border: 'border-red-400',
    text: 'text-red-600 bg-red-50',
    avatar: 'from-red-500 to-red-600',
    hover: 'hover:text-red-600 hover:bg-red-50'
},
{
    border: 'border-teal-400',
    text: 'text-teal-600 bg-teal-50',
    avatar: 'from-teal-500 to-teal-600',
    hover: 'hover:text-teal-600 hover:bg-teal-50'
},
{
    border: 'border-cyan-400',
    text: 'text-cyan-600 bg-cyan-50',
    avatar: 'from-cyan-500 to-cyan-600',
    hover: 'hover:text-cyan-600 hover:bg-cyan-50'
},
{
    border: 'border-amber-400',
    text: 'text-amber-600 bg-amber-50',
    avatar: 'from-amber-500 to-amber-600',
    hover: 'hover:text-amber-600 hover:bg-amber-50'
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

// Función para obtener un color basado en el nombre del rol
const getColorForRole = (rol) => {
    if (!rol) return colorPalette[0];

    // Generar un índice único basado en el nombre del rol
    let hash = 0;
    for (let i = 0; i < rol.length; i++) {
        hash = rol.charCodeAt(i) + ((hash << 5) - hash);
    }

    const index = Math.abs(hash) % colorPalette.length;
    return colorPalette[index];
};

const getFirstName = (fullName) => {
    if (!fullName) return '';
    return fullName.split(' ')[0];
};

const getBorderColor = (rol) => {
    return getColorForRole(rol).border;
}

const getRolColor = (rol) => {
    return getColorForRole(rol).text;
}

const getAvatarColor = (rol) => {
    return getColorForRole(rol).avatar;
}

const getHoverColor = (rol) => {
    return getColorForRole(rol).hover;
}
</script>

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

                <Transition name="fade">
                    <Form v-if="openForm" :fields="usuarioFields" label1="Agregar Usuario"
                        label2="Registre una nuevo usuario" submit-label="Agregar Usuario" submit-route="usuario.store"
                        @add="addUser" @cancel="closeForm" @close="closeForm">
                        <template #icon>
                            <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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

                <Transition name="fade">
                    <Form v-if="openFormEdit" :fields="usuarioEditFields" boton-name="Guardar"
                        :existing-data="selectedItem || {}" :idFor="selectedId" :edit-mode="true"
                        label1="Editar usuario" label2="Actuliza los datos del usuario"
                        rutalabel="Modifique los datos del usuario." submit-label="Editar Usuario"
                        submit-route="usuario.update" @add="handleUpdate" @cancel="closeForm">
                        <template #icon>
                            <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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

                <Transition name="fade">
                    <ModalUser v-if="showModal" :user="selectedId" :data="selectedItem" @close="closeForm" />
                </Transition>

                <Transition name="fade">
                    <ResetPasswordModal v-if="showResetModal" :user="selectedUserForReset" @close="closeResetModal" />
                </Transition>

                <div class="py-2">
                    <div class="px-5 py-1 flex justify-between">
                        <h1 class="font-semibold text-2xl">Usuarios</h1>
                        <Rutas label1="Inicio" label3="Usuarios" />
                    </div>
                </div>

                <div class="flex justify-between p-4 pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                    <Busqueda :initial-value="filters.buscador" name="usuario" only="usuarios" :data="usuarios"
                        ruta-busqueda="usuario.index" />
                    <Button v-if="can('agregar-usuario')" id="btn-agregar" @click.prevent="openFormCreate()"
                        @mouseenter="showTooltip('Agregar', 'btn-agregar')" @mouseleave="hideTooltip"
                        :style="'px-2 py-2 pb-1 rounded-full border-none'"
                        class="shrink-0 self-center bg-gray-200 relative overflow-hidden group">
                        <!-- Efecto de fondo desde el centro -->
                        <span
                            class="absolute inset-0 bg-blue-500 rounded-full scale-0 group-hover:scale-100 transition-transform duration-500 ease-out"></span>

                        <!-- Icono -->
                        <span class="relative z-10">
                            <Icon :icon-button="true" name="userAdd"
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
                <main class="flex-1 overflow-y-auto bg-white border-b-2 border-x-2 rounded-b-lg mr-1">
                    <!-- Contenedor vertical para secciones de roles -->
                    <div class="p-4 space-y-4">

                        <!-- Sección por cada rol -->
                        <div v-for="(usuariosDelRol, rolNombre) in usuariosAgrupadosPorRol" :key="rolNombre">
                            <h2 class="text-xl font-bold text-gray-800 dark:text-black mb-2 capitalize">
                                <span v-if="rolNombre === 'superUsuario'">Super Usuario</span>
                                <span v-else>{{ rolNombre }}</span>
                            </h2>

                            <!-- Cards de usuarios con scroll horizontal -->
                            <!-- Cards de usuarios con scroll horizontal -->
                            <div class="flex overflow-x-auto gap-4 pb-2">
                                <!-- Card con borde según estado -->
                                <div v-for="item in usuariosDelRol" :key="item.id"
                                    class="flex-shrink-0 w-80 bg-gray-50 dark:bg-gray-800 rounded-xl  border-l-4 p-5 hover:shadow-md transition-all duration-300 transform hover:-translate-y-0.5"
                                    :class="getBorderColor(item.rol)">

                                    <!-- Header con badge pequeño al lado del nombre -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1 flex items-center gap-2">
                                            <h3 class="text-base font-bold text-gray-800 dark:text-white capitalize">
                                                {{ getFirstName(item.nombre) }} {{ getFirstName(item.apellido) }}
                                            </h3>
                                            <!-- Badge inline -->
                                            <div v-if="item.estado === 1" class="w-2 h-2 bg-emerald-500 rounded-full"
                                                title="Conectado">
                                            </div>
                                            <div v-else class="w-2 h-2 bg-red-500 rounded-full" title="Desconectado">
                                            </div>
                                        </div>

                                        <!-- Botones de acción -->
                                        <div class="flex space-x-1 ml-2">
                                            <button @click="openModalUser(item, item.id)"
                                                class="p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
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

                                            <button v-if="$page.props.auth.user.id !== item.id && can('editar-usuario')"
                                                @click="openEditUsuario(item, item.id)"
                                                class="p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
                                                title="Editar usuario">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <!-- ✅ Botón para resetear contraseña -->
                                            <button
                                                v-if="$page.props.auth.user.id !== item.id && can('restablecer-superusuario')"
                                                @click="openResetPassword(item)"
                                                class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200"
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

                                    <!-- Avatar y información -->
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-gradient-to-br rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md"
                                            :class="getAvatarColor(item.rol)">
                                            {{ getInitials(item.nombre, item.apellido) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase truncate"
                                                :title="item.cargo.length > 20 ? item.cargo : null">
                                                {{ item.cargo }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ item.email }}
                                                <span v-if="item.habilitado === true"
                                                    class="text-green-600 dark:text-green-400">
                                                    • Cuenta habilitada
                                                </span>
                                                <span v-else class="text-red-600 dark:text-red-400">
                                                    • Cuenta deshabilitada
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <div :class="usuarios.data.length <= 15 ? 'mt-0.5' : 'mt-0'">
                    <Paginacion v-if="usuarios?.last_page > 1" :links="usuarios.links" :from="usuarios.from"
                        :to="usuarios.to" :total="usuarios.total" />
                    <Footer />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Estilos adicionales para las transiciones */
.transform {
    transition: transform 0.2s ease-in-out;
}
</style>
