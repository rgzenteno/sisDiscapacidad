<script setup>
import PrimaryButton from './PrimaryButton.vue';
const props = defineProps({
    user: Number,
    data: {
        type: Object,
        default: () => []
    },
});

console.log('ModalUser props data:', props.data);

const emit = defineEmits(['close']);

const getUser = () => {
    return props.user.filter((u) => u.id === props.user);
};

const openEmail = (email) => {
    const recipient = email;
    const subject = encodeURIComponent('Consulta Administrativa');
    const body = encodeURIComponent('Hola, tengo una consulta sobre el sistema');
    const url = `mailto:${recipient}?subject=${subject}&body=${body}`;
    window.open(url, '_blank');
};

const formatDate = (dateString) => {
    if (!dateString) return 'No verificado';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusInfo = (estado) => {
    if (estado === 1) {
        return {
            text: 'Conectado',
            class: 'bg-green-50 text-green-700 border-green-300 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
            dotClass: 'bg-green-500 dark:bg-green-400'
        };
    } else {
        return {
            text: 'Desconectado',
            class: 'bg-red-50 text-red-700 border-red-300 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
            dotClass: 'bg-red-500 dark:bg-red-400'
        };
    }
};

const getRoleIcon = (rol) => {
    const icons = {
        'admin': `<path fill-rule="evenodd" d="M17 10v1.126c.367.095.714.24 1.032.428l.796-.797 1.415 1.415-.797.796c.188.318.333.665.428 1.032H21v2h-1.126c-.095.367-.24.714-.428 1.032l.797.796-1.415 1.415-.796-.797a3.979 3.979 0 0 1-1.032.428V20h-2v-1.126a3.977 3.977 0 0 1-1.032-.428l-.796.797-1.415-1.415.797-.796A3.975 3.975 0 0 1 12.126 16H11v-2h1.126c.095-.367.24-.714.428-1.032l-.797-.796 1.415-1.415.796.797A3.977 3.977 0 0 1 15 11.126V10h2Zm.406 3.578.016.016c.354.358.574.85.578 1.392v.028a2 2 0 0 1-3.409 1.406l-.01-.012a2 2 0 0 1 2.826-2.83ZM5 8a4 4 0 1 1 8 0 4 4 0 0 1-8 0Zm4-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H7Z" clip-rule="evenodd"/>`,
        'moderator': `<path fill-rule="evenodd" d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z" clip-rule="evenodd"/>`,
        'usuario': `<path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>`
    };
    return icons[rol] || icons['usuario'];
};



</script>

<template>
<div class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-40 px-4 py-4 overflow-y-auto backdrop-blur-sm">
    <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 transform transition-all duration-300">
        <!-- Modal Header -->
        <div class="grid grid-cols-[1fr_auto] gap-6 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-br from-slate-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-t-3xl">
            <div class="min-w-0">
                <div class="grid grid-cols-[auto_1fr] gap-4 items-center">
                    <!-- Avatar -->
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg ring-2 ring-blue-100 dark:ring-blue-900 flex-shrink-0">
                        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M17 10v1.126c.367.095.714.24 1.032.428l.796-.797 1.415 1.415-.797.796c.188.318.333.665.428 1.032H21v2h-1.126c-.095.367-.24.714-.428 1.032l.797.796-1.415 1.415-.796-.797a3.979 3.979 0 0 1-1.032.428V20h-2v-1.126a3.977 3.977 0 0 1-1.032-.428l-.796.797-1.415-1.415.797-.796A3.975 3.975 0 0 1 12.126 16H11v-2h1.126c.095-.367.24-.714.428-1.032l-.797-.796 1.415-1.415.796.797A3.977 3.977 0 0 1 15 11.126V10h2Zm.406 3.578.016.016c.354.358.574.85.578 1.392v.028a2 2 0 0 1-3.409 1.406l-.01-.012a2 2 0 0 1 2.826-2.83ZM5 8a4 4 0 1 1 7.938.703 7.029 7.029 0 0 0-3.235 3.235A4 4 0 0 1 5 8Zm4.29 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h6.101A6.979 6.979 0 0 1 9 15c0-.695.101-1.366.29-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>

                    <!-- Título y subtítulo -->
                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-gray-100 truncate">
                            Detalles del Usuario
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-gray-400 truncate">
                            Información completa del perfil
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botón cerrar -->
            <div class="flex items-start gap-3 flex-shrink-0">
                <button type="button" @click="$emit('close')" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Personal Information -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/20 p-5 rounded-xl border border-blue-200 dark:border-blue-800">
                    <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-4 flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd"/>
                        </svg>
                        <span>Información Personal</span>
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm text-sm">
                            <span class="text-gray-700 dark:text-gray-300 font-semibold min-w-[5rem]">Nombre:</span>
                            <span class="text-gray-600 dark:text-gray-400 capitalize truncate">{{ props.data.nombre || 'María Elena' }}</span>
                        </div>
                        <div class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm text-sm">
                            <span class="text-gray-700 dark:text-gray-300 font-semibold min-w-[5rem]">Apellidos:</span>
                            <span class="text-gray-600 dark:text-gray-400 capitalize truncate">{{ props.data.apellido || 'Rodríguez García' }}</span>
                        </div>
                        <div class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm text-sm">
                            <span class="text-gray-700 dark:text-gray-300 font-semibold min-w-[5rem]">Firma Digital:</span>
                            <span v-if="props.data.digital_signature" class="text-green-600 dark:text-green-400 flex items-center font-medium">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Sí
                            </span>
                            <span v-else class="text-red-500 dark:text-red-400 flex items-center font-medium">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                No
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/20 p-5 rounded-xl border border-blue-200 dark:border-blue-800">
                    <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-4 flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" v-html="getRoleIcon(props.data.rol || 'admin')">
                        </svg>
                        <span>Información Profesional</span>
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm text-sm">
                            <span class="text-gray-700 dark:text-gray-300 font-semibold min-w-[5rem]">Rol:</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                {{ props.data.rol || 'admin' }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm text-sm">
                            <span class="text-gray-700 dark:text-gray-300 font-semibold min-w-[5rem]">Cargo:</span>
                            <span class="text-gray-600 dark:text-gray-400 capitalize truncate">{{ props.data.cargo || 'Administradora de Sistema' }}</span>
                        </div>
                        <div class="flex items-center space-x-2 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm text-sm">
                            <span class="text-gray-700 dark:text-gray-300 font-semibold min-w-[5rem]">Estado:</span>
                            <div :class="['inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border', getStatusInfo(props.data.estado ?? 1).class]">
                                <div :class="['w-2.5 h-2.5 rounded-full mr-2 shadow-sm', getStatusInfo(props.data.estado ?? 1).dotClass]"></div>
                                {{ getStatusInfo(props.data.estado ?? 1).text }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Account Information -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/20 p-5 rounded-xl border border-blue-200 dark:border-blue-800">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-4 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.038 5.61A2.01 2.01 0 0 0 2 6v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6c0-.12-.017-.238-.052-.343a.77.77 0 0 0-.056-.153A2.01 2.01 0 0 0 20 4H4a2.01 2.01 0 0 0-1.962 1.61ZM20 6.5 12 13 4 6.5V6h16v.5Z"/>
                    </svg>
                    <span>Información de Contacto y Cuenta</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-2 min-w-0 flex-1">
                                    <span class="text-gray-700 dark:text-gray-300 font-semibold whitespace-nowrap">Usuario:</span>
                                    <span class="truncate text-gray-600 dark:text-gray-400">{{ props.data.email || 'maria.rodriguez@ejemplo.com' }}</span>
                                </div>
                                <a @click="openEmail(props.data.email || 'maria.rodriguez@ejemplo.com')" class="text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md p-1 transition-colors duration-200 flex-shrink-0 ml-2">
                                    <svg class="cursor-pointer w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 6.223-8-6.222V6h16zM4 18V9.044l7.386 5.745a.994.994 0 0 0 1.228 0L20 9.044 20.002 18H4z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-semibold whitespace-nowrap">Verificado:</span>
                                <span v-if="props.data.email_verified_at" class="text-green-600 dark:text-green-400 flex items-center font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Sí
                                </span>
                                <span v-else class="text-red-500 dark:text-red-400 flex items-center font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    No
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-semibold whitespace-nowrap">Creado:</span>
                                <span class="text-gray-600 dark:text-gray-400 truncate">{{ formatDate(props.data.created_at || '2023-12-01T08:00:00Z') }}</span>
                            </div>
                        </div>
                        <div class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-gray-700 dark:text-gray-300 font-semibold whitespace-nowrap">Actualizado:</span>
                                <span class="text-gray-600 dark:text-gray-400 truncate">{{ formatDate(props.data.updated_at || '2024-06-10T14:20:00Z') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 rounded-b-3xl bg-gradient-to-br from-slate-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
            <button @click="$emit('close')" class="px-6 py-2.5 text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 rounded-xl text-sm font-semibold transition-all duration-200 focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/30">
                Aceptar
            </button>
        </div>
    </div>
</div>
</template>
