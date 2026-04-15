<script setup>
// ============ INICIO IMPORTS ============ //
import { computed } from 'vue'
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    validation: {
        type: Object,
        required: true
    }
})
// ============ FIN PROPS ============ //

// ============ INICIO UTILS ============ //
const MESES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
]

const formatGestion = (gestion) => {
    if (!gestion?.mes || !gestion?.año) return null
    return `${MESES[gestion.mes - 1]} ${gestion.año}`
}
// ============ FIN UTILS ============ //

// ============ INICIO COMPUTEDS ============ //

// Gestión extraída del PDF en formato legible (ej: "Diciembre 2025")
const gestionPDF = computed(() => formatGestion(props.validation.extractedData?.gestion))

// Gestión esperada (solo presente cuando hay mismatch)
const gestionEsperada = computed(() => formatGestion(props.validation.extractedData?.gestionEsperada))

// Unifica missingColumns y missingFields en un solo array
const allMissingItems = computed(() =>
    [...(props.validation.missingColumns ?? []), ...(props.validation.missingFields ?? [])]
)

// ¿Hay contenido para el body?
const hasDetails = computed(() =>
    gestionPDF.value ||
    props.validation.totalRows > 0 ||
    props.validation.foundColumns?.length ||
    allMissingItems.value.length
)

// Estilos reactivos según estado (isValid: true | false | null)
const statusStyles = computed(() => {
    if (props.validation.isValid === true) return {
        card:   'border-emerald-200 dark:border-emerald-700',
        header: 'bg-emerald-50 dark:bg-emerald-900/20',
        body:   'bg-white dark:bg-gray-900 border-emerald-100 dark:border-emerald-800',
        iconBg: 'bg-emerald-100 dark:bg-emerald-800/50',
        icon:   'text-emerald-600 dark:text-emerald-400',
        text:   'text-emerald-700 dark:text-emerald-300',
    }
    if (props.validation.isValid === false) return {
        card:   'border-red-200 dark:border-red-700',
        header: 'bg-red-50 dark:bg-red-900/20',
        body:   'bg-white dark:bg-gray-900 border-red-100 dark:border-red-800',
        iconBg: 'bg-red-100 dark:bg-red-800/50',
        icon:   'text-red-600 dark:text-red-400',
        text:   'text-red-700 dark:text-red-300',
    }
    // null → cargando
    return {
        card:   'border-blue-200 dark:border-blue-700',
        header: 'bg-blue-50 dark:bg-blue-900/20',
        body:   'bg-white dark:bg-gray-900 border-blue-100 dark:border-blue-800',
        iconBg: 'bg-blue-100 dark:bg-blue-800/50',
        icon:   'text-blue-600 dark:text-blue-400',
        text:   'text-blue-700 dark:text-blue-300',
    }
})
// ============ FIN COMPUTEDS ============ //
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.25s ease-out; }
.slide-fade-leave-active { transition: all 0.2s ease-in; }
.slide-fade-enter-from   { opacity: 0; transform: translateY(-6px); }
.slide-fade-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>

<template>
    <Transition name="slide-fade">
        <div v-if="validation.message" class="mt-3 rounded-xl border shadow-sm overflow-hidden"
            :class="statusStyles.card">

            <!-- ===== HEADER: icono + mensaje principal ===== -->
            <div class="px-4 py-3 flex items-center gap-3" :class="statusStyles.header">

                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                    :class="statusStyles.iconBg">

                    <!-- Éxito -->
                    <svg v-if="validation.isValid === true" class="w-4 h-4" :class="statusStyles.icon"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>

                    <!-- Error -->
                    <svg v-else-if="validation.isValid === false" class="w-4 h-4" :class="statusStyles.icon"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>

                    <!-- Cargando -->
                    <svg v-else class="w-4 h-4 animate-spin" :class="statusStyles.icon"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </div>

                <p class="text-sm font-semibold" :class="statusStyles.text">
                    {{ validation.message }}
                </p>
            </div>

            <!-- ===== BODY: detalles (solo si hay algo que mostrar) ===== -->
            <div v-if="hasDetails" class="px-4 py-3 space-y-3 border-t" :class="statusStyles.body">

                <!-- Gestión del PDF + total de registros (estado válido) -->
                <div v-if="gestionPDF && validation.isValid === true"
                    class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 dark:text-gray-500 uppercase tracking-wide font-medium">
                            Mes del PDF
                        </span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ gestionPDF }}
                        </span>
                        <!-- check inline de que coincide con lo esperado -->
                        <span class="text-emerald-600 dark:text-emerald-400 text-xs">✓ Correcto</span>
                    </div>

                    <!-- Total de registros en la misma línea -->
                    <div v-if="validation.totalRows > 0"
                        class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ validation.totalRows }}
                        </span> registros
                    </div>
                </div>

                <!-- Mismatch: mes del PDF vs mes esperado -->
                <div v-if="gestionPDF && gestionEsperada" class="space-y-1.5">
                    <div class="flex items-center gap-3 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500 dark:text-gray-500 uppercase tracking-wide font-medium">
                                PDF
                            </span>
                            <span class="font-mono bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700 rounded px-2 py-0.5">
                                {{ gestionPDF }}
                            </span>
                        </div>
                        <span class="text-gray-400">→</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500 dark:text-gray-500 uppercase tracking-wide font-medium">
                                Esperado
                            </span>
                            <span class="font-mono bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5">
                                {{ gestionEsperada }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Columnas encontradas -->
                <div v-if="validation.foundColumns?.length" class="space-y-1.5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-500 uppercase tracking-wide">
                        Columnas encontradas
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="col in validation.foundColumns" :key="col"
                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                            {{ col }}
                        </span>
                    </div>
                </div>

                <!-- Campos/columnas faltantes (missingFields + missingColumns unificados) -->
                <div v-if="allMissingItems.length" class="space-y-1.5">
                    <p class="text-xs font-medium text-red-500 dark:text-red-400 uppercase tracking-wide">
                        {{ validation.missingFields?.length ? 'Campos faltantes en PDF' : 'Columnas faltantes' }}
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="item in allMissingItems" :key="item"
                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border border-red-200 dark:border-red-700">
                            {{ item }}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </Transition>
</template>
