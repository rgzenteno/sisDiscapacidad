<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const inputRef = ref(null);
const showDateModal = ref(false);
const modalRef = ref(null);
const triggerRef = ref(null);

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    placeholder: { type: String, default: "Seleccionar fecha" },
    inputType: {
        type: String, default: 'text',
        validator: (value) => ['text', 'number', 'email', 'date', 'password', 'complemento', 'distrito', 'discapacidad', 'usuario', 'letras-punto', 'direccion'].includes(value)
    },
    errors: { type: [String, Object, Boolean], default: false },
    focusColor: { type: String, default: 'blue' },
    customClass: { type: [String, Boolean], default: 'capitalize' },
    readonly: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    autofocus: { type: Boolean, default: false }
});

const showAnioList = ref(false);
const showMesList = ref(false);
const anioListRef = ref(null);
const mesListRef = ref(null);
const modalStyle = ref({});

const openModal = () => {
    if (props.disabled || props.readonly) return;
    const rect = triggerRef.value?.getBoundingClientRect();
    if (rect) {
        modalStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
            width: `${Math.max(rect.width, 256)}px`,
            zIndex: 9999,
        };
    }
    showDateModal.value = !showDateModal.value;
};

const emit = defineEmits(['update:modelValue']);

// ── Fecha ──────────────────────────────────────────────
const dia = ref('');
const mes = ref('');
const anio = ref('');

// Mostrar en el input principal: DD/MM/YYYY
const displayDate = ref('');


const emitDate = () => {
    const d = String(dia.value).padStart(2, '0');
    const m = String(mes.value).padStart(2, '0');
    const y = String(anio.value);
    if (dia.value && mes.value && y.length === 4) {
        emit('update:modelValue', `${y}-${m}-${d}`);
        displayDate.value = `${d}/${m}/${y}`;
    }
};

// ── Calendario navegable ───────────────────────────────
const calMes = ref(String(new Date().getMonth() + 1).padStart(2, '0'));
const calAnio = ref(String(new Date().getFullYear()));

// Elimina el segundo watch (el de calMes/calAnio) y reemplaza el primero por este:
watch(() => props.modelValue, (val) => {
    if (val && String(val).includes('-')) {
        const [y, m, d] = String(val).split('-');
        anio.value = y || '';
        mes.value = m || '';
        dia.value = d || '';
        displayDate.value = `${d}/${m}/${y}`;
        calAnio.value = y || String(new Date().getFullYear());
        calMes.value = m || String(new Date().getMonth() + 1).padStart(2, '0');
    } else {
        dia.value = mes.value = anio.value = displayDate.value = '';
    }
}, { immediate: true });

const meses = [
    { value: '01', label: 'Enero' }, { value: '02', label: 'Febrero' },
    { value: '03', label: 'Marzo' }, { value: '04', label: 'Abril' },
    { value: '05', label: 'Mayo' }, { value: '06', label: 'Junio' },
    { value: '07', label: 'Julio' }, { value: '08', label: 'Agosto' },
    { value: '09', label: 'Septiembre' }, { value: '10', label: 'Octubre' },
    { value: '11', label: 'Noviembre' }, { value: '12', label: 'Diciembre' },
];
const currentYear = new Date().getFullYear();
const anios = Array.from({ length: 100 }, (_, i) => String(currentYear - i));

// Días y offset del mes actual en el calendario
const daysInMonth = computed(() => {
    return new Date(Number(calAnio.value), Number(calMes.value), 0).getDate();
});
const firstDayOfMonth = computed(() => {
    const day = new Date(Number(calAnio.value), Number(calMes.value) - 1, 1).getDay();
    return day; // 0=Dom, posición de inicio en la grilla
});

const prevMonth = () => {
    let m = Number(calMes.value) - 1;
    let y = Number(calAnio.value);
    if (m < 1) { m = 12; y--; }
    calMes.value = String(m).padStart(2, '0');
    calAnio.value = String(y);
};
const nextMonth = () => {
    let m = Number(calMes.value) + 1;
    let y = Number(calAnio.value);
    if (m > 12) { m = 1; y++; }
    calMes.value = String(m).padStart(2, '0');
    calAnio.value = String(y);
};

const selectDay = (d) => {
    dia.value = String(d).padStart(2, '0');
    mes.value = calMes.value;
    anio.value = calAnio.value;
    emitDate();
    showDateModal.value = false;
};

const isSelected = (d) => {
    return dia.value === String(d).padStart(2, '0')
        && mes.value === calMes.value
        && anio.value === calAnio.value;
};
const isToday = (d) => {
    const t = new Date();
    return d === t.getDate()
        && Number(calMes.value) === t.getMonth() + 1
        && Number(calAnio.value) === t.getFullYear();
};

// Cerrar modal al click fuera
const handleClickOutside = (e) => {
    if (modalRef.value && !modalRef.value.contains(e.target) &&
        triggerRef.value && !triggerRef.value.contains(e.target)) {
        showDateModal.value = false;
    }
    if (anioListRef.value && !anioListRef.value.contains(e.target)) {
        showAnioList.value = false;
    }
    if (mesListRef.value && !mesListRef.value.contains(e.target)) {
        showMesList.value = false;
    }
};

