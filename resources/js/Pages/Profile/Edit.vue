<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateDigitalSignature from './Partials/UpdateDigitalSignature.vue';
import Header from '@/components/Header.vue';
import Sidebar from '@/components/Sidebar.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Seccion from '@/components/Seccion.vue';
import Rutas from '@/components/Rutas.vue';

const page = usePage()
const pagosDisponibles = computed(() => page.props.pagosDisponibles);
const notificacionTutor = computed(() => page.props.notificacionTutor);

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    user: {  // <-- Asegúrate de tener esto
        type: Object,
        required: true,
    },
});
</script>
<template>

    <Head title="UMADIS" />
    <AuthenticatedLayout>
        <div class="flex h-screen bg-gray-50 font-roboto overflow-hidden">
            <Sidebar />

            <div class="flex-1 flex flex-col overflow-hidden">
                <Header class="mb-0" />

                <!-- Encabezado de la página -->
                <div class="py-2">
                    <div class="px-5 py-1 flex justify-between items-center">
                        <h1 class="font-semibold text-2xl text-gray-800">Editar Perfil</h1>
                        <Rutas label1="Inicio" label3="Editar Perfil" />
                    </div>
                </div>

                <!-- Header del contenido (actualmente vacío pero estructura preparada) -->
                <div
                    class="flex justify-between items-center p-4 pb-1 bg-white border-x-2 border-t-2 rounded-t-lg mr-1">
                    <!-- Aquí puedes agregar filtros, botones de acción, etc. -->
                </div>

                <!-- Contenido principal -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white border-x-2 border-b-2 rounded-b-lg mr-1">
                    <div class="p-4 pt-0">
                        <!-- Grid responsivo para los formularios -->
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                            <!-- Formulario de información del perfil -->
                            <div
                                class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status"
                                    class="max-w-xl" />
                            </div>
                            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                                <UpdateDigitalSignature :user="user" />
                            </div>
                        </div>

                        <!-- Formulario de cambio de contraseña -->
                            <div
                                class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                                <UpdatePasswordForm  />
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
