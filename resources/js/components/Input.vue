<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Seleccionar fecha'
    },
    inputType: {
        type: String,
        default: 'text',
        validator: (value) => [
            'text', 'number', 'email', 'date', 'password',
            'complemento', 'distrito', 'discapacidad', 'usuario',
            'letras-punto', 'direccion', 'texto', 'observacion'
        ].includes(value)
    },
    errors: {
        type: [String, Object, Boolean],
        default: false
    },
    focusColor: {
        type: String,
        default: 'blue'
    },
    customClass: {
        type: [String, Boolean],
        default: 'capitalize'
    },
    readonly: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    autofocus: {
        type: Boolean,
        default: false
    },
    inputClass: {
        type: String,
        default: 'px-4 py-2.5'
    },
    message: {
        type: String,
        default: ''
    },
    messageType: {
        type: String,
        default: 'warning', // 'warning' | 'info' | 'error' | 'success'
        validator: (v) => ['warning', 'info', 'error', 'success'].includes(v)
    }
});

// ============================================================================
// EMITS
// ============================================================================
const emit = defineEmits(['update:modelValue']);

// ============================================================================
// REFS - REFERENCIAS DOM
// ============================================================================
const inputRef = ref(null);
const modalRef = ref(null);
const triggerRef = ref(null);
const anioListRef = ref(null);
const mesListRef = ref(null);

// ============================================================================
// REFS - ESTADO DEL MODAL Y CALENDARIO
// ============================================================================
const showDateModal = ref(false);
const showAnioList = ref(false);
const showMesList = ref(false);

// ============================================================================
// REFS - FECHA
// ============================================================================
const dia = ref('');
const mes = ref('');
const anio = ref('');
const displayDate = ref('');
const calMes = ref(String(new Date().getMonth() + 1).padStart(2, '0'));
const calAnio = ref(String(new Date().getFullYear()));

// ============================================================================
// REFS - ESTILOS DINÁMICOS
// ============================================================================
const modalStyle = ref({});
const mesListStyle = ref({});
const anioListStyle = ref({});

// ============================================================================
// DATOS ESTÁTICOS
// ============================================================================
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

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Número de días del mes actualmente visible en el calendario
 */
const daysInMonth = computed(() => {
    return new Date(Number(calAnio.value), Number(calMes.value), 0).getDate();
});

/**
 * Día de la semana en que inicia el mes (0 = Domingo) para calcular el offset de la grilla
 */
const firstDayOfMonth = computed(() => {
    return new Date(Number(calAnio.value), Number(calMes.value) - 1, 1).getDay();
});

/**
 * Estilos y configuración del ícono según el tipo de mensaje activo
 */
const messageStyles = computed(() => ({
    warning: {
        card: 'bg-amber-50 border-amber-300 text-amber-800',
        icon: 'alertTriangle',
        iconClass: 'text-amber-500',
        stroke: 'currentColor'
    },
    info: {
        card: 'bg-blue-50 border-blue-300 text-blue-800',
        icon: 'infoCircle',
        iconClass: 'text-blue-500',
        stroke: 'currentColor'
    },
    error: {
        card: 'bg-red-50 border-red-300 text-red-800',
        icon: 'xCircle',
        iconClass: 'text-red-500',
        stroke: 'currentColor'
    },
    success: {
        card: 'bg-green-50 border-green-300 text-green-800',
        icon: 'checkCircle',
        iconClass: 'text-green-500',
        stroke: 'none'
    },
}[props.messageType]));

// ============================================================================
// WATCHERS
// ============================================================================

watch(() => props.modelValue, (val) => {
    if (!val || !String(val).includes('-')) {
        dia.value = mes.value = anio.value = displayDate.value = '';
        return;
    }
    const [y, m, d] = String(val).split('-');
    dia.value = d;
    mes.value = m;
    anio.value = y;
    displayDate.value = `${d}/${m}/${y}`;
    calAnio.value = y;
    calMes.value = m;
}, { immediate: true });

