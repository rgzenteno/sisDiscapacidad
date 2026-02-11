/**
 * Utilidades para formateo de datos
 */

// ============ INICIO FORMATEO DE MONEDA ============ //
/**
 * Formatea un número como moneda boliviana
 * @param {Number} amount - Monto a formatear
 * @returns {String} - Monto formateado (ej: "1.234,56")
 */
export const formatCurrency = (amount) => {
    if (!amount && amount !== 0) return '0';
    return parseFloat(amount).toLocaleString('es-BO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
};

/**
 * Formatea un número como moneda con símbolo
 * @param {Number} amount - Monto a formatear
 * @returns {String} - Monto formateado con símbolo (ej: "Bs. 1.234,56")
 */
export const formatCurrencyWithSymbol = (amount) => {
    return `Bs. ${formatCurrency(amount)}`;
};
// ============ FIN FORMATEO DE MONEDA ============ //

// ============ INICIO FORMATEO DE FECHA ============ //
/**
 * Formatea una fecha en formato YYYY-MM-DD a DD/MM/YYYY
 * @param {String} fecha - Fecha en formato YYYY-MM-DD
 * @returns {String} - Fecha formateada (ej: "26/01/2025")
 */
export const formatDate = (fecha) => {
    if (!fecha) return '';
    const date = new Date(fecha);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

/**
 * Formatea una fecha en formato largo
 * @param {String} fecha - Fecha en formato YYYY-MM-DD
 * @returns {String} - Fecha formateada (ej: "26 de enero de 2025")
 */
export const formatDateLong = (fecha) => {
    if (!fecha) return '';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-BO', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

/**
 * Formatea una fecha y hora
 * @param {String} fecha - Fecha en formato ISO
 * @returns {String} - Fecha y hora formateada
 */
export const formatDateTime = (fecha) => {
    if (!fecha) return '';
    const date = new Date(fecha);
    return date.toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
// ============ FIN FORMATEO DE FECHA ============ //

// ============ INICIO FORMATEO DE CI ============ //
/**
 * Formatea una cédula de identidad con su complemento
 * @param {String} ci - Cédula de identidad
 * @param {String} complemento - Complemento (opcional)
 * @returns {String} - CI formateada (ej: "12345678-1A")
 */
export const formatCI = (ci, complemento = '') => {
    if (!ci) return '';
    if (complemento && complemento.trim() !== '') {
        return `${ci}-${complemento.toUpperCase()}`;
    }
    return ci;
};
// ============ FIN FORMATEO DE CI ============ //

// ============ INICIO FORMATEO DE TEXTO ============ //
/**
 * Capitaliza la primera letra de cada palabra
 * @param {String} text - Texto a capitalizar
 * @returns {String} - Texto capitalizado
 */
/* export const capitalize = (text) => {
    if (!text) return '';
    return text
        .toLowerCase()
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}; */

/**
 * Convierte texto a mayúsculas
 * @param {String} text - Texto a convertir
 * @returns {String} - Texto en mayúsculas
 */
export const toUpperCase = (text) => {
    if (!text) return '';
    return text.toUpperCase();
};

/**
 * Trunca un texto largo
 * @param {String} text - Texto a truncar
 * @param {Number} maxLength - Longitud máxima
 * @returns {String} - Texto truncado con "..."
 */
export const truncateText = (text, maxLength = 50) => {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};
// ============ FIN FORMATEO DE TEXTO ============ //

// ============ INICIO FORMATEO DE TELÉFONO ============ //
/**
 * Formatea un número de teléfono boliviano
 * @param {String} telefono - Número de teléfono (8 dígitos)
 * @returns {String} - Teléfono formateado (ej: "7123-4567")
 */
export const formatTelefono = (telefono) => {
    if (!telefono) return '';
    const cleaned = telefono.replace(/\D/g, '');
    if (cleaned.length === 8) {
        return `${cleaned.substring(0, 4)}-${cleaned.substring(4)}`;
    }
    return telefono;
};
// ============ FIN FORMATEO DE TELÉFONO ============ //

// ============ INICIO FORMATEO DE TAMAÑO DE ARCHIVO ============ //
/**
 * Formatea el tamaño de un archivo
 * @param {Number} bytes - Tamaño en bytes
 * @returns {String} - Tamaño formateado (ej: "1.5 MB")
 */
export const formatFileSize = (bytes) => {
    if (!bytes || bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
// ============ FIN FORMATEO DE TAMAÑO DE ARCHIVO ============ //

// ============ INICIO FORMATEO DE PORCENTAJE ============ //
/**
 * Formatea un número como porcentaje
 * @param {Number} value - Valor decimal (ej: 0.75)
 * @returns {String} - Porcentaje formateado (ej: "75%")
 */
export const formatPercentage = (value) => {
    if (!value && value !== 0) return '0%';
    return `${(value * 100).toFixed(2)}%`;
};
// ============ FIN FORMATEO DE PORCENTAJE ============ //

// ============ INICIO FORMATEO DE CÓDIGO DE CARNET ============ //
/**
 * Genera código de carnet basado en nombre y fecha de nacimiento
 * @param {String} nombreCompleto - Nombre completo de la persona
 * @param {String} fechaNacimiento - Fecha de nacimiento (YYYY-MM-DD)
 * @returns {String} - Código de carnet (ej: "03-20001225JPS")
 */
export const generateCodigoCarnet = (nombreCompleto, fechaNacimiento) => {
    if (!nombreCompleto || !fechaNacimiento) return '';

    // Obtener iniciales del nombre
    const palabras = nombreCompleto.trim().split(' ');
    const iniciales = palabras
        .map(palabra => palabra.charAt(0).toUpperCase())
        .join('');

    // Formatear fecha (eliminar guiones)
    const fechaFormateada = fechaNacimiento.replace(/-/g, '');

    // Retornar código completo
    return `03-${fechaFormateada}${iniciales}`;
};
// ============ FIN FORMATEO DE CÓDIGO DE CARNET ============ //
