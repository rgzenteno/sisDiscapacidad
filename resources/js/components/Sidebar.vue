<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSidebar } from '../composables/useSidebar'
import { Link, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { can } from '@/lib/can.ts';

const { isOpen } = useSidebar();
const page = usePage();

const activeClass = "bg-[#223B87] border-gray-50 rounded-r-lg mr-2 text-white border-l-4 border-black dark:text-white dark:bg-gray-700 dark:text-white dark:border-white"
const inactiveClass = "border-gray-100 text-white hover:bg-blue-600 dark:hover:bg-gray-500"

const currentRoute = ref('');
const activeButton = ref('dashboard');
const lastUserId = ref(null);

// Inicializar con el usuario actual
onMounted(() => {
    const currentUserId = page.props.auth?.user?.id;
    if (currentUserId) {
        lastUserId.value = currentUserId;
    }

    checkRoutePermission();
    updateActiveButton();
});

// Nueva función para verificar permisos
function checkRoutePermission() {
    const current = route().current();

    // Si está en dashboard o login, no verificar
    if (!current || current === 'dashboard' || current === 'login') {
        return;
    }

    // Obtener la ruta principal
    const mainRoute = current.split('.')[0];

    // Mapeo de rutas a permisos
    const routePermissions = {
        'postulante': 'general',
        'persona': 'beneficiario',
        'tutor': 'tutor',
        'gestion': 'gestion',
        'usuario': 'usuario',
        'roles': 'roles'
    };

    const requiredPermission = routePermissions[mainRoute];

    // Si la ruta requiere permiso y el usuario NO lo tiene
    if (requiredPermission && !can(requiredPermission)) {
        // Redirigir al dashboard
        router.visit(route('dashboard'), {
            replace: true,
            preserveState: false,
            preserveScroll: false
        });
    }
}

// Detectar cambio de usuario INMEDIATAMENTE
// Detectar cambio de usuario INMEDIATAMENTE
watch(() => page.props.auth?.user?.id, (newUserId, oldUserId) => {
    // Si hay un cambio real de usuario (no la inicialización)
    if (oldUserId && newUserId && oldUserId !== newUserId) {
        console.log('Cambio de usuario detectado - redirigiendo a dashboard');

        // Resetear estado
        activeButton.value = 'dashboard';
        lastUserId.value = newUserId;

        // Redirigir SIEMPRE al dashboard cuando cambia el usuario
        // Eliminar la condición if (route().current() !== 'dashboard')
        router.visit(route('dashboard'), {
            replace: true,
            preserveState: false,
            preserveScroll: false
        });
    } else if (newUserId && !oldUserId) {
        // Primera carga
        lastUserId.value = newUserId;
    }
});

// Actualizar botón activo cuando cambia la ruta
watch(() => route().current(), () => {
    updateActiveButton();
});

function updateActiveButton() {
    currentRoute.value = route().current();

    if (currentRoute.value === 'login' || currentRoute.value === 'dashboard') {
        activeButton.value = 'dashboard';
    } else {
        const mainRoute = currentRoute.value?.split('.')[0] || 'dashboard';
        activeButton.value = mainRoute;
    }
}

const setActiveButton = (button) => {
    activeButton.value = button;
}
</script>

<template>
    <div class="flex p-1">
        <!-- Backdrop -->
        <div :class="isOpen ? 'block' : 'hidden'"
            class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden" @click="isOpen = false" />
        <!-- End Backdrop -->

        <div :class="isOpen ? 'translate-x-0 ease-out ml-1 mt-1 mb-1' : '-translate-x-full ease-in'"
            class="rounded-lg fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform bg-[#13326A] dark:bg-gray-900 lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex items-center justify-center mt-8">
                <div class="flex items-center">
                    <!-- Texto  -->
                    <span class="mx-2 text-4xl font-semibold text-white dark:text-white"
                        style="text-shadow: -5px 5px 4px rgb(158 158 158 / 79%);">UMADIS</span>
                </div>
            </div>
            <AuthenticatedLayout>
                <nav class="mt-8">
                    <Link class="flex items-center px-6 py-2 mt-4 duration-200 "
                        :class="[activeButton === 'dashboard' ? activeClass : inactiveClass]" :href="route('dashboard')"
                        @click="setActiveButton('dashboard')">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M2 10C2 5.58172 5.58172 2 10 2V10H18C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10Z"
                                fill="currentColor" />
                            <path d="M12 2.25195C14.8113 2.97552 17.0245 5.18877 17.748 8.00004H12V2.25195Z"
                                fill="currentColor" />
                        </svg>
                        <span class="mx-4">Inicio</span>
                    </Link>

                    <Link v-if="can('general')" class="flex items-center px-6 py-2 mt-4 duration-200"
                        :class="[activeButton === 'general' ? activeClass : inactiveClass]"
                        :href="route('general.index')" @click="setActiveButton('general')">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M17 10v1.126c.367.095.714.24 1.032.428l.796-.797 1.415 1.415-.797.796c.188.318.333.665.428 1.032H21v2h-1.126c-.095.367-.24.714-.428 1.032l.797.796-1.415 1.415-.796-.797a3.979 3.979 0 0 1-1.032.428V20h-2v-1.126a3.977 3.977 0 0 1-1.032-.428l-.796.797-1.415-1.415.797-.796A3.975 3.975 0 0 1 12.126 16H11v-2h1.126c.095-.367.24-.714.428-1.032l-.797-.796 1.415-1.415.796.797A3.977 3.977 0 0 1 15 11.126V10h2Zm.406 3.578.016.016c.354.358.574.85.578 1.392v.028a2 2 0 0 1-3.409 1.406l-.01-.012a2 2 0 0 1 2.826-2.83ZM5 8a4 4 0 1 1 7.938.703 7.029 7.029 0 0 0-3.235 3.235A4 4 0 0 1 5 8Zm4.29 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h6.101A6.979 6.979 0 0 1 9 15c0-.695.101-1.366.29-2Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">General</span>
                    </Link>

                    <Link v-if="can('beneficiario')" class="flex items-center px-6 py-2 mt-4 duration-200"
                        :class="[activeButton === 'persona' ? activeClass : inactiveClass]"
                        :href="route('persona.index')" @click="setActiveButton('persona')">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">Beneficiarios</span>
                    </Link>

                    <Link v-if="can('tutor')" class="flex items-center px-6 py-2 mt-4 duration-200"
                        :class="[activeButton === 'tutor' ? activeClass : inactiveClass]" :href="route('tutor.index')"
                        @click="setActiveButton('tutor')">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">Tutores</span>
                    </Link>

                    <Link v-if="can('gestion')" class="flex items-center px-6 py-2 mt-4 duration-200 "
                        :class="[activeButton === 'gestion' ? activeClass : inactiveClass]"
                        :href="route('gestion.index')" @click="setActiveButton('gestion')">
                        <svg class="w-6 h-6 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">Gestiones</span>
                    </Link>

                    <Link v-if="can('usuario')" class="flex items-center px-6 py-2 mt-4 duration-200"
                        :class="[activeButton === 'usuario' ? activeClass : inactiveClass]"
                        :href="route('usuario.index')" @click="setActiveButton('usuario')">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 20a7.966 7.966 0 0 1-5.002-1.756l.002.001v-.683c0-1.794 1.492-3.25 3.333-3.25h3.334c1.84 0 3.333 1.456 3.333 3.25v.683A7.966 7.966 0 0 1 12 20ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10c0 5.5-4.44 9.963-9.932 10h-.138C6.438 21.962 2 17.5 2 12Zm10-5c-1.84 0-3.333 1.455-3.333 3.25S10.159 13.5 12 13.5c1.84 0 3.333-1.455 3.333-3.25S13.841 7 12 7Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">Usuarios</span>
                    </Link>

                    <Link v-if="can('roles')" class="flex items-center px-6 py-2 mt-4 duration-200"
                        :class="[activeButton === 'roles' ? activeClass : inactiveClass]" :href="route('roles.index')"
                        @click="setActiveButton('roles')">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M7 2a2 2 0 0 0-2 2v1a1 1 0 0 0 0 2v1a1 1 0 0 0 0 2v1a1 1 0 1 0 0 2v1a1 1 0 1 0 0 2v1a1 1 0 1 0 0 2v1a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H7Zm3 8a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm-1 7a3 3 0 0 1 3-3h2a3 3 0 0 1 3 3 1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="mx-4">Roles</span>
                    </Link>

                </nav>

            </AuthenticatedLayout>
        </div>
    </div>
</template>
