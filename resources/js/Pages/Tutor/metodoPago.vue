<script setup>
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import Seccion from '@/components/Seccion.vue';
import Mensajes from '@/components/Mensajes.vue';
import Input from '@/components/Input.vue';
import {
    computed,
    ref,
    watch
} from 'vue';
import {
    Head,
    useForm,
    usePage
} from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import Rutas from '@/components/Rutas.vue';

const page = usePage();
const pagosDisponibles = computed(() => page.props.pagosDisponibles);
const notificacionTutor = computed(() => page.props.notificacionTutor);
const datosPago = computed(() => page.props.datosPago);
const datosQr = computed(() => page.props.datosQr);
const datosTranferir = computed(() => page.props.datosTranferir);

const formEditTutor = ref(false);
const formRegistrarCuenta = ref(false);
const showMensaje = ref(false);
const showQRForm = ref(false);
const showBankForm = ref(false);

const tipo = ref('');
const contenido = ref('');

//Mensajes
const MensajeErrorForm = {
    registroCorrecto: {
        header: 'Registro exitoso',
        text: 'Los datos se registraron correctamente.'
    },
    errorRegistro: {
        header: 'Error al registrar',
        text: 'Por favor, complete todos los campos requeridos.'
    },
    advertencia: {
        header: 'Algo inesperado ocurrió',
        text: 'Ha ocurrido un error inesperado. Intente nuevamente.'
    },
    eliminadoCorrecto: {
        header: 'Eliminacion exitosa',
        text: 'Se ha eliminina el dato correctamente.'
    },
    habilitadoCorrecto: {
        header: 'Habiltiación exitosa',
        text: 'Se ha habilitado el metodo de cobro.'
    }
};

const contMensajes = (tipoSms, tipoContenido) => {
    tipo.value = tipoSms;
    contenido.value = [MensajeErrorForm[tipoContenido]];
    showMensaje.value = true;
}

// Handler para cerrar el mensaje
const handleClose = () => {
    showMensaje.value = false;
}

// Formularios Qr
const qrForm = useForm({
    id_tutor: datosPago.value.id_tutor,
    image: null,
    nombre: `${datosPago.value.nombre_tutor} ${datosPago.value.apellido_tutor}`,
    telefono: datosPago.value.telefono,
    entidad: ''
});

const validateForm = () => {
    // Reinicia los errores
    Object.keys(qrForm.errors).forEach((key) => {
        qrForm.errors[key] = false;
    });

    // Valida cada campo
    if (!qrForm.image) qrForm.errors.image = true;
    if (!qrForm.nombre.trim()) qrForm.errors.nombre = true;
    if (!qrForm.telefono.trim()) qrForm.errors.telefono = true;
    if (!qrForm.entidad.trim()) qrForm.errors.entidad = true;

    // Retorna true si no hay errores
    return !Object.values(qrForm.errors).includes(true);
};

const saveQRForm = () => {
    if (!validateForm()) {
        contMensajes('error', 'errorRegistro');
        return;
    }

    const formData = new FormData();
    formData.append('id_tutor', qrForm.id_tutor);
    formData.append('image', qrForm.image);
    formData.append('nombre', qrForm.nombre);
    formData.append('telefono', qrForm.telefono);
    formData.append('entidad', qrForm.entidad || '');

    axios.post(route('metodo.agregarQr'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        .then((response) => {
            contMensajes('correcto', 'registroCorrecto');
            qrForm.reset();
            router.reload();

        })
        .catch((error) => {
            contMensajes('advertencia', 'advertencia');
        });
};

// Formularios Banck
const bankForm = useForm({
    id_tutor: datosPago.value.id_tutor,
    titular_cuenta: `${datosPago.value.nombre_tutor} ${datosPago.value.apellido_tutor}`,
    nombre_banco: '',
    numero_cuenta: '',
    tipo: ''
});

const validateBank = () => {
    // Reinicia los errores
    Object.keys(bankForm.errors).forEach((key) => {
        bankForm.errors[key] = false;
    });

    // Valida cada campo
    if (!bankForm.titular_cuenta.trim()) bankForm.errors.titular_cuenta = true;
    if (!bankForm.nombre_banco.trim()) bankForm.errors.nombre_banco = true;
    if (!bankForm.numero_cuenta.trim()) bankForm.errors.numero_cuenta = true;
    if (!bankForm.tipo.trim()) bankForm.errors.tipo = true;

    // Retorna true si no hay errores
    return !Object.values(bankForm.errors).includes(true);
};

// Función para guardar formulario banco
const saveBankForm = () => {
    if (!validateBank()) {
        contMensajes('error', 'errorRegistro');
        return;
    }

    bankForm.post(route('metodo.agregarT'), {
        onSuccess: async () => {
            router.reload();
            contMensajes('correcto', 'registroCorrecto');
        },
        onError: (errors) => {
            contMensajes('advertencia', 'advertencia');
        }
    });
};

const isDragging = ref(false)
const selectedFile = ref(null)

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        qrForm.image = file; // Asigna la imagen al objeto qrForm
    }
};

