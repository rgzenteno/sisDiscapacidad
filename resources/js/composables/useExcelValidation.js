// ============ INICIO IMPORTS ============ //
import { ref } from 'vue';
import * as XLSX from 'xlsx';
// ============ FIN IMPORTS ============ //

/**
 * Composable para validar columnas de archivos Excel
 * @returns {Object} - Métodos y estados para validación de Excel
 */
export function useExcelValidation() {

    // ============ INICIO REFS ============ //
    const fileValidation = ref({
        isValid: false,
        message: '',
        missingColumns: [],
        foundColumns: []
    });
    // ============ FIN REFS ============ //

    // ============ INICIO MÉTODOS ============ //
    /**
     * Normaliza el nombre de una columna (elimina espacios, convierte a minúsculas)
     */
    const normalizeColumnName = (columnName) => {
        if (!columnName) return '';
        return columnName
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, ' '); // Reemplaza múltiples espacios por uno solo
    };

    /**
     * Valida las columnas de un archivo Excel
     * @param {File} file - Archivo Excel a validar
     * @param {Array} requiredColumns - Array de objetos con las columnas requeridas
     * @returns {Object} - Resultado de la validación
     */
    const validateExcelColumns = async (file, requiredColumns) => {
        return new Promise((resolve) => {
            const reader = new FileReader();

            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });

                    // Obtener la primera hoja
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];

                    // Convertir a JSON para obtener los headers (solo primeras 3 filas)
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

                    if (jsonData.length === 0) {
                        const result = {
                            isValid: false,
                            message: '❌ El archivo está vacío',
                            missingColumns: requiredColumns.map(col => col.nombre),
                            foundColumns: []
                        };
                        fileValidation.value = result;
                        resolve(result);
                        return;
                    }

                    // BUSCAR EN LAS PRIMERAS 3 FILAS (como tu ejemplo)
                    const firstThreeRows = jsonData.slice(0, 3);
                    let fileHeaders = [];

                    // Buscar la fila que contiene los headers
                    for (let row of firstThreeRows) {
                        if (row && row.length > 0) {
                            const normalizedRow = row.map(header => normalizeColumnName(header));
                            // Verificar si esta fila tiene alguna de las columnas requeridas
                            const hasRequiredColumn = requiredColumns.some(reqCol =>
                                normalizedRow.some(header => header === normalizeColumnName(reqCol.nombre))
                            );
                            if (hasRequiredColumn) {
                                fileHeaders = normalizedRow;
                                break;
                            }
                        }
                    }

                    // Si no se encontraron headers en las primeras 3 filas
                    if (fileHeaders.length === 0) {
                        const result = {
                            isValid: false,
                            message: 'formato_incorrecto',
                            missingColumns: requiredColumns.map((col) => col.nombre),
                            foundColumns: []
                        };
                        fileValidation.value = result;
                        resolve(result);
                        return;
                    }

                    // Validar columnas requeridas
                    const missingColumns = [];
                    const foundColumns = [];

                    requiredColumns.forEach(reqCol => {
                        const normalizedRequired = normalizeColumnName(reqCol.nombre);
                        const isFound = fileHeaders.some(header => header === normalizedRequired);

                        if (isFound) {
                            foundColumns.push(reqCol.nombre);
                        } else if (reqCol.obligatorio) {
                            missingColumns.push(reqCol.nombre);
                        }
                    });

                    // Crear resultado de validación
                    const isValid = missingColumns.length === 0;
                    const result = {
                        isValid,
                        message: isValid
                            ? '✓ Todas las columnas requeridas están presentes'
                            : `❌ Faltan columnas obligatorias: ${missingColumns.join(', ')}`,
                        missingColumns,
                        foundColumns
                    };

                    fileValidation.value = result;
                    resolve(result);

                } catch (error) {
                    console.error('Error al validar Excel:', error);
                    const result = {
                        isValid: false,
                        message: '❌ Error al leer el archivo. Asegúrate de que sea un Excel válido.',
                        missingColumns: requiredColumns.map(col => col.nombre),
                        foundColumns: []
                    };
                    fileValidation.value = result;
                    resolve(result);
                }
            };

            reader.onerror = () => {
                const result = {
                    isValid: false,
                    message: '❌ Error al cargar el archivo',
                    missingColumns: requiredColumns.map(col => col.nombre),
                    foundColumns: []
                };
                fileValidation.value = result;
                resolve(result);
            };

            reader.readAsArrayBuffer(file);
        });
    };
    // ============ FIN MÉTODOS ============ //

    return {
        fileValidation,
        validateExcelColumns
    };
}
