<script setup>
import Paginacion from '@/components/Paginacion.vue';
import Busqueda from '@/components/Busqueda.vue';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Footer from '@/components/Footer.vue';
import {
    Link,
    useForm,
    usePage
} from '@inertiajs/vue3';
import {
    computed,
    ref
} from 'vue';
import Seccion from '@/components/Seccion.vue';
import { Head } from '@inertiajs/vue3';
import Rutas from '@/components/Rutas.vue';
const page = usePage();
const persona = computed(() => page.props.persona);
const filters = computed(() => page.props.filters || {});

const getCarnetUrl = (ruta, id) => {
    return route(ruta, window.btoa(id));
}
</script>

    <template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <Header class="mb-0" />

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Pagos</h1>
                <Rutas label1="Inicio" label3="Pagos" />
            </div>
            <!-- <Seccion class="mt-1 " nombre="Tutor" texto="Gestione todos los tutores existentes" :botonReportTutor="true" :boton="false" /> -->
        </div>

        <div class="flex justify-between p-4 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg mr-1">
            <Busqueda    :initial-value="filters.buscador" only="persona" :data="persona" name="beneficiario" ruta-busqueda="pago.index" />
            <Seccion class="mt-1" nombre="Pago" texto="Seleccione al beneficiario para realizar el pago correspondiente" :boton="false" />
        </div>
        <main class="flex-1 overflow-x-hidden overflow-y-auto border-b-2 bg-white border-x-2 rounded-b-lg mr-1">
            <div v-if="persona.data.length > 0" class="mx-0 py-2">
                <div class="relative mx-4 overflow-x-auto border rounded-lg shadow-md sm:rounded-lg">
                    <table class="w-full rounded-lg text-xs text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                            <tr class="border-b">
                                <th scope="col" class="px-6 py-2.5"> Nombre Completo </th>
                                <th scope="col" class="px-6 py-2.5"> Distrito </th>
                                <th scope="col" class="px-6 py-2.5"> Cedula Identidad </th>
                                <th scope="col" class="text-center px-6 py-2.5"> Observación </th>
                                <th scope="col" class="text-center px-6 py-2.5"> Acciones </th>
                            </tr>
                        </thead>
                        <tbody v-for="item in persona.data" :key="item.id_persona">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <th scope="row" class="capitalize px-6 py-3.5 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ item.nombre_persona }} {{ item.apellido_persona }}
                                </th>
                                <td class="px-6 py-3.5">
                                    {{ item.distrito }}
                                </td>
                                <td scope="row" class="px-6 py-3.5 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ item.ci_persona }}
                                </td>
                                <td class="px-3 py-1">
                                    <div class="text-sm text-gray-700 dark:text-gray-300">
                                        <span v-if="!item.observacion_persona" class="text-center block italic text-gray-500">
                                            ninguna
                                        </span>
                                        <span v-else>
                                            {{ item.observacion_persona }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-1">
                                    <ul class="flex justify-center">
                                        <li>
                                            <Link :href="getCarnetUrl('pago.show', item.id_persona)" class="cursor-pointer action-link">
                                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M8 7V6a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-1M3 18v-7a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                                            </svg>
                                            </Link>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center text-gray-800 h-3/5 w-full mt-10 pt-10">
                <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd" />
                </svg>
                <span class="text-lg py-2">No se encontraron datos. ¡Habilite a los beneficiarios!</span>
            </div>
        </main>
        <Paginacion v-if="persona.data.length > 0" :links="persona.links" :from="persona.from" :to="persona.to" :total="persona.total" />
        <Footer />
    </div>
</div>
</template>
