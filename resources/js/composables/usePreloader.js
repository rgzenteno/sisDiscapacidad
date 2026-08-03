// ============ INICIO IMPORTS ============ //
import { reactive } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Estado global del overlay de carga — un único objeto reactivo compartido
 * por toda la app (patrón singleton), leído por PreloaderOverlay.vue
 * (montado una sola vez en AppLayout.vue). No requiere Vuex/Pinia: la
 * reactividad de Vue ya es suficiente para este caso de uso simple.
 */
const state = reactive({
    visible: false,
    message: '',
});

// Tiempo mínimo que el overlay se mantiene visible una vez mostrado. Sin
// esto, operaciones muy rápidas (ej. retroactivo, que ya manda los datos
// procesados y no un archivo) lo prenden y apagan tan rápido que el logo
// nunca llega a verse.
const MIN_VISIBLE_MS = 600;

let shownAt = 0;
let hideTimeout = null;

/**
 * API pública del preloader global, para usar desde cualquier proceso lento
 * del sistema (no solo "agregar mes"):
 *   Preloader.show('Validando información del mes...')
 *   Preloader.setMessage('Guardando cambios...')
 *   Preloader.hide()
 */
export const Preloader = {
    state,
    show(message = 'Cargando...') {
        clearTimeout(hideTimeout);
        hideTimeout = null;
        shownAt = Date.now();
        state.message = message;
        state.visible = true;
    },
    setMessage(message) {
        state.message = message;
    },
    hide() {
        const transcurrido = Date.now() - shownAt;
        const faltante = MIN_VISIBLE_MS - transcurrido;

        if (faltante > 0) {
            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(() => {
                state.visible = false;
                hideTimeout = null;
            }, faltante);
            return;
        }

        state.visible = false;
    },
};
