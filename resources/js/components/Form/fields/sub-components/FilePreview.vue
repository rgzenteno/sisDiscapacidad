<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { computed, ref } from 'vue';

/**
 * Componentes
 */
import Icon from '@/components/Icon.vue';

// ============================================================================
// PROPS Y EMITS
// ============================================================================
const props = defineProps({
    fileName: String,
    file: Object,
    validation: {
        type: Object,
        default: () => ({})
    }
});

defineEmits(['remove']);

// ============================================================================
// REFS
// ============================================================================
const showDetails = ref(false);

// ============================================================================
// COMPUTED
// ============================================================================

/**
 * Obtiene la gestión detectada en el archivo para comparar con la esperada
 */
const gestionMismatchPDF = computed(() => {
    const g = props.validation.extractedData?.gestion;
    if (!g) return props.validation.gestionPDF ?? null;
    if (typeof g === 'string') return g;
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return `${meses[(g.mes ?? 1) - 1]} ${g.año}`;
});

/**
 * Obtiene la gestión esperada para comparar con la detectada en el archivo
 */
const gestionMismatchEsperada = computed(() => {
    const g = props.validation.extractedData?.gestionEsperada;
    if (!g) return props.validation.gestionEsperada ?? null;
    if (typeof g === 'string') return g;
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return `${meses[(g.mes ?? 1) - 1]} ${g.año}`;
});

/**
 * Determina si hay detalles de validación disponibles para mostrar en el
 * panel expandible — solo cuando hay algo que revisar (columnas faltantes o
 * un mes que no coincide con el esperado). Un PDF válido y correcto no
 * necesita flechita ni panel: "PDF válido" ya lo dice todo — el desglose
 * completo (registros, activos, bajas, etc.) vive en el modal "Ver detalle".
 */
const hasDetails = computed(() =>
    !!(
        props.validation.foundColumns?.length ||
        props.validation.missingColumns?.length ||
        props.validation.missingFields?.length ||
        gestionMismatchEsperada.value
    )
);
</script>

