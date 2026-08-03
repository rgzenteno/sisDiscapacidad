<script setup>
// ============================================================================
// IMPORTS
// ============================================================================
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

/**
 * Componentes
 */
import AppLayout from '@/Layouts/AppLayout.vue';
import Rutas from '@/components/Rutas.vue';
import Button from '@/components/Button.vue';

// ============================================================================
// PROPS
// ============================================================================
const props = defineProps({
    logos: {
        type: Object,
        required: true,
    },
    parametros: {
        type: Array,
        default: () => [],
    },
});

const mensajes = ref([]);

const mostrarMensaje = (tipo, titulo, texto) => {
    mensajes.value.push({
        id: Date.now() + Math.random(),
        tipo,
        contenido: [{ header: titulo, text: texto }],
    });
};

const cerrarMensaje = (id) => {
    mensajes.value = mensajes.value.filter((m) => m.id !== id);
};

// ============================================================================
// DEFINICIÓN DE LOS LOGOS EDITABLES
// ============================================================================
const definiciones = [
    { clave: 'sacaba', titulo: 'Logo Izquierdo (sacaba.png)', descripcion: 'Aparece a la izquierda del encabezado en todos los reportes PDF y Excel.' },
    { clave: 'sigamos', titulo: 'Logo Derecho (sigamos.png)', descripcion: 'Aparece a la derecha del encabezado en todos los reportes PDF y Excel.' },
    { clave: 'logo', titulo: 'Logo del Sistema (logo.png)', descripcion: 'Ícono de pestaña del navegador (favicon) y logo de la pantalla de carga.' },
];

// Estado por logo: archivo seleccionado, preview local, form de Inertia
const estado = {};
for (const { clave } of definiciones) {
    estado[clave] = {
        fileInput: ref(null),
        selectedFile: ref(null),
        previewUrl: ref(null),
        form: useForm({ logo: clave, archivo: null }),
    };
}

const triggerFileInput = (clave) => {
    estado[clave].fileInput.value?.click();
};

const handleFileSelect = (clave, event) => {
    const file = event.target.files[0];
    if (!file) return;
    processFile(clave, file);
};

const processFile = (clave, file) => {
    if (!file.type.includes('png')) {
        mostrarMensaje('error', 'Formato inválido', 'El logo debe ser un archivo PNG.');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        mostrarMensaje('error', 'Archivo muy grande', 'El logo no puede superar los 2MB.');
        return;
    }

    const e = estado[clave];
    e.selectedFile.value = file;
    e.form.archivo = file;

    const reader = new FileReader();
    reader.onload = (ev) => { e.previewUrl.value = ev.target.result; };
    reader.readAsDataURL(file);
};

const guardar = (clave) => {
    const e = estado[clave];
    e.form.post(route('configuracion.logo.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            e.selectedFile.value = null;
            e.previewUrl.value = null;
            if (e.fileInput.value) e.fileInput.value.value = '';
            mostrarMensaje('correcto', 'Logo actualizado', 'El logo se reemplazó correctamente. Los próximos reportes ya lo usarán.');
        },
        onError: () => {
            mostrarMensaje('error', 'Error', 'No se pudo actualizar el logo. Verifica el archivo e intenta nuevamente.');
        },
    });
};

// ============================================================================
// PARÁMETROS DEL SISTEMA (tabla genérica clave/valor — ej. Monto - Persona)
// ============================================================================
// Un form de Inertia por parámetro, armado una sola vez (igual que `estado`
// para los logos) para no perder lo que el usuario esté editando si la
// página se re-renderiza.
const parametroForms = {};
for (const p of props.parametros) {
    parametroForms[p.id] = useForm({ valor: p.valor ?? '' });
}

// "Monto - Persona" es siempre un monto numérico — no se deja escribir
// letras (la validación real y definitiva sigue estando en el backend,
// esto es solo para no dejar que el usuario ni siquiera lo intente).
const soloNumeros = (event) => {
    if (!/[0-9.]/.test(event.key)) {
        event.preventDefault();
    }
};

const guardarParametro = (parametro) => {
    const form = parametroForms[parametro.id];
    form.post(route('configuracion.parametro.update', parametro.id), {
        preserveScroll: true,
        onSuccess: () => {
            mostrarMensaje('correcto', 'Parámetro actualizado', `"${parametro.tipo}" se actualizó correctamente.`);
        },
        onError: () => {
            mostrarMensaje('error', 'Error', 'No se pudo actualizar el parámetro. Intenta nuevamente.');
        },
    });
};
</script>