watch(showMesList, (val) => { if (val) updateDropdownPositions(); });
watch(showAnioList, (val) => { if (val) updateDropdownPositions(); });

// ============================================================================
// FUNCIONES - MODAL Y POSICIONAMIENTO
// ============================================================================

/**
 * Abre el modal de fecha y calcula su posición óptima en el viewport
 */
const openModal = async () => {
    if (props.disabled || props.readonly) return;

    showDateModal.value = true;

    // Esperar a que el modal esté renderizado para medir
    await nextTick();

    const rect = triggerRef.value?.getBoundingClientRect();
    const modal = modalRef.value;

    if (!rect || !modal) return;

    const modalRect = modal.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    let top;
    let left = rect.left;

    // Decidir si el modal se posiciona abajo o arriba
    if (spaceBelow >= modalRect.height + 8) {
        top = rect.bottom + 4;         // abajo
    } else if (spaceAbove >= modalRect.height + 8) {
        top = rect.top - modalRect.height - 4; // arriba
    } else {
        top = Math.max(8, window.innerHeight - modalRect.height - 8); // centro en viewport
    }

    // Evitar desbordamiento horizontal
    if (left + modalRect.width > window.innerWidth) {
        left = window.innerWidth - modalRect.width - 8;
    }
    if (left < 8) left = 8;

    modalStyle.value = {
        position: 'fixed',
        top: `${top}px`,
        left: `${left}px`,
        zIndex: 9999,
    };
};

/**
 * Actualiza la posición de los dropdowns de mes y año según el DOM actual
 */
const updateDropdownPositions = async () => {
    await nextTick();

    if (mesListRef.value) {
        const rect = mesListRef.value.getBoundingClientRect();
        mesListStyle.value = {
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
        };
    }
    if (anioListRef.value) {
        const rect = anioListRef.value.getBoundingClientRect();
        anioListStyle.value = {
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
        };
    }
};

/**
 * Cierra el modal de fecha y los dropdowns al hacer clic fuera de ellos
 * @param {MouseEvent} e - Evento de clic
 */
const handleClickOutside = (e) => {
    const mesDropdown = document.querySelector('.mes-dropdown');
    const anioDropdown = document.querySelector('.anio-dropdown');

    if (
        modalRef.value && !modalRef.value.contains(e.target) &&
        triggerRef.value && !triggerRef.value.contains(e.target) &&
        !mesDropdown?.contains(e.target) &&
        !anioDropdown?.contains(e.target)
    ) {
        showDateModal.value = false;
    }
    if (anioListRef.value && !anioListRef.value.contains(e.target) && !anioDropdown?.contains(e.target)) {
        showAnioList.value = false;
    }
    if (mesListRef.value && !mesListRef.value.contains(e.target) && !mesDropdown?.contains(e.target)) {
        showMesList.value = false;
    }
};

// ============================================================================
// FUNCIONES - CALENDARIO
// ============================================================================

/**
 * Emite la fecha seleccionada en formato YYYY-MM-DD y actualiza el display
 */
const emitDate = () => {
    const d = String(dia.value).padStart(2, '0');
    const m = String(mes.value).padStart(2, '0');
    const y = String(anio.value);
    if (dia.value && mes.value && y.length === 4) {
        emit('update:modelValue', `${y}-${m}-${d}`);
        displayDate.value = `${d}/${m}/${y}`;
    }
};

/**
 * Retrocede un mes en el calendario navegable
 */
const prevMonth = () => {
    let m = Number(calMes.value) - 1;
    let y = Number(calAnio.value);
    if (m < 1) { m = 12; y--; }
    calMes.value = String(m).padStart(2, '0');
    calAnio.value = String(y);
};

/**
 * Avanza un mes en el calendario navegable
 */
const nextMonth = () => {
    let m = Number(calMes.value) + 1;
    let y = Number(calAnio.value);
    if (m > 12) { m = 1; y++; }
    calMes.value = String(m).padStart(2, '0');
    calAnio.value = String(y);
};

/**
 * Selecciona un día del calendario y cierra el modal
 * @param {number} d - Día seleccionado
 */
