<script setup>
import PrimaryButton from './PrimaryButton.vue';
const props = defineProps({
    nombre: String,
    tipo: String,
});
const emit = defineEmits(['close']); // Emite un evento para cerrar el modal
</script>

<template>
<!-- Modal Background -->
<div id="progress-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <!-- Modal Content -->
    <div class="relative bg-white dark:bg-gray-700 rounded-lg shadow p-6 w-full max-w-md mx-4">
        <!-- Close Button -->
        <button type="button" @click="$emit('close')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
            </svg>
        </button>

        <!-- Tutor Details -->
        <!-- Icono -->
        <div v-if="props.tipo === 'sinCarnet'">
            <!-- Primer ícono -->
            <div class="relative flex items-center">
                <svg class="absolute top-2 left-7 w-4 h-4 text-red-500 dark:text-red-500 transform translate-x-1/2 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="m6 6 12 12m3-6a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <!-- Segundo ícono -->
                <svg class="w-12 h-12 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z" clip-rule="evenodd" />
                </svg>
                <!-- Texto -->
                <h2 class="ml-4 text-2xl font-semibold text-gray-800 dark:text-gray-100">Sin carnet</h2>
            </div>

            <!-- Párrafo justificado -->
            <p class="text-justify text-gray-700 dark:text-gray-300 mb-6">
                Se le informa que el beneficiario
                <span class="capitalize font-bold text-gray-800 dark:text-gray-100">{{ props.nombre }}</span>
                no cuenta con el carnet correspondiente para realizar el cobro del bono. Por favor, aproxímese a las oficinas de
                <span class="font-semibold text-gray-800 dark:text-gray-100">UMADIS</span> con los siguientes requisitos.
            </p>
        </div>

        <div v-else>
            <!-- Primer ícono -->
            <div class="relative flex items-center">
                <svg class="absolute top-2 left-7 w-5 h-5 text-yellow-400 dark:text-yellow-400 transform translate-x-1/2 -translate-y-1/2 z-20" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>

                <!-- Segundo ícono -->
                <svg class="w-12 h-12 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z" clip-rule="evenodd" />
                </svg>
                <!-- Texto -->
                <h2 class="ml-4 text-2xl font-semibold text-gray-800 dark:text-gray-100">Carnet vencido</h2>
            </div>

            <!-- Párrafo justificado -->
            <p class="text-justify text-gray-700 dark:text-gray-300 mb-6">
                Se le informa que el beneficiario
                <span class="capitalize font-bold text-gray-800 dark:text-gray-100">{{ props.nombre }}</span>
                tiene el carnet vencido y no puede realizar el cobro del bono. Por favor, aproxímese a las oficinas de
                <span class="font-semibold text-gray-800 dark:text-gray-100">UMADIS</span> con los siguientes requisitos para la renovación del carnet de discapacidad.
            </p>
        </div>

        <div class="rounded-lg">
            <!-- Subtítulo de requisitos -->
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3">
                Requisitos:
            </h2>

            <!-- Lista de requisitos -->
            <div class="space-y-2">
                <div class="p-2 bg-gray-300 rounded-lg dark:bg-gray-800">
                    <p class="text-gray-700 dark:text-gray-300">
                        🩺 Informe médico del especialista.
                    </p>
                </div>
                <div class="p-2 bg-gray-300 rounded-lg dark:bg-gray-800">
                    <p class="text-gray-700 dark:text-gray-300">
                        🆔 Cédula de identidad vigente de la persona con discapacidad.
                    </p>
                </div>
                <div class="p-2 bg-gray-300 rounded-lg dark:bg-gray-800">
                    <p class="text-gray-700 dark:text-gray-300">
                        👨‍👩‍👧‍👦 Cédula de identidad vigente (original y fotocopia) de uno de los padres. En caso de ser tutor, traer certificado de tutoría o custodia.
                    </p>
                </div>
                <div class="p-2 bg-gray-300 rounded-lg dark:bg-gray-800">
                    <p class="text-gray-700 dark:text-gray-300">
                        🗺️ Croquis del domicilio con nombres de calles y zona, en caso de que no haya lugares de referencia.
                    </p>
                </div>
            </div>

        </div>

        <!-- Accept Button -->
        <div class="mt-6 text-center">
            <PrimaryButton @click="$emit('close')" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
                Aceptar
            </PrimaryButton>
        </div>
    </div>
</div>
</template>
