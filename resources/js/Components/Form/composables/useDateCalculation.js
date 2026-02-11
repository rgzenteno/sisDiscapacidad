// ============ INICIO IMPORTS ============ //
import { watch } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para calcular fecha de vencimiento
 * @param {Object} form - Formulario de Inertia
 */
export function useDateCalculation(form) {

    // ============ INICIO WATCHERS ============ //
    // Watch para calcular fecha de vencimiento (+4 años desde emisión)
    watch(() => form.fecha_emision, (nuevaFecha) => {
        if (nuevaFecha) {
            const fechaEmision = new Date(nuevaFecha);
            const fechaVencimiento = new Date(fechaEmision);

            // Sumar 4 años
            fechaVencimiento.setFullYear(fechaVencimiento.getFullYear() + 4);

            // Formatear fecha como YYYY-MM-DD
            const year = fechaVencimiento.getFullYear();
            const month = String(fechaVencimiento.getMonth() + 1).padStart(2, '0');
            const day = String(fechaVencimiento.getDate()).padStart(2, '0');
            const fechaFormateada = `${year}-${month}-${day}`;

            form.fecha_vencimiento = fechaFormateada;
        } else {
            form.fecha_vencimiento = '';
        }
    }, { immediate: true });
    // ============ FIN WATCHERS ============ //
}
