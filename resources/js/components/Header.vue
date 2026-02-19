<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import Mensajes from '@/components/Mensajes.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import ResponsiveNavLink from '@/components/ResponsiveNavLink.vue';
import DropdownLink from '@/components/DropdownLink.vue';
import { useSidebar } from '../composables/useSidebar'
// Utilidades
import { can } from '@/lib/can';

const dropdownOpen = ref(false);
const isDarkMode = ref(false);

const { isOpen } = useSidebar();

const props = defineProps({
    datos: {
        type: Object,
        default: () => ({}) // Valor predeterminado corregido como objeto vacío
    },
    pagos: Boolean,
    tutor: Boolean
});

const triggerBtn = ref(null);

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = (e) => {
    // Si el clic no fue en el botón ni en algún elemento dentro del dropdown
    if (triggerBtn.value && !triggerBtn.value.contains(e.target) &&
        !e.target.closest('.dropdown-content')) {
        dropdownOpen.value = false;
    }
};

// Montamos los event listeners
onMounted(() => {
    document.addEventListener('click', closeDropdown);
    toggleTheme();
});

// Limpiamos los event listeners
onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});

const toggleTheme = () => {
    // Siempre intenta cargar el tema guardado, pero usa 'light' como valor por defecto
    const savedTheme = localStorage.getItem('color-theme') || 'light'

    isDarkMode.value = savedTheme === 'dark'

    if (isDarkMode.value) {
        document.documentElement.classList.add('dark')
    } else {
        document.documentElement.classList.remove('dark')
    }

    // Opcional: asegurarse de que esté guardado como 'light' si no existía
    if (!localStorage.getItem('color-theme')) {
        localStorage.setItem('color-theme', 'light')
    }
}

/* const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value

    if (isDarkMode.value) {
        document.documentElement.classList.add('dark')
        localStorage.setItem('color-theme', 'dark')
    } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('color-theme', 'light')
    }
} */

onMounted(() => {
    // Check initial theme preference
    if (localStorage.getItem('color-theme') === 'dark' ||
        (!('color-theme' in localStorage) &&
            window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDarkMode.value = true
        document.documentElement.classList.add('dark')
    }
})

const form = useForm({
    id_not_tutor: '',
    estado: '',
});

const showNotifications = ref(false)
const notificationRef = ref(null)
const buttonRef = ref(null)


// Método para manejar clics fuera del contenedor
const handleClickOutside = (event) => {
    // Verificar si los refs existen y no contienen el evento
    if (
        notificationRef.value &&
        buttonRef.value &&
        !notificationRef.value.contains(event.target) &&
        !buttonRef.value.contains(event.target)
    ) {
        showNotifications.value = false
    }
}

// Añadir event listener cuando el componente se monta
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

// Eliminar event listener cuando el componente se desmonta
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
});


// Ref para almacenar los tiempos actualizados
const tiemposActualizados = ref({});

// Variable para almacenar el intervalo
let intervalId;


