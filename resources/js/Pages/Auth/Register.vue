<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import Seccion from '@/components/Seccion.vue';
import Rutas from '@/components/Rutas.vue';
import Dropdown from '@/components/Dropdown.vue';
import { initFlowbite } from 'flowbite'
import {
    Head,
    Link,
    useForm
} from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

// AGREGAR PROPS
const props = defineProps({
    roles: {
        type: Array,
        default: () => []
    }
});

// Inicializar después de que el DOM esté listo
onMounted(() => {
    initFlowbite()
})

const form = useForm({
    nombre: '',
    apellido: '',
    rol: '',
    cargo: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    console.log('Enviando datos:', form.data()); // Debug

    form.post(route('register'), {
        onSuccess: () => {
            console.log('Éxito');
        },
        onError: (errors) => {
            console.log('Errores:', errors);
        },
        onFinish: () => {
            console.log('Finalizado');
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<style scoped>
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

/* Responsividad */
@media (max-width: 768px) {
    .text-\[20rem\] {
        font-size: 8rem;
    }

    .max-w-2xl {
        max-width: 95%;
        margin: 1rem;
    }
}

@media (max-width: 480px) {
    .text-\[20rem\] {
        font-size: 5rem;
    }

    .grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .p-8 {
        padding: 1.5rem;
    }
}
</style>
<template>
    <main
        class="flex-1 overflow-hidden mt-0 ml-0 min-h-screen bg-[#0F2956] flex items-center justify-center p-4 relative">
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

        <!-- Letras grandes UMADIS de fondo -->
        <div class="absolute top-0 right-0 flex items-start justify-end pointer-events-none overflow-hidden p-8">
            <span class="mx-2 text-7xl font-semibold text-white dark:text-white blur-sm"
                style="text-shadow: -5px 5px 4px rgb(158 158 158 / 79%);">UMADIS</span>
        </div>

        <!-- Contenedor principal del formulario -->
        <div
            class="backdrop-blur-xl bg-white/10 z-20 rounded-3xl shadow-2xl border border-[#285FC6]/20 w-full max-w-2xl">
            <div class="p-8">
                <!-- Título del formulario -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-white/90 mb-2">Registro al Sistema</h2>
                    <p class="text-[#B9B8BD] text-sm">Agrega un nuevo registro de usuario</p>
                </div>

                <!-- Formulario -->
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Nombre <span class="text-red-400">*</span>
                            </label>
                            <input type="text" v-model="form.nombre" required autofocus
                                class="capitalize placeholder:normal-case w-full px-4 py-2 bg-[#0F2956]/60 border border-[#7D838E]/50 rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                placeholder="Ingrese el nombre" />
                            <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-400">{{ form.errors.nombre }}</p>
                        </div>

                        <!-- Apellido -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Apellido <span class="text-red-400">*</span>
                            </label>
                            <input type="text" v-model="form.apellido" required
                                class="capitalize placeholder:normal-case w-full px-4 py-2 bg-[#0F2956]/60 border border-[#7D838E]/50 rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                placeholder="Ingrese el apellido" />
                            <p v-if="form.errors.apellido" class="mt-1 text-sm text-red-400">{{ form.errors.apellido }}
                            </p>
                        </div>

                        <!-- Dropdown de Roles -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Rol <span class="text-red-400">*</span>
                            </label>

                            <Dropdown align="left" width="60">
                                <template #trigger>
                                    <button type="button"
                                        class="inline-flex items-center justify-between w-full gap-3 px-4 py-2 text-sm font-medium rounded-lg transition-all focus:ring-2 focus:outline-none bg-[#0F2956]/60 border border-[#7D838E]/50 text-white placeholder-[#7D838E] focus:ring-[#285FC6] focus:border-[#285FC6] cursor-pointer">
                                        <span class="truncate">
                                            {{roles.find(r => r.id === form.rol)?.name || 'Seleccione un rol'}}
                                        </span>
                                        <svg class="w-2.5 h-2.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 10 6">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 4 4 4-4" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div
                                        class="w-full max-w-sm bg-[#0F2956]/95 backdrop-blur-sm rounded-lg border border-[#285FC6]/30 shadow-2xl">
                                        <!-- Lista scrolleable con altura limitada -->
                                        <div class="max-h-48 overflow-y-auto">
                                            <ul class="py-1">
                                                <!-- Mensaje cuando no hay opciones -->
                                                <li v-if="!roles || roles.length === 0" class="px-4 py-3">
                                                    <div class="flex items-center gap-3 text-[#7D838E]">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                        <span class="text-sm">No hay roles disponibles</span>
                                                    </div>
                                                </li>

                                                <!-- Opciones del select -->
                                                <li v-for="rol in roles" :key="rol.id">
                                                    <button type="button" @click="form.rol = rol.id"
                                                        class="flex items-center justify-between w-full px-4 py-2 text-sm text-left transition-colors duration-150"
                                                        :class="form.rol === rol.id
                                                            ? 'bg-[#285FC6]/50 text-white font-medium'
                                                            : 'text-white/90 hover:bg-[#285FC6]/30'">
                                                        <span>{{ rol.name }}</span>
                                                        <svg v-if="form.rol === rol.id" class="w-4 h-4 text-white"
                                                            fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </template>
                            </Dropdown>

                            <p v-if="form.errors.rol" class="mt-1 text-sm text-red-400">{{ form.errors.rol }}</p>
                        </div>

                        <!-- Cargo -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Cargo <span class="text-red-400">*</span>
                            </label>
                            <input type="text" v-model="form.cargo" required
                                class="uppercase placeholder:normal-case w-full px-4 py-2 bg-[#0F2956]/60 border border-[#7D838E]/50 rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                placeholder="Ingrese el cargo" />
                            <p v-if="form.errors.cargo" class="mt-1 text-sm text-red-400">{{ form.errors.cargo }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Usuario <span class="text-red-400">*</span>
                            </label>
                            <input type="text" v-model="form.email" required
                                class="w-full px-4 py-2 bg-[#0F2956]/60 border border-[#7D838E]/50 rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                placeholder="Ingrese el nombre de usuario" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</p>
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Contraseña <span class="text-red-400">*</span>
                            </label>
                            <input type="password" v-model="form.password" required
                                class="w-full px-4 py-2 bg-[#0F2956]/60 border border-[#7D838E]/50 rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                placeholder="••••••••" />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">{{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Confirmar contraseña -->
                        <div>
                            <label class="block text-white/90 text-sm font-medium mb-2">
                                Confirmar contraseña <span class="text-red-400">*</span>
                            </label>
                            <input type="password" v-model="form.password_confirmation" required
                                class="w-full px-4 py-2 bg-[#0F2956]/60 border border-[#7D838E]/50 rounded-lg text-white placeholder-[#7D838E] focus:outline-none focus:ring-2 focus:ring-[#285FC6] focus:border-[#285FC6] transition duration-200"
                                placeholder="••••••••" />
                            <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-400">{{
                                form.errors.password_confirmation
                                }}</p>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="mt-8 flex items-center justify-end gap-3">
                        <Link :href="route('usuario.index')">
                            <button type="button"
                                class="px-6 py-2 bg-[#7D838E]/30 hover:bg-[#7D838E]/50 text-white font-semibold rounded-lg transition duration-300 border border-[#7D838E]/50">
                                Iniciar sesión
                            </button>
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-gradient-to-r from-[#285FC6] to-[#285FC6]/80 hover:from-[#285FC6]/90 hover:to-[#285FC6] text-white font-semibold rounded-lg transition duration-300 transform hover:scale-105 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border border-[#285FC6]/50">
                            <span v-if="!form.processing">Registrar</span>
                            <span v-else class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Registrando...
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Footer info -->
                <div class="text-center mt-6 pt-6 border-t border-[#7D838E]/30">
                    <p class="text-[#B9B8BD] text-xs">
                        Unidad Municipal de Atención a Personas con Discapacidad
                    </p>
                    <p class="text-[#B9B8BD]/70 text-xs mt-1">
                        © 2025 UMADIS - Gobierno Autónomo Municipal de Sacaba
                    </p>
                </div>
            </div>
        </div>
    </main>
</template>
