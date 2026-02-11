/* <script setup>
import Mensajes from './Mensajes.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    message: {
        type: String,
        default: null
    },
    id: {
        type: String,
        default: () => `error-${Math.random().toString(36).substr(2, 9)}`
    }
});

// Estado para los mensajes
const tipo = ref('');
const contenido = ref([]);
const showMensaje = ref(false);

// Configuración de mensajes predefinidos
const MensajeErrorForm = {
    registroCorrecto: {
        header: 'Registro exitoso',
        text: 'Los datos se registraron correctamente.'
    },
    datosincorrectos: {
        header: 'Error al registrar',
        text: 'Credenciales incorrectas. Por favor, verifique y vuelva a intentarlo.'
    },
};

// Función para mostrar mensajes de manera flexible
const mostrarMensaje = (tipoMsg, titulo, texto) => {
    tipo.value = tipoMsg;
    contenido.value = [{
        header: titulo,
        text: texto
    }];
    showMensaje.value = true;
};

// Función para mostrar mensajes predefinidos
const contMensajes = (tipoSms, tipoContenido) => {
    const mensaje = MensajeErrorForm[tipoContenido];
    if (mensaje) {
        tipo.value = tipoSms;
        contenido.value = [mensaje];
        showMensaje.value = true;
    }
};

const emit = defineEmits(['messageClosed']);

// Función para manejar el cierre
const handleClose = () => {
    showMensaje.value = false;
    tipo.value = '';
    contenido.value = [];
    emit('messageClosed');
};

// Watcher para detectar cambios en el prop message
watch(() => props.message, (newValue, oldValue) => {
    if (newValue != null && newValue !== oldValue) {
        contMensajes('error', 'datosincorrectos');
    }
}, { immediate: true });

// Exponer funciones para uso externo
defineExpose({
    mostrarMensaje,
    contMensajes,
    cerrarMensaje: handleClose
});
</script>

<template>
    <!-- Solo renderizamos el componente Mensajes cuando hay mensajes -->
    <Mensajes 
        v-if="showMensaje"
        :id="id"
        :tipo="tipo" 
        :contenido="contenido" 
        @close="handleClose" 
    />
</template> */