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
    const mostrarPresupuestoAnual = ref(false);

    // ============ FUNCIÓN HELPER — calcular total de personas ============ //
    const calcularTotalPersonas = () => {
        const cisActivas = props.cisPersonasActivas || [];
        const cisTodasPersonas = props.cisTodasPersonas || [];

        let registrosPdf = [];
        if (form.registros_extraidos) {
            try {
                registrosPdf = JSON.parse(form.registros_extraidos);
            } catch { registrosPdf = []; }
        }

        if (registrosPdf.length > 0 && cisActivas.length > 0) {
            const setCisActivas = new Set(cisActivas.map(String));
            const setCisTodasPersonas = new Set(cisTodasPersonas.map(String));

            // Activos: están en PDF y su último estado es activo
            const coincidentes = registrosPdf.filter(r =>
                setCisActivas.has(String(r.ci))
            );

            // Nuevos: están en PDF pero NO existen en BD en absoluto
            const nuevos = registrosPdf.filter(r =>
                !setCisTodasPersonas.has(String(r.ci))
            );

            // Excluidos: existen en BD pero con estado no activo (no se cobran)
            const excluidos = registrosPdf.filter(r =>
                setCisTodasPersonas.has(String(r.ci)) && !setCisActivas.has(String(r.ci))
            );

            return coincidentes.length + nuevos.length;
        }

        return props.personasValidas || 0;
    };

    // ============ COMPUTED — presupuesto sugerido para mostrar ============ //
    const presupuestoSugerido = computed(() => {
        const monto = parseInt(form.monto) || 0;
        if (!monto) return 'Bs. 0,00';

        // Forzar reactividad leyendo registros_extraidos en el computed
        const _dep = form.registros_extraidos;

        const total = calcularTotalPersonas();
        return (total * monto).toLocaleString('es-BO');
    });

    // ============ WATCHER — actualizar form.presupuesto al cambiar monto ============ //
    watch(() => form.monto, (nuevoMonto) => {
        if (!props.editMode) {
            if (nuevoMonto) {
                if (form.archivo_validacion?.isValid !== true) {
                    form.presupuesto = '';
                    return;
                }
                form.presupuesto = calcularTotalPersonas() * parseInt(nuevoMonto);
                if (form.errors.presupuesto) delete form.errors.presupuesto;
            } else {
                form.monto = '';
                form.presupuesto = '';
            }
        }
    });

    // ============ WATCHER — mostrar campo presupuesto anual ============ //
    watch(() => props.dataGestion, (dataGestion) => {
        if (props.gestion && dataGestion) {
            const existeGestion = dataGestion.some(g => g.gestion === props.gestion.gestion);
            mostrarPresupuestoAnual.value = !existeGestion;
        }
    }, { immediate: true });

    return { mostrarPresupuestoAnual, presupuestoSugerido };
}
