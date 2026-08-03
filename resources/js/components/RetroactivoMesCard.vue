<script setup>
// ============================================================================
// Franja de retroactivo en la mitad inferior del card de un mes normal.
// Siempre muestra una sola línea de estado (mismo alto en todos los cards);
// el detalle o la carga del archivo se abren en un modal aparte, separado
// del modal de datos del mes normal.
// ============================================================================
import { computed, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/components/Modal.vue';
import Icon from '@/components/Icon.vue';
import Input from '@/components/Input.vue';
import FileDropzone from '@/components/Form/fields/sub-components/FileDropzone.vue';
import FilePreview from '@/components/Form/fields/sub-components/FilePreview.vue';
import { Preloader } from '@/composables/usePreloader';
import { can } from '@/lib/can';

const props = defineProps({
    idGestion: { type: [Number, String], required: true },
    gestion: { type: [Number, String], required: true },
    posicion: { type: Number, required: true },
    estado: { type: Object, default: null },
    fueraDeAlcance: { type: Boolean, default: false },
    cajaCerrada: { type: Boolean, default: false },
});

const emit = defineEmits(['created', 'updated']);

const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];
const nombreMes = (numero) => meses[numero - 1] ?? 'Mes inválido';

const retroExistente = computed(() => {
    if (!props.estado?.retros_creados) return null;
    return props.estado.retros_creados.find((r) => r.posicion === props.posicion) ?? null;
});

const esSiguiente = computed(() =>
    !!props.estado?.habilitado &&
    props.estado?.siguiente_posicion === props.posicion &&
    !retroExistente.value
);

const esClickeable = computed(() => !props.fueraDeAlcance && (esSiguiente.value || !!retroExistente.value));

const mesEnJuego = computed(() =>
    retroExistente.value?.mes_original ?? props.estado?.siguiente_mes_original ?? null
);

