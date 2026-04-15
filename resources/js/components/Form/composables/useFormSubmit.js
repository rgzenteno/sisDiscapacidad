// ============ INICIO IMPORTS ============ //
import { useFormValidation } from "./useFormValidation";
import { router } from "@inertiajs/vue3";
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

        // ✅ AGREGAR DATOS EXTRAÍDOS DE PDF/EXCEL
        const validation =
            Object.keys(fileValidation).length > 0
                ? fileValidation
                : form.archivo_validacion || {};

        // Validar formulario
        if (!validateForm(validation)) {
            emit("sinDatos");
            return;
        }

        // ✅ SERIALIZAR DATOS EXTRAÍDOS
        if (validation.extractedData) {
            const extracted = validation.extractedData;

            if (extracted.gestion) {
                form.gestion_extraida = JSON.stringify(extracted.gestion);
            }
            if (extracted.registros && extracted.registros.length > 0) {
                form.registros_extraidos = JSON.stringify(extracted.registros);
            }

            // ✅ IMPORTANTE: Limpiar archivo_validacion para no enviarlo
            delete form.archivo_validacion;
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

        // Validar que las contraseñas coincidan si ambos campos existen
        const tienePassword = props.fields.some(f => f.name === 'password');
        const tieneConfirmacion = props.fields.some(f => f.name === 'password_confirmation');

        if (tienePassword && tieneConfirmacion) {
            if (form.password && form.password_confirmation && form.password !== form.password_confirmation) {
                form.errors.password = 'Las contraseñas no coinciden';
                form.errors.password_confirmation = 'Las contraseñas no coinciden';
                emit("sinDatos");
                return;
            }
        }

        // Limpiar fechas si el carnet es indefinido
        if (form.indefinido == 1) {
            form.fecha_emision = null;
            form.fecha_vencimiento = null;
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

                    if (props.addTutor && props.idFor) {
                        form.id_persona = props.idFor;
                    }

                    Object.keys(form.data()).forEach((key) => {
                        if (form[key] !== null && form[key] !== undefined) {
                            if (
                                typeof form[key] === "string" ||
                                form[key] instanceof Blob
                            ) {
                                formData.append(key, form[key]);
                            } else if (typeof form[key] === "object") {
                                formData.append(key, JSON.stringify(form[key]));
                            } else {
                                formData.append(key, form[key]);
                            }
                        }
                    });

                    // ✅ AGREGAR CAMPOS EXTRAS QUE NO ESTÁN EN form.data()
                    if (form.gestion_extraida) {
                        formData.append(
                            "gestion_extraida",
                            form.gestion_extraida,
                        );
                    }
                    if (form.registros_extraidos) {
                        formData.append(
                            "registros_extraidos",
                            form.registros_extraidos,
                        );
                    }

                    // ✅ USAR router.post() EN LUGAR DE form.post() PARA QUE ENVÍE EL FORMDATA
                    router.post(route(props.submitRoute), formData, {
                        onSuccess: () => {
                            handleSuccess();
                        },
                        onError: (errors) => {
                            form.errors = errors;
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
                            if (errors.password) {
                                emit("sinDatos");
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
