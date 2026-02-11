<script setup>
import { onMounted, onUnmounted } from 'vue';
import Button from './Button.vue';

const props = defineProps({
    tipo: {
        type: String,
        required: true
    },
    registradoId: {
        type: [Number, String],
        default: null
    }
});

//console.log(props);

const emit = defineEmits(['close', 'continuar']); // Emite un evento para cerrar el modal

const closeOnEscape = (e) => {
    if (e.key === 'Escape' || e.key === 'Enter') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <!-- Modal Background -->
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm flex items-center justify-center z-50 px-4">
        <!-- Modal Content -->
        <div
            class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md transform transition-all duration-300">
            <!-- Close Button -->

            <button type="button" @click="$emit('close')"
                class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>

            <div v-if="props.tipo != 'registrado'">

                <!-- Contenido -->
                <div class="p-8 text-center">
                    <!-- Baja Temporal -->
                    <div v-show="props.tipo === 'baja_temporal'">
                        <!-- Icono -->
                        <div
                            class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                            <svg class="w-12 h-12 text-yellow-500 dark:text-yellow-400"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <!-- Título -->
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                            Baja Temporal
                        </h2>
                        <!-- Texto -->
                        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            El beneficiario se encuentra con una
                            <span class="font-semibold text-gray-800 dark:text-gray-100">baja temporal</span>, lo que
                            impide habilitar el mes para recibir pagos de bonos hasta
                            que su estado se actualice. Asegúrese de realizar el seguimiento
                            adecuado, ya que este estado afecta la asignación de beneficios.
                        </p>
                    </div>

                    <!-- Baja Definitiva -->
                    <div v-show="props.tipo === 'baja_definitiva'">
                        <!-- Icono -->
                        <div
                            class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40 shadow-inner mb-4">
                            <svg class="w-12 h-12 text-red-500 dark:text-red-400" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M15 9l-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <!-- Título -->
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                            Baja Definitiva
                        </h2>
                        <!-- Texto -->
                        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            El beneficiario está marcado con
                            <span class="font-semibold text-gray-800 dark:text-gray-100">Baja Definitiva</span>, lo que
                            significa que no es elegible para recibir más pagos de
                            bonos. Si considera que esta baja es un
                            <span class="font-semibold text-gray-800 dark:text-gray-100">error</span>, por favor
                            verifique el estado del beneficiario y proceda con la
                            corrección si es necesario.
                        </p>
                    </div>
                </div>

                <!-- DESPUÉS del div de baja_definitiva, AGREGAR: -->

                <!-- Ya es Beneficiario -->
                <div v-show="props.tipo === 'beneficiario'" class="p-3 pt-0 text-center">
                    <!-- Icono -->
                    <div
                        class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900/40 shadow-inner mb-4">
                        <svg class="w-12 h-12 text-sky-500 dark:text-sky-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <!-- Título -->
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                        Beneficiario Registrado
                    </h2>
                    <!-- Texto -->
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        Esta persona ya se encuentra registrada como
                        <span class="font-semibold text-gray-800 dark:text-gray-100">beneficiario activo</span> en el
                        sistema.
                        No es necesario volver a registrarla. Si necesita realizar alguna modificación en sus datos
                        personales
                        o información del beneficiario, puede editarlos directamente desde la tabla de beneficiarios.
                    </p>
                </div>
                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700/50 text-center">
                    <Button @click="$emit('close')" class="text-white bg-blue-600 hover:bg-blue-500">
                        Aceptar
                    </Button>
                </div>
            </div>

            <div v-else class="p-8 text-center">
                <!-- Icono -->
                <div
                    class="w-20 h-20 mx-auto flex items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/40 shadow-inner mb-4">
                    <svg class="w-12 h-12 text-yellow-500 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <!-- Título -->
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-3">
                    Verificación Requerida
                </h2>
                <!-- Texto -->
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                    Esta persona <span class="font-semibold text-gray-800 dark:text-gray-100">no está habilitada para
                        recibir pagos de bonos</span>.
                    Por favor, <span class="font-semibold text-gray-800 dark:text-gray-100">verifique que cumple todos
                        los requisitos antes de presionar continuar</span>.
                </p>
                <!-- Footer -->
                <div class="px-0 pt-6 border-t border-gray-100 dark:border-gray-700/50 flex justify-end gap-3">
                    <Button @click="$emit('close')" class="text-slate-700 bg-white hover:bg-slate-100">
                        Cancelar
                    </Button>
                    <Button @click="$emit('continuar', registradoId)" class="text-white bg-blue-600 hover:bg-blue-500">
                        Continuar
                    </Button>
                </div>
            </div>

        </div>
    </div>
</template>
