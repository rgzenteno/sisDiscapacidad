// ============ INICIO IMPORTS ============ //
import { ref } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para validar columnas de archivos Excel
 * @returns {Object} - { fileValidation, validateExcelColumns }
 */
export function useExcelValidation() {

    // ============ INICIO REFS ============ //
    const fileValidation = ref({
        isValid: null,
        message: '',
        missingColumns: [],
        foundColumns: [],
        totalRows: 0,
        headerRow: 0
    });
    // ============ FIN REFS ============ //

    // ============ INICIO MÉTODOS ============ //
    /**
     * Valida las columnas de un archivo Excel
     * @param {File} file - Archivo Excel a validar
     * @param {Array} requiredColumns - Columnas requeridas
     * @returns {Promise<Object>} - Resultado de la validación
     */
    const validateExcelColumns = async (file, requiredColumns) => {
        return new Promise((resolve) => {
            const reader = new FileReader();

            reader.onload = async (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });

                    // Leer la primera hoja
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];

                    // Convertir a JSON todas las filas
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, {
                        header: 1,
                        defval: '',
                        blankrows: false
                    });

                    if (jsonData.length === 0) {
                        resolve({
                            isValid: false,
                            message: 'El archivo Excel está vacío',
                            missingColumns: requiredColumns,
                            foundColumns: []
                        });
                        return;
                    }

                    // Buscar la fila de encabezados (hasta la fila 5)
                    let headers = [];
                    let headerRowIndex = -1;

                    for (let i = 0; i < Math.min(5, jsonData.length); i++) {
                        const row = jsonData[i];

                        // Verificar si esta fila contiene encabezados
                        // Normalizamos y limpiamos cada celda
                        const normalizedRow = row.map(cell =>
                            cell?.toString()
                                .trim()
                                .toLowerCase()
                                .replace(/\s+/g, ' ') // Múltiples espacios a uno solo
                                .replace(/\./g, '') // Quitar puntos
                        ).filter(cell => cell !== '');

                        // Si encontramos columnas similares a las requeridas, es la fila de encabezados
                        const requiredColsNormalized = requiredColumns.map(col =>
                            col.toLowerCase()
                                .replace(/\s+/g, ' ')
                                .replace(/\./g, '')
                        );

                        const matchCount = normalizedRow.filter(cell =>
                            requiredColsNormalized.some(reqCol =>
                                cell.includes(reqCol) || reqCol.includes(cell)
                            )
                        ).length;

                        // Si coinciden al menos el 50% de las columnas, es la fila de encabezados
                        if (matchCount >= requiredColsNormalized.length * 0.5) {
                            headers = normalizedRow;
                            headerRowIndex = i;
                            break;
                        }
                    }

                    if (headerRowIndex === -1 || headers.length === 0) {
                        resolve({
                            isValid: false,
                            message: 'No se encontraron encabezados válidos en las primeras 5 filas',
                            missingColumns: requiredColumns,
                            foundColumns: []
                        });
                        return;
                    }

                    // Normalizar columnas requeridas
                    const requiredColsNormalized = requiredColumns.map(col =>
                        col.toLowerCase()
                            .replace(/\s+/g, ' ')
                            .replace(/\./g, '')
                    );

                    // Verificar qué columnas se encontraron
                    const foundColumns = [];
                    const missingColumns = [];

                    requiredColsNormalized.forEach((reqCol, index) => {
                        const found = headers.some(header => {
                            // Comparación flexible: si el header contiene la columna requerida o viceversa
                            return header.includes(reqCol) || reqCol.includes(header);
                        });

                        if (found) {
                            foundColumns.push(requiredColumns[index]);
                        } else {
                            missingColumns.push(requiredColumns[index]);
                        }
                    });

                    const isValid = missingColumns.length === 0;

                    // Calcular filas de datos (total - filas de encabezado - filas vacías)
                    const dataRows = jsonData.length - headerRowIndex - 1;

                    resolve({
                        isValid,
                        message: isValid
                            ? `✓ Todas las columnas requeridas están presentes (Encabezados en fila ${headerRowIndex + 1})`
                            : `✗ Faltan columnas requeridas`,
                        missingColumns,
                        foundColumns,
                        totalRows: dataRows > 0 ? dataRows : 0,
                        headerRow: headerRowIndex + 1
                    });

                } catch (error) {
                    console.error('Error al procesar Excel:', error);
                    resolve({
                        isValid: false,
                        message: 'Error al leer el archivo Excel',
                        missingColumns: requiredColumns,
                        foundColumns: []
                    });
                }
            };

            reader.onerror = () => {
                resolve({
                    isValid: false,
                    message: 'Error al cargar el archivo',
                    missingColumns: requiredColumns,
                    foundColumns: []
                });
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