// Limpiar el intervalo cuando el componente se desmonta
onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <header
            class="flex items-center justify-between px-6 py-2 border bg-white border-b-4 border-gray-300 rounded-lg mt-1 mr-1 dark:border-gray-900">
            <!-- Mobile Menu Button -->
            <div class="flex items-center">
                <button class="text-gray-500 focus:outline-none lg:hidden" @click="isOpen = true">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <!-- Right Side Navigation -->
            <div class="flex items-center">
                <!-- Theme Toggle -->
                <!-- <button id="theme-toggle" @click="toggleTheme" class="ms-2 relative flex items-center justify-center text-gray-700 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-10 w-10 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                <svg v-if="isDarkMode" class="hidden dark:block" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z" fill="currentColor">
                    </path>
                </svg>
                <svg v-else class="dark:hidden" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z" fill="currentColor"></path>
                </svg>
            </button> -->

                <!-- User Menu -->
                <div v-if="props.tutor !== true" class="ms-1 relative">
                    <div class="relative flex justify-between">
                        <!-- Botón del usuario -->
                        <button ref="triggerBtn" @click="toggleDropdown"
                            class="relative z-10 flex items-center space-x-1.5 rounded-lg px-3 py-0 transition-all duration-200 dark:hover:bg-gray-800">
                            <!-- Avatar -->
                            <div v-if="$page.props.auth?.user.avatar"
                                class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-white/10 dark:ring-gray-800/80">
                                <img :src="$page.props.auth?.user.avatar" :alt="$page.props.auth?.user.nombre"
                                    class="w-full h-full object-cover" />
                            </div>
                            <div v-else
                                class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 ring-2 ring-white/10 dark:ring-gray-800/80">
                                <span
                                    class="text-white uppercase font-semibold text-sm flex items-center justify-center h-full">
                                    {{ $page.props.auth?.user.nombre.charAt(0) }}
                                </span>
                            </div>

                            <!-- Nombre del usuario -->
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 capitalize">
                                {{ $page.props.auth?.user.nombre }}
                            </h3>

                            <!-- Flecha hacia abajo -->
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': dropdownOpen }" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <transition enter-active-class="transition duration-200 ease-out transform"
                            enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100"
                            leave-active-class="transition duration-150 ease-in transform"
                            leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
                            <div v-show="dropdownOpen"
                                class="dropdown-content absolute right-0 z-50 w-72 mt-12 origin-top-right bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-black/5 dark:ring-white/10 focus:outline-none">

                                <!-- Header Section con info del usuario -->
                                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center space-x-3">
                                        <!-- Avatar -->
                                        <div v-if="$page.props.auth?.user.avatar"
                                            class="w-12 h-12 rounded-full overflow-hidden ring-2 ring-gray-200 dark:ring-gray-600">
                                            <img :src="$page.props.auth?.user.avatar"
                                                :alt="$page.props.auth?.user.nombre"
                                                class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else
                                            class="w-12 h-12 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 ring-2 ring-gray-200 dark:ring-gray-600">
                                            <span
                                                class="text-white uppercase font-semibold text-lg flex items-center justify-center h-full">
                                                {{ $page.props.auth?.user.nombre.charAt(0) }}
                                            </span>
                                        </div>

                                        <!-- User Info -->
                                        <div class="flex-1 min-w-0">
                                            <h3
                                                class="text-lg font-semibold text-gray-900 dark:text-white capitalize truncate">
                                                {{ $page.props.auth?.user.nombre }} {{ $page.props.auth?.user.apellido
                                                }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                {{ $page.props.auth?.user.email }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <!-- Edit Profile -->
                                    <ResponsiveNavLink :href="route('profile.edit')"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z"
                                                fill="currentColor"></path>
                                        </svg>
                                        <span class="ms-1">Editar Perfil</span>
                                    </ResponsiveNavLink>

                                    <!-- Account Settings -->
                                    <ResponsiveNavLink v-if="can('logs-superusuario')" :href="route('log.index')"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors">
                                        <svg width="24" height="24" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300"
                                            fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 3v4a1 1 0 0 1-1 1H5m4 8h6m-6-4h6m4-8v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1Z" />
                                        </svg>
                                        <span class="ms-1">Registro de Actividad</span>
                                    </ResponsiveNavLink>

                                    <!-- Account Settings -->
                                    <!--  <ResponsiveNavLink v-if="can('logs-superusuario')" :href="route('sistem.config')"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors">
                                        <svg class="w-6 h-6 text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                            viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 13v-2a1 1 0 0 0-1-1h-.757l-.707-1.707.535-.536a1 1 0 0 0 0-1.414l-1.414-1.414a1 1 0 0 0-1.414 0l-.536.535L14 4.757V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.757l-1.707.707-.536-.535a1 1 0 0 0-1.414 0L4.929 6.343a1 1 0 0 0 0 1.414l.536.536L4.757 10H4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.757l.707 1.707-.535.536a1 1 0 0 0 0 1.414l1.414 1.414a1 1 0 0 0 1.414 0l.536-.535 1.707.707V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.757l1.707-.708.536.536a1 1 0 0 0 1.414 0l1.414-1.414a1 1 0 0 0 0-1.414l-.535-.536.707-1.707H20a1 1 0 0 0 1-1Z" />
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                        </svg>
                                        <span class="ms-1">Configuracion del sistema</span>
                                    </ResponsiveNavLink> -->

                                    <!-- Divider -->
                                    <div class="h-px my-2 bg-gray-200 dark:bg-gray-700"></div>

                                    <!-- Sign Out -->
                                    <ResponsiveNavLink :href="route('logout')" method="post" as="button"
                                        class="flex w-full items-center px-4 py-3 text-sm text-red-500 dark:text-red-200 hover:bg-red-50 dark:hover:bg-red-700/50 group transition-colors">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="text-red-500 group-hover:text-red-700 dark:group-hover:text-red-300">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M15.1007 19.247C14.6865 19.247 14.3507 18.9112 14.3507 18.497L14.3507 14.245H12.8507V18.497C12.8507 19.7396 13.8581 20.747 15.1007 20.747H18.5007C19.7434 20.747 20.7507 19.7396 20.7507 18.497L20.7507 5.49609C20.7507 4.25345 19.7433 3.24609 18.5007 3.24609H15.1007C13.8581 3.24609 12.8507 4.25345 12.8507 5.49609V9.74501L14.3507 9.74501V5.49609C14.3507 5.08188 14.6865 4.74609 15.1007 4.74609L18.5007 4.74609C18.9149 4.74609 19.2507 5.08188 19.2507 5.49609L19.2507 18.497C19.2507 18.9112 18.9149 19.247 18.5007 19.247H15.1007ZM3.25073 11.9984C3.25073 12.2144 3.34204 12.4091 3.48817 12.546L8.09483 17.1556C8.38763 17.4485 8.86251 17.4487 9.15549 17.1559C9.44848 16.8631 9.44863 16.3882 9.15583 16.0952L5.81116 12.7484L16.0007 12.7484C16.4149 12.7484 16.7507 12.4127 16.7507 11.9984C16.7507 11.5842 16.4149 11.2484 16.0007 11.2484L5.81528 11.2484L9.15585 7.90554C9.44864 7.61255 9.44847 7.13767 9.15547 6.84488C8.86248 6.55209 8.3876 6.55226 8.09481 6.84525L3.52309 11.4202C3.35673 11.5577 3.25073 11.7657 3.25073 11.9984Z"
                                                fill="currentColor"></path>
                                        </svg>
                                        <span class="ms-1">Cerrar sesión</span>
                                    </ResponsiveNavLink>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Exit Button for Tutor -->
                <div v-else class="hover:bg-gray-100  p-2 ms-1 rounded-lg ">
                    <Link :href="route('tutorVista.exit')">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 12H4m12 0-4 4m4-4-4-4m3-4h2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-2" />
                        </svg>
                    </Link>
                </div>
            </div>
        </header>
    </AuthenticatedLayout>
</template>
