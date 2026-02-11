<script setup>
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
const props = defineProps({
    idFor: Number,
    estadoBaja: String,
    persona: {
        type: [Object, Array],
        default: () => ({})
    }
});
const emit = defineEmits(['close', 'editar']); // Emite un evento para cerrar el modal
</script>

<template>
<!-- Modal Background -->
<div id="progress-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <!-- Modal Content -->
    <div class="relative bg-white dark:bg-gray-700 rounded-lg shadow p-6 w-full max-w-md mx-4">
        <!-- Close Button -->
        <button type="button" @click="$emit('close')" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
            </svg>
        </button>
        <button type="button" @click="$emit('close')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
            </svg>
        </button>

        <!-- Tutor Details -->
        <div v-show="props.estadoBaja === 'bajatemporal' || props.estadoBaja === 'activo'">
            <!-- Icono -->
            <div class="flex flex-col items-center justify-center">
                <!-- Icono de advertencia -->
                <h1 class="text-6xl text-yellow-500">⚠️</h1>
                <!-- Texto de advertencia -->
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mt-2">¡Atención!</h2>
            </div>

            <div class="rounded-lg">
                <!-- Párrafo justificado -->
                <p class="text-justify text-gray-700 dark:text-gray-300 mb-6">
                    Está a punto de solicitar la baja definitiva del beneficiario, lo que implica que no será elegible para recibir pagos futuros de los bonos asignados.
                    Por esta razón, proceda con extrema precaución, ya que esta acción tendrá un impacto significativo en el proceso de asignación de beneficios.
                </p>
            </div>
            <!-- Accept Button -->
            <div class="mt-6 text-center">
                <PrimaryButton @click="$emit('editar', props.persona, props.idFor)" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
                    Continuar
                </PrimaryButton>
                <SecondaryButton @click="$emit('close')" class="mx-2 my-2 ms:my-0 px-6 py-2 bg-blue-600 text-sm font-medium rounded-lg shadow hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
                    Cancelar
                </SecondaryButton>
            </div>
        </div>

        <div v-show="props.estadoBaja === 'bajadefinitiva'">
            <div class="flex flex-col items-center justify-center mb-5">
                <!-- Icono de edición bloqueada -->
                <h1 class="text-6xl text-gray-500">🚫</h1>
                <!-- Título de advertencia -->
                <h2 class="text-center text-2xl font-semibold text-gray-800 dark:text-gray-100 mt-2">No se puede solicitar la</h2>
                <h2 class="text-center text-xl font-semibold text-gray-800 dark:text-gray-100">(Baja Definitiva)</h2>
            </div>

            <div class="rounded-lg mb-10">
                <!-- Párrafo justificado -->
                <p class="text-justify text-gray-700 dark:text-gray-300 mb-6">
                    El beneficiario ya se encuentra con
                    <span class="font-bold text-gray-800 dark:text-gray-100">Baja Definitiva</span>.
                    Si considera que esta situación se debe a un
                    <span class="font-bold text-gray-800 dark:text-gray-100">error</span>, por favor acérquese a las oficinas de
                    <span class="font-bold text-gray-800 dark:text-gray-100">UMADIS</span> para realizar una corrección.
                </p>
            </div>

            <!-- Accept Button -->
            <div class="mt-6 text-center">
                <PrimaryButton @click="$emit('close')" class="px-6 py-2 bg-blue-700 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800">
                    Aceptar
                </PrimaryButton>
            </div>
        </div>
        <div v-show="props.estadoBaja === 'sinMes'">
            <div class="flex flex-col items-center justify-center">
                <!-- Icono: Información -->
                <h1 class="text-6xl text-blue-500">ℹ️</h1>
                <!-- Título de advertencia -->
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mt-2">No tiene meses habilitados</h2>
            </div>

            <div class="rounded-lg">
                <!-- Párrafo justificado -->
                <p class="text-justify text-gray-700 dark:text-gray-300 mb-6">
                    El beneficiario no tiene meses disponibles para cobrar el bono. Verifique si se encuentra en
                    <span class="font-bold text-gray-800 dark:text-gray-100">Baja Temporal</span> o
                    <span class="font-bold text-gray-800 dark:text-gray-100">Baja Definitiva</span>.
                    En caso de que su estado esté correcto, espere pacientemente hasta que se le habilite un mes.
                    Si cree que es un error, puede acercarse a las oficinas de
                    <span class="font-bold text-gray-800 dark:text-gray-100">UMADIS</span> para confirmar su situación.
                </p>
            </div>

            <!-- Botón de aceptación -->
            <div class="mt-6 text-center">
                <PrimaryButton @click="$emit('close')" class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
                    Aceptar
                </PrimaryButton>
            </div>
        </div>
        <div v-show="props.estadoBaja === 'existe'">
            <div class="flex flex-col items-center justify-center">
                <!-- Icono: Advertencia -->
                <h1 class="text-6xl text-yellow-500">⚠️</h1>
                <!-- Título actualizado -->
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mt-2">Solicitud en Curso</h2>
            </div>
            <AuthenticatedLayout>
                <div class="rounded-lg">
                    <!-- Mensaje actualizado -->
                    <p class="text-justify text-gray-700 dark:text-gray-300 mb-6">
                        Se ha detectado que ya existe una solicitud de baja definitiva para el beneficiario. Por favor, espere con paciencia mientras se procesa su solicitud.
                        El equipo de <span class="font-bold text-gray-800 dark:text-gray-100">UMADIS</span> atenderá la solicitud lo más pronto posible.
                        Si considera que esta solicitud fue realizada por error, puede contactarnos al numero :
                        <span class="font-bold text-gray-800 dark:text-gray-100">{{$page.props.auth.user?.telefono}}</span>
                    </p>
                </div>
            </AuthenticatedLayout>
            <!-- Botón de aceptación -->
            <div class="mt-6 text-center">
                <PrimaryButton @click="$emit('close')" class="px-6 py-2 bg-blue-900 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
                    Aceptar
                </PrimaryButton>
            </div>
        </div>

    </div>
</div>
</template>
