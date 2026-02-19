<script setup>
import DangerButton from '@/components/DangerButton.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};
const closeModal = () => {
    confirmingUserDeletion.value = false;
    emit('close');
    form.clearErrors();
    form.reset();
};
const emit = defineEmits(['close']); // Emite un evento para cerrar el modal
</script>

<template>
<!-- Modal Background -->
<div  class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" aria-hidden="true">
    <!-- Modal Content -->
    <div class="relative bg-white dark:bg-gray-700 rounded-lg shadow p-6 w-full max-w-md mx-4">

        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                ¿Seguro que quieres eliminar tu cuenta?
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Una vez eliminada su cuenta, todos sus recursos y datos
                se borrarán permanentemente. Introduzca su contraseña para
                confirmar que desea eliminar definitivamente su cuenta.
            </p>

            <div class="mt-6">
                <InputLabel for="password" value="Contraseña" class="sr-only" />

                <TextInput id="password" ref="passwordInput" v-model="form.password" type="password" class="mt-1 block w-3/4" placeholder="Contraseña" @keyup.enter="deleteUser" />

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <DangerButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="deleteUser">
                    Eliminar cuenta
                </DangerButton>
            </div>
        </div>
    </div>
</div>
</template>
