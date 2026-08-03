<script setup>

import { ref } from 'vue'
import Icon from '@/components/Icon.vue';
import Button from '@/components/Button.vue';
import Input from '@/components/Input.vue';
import Modal from "@/components/Modal.vue";

const props = defineProps({
    registro: {
        type: Object,
        default: () => ({})
    },
    procesando: {
        type: Boolean,
        default: false
    },
});

const emit = defineEmits(['guardar', 'close']);

const ESTADOS_LABEL = {
    activo: 'Activo',
    baja_temporal: 'Baja Temporal',
    baja_definitiva: 'Baja Definitiva',
    depurado: 'Depurado',
    pagos_suspendidos: 'Pagos Suspendidos',
};

const labelEstado = (estado) => ESTADOS_LABEL[estado?.toLowerCase()] || 'Sin estado';

// Formatea "YYYY-MM-DD" (o "Actual") a un texto de mes/año legible, igual
// criterio que ModalEstadoBene, sin depender de sus internals.
const formatearMes = (fecha) => {
    if (!fecha || fecha === 'Actual') return 'En curso';

    const limpio = String(fecha).split(' ')[0];
    const d = new Date(limpio + 'T00:00:00');
    const mes = d.toLocaleDateString('es-ES', { month: 'long' });
    const anio = d.getFullYear();

    return `${mes.charAt(0).toUpperCase() + mes.slice(1)} - ${anio}`;
};

const observaciones = ref(props.registro?.observacion || '');

const guardar = () => {
    emit('guardar', observaciones.value);
};
</script>

<template>
    <Modal :showHeader="true" :showFooter="false" maxWidth="max-w-md" @close="emit('close')">
        <template #icon>
            <Icon :icon-button="true" name="clipboard" class-name="text-white" :size="20" />
        </template>
        <template #label1>
            Editar observación
        </template>
        <template #label2>{{ labelEstado(registro.estado) }}</template>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 px-3.5 py-2.5 text-sm">
                <p class="text-slate-500">
                    <span class="font-medium text-slate-600">Estado:</span> {{ labelEstado(registro.estado) }}
                </p>
                <p class="text-slate-500 mt-1">
                    <span class="font-medium text-slate-600">Periodo:</span>
                    {{ formatearMes(registro.desde) }}
                    <span v-if="registro.hasta && formatearMes(registro.hasta) !== formatearMes(registro.desde)">
                        → {{ formatearMes(registro.hasta) }}
                    </span>
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Observación</label>
                <Input input-type="observacion" :model-value="observaciones" :maxlength="50"
                    placeholder="Ingrese la observación" @update:model-value="observaciones = $event" />
            </div>
        </div>

        <template #footer>
            <div class="sm:px-6 border-t border-gray-100 dark:border-gray-700/50 py-5">
                <div class="flex justify-center sm:justify-end gap-3">
                    <Button @click="emit('close')" :style="'py-2.5 px-10 sm:px-12 rounded-xl border border-gray-200'"
                        class="text-slate-700 bg-slate-100 hover:bg-slate-200">
                        Cancelar
                    </Button>
                    <Button @click="guardar" :disabled="procesando"
                        :style="'py-2.5 px-10 sm:px-12 rounded-xl border relative'"
                        :class="procesando ? 'opacity-60 cursor-not-allowed bg-[rgb(var(--brand-400))]' : 'bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))]'"
                        class="text-white">
                        {{ procesando ? 'Guardando...' : 'Guardar' }}
                    </Button>
                </div>
            </div>
        </template>
    </Modal>
</template>
