<script setup>
// ============================================================================
// Vista previa (solo lectura) de lo que hará addMes() al subir este PDF —
// mismo desglose que gestion.mes.preview, con detalle de CI+nombre por
// categoría para no tener que buscar manualmente entre cientos de personas.
// ============================================================================
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue';
import Modal from '@/components/Modal.vue';
import Icon from '@/components/Icon.vue';

const props = defineProps({
    mes: { type: [Number, String], default: null },
    año: { type: [Number, String], default: null },
    resumen: { type: Object, default: () => ({}) },
    detalle: { type: Object, default: () => ({}) },
    presupuestoSugerido: { type: Number, default: 0 },
});

const emit = defineEmits(['close']);

const closeOnEscape = (e) => {
    if (e.key === 'Escape') emit('close');
};
onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const MESES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];
const nombreMes = computed(() => MESES[Number(props.mes) - 1] ?? props.mes);

// Categorías en el mismo orden/keys que devuelve GestionController::previsualizarMes()
// — un color distinto por categoría (nunca se repite) para que se distingan
// de un vistazo sin necesidad de leer la etiqueta.
const CATEGORIAS = [
    { key: 'activos', label: 'Activos', nota: 'Se habilitan este mes', color: 'slate' },
    { key: 'nuevos', label: 'Nuevos beneficiarios', nota: 'Se registran y habilitan', color: 'sky' },
    { key: 'baja_temporal', label: 'Baja temporal', nota: 'Se omiten, no se habilitan', color: 'amber' },
    { key: 'baja_definitiva', label: 'Baja definitiva', nota: 'Se omiten, no se habilitan', color: 'orange' },
    { key: 'depurados_restauran_baja_temporal', label: 'Depurados → vuelven a baja temporal', nota: 'Reaparecen en el PDF', color: 'teal' },
    { key: 'depurados_restauran_fallecido', label: 'Depurados → fallecidos', nota: 'Reaparecen en el PDF', color: 'rose' },
    { key: 'conflicto_estado_mes', label: 'Conflicto de estado este mes', nota: 'Revisar manualmente', color: 'red' },
    { key: 'duplicados_en_pdf', label: 'CI duplicado en el PDF', nota: 'Solo se procesa la primera aparición', color: 'violet' },
    { key: 'conflicto_ci_nombre_distinto', label: 'CI repetido con nombre distinto', nota: 'Error del PDF — nadie se habilita, revisar manualmente', color: 'fuchsia' },
    { key: 'se_depuraran_por_ausencia', label: 'No aparecen en este PDF', nota: 'Se depuran automáticamente', color: 'pink' },
];

