// ============ INICIO IMPORTS ============ //
import { watch } from "vue";
// ============ FIN IMPORTS ============ //

const OBSERVACION_FIJA = {
    baja_temporal: "PADRE FUNCIONARIO TRABAJANDO CON ITEM",
    baja_definitiva: "FALLECIDO",
};

/**
 * Autocompleta observaciones con el texto fijo del estado elegido (baja
 * temporal/definitiva), para ahorrarle el trabajo al usuario. Si el usuario ya
 * tocó el campo a mano (en esta sesión del formulario, sea agregando o
 * editando), no se lo pisa al cambiar de estado otra vez.
 * @param {Object} form - Formulario de Inertia
 * @param {import('vue').Ref<boolean>} [observacionesTocada] - true cuando el
 *   usuario editó el campo manualmente; el valor precargado al editar un
 *   estado existente no cuenta como "tocado".
 */
export function useObservacionFija(form, observacionesTocada) {
    // ============ INICIO WATCHERS ============ //
    watch(
        () => form.estado,
        (nuevoEstado) => {
            if (!("observaciones" in form)) return;
            if (observacionesTocada?.value) return;

            form.observaciones = OBSERVACION_FIJA[nuevoEstado] ?? "";
        },
    );
    // ============ FIN WATCHERS ============ //
}
