/**
 * Utilidades para validación de datos
 */

// ============ INICIO VALIDADORES DE EMAIL ============ //
/**
 * Valida si un email es válido
 * @param {String} email - Email a validar
 * @returns {Boolean} - true si es válido
 */
export const isValidEmail = (email) => {
    if (!email) return false;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};
// ============ FIN VALIDADORES DE EMAIL ============ //

// ============ INICIO VALIDADORES DE CI ============ //
/**
 * Valida si una cédula de identidad es válida
 * @param {String} ci - Cédula de identidad
 * @returns {Boolean} - true si es válida
 */
export const isValidCI = (ci) => {
    if (!ci) return false;
    // Validar que solo contenga números
    const ciRegex = /^\d+$/;
    return ciRegex.test(ci) && ci.length >= 6 && ci.length <= 10;
};
// ============ FIN VALIDADORES DE CI ============ //

// ============ INICIO VALIDADORES DE TELÉFONO ============ //
/**
 * Valida si un número de teléfono es válido (Bolivia)
 * @param {String} telefono - Número de teléfono
 * @returns {Boolean} - true si es válido
 */
export const isValidTelefono = (telefono) => {
    if (!telefono) return false;
    // Validar teléfonos de Bolivia (8 dígitos)
    const telefonoRegex = /^\d{8}$/;
    return telefonoRegex.test(telefono);
};
// ============ FIN VALIDADORES DE TELÉFONO ============ //

// ============ INICIO VALIDADORES DE CAMPOS REQUERIDOS ============ //
/**
 * Valida si un campo requerido tiene valor
 * @param {any} value - Valor a validar
 * @returns {Boolean} - true si tiene valor
 */
export const isRequired = (value) => {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    if (Array.isArray(value)) return value.length > 0;
    return true;
};
// ============ FIN VALIDADORES DE CAMPOS REQUERIDOS ============ //

// ============ INICIO VALIDADORES DE FECHA ============ //
/**
 * Valida si una fecha es válida
 * @param {String} fecha - Fecha en formato YYYY-MM-DD
 * @returns {Boolean} - true si es válida
 */
export const isValidDate = (fecha) => {
    if (!fecha) return false;
    const date = new Date(fecha);
    return date instanceof Date && !isNaN(date);
};

/**
 * Valida si una fecha es mayor o igual a otra
 * @param {String} fecha1 - Primera fecha
 * @param {String} fecha2 - Segunda fecha
 * @returns {Boolean} - true si fecha1 >= fecha2
 */
export const isFechaMayorOigual = (fecha1, fecha2) => {
    if (!fecha1 || !fecha2) return false;
    const date1 = new Date(fecha1);
    const date2 = new Date(fecha2);
    return date1 >= date2;
};
// ============ FIN VALIDADORES DE FECHA ============ //

// ============ INICIO VALIDADORES DE RANGO ============ //
/**
 * Valida si un número está dentro de un rango
 * @param {Number} value - Valor a validar
 * @param {Number} min - Valor mínimo
 * @param {Number} max - Valor máximo
 * @returns {Boolean} - true si está en el rango
 */
export const isInRange = (value, min, max) => {
    const num = parseFloat(value);
    if (isNaN(num)) return false;
    return num >= min && num <= max;
};
// ============ FIN VALIDADORES DE RANGO ============ //

// ============ INICIO VALIDADORES DE LONGITUD ============ //
/**
 * Valida la longitud de un string
 * @param {String} value - Valor a validar
 * @param {Number} min - Longitud mínima
 * @param {Number} max - Longitud máxima
 * @returns {Boolean} - true si cumple la longitud
 */
export const isValidLength = (value, min, max) => {
    if (!value) return false;
    const length = value.toString().length;
    return length >= min && length <= max;
};
// ============ FIN VALIDADORES DE LONGITUD ============ //

// ============ INICIO VALIDADORES DE ARCHIVO ============ //
/**
 * Valida la extensión de un archivo
 * @param {String} fileName - Nombre del archivo
 * @param {Array} allowedExtensions - Extensiones permitidas ['.xlsx', '.xls']
 * @returns {Boolean} - true si la extensión es válida
 */
export const isValidFileExtension = (fileName, allowedExtensions) => {
    if (!fileName) return false;
    const fileExtension = '.' + fileName.split('.').pop().toLowerCase();
    return allowedExtensions.includes(fileExtension);
};

/**
 * Valida el tamaño de un archivo
 * @param {Number} fileSize - Tamaño del archivo en bytes
 * @param {Number} maxSizeMB - Tamaño máximo en MB
 * @returns {Boolean} - true si el tamaño es válido
 */
export const isValidFileSize = (fileSize, maxSizeMB) => {
    const maxSizeBytes = maxSizeMB * 1024 * 1024;
    return fileSize <= maxSizeBytes;
};
// ============ FIN VALIDADORES DE ARCHIVO ============ //

// ============ INICIO VALIDADORES DE PERMISOS ============ //
/**
 * Valida si se ha seleccionado al menos un permiso
 * @param {Array} permissions - Array de permisos seleccionados
 * @returns {Boolean} - true si hay al menos uno
 */
export const hasPermissions = (permissions) => {
    return Array.isArray(permissions) && permissions.length > 0;
};
// ============ FIN VALIDADORES DE PERMISOS ============ //
