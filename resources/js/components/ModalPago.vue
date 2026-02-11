<script setup>
import { ref } from 'vue';
import ModalConfirmacion from './ModalConfirmacion.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const modalConfirm = ref(false);

const props = defineProps({
    id: Number,
    tipo: String,
    datos: {
        type: [Object, Array],
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'submit']); // Emite un evento para cerrar el modal

const confirmar = (idHabilitado, tipo) => {
    modalConfirm.value = true;
}

const closeModal = () => {
    modalConfirm.value = false;
}

const submit = () => {
    emit('submit', props.id, props.tipo);
}

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

<template>
<!-- Modal Background -->
<ModalConfirmacion v-if="modalConfirm" :id="id" :tipo="tipo" @confirmar="submit" @close="closeModal" />
<div id="progress-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-30">
    <!-- Modal Content -->
    <div class="relative bg-white dark:bg-gray-700 rounded-lg shadow pt-2 py-2 w-full max-w-md mx-4">
        <!-- Close Button -->
        <button type="button" @click="$emit('close')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
            </svg>
        </button>

        <!-- Tutor Details -->
        <div v-if="tipo === 'qr'" class="p-6 pb-2">
            <!-- Encabezado -->
            <div class="text-center mb-4">
                <!-- Icono con fondo decorativo -->
                <div class="flex items-center justify-center mb-1">
                    <div class="bg-blue-100 dark:bg-gray-800 rounded-full p-2">
                        <svg class="w-12 h-12 text-blue-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4Zm10 10h6v6h-6v-6Zm0-10h6v6h-6V4Zm-4 10h.01v.01H10V14Zm0 4h.01v.01H10V18Zm-3 2h.01v.01H7V20Zm0-4h.01v.01H7V16Zm-3 2h.01v.01H4V18Zm0-4h.01v.01H4V14Z" />
                            <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M7 7h.01v.01H7V7Zm10 10h.01v.01H17V17Z" />
                        </svg>
                    </div>
                </div>
                <!-- Título -->
                <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Datos para el Pago con QR</h2>
            </div>

            <!-- Lista de Datos -->
            <div v-for="item in datos" :key="item.id_qr" class="mb-6">
                <!-- Imagen QR -->
                <div class="flex justify-center mb-4">
                    <div class="relative w-40 h-40 bg-gray-200 rounded-lg mx-auto flex items-center justify-center mb-2 overflow-hidden">
                        <!-- Blurred image -->
                        <img :src="`/${item.image_qr}`" alt="QR Image" class="rounded-lg w-full h-full object-cover transition-all duration-300" :class="{ 'filter blur-md': isBlurred }" />

                        <!-- Centered button -->
                        <button v-if="isBlurred" @click="removeBlur" class="absolute inset-0 m-auto w-12 h-12 bg-white bg-opacity-30 rounded-full flex items-center justify-center backdrop-filter backdrop-blur-sm hover:bg-opacity-50 transition-all duration-200 focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <template v-if="isBlurred">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </template>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Detalles -->
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 text-gray-800 dark:text-gray-200">
                    <p class="capitalize border-b border-gray-300 dark:border-gray-600 pb-2 mb-2"><strong>Nombre:</strong> {{ item.nombre }}</p>
                    <p class="border-b border-gray-300 dark:border-gray-600 pb-2 mb-2"><strong>Teléfono:</strong> {{ item.telefono }}</p>
                    <p class="pb-2"><strong>Banco:</strong> {{ item.entidad }}</p>
                </div>
            </div>
        </div>

        <div v-if="tipo === 'transferencia'" class="p-6 pb-2 bg-white dark:bg-gray-800">
            <div class="text-center mb-4">
                <!-- Icono con fondo decorativo -->
                <div class="flex items-center justify-center mb-1">
                    <div class="bg-purple-100 dark:bg-purple-100 rounded-full p-2">
                        <svg class="w-12 h-12 text-purple-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M6 14h2m3 0h5M3 7v10a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1Z" />
                        </svg>
                    </div>
                </div>
                <!-- Título -->
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Datos para el pago por Transferencia</h2>
            </div>

            <!-- Detalles de la cuenta -->
            <div v-for="item in datos" :key="item.id_qr" class="rounded-lg bg-gray-100 dark:bg-gray-700 p-4 mb-4">
                <div class="flex flex-col items-center">
                    <div class="rounded-lg mx-auto py-5 flex items-center justify-center" style="width: fit-content;">
                        <img :src="`/images/logos/${ item.nombre_banco.split(' ')[1] || '' }.png`" alt="Banco Image" class="iconoLog rounded-lg object-cover" />
                    </div>
                    <div class="w-full space-y-2 text-gray-800 dark:text-gray-300">
                        <p class="border-b border-gray-300 pb-2"><strong>Banco:</strong> {{ item.nombre_banco }}</p>
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
                        <p class="border-b border-gray-300 pb-2"><strong>Titular de la Cuenta:</strong> <span class="capitalize">{{ item.titular_cuenta }}</span></p>
                        <p class="border-b border-gray-300 pb-2"><strong>Tipo de Cuenta:</strong> {{ item.tipo }}</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Botones de acción -->
        <div class="mb-6 text-center ">
            <PrimaryButton @click.prevent="confirmar(props.id, props.tipo)" class="px-6 py-2 text-sm font-medium rounded-lg shadow">
                Pagado
            </PrimaryButton>
            <SecondaryButton @click="$emit('close')" class="mx-2 my-2 ms:my-0 px-6 py-2 text-sm font-medium rounded-lg shadow">
                Cancelar
            </SecondaryButton>
        </div>
    </div>
</div>
</template>
