// ============ INICIO IMPORTS ============ //
import { onMounted, onUnmounted } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para manejar shortcuts de teclado
 * @param {Function} handleCancel - Función a ejecutar al presionar ESC
 */
export function useKeyboardShortcuts(handleCancel) {

    // ============ INICIO MÉTODOS ============ //
    /**
     * Maneja el evento de teclado
     * @param {KeyboardEvent} e - Evento de teclado
     */
    const closeOnEscape = (e) => {
        if (e.key === 'Escape') {
            handleCancel();
        }
    };
    // ============ FIN MÉTODOS ============ //

    // ============ INICIO LIFECYCLE ============ //
    // Registrar listener al montar
    onMounted(() => {
        document.addEventListener('keydown', closeOnEscape);
    });

    // Limpiar listener al desmontar
    onUnmounted(() => {
        document.removeEventListener('keydown', closeOnEscape);
    });
    // ============ FIN LIFECYCLE ============ //
}