const formatCurrency = (valor) =>
    new Intl.NumberFormat('es-BO', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(valor ?? 0);

/**
 * Texto y estilo de la franja según el estado de esta posición.
 */
const franja = computed(() => {
    if (props.fueraDeAlcance) {
        return { texto: 'Sin alcance', icono: 'closeCircleOutline', clase: 'bg-gray-50 text-gray-400 dark:bg-gray-800/60 dark:text-gray-500' };
    }
    if (retroExistente.value?.sin_retroactivo) {
        return { texto: `${nombreMes(retroExistente.value.mes_original)} sin retroactivo`, icono: 'closeCircleOutline', clase: 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-700/30 dark:text-slate-400' };
    }
    if (retroExistente.value) {
        return { texto: `${nombreMes(retroExistente.value.mes_original)} cargado`, icono: 'checkCircle', clase: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400' };
    }
    if (esSiguiente.value) {
        return { texto: `Cargar ${nombreMes(mesEnJuego.value)}`, icono: 'upload', clase: 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400' };
    }
    return { texto: 'Pendiente', icono: 'timeCircle', clase: 'bg-amber-50 text-amber-600 dark:bg-amber-900/10 dark:text-amber-400' };
});

// ============================================================================
// MODAL DE RETROACTIVO (detalle o carga, según el estado)
// ============================================================================
const mostrarModal = ref(false);
const detalle = ref(null);
const cargandoDetalle = ref(false);

const abrirModal = () => {
    if (!esClickeable.value) return;
    mostrarModal.value = true;

    // Si se confirmó "sin retroactivo" no hay nada que pedir — el detalle
    // sería todo ceros, se muestra directo el mensaje en el template.
    if (retroExistente.value && !retroExistente.value.sin_retroactivo) {
        cargarDetalle();
    }
};

const cargarDetalle = async () => {
    detalle.value = null;
    cargandoDetalle.value = true;
    try {
        const { data } = await axios.get(route('gestion.retroactivo.detalle'), {
            params: { id_mes: retroExistente.value.id_mes },
        });
        detalle.value = data;
    } catch (e) {
        detalle.value = null;
    } finally {
        cargandoDetalle.value = false;
    }
};

const cerrarModal = () => {
    mostrarModal.value = false;
    detalle.value = null;
    reiniciarFormulario();
    cancelarEdicionPresupuesto();
};

// ============================================================================
// EDITAR PRESUPUESTO DE UN MES-RETRO YA CARGADO
// ============================================================================
const editandoPresupuesto = ref(false);
const presupuestoEditado = ref(0);
const guardandoPresupuesto = ref(false);
const errorEditarPresupuesto = ref('');

const puedeEditarPresupuesto = computed(() =>
    can('editar-mes') && !props.cajaCerrada
);

const iniciarEdicionPresupuesto = () => {
    presupuestoEditado.value = retroExistente.value?.presupuesto ?? 0;
    errorEditarPresupuesto.value = '';
    editandoPresupuesto.value = true;
};

const cancelarEdicionPresupuesto = () => {
    editandoPresupuesto.value = false;
    errorEditarPresupuesto.value = '';
};

const guardarPresupuesto = async () => {
    if (!presupuestoEditado.value || presupuestoEditado.value <= 0) {
        errorEditarPresupuesto.value = 'Ingresá un presupuesto válido.';
        return;
    }

    guardandoPresupuesto.value = true;
    errorEditarPresupuesto.value = '';
    try {
        await axios.put(route('gestion.updateMes', retroExistente.value.id_mes), {
            presupuesto: presupuestoEditado.value,
        });
        editandoPresupuesto.value = false;
        emit('updated');
        if (detalle.value) cargarDetalle();
    } catch (e) {
        errorEditarPresupuesto.value = e.response?.data?.error ?? 'No se pudo actualizar el presupuesto.';
    } finally {
        guardandoPresupuesto.value = false;
    }
};

// ============================================================================
// MODAL DE DETALLE (desglose de la vista previa, separado del formulario de
// carga: se abre automáticamente al calcular, y "Aceptar" solo lo cierra y
// vuelve al formulario, que ya queda con el presupuesto sugerido cargado)
// ============================================================================
const mostrarDetalle = ref(false);

const ciCopiado = ref('');
let timeoutCopiado = null;

const copiarCi = async (ci) => {
    try {
        await navigator.clipboard.writeText(ci);
        ciCopiado.value = ci;
        clearTimeout(timeoutCopiado);
        timeoutCopiado = setTimeout(() => { ciCopiado.value = ''; }, 1500);
    } catch (e) {
        // Portapapeles no disponible (ej. contexto no seguro) — se ignora.
    }
};

// ============================================================================
// FORMULARIO DE CARGA (mismo patrón visual que "Agregar Mes": FileDropzone /
// FilePreview para el archivo, y "calcular > revisar detalle > confirmar"
// para el presupuesto — ver PresupuestoField.vue)
// ============================================================================
const nombreArchivo = ref('');
const registros = ref([]);
const preview = ref(null);
const cargandoArchivo = ref(false);
const cargandoPreview = ref(false);
const error = ref('');
const errorCalculo = ref('');
const validacion = ref(null);
const isDragging = ref(false);
const fileInputRef = ref(null);

const form = useForm({
    id_gestion: props.idGestion,
    posicion: props.posicion,
    presupuesto: 0,
    registros: [],
    exclusiones: [],
    sin_retroactivo: false,
});

// Modo alternativo dentro del mismo formulario: el mes revisado no tuvo
// ninguna lista de retro que subir. Se ofrece solo mientras no se cargó un
// archivo — evita confundirlo con "cancelar una carga en curso".
const modoSinRetro = ref(false);

/**
 * Estado de validación del archivo en el mismo formato que usa FilePreview.vue
 * (sin foundColumns/missingColumns: al ser válido no necesita panel expandible,
 * el desglose completo vive en el modal de detalle aparte).
 */
const fileValidationView = computed(() => {
    if (cargandoArchivo.value) {
        return { isValid: null, message: 'Validando archivo...' };
    }
    if (error.value) {
        return { isValid: false, message: error.value };
    }
    if (validacion.value) {
        return { isValid: true, message: `Archivo verificado: ${validacion.value.mes} ${validacion.value.gestion}` };
    }
    return {};
});

const quitarAcentos = (valor) =>
    String(valor ?? '').normalize('NFD').replace(new RegExp('[̀-ͯ]', 'g'), '');

const normalizarTexto = (valor) => quitarAcentos(valor).trim().toLowerCase();

/**
 * Busca, dentro de las primeras `maxFilas` filas, la celda que contiene la
 * etiqueta indicada (ej. "Mes de Pago") y devuelve el primer valor no vacío
 * a su derecha en esa misma fila (formato de encabezado tipo "Etiqueta :" |
 * valor que usa el reporte de SIGEP).
 */
const buscarValorPorEtiqueta = (filas, etiqueta, maxFilas = 17) => {
    const etiquetaNormalizada = normalizarTexto(etiqueta);
    const limite = Math.min(maxFilas, filas.length);

    for (let i = 0; i < limite; i++) {
        const fila = filas[i] ?? [];
        for (let j = 0; j < fila.length; j++) {
            if (normalizarTexto(fila[j]).includes(etiquetaNormalizada)) {
                for (let k = j + 1; k < fila.length; k++) {
                    const valor = String(fila[k] ?? '').trim();
                    if (valor) return valor;
                }
            }
        }
    }
    return null;
};

/**
 * Extrae [{ci, nombre}] de un Excel con columna "N° Documento" (y
 * opcionalmente "Razón Social"). Antes valida, dentro de las primeras 17
 * filas (encabezado del reporte de SIGEP), que el "Mes de Pago" y la
 * "Gestión de Pago" del archivo correspondan al mes-retro que se está
 * cargando — evita subir por error el archivo de otro mes/gestión.
 */
const parsearExcel = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const hoja = workbook.Sheets[workbook.SheetNames[0]];
            const filas = XLSX.utils.sheet_to_json(hoja, { header: 1, defval: '', blankrows: false });

            const mesExcel = buscarValorPorEtiqueta(filas, 'mes de pago');
            const gestionExcel = buscarValorPorEtiqueta(filas, 'gestion de pago');

            if (!mesExcel || !gestionExcel) {
                reject(new Error('No se encontró "Mes de Pago" o "Gestión de Pago" en las primeras 17 filas del archivo.'));
                return;
            }

            const mesEsperado = nombreMes(mesEnJuego.value);
            const gestionEsperada = props.gestion;

            const mesCoincide = normalizarTexto(mesExcel) === normalizarTexto(mesEsperado);
            const gestionCoincide = Number(gestionExcel) === Number(gestionEsperada);

            if (!mesCoincide || !gestionCoincide) {
                reject(new Error(
                    `El archivo corresponde a ${mesExcel} ${gestionExcel}, se esperaba ${mesEsperado} ${gestionEsperada}.`
                ));
                return;
            }

            // El formato que envían no es estable: la fila de encabezado real
            // ("N° Documento", "Razón Social", etc.) puede variar entre la fila
            // 15 y la 20 según haya datos basura antes. Por eso se busca en una
            // ventana más amplia y se exige que la fila tenga, además de
            // "documento", alguna otra palabra típica del encabezado real —
            // así una coincidencia suelta de una fila de basura no se confunde
            // con el encabezado.
            const MAX_FILAS_ENCABEZADO_DATOS = 25;
            const PALABRAS_CLAVE_ENCABEZADO = ['importe', 'banco destino', 'cuenta destino', 'razon social', 't doc', 'nro'];

            let indiceEncabezado = -1;
            let colDocumento = -1;
            let colNombre = -1;

            for (let i = 0; i < Math.min(MAX_FILAS_ENCABEZADO_DATOS, filas.length); i++) {
                const filaNormalizada = filas[i].map(normalizarTexto);
                const idxDocumento = filaNormalizada.findIndex((c) => c.includes('documento'));
                if (idxDocumento === -1) continue;

                const pareceEncabezado = filaNormalizada.some((c) => PALABRAS_CLAVE_ENCABEZADO.some((p) => c.includes(p)));
                if (!pareceEncabezado) continue;

                indiceEncabezado = i;
                colDocumento = idxDocumento;
                colNombre = filaNormalizada.findIndex((c) => c.includes('razon social') || c.includes('apellidos') || c.includes('nombres'));
                break;
            }

            if (indiceEncabezado === -1) {
                reject(new Error(`No se encontró la columna "N° Documento" en las primeras ${MAX_FILAS_ENCABEZADO_DATOS} filas.`));
                return;
            }

            const filasDatos = [];
            for (let i = indiceEncabezado + 1; i < filas.length; i++) {
                const ci = String(filas[i][colDocumento] ?? '').trim();
                const nombre = colNombre !== -1 ? String(filas[i][colNombre] ?? '').trim() : '';
                if (!ci) continue;
                filasDatos.push({ ci, nombre });
            }

            resolve({ registros: filasDatos, mesExcel, gestionExcel });
        } catch (err) {
            reject(err);
        }
    };

    reader.onerror = () => reject(new Error('Error al leer el archivo.'));
    reader.readAsArrayBuffer(file);
});

