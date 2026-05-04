<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, defineComponent, h } from 'vue';

/**
 * Componentes
 */
import Mensajes from '@/components/Mensajes.vue';

// ============================================================================
// PROPS Y COMPUTED - DATOS DE LA PÁGINA
// ============================================================================
defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// ============================================================================
// REFS - DATOS TEMPORALES
// ============================================================================
const mensajes = ref([]);
const showPassword = ref(false);
const slideActual = ref(0);

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

const submit = () => {
    form.post(route('login'), {
        onError: (errors) => {
            if (errors.email) {
                mostrarMensaje('error', 'Error', errors.email);
            } else if (errors.password) {
                mostrarMensaje('error', 'Error', errors.password);
            } else {
                mostrarMensaje('error', 'Error', 'Ha ocurrido un error. Por favor, intente nuevamente.');
            }
        },
        onSuccess: () => {
            mostrarMensaje('error', 'Error', 'Ha ocurrido un error. Por favor, intente nuevamente.');
        },
        onFinish: () => {
            form.reset('password');
        }
    });
};

const slides = [
    {
        nombre: 'Discapacidad Intelectual',
        descripcion: 'Limitaciones en el funcionamiento intelectual y conducta adaptativa',
        icono: defineComponent({
            render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
                h('circle', { cx: '12', cy: '5', r: '2' }),
                h('path', { d: 'M12 7v5l-3 5m3-5l3 5' }),
                h('path', { d: 'M8 17a5 5 0 1 0 8 0' }),
            ])
        }),
    },
    {
        nombre: 'Discapacidad Física Motora',
        descripcion: 'Limitación en el movimiento y la movilidad física',
        icono: defineComponent({
            render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
                h('path', { d: 'M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z' }),
                h('circle', { cx: '12', cy: '12', r: '3' }),
                h('line', { x1: '4', y1: '4', x2: '20', y2: '20' }),
            ])
        }),
    },
    {
        nombre: 'Discapacidad Auditiva',
        descripcion: 'Pérdida parcial o total de la audición',
        icono: defineComponent({
            render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
                h('path', { d: 'M12 4a6 6 0 016 6c0 3-2 5-2 7h-4' }),
                h('path', { d: 'M9 17a3 3 0 006 0' }),
                h('line', { x1: '3', y1: '3', x2: '21', y2: '21' }),
            ])
        }),
    },
    {
        nombre: 'Discapacidad Mental Psíquica',
        descripcion: 'Condiciones relacionadas con la salud mental y el comportamiento',
        icono: defineComponent({
            render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
                h('path', { d: 'M12 2a9 9 0 00-3 17.5V21h6v-1.5A9 9 0 0012 2z' }),
                h('path', { d: 'M9 9h.01M15 9h.01M9 13a3 3 0 006 0' }),
            ])
        }),
    },
    {
        nombre: 'Discapacidad Múltiple',
        descripcion: 'Presencia de dos o más tipos de discapacidad de forma simultánea',
        icono: defineComponent({
            render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
                h('path', { d: 'M12 21C7 17 3 13.5 3 9a5 5 0 0110 0 5 5 0 0110 0c0 4.5-4 8-9 12z' }),
            ])
        }),
    },
];

let intervalo;
onMounted(() => {
    intervalo = setInterval(() => {
        slideActual.value = (slideActual.value + 1) % slides.length;
    }, 5000);
});

