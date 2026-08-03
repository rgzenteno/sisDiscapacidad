<script setup>
// ============================================================================
// Selector de mes/gestión para la suspensión de pagos — solo permite elegir
// meses que ya están registrados en la tabla `mes` (no cualquier mes
// arbitrario) y bloquea los que este beneficiario ya cobró.
// ============================================================================
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    mesesDisponibles: { type: Array, default: () => [] },
    mesesPagados: { type: Array, default: () => [] },
    error: { type: String, default: '' },
    placeholder: { type: String, default: 'Seleccionar mes y gestión' },
});

const emit = defineEmits(['update:modelValue']);

const showPicker = ref(false);
const triggerRef = ref(null);
const pickerRef = ref(null);
const pickerStyle = ref({});
const selectedAnio = ref('');

const mesesAbrev = [
    { value: '01', label: 'Ene' }, { value: '02', label: 'Feb' }, { value: '03', label: 'Mar' },
    { value: '04', label: 'Abr' }, { value: '05', label: 'May' }, { value: '06', label: 'Jun' },
    { value: '07', label: 'Jul' }, { value: '08', label: 'Ago' }, { value: '09', label: 'Sep' },
    { value: '10', label: 'Oct' }, { value: '11', label: 'Nov' }, { value: '12', label: 'Dic' },
];

const mesesCompletos = [
    { value: '01', label: 'Enero' }, { value: '02', label: 'Febrero' }, { value: '03', label: 'Marzo' },
    { value: '04', label: 'Abril' }, { value: '05', label: 'Mayo' }, { value: '06', label: 'Junio' },
    { value: '07', label: 'Julio' }, { value: '08', label: 'Agosto' }, { value: '09', label: 'Septiembre' },
    { value: '10', label: 'Octubre' }, { value: '11', label: 'Noviembre' }, { value: '12', label: 'Diciembre' },
];

watch(() => props.modelValue, (val) => {
    if (val && String(val).includes('-')) {
        selectedAnio.value = String(val).split('-')[0];
    }
}, { immediate: true });

const aniosDisponibles = computed(() => {
    const años = [...new Set(props.mesesDisponibles.map(m => String(m.gestion)))];
    return años.sort();
});

const mesesDelAnioSeleccionado = computed(() => {
    if (!selectedAnio.value) return [];
    return props.mesesDisponibles
        .filter(m => String(m.gestion) === selectedAnio.value)
        .map(m => String(m.mes).padStart(2, '0'));
});

const isMesPagado = (anio, mes) => props.mesesPagados.some(
    p => String(p.gestion) === String(anio) && String(p.mes).padStart(2, '0') === mes
);

const isMesDisabled = (mes) => {
    if (!mesesDelAnioSeleccionado.value.includes(mes)) return true;
    return isMesPagado(selectedAnio.value, mes);
};

const getTitleMes = (mes) => {
    if (!mesesDelAnioSeleccionado.value.includes(mes)) return 'Mes no registrado en el sistema';
    if (isMesPagado(selectedAnio.value, mes)) return 'Ya cobrado';
    return '';
};

const displayValue = computed(() => {
    if (!props.modelValue || !String(props.modelValue).includes('-')) return '';
    const [anio, mes] = String(props.modelValue).split('-');
    const m = mesesCompletos.find(m => m.value === mes);
    return `${m?.label || ''} ${anio}`;
});

const openPicker = () => {
    if (!selectedAnio.value) {
        selectedAnio.value = aniosDisponibles.value[aniosDisponibles.value.length - 1] || '';
    }

    const rect = triggerRef.value?.getBoundingClientRect();
    if (rect) {
        pickerStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
            width: `${Math.max(rect.width, 240)}px`,
            zIndex: 9999,
        };
    }
    showPicker.value = !showPicker.value;
};

const selectMes = (mes) => {
    if (isMesDisabled(mes)) return;
    emit('update:modelValue', `${selectedAnio.value}-${mes}`);
    showPicker.value = false;
};

const handleClickOutside = (e) => {
    if (
        pickerRef.value && !pickerRef.value.contains(e.target) &&
        triggerRef.value && !triggerRef.value.contains(e.target)
    ) {
        showPicker.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div>
        <div ref="triggerRef" tabindex="-1" @click="openPicker" class="relative group cursor-pointer">
            <input readonly tabindex="-1" :value="displayValue" :placeholder="placeholder" :class="[
                'w-full text-sm rounded-lg px-3 py-2 border transition-colors duration-200 outline-none cursor-pointer',
                'bg-gray-50 dark:bg-gray-700/40 text-slate-700 dark:text-slate-200',
                error
                    ? 'border-rose-400 dark:border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20'
                    : 'border-gray-300 dark:border-gray-600',
            ]" />
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <Icon :icon-button="true" name="calendar" class-name="text-slate-400" fill="none" stroke="currentColor"
                    stroke-width="2" :size="16" />
            </div>
        </div>

        <p v-if="error" class="text-xs text-red-500 mt-1">{{ error }}</p>
        <p v-else-if="!aniosDisponibles.length" class="text-xs text-amber-500 mt-1">
            No hay meses registrados en el sistema todavía.
        </p>

        <Teleport to="body">
            <Transition name="date-modal">
                <div v-if="showPicker" ref="pickerRef" :style="pickerStyle"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-3">

                    <!-- Selector de gestión -->
                    <div class="flex gap-1 mb-3 overflow-x-auto pb-1">
                        <button v-for="anio in aniosDisponibles" :key="anio" type="button" @click="selectedAnio = anio"
                            :class="[
                                'px-3 py-1 rounded-lg text-sm font-medium whitespace-nowrap transition-colors',
                                selectedAnio === anio
                                    ? 'bg-purple-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200'
                            ]">
                            {{ anio }}
                        </button>
                    </div>

                    <!-- Grid de meses -->
                    <div class="grid grid-cols-4 gap-1">
                        <button v-for="mes in mesesAbrev" :key="mes.value" type="button" @click="selectMes(mes.value)"
                            :disabled="isMesDisabled(mes.value)" :title="getTitleMes(mes.value)" :class="[
                                'py-2 rounded-lg text-xs font-medium transition-colors relative',
                                props.modelValue === `${selectedAnio}-${mes.value}`
                                    ? 'bg-purple-600 text-white'
                                    : isMesPagado(selectedAnio, mes.value)
                                        ? 'bg-emerald-50 text-emerald-500 cursor-not-allowed'
                                        : isMesDisabled(mes.value)
                                            ? 'bg-gray-50 text-gray-300 cursor-not-allowed dark:bg-gray-700 dark:text-gray-600'
                                            : 'bg-gray-100 text-gray-700 hover:bg-purple-50 hover:text-purple-600 dark:bg-gray-700 dark:text-gray-200',
                            ]">
                            {{ mes.label }}
                            <Icon v-if="isMesPagado(selectedAnio, mes.value)" name="cash" :size="13" :height="13"
                                class="absolute top-0.5 right-1" class-name="text-emerald-500" icon-button />
                        </button>
                    </div>

                    <p v-if="!selectedAnio" class="text-xs text-gray-400 text-center mt-2">
                        Selecciona una gestión primero
                    </p>
                    <p v-else-if="!mesesDelAnioSeleccionado.length" class="text-xs text-gray-400 text-center mt-2">
                        Sin meses registrados en {{ selectedAnio }}
                    </p>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.date-modal-enter-active,
.date-modal-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.date-modal-enter-from,
.date-modal-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
