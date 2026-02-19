<template>
    <nav v-if="label1" class="flex pl-1 mr-3" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 rtl:space-x-reverse">
            <li class="inline-flex items-center">
                <a @click="handleNavigation(label1)"
                    class="cursor-pointer inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-black dark:hover:text-white">
                    {{ label1 }}
                </a>
            </li>
            <li v-if="label2">
                <div class="flex items-center">
                    <svg class="stroke-current mx-1" width="17" height="16" viewBox="0 0 17 16" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="currentColor" stroke-width="1.2"
                            stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <a @click="handleNavigation(label2)"
                        class="cursor-pointer text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-900 dark:hover:text-white">{{
                            label2 }}</a>
                </div>
            </li>
            <li v-if="label3" aria-current="page">
                <div class="flex items-center">
                    <svg class="stroke-current mx-1" width="17" height="16" viewBox="0 0 17 16" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="currentColor" stroke-width="1.2"
                            stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-700">{{ label3 }}</span>
                </div>
            </li>
        </ol>
    </nav>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
    label1: String,
    label2: String,
    label3: String,
});

const goBack = () => {
    window.history.back();
};

const handleNavigation = (label) => {
    // Si es label1, siempre va al dashboard
    if (label === props.label1) {
        router.visit('/dashboard');
        return;
    }

    switch (label) {
        case 'Carnet':
            router.visit('/carnet');
            break;
        case 'Usuario':
            router.visit('/usuario/index');
            break;
        case 'Pagar':
            router.visit('/pago/index');
            break;
        case 'Habilitaciones':
            router.visit('/habilitado/index');
            break;
        case 'Tutor':
            router.visit('/tutor/index');
            break;
        case 'Beneficiarios':
            router.visit('/persona/index');
            break;
        case 'Gestiones':
            router.visit('/gestion/index');
            break;
        case 'Tutorados':
            router.visit('/tutorados');
            break;
        case 'Notificaciones':
            router.visit('/notificaciones/index');
            break;
        default:
            goBack();
    }
};
</script>