const selectDay = (d) => {
    dia.value = String(d).padStart(2, '0');
    mes.value = calMes.value;
    anio.value = calAnio.value;
    emitDate();
    showDateModal.value = false;
};

/**
 * Indica si un día está seleccionado en el mes y año actuales del calendario
 * @param {number} d - Día a verificar
 * @returns {boolean}
 */
const isSelected = (d) => {
    return dia.value === String(d).padStart(2, '0')
        && mes.value === calMes.value
        && anio.value === calAnio.value;
};

/**
 * Indica si un día corresponde a la fecha de hoy
 * @param {number} d - Día a verificar
 * @returns {boolean}
 */
const isToday = (d) => {
    const t = new Date();
    return d === t.getDate()
        && Number(calMes.value) === t.getMonth() + 1
        && Number(calAnio.value) === t.getFullYear();
};

// ============================================================================
// FUNCIONES - INPUTS
// ============================================================================

/**
 * Mapa de expresiones regulares para filtrar caracteres inválidos por tipo de input
 */
const filters = {
    text: /[^a-zA-ZáéíóúñÁÉÍÓÚÑ\s]/g,
    number: /[^0-9]/g,
    email: /[^a-zA-Z0-9@._-]/g,
    usuario: /[^a-zA-Z0-9._-]/g,
    complemento: /[^a-zA-Z0-9]/g,
    distrito: /[^a-zA-Z0-9\-\s]/g,
    discapacidad: /[^a-zA-Z0-9\-\s]/g,
    'letras-punto': /[^a-zA-ZáéíóúñÁÉÍÓÚÑ0-9\s.\-]/g,
    observacion: /[^a-zA-ZáéíóúñÁÉÍÓÚÑ0-9\s.,\-]/g,
};

/**
 * Filtra y normaliza el valor del input según su tipo antes de emitirlo
 * @param {InputEvent} e - Evento de input
 */
const handleInput = (e) => {
    if (props.readonly || props.disabled) return;

    let value = e.target.value;

    if (filters[props.inputType]) {
        value = value.replace(filters[props.inputType], '');
    }

    if (props.inputType === 'complemento') {
        value = value.toUpperCase().substring(0, 2);
    }

    if (props.inputType === 'observacion') {
        value = value.toUpperCase();
    }

    emit('update:modelValue', value);
    e.target.value = value;
};

/**
 * Cierra el modal de fecha al presionar ESC
 * @param {KeyboardEvent} e - Evento de teclado
 */
const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        showDateModal.value = false;
    }
};

/**
 * Detecta si el dispositivo es móvil o táctil
 * @returns {boolean}
 */
const isMobile = () =>
    /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) ||
    ('ontouchstart' in window && navigator.maxTouchPoints > 1);