onUnmounted(() => clearInterval(intervalo));
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />

    <div class="flex font-roboto">

        <!-- Contenedor principal -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- ============================================================================ -->
            <!-- SISTEMA DE MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                @close="cerrarMensaje" />

            <div class="bg-[#0F2956] flex items-center justify-center relative overflow-hidden px-2"
                style="height: 100dvh;">

                <!-- Círculos de fondo: tamaño reducido en mobile con clases responsive -->
                <div>
                    <!-- Superior izquierda -->
                    <div
                        class="absolute top-0 left-0 w-48 h-48 sm:w-96 sm:h-96 bg-[#285FC6]/20 rounded-full transform -translate-x-16 sm:-translate-x-32 -translate-y-16 sm:-translate-y-32 opacity-60 blur-sm">
                    </div>
                    <div
                        class="absolute top-10 left-10 w-36 h-36 sm:w-72 sm:h-72 bg-[#7D838E]/15 rounded-full transform -translate-x-10 sm:-translate-x-24 -translate-y-8 sm:-translate-y-16 opacity-50 blur-sm">
                    </div>
                    <div
                        class="absolute top-20 -left-4 w-28 h-28 sm:w-52 sm:h-52 bg-[#285FC6]/25 rounded-full opacity-40 blur-sm">
                    </div>
                    <div
                        class="absolute top-40 left-24 w-20 h-20 sm:w-36 sm:h-36 bg-[#AAAAAE]/20 rounded-full opacity-35 blur-sm">
                    </div>
                    <div
                        class="absolute top-56 left-6 w-16 h-16 sm:w-28 sm:h-28 bg-[#285FC6]/30 rounded-full opacity-30 blur-sm">
                    </div>
                    <!-- Inferior derecha -->
                    <div
                        class="absolute bottom-0 right-0 w-44 h-44 sm:w-80 sm:h-80 bg-[#7D838E]/25 rounded-full transform translate-x-12 sm:translate-x-24 translate-y-12 sm:translate-y-24 opacity-40 blur-sm">
                    </div>
                    <div
                        class="absolute bottom-10 right-16 w-36 h-36 sm:w-64 sm:h-64 bg-[#285FC6]/20 rounded-full transform translate-x-8 sm:translate-x-16 translate-y-6 sm:translate-y-12 opacity-55 blur-sm">
                    </div>
                    <div
                        class="absolute bottom-4 -right-3 w-28 h-28 sm:w-48 sm:h-48 bg-[#AAAAAE]/30 rounded-full opacity-35 blur-sm">
                    </div>
                    <div
                        class="absolute bottom-32 right-8 w-18 h-18 sm:w-32 sm:h-32 bg-[#B9B8BD]/25 rounded-full opacity-45 blur-sm">
                    </div>
                </div>

                <!-- Card principal del login -->
                <div
                    class="backdrop-blur-sm z-20 rounded-3xl shadow-2xl border border-[#285FC6]/20 w-full sm:max-w-none sm:w-auto">
                    <div class="flex rounded-3xl overflow-hidden">

                        <!-- Panel izquierdo — solo visible en sm+ -->
                        <div class="hidden sm:block sm:flex-1 bg-[#ffffff]/20 bg-gradient-to-br pl-4 py-4 sm:p-12 pt-0">
                            <div class="flex flex-col items-center space-y-4 justify-center relative overflow-hidden">

                                <!-- Imagen -->
                                <div
                                    class="w-24 h-24 bg-black/15 rounded-full flex items-center justify-center border border-black/30">
                                    <img src="/images/ancianos.png" alt="Personas con discapacidad" width="60">
                                </div>

                                <!-- Título y descripción -->
                                <div class="text-center max-w-xs">
                                    <h2 class="text-lg font-semibold text-black leading-tight mb-3">
                                        Sistema de Gestión de Beneficiarios
                                    </h2>
                                    <p class="text-black/80 text-sm leading-relaxed">
                                        Administra los registros de personas con discapacidad del municipio de Sacaba,
                                        sus
                                        carnets, pagos y seguimiento de estado.
                                    </p>
                                </div>

                                <!-- Slider de tipos de discapacidad -->
                                <div class="w-full text-center">
                                    <p class="text-black/60 text-sm mb-4">Atención integral a todos los tipos de
                                        discapacidad</p>
                                    <div class="flex flex-col items-center gap-3">
                                        <transition name="fade" mode="out-in">
                                            <div :key="slideActual" class="flex flex-col items-center gap-2">
                                                <div
                                                    class="w-16 h-16 bg-black/10 rounded-full flex items-center justify-center border border-black/15">
                                                    <component :is="slides[slideActual].icono"
                                                        class="w-8 h-8 text-black/60" />
                                                </div>
                                                <p class="text-black/70 text-sm font-medium">{{
                                                    slides[slideActual].nombre }}</p>
                                                <p class="text-black/50 text-xs max-w-[190px]">{{
                                                    slides[slideActual].descripcion }}</p>
                                            </div>
                                        </transition>
                                        <!-- Dots -->
                                        <div class="flex gap-1.5 mt-1">
                                            <span v-for="(_, i) in slides" :key="i"
                                                class="w-1.5 h-1.5 rounded-full transition-all duration-300"
                                                :class="i === slideActual ? 'bg-black/50 w-3' : 'bg-black/20'">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel derecho — formulario -->
                        <div
                            class="flex-1 p-5 sm:p-8 flex flex-col justify-center relative bg-[#ffffff]/5 sm:bg-transparent">
                            <div class="flex justify-center text-center mb-4 sm:mb-4">
                                <span class="mx-2 text-3xl sm:text-5xl font-semibold text-white"
                                    style="text-shadow: -5px 5px 4px rgb(158 158 158 / 79%);">UMADIS</span>
                            </div>

                            <div class="text-center sm:mb-4">
                                <p class="text-[#AAAAAE] text-sm">Por favor, inicia sesión para acceder al sistema de
                                    gestión</p>
                            </div>
