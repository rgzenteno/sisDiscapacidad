<script setup>
import { computed } from 'vue'

const props = defineProps({
    validation: { type: Object, required: true }
})

const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const formatGestion = (g) => g?.mes && g?.año ? `${MESES[g.mes - 1]} ${g.año}` : null

const gestionPDF = computed(() => formatGestion(props.validation.extractedData?.gestion))
const gestionEsperada = computed(() => formatGestion(props.validation.extractedData?.gestionEsperada))
const allMissingItems = computed(() => [...(props.validation.missingColumns ?? []), ...(props.validation.missingFields ?? [])])
const hasBody = computed(() =>
    (gestionPDF.value && props.validation.isValid === true) ||
    props.validation.foundColumns?.length ||
    allMissingItems.value.length
)

const s = computed(() => {
    if (props.validation.isValid === true) return {
        card: 'border-emerald-200 dark:border-emerald-700',
        header: 'bg-emerald-50 dark:bg-emerald-900/20',
        body: 'bg-white dark:bg-gray-900 border-emerald-100 dark:border-emerald-800',
        iconBg: 'bg-emerald-100 dark:bg-emerald-800/50',
        icon: 'text-emerald-600 dark:text-emerald-400',
        text: 'text-emerald-700 dark:text-emerald-300',
    }
    if (props.validation.isValid === false) return {
        card: 'border-red-200 dark:border-red-700',
        header: 'bg-red-50 dark:bg-red-900/20',
        body: 'bg-white dark:bg-gray-900 border-red-100 dark:border-red-800',
        iconBg: 'bg-red-100 dark:bg-red-800/50',
        icon: 'text-red-600 dark:text-red-400',
        text: 'text-red-700 dark:text-red-300',
    }
    return {
        card: 'border-blue-200 dark:border-blue-700',
        header: 'bg-blue-50 dark:bg-blue-900/20',
        body: 'bg-white dark:bg-gray-900 border-blue-100 dark:border-blue-800',
        iconBg: 'bg-blue-100 dark:bg-blue-800/50',
        icon: 'text-blue-600 dark:text-blue-400',
        text: 'text-blue-700 dark:text-blue-300',
    }
})
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.25s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from {
    opacity: 0;
    transform: translateY(-6px);
}

.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>

<template>
    <Transition name="slide-fade">
        <div v-if="validation.message" class="mt-1 rounded-xl border shadow-sm overflow-hidden" :class="s.card">

            <!-- HEADER -->
            <div class="px-4 py-0 flex items-center gap-3" :class="s.header">

                <!-- Icono -->
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center" :class="s.iconBg">
                    <!-- Éxito -->
                    <svg v-if="validation.isValid === true" class="w-4 h-4" :class="s.icon" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <!-- Error -->
                    <svg v-else-if="validation.isValid === false" class="w-4 h-4" :class="s.icon" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <!-- Cargando -->
                    <svg v-else class="w-4 h-4 animate-spin" :class="s.icon" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </div>

                <!-- Mensaje + mismatch inline -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold" :class="s.text">
                        {{ validation.message }}
                    </p>
                    <!-- Mismatch de gestión: justo debajo del mensaje -->
                    <div v-if="gestionPDF && gestionEsperada" class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <span class="text-xs font-mono bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                            border border-red-200 dark:border-red-700 rounded px-2 py-0.5">
                            PDF: {{ gestionPDF }}
                        </span>
                        <span class="text-xs text-gray-400">→</span>
                        <span class="text-xs font-mono bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300
                            border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5">
                            Esperado: {{ gestionEsperada }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- BODY: solo cuando hay detalles adicionales -->
            <div v-if="hasBody" class="px-4 py-3 space-y-3 border-t" :class="s.body">

                <!-- Mes correcto + total registros -->
                <div v-if="gestionPDF && validation.isValid === true"
                    class="flex items-center justify-between px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <!-- Left: PDF month info -->
                    <div class="flex items-center gap-3">
                        <!-- Status dot -->
                        <span
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex-shrink-0">
                            <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 12 12" fill="none">
                                <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>

                        <!-- Label + Value -->
                        <div class="flex items-center gap-1.5">
                            <span
                                class="text-[11px] uppercase tracking-widest font-semibold text-gray-400 dark:text-gray-500 leading-none">
                                Mes del PDF
                            </span>
                            <span class="text-[11px] text-gray-300 dark:text-gray-600 select-none">·</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-none">
                                {{ gestionPDF }}
                            </span>
                        </div>

                        <!-- Valid badge -->
                        <span
                            class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-full leading-none">
                            Correcto
                        </span>
                    </div>

                    <!-- Right: Row count -->
                    <div v-if="validation.totalRows > 0"
                        class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-gray-700 dark:text-gray-200 tabular-nums">
                            {{ validation.totalRows }}
                        </span>
                        <span>registros</span>
                    </div>
                </div>

                <!-- Columnas encontradas -->
                <div v-if="validation.foundColumns?.length" class="space-y-1.5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Columnas encontradas</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="col in validation.foundColumns" :key="col"
                            class="px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800
                            dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                            {{ col }}
                        </span>
                    </div>
                </div>

                <!-- Campos faltantes -->
                <div v-if="allMissingItems.length" class="space-y-1.5">
                    <p class="text-xs font-medium text-red-500 uppercase tracking-wide">
                        {{ validation.missingFields?.length ? 'Campos faltantes en PDF' : 'Columnas faltantes' }}
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="item in allMissingItems" :key="item" class="px-2 py-0.5 rounded-md text-xs font-medium bg-red-100 text-red-800
                            dark:bg-red-900/40 dark:text-red-300 border border-red-200 dark:border-red-700">
                            {{ item }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