// Tonos suaves (fondo -50, texto -600, barra -300) — evita el efecto
// "semáforo" de colores muy saturados, más acorde a un sistema profesional.
const COLOR_CLASSES = {
    slate: {
        card: 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700',
        text: 'text-slate-600 dark:text-slate-300',
        badge: 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300',
        bar: 'bg-slate-300',
    },
    sky: {
        card: 'bg-sky-50 dark:bg-sky-900/10 border-sky-200 dark:border-sky-800',
        text: 'text-sky-600 dark:text-sky-400',
        badge: 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
        bar: 'bg-sky-300',
    },
    amber: {
        card: 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800',
        text: 'text-amber-600 dark:text-amber-400',
        badge: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
        bar: 'bg-amber-300',
    },
    orange: {
        card: 'bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800',
        text: 'text-orange-600 dark:text-orange-400',
        badge: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
        bar: 'bg-orange-300',
    },
    teal: {
        card: 'bg-teal-50 dark:bg-teal-900/10 border-teal-200 dark:border-teal-800',
        text: 'text-teal-600 dark:text-teal-400',
        badge: 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
        bar: 'bg-teal-300',
    },
    rose: {
        card: 'bg-rose-50 dark:bg-rose-900/10 border-rose-200 dark:border-rose-800',
        text: 'text-rose-500 dark:text-rose-400',
        badge: 'bg-rose-100 text-rose-500 dark:bg-rose-900/30 dark:text-rose-400',
        bar: 'bg-rose-300',
    },
    red: {
        card: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800',
        text: 'text-red-500 dark:text-red-400',
        badge: 'bg-red-100 text-red-500 dark:bg-red-900/30 dark:text-red-400',
        bar: 'bg-red-300',
    },
    violet: {
        card: 'bg-violet-50 dark:bg-violet-900/10 border-violet-200 dark:border-violet-800',
        text: 'text-violet-600 dark:text-violet-400',
        badge: 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
        bar: 'bg-violet-300',
    },
    fuchsia: {
        card: 'bg-fuchsia-50 dark:bg-fuchsia-900/10 border-fuchsia-200 dark:border-fuchsia-800',
        text: 'text-fuchsia-600 dark:text-fuchsia-400',
        badge: 'bg-fuchsia-100 text-fuchsia-600 dark:bg-fuchsia-900/30 dark:text-fuchsia-400',
        bar: 'bg-fuchsia-300',
    },
    pink: {
        card: 'bg-pink-50 dark:bg-pink-900/10 border-pink-200 dark:border-pink-800',
        text: 'text-pink-500 dark:text-pink-400',
        badge: 'bg-pink-100 text-pink-500 dark:bg-pink-900/30 dark:text-pink-400',
        bar: 'bg-pink-300',
    },
};

const LIMITE_SIN_BUSQUEDA = 200;

const busqueda = ref('');

const listaCategoria = (key) => props.detalle?.[key] ?? [];

// Abiertas por defecto solo las categorías que requieren revisión manual —
// activos/nuevos pueden ser cientos, no tiene sentido desplegarlos de entrada.
const mostrar = reactive(
    Object.fromEntries(CATEGORIAS.map((c) => [
        c.key,
        ['conflicto_estado_mes', 'duplicados_en_pdf', 'conflicto_ci_nombre_distinto'].includes(c.key) && listaCategoria(c.key).length > 0,
    ]))
);

const categoriasConDatos = computed(() => CATEGORIAS
    .map((c) => {
        const items = listaCategoria(c.key);
        const q = busqueda.value.trim().toLowerCase();
        const filtrados = q
            ? items.filter((p) => p.ci?.toString().includes(q) || p.nombre?.toLowerCase().includes(q))
            : items;
        const visibles = (!q && filtrados.length > LIMITE_SIN_BUSQUEDA)
            ? filtrados.slice(0, LIMITE_SIN_BUSQUEDA)
            : filtrados;

        return { ...c, colores: COLOR_CLASSES[c.color], items, filtrados, visibles };
    })
    .filter((c) => c.items.length > 0));

const totalResultadosBusqueda = computed(() =>
    categoriasConDatos.value.reduce((acc, c) => acc + c.filtrados.length, 0));

