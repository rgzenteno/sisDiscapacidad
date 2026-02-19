// ============ INICIO IMPORTS ============ //
import { ref, computed, watch } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para cálculos de presupuesto
 * @param {Object} form - Formulario de Inertia
 * @param {Object} props - Props del componente
 * @returns {Object} - { mostrarPresupuestoAnual, presupuestoSugerido }
 */
export function usePresupuestoCalculation(form, props) {

    // ============ INICIO REFS ============ //
    const mostrarPresupuestoAnual = ref(false);
    // ============ FIN REFS ============ //

    // ============ INICIO COMPUTED ============ //
    /**
     * Calcula el presupuesto sugerido basado en personas válidas y monto
     */
    const presupuestoSugerido = computed(() => {
        const monto = parseInt(form.monto) || 0;
        const personas = props.personasValidas || 0;
        return (personas * monto).toLocaleString('es-BO');
    });
    // ============ FIN COMPUTED ============ //

    // ============ INICIO WATCHERS ============ //
    // Watch para calcular presupuesto automáticamente SOLO si NO está en modo edición
    watch(() => form.monto, (nuevoMonto) => {
        // 👇 Solo calcular si NO estamos en modo edición
        if (!props.editMode) {
            if (nuevoMonto) {
                const resultado = (props.personasValidas || 0) * parseInt(nuevoMonto);
                form.presupuesto = resultado;

                // Limpiar error si existe
                if (form.errors.presupuesto) {
                    delete form.errors.presupuesto;
                }
            } else {
                form.monto = '';
                form.presupuesto = '';
            }
        }
    });

    // Determinar si se debe mostrar el campo de presupuesto anual
    watch(() => props.dataGestion, (dataGestion) => {
        if (props.gestion && dataGestion) {
            const existeGestion = dataGestion.some(g => g.gestion === props.gestion.gestion);
            mostrarPresupuestoAnual.value = !existeGestion;
        }
    }, { immediate: true });
    // ============ FIN WATCHERS ============ //

    return {
        mostrarPresupuestoAnual,
        presupuestoSugerido
    };
}
