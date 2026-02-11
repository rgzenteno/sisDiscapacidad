<script setup>
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import Footer from '@/components/Footer.vue';
import {
    computed,
    onMounted,
    ref
} from 'vue';
import {
    Head,
    Link,
    usePage
} from '@inertiajs/vue3';
import Seccion from '@/components/Seccion.vue';

import { useSidebar } from '@/composables/useSidebar';

const page = usePage();
const personas = ref(page.props.personas);
const tutorados = ref(page.props.tutorados);
const notificacionTutor = computed(() => page.props.notificacionTutor);
const habilitado = ref(page.props.habilitado);

const { isOpen } = useSidebar();

const activeClass = "bg-[#223B87] border-gray-50 rounded-r-lg mr-2 text-white border-l-4 border-black dark:text-white dark:bg-gray-700 dark:text-white dark:border-white"
const inactiveClass = "border-gray-100 text-white hover:bg-blue-600 dark:hover:bg-gray-500"

// Get current route name
const currentRoute = ref('');
const activeButton = ref(localStorage.getItem('activeButton') || 'cobros');

onMounted(() => {
    currentRoute.value = route().current();
    
    // If we're on the login page or tutorados view, set to tutorados
    if (currentRoute.value === 'login' || currentRoute.value === 'tutorVista.cobros') {
        activeButton.value = 'cobros';
        localStorage.setItem('activeButton', 'cobros');
    } else {
        // Extract the main route name
        const mainRoute = currentRoute.value.split('.')[0];
        activeButton.value = mainRoute;
        localStorage.setItem('activeButton', mainRoute);
    }
});

const setActiveButton = (button) => {
    activeButton.value = button;
    localStorage.setItem('activeButton', button);
}

const getNotificacion = () => {
    return notificacionTutor.value.filter(n => n.id_tutor === tutorados.value[0].id_tutor)
}

function getMonthNameFromDate(dateString) {
    // Validar si dateString no es null, undefined o no es un string
    if (!dateString || typeof dateString !== 'string') {
        return null; // Devuelve null si el argumento no es válido
    }

    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Extraer el mes del string de fecha (posición 5 a 7)
    const monthNumber = dateString.substring(5, 7);

    // Convertir el número de mes a un índice de array (restando 1)
    const index = parseInt(monthNumber, 10) - 1;

    // Verificar si el índice es válido
    if (index >= 0 && index < 12) {
        return months[index];
    } else {
        return 'Mes inválido'; // O devuélvelo como null si prefieres no mostrar nada
    }
}
</script>

<template>
    <Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <div class="flex sm:p-1" style="padding-left:4px">
        <!-- Backdrop -->
        <div :class="isOpen ? 'block' : 'hidden'" class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden" @click="isOpen = false" />
        
        <div :class="isOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="xs:m-1 rounded-lg fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-200 transform bg-[#13326A] dark:bg-gray-900 lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex items-center justify-center mt-8">
                <div class="flex items-center">
                    <span class="mx-2 text-4xl font-semibold text-white dark:text-white" style="text-shadow: -5px 5px 4px rgb(158 158 158 / 79%);">UMADIS</span>
                </div>
            </div>
            <nav class="mt-8">
                <Link class="flex items-center px-6 py-2 mt-4 duration-200" :class="[activeButton === 'tutorados' ? activeClass : inactiveClass]" :href="route('tutorVista.show')" @click="setActiveButton('tutorados')">
                    <svg class="w-6 h-6 text-gray-400 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 6a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm-1.5 8a4 4 0 0 0-4 4 2 2 0 0 0 2 2h7a2 2 0 0 0 2-2 4 4 0 0 0-4-4h-3Zm6.82-3.096a5.51 5.51 0 0 0-2.797-6.293 3.5 3.5 0 1 1 2.796 6.292ZM19.5 18h.5a2 2 0 0 0 2-2 4 4 0 0 0-4-4h-1.1a5.503 5.503 0 0 1-.471.762A5.998 5.998 0 0 1 19.5 18ZM4 7.5a3.5 3.5 0 0 1 5.477-2.889 5.5 5.5 0 0 0-2.796 6.293A3.501 3.501 0 0 1 4 7.5ZM7.1 12H6a4 4 0 0 0-4 4 2 2 0 0 0 2 2h.5a5.998 5.998 0 0 1 3.071-5.238A5.505 5.505 0 0 1 7.1 12Z" clip-rule="evenodd" />
                    </svg>
                    <span class="mx-4">Tutorados</span>
                </Link>

                <Link class="flex items-center px-6 py-2 mt-4 duration-200" :class="[activeButton === 'cobros' ? activeClass : inactiveClass]" href="#" @click="setActiveButton('cobros')">
                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd" />
                    </svg>
                    <span class="mx-4">Beneficiario</span>
                </Link>
            </nav>
        </div>
    </div>
    <div class="flex-1 flex flex-col overflow-hidden">
        <Header :datos="getNotificacion()" :tutor="true" />
        <Rutas label1="Tutorados" label3="Beneficiario" />
        <Seccion class="mr-1 mt-1" :nombre="`Beneficiario: ${tutorados[0].nombre_persona + ' ' + tutorados[0].apellido_persona}`" texto="Seguimiento de los bonos pagados del beneficiario(a)." :boton="false" />
        <main class="flex-1 overflow-x-hidden overflow-y-auto mt-1 mr-1">
            <div class="mx-0">
                <div v-if="tutorados" class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right border border-gray-300 text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    mes
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Gestión
                                </th>
                                <th scope="col" class="px-6 py-3 text-center">
                                    habilitado
                                </th>
                                <th scope="col" class="px-6 py-3 text-center">
                                    pagado
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Observación
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in tutorados" :key="item.id_tutor" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <th scope="row" class="capitalize px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ getMonthNameFromDate(item.gestion) }}
                                </th>
                                <td class="px-6 py-4">
                                    <div>
                                        {{ item.gestion.slice(0, 4) }}
                                    </div>
                                </td>
                                <th scope="col" class="px-6 py-3">
                                    <div v-if="item.habilitado === 1" class="rounded flex items-center justify-center">
                                        <div class="h-2.5 w-2.5 rounded-full bg-green-500"></div>
                                    </div>
                                    <div v-else class="rounded flex items-center justify-center">
                                        <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                                    </div>
                                </th>
                                <td class="px-6 py-4">
                                    <div v-if="item.id_pago != null" class="rounded flex items-center justify-center">
                                        <div class="h-2.5 w-2.5 rounded-full bg-green-500"></div>
                                    </div>
                                    <div v-else class="rounded flex items-center justify-center">
                                        <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                                    </div>

                                </td>
                                <td class="px-6 py-4">
                                    {{ item.observacion_persona }}
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else>
                    <h1>No existen datos </h1>
                </div>
            </div>
            <Paginacion v-if="personas >= 1" :links="personas.links" :from="personas.from" :to="personas.to" :total="personas.total" />
        </main>
        <Footer />
    </div>
</div>
</template>
