// ============ INICIO IMPORTS ============ //
import { watch } from "vue";
// ============ FIN IMPORTS ============ //

/**
 * Composable para calcular fecha de vencimiento
 * @param {Object} form - Formulario de Inertia
 */
export function useDateCalculation(form) {
    // ============ INICIO WATCHERS ============ //
    // Watch para calcular fecha de vencimiento (+6 años desde emisión)
    watch(
        () => form.fecha_emision,
        (nuevaFecha) => {
            if (nuevaFecha && !form.indefinido) {
                const [year, month, day] = nuevaFecha.split("-").map(Number);
                const monthVenc = String(month).padStart(2, "0");
                const dayVenc = String(day).padStart(2, "0");
                form.fecha_vencimiento = `${year + 6}-${monthVenc}-${dayVenc}`;
            } else {
                form.fecha_vencimiento = "";
            }
        },
        { immediate: true },
    );
    // ============ FIN WATCHERS ============ //
}
