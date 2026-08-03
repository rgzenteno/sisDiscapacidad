<!-- @/components/AppLayout.vue -->
<script setup>
import Header from '@/components/Header.vue';
import Mensajes from '@/components/Mensajes.vue';
import PreloaderOverlay from '@/components/PreloaderOverlay.vue';
import Sidebar from '@/components/Sidebar.vue';
import { Head } from '@inertiajs/vue3';
import { useColorSistema } from '@/composables/useColorSistema';

const { estiloBrand } = useColorSistema();

defineProps({
    title: {
        type: String,
        default: 'UMADIS'
    },
    mensajes: {
        type: Array,
        default: () => []
    },
    estilo: {
        type: String,
        default: ''
    }
});
const emit = defineEmits(['cerrarMensaje']);
</script>

<template>
    <!-- ============================================================================ -->
    <!-- HEAD Y CONTENEDOR PRINCIPAL -->
    <!-- ============================================================================ -->

    <Head title="UMADIS" />
    <div class="flex h-screen -ml-1 bg-gray-200 font-roboto" :style="estiloBrand">
        <!-- Sidebar de navegación -->
        <Sidebar />

        <!-- Contenedor principal -->
        <div class="flex-1 flex flex-col overflow-hidden" :class="estilo">

            <!-- ============================================================================ -->
            <!-- SISTEMA DE MENSAJES -->
            <!-- ============================================================================ -->
            <Mensajes v-for="m in mensajes" :key="m.id" :id="m.id" :tipo="m.tipo" :contenido="m.contenido"
                @close="emit('cerrarMensaje', m.id)" />

            <!-- Header -->
            <Header class="mb-0" />

            <!-- Contenido de cada vista -->
            <slot />
        </div>

        <!-- Overlay de carga global (Preloader.show/setMessage/hide) -->
        <PreloaderOverlay />
    </div>
</template>