const procesarArchivo = async (file) => {
    if (!file) return;

    error.value = '';
    preview.value = null;
    registros.value = [];
    validacion.value = null;
    cargandoArchivo.value = true;

    try {
        const { registros: extraidos, mesExcel, gestionExcel } = await parsearExcel(file);

        if (extraidos.length === 0) {
            error.value = 'No se encontraron filas con N° Documento en el archivo.';
            return;
        }

        registros.value = extraidos;
        nombreArchivo.value = file.name;
        validacion.value = { mes: mesExcel, gestion: gestionExcel };
    } catch (e) {
        error.value = e.message ?? 'No se pudo procesar el archivo.';
    } finally {
        cargandoArchivo.value = false;
    }
};

const manejarArchivo = (event) => procesarArchivo(event.target.files[0]);

const manejarDrop = (event) => {
    isDragging.value = false;
    procesarArchivo(event.dataTransfer?.files?.[0]);
};

const triggerFileInput = () => fileInputRef.value?.click();

const quitarArchivo = () => {
    nombreArchivo.value = '';
    registros.value = [];
    preview.value = null;
    error.value = '';
    errorCalculo.value = '';
    validacion.value = null;
    mostrarDetalle.value = false;
    form.presupuesto = 0;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const calcularSugerido = async () => {
    errorCalculo.value = '';

    if (registros.value.length === 0) {
        errorCalculo.value = 'Cargue el archivo Excel antes de calcular el presupuesto sugerido.';
        return;
    }

    cargandoPreview.value = true;
    try {
        const { data } = await axios.post(route('gestion.retroactivo.preview'), {
            id_gestion: props.idGestion,
            posicion: props.posicion,
            registros: registros.value,
        });
        preview.value = data;
        form.presupuesto = data.presupuesto_sugerido;
        form.registros = registros.value;
        mostrarDetalle.value = true;
    } catch (e) {
        errorCalculo.value = e.response?.data?.error ?? 'No se pudo calcular el presupuesto sugerido.';
    } finally {
        cargandoPreview.value = false;
    }
};

// ============================================================================
// DESGLOSE DEL PREVIEW (mismo componente visual que ModalPreviewMes.vue:
// categorías colapsables con barra de color + badge + chevron, búsqueda por
// CI/nombre entre las 4 categorías). Empiezan contraídas — a diferencia del
// mes normal, acá no hay categorías que "requieran revisión" por defecto.
// ============================================================================
const filtroPreview = ref('');
const mostrarCategoria = reactive({ habilitados: false, yaPagados: false, yaHabilitados: false, bajas: false });

// CI -> motivo de las personas que, aunque el historial diga que corresponde
// pagarles, quien carga el retro decidió excluir a mano (ej. antecedente de
// baja que ya no está vigente en el historial pero genera dudas). No toca
// historial_estados — solo evita que se les cree el habilitado-retro.
const exclusionesManuales = reactive({});

const alternarExclusionManual = (ci) => {
    if (ci in exclusionesManuales) {
        delete exclusionesManuales[ci];
    } else {
        exclusionesManuales[ci] = '';
    }
};

const hayExclusionesSinMotivo = computed(() =>
    Object.values(exclusionesManuales).some((motivo) => !motivo.trim())
);

const coincideFiltro = (registro) => {
    if (!filtroPreview.value.trim()) return true;
    const consulta = normalizarTexto(filtroPreview.value);
    return normalizarTexto(registro.ci).includes(consulta) || normalizarTexto(registro.nombre ?? '').includes(consulta);
};

const habilitadosFiltrados = computed(() => (preview.value?.habilitados ?? []).filter(coincideFiltro));
const yaPagadosFiltrados = computed(() => (preview.value?.ya_pagados ?? []).filter(coincideFiltro));
const yaHabilitadosFiltrados = computed(() => (preview.value?.ya_habilitados ?? []).filter(coincideFiltro));
const bajasFiltradas = computed(() => (preview.value?.bajas ?? []).filter(coincideFiltro));

// ============================================================================
// PRELOADER — "Confirmar retroactivo" (mismo patrón que "Agregar Mes": una
// única petición HTTP sin progreso intermedio real, se muestran estos
// mensajes en secuencia mientras se espera la respuesta)
// ============================================================================
const MENSAJES_CARGA_RETRO = [
    'Validando registros...',
    'Verificando estados históricos...',
    'Generando mes retroactivo...',
    'Guardando cambios...',
];

let intervaloPreloaderRetro = null;

// Mientras se guarda, el modal de este componente se oculta (igual que
// Form.vue hace con "importing") — si no, su propio fondo oscuro difuminado
// queda debajo del overlay del Preloader y el logo (azul) se pierde por
// falta de contraste contra ese doble oscurecido.
const guardandoRetro = ref(false);

const iniciarPreloaderRetro = () => {
    let paso = 0;
    guardandoRetro.value = true;
    Preloader.show(MENSAJES_CARGA_RETRO[0]);
    intervaloPreloaderRetro = setInterval(() => {
        paso = Math.min(paso + 1, MENSAJES_CARGA_RETRO.length - 1);
        Preloader.setMessage(MENSAJES_CARGA_RETRO[paso]);
    }, 1800);
};

const detenerPreloaderRetro = () => {
    if (intervaloPreloaderRetro) {
        clearInterval(intervaloPreloaderRetro);
        intervaloPreloaderRetro = null;
    }
    guardandoRetro.value = false;
    Preloader.hide();
};

const confirmar = () => {
    if (hayExclusionesSinMotivo.value) return;

    form.sin_retroactivo = false;
    form.exclusiones = Object.entries(exclusionesManuales).map(([ci, motivo]) => ({ ci, motivo }));

    iniciarPreloaderRetro();
    form.post(route('gestion.retroactivo.store'), {
        preserveScroll: true,
        onSuccess: () => {
            detenerPreloaderRetro();
            mostrarModal.value = false;
            reiniciarFormulario();
            emit('created');
        },
        onError: () => {
            detenerPreloaderRetro();
        },
    });
};

/**
 * Confirma que este mes no tuvo retroactivo: crea igual el mes-retro (para
 * destrabar la secuencia) pero sin procesar ningún registro. El backend
 * distingue este caso de una carga normal por el flag `sin_retroactivo`, no
 * por mandar un archivo vacío — así queda claro que fue una decisión
 * consciente y no un archivo mal leído.
 */
const confirmarSinRetro = () => {
    form.sin_retroactivo = true;
    form.presupuesto = 0;
    form.registros = [];
    form.exclusiones = [];

    iniciarPreloaderRetro();
    form.post(route('gestion.retroactivo.store'), {
        preserveScroll: true,
        onSuccess: () => {
            detenerPreloaderRetro();
            mostrarModal.value = false;
            reiniciarFormulario();
            emit('created');
        },
        onError: () => {
            detenerPreloaderRetro();
        },
    });
};

const reiniciarFormulario = () => {
    nombreArchivo.value = '';
    registros.value = [];
    preview.value = null;
    error.value = '';
    errorCalculo.value = '';
    validacion.value = null;
    filtroPreview.value = '';
    mostrarDetalle.value = false;
    modoSinRetro.value = false;
    mostrarCategoria.habilitados = false;
    mostrarCategoria.yaPagados = false;
    mostrarCategoria.yaHabilitados = false;
    mostrarCategoria.bajas = false;
    Object.keys(exclusionesManuales).forEach((ci) => delete exclusionesManuales[ci]);
    form.reset();
    form.registros = [];
    form.exclusiones = [];
    form.sin_retroactivo = false;
};
</script>

<template>
    <!-- Franja inferior: siempre una sola línea, mismo alto en todos los estados -->
    <div class="mt-3 -mx-3 -mb-3 px-3 py-2 rounded-b-xl border-t border-gray-100 dark:border-gray-700 transition-colors"
        :class="[franja.clase, esClickeable ? 'cursor-pointer' : 'cursor-default']" @click.stop="abrirModal">
        <div class="flex items-center justify-between gap-2">
            <span class="text-[10px] font-bold uppercase tracking-wider opacity-70">Retroactivo</span>
            <span class="inline-flex items-center gap-1 text-xs font-semibold">
                <Icon :icon-button="true" :name="franja.icono" class-name="opacity-80" :size="13" />
                {{ franja.texto }}
            </span>
        </div>
    </div>

    <!-- Modal: detalle / carga de retroactivo (independiente del modal del mes normal) -->
    <Teleport to="body">
        <Modal v-if="mostrarModal" :hidden="mostrarDetalle || guardandoRetro" :showFooter="false" maxWidth="max-w-md" @close="cerrarModal">
            <template #icon>
                <Icon :icon-button="true"
                    :name="retroExistente?.sin_retroactivo ? 'closeCircleOutline' : (retroExistente ? 'checkCircle' : 'upload')"
                    class-name="text-white" :size="18" />
            </template>
            <template #label1>Retroactivo {{ nombreMes(mesEnJuego) }}</template>
            <template #label2>
                {{ retroExistente?.sin_retroactivo ? 'Confirmado sin retroactivo' : (retroExistente ? 'Mes-retroactivo ya cargado' : 'Cargar lista de retroactivo') }}
            </template>

            <!-- Vista: ya cargado -->
            <div v-if="retroExistente" class="space-y-3">
                <div class="rounded-xl p-3 space-y-2 border"
                    :class="retroExistente.sin_retroactivo
                        ? 'bg-slate-50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700'
                        : 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800'">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-300 text-sm">Mes</span>
                        <span class="font-semibold text-sm"
                            :class="retroExistente.sin_retroactivo ? 'text-slate-600 dark:text-slate-300' : 'text-emerald-700 dark:text-emerald-400'">
                            {{ nombreMes(retroExistente.mes_original) }}
                        </span>
                    </div>
                    <template v-if="!retroExistente.sin_retroactivo">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-300 text-sm">Monto / persona</span>
                            <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                Bs. {{ formatCurrency(retroExistente.monto) }}
                            </span>
                        </div>
                        <div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Presupuesto asignado</span>
                                <div v-if="!editandoPresupuesto" class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                        Bs. {{ formatCurrency(retroExistente.presupuesto) }}
                                    </span>
                                    <button v-if="puedeEditarPresupuesto" type="button" @click="iniciarEdicionPresupuesto"
                                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                        Editar
                                    </button>
                                </div>
                            </div>

                            <div v-if="editandoPresupuesto" class="mt-2 space-y-1.5">
                                <Input input-type="number" :model-value="presupuestoEditado"
                                    @update:model-value="presupuestoEditado = Number($event)" />
                                <p v-if="errorEditarPresupuesto" class="text-xs text-rose-600">{{ errorEditarPresupuesto }}</p>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" @click="cancelarEdicionPresupuesto" :disabled="guardandoPresupuesto"
                                        class="text-xs font-medium text-slate-500 hover:underline disabled:opacity-50">
                                        Cancelar
                                    </button>
                                    <button type="button" @click="guardarPresupuesto" :disabled="guardandoPresupuesto"
                                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline disabled:opacity-50">
                                        {{ guardandoPresupuesto ? 'Guardando...' : 'Guardar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Sin retroactivo: mensaje directo, no tiene sentido pedir/mostrar
                     un desglose que sería todo ceros -->
                <div v-if="retroExistente.sin_retroactivo"
                    class="flex items-start gap-2 bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700 rounded-xl p-3">
                    <Icon :icon-button="true" name="closeCircleOutline" class-name="text-slate-400 flex-shrink-0 mt-0.5" :size="16" />
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Este mes se revisó y SIGEP no envió ninguna lista de retroactivo —
                        no se generó ningún habilitado ni pago.
                    </p>
                </div>

                <template v-else>
                    <!-- Cargando detalle -->
                    <div v-if="cargandoDetalle" class="flex items-center justify-center gap-2 py-4 text-sm text-slate-400">
                        <svg class="animate-spin w-4 h-4 text-[rgb(var(--brand-600))]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                        </svg>
                        Cargando detalle...
                    </div>

                    <!-- Detalle: habilitados, bajas, pagados, no pagados, presupuesto -->
                    <div v-else-if="detalle" class="space-y-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600/40 rounded-xl px-2 py-2.5 text-center">
                                <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold">Habilitados</p>
                                <p class="text-lg font-bold text-slate-700 dark:text-slate-200">{{ detalle.cantidad_habilitados }}</p>
                            </div>
                            <div class="bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800 rounded-xl px-2 py-2.5 text-center">
                                <p class="text-[10px] text-rose-500 uppercase tracking-wide font-semibold">Bajas</p>
                                <p class="text-lg font-bold text-rose-700 dark:text-rose-400">{{ detalle.cantidad_bajas }}</p>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl px-2 py-2.5 text-center">
                                <p class="text-[10px] text-emerald-500 uppercase tracking-wide font-semibold">Pagados</p>
                                <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400">{{ detalle.cantidad_pagados }}</p>
                            </div>
                            <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl px-2 py-2.5 text-center">
                                <p class="text-[10px] text-amber-500 uppercase tracking-wide font-semibold">No pagados</p>
                                <p class="text-lg font-bold text-amber-700 dark:text-amber-400">{{ detalle.cantidad_no_pagados }}</p>
                            </div>
                        </div>

                        <div class="bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800 rounded-xl p-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Total procesados (habilitados + bajas)</span>
                                <span class="font-bold text-violet-700 dark:text-violet-400 text-lg">
                                    {{ detalle.total_procesados }}
                                </span>
                            </div>
                            <p class="text-[11px] text-violet-500 dark:text-violet-400 mt-0.5">
                                Comparar con el total de filas del Excel de retroactivo cargado.
                            </p>
                        </div>

                        <div class="bg-sky-50 dark:bg-sky-900/10 border border-sky-200 dark:border-sky-800 rounded-xl p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Presupuesto utilizado</span>
                                <span class="font-semibold text-sky-700 dark:text-sky-400 text-sm">
                                    Bs. {{ formatCurrency(detalle.total_pagado) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300 text-sm">Presupuesto disponible</span>
                                <span class="font-semibold text-gray-800 dark:text-white text-sm">
                                    Bs. {{ formatCurrency(detalle.presupuesto_restante) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Vista: formulario de carga -->
            <div v-else class="space-y-3">
                <!-- Modo alternativo: el mes revisado no tuvo lista de retro -->
                <template v-if="modoSinRetro">
                    <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl p-3 space-y-1">
                        <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">
                            Vas a marcar {{ nombreMes(mesEnJuego) }} {{ gestion }} como "sin retroactivo"
                        </p>
                        <p class="text-xs text-amber-600 dark:text-amber-500">
                            No se creará ningún habilitado ni se sube ningún archivo — solo queda registrado que se
                            revisó y SIGEP no envió pendientes para este mes.
                        </p>
                    </div>
                    <button type="button" @click="modoSinRetro = false"
                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                        ← Volver a cargar un archivo
                    </button>
                </template>

                <template v-else>
                    <!-- Archivo: mismo componente Dropzone/Preview que "Agregar Mes" -->
                    <FileDropzone v-if="!nombreArchivo"
                        :field="{ placeholder: 'Arrastra tu archivo aquí o hacé clic para seleccionar', acceptedTypes: '.xlsx,.xls,.csv', maxSize: 5 }"
                        :is-dragging="isDragging" :error="''" @click="triggerFileInput" @drop="manejarDrop"
                        @dragover="isDragging = true" @dragleave="isDragging = false" />

                    <FilePreview v-else :file-name="nombreArchivo" :validation="fileValidationView" @remove="quitarArchivo" />

                    <input ref="fileInputRef" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="manejarArchivo" />

                    <button v-if="!nombreArchivo" type="button" @click="modoSinRetro = true"
                        class="w-full text-xs text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 underline decoration-dotted underline-offset-2 text-center py-1 transition-colors">
                        Este mes no tuvo retroactivo — marcar como "sin retroactivo"
                    </button>

                    <!-- Presupuesto: mismo patrón "calcular > revisar detalle > confirmar" que PresupuestoField.vue -->
                    <div>
                        <div class="flex items-center gap-0.5 mb-1">
                            <label class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">
                                Presupuesto para este mes-retroactivo (Bs.)
                            </label>
                            <span class="text-rose-400 text-sm leading-none">*</span>
                        </div>

                        <p v-if="!validacion" class="text-sm text-red-400 italic">
                            Cargue el archivo Excel correcto para calcular el presupuesto.
                        </p>

                        <template v-else-if="!preview">
                            <button type="button" @click="calcularSugerido" :disabled="cargandoPreview"
                                class="w-full text-sm px-3 py-2.5 rounded-lg bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))] text-white font-medium disabled:opacity-50 transition-colors">
                                {{ cargandoPreview ? 'Calculando...' : 'Calcular presupuesto sugerido' }}
                            </button>
                            <p v-if="errorCalculo" class="text-sm text-rose-600 mt-1">{{ errorCalculo }}</p>
                        </template>

                        <template v-else>
                            <Input input-type="number" :model-value="form.presupuesto" readonly class="!bg-gray-100"
                                @update:model-value="form.presupuesto = Number($event)" />
                            <div class="flex items-center justify-end mt-1">
                                <button type="button" @click="mostrarDetalle = true"
                                    class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                    Ver detalle
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <template #footer>
                <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-4">
                    <div class="flex justify-center gap-3">
                        <button type="button" @click="cerrarModal"
                            class="py-2.5 px-8 rounded-xl border border-[rgb(var(--brand-300))] text-[rgb(var(--brand-700))] bg-[rgb(var(--brand-50))] hover:bg-[rgb(var(--brand-100))] dark:bg-[rgba(var(--brand-900),0.2)] dark:text-[rgb(var(--brand-300))] dark:border-[rgb(var(--brand-700))] dark:hover:bg-[rgba(var(--brand-900),0.35)] text-sm font-medium transition-colors">
                            Aceptar
                        </button>
                        <button v-if="!retroExistente && modoSinRetro" type="button" @click="confirmarSinRetro"
                            :disabled="form.processing"
                            class="py-2.5 px-8 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Guardando...' : 'Confirmar sin retroactivo' }}
                        </button>
                        <button v-else-if="!retroExistente" type="button" @click="confirmar"
                            :disabled="!nombreArchivo || !preview || !form.presupuesto || form.presupuesto <= 0 || form.processing || hayExclusionesSinMotivo"
                            :title="hayExclusionesSinMotivo ? 'Completá el motivo de las exclusiones marcadas antes de confirmar' : ''"
                            class="py-2.5 px-8 rounded-xl bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))] text-white text-sm font-medium disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Guardando...' : 'Confirmar' }}
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </Teleport>

    <!-- Modal: detalle categorizado de la vista previa (separado de la carga, se
         abre automáticamente al calcular; "Aceptar" solo cierra y vuelve al
         formulario, que ya queda con el presupuesto sugerido cargado) -->
    <Teleport to="body">
        <Modal v-if="mostrarDetalle" :showFooter="false" maxWidth="max-w-lg" @close="mostrarDetalle = false">
            <template #icon>
                <Icon :icon-button="true" name="checkCircle" class-name="text-white" :size="18" />
            </template>
            <template #label1>Detalle Retroactivo {{ nombreMes(mesEnJuego) }}</template>
            <template #label2>Revisá antes de confirmar la carga</template>

            <div class="space-y-3">
                <!-- Resumen: Total = Habilitados + Ya pagados + Ya habilitados + Bajas -->
                <div class="grid gap-1.5" style="grid-template-columns: repeat(auto-fit, minmax(6.5rem, 1fr));">
                    <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600/40 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide font-semibold">Total</p>
                        <p class="text-base font-bold text-slate-700 dark:text-slate-200">{{ preview?.total_cis }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-800 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-emerald-600 uppercase tracking-wide font-semibold">Habilitados</p>
                        <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ preview?.total_habilitados }}</p>
                    </div>
                    <div v-if="preview?.ya_pagados?.length > 0" class="bg-sky-50 dark:bg-sky-900/10 rounded-lg border border-sky-200 dark:border-sky-800 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-sky-600 uppercase tracking-wide font-semibold">Ya pagados</p>
                        <p class="text-base font-bold text-sky-600 dark:text-sky-400">{{ preview.ya_pagados.length }}</p>
                    </div>
                    <div v-if="preview?.ya_habilitados?.length > 0" class="bg-violet-50 dark:bg-violet-900/10 rounded-lg border border-violet-200 dark:border-violet-800 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-violet-600 uppercase tracking-wide font-semibold">Ya habilitados</p>
                        <p class="text-base font-bold text-violet-600 dark:text-violet-400">{{ preview.ya_habilitados.length }}</p>
                    </div>
                    <div v-if="preview?.bajas?.length > 0" class="bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800 px-2 py-1.5 text-center">
                        <p class="text-[10px] text-amber-600 uppercase tracking-wide font-semibold">Bajas</p>
                        <p class="text-base font-bold text-amber-600 dark:text-amber-400">{{ preview.bajas.length }}</p>
                    </div>
                </div>

                <!-- Buscador -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                        </svg>
                    </div>
                    <input v-model="filtroPreview" type="text" placeholder="Buscar por N° Documento o nombre..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-gray-200 dark:placeholder-gray-500 transition-all" />
                    <button v-if="filtroPreview" @click="filtroPreview = ''"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <p v-if="filtroPreview && habilitadosFiltrados.length === 0 && yaPagadosFiltrados.length === 0 && yaHabilitadosFiltrados.length === 0 && bajasFiltradas.length === 0"
                    class="text-center text-sm text-gray-400 dark:text-gray-500 py-4">
                    Sin resultados para "{{ filtroPreview }}"
                </p>

                <!-- Categorías -->
                <div class="space-y-2">
                    <!-- Habilitados -->
                    <div v-show="!filtroPreview || habilitadosFiltrados.length > 0"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button type="button" @click="mostrarCategoria.habilitados = !mostrarCategoria.habilitados"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-6 rounded-full flex-shrink-0 bg-emerald-300"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">Habilitados</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">Se habilitan por este retroactivo</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    {{ filtroPreview ? `${habilitadosFiltrados.length} de ` : '' }}{{ preview?.habilitados?.length ?? 0 }}
                                </span>
                                <svg :class="{ 'rotate-180': mostrarCategoria.habilitados }" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <ul v-show="mostrarCategoria.habilitados || filtroPreview" class="divide-y divide-gray-100 dark:divide-gray-700/50 max-h-56 overflow-y-auto">
                            <li v-for="r in habilitadosFiltrados" :key="r.ci" class="px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                :class="r.tiene_baja_previa ? 'bg-amber-50/60 dark:bg-amber-900/10' : ''">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-slate-700 dark:text-slate-200 uppercase truncate flex items-center gap-1">
                                            <Icon v-if="r.tiene_baja_previa" :icon-button="true" name="alertTriangle"
                                                class-name="text-amber-500 flex-shrink-0" fill="none" stroke="currentColor"
                                                stroke-width="2" :size="14" />
                                            {{ r.nombre || '—' }}
                                            <span v-if="r.nuevo" class="text-sky-500 text-xs">(nuevo)</span>
                                        </p>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500">CI: {{ r.ci }}</p>
                                        <p v-if="r.tiene_baja_previa" class="text-[11px] text-amber-600 dark:text-amber-400">
                                            Tiene un antecedente de baja en su historial — revisar antes de confirmar.
                                        </p>
                                    </div>
                                    <button type="button" @click="copiarCi(r.ci)"
                                        class="flex-shrink-0 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 text-slate-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        {{ ciCopiado === r.ci ? 'Copiado' : 'Copiar CI' }}
                                    </button>
                                    <button v-if="r.tiene_baja_previa" type="button" @click="alternarExclusionManual(r.ci)"
                                        class="flex-shrink-0 text-[11px] font-medium px-2 py-1 rounded-md border transition-colors"
                                        :class="r.ci in exclusionesManuales
                                            ? 'border-rose-300 bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:border-rose-700 dark:text-rose-400'
                                            : 'border-gray-200 dark:border-gray-600 text-slate-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                        {{ r.ci in exclusionesManuales ? 'Excluida' : 'Excluir de este retroactivo' }}
                                    </button>
                                </div>

                                <div v-if="r.ci in exclusionesManuales" class="mt-1.5 pl-1">
                                    <input type="text" v-model="exclusionesManuales[r.ci]"
                                        placeholder="Motivo de la exclusión (obligatorio)"
                                        class="w-full text-xs px-2 py-1.5 bg-white dark:bg-gray-800 border border-rose-200 dark:border-rose-700 rounded-md focus:ring-1 focus:ring-rose-400 focus:border-transparent dark:text-gray-200 dark:placeholder-gray-500" />
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Ya pagados -->
                    <div v-if="preview?.ya_pagados?.length > 0" v-show="!filtroPreview || yaPagadosFiltrados.length > 0"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button type="button" @click="mostrarCategoria.yaPagados = !mostrarCategoria.yaPagados"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-6 rounded-full flex-shrink-0 bg-sky-300"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">Ya pagados</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">Ya tienen pago válido en el mes normal — no se duplica</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400">
                                    {{ filtroPreview ? `${yaPagadosFiltrados.length} de ` : '' }}{{ preview?.ya_pagados?.length ?? 0 }}
                                </span>
                                <svg :class="{ 'rotate-180': mostrarCategoria.yaPagados }" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <ul v-show="mostrarCategoria.yaPagados || filtroPreview" class="divide-y divide-gray-100 dark:divide-gray-700/50 max-h-56 overflow-y-auto">
                            <li v-for="r in yaPagadosFiltrados" :key="r.ci" class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-700 dark:text-slate-200 uppercase truncate">{{ r.nombre || '—' }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">CI: {{ r.ci }} · {{ r.motivo }}</p>
                                </div>
                                <button type="button" @click="copiarCi(r.ci)"
                                    class="flex-shrink-0 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 text-slate-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    {{ ciCopiado === r.ci ? 'Copiado' : 'Copiar CI' }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Ya habilitados (habilitado ya existente en el mes normal, sin pagar aún) -->
                    <div v-if="preview?.ya_habilitados?.length > 0" v-show="!filtroPreview || yaHabilitadosFiltrados.length > 0"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button type="button" @click="mostrarCategoria.yaHabilitados = !mostrarCategoria.yaHabilitados"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-6 rounded-full flex-shrink-0 bg-violet-300"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">Ya habilitados</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">Ya habilitados en el mes normal, pendientes de pago — verificar</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                                    {{ filtroPreview ? `${yaHabilitadosFiltrados.length} de ` : '' }}{{ preview?.ya_habilitados?.length ?? 0 }}
                                </span>
                                <svg :class="{ 'rotate-180': mostrarCategoria.yaHabilitados }" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <ul v-show="mostrarCategoria.yaHabilitados || filtroPreview" class="divide-y divide-gray-100 dark:divide-gray-700/50 max-h-56 overflow-y-auto">
                            <li v-for="r in yaHabilitadosFiltrados" :key="r.ci" class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-700 dark:text-slate-200 uppercase truncate">{{ r.nombre || '—' }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">CI: {{ r.ci }} · {{ r.motivo }}</p>
                                </div>
                                <button type="button" @click="copiarCi(r.ci)"
                                    class="flex-shrink-0 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 text-slate-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    {{ ciCopiado === r.ci ? 'Copiado' : 'Copiar CI' }}
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Bajas -->
                    <div v-if="preview?.bajas?.length > 0" v-show="!filtroPreview || bajasFiltradas.length > 0"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button type="button" @click="mostrarCategoria.bajas = !mostrarCategoria.bajas"
                            class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-6 rounded-full flex-shrink-0 bg-amber-300"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">Bajas</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">No se habilitan por este retroactivo</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                    {{ filtroPreview ? `${bajasFiltradas.length} de ` : '' }}{{ preview?.bajas?.length ?? 0 }}
                                </span>
                                <svg :class="{ 'rotate-180': mostrarCategoria.bajas }" class="w-4 h-4 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <ul v-show="mostrarCategoria.bajas || filtroPreview" class="divide-y divide-gray-100 dark:divide-gray-700/50 max-h-56 overflow-y-auto">
                            <li v-for="r in bajasFiltradas" :key="r.ci" class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <div class="flex-1 min-w-0">
                                    <p class="text-slate-700 dark:text-slate-200 uppercase truncate">{{ r.nombre || '—' }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">CI: {{ r.ci }} · {{ r.motivo }}</p>
                                </div>
                                <button type="button" @click="copiarCi(r.ci)"
                                    class="flex-shrink-0 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 dark:border-gray-600 text-slate-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    {{ ciCopiado === r.ci ? 'Copiado' : 'Copiar CI' }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-between font-semibold text-slate-800 dark:text-slate-100 pt-1 border-t border-gray-200 dark:border-gray-600 text-sm">
                    <span>Presupuesto sugerido</span><span>Bs. {{ preview?.presupuesto_sugerido }}</span>
                </div>
            </div>

            <template #footer>
                <div class="px-1 border-t border-gray-100 dark:border-gray-700/50 py-4">
                    <div class="flex justify-center">
                        <button type="button" @click="mostrarDetalle = false"
                            class="py-2.5 px-8 rounded-xl bg-[rgb(var(--brand-600))] hover:bg-[rgb(var(--brand-500))] text-white text-sm font-medium transition-colors">
                            Aceptar
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </Teleport>
</template>
