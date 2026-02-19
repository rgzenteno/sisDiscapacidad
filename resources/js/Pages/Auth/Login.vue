<script setup>
import Mensajes from '@/components/Mensajes.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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

const errorMessage = ref(null);

const submit = () => {
    form.post(route('login'), {
        onError: (errors) => {
            // Manejar errores del campo email
            if (errors.email) {
                // Puede ser error de validación (formato) o credenciales incorrectas
                mostrarMensaje('error', 'Error', errors.email);
            } else if (errors.password) {
                // Error de validación del campo password
                mostrarMensaje('error', 'Error', errors.password);
            } else {
                // Fallback para cualquier otro error
                mostrarMensaje('error', 'Error', 'Ha ocurrido un error. Por favor, intente nuevamente.');
            }
        },
        onSuccess: (response) => {
            mostrarMensaje('error', 'Error', 'Ha ocurrido un error. Por favor, intente nuevamente.');
        },
        onFinish: () => {
            // Solo resetear la contraseña independientemente del resultado
            form.reset('password');
        }
    });
};

const showPassword = ref(false);
</script>

<template>

    <Head title="UMADIS" />
    <!-- Mensajes de notificación -->
    <div class="fixed top-4 right-4 flex flex-col gap-2 z-50">
        <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
            @close="cerrarMensaje" />
    </div>
    <div class="min-h-screen bg-[#0F2956] flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Formas orgánicas de fondo con la paleta oficial -->
        <!-- Área superior izquierda -->
        <div
            class="absolute top-0 left-0 w-96 h-96 bg-[#285FC6]/20 rounded-full transform -translate-x-32 -translate-y-32 opacity-60 blur-sm">
        </div>
        <div
            class="absolute top-16 left-20 w-72 h-72 bg-[#7D838E]/15 rounded-full transform -translate-x-24 -translate-y-16 opacity-50 blur-sm">
        </div>
        <div class="absolute top-32 -left-8 w-52 h-52 bg-[#285FC6]/25 rounded-full opacity-40 blur-sm"></div>
        <div class="absolute top-64 left-48 w-36 h-36 bg-[#AAAAAE]/20 rounded-full opacity-35 blur-sm"></div>
        <div class="absolute top-96 left-12 w-28 h-28 bg-[#285FC6]/30 rounded-full opacity-30 blur-sm"></div>

        <!-- Área inferior derecha -->
        <div
            class="absolute bottom-0 right-0 w-80 h-80 bg-[#7D838E]/25 rounded-full transform translate-x-24 translate-y-24 opacity-40 blur-sm">
        </div>
        <div
            class="absolute bottom-20 right-32 w-64 h-64 bg-[#285FC6]/20 rounded-full transform translate-x-16 translate-y-12 opacity-55 blur-sm">
        </div>
        <div class="absolute bottom-8 -right-6 w-48 h-48 bg-[#AAAAAE]/30 rounded-full opacity-35 blur-sm"></div>
        <div class="absolute bottom-56 right-16 w-32 h-32 bg-[#B9B8BD]/25 rounded-full opacity-45 blur-sm"></div>

        <!-- Contenedor principal del login -->
        <div class=" backdrop-blur-xl z-20 rounded-3xl shadow-2xl border border-[#285FC6]/20">
            <div class="flex rounded-3xl overflow-hidden max-w-5xl w-full min-h-[500px]">
                <!-- Panel izquierdo - Área de bienvenida -->
                <div
                    class="flex-1 bg-[#ffffff]/20 bg-gradient-to-br pl-4 p-12 pt-0 flex flex-col items-center justify-center relative overflow-hidden">

                    <!-- Icono principal de accesibilidad estilizado -->
                    <div class="mb-1 relative">
                        <div
                            class="w-24 h-24 bg-black/15 rounded-full flex items-center justify-center backdrop-blur-sm border border-black/30 shadow-2xl">
                            <img src="/images/ancianos.png" alt="img silla de ruedas" width="60">
                        </div>
                        <div class="absolute inset-0 w-24 h-24 border border-black/20 rounded-full animate-pulse"></div>
                    </div>

                    <!-- Mensaje inspirador principal -->
                    <div class="text-center max-w-xs space-y-4 z-10">
                        <h2 class="text-xl font-semibold text-black mb-3 leading-tight">
                            "Construyendo Puentes hacia la Inclusión"
                        </h2>

                        <p class="text-black/85 text-sm leading-relaxed font-medium">
                            Tu trabajo transforma vidas. Cada gestión que realizas construye una sociedad más justa e
                            inclusiva.
                        </p>

                        <div class="bg-black/10 backdrop-blur-sm rounded-xl p-3 mt-4 border border-black/20">
                            <p class="text-black/90 text-xs font-medium">
                                💙 "Juntos hacemos la diferencia. Tu dedicación es el motor que impulsa la dignidad de
                                las personas con discapacidad."
                            </p>
                        </div>
                    </div>

                    <!-- Indicadores de valores institucionales -->
                    <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex space-x-4 text-black/60">
                        <div class="text-center">
                            <div class="w-6 h-6 bg-black/20 rounded-full flex items-center justify-center mb-1 mx-auto">
                                <span class="text-xs">🤝</span>
                            </div>
                            <p class="text-xs font-medium">Apoyo</p>
                        </div>
                        <div class="text-center">
                            <div class="w-6 h-6 bg-black/20 rounded-full flex items-center justify-center mb-1 mx-auto">
                                <span class="text-xs">⚖️</span>
                            </div>
                            <p class="text-xs font-medium">Derechos</p>
                        </div>
                        <div class="text-center">
                            <div class="w-6 h-6 bg-black/20 rounded-full flex items-center justify-center mb-1 mx-auto">
                                <span class="text-xs">🌟</span>
                            </div>
                            <p class="text-xs font-medium">Dignidad</p>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho - Formulario de login -->
                <div class="flex-1 p-8 flex flex-col justify-center relative">
                    <!-- Icono de usuario -->
                    <div class="flex justify-center mb-4">
                        <span class="mx-2 text-5xl font-semibold text-white dark:text-white"
                            style="text-shadow: -5px 5px 4px rgb(158 158 158 / 79%);">UMADIS</span>
                    </div>

                    <div class="text-center mb-4">

                        <p class="text-[#AAAAAE]">Por favor, inicia sesión para acceder al sistema de gestión</p>
                    </div>

                    <!-- Mensaje de estado -->
                    <div v-if="status"
                        class="mb-4 p-3 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400 text-sm text-center">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6 px-4">
                        <div class="px-4 space-y-3">
                            <!-- Email -->
                            <div>
                                <label class="block text-[#B9B8BD] text-sm font-medium mb-2" for="email">
                                    Usuario
                                </label>
                                <input v-model="form.email" id="email" type="text" required autofocus autocomplete="off"
                                    class="w-full px-4 py-2 bg-[#0F2956]/80 border border-[#7D838E] rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                    placeholder="Ingrese el nombre de usuario" />
                            </div>
                            <!-- Password -->
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
                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                            viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3.933 13.909A4.357 4.357 0 0 1 3 12c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 21 12c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M5 19 19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>

                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Remember me y Forgot password -->
                            <div class="flex items-center justify-between text-sm">
                                <label class="flex items-center text-[#B9B8BD] cursor-pointer">
                                    <input v-model="form.remember" type="checkbox"
                                        class="w-4 h-4 text-[#285FC6] bg-[#0F2956] border-[#7D838E] rounded focus:ring-[#285FC6] focus:ring-2" />
                                    <span class="ml-2">Recuérdame</span>
                                </label>
                                <Link v-if="canResetPassword" :href="route('password.request')"
                                    class="text-[#285FC6] hover:text-[#AAAAAE] transition duration-200">

                                </Link>
                            </div>
                            <!-- Submit button -->
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
                        <!-- Divider -->
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-[#7D838E]/40"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-[#0F2956] text-[#7D838E]">Sistema Municipal</span>
                            </div>
                        </div>

                        <!-- Footer info -->
                        <div class="text-center space-y-2">
                            <p class="text-[#7D838E] text-xs">
                                Unidad Municipal de Atención a Personas con Discapacidad
                            </p>
                            <p class="text-[#7D838E]/70 text-xs">
                                © 2025 UMADIS - Gobierno Autono Municipal de Sacaba
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animaciones personalizadas */
.delay-75 {
    animation-delay: 0.075s;
}

.delay-150 {
    animation-delay: 0.15s;
}

/* Responsividad */
@media (max-width: 768px) {
    .max-w-5xl {
        max-width: 95%;
        margin: 1rem;
    }

    .flex {
        flex-direction: column;
    }

    .flex-1:first-child {
        min-height: 300px;
        padding: 2rem 1rem;
    }

    .flex-1:last-child {
        padding: 2rem 1rem;
    }

    .text-6xl {
        font-size: 3rem;
    }

    .text-3xl {
        font-size: 1.75rem;
    }

    .text-2xl {
        font-size: 1.5rem;
    }

    .text-lg {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .bg-\[0F2956\]\/95 {
        margin: 0.5rem;
        border-radius: 1rem;
    }

    .space-y-6>*+* {
        margin-top: 1rem;
    }

    .px-4 {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .p-8 {
        padding: 1.5rem;
    }

    .p-12 {
        padding: 2rem;
    }

    .text-6xl {
        font-size: 2.5rem;
    }
}

/* Estados de hover y focus mejorados */
input:focus {
    box-shadow: 0 0 0 3px rgba(40, 95, 198, 0.1);
}

button:hover:not(:disabled) {
    box-shadow: 0 10px 25px rgba(40, 95, 198, 0.2);
}

/* Animación suave para elementos */
* {
    transition: all 0.2s ease-in-out;
}
</style>
