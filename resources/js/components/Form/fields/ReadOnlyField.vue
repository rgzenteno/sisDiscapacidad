<script setup>

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number],
        default: ''
    },
    props: {
        type: Object,
        required: true
    },
    nombreFor: {
        type: String,
        default: ''
    },
    apellidoFor: {
        type: String,
        default: ''
    },
    ciFor: {
        type: String,
        default: null
    },
});


const getInitials = (nombre, apellido) => {
    const n = nombre?.charAt(0)?.toUpperCase() ?? ''
    const a = apellido?.charAt(0)?.toUpperCase() ?? ''
    return `${n}${a}` || '??'
}

const toTitleCase = str =>
    str
        ? str
            .toLocaleLowerCase('es')
            .replace(/(^|\s)\S/g, l => l.toLocaleUpperCase('es'))
        : ''
</script>

<template>
    <div class="w-full">

        <!-- Input de solo lectura para id_persona -->
        <!-- Avatar + nombre del beneficiario -->
        <div
            class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700/40 rounded-xl px-4 py-2">
            <div
                class="w-11 h-11 rounded-full bg-gradient-to-br from-indigo-500 to-cyan-400 flex items-center justify-center shadow-md flex-shrink-0">
                <span class="text-white font-bold text-sm uppercase">
                    {{ getInitials(nombreFor, apellidoFor) }}
                </span>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Beneficiario</p>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200 capitalize truncate">
                    {{ toTitleCase(nombreFor) }}
                    {{ toTitleCase(apellidoFor) }}
                </p>
                <p class="text-xs text-indigo-500 dark:text-indigo-300">
                    CI: {{ ciFor }}
                </p>
            </div>
        </div>

        <!-- Separador: Estado -->
        <div class="flex items-center gap-2 mt-2">
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
            </div>
            <div class="flex items-center justify-center">
                <span class="text-sm text-slate-400">Datos del Carnet Dis.</span>
            </div>
            <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-500 to-transparent">
            </div>
        </div>
        <!-- <input v-if="field.typeInput === 'id'" :value="nombreFor"
            class="w-full cursor-not-allowed capitalize font-bold bg-[rgb(var(--brand-100))] border border-[rgb(var(--brand-400))] text-[rgb(var(--brand-500))] text-sm rounded-xl px-2 py-2.5 focus:outline-none focus:ring-0 focus:border-[rgb(var(--brand-400))]"
            readonly /> -->

        <!-- Input de solo lectura para verificar (fecha_vencimiento, etc) -->
        <input v-if="field.typeInput === 'verificar'" type="text" :id="field.name" :value="modelValue"
            :placeholder="field.placeholder || 'Ingrese ' + field.placeholder"
            class="w-full cursor-not-allowed bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500"
            readonly disabled />
    </div>
</template>
