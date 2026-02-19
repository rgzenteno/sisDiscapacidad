<script setup>
import { computed, onMounted, onUnmounted, ref, nextTick, provide } from 'vue';

const props = defineProps({
    align: { type: String, default: 'left' },
    width: { type: String, default: '48' },
    contentClasses: { type: String, default: 'rounded-lg bg-white dark:bg-gray-700' },
});

const open = ref(false);
const dropdownRef = ref(null);
const triggerRef = ref(null);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

const toggleDropdown = async () => {
    open.value = !open.value;
};

// Función para cerrar el dropdown (disponible para los elementos hijos)
const closeDropdown = () => {
    open.value = false;
};

// Event listener para detectar clicks en elementos del dropdown
const handleContentClick = (e) => {
    // Verifica si el elemento clickeado es un elemento seleccionable
    const clickedElement = e.target.closest('[data-dropdown-item]') ||
        e.target.closest('li') ||
        e.target.closest('button') ||
        e.target.closest('a') ||
        e.target.closest('[role="option"]');

    if (clickedElement) {
        // Cierra el dropdown después de un pequeño delay para permitir que se complete la acción
        setTimeout(() => {
            open.value = false;
        }, 100);
    }
};

// Proporciona la función closeDropdown a los componentes hijos
provide('closeDropdown', closeDropdown);

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
});

const widthClass = computed(() => ({
    48: 'w-48',
    60: 'w-60',
    full: 'w-full'
} [props.width] || 'w-48'));

// Expone la función closeDropdown para uso externo
defineExpose({
    closeDropdown
});
</script>

<template>
<div class="relative" ref="triggerRef">
    <div @click="toggleDropdown">
        <slot name="trigger" />
    </div>

    <!-- Overlay para cerrar el dropdown -->
    <div v-show="open" class="fixed inset-0 z-40" @click="open = false"></div>

    <!-- Dropdown con posicionamiento fijo -->
    <Transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
        <div v-show="open" ref="dropdownRef" class="fixed z-50" :class="widthClass" @click="handleContentClick">
            <div :class="contentClasses" class="shadow-lg border border-gray-200 dark:border-gray-600">
                <slot name="content" />
            </div>
        </div>
    </Transition>
</div>
</template>
