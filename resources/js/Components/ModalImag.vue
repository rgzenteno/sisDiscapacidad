<script setup>
const props = defineProps({
    img: String,
});

const emit = defineEmits(['close']);
</script>

<template>
<div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 px-4 py-6 overflow-y-auto backdrop-blur-sm">
    <div class="relative w-full max-w-2xl max-h-full">
        <!-- Botón de cerrar - ahora posicionado absolutamente fuera del contenedor de la imagen -->
        <button 
            @click="$emit('close')" 
            class="absolute -top-3 -right-3 text-gray-500 dark:text-gray-400 dark:hover:text-red-200 bg-white dark:bg-gray-700 hover:bg-red-100 dark:hover:bg-gray-600 rounded-full p-2 transition-colors duration-200 shadow-lg z-10"
        >
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>
        
        <!-- Contenedor de la imagen -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 transform transition-all duration-300 overflow-y-auto">
            <img 
                :src="'/storage/comprobantes/' + props.img" 
                alt="Comprobante de pago" 
                class="w-full h-full object-contain" 
            />
        </div>
    </div>
</div>
</template>