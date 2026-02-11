<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Rutas from '@/components/Rutas.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    onMounted,
    ref,
    computed
} from 'vue';
import {
    usePage
} from '@inertiajs/vue3';
import {
    initFlowbite
} from 'flowbite';
import {
    useForm
} from '@inertiajs/vue3';
import Seccion from '@/components/Seccion.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import Input from '@/components/Input.vue';
import Footer from '@/components/Footer.vue';
import Mensajes from '@/components/Mensajes.vue';

const page = usePage();
const persona = ref(page.props.persona);
const id_persona = ref(page.props.id_persona);
const origen = ref(page.props.origen);
const nombreCompleto = ref(page.props.nombreCompleto);
const carnet = computed(() => page.props.carnet);

const isSubmitting = ref(false);

// Crear un formulario reactivo con Inertia
const form = useForm({
    id_carnet: '',
    doc: '',
    discapacidad: '',
    fecha_emision: '',
    fecha_vencimiento: ''
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

const carnetsData = computed(() => {
    const filteredCarnets = carnet.value.filter(c => String(c.id_persona) === String(id_persona.value));

    return filteredCarnets.map(carnetData => {
        const fechaVencimiento = new Date(carnetData.fecha_vencimiento);
        const fechaActual = new Date();

        return {
            ...carnetData,
            esVencido: fechaVencimiento < fechaActual
        };
    });
});

// Método para enviar el formulario
// Modificar tu función submitForm existente:
const submitForm = () => {
    // Validación básica
    if (!form.doc || !form.discapacidad) {
        alert('Por favor, complete los campos obligatorios');
        return;
    }

    // Enviar el formulario usando Inertia
    form.put(route('carnet.update', form.id_carnet), {
        onSuccess: () => {
            mostrarMensaje('correcto', 'Edición exitosa', 'La información se actualizó correctamente.');
            // AGREGAR ESTO: Actualizar datos originales después del éxito
            originalData.value = {
                doc: form.doc,
                discapacidad: form.discapacidad,
                fecha_emision: form.fecha_emision,
                fecha_vencimiento: form.fecha_vencimiento
            };

            // Opcional: Mostrar mensaje de éxito
            router.reload({ only: ['carnet'] });
        },
        onError: (errors) => {
            console.error('Error al guardar:', errors);
        }
    });
}

// Llamar a getCarnet cuando se monta el componente
onMounted(() => {
    initFlowbite();
    getCarnet(); // Llenar formulario al montar
})

const options = [
    { text: 'INTELECTUAL', value: 'intelectual' },
    { text: 'FISICA-MOTORA', value: 'fisica-motora' },
    { text: 'AUDITIVA', value: 'auditiva' },
    { text: 'VISUAL', value: 'visual' },
    { text: 'MENTAL-PSIQUICA', value: 'mental-psiquica' },
    { text: 'MULTIPLE', value: 'multiple' }
];

// Datos originales para comparar cambios
const originalData = ref({});

// Computed para verificar si hay cambios
const hasChanges = computed(() => {
    return (
        form.doc !== originalData.value.doc ||
        form.discapacidad !== originalData.value.discapacidad ||
        form.fecha_emision !== originalData.value.fecha_emision ||
        form.fecha_vencimiento !== originalData.value.fecha_vencimiento
    );
});

// Función para resetear el formulario
const resetForm = () => {
    form.doc = originalData.value.doc || '';
    form.discapacidad = originalData.value.discapacidad || '';
    form.fecha_emision = originalData.value.fecha_emision || '';
    form.fecha_vencimiento = originalData.value.fecha_vencimiento || '';
    form.clearErrors();
};

// Modificar tu función getCarnet existente para guardar los datos originales:
const getCarnet = () => {
    const filteredCarnet = carnet.value.filter(c => String(c.id_persona) === id_persona.value);

    if (filteredCarnet.length > 0) {
        const carnetData = filteredCarnet[0];
        form.id_carnet = carnetData.id_carnet;
        form.doc = carnetData.doc;
        form.discapacidad = carnetData.discapacidad;
        form.fecha_emision = carnetData.fecha_emision;
        form.fecha_vencimiento = carnetData.fecha_vencimiento;

        // AGREGAR ESTO: Guardar datos originales
        originalData.value = {
            doc: carnetData.doc,
            discapacidad: carnetData.discapacidad,
            fecha_emision: carnetData.fecha_emision,
            fecha_vencimiento: carnetData.fecha_vencimiento
        };
    }

    return filteredCarnet;
}
</script>

<template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-5 0 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido" @close="cerrarMensaje" />
        </div>
        <Header class="mb-0" />

        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Carnet Discapacidad</h1>
                <Rutas label1="Inicio" :label2="origen" label3="Carnet Discapacidad" />
            </div>
        </div>

        <div class="flex justify-between p-4 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg mr-1">
            <Seccion :nombre="`Beneficiario: ${nombreCompleto}`" texto="Visualice y edite los detalles del carnet de discapacidad del beneficiario seleccionado." :boton="false" />
        </div>

        <main id="list" class="flex-1 overflow-x-hidden overflow-y-auto border-b-2 border-x-2 rounded-b-lg mr-1">
            <div class="grid grid-cols-1 mx-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-1">
                <!-- Vista de Carnet -->
                <div v-for="item in carnetsData" :key="item.id_carnet" class="container mx-auto px-4 py-4">
                    <!-- Header compacto -->
                    <div class="flex justify-center">
                        <div class="relative w-full max-w-sm text-black border-2 py-2 text-center rounded-t-lg shadow-md" :class="[
                                    item.esVencido 
                                    ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700' 
                                    : 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700'
                                ]">
                            <h1 class="text-lg font-bold">
                                Carnet de Discapacidad
                            </h1>
                        </div>
                    </div>

                    <!-- Carnet card compacta -->
                    <div class="flex justify-center">
                        <div class="relative w-full max-w-sm">
                            <!-- Card principal -->
                            <div :class="[
                                    item.esVencido 
                                    ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700' 
                                    : 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700'
                                ]" class="relative border-b-2 border-x-2 rounded-b-lg p-4 shadow-lg">

                                <div class="mt-0">
                                    <span v-if="item.esVencido" class="absolute top-3 right-2 inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full border border-red-300 dark:bg-red-900/50 dark:text-red-300 dark:border-red-700">
                                        <!-- Border animado -->
                                        <svg class="w-3 h-3 mr-1 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <span class="relative z-10">VENCIDO</span>
                                        <span class="relative flex h-3 w-3 ms-1">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                        </span>
                                    </span>
                                    <span v-else class="absolute top-3 right-2 inline-flex items-center px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full border border-green-300 dark:bg-green-900/50 dark:text-green-300 dark:border-green-700">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        VIGENTE
                                    </span>
                                </div>

                                <!-- Logo interno pequeño -->
                                <div class="text-center mb-4">
                                    <div class="inline-flex items-center justify-center w-10 h-10 bg-white dark:bg-gray-800 rounded-full shadow-md mb-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                        Documento Oficial
                                    </p>
                                </div>

                                <!-- Información compacta -->
                                <div class="space-y-3">
                                    <!-- C.I. Discapacidad -->
                                    <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                                            <span class="font-medium text-gray-600 dark:text-gray-300 text-xs">C.I.</span>
                                        </div>
                                        <span class="font-mono font-bold text-gray-900 dark:text-white text-sm">
                                            {{ item.doc }}
                                        </span>
                                    </div>

                                    <!-- Tipo Discapacidad -->
                                    <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-1.5 h-1.5 bg-purple-500 rounded-full"></div>
                                            <span class="font-medium text-gray-600 dark:text-gray-300 text-xs">Tipo</span>
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white uppercase text-xs tracking-wide">
                                            {{ item.discapacidad }}
                                        </span>
                                    </div>

                                    <!-- Fechas compactas -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-1.5 h-1.5 bg-green-500 rounded-full"></div>
                                                <span class="font-medium text-gray-600 dark:text-gray-300 text-xs">Emisión</span>
                                            </div>
                                            <span class="font-mono text-gray-800 dark:text-gray-200 text-xs">
                                                {{ item.fecha_emision }}
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center space-x-2">
                                                <div :class="[item.esVencido ? 'bg-red-500' : 'bg-amber-500']" class="w-1.5 h-1.5 rounded-full"></div>
                                                <span class="font-medium text-gray-600 dark:text-gray-300 text-xs">Vencimiento</span>
                                            </div>
                                            <span class="font-mono text-gray-800 dark:text-gray-200 text-xs">
                                                {{ item.fecha_vencimiento }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer compacto -->
                                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                                        Documento válido
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Edición -->
                <div class="container mx-auto px-4 py-8 max-w-2xl ">
                    <!-- Header -->
                    <div class="sticky top-0 flex items-center px-6 py-2 dark:border-gray-600/50 border-t-2 border-x-2 rounded-t-xl bg-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-sm">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-lg">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                    Editar Carnet de Discapacidad
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    Modifica los datos del carnet de discapacidad
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card contenedor del formulario -->
                    <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow-lg border border-gray-200 dark:border-gray-700">
                        <!-- Formulario -->
                        <form @submit.prevent="submitForm" id="carnet-form" class="p-6 pt-2 space-y-6">
                            <input type="hidden" v-model="form.id_carnet" />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- C.I. Discapacidad -->
                                <div>
                                    <label for="doc" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        C.I. Discapacidad
                                    </label>
                                    <Input input-type="number" v-model="form.doc" placeholder="Ingrese el número de C.I." :errors="form.errors.doc && !form.doc" />
                                </div>

                                <!-- Tipo Discapacidad -->
                                <div>
                                    <label for="discapacidad" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Tipo Discapacidad
                                    </label>
                                    <select v-model="form.discapacidad" aria-required="true" aria-label="Seleccionar tipo de discapacidad" :class="[
                                            form.errors.discapacidad && !form.discapacidad 
                                            ? 'border-red-500 text-red-500 placeholder-red-600' 
                                            : (form.discapacidad && form.discapacidad.trim() !== '' 
                                                ? 'border-gray-300 hover:border-blue-400' 
                                                : 'border-gray-300')
                                        ]" class="w-full cursor-pointer bg-gray-50 border border-gray-300 text-gray-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors">
                                        <option value="" selected hidden>Seleccione la discapacidad</option>
                                        <option v-for="option in options" :key="option.text" :value="option.value">
                                            {{ option.text }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Fecha de Emisión -->
                                <div>
                                    <label for="fecha_emision" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Fecha de Emisión
                                    </label>
                                    <Input input-type="date" v-model="form.fecha_emision" placeholder="Seleccione la fecha de emisión" :errors="form.errors.fecha_emision && !form.fecha_emision" />
                                </div>

                                <!-- Fecha de Vencimiento -->
                                <div>
                                    <label for="fecha_vencimiento" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Fecha de Vencimiento
                                    </label>
                                    <Input input-type="date" v-model="form.fecha_vencimiento" placeholder="Seleccione la fecha de vencimiento" :errors="form.errors.fecha_vencimiento && !form.fecha_vencimiento" />
                                </div>
                            </div>
                        </form>

                        <!-- Footer del formulario -->
                        <div class="flex items-center justify-between px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                            <!-- Espacio para información adicional o botones secundarios -->
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <span v-if="hasChanges" class="text-amber-600 dark:text-amber-400">
                                    • Hay cambios sin guardar
                                </span>
                                <span v-else class="text-green-600 dark:text-green-400">
                                    • Sin cambios pendientes
                                </span>
                            </div>

                            <!-- Botón principal -->
                            <div class="flex space-x-3">
                                <button v-if="hasChanges" type="button" @click="resetForm" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-700 transition-colors">
                                    Cancelar
                                </button>
                                <PrimaryButton type="submit" form="carnet-form" :disabled="!hasChanges" :class="{ 
                                        'opacity-50 cursor-not-allowed': !hasChanges,
                                        'transform hover:scale-105 active:scale-95': hasChanges
                                        }" class="px-6 py-2 text-sm font-medium transition-all duration-200 ease-in-out">
                                    <span class="flex items-center space-x-2">
                                        <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span>{{ isSubmitting ? 'Actualizando...' : 'Guardar Cambios' }}</span>
                                    </span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <Footer class="mt-1" />
    </div>
</div>
</template>