const formatCurrency = (valor) =>
    new Intl.NumberFormat('es-BO', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(valor ?? 0);

const ciCopiado = ref('');
const copiarCi = async (ci) => {
    try {
        await navigator.clipboard.writeText(String(ci));
        ciCopiado.value = ci;
        setTimeout(() => { if (ciCopiado.value === ci) ciCopiado.value = ''; }, 1200);
    } catch {
        // Portapapeles no disponible (permiso denegado, contexto no seguro) — no bloquea el flujo.
    }
};
</script>

<template>
    <Teleport to="body">
        <Modal maxWidth="max-w-2xl" styleBody="p-4 sm:px-5 py-4" :showFooter="false" @close="$emit('close')">
            <template #icon>
                <Icon :icon-button="true" name="fileCheck" class-name="text-white" :size="18" />
            </template>
            <template #label1>PDF {{ nombreMes }} {{ año }}</template>
            <template #label2>Desglose de los cambios que se realizarán al subir este PDF</template>

            <div class="space-y-3">
                <!-- Resumen compacto — auto-fit: las tarjetas se acoplan al contenido, -->
                <!-- sin dejar huecos vacíos cuando la última fila no completa la grilla -->
                <div class="grid gap-1.5" style="grid-template-columns: repeat(auto-fit, minmax(6.5rem, 1fr));">
                    <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600/40 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold">Total PDF</p>
                        <p class="text-base font-bold text-slate-700 dark:text-slate-200">{{ resumen.total_cis_pdf ?? 0 }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-800 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-emerald-600 uppercase tracking-wide font-semibold">Se habilitan</p>
                        <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ resumen.total_habilitables ?? 0 }}</p>
                    </div>
                    <div v-for="c in categoriasConDatos.filter(c => !['activos', 'nuevos'].includes(c.key))" :key="c.key"
                        class="rounded-lg border px-2 py-1.5 text-center" :class="c.colores.card">
                        <p class="text-[10px] uppercase tracking-wide font-semibold truncate" :class="c.colores.text" :title="c.label">{{ c.label }}</p>
                        <p class="text-base font-bold" :class="c.colores.text">{{ c.items.length }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between px-0.5">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Monto por persona: <span class="font-semibold">Bs. {{ formatCurrency(resumen.monto) }}</span>
                    </p>
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                        Presupuesto sugerido: Bs. {{ formatCurrency(presupuestoSugerido) }}
                    </p>
                </div>

                <!-- Buscador -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                        </svg>
                    </div>
                    <input v-model="busqueda" type="text" placeholder="Buscar por CI o nombre..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-gray-200 dark:placeholder-gray-500 transition-all" />
                    <button v-if="busqueda" @click="busqueda = ''"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <p v-if="busqueda && totalResultadosBusqueda === 0" class="text-center text-sm text-gray-400 dark:text-gray-500 py-4">
                    Sin resultados para "{{ busqueda }}"
                </p>

                <!-- Categorías -->
                <div class="space-y-2">
                    <div v-for="c in categoriasConDatos" :key="c.key" v-show="!busqueda || c.filtrados.length > 0"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button type="button" @click="mostrar[c.key] = !mostrar[c.key]"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-6 rounded-full flex-shrink-0" :class="c.colores.bar"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{{ c.label }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">{{ c.nota }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="c.colores.badge">
                                    {{ busqueda ? `${c.filtrados.length} de ` : '' }}{{ c.items.length }}
                                </span>
                                <svg :class="{ 'rotate-180': mostrar[c.key] }" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <ul v-show="mostrar[c.key] || busqueda" class="divide-y divide-gray-100 dark:divide-gray-700/50 max-h-56 overflow-y-auto">
                            <li v-for="p in (busqueda ? c.filtrados : c.visibles)" :key="p.ci"
                                class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-700 dark:text-slate-200 truncate">{{ p.nombre || '—' }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">CI: {{ p.ci }}</p>
                                </div>
                                <button type="button" @click="copiarCi(p.ci)"
                                    class="flex-shrink-0 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 text-slate-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    {{ ciCopiado === p.ci ? 'Copiado' : 'Copiar CI' }}
                                </button>
                            </li>
                        </ul>
                        <p v-if="!busqueda && c.filtrados.length > LIMITE_SIN_BUSQUEDA && mostrar[c.key]"
                            class="px-3 py-1.5 text-[11px] text-slate-400 dark:text-slate-500 border-t border-gray-100 dark:border-gray-700/50">
                            Mostrando {{ LIMITE_SIN_BUSQUEDA }} de {{ c.filtrados.length }} — usa el buscador para encontrar a alguien puntual.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="px-4 border-t border-gray-100 dark:border-gray-700/50 py-3">
                    <div class="flex justify-center">
                        <button type="button" @click="$emit('close')"
                            class="py-2 px-8 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium transition-colors">
                            Aceptar
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </Teleport>
</template>
