// ============ INICIO IMPORTS ============ //
import { useFormValidation } from "./useFormValidation";
// ============ FIN IMPORTS ============ //

/**
 * Composable para manejar el envío del formulario
 * @param {Object} form - Formulario de Inertia
 * @param {Object} props - Props del componente
 * @param {Function} emit - Función emit
 * @returns {Object} - { handleSubmit, handleCancel, handleOmitir }
 */
export function useFormSubmit(form, props, emit) {
    // ============ INICIO COMPOSABLES ============ //
    const { validateForm, validarDuplicados, validarFechaMinima } =
        useFormValidation(form, props, emit);
    // ============ FIN COMPOSABLES ============ //

    // ============ INICIO MÉTODOS ============ //
    /**
     * Maneja el éxito del envío
     */
    const handleSuccess = () => {
        form.reset();
        emit("add");
    };

    /**
     * Maneja la cancelación del formulario
     */
    const handleCancel = () => {
        emit("cancel");
        form.reset();
    };

    /**
     * Maneja el botón omitir
     */
    const handleOmitir = () => {
        form.reset();
        emit("omitir");
    };

    /**
     * Maneja el envío del formulario
     */
    const handleSubmit = async (fileValidation = {}) => {
        // Validar duplicados primero
        if (!validarDuplicados()) {
            return;
        }

        // Validar fechas
        if (!validarFechaMinima()) {
            emit("fechaInvalida");
            return;
        }

        // Validar código de carnet
        const campoDoc = props.fields.find((f) => f.name === "doc");
        if (campoDoc && form.doc) {
            const regexCarnet = /^03-\d{8}[A-Z]+$/;
            if (!regexCarnet.test(form.doc)) {
                form.errors.doc =
                    "El formato no es válido. Debe ser: 03-YYYYMMDD-INICIALES";
                emit("sinDatos");
                return;
            }
        }

        // Validar formulario
        if (!validateForm(fileValidation)) {
            emit("sinDatos");
            return;
        }

        // Agregar datos adicionales al formulario
        if (props.data.id_persona && props.data.id_estado) {
            form.id_persona = props.data.id_persona;
            form.id_estado = props.data.id_estado;
        }

        if (props.data.tipo === "gestion") {
            if (props.data.id) {
                form.id_gestion = props.data.id;
            }
            if (props.data.mes) {
                form.mes = props.data.mes;
            }
            if (props.data.gestion) {
                form.gestion = props.data.gestion;
            }
        }

        if (props.claveForanea === "id_persona") {
            form[props.claveForanea] = props.idFor;
        }

        if (props.claveForanea === "id_tutor") {
            form.id_tutor = props.idFor;
        }

        const isEditing =
            props.editMode && Object.keys(props.existingData).length > 0;

        try {
            if (isEditing) {
                await form.put(route(props.submitRoute, props.idFor), {
                    onSuccess: () => {
                        handleSuccess();
                    },
                    onError: (errors) => {
                        console.error("Error en actualización:", errors);
                    },
                });
            } else {
                const hasFiles = props.fields.some(
                    (field) =>
                        field.typeInput === "file_upload" && form[field.name],
                );

                if (hasFiles) {
                    const formData = new FormData();

                    // ✅ AGREGAR id_persona antes de crear FormData
                    if (props.addTutor && props.idFor) {
                        form.id_persona = props.idFor;
                    }

                    Object.keys(form.data()).forEach((key) => {
                        if (form[key] !== null && form[key] !== undefined) {
                            formData.append(key, form[key]);
                        }
                    });

                    await form.post(route(props.submitRoute), {
                        data: formData,
                        forceFormData: true,
                        onSuccess: () => {
                            handleSuccess();
                        },
                        onError: (errors) => {
                            if (errors.ci_persona) {
                                emit("close", form.ci_persona);
                            }
                        },
                    });
                } else {
                    if (props.addTutor && props.idFor) {
                        form.id_persona = props.idFor;
                    }

                    await form.post(route(props.submitRoute), {
                        onSuccess: () => {
                            handleSuccess();
                        },
                        onError: (errors) => {
                            if (errors.ci_persona) {
                                emit("close", form.ci_persona);
                            }
                        },
                    });
                }
            }
        } catch (error) {
            console.error("Error general:", error);
        }
    };
    // ============ FIN MÉTODOS ============ //

    return {
        handleSubmit,
        handleCancel,
        handleOmitir,
    };
}
