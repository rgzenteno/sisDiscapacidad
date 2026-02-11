/**
 * Utilidades para mapeo de datos de campos
 */

// ============ INICIO MAPEO DE DATOS EXISTENTES ============ //
/**
 * Mapea datos existentes a los campos del formulario
 * @param {Object} existingData - Datos existentes a mapear
 * @param {Array} fields - Campos del formulario
 * @param {Boolean} editMode - Si está en modo edición
 * @returns {Object} - Objeto con datos mapeados
 */
export const mapExistingDataToForm = (existingData, fields, editMode) => {
    if (!editMode || !existingData) return {};

    const mappedData = {};

    fields.forEach(field => {
        const fieldName = field.name;

        // Mapeo especial para permisos
        if (field.typeInput === 'checkbox_permissions' && existingData[fieldName]) {
            mappedData[fieldName] = Array.isArray(existingData[fieldName]) ?
                existingData[fieldName].map(p => typeof p === 'object' ? p.name : p) :
                [];
            return;
        }

        // Mapeo especial para datos de carnet
        if (existingData.carnet && existingData.carnet[fieldName] !== undefined) {
            mappedData[fieldName] = existingData.carnet[fieldName];
        } else if (existingData[fieldName] !== undefined) {
            mappedData[fieldName] = existingData[fieldName];
        }
    });

    return mappedData;
};

/**
 * Mapea datos de carnet a los campos del formulario
 * @param {Object} carnetData - Datos del carnet
 * @returns {Object} - Objeto con datos del carnet mapeados
 */
export const mapCarnetDataToForm = (carnetData) => {
    if (!carnetData) return {};

    return {
        doc: carnetData.doc || '',
        fecha_emision: carnetData.fecha_emision || '',
        fecha_vencimiento: carnetData.fecha_vencimiento || '',
        ...carnetData
    };
};

/**
 * Mapea datos de tutor a los campos del formulario
 * @param {Object} tutorData - Datos del tutor
 * @returns {Object} - Objeto con datos del tutor mapeados
 */
export const mapTutorDataToForm = (tutorData) => {
    if (!tutorData) return {};

    return {
        nombre_tutor: tutorData.nombre_tutor || '',
        apellido_tutor: tutorData.apellido_tutor || '',
        ci_tutor: tutorData.ci_tutor || '',
        complemento_tutor: tutorData.complemento_tutor || '',
        ...tutorData
    };
};
// ============ FIN MAPEO DE DATOS EXISTENTES ============ //

// ============ INICIO MAPEO DE OPCIONES ============ //
/**
 * Mapea permisos a opciones de checkbox
 * @param {Array} permissions - Array de permisos
 * @returns {Array} - Array de opciones formateadas
 */
export const mapPermissionsToOptions = (permissions) => {
    if (!permissions || !Array.isArray(permissions)) return [];

    return permissions.map(permission => ({
        value: permission.name || permission.id,
        text: permission.name || permission.display_name || 'Sin nombre'
    }));
};

/**
 * Mapea array genérico a opciones de select
 * @param {Array} items - Array de items
 * @param {String} valueKey - Clave para el valor
 * @param {String} textKey - Clave para el texto
 * @returns {Array} - Array de opciones formateadas
 */
export const mapArrayToOptions = (items, valueKey = 'id', textKey = 'name') => {
    if (!items || !Array.isArray(items)) return [];

    return items.map(item => ({
        value: item[valueKey],
        text: item[textKey] || 'Sin nombre'
    }));
};
// ============ FIN MAPEO DE OPCIONES ============ //

// ============ INICIO EXTRACCIÓN DE CAMPO ============ //
/**
 * Obtiene el campo complemento para cédula
 * @param {Array} fields - Array de campos
 * @returns {Object|null} - Campo complemento o null
 */
export const getComplementField = (fields) => {
    if (!fields || !Array.isArray(fields)) return null;

    return fields.find(field =>
        field.typeInput === 'comple' && field.typeCi === 'ci'
    );
};

/**
 * Obtiene campos por tipo
 * @param {Array} fields - Array de campos
 * @param {String} typeInput - Tipo de input a buscar
 * @returns {Array} - Array de campos del tipo especificado
 */
export const getFieldsByType = (fields, typeInput) => {
    if (!fields || !Array.isArray(fields)) return [];

    return fields.filter(field => field.typeInput === typeInput);
};

/**
 * Obtiene un campo específico por nombre
 * @param {Array} fields - Array de campos
 * @param {String} fieldName - Nombre del campo
 * @returns {Object|null} - Campo encontrado o null
 */
export const getFieldByName = (fields, fieldName) => {
    if (!fields || !Array.isArray(fields)) return null;

    return fields.find(field => field.name === fieldName);
};
// ============ FIN EXTRACCIÓN DE CAMPO ============ //

// ============ INICIO PREPARACIÓN DE DATOS PARA ENVÍO ============ //
/**
 * Prepara datos del formulario para envío
 * @param {Object} formData - Datos del formulario
 * @param {Object} additionalData - Datos adicionales a agregar
 * @returns {Object} - Datos preparados para envío
 */
export const prepareFormDataForSubmit = (formData, additionalData = {}) => {
    const preparedData = { ...formData };

    // Agregar datos adicionales
    Object.keys(additionalData).forEach(key => {
        if (additionalData[key] !== undefined && additionalData[key] !== null) {
            preparedData[key] = additionalData[key];
        }
    });

    // Limpiar campos vacíos (opcional)
    Object.keys(preparedData).forEach(key => {
        if (preparedData[key] === '' || preparedData[key] === null) {
            delete preparedData[key];
        }
    });

    return preparedData;
};

/**
 * Convierte objeto a FormData para envío de archivos
 * @param {Object} data - Datos a convertir
 * @returns {FormData} - FormData listo para envío
 */
export const convertToFormData = (data) => {
    const formData = new FormData();

    Object.keys(data).forEach(key => {
        if (data[key] !== null && data[key] !== undefined) {
            // Si es un archivo, agregarlo directamente
            if (data[key] instanceof File) {
                formData.append(key, data[key]);
            }
            // Si es un array, agregar cada elemento
            else if (Array.isArray(data[key])) {
                data[key].forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item);
                });
            }
            // Si es un objeto, convertir a JSON string
            else if (typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            }
            // Otros tipos, agregar directamente
            else {
                formData.append(key, data[key]);
            }
        }
    });

    return formData;
};
// ============ FIN PREPARACIÓN DE DATOS PARA ENVÍO ============ //
