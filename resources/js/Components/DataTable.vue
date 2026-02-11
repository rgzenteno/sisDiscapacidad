<template>
    <main class="flex-1 overflow-x-hidden overflow-y-auto border-b-2 border-x-2 bg-white rounded-b-lg mr-1">
        <div v-if="data.length > 0" class="mx-0 py-2">
            <div class="relative mx-4 overflow-x-auto border rounded-lg shadow-md sm:rounded-lg">
                <table class="w-full rounded-lg text-xs text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <!-- Header -->
                    <thead class="sticky top-0 z-10 text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr class="border-b">
                            <th v-for="(column, index) in columns" :key="index" scope="col"
                                :class="['tracking-wider px-3 py-2.5', column.headerClass]">
                                {{ column.label }}
                            </th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody>
                        <tr v-for="(item, index) in data" :key="item[rowKey]"
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <slot name="row" :item="item" :index="index">
                                <!-- Contenido por defecto si no se proporciona slot -->
                                <td v-for="(column, idx) in columns" :key="idx"
                                    :class="['px-3 py-2', column.cellClass]">
                                    {{ item[column.field] }}
                                </td>
                            </slot>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center text-gray-800 h-3/5 w-full mt-10 pt-10">
            <slot name="empty">
                <svg class="w-12 h-12 text-gray-800 dark:text-gray-900" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z"
                        clip-rule="evenodd" />
                </svg>
                <span class="text-lg py-2">{{ emptyMessage }}</span>
            </slot>
        </div>
    </main>
</template>

<script setup>
defineProps({
    // Datos de la tabla
    data: {
        type: Array,
        required: true,
        default: () => []
    },

    // Configuración de columnas
    columns: {
        type: Array,
        required: true,
        // Ejemplo: [{ label: 'Nombre', field: 'nombre', headerClass: '', cellClass: 'whitespace-nowrap' }]
    },

    // Clave única para cada fila
    rowKey: {
        type: String,
        default: 'id'
    },

    // Mensaje cuando no hay datos
    emptyMessage: {
        type: String,
        default: 'No se encontraron datos. ¡Agregue registros para continuar!'
    }
});
</script>

<style scoped>
/* Animación ripple si la necesitas */
.ripple-background {
    position: absolute;
    border-radius: 50%;
    animation: ripple 1.5s infinite;
}

@keyframes ripple {
    0% {
        opacity: 0.6;
        transform: scale(1);
    }

    100% {
        opacity: 0;
        transform: scale(1.5);
    }
}
</style>
