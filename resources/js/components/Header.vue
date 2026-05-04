<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3';

/**
 * Componentes
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveNavLink from '@/components/ResponsiveNavLink.vue';
import DropdownLink from '@/components/DropdownLink.vue';

/**
 * Utilidades
 */
import { useSidebar } from '../composables/useSidebar'
import { can } from '@/lib/can';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================


// Props principales
const dropdownOpen = ref(false);
const isDarkMode = ref(false);

const { isOpen } = useSidebar();


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
</script>

<template>
    <AuthenticatedLayout>
        <header
            class="flex items-center justify-between px-2 sm:px-6 py-2 border bg-white border-b-4 border-gray-300 rounded-lg mt-1 mr-1 dark:border-gray-900">
            <!-- Mobile Menu Button -->
            <div class="flex items-center">
                <button class="text-gray-500 focus:outline-none lg:hidden" @click="isOpen = true">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <div class="block sm:hidden font-extrabold items-center justify-center">
                <span class="text-3xl text-[#13326A] dark:text-white"
                    style="text-shadow: 0 0 1px #13326A, 1px 0 0px #13326A, 0px 0 0px #13326A, -5px 5px 4px rgb(158 158 158 / 90%);">UMADIS</span>
            </div>

            <!-- Right Side Navigation -->
            <div class="flex items-center">

                <!-- User Menu -->
                <div class="ms-1 relative">
                    <div class="relative flex justify-between">
                        <!-- Botón del usuario -->
                        <button ref="triggerBtn" @click="toggleDropdown"
                            class="relative z-10 flex items-center space-x-1.5 rounded-lg transition-all duration-200 dark:hover:bg-gray-800">
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
                                {{ $page.props.auth?.user.nombre.toLowerCase() }}
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
                                class="dropdown-content absolute right-0 z-50 w-72 sm:w-72 mt-12 origin-top-right bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-black/5 dark:ring-white/10 focus:outline-none">

                                <!-- Header Section con info del usuario -->
                                <div class="pt-5 pb-2 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex flex-col items-center space-y-1">
                                        <!-- Avatar -->
                                        <div
                                            class="w-16 h-16 rounded-full mb-3 overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 ring-2 ring-gray-200 dark:ring-gray-600">
                                            <span
                                                class="text-white uppercase font-semibold text-lg flex items-center justify-center h-full">
                                                {{ $page.props.auth?.user.nombre.charAt(0) }}
                                            </span>
                                        </div>

                                        <!-- User Info -->
                                        <div class="text-center">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                                                {{ $page.props.auth?.user.nombre.toLowerCase() }} {{
                                                    $page.props.auth?.user.apellido.toLowerCase() }}
                                            </h3>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400 text-center leading-snug break-words line-clamp-2">
                                                {{ $page.props.auth?.user.cargo }}
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

                                    <div class="h-px my-2 bg-gray-200 dark:bg-gray-700"></div>

                                    <!-- Sign Out -->
                                    <ResponsiveNavLink :href="route('logout')" method="post" as="button"
                                        class="flex w-full items-center px-4 text-sm text-red-500 dark:text-red-200 hover:bg-red-50 dark:hover:bg-red-700/50 group transition-colors">
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
            </div>
        </header>
    </AuthenticatedLayout>
</template>
