/**
 * Utilidades para verificar duplicados
 */

// ============ INICIO VERIFICACIÓN DE DUPLICADOS ============ //
/**
 * Verifica si un distrito ya existe
 * @param {String} distrito - Nombre del distrito a verificar
 * @param {Array} distritos - Array de distritos existentes
 * @returns {Boolean} - true si existe duplicado
 */
export const checkDistritoDuplicate = (distrito, distritos) => {
    if (!distrito || !distritos) return false;

    const distritoNormalizado = distrito.toString().trim().toLowerCase();

    return distritos.some(d =>
        d.distrito?.toString().trim().toLowerCase() === distritoNormalizado
    );
};

/**
 * Verifica si una discapacidad ya existe
 * @param {String} discapacidad - Nombre de la discapacidad a verificar
 * @param {Array} discapacidades - Array de discapacidades existentes
 * @returns {Boolean} - true si existe duplicado
 */
export const checkDiscapacidadDuplicate = (discapacidad, discapacidades) => {
    if (!discapacidad || !discapacidades) return false;

    const discapacidadNormalizada = discapacidad.toString().trim().toLowerCase();

    return discapacidades.some(d =>
        d.discapacidad?.toString().trim().toLowerCase() === discapacidadNormalizada
    );
};

/**
 * Verifica si una cédula de identidad ya existe
 * @param {String} ci - Cédula de identidad
 * @param {Array} personas - Array de personas existentes
 * @returns {Boolean} - true si existe duplicado
 */
export const checkCIDuplicate = (ci, personas) => {
    if (!ci || !personas) return false;

    const ciNormalizado = ci.toString().trim();

    return personas.some(p =>
        p.ci_persona?.toString().trim() === ciNormalizado
    );
};

/**
 * Verifica si un email ya existe
 * @param {String} email - Email a verificar
 * @param {Array} usuarios - Array de usuarios existentes
 * @returns {Boolean} - true si existe duplicado
 */
export const checkEmailDuplicate = (email, usuarios) => {
    if (!email || !usuarios) return false;

    const emailNormalizado = email.toString().trim().toLowerCase();

    return usuarios.some(u =>
        u.email?.toString().trim().toLowerCase() === emailNormalizado
    );
};

/**
 * Verifica si un valor ya existe en un array de objetos
 * @param {String} value - Valor a verificar
 * @param {Array} items - Array de items
 * @param {String} fieldName - Nombre del campo a comparar
 * @returns {Boolean} - true si existe duplicado
 */
export const checkDuplicate = (value, items, fieldName) => {
    if (!value || !items || !fieldName) return false;

    const valueNormalizado = value.toString().trim().toLowerCase();

    return items.some(item =>
        item[fieldName]?.toString().trim().toLowerCase() === valueNormalizado
    );
};
// ============ FIN VERIFICACIÓN DE DUPLICADOS ============ //
