<script setup>
import { onMounted, onUnmounted } from 'vue';
import PrimaryButton from './PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';
import { can } from '@/lib/can.ts';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
});

const emit = defineEmits(['close']);

const getTutor = () => {
    return props.tutor.filter((t) => t.id_tutor === props.data);
};

const openWhatsApp = (whatsapp) => {
    const phoneNumber = whatsapp;
    const message = encodeURIComponent('Hola, tengo una consulta');
    const url = `https://wa.me/${phoneNumber}?text=${message}`;
    window.open(url, '_blank');
};

const openEmail = (email) => {
    const recipient = email;
    const subject = encodeURIComponent('Consulta');
    const body = encodeURIComponent('Hola, tengo una consulta');
    const url = `mailto:${recipient}?subject=${subject}&body=${body}`;
    window.open(url, '_blank');
};
const openGoogleMaps = (direccion) => {
    const address = encodeURIComponent(direccion);
    const url = `https://www.google.com/maps/search/?api=1&query=${address}`;
    window.open(url, '_blank');
};

const getCarnetUrl = (ruta, id) => {
    return route(ruta, window.btoa(id));
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div
        class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-50 px-4 py-6 overflow-y-auto backdrop-blur-sm">
        <div
            class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 transform transition-all duration-300">
            <!-- Modal Header -->
            <div
                class="grid grid-cols-[1fr_auto] gap-6 px-6 py-3 border-b border-gray-100 bg-gray-50 dark:bg-gray-700/50 rounded-t-3xl">
                <!-- Contenido principal -->
                <div class="min-w-0">
                    <!-- Fila 1: Avatar/ícono y título -->
                    <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                        <!-- Avatar -->
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Título y subtítulo -->
                        <div class="min-w-0">
                            <h2 class="text-2xl font-semibold text-slate-800 dark:text-gray-100 truncate">
                                Detalles del Tutor
                            </h2>
                            <p class="text-sm text-slate-500 truncate">
                                Información básica del tutor
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex items-start gap-3 flex-shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Personal Information -->
                    <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-lg">
                        <h3
                            class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center space-x-2">
                            <svg class="w-6 h-6 text-blue-600 dark:text-white" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path fill-rule="evenodd"
                                    d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span>Información Personal</span>
                        </h3>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2 p-2 bg-white dark:bg-gray-800 rounded-lg text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium min-w-[4.5rem]">Nombre:</span>
                                <span class="text-gray-600 dark:text-gray-400 capitalize truncate">{{
                                    props.data.tutor?.nombre_tutor }}</span>
                            </div>
                            <div class="flex items-center space-x-2 p-2 bg-white dark:bg-gray-800 rounded-lg text-sm">
                                <span
                                    class="text-gray-700 dark:text-gray-300 font-medium min-w-[4.5rem]">Apellidos:</span>
                                <span class="text-gray-600 dark:text-gray-400 capitalize truncate">{{
                                    props.data.tutor?.apellido_tutor }}</span>
                            </div>
                            <div class="flex items-center space-x-2 p-2 bg-white dark:bg-gray-800 rounded-lg text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-medium min-w-[4.5rem]">C.I.:</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ props.data.tutor?.ci_tutor }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-lg">
                        <h3
                            class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center space-x-2">
                            <svg class="w-6 h-6 text-blue-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z" />
                            </svg>
                            <span>Información de Contacto</span>
                        </h3>
                        <div class="space-y-2">
                            <div class="p-2 bg-white dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span
                                            class="text-gray-700 dark:text-gray-300 font-medium whitespace-nowrap">Teléfono:</span>
                                        <span class="truncate">
                                            <span v-if="props.data.tutor?.telefono"
                                                class="text-gray-600 dark:text-gray-400">{{ props.data.tutor?.telefono
                                                }}</span>
                                            <span v-else class="text-red-500 dark:text-red-400 italic">no
                                                disponible</span>
                                        </span>
                                    </div>
                                    <a v-if="props.data.tutor?.telefono"
                                        @click="openWhatsApp(props.data.tutor?.telefono)"
                                        class="text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors duration-200 flex-shrink-0 ml-2">
                                        <svg class="cursor-pointer w-5 h-5 text-green-600 dark:text-white"
                                            aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="none">
                                            <path fill="currentColor" fill-rule="evenodd"
                                                d="M12 4a8 8 0 0 0-6.895 12.06l.569.718-.697 2.359 2.32-.648.379.243A8 8 0 1 0 12 4ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.96 9.96 0 0 1-5.016-1.347l-4.948 1.382 1.426-4.829-.006-.007-.033-.055A9.958 9.958 0 0 1 2 12Z"
                                                clip-rule="evenodd" />
                                            <path fill="currentColor"
                                                d="M16.735 13.492c-.038-.018-1.497-.736-1.756-.83a1.008 1.008 0 0 0-.34-.075c-.196 0-.362.098-.49.291-.146.217-.587.732-.723.886-.018.02-.042.045-.057.045-.013 0-.239-.093-.307-.123-1.564-.68-2.751-2.313-2.914-2.589-.023-.04-.024-.057-.024-.057.005-.021.058-.074.085-.101.08-.079.166-.182.249-.283l.117-.14c.121-.14.175-.25.237-.375l.033-.066a.68.68 0 0 0-.02-.64c-.034-.069-.65-1.555-.715-1.711-.158-.377-.366-.552-.655-.552-.027 0 0 0-.112.005-.137.005-.883.104-1.213.311-.35.22-.94.924-.94 2.16 0 1.112.705 2.162 1.008 2.561l.041.06c1.161 1.695 2.608 2.951 4.074 3.537 1.412.564 2.081.63 2.461.63.16 0 .288-.013.4-.024l.072-.007c.488-.043 1.56-.599 1.804-1.276.192-.534.243-1.117.115-1.329-.088-.144-.239-.216-.43-.308Z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="p-2 bg-white dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span
                                            class="text-gray-700 dark:text-gray-300 font-medium whitespace-nowrap">Email:</span>
                                        <span class="truncate">
                                            <span v-if="props.data.tutor?.email"
                                                class="text-gray-600 dark:text-gray-400">{{ props.data.tutor?.email
                                                }}</span>
                                            <span v-else class="text-red-500 dark:text-red-400 italic">no
                                                disponible</span>
                                        </span>
                                    </div>
                                    <a v-if="props.data.tutor?.email" @click="openEmail(props.data.tutor?.email)"
                                        class="text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors duration-200 flex-shrink-0 ml-2">
                                        <svg class="cursor-pointer w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 6.223-8-6.222V6h16zM4 18V9.044l7.386 5.745a.994.994 0 0 0 1.228 0L20 9.044 20.002 18H4z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <!-- Address section with button -->
                            <div class="p-2 bg-white dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-2 min-w-0 flex-1">
                                        <span
                                            class="text-gray-700 dark:text-gray-300 font-medium whitespace-nowrap">Dirección:</span>
                                        <span class="truncate">
                                            <span v-if="props.data.tutor?.direccion"
                                                class="text-gray-600 dark:text-gray-400 capitalize">{{
                                                props.data.tutor?.direccion }}</span>
                                            <span v-else class="text-red-500 dark:text-red-400 italic">No
                                                disponible</span>
                                        </span>
                                    </div>
                                    <a v-if="props.data.tutor?.direccion"
                                        @click="openGoogleMaps(props.data.tutor?.direccion)"
                                        class="text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors duration-200 flex-shrink-0 ml-2"
                                        title="Abrir en Google Maps">
                                        <svg class="cursor-pointer w-5 h-5" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex justify-end px-6 py-4 border-t dark:border-gray-600/50 rounded-b-3xl bg-gray-50 dark:bg-gray-700/50">
                <Link v-if="can('tutorados-tutor')" :href="getCarnetUrl('tutor.tutorados', props.data.id_tutor)"
                    class="px-4 py-2 border  text-white bg-blue-600 hover:bg-blue-500 rounded-lg text-sm font-medium transition-colors duration-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800">
                    Ver Tutorados
                </Link>
                <button @click="$emit('close')"
                    class="ms-2 px-6 py-2 border border-gray-400 text-gray-600 bg-white hover:bg-gray-50 rounded-lg text-sm font-medium transition-colors duration-200 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>