<template>
    <AppLayout :mensajes="mensajes" @cerrarMensaje="cerrarMensaje">

        <!-- ============================================================================ -->
        <!-- ENCABEZADO DE PÁGINA -->
        <!-- ============================================================================ -->
        <div class="px-1 py-1 sm:py-3 sm:px-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
            <h1 class="font-semibold text-xl sm:text-2xl">Configuración</h1>
            <Rutas label1="Inicio" label3="Configuración" class="sm:text-xs" />
        </div>

        <!-- ============================================================================ -->
        <!-- CONTENIDO -->
        <!-- ============================================================================ -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white rounded-lg pt-3 mr-1 mb-1.5 dark:bg-gray-800">
            <div class="p-2 sm:p-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-2xl">
                    Los logos que se suban acá se guardan siempre con el mismo nombre de
                    archivo (<code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">sacaba.png</code> /
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">sigamos.png</code> /
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">logo.png</code>), así que se
                    actualizan automáticamente en los reportes PDF/Excel y en el favicon/pantalla de carga del
                    sistema.
                </p>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <section v-for="def in definiciones" :key="def.clave"
                        class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ def.titulo }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ def.descripcion }}</p>
                        </header>

                        <div class="mt-4">
                            <div class="relative">
                                <div @click="triggerFileInput(def.clave)"
                                    class="border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500">

                                    <!-- Preview del archivo recién elegido -->
                                    <div v-if="estado[def.clave].previewUrl.value" class="mb-4">
                                        <img :src="estado[def.clave].previewUrl.value" alt="Vista previa"
                                            class="max-h-32 mx-auto border border-gray-200 rounded bg-white p-2" />
                                    </div>

                                    <!-- Logo actual guardado en el servidor -->
                                    <div v-else-if="props.logos[def.clave]?.url" class="mb-4">
                                        <img :src="props.logos[def.clave].url" alt="Logo actual"
                                            class="max-h-32 mx-auto border border-gray-200 rounded bg-white p-2" />
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Actual — actualizado el {{ props.logos[def.clave].actualizado }}
                                        </p>
                                    </div>

                                    <!-- Sin logo cargado -->
                                    <div v-else>
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                            viewBox="0 0 48 48">
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Sin logo cargado</p>
                                    </div>

                                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                        <p class="font-semibold">
                                            {{ estado[def.clave].selectedFile.value?.name ||
                                                'Haz clic para seleccionar un archivo PNG' }}
                                        </p>
                                        <p class="mt-1 text-xs">PNG hasta 2MB</p>
                                    </div>

                                    <input :ref="el => estado[def.clave].fileInput.value = el" type="file"
                                        accept="image/png" @change="(e) => handleFileSelect(def.clave, e)"
                                        class="hidden" />
                                </div>
                            </div>

                            <p v-if="estado[def.clave].form.errors.archivo" class="mt-2 text-sm text-red-600">
                                {{ estado[def.clave].form.errors.archivo }}
                            </p>

                            <div class="mt-4 flex items-center gap-4">
                                <Button
                                    :disabled="estado[def.clave].form.processing || !estado[def.clave].selectedFile.value"
                                    :style="'px-4 py-2 rounded-md'"
                                    class="bg-[rgb(var(--brand-600))] text-white font-semibold text-sm hover:bg-[rgb(var(--brand-700))] disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="guardar(def.clave)">
                                    {{ estado[def.clave].form.processing ? 'Guardando...' : 'Guardar logo' }}
                                </Button>

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-if="estado[def.clave].form.recentlySuccessful" class="text-sm text-green-600">
                                        Guardado correctamente.
                                    </p>
                                </Transition>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ============================================================================ -->
                <!-- PARÁMETROS DEL SISTEMA -->
                <!-- ============================================================================ -->
                <div class="mt-8">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Parámetros del sistema</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 max-w-2xl">
                        Valores generales usados en distintas partes del sistema.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <section v-for="parametro in parametros" :key="parametro.id"
                            class="bg-white p-4 shadow-sm border rounded-lg sm:p-6 dark:bg-gray-800 dark:border-gray-700">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white">
                                {{ parametro.tipo }}
                            </label>

                            <div class="mt-3 flex items-center gap-3">
                                <input v-if="parametro.tipo === 'Monto - Persona'"
                                    v-model="parametroForms[parametro.id].valor" type="text" inputmode="decimal"
                                    @keypress="soloNumeros"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Sin configurar" />

                                <input v-else-if="parametro.tipo === 'Color - Sistema'"
                                    v-model="parametroForms[parametro.id].valor" type="color"
                                    class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600 p-0.5 bg-white dark:bg-gray-700" />

                                <input v-else v-model="parametroForms[parametro.id].valor" type="text"
                                    class="block w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Sin configurar" />

                                <Button :disabled="parametroForms[parametro.id].processing"
                                    :style="'px-4 py-2 rounded-md whitespace-nowrap'"
                                    class="bg-[rgb(var(--brand-600))] text-white font-semibold text-sm hover:bg-[rgb(var(--brand-700))] disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="guardarParametro(parametro)">
                                    {{ parametroForms[parametro.id].processing ? 'Guardando...' : 'Guardar' }}
                                </Button>
                            </div>

                            <p v-if="parametroForms[parametro.id].errors.valor" class="mt-2 text-sm text-red-600">
                                {{ parametroForms[parametro.id].errors.valor }}
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
