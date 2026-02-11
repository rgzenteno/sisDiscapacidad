<!-- Tooltip.vue -->
<template>
<div class="tooltip-wrapper">
    <div class="tooltip-container" @mouseenter="showTooltip" @mouseleave="hideTooltip">
        <!-- Contenido sobre el cual aparecerá el tooltip -->
        <slot></slot>

        <!-- Portal para el tooltip -->
        <div v-show="isVisible" class="tooltip" :class="[position, {'tooltip-visible': isVisible}]" :style="tooltipStyle">
            {{ text }}
        </div>
    </div>
</div>
</template>

  
<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    text: {
        type: String,
        required: true
    },
    position: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
    },
    offset: {
        type: Number,
        default: 5
    }
})

const isVisible = ref(false)

const showTooltip = () => {
    isVisible.value = true
}

const hideTooltip = () => {
    isVisible.value = false
}

const tooltipStyle = computed(() => {
    const styles = {
        top: {
            bottom: '100%',
            left: '50%',
            transform: 'translateX(-50%)',
            marginBottom: `${props.offset}px`
        },
        bottom: {
            top: '100%',
            left: '50%',
            transform: 'translateX(-50%)',
            marginTop: `${props.offset}px`
        },
        left: {
            top: '50%',
            right: '100%',
            transform: 'translateY(-50%)',
            marginRight: `${props.offset}px`
        },
        right: {
            top: '50%',
            left: '100%',
            transform: 'translateY(-50%)',
            marginLeft: `${props.offset}px`
        }
    }

    return styles[props.position]
})
</script>

  
<style>
/* Estos estilos deben ser globales para asegurar que funcionen en la tabla */
.tooltip-wrapper {
    position: relative;
    display: inline-block;
}

.tooltip-container {
    position: relative;
    display: inline-block;
    cursor: pointer;
}

.tooltip {
    position: fixed;
    /* Cambiado a fixed para evitar problemas con overflow */
    background-color: #333;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    white-space: nowrap;
    z-index: 9999;
    /* Z-index muy alto para asegurar que esté por encima de todo */
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

.tooltip-visible {
    opacity: 1;
    visibility: visible;
}

/* Asegurarse de que el contenedor tenga un z-index alto también */
td,
th {
    position: relative;
    z-index: 1;
}

/* Estilos específicos para cada posición */
.tooltip.top {
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
}

.tooltip.bottom {
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
}

.tooltip.left {
    right: 100%;
    top: 50%;
    transform: translateY(-50%);
}

.tooltip.right {
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
}
</style>