const form = useForm({
    id_tutor: '',
    tipo: '',
    id_cuenta: ''
});

// Función para eliminar método de pago
const removePaymentMethod = (idQr, tipo) => {
    form.id_cuenta = idQr;
    form.tipo = tipo;
    form.post(route('metodo.eliminar'), {
        onSuccess: async () => {
            qrForm.reset();
            bankForm.reset();
            showQRForm.value = false;
            showBankForm.value = false;
            contMensajes('correcto', 'eliminadoCorrecto');
            router.reload();
        },
        onError: (errors) => {
            router.reload();
            contMensajes('advertencia', 'advertencia');
        }
    });
};

const Habilitar = (idTutor, tipo) => {
    console.log(idTutor);
    form.id_tutor = idTutor;

    form.tipo = tipo;

    form.get(route('metodo.agregarMetodo'), {
        onSuccess: async () => {
            contMensajes('correcto', 'habilitadoCorrecto');
            router.reload();
        },
        onError: (errors) => {
            contMensajes('advertencia', 'advertencia');
        }
    });
};

const handleDrop = (event) => {
    isDragging.value = false
    const file = event.dataTransfer.files[0]
    if (file) {
        selectedFile.value = file;
    }
}

const options = [
    { text: "Banco de Crédito de Bolivia (BCP)", value: "Banco de Crédito de Bolivia (BCP)" },
    { text: "Banco Nacional de Bolivia (BNB)", value: "Banco Nacional de Bolivia (BNB)" },
    { text: "Banco Solidario", value: "Banco Solidario" },
    { text: "Banco FIE", value: "Banco FIE" },
    { text: "Banco Union", value: "Banco Union" },
    { text: "Banco Mercantil Santa Cruz", value: "Banco Mercantil Santa Cruz" },
    { text: "Banco Bisa", value: "Banco Bisa" },
    { text: "Banco Ecofuturo", value: "Banco Ecofuturo" },
    { text: "Banco Ganadero", value: "Banco Ganadero" },
    { text: "Banco Fortaleza", value: "Banco Fortaleza" },
    { text: "Banco Prodem", value: "Banco Prodem" },
    { text: "Banco Safi", value: "Banco Safi" },
    { text: "Banco Nacional de Comercio", value: "Banco Nacional de Comercio" },
    { text: "Banco Hipotecario", value: "Banco Hipotecario" },
];

// Accede a las props pasadas por Inertia.js
const props = defineProps({
    item: {
        type: Object,
        required: false,
        default: () => ({}) // Valor por defecto para evitar errores
    }
});

const isBlurred = ref(true);
let timeoutId = null;

const removeBlur = () => {
    isBlurred.value = false;

    // Clear any existing timeout
    if (timeoutId) {
        clearTimeout(timeoutId);
    }

    // Set timeout to reapply blur after 2 seconds
    timeoutId = setTimeout(() => {
        isBlurred.value = true;
    }, 5000);
};

const showFullAccount = ref(false);

const toggleAccountVisibility = () => {
    showFullAccount.value = !showFullAccount.value;
};
</script>

<style>
.file-container {
    display: flex;
    align-items: center;
    text-align: center;
    /* Alinea verticalmente los elementos */
    justify-content: center;
    /* Centra el contenido horizontalmente */
    height: 80px;
    /* Ajusta la altura según sea necesario */
    overflow: hidden;
    /* Esconde el texto que se desborda */
}

