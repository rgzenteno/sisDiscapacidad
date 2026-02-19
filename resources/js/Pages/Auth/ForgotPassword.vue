<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => {
            // Redirige a la URL deseada
            window.location.href = route('login'); // Cambia 'dashboard' por la ruta a la que deseas redirigir
        },
        onError: (errors) => {
            console.error('Ocurrió un error:', errors);
        },
    });
};


</script>

<template>
<Head title="Forgot Password" />
<div class="flex min-h-screen items-center justify-center bg-gray-400 dark:bg-gray-800">
    <div class="w-full max-w-md overflow-hidden rounded-lg bg-white p-10 pt-6 shadow-lg dark:bg-gray-900">
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            ¿Ha olvidado su contraseña? No se preocupe.
            Díganos su dirección de correo electrónico y le enviaremos un enlace para restablecer
            la contraseña que le permitirá elegir una nueva.
        </div>

        <!-- Success Alert -->
        <div v-if="status" class="fixed top-4 right-4 flex flex-col gap-2 w-60 sm:w-72 text-[10px] sm:text-xs z-50 transition-opacity duration-1000">
            <div class="succsess-alert cursor-default flex items-center justify-between w-full h-12 sm:h-14 rounded-lg bg-green-500 dark:bg-[#1e1e2d] px-[10px]">
                <div class="flex gap-2">
                    <div class="text-[#000000] bg-white/5 backdrop-blur-xl p-1 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-800 dark:text-gray-400">{{ status }}</p>
                    </div>
                </div>
                <button @click.prevent="cerrar" class="text-gray-600 dark:text-gray-300 hover:bg-white/5 dark:hover:bg-white/10 p-1 rounded-md transition-colors ease-linear">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autofocus autocomplete="username" />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Enviar
                </PrimaryButton>
            </div>
        </form>
    </div>
</div>
</template>
