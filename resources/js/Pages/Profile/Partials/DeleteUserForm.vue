<script setup>
import DangerButton from '@/components/DangerButton.vue';
//import Modal from '@/components/Modal.vue';
import { ref } from 'vue';

const modalDelete = ref(false);

const openModal = () =>{
    modalDelete.value = true;
}

const closeModal = ()=>{
    modalDelete.value = false;
}

</script>

<template>
    <!-- <Modal v-show="modalDelete" @close="closeModal"/> -->
    <section class="space-y-0">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Eliminar cuenta
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Una vez eliminada su cuenta, todos sus recursos y datos
                se eliminarán de forma permanente. Antes de eliminar su cuenta, por favor
                descargue cualquier dato o información que desee conservar.
            </p>
        </header>
        <DangerButton @click="openModal()">Eliminar cuenta</DangerButton>
    </section>
</template>
