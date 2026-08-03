<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Componentes
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateDigitalSignature from './Partials/UpdateDigitalSignature.vue';
import Header from '@/components/Header.vue';
import Sidebar from '@/components/Sidebar.vue';
import Seccion from '@/components/Seccion.vue';
import Rutas from '@/components/Rutas.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

/**
 * Props principales
 */
const pagosDisponibles = computed(() => page.props.pagosDisponibles);
const notificacionTutor = computed(() => page.props.notificacionTutor);

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />
    <AuthenticatedLayout>
        <div class="flex h-screen -ml-1 bg-gray-200 font-roboto">
            <!-- Sidebar de navegación -->
            <Sidebar />

            <div class="flex-1 flex flex-col overflow-hidden">
                <Header class="mb-0" />

                <!-- ============================================================================ -->
                <!-- ENCABEZADO DE PÁGINA -->
                <!-- ============================================================================ -->
                <div
                    class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                    <h1 class="font-semibold text-xl sm:text-2xl">Editar Perfil</h1>
                    <Rutas label1="Inicio" label3="Editar Perfil" class="sm:text-xs" />
                </div>

                <!-- Contenido principal -->
                <main
                class="flex-1 overflow-x-hidden overflow-y-auto bg-white rounded-lg pt-3 mr-1 mb-1.5">
                    <div class="p-2 sm:p-4">
                        <!-- Grid responsivo para los formularios -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                            <!-- Formulario de información del perfil -->
                            <div
                                class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status"
                                    class="max-w-xl" />
                            </div>
                            <div class="bg-white p-4 shadow-sm border rounded-lg sm:p-8 sm:rounded-lg">
                                <UpdateDigitalSignature :user="user" />
                            </div>
                        </div>

                        <!-- Formulario de cambio de contraseña -->
                        <div
                            class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                            <UpdatePasswordForm />
                        </div>

                        <!-- Formulario de eliminación de cuenta -->
                        <!-- <div
                            class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                            <DeleteUserForm />
                        </div> -->
                    </div>
                </main>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