<template>
    <div class="rounded-xl border overflow-hidden" :class="[
        validation.isValid === true
            ? 'border-emerald-200 dark:border-emerald-800'
            : validation.isValid === false
                ? 'border-red-200 dark:border-red-800'
                : 'border-gray-200 dark:border-gray-700'
        ]">

        <!-- Fila superior: ícono + nombre + botón remover -->
        <div class="flex items-center gap-3 px-4 py-3">
            <!-- Ícono de archivo -->
            <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="[
                validation.isValid === true ? 'bg-emerald-50 dark:bg-emerald-900/30'
                    : validation.isValid === false ? 'bg-red-50 dark:bg-red-900/30'
                        : 'bg-gray-100 dark:bg-gray-800'
            ]">
                <Icon :icon-button="true" name="filePDF"
                    :class-name="validation.isValid === true ? 'text-emerald-500' : validation.isValid === false ? 'text-red-500' : 'text-gray-400'"
                    :size="20" />
            </div>

            <!-- Nombre -->
            <p class="flex-1 min-w-0 text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                {{ fileName }}
            </p>

            <!-- Botón remover -->
            <button @click="$emit('remove')" type="button" class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center text-gray-400
               hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <!-- Sección de validación (misma lógica que FileValidation.vue) -->
        <Transition name="slide-fade">
            <div v-if="validation.message" class="border-t" :class="[
                validation.isValid === true
                    ? 'border-emerald-100 dark:border-emerald-900'
                    : validation.isValid === false
                        ? 'border-red-100 dark:border-red-900'
                        : 'border-gray-100 dark:border-gray-800'
            ]">

                <!-- Header de validación -->
                <button v-if="hasDetails" type="button" @click="showDetails = !showDetails"
                    class="w-full px-4 py-2 flex items-center gap-2.5 transition-colors" :class="[
                        validation.isValid === true ? 'hover:bg-emerald-50/80 dark:hover:bg-emerald-900/20'
                            : validation.isValid === false ? 'hover:bg-red-50/80 dark:hover:bg-red-900/20'
                                : ''
                    ]">
                    <!-- Dot de estado -->
                    <span class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center" :class="[
                        validation.isValid === true ? 'bg-emerald-100 dark:bg-emerald-900/50'
                            : validation.isValid === false ? 'bg-red-100 dark:bg-red-900/50'
                                : 'bg-gray-200 dark:bg-gray-700'
                    ]">
                        <svg v-if="validation.isValid === true" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"
                            fill="none" stroke="currentColor" viewBox="0 0 12 12">
                            <path d="M2 6l3 3 5-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <svg v-else-if="validation.isValid === false" class="w-3 h-3 text-red-500" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                        <svg v-else class="w-3 h-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60"
                                stroke-dashoffset="20" />
                        </svg>
                    </span>

                    <!-- Mensaje -->
                    <span class="text-sm font-medium flex-1 text-left" :class="[
                        validation.isValid === true ? 'text-emerald-700 dark:text-emerald-300'
                            : validation.isValid === false ? 'text-red-600 dark:text-red-400'
                                : 'text-gray-500 dark:text-gray-400'
                    ]">{{ validation.message }}</span>

                    <!-- Badges resumen (solo cuando está colapsado) -->
                    <!-- <template v-if="!showDetails && validation.isValid === true">
            <span v-if="validation.totalRows > 0" class="text-[11px] font-medium px-2 py-0.5 rounded-full border
             bg-emerald-50 text-emerald-800 border-emerald-200
             dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
              {{ validation.totalRows }} registros
            </span>
            <span v-if="validation.extractedData?.gestion" class="text-[11px] font-medium px-2 py-0.5 rounded-full border
             bg-emerald-50 text-emerald-800 border-emerald-200
             dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
              {{ gestionLabel }}
            </span>
          </template> -->



                    <!-- Chevron -->
                    <svg class="flex-shrink-0 w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
                        :class="showDetails ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>

                <!-- Sin detalles: solo row estático -->
                <div v-else class="px-4 py-2 flex items-center gap-2.5">
                    <span class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center" :class="[
                        validation.isValid === true ? 'bg-emerald-100 dark:bg-emerald-900/50'
                            : validation.isValid === false ? 'bg-red-100 dark:bg-red-900/50'
                                : 'bg-gray-200 dark:bg-gray-700'
                    ]">
                        <svg v-if="validation.isValid === true" class="w-3 h-3 text-emerald-600 dark:text-emerald-400"
                            fill="none" stroke="currentColor" viewBox="0 0 12 12">
                            <path d="M2 6l3 3 5-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <svg v-else-if="validation.isValid === false" class="w-3 h-3 text-red-500" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                        <svg v-else class="w-3 h-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60"
                                stroke-dashoffset="20" />
                        </svg>
                    </span>
                    <span class="text-sm font-medium" :class="[
                        validation.isValid === true ? 'text-emerald-700 dark:text-emerald-300'
                            : validation.isValid === false ? 'text-red-600 dark:text-red-400'
                                : 'text-gray-500 dark:text-gray-400'
                    ]">{{ validation.message }}</span>
                </div>

                <!-- Body: detalles adicionales -->
                <div v-show="showDetails" v-if="hasDetails" class="px-4 py-3 space-y-3 border-t" :class="[
                    validation.isValid === true ? 'border-emerald-100 dark:border-emerald-900'
                        : 'border-red-100 dark:border-red-900'
                ]">


                    <!-- Mismatch gestión (solo en error, siempre visible) -->
                    <div v-if="gestionMismatchPDF && gestionMismatchEsperada" class="flex flex-col gap-1">
                        <span class="text-xs text-red-600">El mes del PDF no coincide con la gestión actual</span>

                        <!-- Mismatch row -->
                        <div class="flex justify-center items-center gap-2">
                            <!-- Valor recibido (incorrecto) -->
                            <span class="inline-flex items-center text-[11px] font-mono px-2 py-1 rounded-md
                        bg-red-100 text-red-700 border border-red-200
                        dark:bg-red-900/30 dark:text-red-300 dark:border-red-700/50
                        line-through decoration-red-400/70">
                                {{ gestionMismatchPDF }}
                            </span>

                            <!-- Flecha -->
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="text-gray-400 dark:text-gray-500 shrink-0">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>

                            <!-- Valor esperado (correcto) -->
                            <span class="inline-flex items-center text-[11px] font-mono px-2 py-1 rounded-md
                        bg-emerald-50 text-emerald-700 border border-emerald-200
                        dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-700/50">
                                {{ gestionMismatchEsperada }}
                            </span>
                        </div>
                    </div>

                    <!-- Columnas encontradas -->
                    <div v-if="validation.foundColumns?.length" class="space-y-1.5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Columnas encontradas
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="col in validation.foundColumns" :key="col" class="px-2 py-0.5 rounded-md text-xs font-medium
                        bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                        border border-emerald-200 dark:border-emerald-700">
                                {{ col }}
                            </span>
                        </div>
                    </div>

                    <!-- Campos/columnas faltantes -->
                    <div v-if="validation.missingColumns?.length || validation.missingFields?.length"
                        class="space-y-1.5">
                        <p class="text-xs font-medium text-red-500 uppercase tracking-wide">
                            {{ validation.missingFields?.length ? 'Columnas necesarias en el PDF'
                                : 'Columnas faltantes' }}
                        </p>
                        <div class="flex justify-center items-center gap-2">
                            <span v-for="item in (validation.missingFields ?? validation.missingColumns)" :key="item"
                                class="px-1 py-0.5 rounded-md text-xs font-medium
                        capitalize bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                        border border-red-200 dark:border-red-700">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
