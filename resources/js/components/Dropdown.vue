<script setup>
import { computed, onMounted, onUnmounted, ref, watch, provide, nextTick } from 'vue';
import { useDropdownCoordinator } from '@/composables/useDropdownCoordinator';

const props = defineProps({
    align: {
        type: String,
        default: 'left'
    },
    width: {
        type: String,
        default: '48'
    },
    contentClasses: {
        type: String,
        default: 'rounded-lg bg-white dark:bg-gray-700'
    },
    // Opt-in: en vez de un ancho fijo (`width`), el contenido toma el mismo
    // ancho en píxeles que el trigger — para selects largos donde una lista
    // más angosta que el input se ve descuadrada (ver Presupuesto/Index.vue).
    matchTriggerWidth: {
        type: Boolean,
        default: false
    },
});

// Coordina que solo un Dropdown esté abierto a la vez en toda la app: al
// abrir este, si había otro abierto, ese se cierra solo (ver watch abajo).
const { activeDropdownId, id: dropdownId } = useDropdownCoordinator();

const open = ref(false);
const dropdownRef = ref(null);
const triggerRef = ref(null);
const dropdownStyle = ref({});

const closeDropdownLocal = () => {
    open.value = false;
    if (activeDropdownId.value === dropdownId) {
        activeDropdownId.value = null;
    }
};

watch(activeDropdownId, (val) => {
    if (val !== dropdownId && open.value) {
        open.value = false;
    }
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        closeDropdownLocal();
    }
};

const calculatePosition = () => {
    if (!triggerRef.value || !dropdownRef.value) return;

    const trigger = triggerRef.value.getBoundingClientRect();
    const dropdown = dropdownRef.value;
    const dw = props.matchTriggerWidth ? trigger.width : dropdown.offsetWidth;
    const dh = dropdown.offsetHeight;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const GAP = 4;
    const MARGIN = 8;

    // Vertical: preferir abajo, si no cabe ir arriba
    let top = trigger.bottom + GAP;
    if (top + dh > vh - MARGIN && trigger.top - dh - GAP > MARGIN) {
        top = trigger.top - dh - GAP;
    }
    // Clamp vertical
    top = Math.max(MARGIN, Math.min(top, vh - dh - MARGIN));

    // Horizontal según align
    let left;
    if (props.align === 'right') {
        left = trigger.right - dw;
    } else if (props.align === 'center') {
        left = trigger.left + trigger.width / 2 - dw / 2;
    } else {
        left = trigger.left;
    }
    // Clamp horizontal — nunca sale del viewport
    left = Math.max(MARGIN, Math.min(left, vw - dw - MARGIN));

    dropdownStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        ...(props.matchTriggerWidth ? { width: `${trigger.width}px` } : {}),
    };
};

const toggleDropdown = async () => {
    if (open.value) {
        closeDropdownLocal();
    } else {
        activeDropdownId.value = dropdownId;
        open.value = true;
    }
};

const closeDropdown = () => {
    closeDropdownLocal();
};

const handleContentClick = (e) => {
    const clickedElement = e.target.closest('[data-dropdown-item]') ||
        e.target.closest('li') ||
        e.target.closest('button') ||
        e.target.closest('a') ||
        e.target.closest('[role="option"]');

    if (clickedElement) {
        setTimeout(() => {
            closeDropdownLocal();
        }, 100);
    }
};

provide('closeDropdown', closeDropdown);

// Recalcula posición al abrir y en resize/scroll
watch(open, async (val) => {
    if (val) {
        await nextTick();
        const dropdown = dropdownRef.value;
        // Mide sin parpadeo visible
        const prevVisibility = dropdown.style.visibility;
        const prevDisplay = dropdown.style.display;
        dropdown.style.visibility = 'hidden';
        dropdown.style.display = 'block';
        // Fija el ancho ANTES de medir la altura (offsetHeight) — si no, la
        // altura se mide con el ancho de la clase CSS anterior y el texto
        // puede reflowear distinto una vez aplicado el ancho real.
        if (props.matchTriggerWidth && triggerRef.value) {
            dropdown.style.width = `${triggerRef.value.getBoundingClientRect().width}px`;
        }
        calculatePosition();
        dropdown.style.visibility = prevVisibility;
        dropdown.style.display = prevDisplay;
    }
});

const alignClass = computed(() => ({
    left: 'left-0',
    right: 'right-0',
    center: 'left-1/2 -translate-x-1/2',
}[props.align] || 'left-0'));

const handleReposition = () => {
    if (open.value) calculatePosition();
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    window.addEventListener('resize', handleReposition);
    window.addEventListener('scroll', handleReposition, true);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    window.removeEventListener('resize', handleReposition);
    window.removeEventListener('scroll', handleReposition, true);
    if (activeDropdownId.value === dropdownId) {
        activeDropdownId.value = null;
    }
});

const widthClass = computed(() => ({
    48: 'w-48',
    60: 'w-60',
    full: 'w-full'
}[props.width] || 'w-48'));

defineExpose({ closeDropdown });
</script>

<template>
    <div class="relative" ref="triggerRef">
        <div @click="toggleDropdown">
            <slot name="trigger" :open="open" />
        </div>

        <!-- Teleport saca el backdrop y el dropdown del árbol DOM: evita que un
             ancestro con `transform` (ej. hover:-translate-y de un card) los
             confine a su propio tamaño en vez de cubrir toda la pantalla. -->
        <Teleport to="body">
            <div v-show="open" class="fixed inset-0 z-40" @click="closeDropdownLocal"></div>

            <Transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <div
                    v-show="open"
                    ref="dropdownRef"
                    class="fixed z-50"
                    :class="widthClass"
                    :style="dropdownStyle"
                    @click="handleContentClick"
                >
                    <div :class="contentClasses" class="shadow-lg border border-gray-200 dark:border-gray-600">
                        <slot name="content" />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
