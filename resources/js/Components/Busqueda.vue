<script setup>
import { ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    data: Object,
    rutaBusqueda: String,
    only: String,
    name: String,
    initialValue: {
        type: String,
        default: '',
    }
});

const buscador = ref(props.initialValue);
let debounceTimeout = null;

const buscarProducto = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);

    debounceTimeout = setTimeout(() => {
        router.get(
            route(props.rutaBusqueda), { buscador: buscador.value }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: [props.only]
            }
        );
    }, 300);
};

const limpiarBusqueda = () => {
    buscador.value = '';
};

watch(buscador, () => {
    buscarProducto();
});

onMounted(() => {
    buscador.value = props.initialValue;
});

watch(() => props.initialValue, (newValue) => {
    if (newValue) {
        buscador.value = newValue;
    }
});
</script>

<template>
<div>
    <label for="table-search" class="sr-only">Search</label>
    <div class="relative py-1">
        <!-- Ícono de lupa -->
        <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
            </svg>
        </div>

        <!-- Campo de entrada -->
        <input
            type="text"
            v-model="buscador"
            :placeholder="`Buscar ${props.name}...`"
            class="p-2 block ps-10 pr-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-white focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-gray-500 dark:focus:border-gray-500"
        />

        <!-- Botón X para limpiar (solo visible cuando hay texto) -->
        <div v-if="buscador" class="absolute inset-y-0 right-0 flex items-center pr-3">
            <button
                @click="limpiarBusqueda"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none"
                type="button"
            >
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
</template>
