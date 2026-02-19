<script setup>
import { ref, onMounted } from 'vue';
import Pusher from 'pusher-js';

const notificaciones = ref([]);
const idTutor = ref(506); // El ID del tutor que recibirá las notificaciones

// Función para inicializar Pusher
const initPusher = () => {
  console.log('Iniciando Pusher...');
  
  const pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    encrypted: true
  });

  console.log('Suscribiéndose al canal:', `tutor.${idTutor.value}`);
  
  const channel = pusher.subscribe(`tutor.${idTutor.value}`);
  
  channel.bind('nueva-notificacion', (data) => {
    console.log('Nueva notificación recibida:', data);
    notificaciones.value.unshift(data);
  });

  // Verificar si la conexión fue exitosa
  pusher.connection.bind('connected', () => {
    console.log('¡Conectado a Pusher!');
  });

  // Verificar si hay errores de conexión
  pusher.connection.bind('error', (err) => {
    console.error('Error de conexión Pusher:', err);
  });
};

onMounted(() => {
  initPusher();
});
</script>

<template>
  <div>
    <h2>Notificaciones ({{ notificaciones.length }})</h2>
    <div v-if="notificaciones.length === 0">
      No hay notificaciones
    </div>
    <div v-else>
      <div v-for="notificacion in notificaciones" :key="notificacion.id">
        <p>{{ notificacion.mensaje }}</p>
        <small>{{ new Date(notificacion.hora).toLocaleString() }}</small>
      </div>
    </div>
  </div>
</template>