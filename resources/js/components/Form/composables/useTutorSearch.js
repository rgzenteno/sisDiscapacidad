// ============ INICIO IMPORTS ============ //
import { ref, watch } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para búsqueda de tutores por CI
 * @param {Object} form - Formulario de Inertia
 * @param {Object} props - Props del componente
 * @param {Function} emit - Función emit
 * @returns {Object} - { search, searchTutor }
 */
export function useTutorSearch(form, props, emit) {

    // ============ INICIO REFS ============ //
    const search = ref(false);
    // ============ FIN REFS ============ //

    // ============ INICIO MÉTODOS ============ //
    /**
     * Busca un tutor por su CI
     * @param {String} ciValue - Cédula de identidad del tutor
     */
    const searchTutor = (ciValue) => {
        const tutorEncontrado = props.tutores?.find(tutor => tutor.ci_tutor === ciValue);

        if (tutorEncontrado) {
            search.value = true;

            // Mapear todos los campos del tutor encontrado al formulario
            Object.keys(tutorEncontrado).forEach(key => {
                if (form[key] !== undefined) {
                    form[key] = tutorEncontrado[key];
                }
            });
        }
    };
    // ============ FIN MÉTODOS ============ //

    // ============ INICIO WATCHERS ============ //
    // Watch para el campo CI del tutor
    watch(() => form.ci_tutor, (newCI, oldCI) => {
        if (newCI && newCI.length >= 6) {
            const tutorEncontrado = props.tutores?.find(tutor => tutor.ci_tutor === newCI);

            if (tutorEncontrado) {
                searchTutor(newCI);
                emit('tutorEncontradoSms');
            } else if (search.value === true) {
                const ciValue = form.ci_tutor;
                form.reset();
                form.ci_tutor = ciValue;
                search.value = false;
            }
        } else if (search.value === true) {
            const ciValue = form.ci_tutor;
            form.reset();
            form.ci_tutor = ciValue;
            search.value = false;
        }
    });
    // ============ FIN WATCHERS ============ //

    return {
        search,
        searchTutor
    };
}
