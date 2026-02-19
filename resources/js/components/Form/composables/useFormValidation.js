// ============ INICIO IMPORTS ============ //
import {
    checkDistritoDuplicate,
    checkDiscapacidadDuplicate,
} from "../utils/duplicateCheck";
// ============ FIN IMPORTS ============ //

/**
 * Composable para validación de formularios
 * @param {Object} form - Formulario de Inertia
 * @param {Object} props - Props del componente
 * @param {Function} emit - Función emit
 * @returns {Object} - { validateForm, validarDuplicados, validarFechaMinima }
 */
export function useFormValidation(form, props, emit) {
    // ============ INICIO VALIDACIÓN DUPLICADOS ============ //
    /**
     * Valida si hay duplicados de distrito o discapacidad
     */
    const validarDuplicados = () => {
        // Validar distrito duplicado
        if (props.submitRoute === "dropdown.store" && form.distrito) {
            const existe = checkDistritoDuplicate(
                form.distrito,
                props.distritos,
            );
            if (existe) {
                emit("encontrado");
                form.reset();
                return false;
            }
        }

        // Validar discapacidad duplicada
        if (props.submitRoute === "dropdown.addDis" && form.discapacidad) {
            const existe = checkDiscapacidadDuplicate(
                form.discapacidad,
                props.discapacidad,
            );
            if (existe) {
                emit("encontrado");
                form.reset();
                return false;
            }
        }

        return true;
    };
    // ============ FIN VALIDACIÓN DUPLICADOS ============ //

    // ============ INICIO VALIDACIÓN FECHA ============ //
    /**
     * Valida que la fecha no sea anterior a la fecha de inicio
     */
    const validarFechaMinima = () => {
        let fechaValida = true;

        props.fields.forEach((field) => {
            if (field.type === "date" && form[field.name]) {
                if (props.data?.fecha_inicio) {
                    const fechaMinima = new Date(props.data.fecha_inicio);
                    const fechaNueva = new Date(form[field.name]);

                    if (fechaNueva < fechaMinima) {
                        form.errors[field.name] =
                            `La fecha no puede ser anterior al ${fechaMinima.toLocaleDateString("es-BO")}`;
                        fechaValida = false;
                    }
                }
            }
        });

        return fechaValida;
    };
    // ============ FIN VALIDACIÓN FECHA ============ //

    // ============ INICIO VALIDACIÓN GENERAL ============ //
    /**
     * Valida todos los campos del formulario
     */
    const validateForm = (fileValidation) => {
        form.errors = {};

        // Validar archivos
        if (fileValidation?.isValid === false) {
            const fileField = props.fields.find(
                (f) => f.typeInput === "file_upload",
            );
            if (fileField) {
                form.errors[fileField.name] =
                    "El archivo no contiene las columnas requeridas";
            }
        }

        // Validar presupuesto anual y presupuesto si es necesario
        if (props.gestion && props.gestion.length > 0) {
            const mostrarPresupuestoAnual =
                !props.dataGestion ||
                !props.dataGestion.some(
                    (g) => g.gestion === props.gestion.gestion,
                );

            if (
                mostrarPresupuestoAnual &&
                (!form.presupuesto_anual ||
                    form.presupuesto_anual.trim() === "")
            ) {
                form.errors.presupuesto_anual =
                    "El presupuesto anual es obligatorio.";
            }
            if (!form.presupuesto || form.presupuesto === "") {
                form.errors.presupuesto = "El presupuesto es obligatorio.";
            }
        }

        // Validar campos requeridos
        props.fields.forEach((field) => {
            if (!field.name) return;

            const fieldValue = form[field.name];

            // Validación específica para checkboxes de permisos
            if (field.typeInput === "checkbox_permissions" && field.required) {
                if (
                    !fieldValue ||
                    !Array.isArray(fieldValue) ||
                    fieldValue.length === 0
                ) {
                    form.errors[field.name] =
                        "Debe seleccionar al menos un permiso.";
                }
            }
            // Validación general para campos requeridos
            else if (field.required) {
                // Verificar si el campo está realmente vacío (null, undefined, o string vacío)
                // Pero permitir 0 como valor válido
                const isEmpty =
                    fieldValue === null ||
                    fieldValue === undefined ||
                    (typeof fieldValue === "string" && !fieldValue.trim());

                if (isEmpty) {
                    form.errors[field.name] =
                        `${field.label || field.placeholder || field.name} es obligatorio.`;
                }
            }
        });

        return Object.keys(form.errors).length === 0;
    };
    // ============ FIN VALIDACIÓN GENERAL ============ //

    return {
        validateForm,
        validarDuplicados,
        validarFechaMinima,
    };
}