// ============================================================================
// LIFECYCLE
// ============================================================================
onMounted(() => {
    if (props.autofocus && inputRef.value && props.inputType !== 'date' && !isMobile()) {
        nextTick(() => inputRef.value?.focus());
    }
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <!-- ── Input fecha ── -->
    <div v-if="inputType === 'date'" class="relative">

        <!-- Input display (solo lectura, abre el modal) -->
        <div ref="triggerRef" @click="openModal" class="relative">
            <input readonly :value="displayDate" :placeholder="placeholder" :disabled="disabled" :class="[
                'placeholder-no-uppercase w-full text-sm rounded-xl border transition-colors duration-200 outline-none',
                'bg-gray-50 dark:bg-gray-700/40',
                'text-slate-700 dark:text-slate-200',
                '',
                disabled
                    ? 'border-gray-400 dark:border-gray-600/40 text-slate-400 dark:text-slate-500 bg-gray-100 dark:bg-gray-600/40 cursor-not-allowed focus:outline-none focus:ring-0'
                    : errors
                        ? 'border-rose-400 dark:border-rose-500 placeholder-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500/20'
                        : `border-gray-400 dark:border-gray-600/40 hover:border-blue-300 dark:hover:border-blue-600 focus:border-blue-400 focus:ring-0 focus:ring-gray-100 placeholder-slate-400 dark:placeholder-slate-500`,
                inputClass
            ]" />
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <Icon :icon-button="true" name="calendar"
                    :class-name="disabled ? 'text-slate-300 dark:text-slate-600' : errors ? 'text-rose-400' : 'text-slate-400'"
                    :size="18" />
            </div>
        </div>

        <!-- Mini modal -->
        <Teleport to="body">
            <Transition name="date-modal">
                <div v-if="showDateModal" ref="modalRef" :style="modalStyle"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-3 w-[280px] max-h-[90vh] overflow-auto">

                    <!-- Header: flechas + mes/año -->
                    <div class="flex items-center justify-between mb-3">
                        <button type="button" @click="prevMonth"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                        </button>

                        <div class="flex items-center w-full">
                            <div class="flex justify-center relative w-full items-center" ref="mesListRef">
                                <button type="button" @click="showMesList = !showMesList"
                                    class="text-sm font-semibold bg-transparent cursor-pointer text-gray-800 dark:text-gray-100 text-left">
                                    {{meses.find(m => m.value === calMes)?.label}} ▾
                                </button>
                                <Teleport to="body">
                                    <div v-if="showMesList"
                                        class="mes-dropdown fixed bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-y-auto max-h-60 z-[10000] w-28"
                                        :style="mesListStyle">
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
                                </Teleport>
                            </div>

                            <div class="flex justify-center relative w-full items-center" ref="anioListRef">
                                <button type="button" @click="showAnioList = !showAnioList"
                                    class="text-sm font-semibold bg-transparent cursor-pointer text-gray-800 dark:text-gray-100 w-14 text-left">
                                    {{ calAnio }} ▾
                                </button>
                                <Teleport to="body">
                                    <div v-if="showAnioList"
                                        class="anio-dropdown fixed bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-y-auto max-h-60 z-[10000] w-20"
                                        :style="anioListStyle">
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
                                </Teleport>
                            </div>
                        </div>

                        <button type="button" @click="nextMonth"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
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

    <!-- ── Card mensaje (type = 'texto') ── -->
    <div v-else-if="inputType === 'texto'"
        :class="['flex items-center gap-2 px-3 py-1.5 rounded-xl border text-sm font-medium w-full', messageStyles.card]">
        <Icon :icon-button="true" :name="messageStyles.icon" :class-name="`${messageStyles.iconClass} flex-shrink-0`"
            fill="none" :stroke="messageStyles.stroke" stroke-width="2" :size="16" />
        <span class="leading-snug text-xs whitespace-normal break-words min-w-0">{{ message }}</span>
    </div>

    <!-- ── Resto de inputs sin cambios ── -->
    <div v-else class="relative">
        <input ref="inputRef" :type="inputType === 'password' ? 'password' : 'text'" :value="modelValue"
            @input="handleInput" :readonly="readonly" :disabled="disabled" :class="[
                'placeholder-no-uppercase',
                'w-full text-sm rounded-xl px-4 py-2.5 border transition-colors duration-200 outline-none',
                'bg-gray-50 dark:bg-gray-700/40',
                'text-slate-700 dark:text-slate-200',
                'placeholder-slate-400 dark:placeholder-slate-500',
                errors
                    ? 'border-rose-400 dark:border-rose-500 placeholder-rose-400 focus:border-rose-500 focus:ring-1 focus:ring-rose-500/20'
                    : `border-gray-400 dark:border-gray-600/40 hover:border-blue-300 dark:hover:border-blue-600 focus:border-blue-400 focus:ring-0 focus:ring-gray-100`,
                readonly || disabled
                    ? 'bg-gray-100 dark:bg-gray-600/40 border-gray-200 dark:border-gray-600/40 text-slate-400 dark:text-slate-500 cursor-not-allowed'
                    : '',
                typeof customClass === 'string' ? customClass : (customClass === true ? 'capitalize' : ''),
            ]" :placeholder="placeholder" />
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
