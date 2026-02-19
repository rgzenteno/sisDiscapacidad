<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

// Componentes
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Form from '@/components/Form/Form.vue';
import Busqueda from '@/components/Busqueda.vue';
import Paginacion from '@/components/Paginacion.vue';
import Footer from '@/components/Footer.vue';
import Mensajes from '@/components/Mensajes.vue';
import Rutas from '@/components/Rutas.vue';
import DataTable from '@/components/DataTable.vue';

// Utilidades
import { can } from '@/lib/can';
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
const page = usePage();

// Props principales
const tutor = computed(() => page.props.tutor);
const filters = computed(() => page.props.filters || {});
const totalTutores = computed(() => page.props.totalTutores);

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const selectedId = ref(null);
const selectedItem = ref(null);
const formEditTutor = ref(false);
const formRegistrarCuenta = ref(false);
const mensajes = ref([]);

// ============================================================================
// CONFIGURACIÓN DE CAMPOS - TUTORES
// ============================================================================
const tutorFields = [
    {
        typeCi: 'ci',
        typeInput: 'cedula',
        name: 'ci_tutor',
        label: 'C.I.',
        type: 'number',
        required: false,
        placeholder: 'la cédula de identidad',
        readonly: false,
        range: 10,
        autofocus: true
    },
    {
        typeCi: 'ci',
        typeInput: 'comple',
        name: 'complemento_tutor',
        label: 'Complemento C.I.',
        type: 'complemento',
        required: false,
        readonly: false,
        hidden: true
    },
    {
        typeInput: 'text',
        name: 'nombre_tutor',
        label: 'Nombre',
        type: 'text',
        required: true,
        placeholder: 'el nombre',
        readonly: false,
        nameStyle: true,
        range: 30,
    },
    {
        typeInput: 'text',
        name: 'apellido_tutor',
        label: 'Apellidos',
        type: 'text',
        required: true,
        placeholder: 'el apellido',
        readonly: false,
        nameStyle: true,
        range: 30,
    },
    {
        typeInput: 'text',
        name: 'telefono',
        label: 'Celular',
        type: 'number',
        required: false,
        placeholder: 'el número de teléfono',
        readonly: false,
        range: 10,
    },
    {
        typeInput: 'text',
        name: 'email',
        label: 'Correo Electronico',
        type: 'email',
        required: false,
        placeholder: 'el correo electronico',
        readonly: false,
        nameStyle: false,
        range: 40,
    },
    {
        typeInput: 'text',
        name: 'direccion',
        label: 'Dirección',
        type: 'text',
        required: false,
        placeholder: 'la dirección',
        readonly: false,
        nameStyle: false,
        range: 49,
    }
];

