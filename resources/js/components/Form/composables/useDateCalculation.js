// ============ INICIO IMPORTS ============ //
import { watch } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para calcular fecha de vencimiento
 * @param {Object} form - Formulario de Inertia
 */
export function useDateCalculation(form) {

    // ============ INICIO WATCHERS ============ //
    // Watch para calcular fecha de vencimiento (+5 años desde emisión)
    watch(() => form.fecha_emision, (nuevaFecha) => {
        if (nuevaFecha) {
            // Parsear la fecha en formato YYYY-MM-DD sin conversión de zona horaria
            const [year, month, day] = nuevaFecha.split('-').map(Number);
            const fechaEmision = new Date(year, month - 1, day);

            // Crear nueva fecha para vencimiento
            const fechaVencimiento = new Date(fechaEmision);

            // Sumar 5 años
            fechaVencimiento.setFullYear(fechaVencimiento.getFullYear() + 5);

            // Formatear fecha como YYYY-MM-DD
            const yearVenc = fechaVencimiento.getFullYear();
            const monthVenc = String(fechaVencimiento.getMonth() + 1).padStart(2, '0');
            const dayVenc = String(fechaVencimiento.getDate()).padStart(2, '0');
            const fechaFormateada = `${yearVenc}-${monthVenc}-${dayVenc}`;

            form.fecha_vencimiento = fechaFormateada;
        } else {
            form.fecha_vencimiento = '';
        }
    }, { immediate: true });
    // ============ FIN WATCHERS ============ //
}
