// ============ INICIO IMPORTS ============ //
import { computed, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { hexToRgb, darkenRgb, rgbToChannels, generarEscala } from '@/utils/color';
// ============ FIN IMPORTS ============ //

/**
 * Color institucional del sistema (parámetro "Color - Sistema", editable en
 * Configuración). Solo se guarda un hex — este composable deriva a partir de
 * ese único valor las variables CSS que usan las vistas (color base y un
 * tono oscuro para fondos), con el mismo factor que separaba el azul de
 * acento (#285FC6) del navy de fondo (#0F2956) en el diseño original. Así,
 * cuando cambie el partido/gestión y se actualice el hex, todo el degradado
 * se recalcula solo, sin tener que guardar un color por cada tono.
 */
const COLOR_POR_DEFECTO = '#285FC6';
const FACTOR_OSCURO = 0.42;

export function useColorSistema() {
    const page = usePage();

    const colorBase = computed(() => page.props.sistema?.color || COLOR_POR_DEFECTO);

    const estiloBrand = computed(() => {
        const rgb = hexToRgb(colorBase.value);
        const escala = generarEscala(rgb);
        const variables = {
            '--brand-rgb': rgbToChannels(rgb),
            '--brand-dark-rgb': rgbToChannels(darkenRgb(rgb, FACTOR_OSCURO)),
        };
        for (const [parada, canales] of Object.entries(escala)) {
            variables[`--brand-${parada}`] = canales;
        }
        return variables;
    });

    // Además de devolver estiloBrand (para :style en el layout/página), se
    // aplican las mismas variables en <html> — así llegan también al
    // contenido que sale del árbol DOM normal vía <Teleport to="body">
    // (Dropdown, MonthYearField, Input, MesSuspensionPicker,
    // ModalPreviewMes, RetroactivoMesCard...), que de otro modo queda fuera
    // del elemento donde estiloBrand está montado y renderiza var(--brand-*)
    // como inválido (negro).
    watchEffect(() => {
        const root = document.documentElement;
        for (const [variable, valor] of Object.entries(estiloBrand.value)) {
            root.style.setProperty(variable, valor);
        }
    });

    return { colorBase, estiloBrand };
}