onMounted(() => {
    if (props.autofocus && inputRef.value && props.inputType !== 'date') {
        nextTick(() => inputRef.value?.focus());
    }
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// ── Otros inputs ───────────────────────────────────────
const handleInput = (e) => {
    let newValue = e.target.value;
    if (props.readonly || props.disabled) return;

    if (props.inputType === 'text') {
        newValue = newValue.replace(/[^a-zA-ZáéíóúñÁÉÍÓÚÑ\s]/g, '');
    } else if (props.inputType === 'number') {
        newValue = newValue.replace(/[^0-9]/g, '');
    } else if (props.inputType === 'email') {
        newValue = newValue.replace(/[^a-zA-Z0-9@._-]/g, '');
    } else if (props.inputType === 'usuario') {
        newValue = newValue.replace(/[^a-zA-Z0-9._-]/g, '');
    } else if (props.inputType === 'complemento') {
        newValue = newValue.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 2);
    } else if (props.inputType === 'distrito' || props.inputType === 'discapacidad') {
        newValue = newValue.replace(/[^a-zA-Z0-9\-\s]/g, '');
    } else if (props.inputType === 'letras-punto') {
        newValue = newValue.replace(/[^a-zA-ZáéíóúñÁÉÍÓÚÑ0-9\s\.\-]/g, '');
    }

    emit('update:modelValue', newValue);
    e.target.value = newValue;
};
</script>

<template>
    <div class="mb-1">

        <!-- ── Input fecha ── -->
        <div v-if="inputType === 'date'" class="relative">

            <!-- Input display (solo lectura, abre el modal) -->
            <div ref="triggerRef" @click="openModal">
                <input readonly :value="displayDate" :placeholder="placeholder" :disabled="disabled" :class="[
                    'placeholder-no-uppercase cursor-pointer',
                    errors ? 'border-red-500 text-red-500 placeholder-red-600' : 'border-gray-300 hover:border-blue-400',
                    `focus:border-${focusColor}-500 focus:ring-${focusColor}-500`,
                    'w-full bg-gray-50 border shadow-sm text-gray-900 text-sm rounded-lg p-2',
                    'dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white',
                    disabled ? 'bg-gray-100 cursor-not-allowed text-gray-600 dark:bg-gray-500' : ''
                ]" />
                <!-- Icono calendario -->
                <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <!-- Mini modal -->
            <Teleport to="body">
                <Transition name="date-modal">
                    <div v-if="showDateModal" ref="modalRef" :style="modalStyle"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-2 w-64">

                        <!-- Header: flechas + mes/año -->
                        <div class="flex items-center justify-between mb-3">
                            <button type="button" @click="prevMonth"
                                class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </button>

                            <div class="flex items-center gap-2">
                                <div class="relative" ref="mesListRef">
                                    <button type="button" @click="showMesList = !showMesList"
                                        class="text-sm font-semibold bg-transparent cursor-pointer text-gray-800 dark:text-gray-100 text-left">
                                        {{meses.find(m => m.value === calMes)?.label}} ▾
                                    </button>
                                    <div v-if="showMesList"
                                        class="absolute bottom-full left-0 mb-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-y-auto max-h-60 z-50 w-28">
                                        <div v-for="m in meses" :key="m.value"
                                            @click="calMes = m.value; showMesList = false" :class="[
                                                'text-sm px-3 py-1 cursor-pointer',
                                                calMes === m.value
                                                    ? 'bg-blue-600 text-white font-semibold'
                                                    : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                                            ]">
                                            {{ m.label }}
                                        </div>
                                    </div>
                                </div>
                                <!-- Reemplaza el select de calAnio por esto -->
                                <div class="relative" ref="anioListRef">
                                    <button type="button" @click="showAnioList = !showAnioList"
                                        class="text-sm font-semibold bg-transparent cursor-pointer text-gray-800 dark:text-gray-100 w-14 text-left">
                                        {{ calAnio }} ▾
                                    </button>
                                    <div v-if="showAnioList"
                                        class="absolute bottom-full left-0 mb-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-y-auto max-h-60 z-50 w-20">
                                        <div v-for="a in anios" :key="a" @click="calAnio = a; showAnioList = false"
                                            :class="[
                                                'text-sm px-3 py-1 cursor-pointer',
                                                calAnio === a
                                                    ? 'bg-blue-600 text-white font-semibold'
                                                    : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                                            ]">
                                            {{ a }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="nextMonth"
                                class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </div>

                        <!-- Días de la semana -->
                        <div class="grid grid-cols-7 mb-1">
                            <span v-for="d in ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá']" :key="d"
                                class="text-center text-[11px] font-semibold text-gray-400 dark:text-gray-500 py-1">
                                {{ d }}
                            </span>
                        </div>

                        <!-- Grilla de días -->
                        <div class="grid grid-cols-7 gap-y-0.5">
                            <!-- Espacios vacíos del inicio del mes -->
                            <span v-for="_ in firstDayOfMonth" :key="'e' + _" />

                            <button v-for="d in daysInMonth" :key="d" type="button" @click="selectDay(d)" :class="[
                                'text-xs h-7 w-full rounded-lg transition-colors',
                                isSelected(d)
                                    ? 'bg-blue-600 text-white font-semibold'
                                    : isToday(d)
                                        ? 'border border-blue-400 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-gray-700'
                                        : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]">
                                {{ d }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>

        <!-- ── Resto de inputs sin cambios ── -->
        <div v-else class="relative">
            <input ref="inputRef" :type="inputType === 'password' ? 'password' : 'text'" :value="modelValue"
                @input="handleInput" :readonly="readonly" :disabled="disabled" :class="[
                    'placeholder-no-uppercase',
                    errors ? 'border-red-500 text-red-500 placeholder-red-600' : 'border-gray-300 hover:border-blue-400',
                    `focus:border-${focusColor}-500 focus:ring-${focusColor}-500`,
                    'w-full bg-gray-50 border shadow-sm text-gray-900 text-sm rounded-lg p-2',
                    'dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white',
                    typeof customClass === 'string' ? customClass : (customClass === true ? 'capitalize' : ''),
                    readonly || disabled ? 'bg-gray-100 cursor-not-allowed text-gray-600 dark:bg-gray-500' : ''
                ]" :placeholder="placeholder" />
        </div>

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
