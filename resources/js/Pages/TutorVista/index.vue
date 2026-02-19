<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';

import Busqueda from '@/components/Busqueda.vue';
import Paginacion from '@/components/Paginacion.vue';
import Footer from '@/components/Footer.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import {
    computed,
    ref
} from 'vue';
import {
    Head,
    Link,
    useForm,
    usePage
} from '@inertiajs/vue3';
import Seccion from '@/components/Seccion.vue';
import DateField from '@/components/DateField.vue';

const page = usePage();
const tutores = ref(page.props.tutores);

const form = useForm({
    carnet: '',
    fecha_nacimiento: ''
});

const submit = () => {
    form.get(route('tutorVista.show'), {
        onSuccess: () => {
            console.log('Se verificó correctamente');
        },
        onError: (errors) => {
            alert('los credenciales no son correctos');
            console.error("Errores al actualizar:", errors);
        }
    });
}
</script>

<template>

    <Head title="UMADIS" />
    <div class="flex h-screen font-roboto bg-gray-300 text-gray-900 dark:bg-gray-800 dark:text-gray-200">
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Sección principal: Vista de tutor -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4">
                <!-- Encabezado de sección -->
                <section class="mb-6">
                    <h1 class="text-2xl font-bold text-center">Gestión de Beneficiarios</h1>
                    <p class="text-center mt-2 text-sm">
                        Acceda a la lista de tutorados, seguimiento de bonos y más.
                    </p>
                </section>

                <!-- Formulario de inicio de sesión -->
                <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-6 dark:bg-gray-900 dark:text-gray-200">
                    <h2 class="text-xl font-semibold mb-4 text-center">Acceso Tutores</h2>

                    <div class="p-5 rounded-lg bg-gray-100">
                        <form @submit.prevent="submit()" class="space-y-3">
                            <!-- Campo para ingresar número de carnet -->
                            <div>
                                <label for="carnet" class="block text-sm font-medium">
                                    Número de Carnet <span class="text-red-700"> *</span>
                                </label>

                                <input type="text" v-model="form.carnet" id="carnet"
                                    placeholder="Ingrese su número de carnet"
                                    class="mt-1 block w-full px-4 py-1.8 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 bg-gray-50 border-gray-300" />
                            </div>
                            <div class="pb-3">
                                <label for="carnet" class="block text-sm font-medium">
                                    Fecha de Nacimiento<span class="text-red-700"> *</span>
                                </label>
                                <DateField id="default-datepicker" v-model="form.fecha_nacimiento"
                                    placeholder="Seleccionar su fecha de nacimiento" class="w-full" />
                            </div>

                            <!-- Botón para enviar el formulario -->
                            <div>
                                <PrimaryButton class="flex items-center justify-center w-full py-2">
                                    Ingresar
                                </PrimaryButton>
                            </div>

                        </form>
                    </div>


                    <!-- Mensaje adicional -->
                    <!-- Mensaje adicional -->
                    <div class="mt-4 text-center text-sm">
                        <p>
                            ¿No pudo iniciar sesión? <a href="#" class="text-blue-500 hover:underline">Contáctanos</a>.
                        </p>
                    </div>
                </div>
            </main>

            <!-- Pie de página -->
            <Footer class="ml-1 bg-white" />
        </div>
    </div>
</template>

<style>
html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Roboto', sans-serif;
}
</style>
