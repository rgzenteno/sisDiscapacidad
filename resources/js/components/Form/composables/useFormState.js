// ============ INICIO IMPORTS ============ //
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useDateCalculation } from './useDateCalculation';
import { useTutorSearch } from './useTutorSearch';
// ============ FIN IMPORTS ============ //

/**
 * Composable para manejar el estado del formulario
 * @param {Object} props - Props del componente Form
 * @param {Function} emit - Función emit del componente
 * @returns {Object} - { form, updateField, resetForm }
 */
export function useFormState(props, emit) {
    // ============ INICIO INICIALIZACIÓN FORM ============ //
    const form = useForm({
        // Mapear todos los campos desde props.fields
        ...((props.fields || []).reduce((acc, field) => {
            // Checkboxes de pago
            if (field.typeInput === 'checkbox_pago') {
                field.options.forEach(option => {
                    if (option.value === 'efectivo') {
                        acc[option.value] = 1;
                    } else {
                        acc[option.value] = props.editMode ?
                            props.existingData[option.value] || 0 : 0;
                    }
                });
            }
            // Checkboxes normales
            else if (field.typeInput === 'check') {
                field.options.forEach(option => {
                    acc[option.value] = props.editMode ?
                        props.existingData[option.value] || 0 : 0;
                });
            }
            // File upload
            else if (field.typeInput === 'file_upload') {
                acc[field.name] = null;
            }
            // Checkboxes de permisos
            else if (field.typeInput === 'checkbox_permissions') {
                acc[field.name] = props.editMode && props.existingData[field.name] ?
                    props.existingData[field.name] : [];
            }
            // Campo complemento
            else if (field.typeInput === 'comple') {
                acc[field.name] = props.editMode ?
                    (props.existingData[field.name] ?? '') : '';
            }
            // Resto de campos
            else if (field.typeInput !== 'check') {
                acc[field.name] = props.editMode && props.existingData[field.name] ?
                    props.existingData[field.name] :
                    field.type === 'number' ? '' :
                    field.type === 'boolean' ? false :
                    field.typeInput === 'time' ? field.value || '' : '';
            }
            return acc;
        }, {})),
        id_persona: null,
    });
    // ============ FIN INICIALIZACIÓN FORM ============ //

    // ============ INICIO GENERACIÓN CÓDIGO CARNET ============ //
    // Generación automática de código de carnet
    if (props.nombreFor && props.fechaNacimiento) {
        const palabras = props.nombreFor.trim().split(' ');
        const iniciales = palabras.map(palabra => palabra.charAt(0).toUpperCase()).join('');
        const fechaFormateada = props.fechaNacimiento.replace(/-/g, '');
        const resultado = `03-${fechaFormateada}${iniciales}`;
        form.doc = resultado;
    }
    // ============ FIN GENERACIÓN CÓDIGO CARNET ============ //

    // ============ INICIO COMPOSABLES ============ //
    // Cálculo de fecha de vencimiento
    useDateCalculation(form);

    // Búsqueda de tutores
    const { search } = useTutorSearch(form, props, emit);
    // ============ FIN COMPOSABLES ============ //

    // ============ INICIO WATCHERS ============ //
    // Watch para cargar permisos en las opciones del campo
    watch(() => props.permissions, (newPermissions) => {
        if (newPermissions && newPermissions.length > 0) {
            const permissionsField = props.fields.find(f => f.typeInput === 'checkbox_permissions');
            if (permissionsField) {
                permissionsField.options = newPermissions.map(permission => ({
                    value: permission.name,
                    text: permission.name
                }));
            }
        }
    }, { immediate: true });

    // Watch para actualizar campos con datos existentes
    watch(() => props.existingData, (newData) => {
        if (props.editMode && newData) {
            (props.fields || []).forEach(field => {
                const fieldName = field.name;

                // Caso especial para permisos
                if (field.typeInput === 'checkbox_permissions' && newData[fieldName]) {
                    form[fieldName] = Array.isArray(newData[fieldName]) ?
                        newData[fieldName].map(p => typeof p === 'object' ? p.name : p) :
                        [];
                    return;
                }

                // Si hay un objeto carnet, intentar obtener el valor de ahí primero
                if (newData.carnet && newData.carnet[fieldName] !== undefined) {
                    form[fieldName] = newData.carnet[fieldName];
                } else if (newData[fieldName] !== undefined) {
                    form[fieldName] = newData[fieldName];
                }
            });
        }
    }, { deep: true, immediate: true });

    // Watch para cargar datos del tutor automáticamente
    watch(() => props.existingData, (newData) => {
        if (newData && newData.tiene_tutor === true) {
            if (newData.nombre_tutor && newData.apellido_tutor) {
                form.nombre_tutor = newData.nombre_tutor;
                form.apellido_tutor = newData.apellido_tutor;

                if (newData.ci_tutor) {
                    form.ci_tutor = newData.ci_tutor;
                }
                if (newData.complemento_tutor) {
                    form.complemento_tutor = newData.complemento_tutor;
                }
            }
        }
    }, { immediate: true, deep: true });
    // ============ FIN WATCHERS ============ //

    // ============ INICIO MÉTODOS ============ //
    /**
     * Actualiza un campo del formulario
     */
    const updateField = (fieldName, value) => {
        form[fieldName] = value;

        // Limpiar error del campo si existe
        if (form.errors[fieldName]) {
            delete form.errors[fieldName];
        }
    };

    /**
     * Resetea el formulario
     */
    const resetForm = () => {
        form.reset();
    };
    // ============ FIN MÉTODOS ============ //

    return {
        form,
        updateField,
        resetForm,
        search
    };
}