// ============================================================================
// CONFIGURACIÓN DE TABLA
// ============================================================================
const tableColumns = [
    { label: 'Nombre Completo', field: 'nombre_completo', headerClass: 'px-3', cellClass: 'whitespace-nowrap capitalize' },
    { label: 'Cedula de Identidad', field: 'ci_tutor', headerClass: 'px-3', cellClass: '' },
    { label: 'Teléfono', field: 'telefono', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Correo', field: 'email', headerClass: 'text-center px-3', cellClass: '' },
    { label: 'Dirección', field: 'direccion', headerClass: 'text-center px-3', cellClass: 'capitalize' },
    { label: 'Acciones', field: 'acciones', headerClass: 'text-center px-3', cellClass: '' }
];

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
 * Cierra un mensaje específico
 * @param {number} id - ID del mensaje a cerrar
 */
const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// ============================================================================
// FUNCIONES - NAVEGACIÓN Y REPORTES
// ============================================================================

/**
 * Navega a una ruta específica con un ID codificado
 * @param {string} ruta - Nombre de la ruta
 * @param {number|string} id - ID a codificar y pasar como parámetro
 */
const getTutoradosUrl = (ruta, id) => {
    const url = route(ruta, window.btoa(id));
    router.visit(url);
};

/**
 * Abrir Whastapp
 */
const openWhatsApp = (whatasapp) => {
    const phoneNumber = whatasapp; // Número de WhatsApp
    const message = encodeURIComponent('Hola, tengo una consulta'); // Mensaje inicial
    const url = `https://wa.me/${phoneNumber}?text=${message}`;
    window.open(url, '_blank'); // Abre en una nueva pestaña
};

/**
 * Abrir Email
 */
const openEmail = (email) => {
    const recipient = email; // Dirección de correo electrónico
    const subject = encodeURIComponent('Consulta'); // Asunto del correo
    const body = encodeURIComponent('Hola, tengo una consulta'); // Cuerpo del mensaje
    const url = `mailto:${recipient}?subject=${subject}&body=${body}`;
    window.open(url, '_blank'); // Abre en una nueva pestaña
};

// ============================================================================
// FUNCIONES - FORMULARIOS EDICIÓN
// ============================================================================

/**
 * Abre el formulario de edición del tutor
 * @param {Object} item - Datos del tutor
 * @param {number|string} idPersona - ID del tutor
 */
const openEditTutor = (item, idTutor) => {
    selectedId.value = idTutor;
    selectedItem.value = { ...item };
    formEditTutor.value = true;
}

/**
 * Maneja la edición exitosa del tutor
 */
const handleUpdate = () => {
    mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
    router.reload();
    formEditTutor.value = false;
}

// ============================================================================
// FUNCIONES - MODALES
// ============================================================================
/**
 * Cierra todos los formularios y modales
 */
const closeForm = () => {
    formEditTutor.value = false;
    formRegistrarCuenta.value = false;
}
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex h-screen bg-gray-200 font-roboto">
        <!-- Sidebar de navegación -->
        <Sidebar />

        <!-- Contenedor principal -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- ============================================================================ -->
            <!-- SISTEMA DE MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                @close="cerrarMensaje" />

            <!-- Header -->
            <Header class="mb-0" />

            <!-- ============================================================================ -->
            <!-- FORMULARIOS - BENEFICIARIOS -->
            <!-- ============================================================================ -->

            <!-- Formulario: Crear Beneficiario -->
            <Transition name="fade">
                <Form v-if="formEditTutor" :fields="tutorFields" boton-name="Guardar"
                    :existing-data="selectedItem || {}" :idFor="selectedId" :edit-mode="true"
                    submit-label="Editar Tutor" submit-route="tutor.update" @add="handleUpdate" @cancel="closeForm">
                    <template #icon>
                        <Icon :icon-button="true" name="userEdit" class-name="text-white" />
                    </template>
                    <template #label1>
                        Editar tutor
                    </template>
                    <template #label2>
                        Actuliza los datos del tutor
                    </template>
                </Form>
            </Transition>

            <!-- ============================================================================ -->
            <!-- ENCABEZADO DE PÁGINA -->
            <!-- ============================================================================ -->
            <div class="px-5 py-3 flex justify-between">
                <h1 class="font-semibold text-2xl">Tutores</h1>
                <Rutas label1="Inicio" label3="Tutores" />
            </div>

            <!-- ============================================================================ -->
            <!-- BARRA DE HERRAMIENTAS -->
            <!-- ============================================================================ -->
            <div class="flex justify-between p-4 pb-3 bg-gray-50 border-x-2 border-t-2 rounded-t-lg mr-1">
                <Busqueda :initial-value="filters.buscador" name="tutor" only="tutor" :data="tutor"
                    ruta-busqueda="tutor.index" />
            </div>

            <!-- ============================================================================ -->
            <!-- TABLA DE DATOS -->
            <!-- ============================================================================ -->
            <DataTable :data="tutor.data" :columns="tableColumns" row-key="id_tutor"
                empty-message="No se encontraron datos. ¡Agregue beneficiarios junto con sus tutores!">
                <!-- Slot personalizado para cada fila -->
                <template #row="{ item }">

                    <!-- Columna: Nombre Completo -->
                    <td class="capitalize px-3 py-1.5 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ item.nombre_tutor }} {{ item.apellido_tutor }}
                    </td>

                    <!-- Columna: Cedula de Identidad -->
                    <td class="px-3 py-1.5 text-gray-900">
                        {{ item.ci_tutor }}
                        <span v-if="item.complemento_tutor !== null">- {{ item.complemento_tutor }}</span>
                    </td>

                    <!-- Columna: Teléfono -->
                    <td class="px-3 py-1.5">
                        <div @click.prevent="item.telefono ? openWhatsApp(item.telefono) : null"
                            :class="item.telefono ? 'cursor-pointer hover:bg-green-100 rounded-lg' : 'cursor-not-allowed'"
                            class="action-link flex items-center justify-center gap-1">
                            <Icon :icon-button="true" name="whatsapp"
                                :class-name="item.telefono ? 'text-green-500' : 'text-gray-500'" :size="18" />
                            <span class="text-gray-400">|</span>
                            <div class="font-medium" :class="item.telefono ? 'text-green-500' : 'text-gray-500'">
                                <span v-if="item.telefono">{{ item.telefono }}</span>
                                <span v-else>Sin teléfono</span>
                            </div>
                        </div>
                    </td>

                    <!-- Columna: Correo -->
                    <td class="py-1.5">
                        <div @click.prevent="item.email ? openEmail(item.email) : null"
                            :class="item.email ? 'cursor-pointer hover:bg-red-100 rounded-lg ' : 'cursor-not-allowed'"
                            class="action-link flex items-center justify-center gap-1">
                            <Icon :icon-button="true" name="envelope"
                                :class-name="item.email ? 'text-red-600' : 'text-gray-500'" :size="18" />
                            <span class="text-gray-400">|</span>
                            <div class="font-medium" :class="item.email ? 'text-red-600' : 'text-gray-500'">
                                <span v-if="item.email">{{ item.email }}</span>
                                <span v-else>Sin correo</span>
                            </div>
                        </div>
                    </td>

                    <!-- Columna: Dirección -->
                    <td class="capitalize px-3 py-1.5">
                        {{ item.direccion }}
                    </td>

                    <!-- Columna: Acciones -->
                    <td class="px-3 py-1.5">
                        <div class="flex justify-center items-center gap-1">
                            <Icon v-if="can('editar-tutor')" @click.prevent="openEditTutor(item, item.id_tutor)"
                                name="userEdit" class-name="text-gray-700" />
                            <Icon v-if="can('tutorados-tutor')"
                                @click.prevent="getTutoradosUrl('tutor.tutorados', item.id_tutor)" name="usersGroup"
                                class-name="text-gray-700" />
                        </div>
                    </td>
                </template>

                <!-- Slot personalizado para estado vacío (opcional) -->
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 px-4">
                        <!-- Icono simple -->
                        <div class="mb-6">
                            <Icon :icon-button="true" name="users" class-name="text-gray-400 dark:text-gray-500"
                                :size="64" :height="64" />
                        </div>

                        <!-- Textos -->
                        <div class="text-center space-y-2 max-w-md">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                                No se encontraron datos
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Comience agregando beneficiarios junto con sus tutores para visualizar la información
                                aquí.
                            </p>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- ============================================================================ -->
            <!-- FOOTER Y PAGINACIÓN -->
            <!-- ============================================================================ -->
            <div :class="tutor.data.length <= 15 ? 'mt-0.5' : 'mt-0'">
                <Paginacion v-if="totalTutores >= 15" :links="tutor.links" :from="tutor.from" :to="tutor.to"
                    :total="tutor.total" />
                <Footer />
            </div>
        </div>
    </div>
</template>