<!--
                            <div v-if="status"
                                class="mb-4 p-3 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400 text-sm text-center">
                                {{ status }}
                            </div> -->

                            <form @submit.prevent="submit">
                                <div class="space-y-7 py-4 sm:space-y-4 px-5 sm:px-6 sm:py-0">
                                    <!-- Usuario -->
                                    <div class="space-y-2 sm:space-y-7">
                                        <div>
                                            <label class="block text-[#B9B8BD] text-sm font-medium mb-2" for="email">
                                                Usuario
                                            </label>
                                            <input v-model="form.email" id="email" type="text" required autofocus
                                                autocomplete="off"
                                                class="w-full px-4 py-2 bg-[#0F2956]/80 border border-[#7D838E] rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                                placeholder="Ingrese el nombre de usuario" />
                                        </div>
                                        <!-- Contraseña -->
                                        <div>
                                            <label class="block text-[#B9B8BD] text-sm font-medium mb-2" for="password">
                                                Contraseña
                                            </label>
                                            <div class="relative">
                                                <input v-model="form.password" id="password"
                                                    :type="showPassword ? 'text' : 'password'" required
                                                    autocomplete="current-password"
                                                    class="w-full px-4 py-2 bg-[#0F2956]/80 border border-[#7D838E] rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200 pr-12"
                                                    placeholder="••••••••" />
                                                <button type="button" @click="showPassword = !showPassword"
                                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-[#7D838E] hover:text-[#B9B8BD] transition duration-200">
                                                    <svg v-if="showPassword" class="w-5 h-5" aria-hidden="true"
                                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        fill="none" viewBox="0 0 24 24">
                                                        <path stroke="currentColor" stroke-linecap="round"
                                                            stroke-linejoin="round" stroke-width="2"
                                                            d="M3.933 13.909A4.357 4.357 0 0 1 3 12c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 21 12c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M5 19 19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                                        </path>
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remember me -->
                                    <div class="flex justify-between text-sm">
                                        <label class="text-[#B9B8BD] cursor-pointer">
                                            <input v-model="form.remember" type="checkbox"
                                                class="w-4 h-4 text-[#285FC6] bg-[#0F2956] border-[#7D838E] rounded focus:ring-[#285FC6] focus:ring-2" />
                                            <span class="ml-2">Recuérdame</span>
                                        </label>
                                        <Link v-if="canResetPassword" :href="route('password.request')"
                                            class="text-[#285FC6] hover:text-[#AAAAAE] transition duration-200">
                                        </Link>
                                    </div>
                                    <!-- Botón submit -->
                                    <button type="submit" :disabled="form.processing"
                                        class="mx-auto block w-56 bg-gradient-to-r from-[#285FC6] to-[#285FC6]/80 hover:from-[#285FC6]/90 hover:to-[#285FC6] text-white font-semibold py-1.5 px-4 rounded-full transition duration-300 transform hover:scale-105 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border border-[#285FC6]/50">
                                        <span v-if="!form.processing">Iniciar Sesión</span>
                                        <span v-else class="flex items-center justify-center">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            Iniciando sesión...
                                        </span>
                                    </button>
                                </div>
                                <div class="hidden sm:block">
                                    <!-- Divider -->
                                    <div class="relative my-5 sm:my-6 sm:mx-6">
                                        <div class="absolute inset-0 flex items-center">
                                            <div class="w-full border-t border-[#7D838E]/40"></div>
                                        </div>
                                        <div class="relative flex justify-center text-sm">
                                            <span class="px-4 bg-[#0F2956] text-[#7D838E]">Sistema Municipal</span>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="text-center space-y-2">
                                        <p class="text-[#7D838E] text-xs">
                                            Unidad Municipal de Atención a Personas con Discapacidad
                                        </p>
                                        <p class="text-[#7D838E]/70 text-xs">
                                            © {{ new Date().getFullYear() }} UMADIS - Gobierno Autónomo Municipal de
                                            Sacaba
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input:focus {
    box-shadow: 0 0 0 3px rgba(40, 95, 198, 0.1);
}

button:hover:not(:disabled) {
    box-shadow: 0 10px 25px rgba(40, 95, 198, 0.2);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