.file-container span {
    white-space: nowrap;
    /* Evita el salto de línea en el primer span */
    overflow: hidden;
    text-overflow: ellipsis;
    /* Muestra "..." si el texto es muy largo */
    display: block;
    /* Asegura que el contenido tenga un bloque dentro del contenedor */
    max-width: 100%;
    /* Ajusta el ancho máximo */
}
</style>
<template>
<Head title="UMADIS" />
<div class="flex h-screen bg-gray-50 font-roboto">
    <Sidebar />
    <div class="flex-1 flex flex-col overflow-hidden">
        <div v-if="showMensaje">
            <Mensajes :tipo="tipo" :contenido="contenido" @close="handleClose" />
        </div>
        <Header class="mb-0" :datos="notificacionTutor.data" :pagos="pagosDisponibles" />
        
        <div class="py-2">
            <div class="px-5 py-1 flex justify-between">
                <h1 class="font-semibold text-2xl">Metodo de Pago</h1>
                <Rutas label1="Inicio" label2="Tutores" label3="Metodo Pago" />
            </div>
            <!-- <Seccion class="mt-1 " nombre="Tutor" texto="Gestione todos los tutores existentes" :botonReportTutor="true" :boton="false" /> -->
        </div>

        <div class="flex justify-between p-4 pb-0 bg-white border-x-2 border-t-2 rounded-t-lg mr-1">
            
        </div>
        
        <main v-show="!formEditTutor && !formRegistrarCuenta" class="flex-1 overflow-x-hidden bg-white overflow-y-auto border-b-2 border-x-2 rounded-b-lg mr-1">
            <div class="max-w-7xl mx-auto p-6 pt-2">
                <!-- Sección de información -->
                <div class="text-center mb-0">
                    <!-- Alerta de advertencia -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 py-2 mb-1">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <!-- Ícono de advertencia -->
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Importante:</strong> Verifique cuidadosamente los datos bancarios antes de guardar. Las transacciones realizadas son irreversibles.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Grid de métodos de pago -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Sección de Efectivo -->
                    <div class="bg-white border rounded-lg shadow-lg overflow-hidden min-h-[400px] flex flex-col" :class="[datosPago.qr === 1 ? 'border-green-600' : 'border-gray-500']">
                        <div class="border p-2 text-center" :class="[datosPago.efectivo === 1 ? 'bg-green-200 border-green-600' : 'bg-gray-300 border-gray-500']">
                            <h2 class="text-xl font-semibold" :class="[datosPago.efectivo === 1 ? 'text-green-900' : 'text-gray-900']">
                                Pago en Efectivo
                            </h2>
                        </div>
                        <div class="w-full text-center px-4 text-sm font-medium" :class="[datosPago.efectivo === 1 ? 'bg-green-100 text-green-500' : 'bg-gray-100 text-gray-500']">
                            <span>{{ datosPago.efectivo === 1 ? 'Habilitado' : 'Deshabilitado' }}</span>
                        </div>
                        <div class="p-3 pt-1 flex-grow flex flex-col justify-center">
                            <template v-if="datosPago.efectivo === 1">
                                <div class="bg-gray-50 px-4 py-2 rounded-lg shadow-sm border border-gray-200 space-y-3">
                                    <div class="flex justify-center mb-8">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-gray-700 py-3 px-3 space-y-5">
                                        <div class="space-y-6">
                                            <p class="flex items-center text-sm font-medium text-gray-900 text-justify">
                                                <span class="text-3xl text-green-500 mr-1">•</span>
                                                El tutor podrá cobrar el bono del beneficiario directamente en nuestras oficinas
                                            </p>
                                            <p class="flex items-center text-sm font-medium text-gray-900 text-justify">
                                                <span class="text-3xl text-green-500 mr-1">•</span>
                                                Disponible en horario de atención: Lunes a Viernes de 8:00 AM a 5:00 PM
                                            </p>
                                            <p class="flex items-center text-sm font-medium text-gray-900 text-justify">
                                                <span class="text-3xl text-green-500 mr-1">•</span>
                                                Se requiere presentar identificación oficial vigente
                                            </p>
                                            <p class="flex items-center text-sm font-medium text-gray-900 text-justify">
                                                <span class="text-3xl text-green-500 mr-1">•</span>
                                                Se entregará un recibo oficial al momento del pago
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Sección de QR -->
                    <div class="bg-white border rounded-lg shadow-lg overflow-hidden min-h-[400px] flex flex-col" :class="[datosPago.qr === 1 ? 'border-blue-600' : 'border-gray-500']">
                        <div class="border p-2 text-center" :class="[datosPago.qr === 1 ? 'bg-blue-200 border-blue-600' : 'bg-gray-300 border-gray-500']">
                            <h2 class="text-xl font-semibold" :class="[datosPago.qr === 1 ? 'text-blue-900' : 'text-gray-900']">
                                Pago con QR
                            </h2>
                        </div>
                        <div class="w-full text-center px-4 text-sm font-medium" :class="[datosPago.qr === 1 ? 'bg-blue-100 text-blue-500' : 'bg-gray-100 text-gray-500']">
                            <span>{{ datosPago.qr === 1 ? 'Habilitado' : 'Deshabilitado' }}</span>
                        </div>
                        <div class="p-3 pt-2 flex-grow flex flex-col justify-center">
                            <template v-if="datosQr.length > 0">
                                <div v-for="item in datosQr" :key="item.id_cuenta" class="bg-gray-50 px-6 py-2 rounded-lg shadow-sm border border-gray-200 space-y-3">
                                    <div class="flex justify-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4Zm10 10h6v6h-6v-6Zm0-10h6v6h-6V4Zm-4 10h.01v.01H10V14Zm0 4h.01v.01H10V18Zm-3 2h.01v.01H7V20Zm0-4h.01v.01H7V16Zm-3 2h.01v.01H4V18Zm0-4h.01v.01H4V14Z" />
                                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M7 7h.01v.01H7V7Zm10 10h.01v.01H17V17Z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="relative w-36 h-36 bg-gray-200 rounded-lg mx-auto flex items-center justify-center mb-2 overflow-hidden">
                                        <!-- Blurred image -->
                                        
                                        <img :src="`/${item.image_qr}`" alt="QR Image" class="rounded-lg w-full h-full object-cover transition-all duration-300" :class="{ 'filter blur-md': isBlurred }" />

                                        <!-- Centered button -->
                                        <button v-if="isBlurred" @click="removeBlur" class="absolute inset-0 m-auto w-12 h-12 bg-white bg-opacity-30 rounded-full flex items-center justify-center backdrop-filter backdrop-blur-sm hover:bg-opacity-50 transition-all duration-200 focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <template v-if="isBlurred" >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                                </template>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-1 text-gray-800 dark:text-gray-200">
                                        <p class="capitalize border-b border-gray-300 dark:border-gray-600  mb-2"><strong>Nombre:</strong> {{ item.nombre }}</p>
                                        <p class="border-b border-gray-300 dark:border-gray-600 mb-2"><strong>Teléfono:</strong> {{ item.telefono }}</p>
                                        <p class=""><strong>Banco:</strong> {{ item.entidad }}</p>
                                    </div>
                                    <div class="mt-4 flex justify-center">
                                        <button @click="removePaymentMethod(item.id_cuenta, 'qr')" class="inline-flex items-center justify-center bg-blue-900 text-white py-2 px-8 rounded-lg hover:bg-red-800 transition-colors duration-200">
                                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div v-if="!showQRForm" class="flex flex-col items-center justify-center h-full">
                                    <div v-if="datosPago.qr === 0">
                                        <button @click.prevent="Habilitar(datosPago.id_tutor, 'qr')" class="bg-blue-900 text-white py-3 px-6 rounded-lg hover:bg-blue-600 transition-colors duration-200 font-medium">
                                            Habilitar
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button @click="showQRForm = true" class="flex justify-center bg-blue-300 text-white py-5 px-3.5 rounded-full hover:bg-blue-400 transition-colors duration-200 font-medium">
                                            <span class="text-blue-800 text-xl">+</span>
                                            <svg class="w-7 h-7 text-blue-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4Zm10 10h6v6h-6v-6Zm0-10h6v6h-6V4Zm-4 10h.01v.01H10V14Zm0 4h.01v.01H10V18Zm-3 2h.01v.01H7V20Zm0-4h.01v.01H7V16Zm-3 2h.01v.01H4V18Zm0-4h.01v.01H4V14Z" />
                                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M7 7h.01v.01H7V7Zm10 10h.01v.01H17V17Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <form v-else @submit.prevent="saveQRForm" class="space-y-2 pt-0 p-4">
                                    <div class="relative">
                                        <label class="block text-sm font-medium text-gray-700 mb-0">Imagen QR <span class="text-red-700">*</span></label>
                                        <div @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop" :class="[qrForm.errors.image && !selectedFile ? 'border-red-500 text-red-500' : (selectedFile ? 'border-gray-300 hover:border-blue-400' : 'border-gray-300')]" class="relative border-2 border-dashed rounded-lg px-4 py-0 transition-colors">
                                            <input type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="handleFileChange">
                                            <div class="file-container">
                                                <span v-if="selectedFile">{{ selectedFile.name }}</span>
                                                <span v-else class="flex">
                                                    <span :class="['text-sm', qrForm.errors.image && !selectedFile ? 'text-red-500' : 'text-blue-600']">
                                                        Selecciona un archivo
                                                    </span>
                                                    <span>o</span>
                                                    <span>arrastra y suelta aquí</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="capitalize block text-sm font-medium text-gray-700">Nombre <span class="text-red-700">*</span></label>
                                        <Input v-model="qrForm.nombre" placeholder="Ingrese el nombre" input-type="text" :errors="qrForm.errors.nombre" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Teléfono <span class="text-red-700">*</span></label>
                                        <Input v-model="qrForm.telefono" placeholder="Ingrese el número de teléfono" input-type="number" :errors="qrForm.errors.telefono" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Banco <span class="text-red-700">*</span></label>
                                        <select v-model="qrForm.entidad" aria-required="true" :class="[qrForm.errors.entidad && !qrForm.entidad ? 'border-red-500 text-red-500 ' : (qrForm.entidad && qrForm.entidad.trim() !== '' ? 'border-gray-300 hover:border-blue-400' : 'border-gray-300')]" class="w-full cursor-pointer bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-700 dark:placeholder-gray-400 dark:text-gray-400 focus:ring--600 focus:border-gray-600">
                                            <option value="" selected hidden>Seleccione una entidad financiera</option>
                                            <option v-for="option in options" :key="option.value" :value="option.value">
                                                {{ option.text }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex space-x-4">
                                        <button type="submit" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors duration-200">
                                            Guardar
                                        </button>
                                        <button type="button" @click="showQRForm = false" class="flex-1 bg-gray-200 text-blak py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </div>

                    <!-- Sección de Transferencia -->
                    <div class="bg-white border rounded-lg shadow-lg overflow-hidden min-h-[400px] flex flex-col" :class="[datosPago.transferencia === 1 ? 'border-purple-600' : 'border-gray-500']">
                        <div class="border p-2 text-center" :class="[datosPago.transferencia === 1 ? 'bg-purple-200 border-purple-600' : 'bg-gray-300 border-gray-500']">
                            <h2 class="text-xl font-semibold" :class="[datosPago.transferencia === 1 ? 'text-purple-900' : 'text-gray-900']">
                                Transferencia Bancaria
                            </h2>
                        </div>
                        <div class="w-full text-center px-4 text-sm font-medium" :class="[datosPago.transferencia === 1 ? 'bg-purple-100 text-purple-500' : 'bg-gray-100 text-gray-500']">
                            <span>{{ datosPago.transferencia === 1 ? 'Habilitado' : 'Deshabilitado' }}</span>
                        </div>
                        <div class="p-3 pt-2 flex-grow flex flex-col justify-center">
                            <template v-if="datosTranferir.length > 0">
                                <div v-for="item in datosTranferir" class="space-y-3">
                                    <div class="bg-gray-50 px-6 py-2 rounded-lg shadow-sm border border-gray-200">
                                        <div class="flex justify-center pb-4">
                                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-purple-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h2m3 0h5M3 7v10a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1Z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="rounded-lg mx-auto pt-5 pb-10 flex items-center justify-center" style="width: fit-content;">
                                            <img :src="`/images/logos/${ item.nombre_banco.split(' ')[1] || '' }.png`" alt="Banco Image" class="iconoLog rounded-lg object-cover" />
                                            <!-- <img src="/images/logos/ganadero.png" alt="Banco Image" class="iconoLog rounded-lg object-cover" /> -->
                                        </div>

                                        <div class="bg-purple-100 dark:bg-gray-800 rounded-lg p-1 text-gray-800 dark:text-gray-200">
                                            <p class="border-b border-gray-300 text-base"><strong>Banco:</strong> {{ item.nombre_banco }}</p>
                                            <p class="border-b border-gray-300 text-base flex items-center justify-between">
                                                <span>
                                                    <strong>Nº de Cuenta:</strong>
                                                    {{ showFullAccount ? item.numero_cuenta : (item.numero_cuenta ? '**** **** ' + item.numero_cuenta.slice(-4) : '') }}
                                                </span>
                                                <button @click="toggleAccountVisibility" class="text-gray-600 dark:text-gray-300 hover:text-purple-600 transition-colors" :title="showFullAccount ? 'Ocultar número' : 'Mostrar número completo'">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <template v-if="showFullAccount">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </template>
                                                        <template v-else>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                                        </template>
                                                    </svg>
                                                </button>
                                            </p>
                                            <p class="border-b border-gray-300 text-base"><strong>Titular de la Cuenta:</strong> <span class="capitalize">{{ item.titular_cuenta }}</span></p>
                                            <p class="border-b border-gray-300 text-base"><strong>Tipo de Cuenta:</strong> {{ item.tipo }}</p>
                                        </div>
                                        <div class="mt-4 flex justify-center">
                                            <button @click="removePaymentMethod(item.id_cuenta, 'transferencia')" class="inline-flex items-center justify-center bg-purple-900 text-white py-2 px-8 rounded-lg hover:bg-red-800 transition-colors duration-200">
                                                <svg class="w-5 h-5 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div v-if="!showBankForm" class="flex flex-col items-center justify-center h-full">
                                    <div v-if="datosPago.transferencia === 0">
                                        <button @click.prevent="Habilitar(datosPago.id_tutor, 'transferencia')" class="bg-blue-900 text-white py-3 px-6 rounded-lg hover:bg-blue-600 transition-colors duration-200 font-medium">
                                            Habilitar
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button @click="showBankForm = true" class="flex justify-center bg-purple-300 text-white py-5 px-3.5 rounded-full hover:bg-purple-400 transition-colors duration-200 font-medium">
                                            <span class="text-purple-800 text-xl">+</span>
                                            <svg class="w-7 h-7 text-purple-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h2m3 0h5M3 7v10a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <form v-else @submit.prevent="saveBankForm" class="space-y-2 pt-0 p-4">
                                    <!-- Form fields remain the same -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Titular <span class="text-red-700">*</span></label>
                                        <Input v-model="bankForm.titular_cuenta" placeholder="Ingrese el nombre del titular" input-type="text" focus-color="purple" :errors="bankForm.errors.titular_cuenta && !bankForm.titular_cuenta" class="capitalize" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Banco <span class="text-red-700">*</span></label>
                                        <select v-model="bankForm.nombre_banco" aria-required="true" :class="[bankForm.errors.nombre_banco && !bankForm.nombre_banco ? 'border-red-500 text-red-500 placeholder-red-600' : (bankForm.nombre_banco && bankForm.nombre_banco.trim() !== '' ? 'border-gray-300 hover:border-blue-400' : 'border-gray-300')]" class="w-full cursor-pointer bg-gray-50 border border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-gray-800 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-700 dark:placeholder-gray-400 dark:text-gray-400">
                                            <option value="" selected hidden>Seleccionar la entidad financiera</option>
                                            <option v-for="option in options" :key="option.value" :value="option.value">
                                                {{ option.text }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Número de cuenta <span class="text-red-700">*</span></label>
                                        <Input v-model="bankForm.numero_cuenta" placeholder="Ingrese el numero de cuenta" input-type="number" focus-color="purple" :errors="bankForm.errors.numero_cuenta && !bankForm.numero_cuenta" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipo de cuenta <span class="text-red-700">*</span></label>
                                        <select v-model="bankForm.tipo" aria-required="true" :class="[bankForm.errors.tipo && !bankForm.tipo ? 'border-red-500 text-red-500 placeholder-red-600' : (bankForm.tipo && bankForm.tipo.trim() !== '' ? 'border-gray-300 hover:border-blue-400' : 'border-gray-300')]" class="w-full cursor-pointer bg-gray-50 border border-gray-300 focus:border-purple-500 focus:ring-purple-500 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-700 dark:border-gray-700 dark:placeholder-gray-400 dark:text-gray-400">
                                            <option value="" selected hidden>Seleccione el tipo de cuenta</option>
                                            <option value="Cuenta Corriente">Cuenta Corriente</option>
                                            <option value="Caja de Ahorro">Caja de Ahorro</option>
                                        </select>
                                    </div>
                                    <div class="flex space-x-4 pt-4">
                                        <button type="submit" class="flex-1 bg-purple-500 text-white py-2 px-4 rounded-lg hover:bg-purple-600 transition-colors duration-200">
                                            Guardar
                                        </button>
                                        <button type="button" @click="showBankForm = false" class="flex-1 bg-gray-200 text-blak py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <Footer />
    </div>
</div>
</template>
