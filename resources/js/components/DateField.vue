<script setup>
import { ref, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: false },
    modelValue: { type: String, required: true },
    placeholder: { type: String, default: "Seleccionar fecha" },
    type: { type: String, default: "date" },
    typeInput: { type: String, default: "date" }
});

const emit = defineEmits(['update:modelValue']);

// Separar el valor inicial en día, mes, año
const dia = ref('');
const mes = ref('');
const anio = ref('');

// Si ya viene un valor (ej: "1997-07-17"), lo descomponemos
watch(() => props.modelValue, (val) => {
    if (val && val.includes('-')) {
        const [y, m, d] = val.split('-');
        anio.value = y || '';
        mes.value = m || '';
        dia.value = d || '';
    }
}, { immediate: true });

// Cuando cambia cualquier campo, emitimos en formato YYYY-MM-DD
const emitDate = () => {
    const d = String(dia.value).padStart(2, '0');
    const m = String(mes.value).padStart(2, '0');
    const y = String(anio.value);
    if (d && m && y && y.length === 4) {
        emit('update:modelValue', `${y}-${m}-${d}`);
    } else {
        emit('update:modelValue', '');
    }
};

const inputClass = "bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-gray-500 focus:border-gray-500 text-center p-2 dark:bg-gray-700 dark:border-gray-700 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 w-full";
</script>

<template>
    <div class="mb-1" v-if="typeInput === 'date'">
        <div class="flex items-end gap-1">

            <!-- Día -->
            <div class="flex flex-col items-center gap-0.5 w-12">
                <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-widest">Día</span>
                <input
                    v-model="dia"
                    @input="emitDate"
                    type="number"
                    min="1"
                    max="31"
                    placeholder="DD"
                    :class="inputClass"
                    @keydown.e.prevent
                />
            </div>

            <span class="text-gray-400 font-bold pb-2">/</span>

            <!-- Mes -->
            <div class="flex flex-col items-center gap-0.5 w-12">
                <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-widest">Mes</span>
                <input
                    v-model="mes"
                    @input="emitDate"
                    type="number"
                    min="1"
                    max="12"
                    placeholder="MM"
                    :class="inputClass"
                    @keydown.e.prevent
                />
            </div>

            <span class="text-gray-400 font-bold pb-2">/</span>

            <!-- Año -->
            <div class="flex flex-col items-center gap-0.5 w-20">
                <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-widest">Año</span>
                <input
                    v-model="anio"
                    @input="emitDate"
                    type="number"
                    min="1900"
                    :max="new Date().getFullYear()"
                    placeholder="AAAA"
                    :class="inputClass"
                    @keydown.e.prevent
                />
            </div>

        </div>
    </div>
</template>
