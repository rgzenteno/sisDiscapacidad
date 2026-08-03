// ============ INICIO IMPORTS ============ //
import { ref } from "vue";
import pdfjsLib from "@/utils/pdfConfig";
// ============ FIN IMPORTS ============ //

export function usePdfValidation() {
    // ============ INICIO REFS ============ //
    const pdfValidation = ref({
        isValid: null,
        message: "",
        missingFields: [],
        foundFields: [],
        extractedData: {
            gestion: null,
            registros: [],
        },
        totalRows: 0,
    });
    // ============ FIN REFS ============ //

    // ============ INICIO MÉTODOS ============ //

    // Cache de PDFs ya cargados para evitar re-parseo
    const pdfCache = new WeakMap();

    const validatePdfStructure = async (file, expectedGestion = null) => {
        return new Promise((resolve) => {
            const reader = new FileReader();

            reader.onload = async (e) => {
                try {
                    const typedArray = new Uint8Array(e.target.result);

                    // Cargar PDF con opciones de rendimiento
                    const pdf = await pdfjsLib.getDocument({
                        data: typedArray,
                        // ✅ Deshabilitar features innecesarias para acelerar carga
                        disableFontFace: true,
                        disableRange: false,
                        disableStream: false,
                        isEvalSupported: false,
                    }).promise;

                    // ✅ Obtener primera página y extraer texto en paralelo donde sea posible
                    const firstPage = await pdf.getPage(1);
                    const firstTextContent = await firstPage.getTextContent({
                        // ✅ Evitar normalización innecesaria de espacios
                        normalizeWhitespace: false,
                        disableCombineTextItems: false,
                    });

                    const firstPageText = firstTextContent.items
                        .map((item) => item.str)
                        .join(" ");
                    const normalizedFirstPage = firstPageText.toUpperCase();

                    // ✅ Validar campos requeridos con un solo regex combinado (más rápido)
                    const fieldPatterns = [
                        { pattern: /GESTI[OÓ]N/i, name: "GESTIÓN" },
                        { pattern: /N[°º]/i, name: "N°" },
                        {
                            pattern: /CARNET\s*DE\s*IDENTI\s*DAD/i,
                            name: "CARNET DE IDENTIDAD",
                        },
                        {
                            pattern: /NOMBRE\s*COMPL\s*ETO/i,
                            name: "NOMBRE COMPLETO",
                        },
                        { pattern: /CEL\s*UL\s*AR/i, name: "CELULAR" },
                    ];

                    const foundFields = [];
                    const missingFields = [];

                    // ✅ Un solo bucle con early exit si hay demasiados campos faltantes
                    for (const field of fieldPatterns) {
                        if (field.pattern.test(normalizedFirstPage)) {
                            foundFields.push(field.name);
                        } else {
                            missingFields.push(field.name);
                            // Si faltan más de 2 campos ya no es el formato correcto, salir antes
                            if (missingFields.length > 2) break;
                        }
                    }

                    if (missingFields.length > 0) {
                        return resolve({
                            isValid: false,
                            message: "PDF Desconocido",
                            missingFields,
                            foundFields,
                            extractedData: { gestion: null, registros: [] },
                            totalRows: 0,
                        });
                    }

                    const gestionMatch = firstPageText.match(
                        /GESTIÓN[:\s]*(\d{1,2})\/(\d{4})/i,
                    );
                    if (!gestionMatch) {
                        return resolve({
                            isValid: false,
                            message:
                                "No se encontró la GESTIÓN en el formato esperado (MM/AAAA)",
                            missingFields: ["GESTIÓN"],
                            foundFields,
                            extractedData: { gestion: null, registros: [] },
                            totalRows: 0,
                        });
                    }

                    const extractedGestion = {
                        mes: parseInt(gestionMatch[1]),
                        año: parseInt(gestionMatch[2]),
                    };

                    // ✅ Extraer texto de TODAS las páginas EN PARALELO con Promise.all
                    const pagePromises = [];
                    for (let pageNum = 2; pageNum <= pdf.numPages; pageNum++) {
                        pagePromises.push(
                            pdf.getPage(pageNum).then((page) =>
                                page
                                    .getTextContent({
                                        normalizeWhitespace: false,
                                    })
                                    .then((tc) =>
                                        tc.items
                                            .map((item) => item.str)
                                            .join(" "),
                                    ),
                            ),
                        );
                    }

                    const remainingPages = await Promise.all(pagePromises);
                    let fullText = [firstPageText, ...remainingPages].join(
                        "\n",
                    );

                    // Corregir separación incorrecta de la letra T
                    fullText = fullText
                        .replace(/([A-ZÁÉÍÓÚÑ]+)T ([A-ZÁÉÍÓÚÑ]+)/g, "$1T$2")
                        .replace(/\bT ([A-ZÁÉÍÓÚÑ]{2,})/g, "T$1");

                    // Validar coincidencia de gestión
                    if (expectedGestion) {
                        if (
                            extractedGestion.mes !== expectedGestion.mes ||
                            extractedGestion.año !== expectedGestion.año
                        ) {
                            resolve({
                                isValid: false,
                                message: 'PDF Incorrecto',
                                missingFields: [],
                                foundFields,
                                extractedData: {
                                    gestion: extractedGestion,
                                    gestionEsperada: expectedGestion,
                                    registros: [],
                                },
                                totalRows: 0,
                            });
                            return;
                        }
                    }

                    // ============ EXTRAER REGISTROS (CI y NOMBRE) ============ //
                    const registros = [];
                    const tokens = fullText.split(/\s+/);

                    // ✅ Precompilar regex fuera del bucle
                    const reOrden = /^\d{1,4}$/;
                    const reCI = /^\d{6,10}$/;
                    const reCelular = /^\d{8,10}$/;
                    const reNombre = /^[A-ZÑÁÉÍÓÚ]+$/i;
                    const reEncabezado =
                        /^(DEPARTAMENTO|PROVINCIA|MUNICIPIO|GESTIÓN|GESTION|FECHA|ELABORACIÓN|ELABORACION|INFORME|HABILITADAS|COBRO|BONO|DISCAPACIDAD|PERSONAS|SACABA|CHAPARE|COCHABAMBA)$/i;
                    const reNombreInvalido =
                        /DEPARTAMENTO|PROVINCIA|MUNICIPIO|INFORME|CARNET|NOMBRE|CELULAR|GESTION|FECHA/i;

                    let i = 0;
                    while (i < tokens.length) {
                        const token = tokens[i];

                        if (reOrden.test(token)) {
                            const siguiente = tokens[i + 1];

                            if (siguiente && reCI.test(siguiente)) {
                                const numero = token;
                                const ci = siguiente;
                                i += 2;

                                const nombreTokens = [];

                                while (i < tokens.length) {
                                    const t = tokens[i];

                                    if (
                                        reOrden.test(t) &&
                                        tokens[i + 1] &&
                                        reCI.test(tokens[i + 1])
                                    ) {
                                        break;
                                    }
                                    if (reCelular.test(t)) {
                                        i++;
                                        continue;
                                    }
                                    if (reEncabezado.test(t)) {
                                        i++;
                                        continue;
                                    }
                                    if (reNombre.test(t))
                                        nombreTokens.push(t.toUpperCase());

                                    i++;
                                }

                                const nombre = nombreTokens.join(" ").trim();
                                const palabrasReservadas = new Set([
                                    'CARNET', 'NOMBRE', 'CELULAR', 'GESTION', 'GESTIÓN',
                                    'FECHA', 'ELABORACION', 'ELABORACIÓN', 'INFORME',
                                    'DEPARTAMENTO', 'PROVINCIA', 'MUNICIPIO'
                                ]);
                                const todasSonReservadas = nombre.split(' ').every(p => palabrasReservadas.has(p));

                                if (nombre.length > 3 && !todasSonReservadas) {
                                    registros.push({ numero, ci, nombre });
                                }

                                continue;
                            }
                        }

                        i++;
                    }

                    if (registros.length === 0) {
                        return resolve({
                            isValid: false,
                            message:
                                "No se encontraron registros válidos en el PDF",
                            missingFields: [],
                            foundFields,
                            extractedData: {
                                gestion: extractedGestion,
                                registros: [],
                            },
                            totalRows: 0,
                        });
                    }

                    resolve({
                        isValid: true,
                        message: "PDF válido",
                        missingFields: [],
                        foundFields,
                        extractedData: {
                            gestion: extractedGestion,
                            gestionEsperada: null,
                            registros,
                        },
                        totalRows: registros.length,
                    });
                } catch (error) {
                    console.error("Error al procesar PDF:", error);
                    resolve({
                        isValid: false,
                        message: "Error al leer el archivo PDF",
                        missingFields: [],
                        foundFields: [],
                        extractedData: { gestion: null, registros: [] },
                        totalRows: 0,
                    });
                }
            };

            reader.onerror = () =>
                resolve({
                    isValid: false,
                    message: "Error al cargar el archivo",
                    missingFields: [],
                    foundFields: [],
                    extractedData: { gestion: null, registros: [] },
                    totalRows: 0,
                });

            reader.readAsArrayBuffer(file);
        });
    };
    // ============ FIN MÉTODOS ============ //

    return { pdfValidation, validatePdfStructure };
}
