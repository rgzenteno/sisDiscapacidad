<script setup>
const props = defineProps({
    id: Number,
    tipo: {
        type: String,
        default: 'eliminar' // 'eliminar' o 'pago'
    },
    titulo: {
        type: String,
        default: null
    },
    mensaje: {
        type: String,
        default: null
    },
    submensaje: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'confirmar']);

// Configuraciones por tipo
const configs = {
    eliminar: {
        icono: 'advertencia',
        iconoColor: 'text-red-600',
        iconoBg: 'bg-red-100',
        titulo: '¿Eliminar registro?',
        mensaje: 'Esta acción no se puede deshacer. El registro será eliminado permanentemente del historial de estados.',
        submensaje: null,
        botonTexto: 'Eliminar',
        botonColor: 'bg-red-600 hover:bg-red-700',
        tituloColor: 'text-slate-800'
    },
    pago: {
        icono: 'advertencia',  // ✅ Usa el mismo ícono triangular que 'eliminar'
        iconoColor: 'text-yellow-600 dark:text-yellow-400',
        iconoBg: 'bg-yellow-100 dark:bg-yellow-900/40',
        titulo: 'Confirmar Pago',
        mensaje: '¿Confirma que el beneficiario recibió su pago?',
        submensaje: 'Esta acción no se puede deshacer. El registro quedará permanente en el sistema.',
        botonTexto: 'Confirmar Pago',
        botonColor: 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800',
        tituloColor: 'text-gray-800 dark:text-gray-200'  // ✅ Título neutral
    }
};

// Configuración actual
const config = {
    ...configs[props.tipo],
    titulo: props.titulo || configs[props.tipo].titulo,
    mensaje: props.mensaje || configs[props.tipo].mensaje,
    submensaje: props.submensaje || configs[props.tipo].submensaje
};

const handleConfirmar = () => {
    if (props.tipo === 'pago') {
        emit('confirmar', props.id, props.tipo);
    } else {
        emit('confirmar');
    }
};
</script>

<template>
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click="emit('close')">
        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700"
            @click.stop>

            <!-- Botón cerrar (X) -->
            <div class="flex items-start gap-3 flex-shrink-0 ms-3">
                    <button type="button" @click="emit('close')"
                        class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>

            <!-- Contenido -->
            <div class="p-8 text-center">
                <!-- Icono -->
                <div :class="[
                    'w-16 h-16 mx-auto flex items-center justify-center rounded-full shadow-inner mb-4',
                    config.iconoBg
                ]">
                    <!-- Icono de advertencia (triángulo) -->
                    <svg v-if="config.icono === 'advertencia'" :class="['w-8 h-8', config.iconoColor]" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>

                    <!-- Icono de alerta (círculo con exclamación) -->
                    <svg v-else-if="config.icono === 'alerta'" :class="['w-10 h-10', config.iconoColor]"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V8Zm-1 7a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"
                            clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Título -->
                <h3 :class="['text-xl font-semibold text-center mb-3', config.tituloColor]">
                    {{ config.titulo }}
                </h3>

                <!-- Mensaje principal -->
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-2">
                    {{ config.mensaje }}
                </p>

                <!-- Submensaje (opcional) -->
                <p v-if="config.submensaje" class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    <strong>{{ config.submensaje }}</strong>
                </p>
            </div>

            <!-- Botones -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                <button @click="emit('close')"
                    class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition">
                    Cancelar
                </button>
                <button @click="handleConfirmar" :class="[
                    'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition',
                    config.botonColor
                ]">
                    {{ config.botonTexto }}
                </button>
            </div>
        </div>
    </div>
</template>
