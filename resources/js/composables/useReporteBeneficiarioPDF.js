import { jsPDF } from "jspdf";

export const useReporteBeneficiarioPDF = () => {
    // ─── Constantes de página (Legal Portrait) ───────────────────────────────
    const PAGE_W = 215.9;
    const PAGE_H = 355.6;
    const M_LEFT = 10;
    const M_RIGHT = 10;
    const M_TOP = 23; // espacio reservado bajo el header
    const M_BOT = 14;

    const ROW_H = 4.5; // altura de fila de datos
    const HEAD_ROW_H = 8; // altura del thead

    // ─── Columnas ─────────────────────────────────────────────────────────────
    // Ancho total disponible: 215.9 - 10 - 10 = 195.9 mm
    const C = {
        n: { x: M_LEFT, w: 8 },
        ci: { x: 18, w: 16 },
        nombre: { x: 34, w: 62 },
        grado: { x: 96, w: 30 },
        monto: { x: 126, w: 22 },
        obs: { x: 148, w: PAGE_W - M_RIGHT - 148 }, // ~57.9
    };

    // ─── Helpers ──────────────────────────────────────────────────────────────
    const MESES = [
        "ENERO",
        "FEBRERO",
        "MARZO",
        "ABRIL",
        "MAYO",
        "JUNIO",
        "JULIO",
        "AGOSTO",
        "SEPTIEMBRE",
        "OCTUBRE",
        "NOVIEMBRE",
        "DICIEMBRE",
    ];

    const getMes = (n) => {
        const i = parseInt(n, 10) - 1;
        return i >= 0 && i < 12 ? MESES[i] : "MES";
    };

    const fmt2 = (n) => {
        const fixed = parseFloat(n || 0).toFixed(2); // "302000.00"
        const [entero, decimal] = fixed.split("."); // ["302000", "00"]
        const miles = entero.replace(/\B(?=(\d{3})+(?!\d))/g, "."); // "302.000"
        return `${miles},${decimal}`; // "302.000,00"
    };

    const fechaArchivo = () =>
        new Date()
            .toLocaleString("sv-SE", {
                timeZone: "America/La_Paz",
                hour12: false,
            })
            .replace(/ /, "_")
            .replace(/\..+/, "");

    // ─── Totales ─────────────────────────────────────────────────────────────
    const calcTotales = (arr) => {
        const activos = arr.filter((i) => i.estado_actual?.estado === "activo");
        const bajasDefinitivas = arr.filter(
            (i) => i.estado_actual?.estado === "baja_definitiva",
        );
        const bajasTemporales = arr.filter(
            (i) => i.estado_actual?.estado === "baja_temporal",
        );

        const monto = parseFloat(arr[0]?.monto || 0); // ✅ monto unitario guardado

        return {
            monto_unitario: monto, // ✅ nuevo campo
            monto_total: arr.length * monto,
            cantidad_baja_definitiva: bajasDefinitivas.length,
            monto_baja_definitiva: bajasDefinitivas.length * monto,
            cantidad_baja_temporal: bajasTemporales.length,
            monto_baja_temporal: bajasTemporales.length * monto,
            cantidad_activos: activos.length,
            monto_activos: activos.length * monto,
        };
    };

    // ─── Dibujar header (logos + título) ─────────────────────────────────────
    const drawPageHeader = (pdf, mes, gestion) => {
        // Fondo blanco para zona del header
        pdf.setFillColor(255, 255, 255);
        pdf.rect(M_LEFT, 1, PAGE_W - M_LEFT - M_RIGHT, 20, "F");

        // Logo izquierdo
        try {
            pdf.addImage("/images/sacaba.png", "PNG", M_LEFT, 2, 36, 17);
        } catch (_) {}

        // Logo derecho
        try {
            pdf.addImage(
                "/images/sigamos.png",
                "PNG",
                PAGE_W - M_RIGHT - 25,
                3,
                27,
                14,
            );
        } catch (_) {}

        // Título centrado (dos líneas)
        pdf.setFontSize(7.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text(
            "PLANILLA DE PAGO DE BONO MENSUAL A FAVOR DE LAS PERSONAS CON DISCAPACIDAD",
            PAGE_W / 2,
            8,
            { align: "center", maxWidth: PAGE_W - M_LEFT - M_RIGHT - 56 },
        );
        pdf.text(
            `GRAVE Y MUY GRAVE MES DE ${getMes(mes)} GESTIÓN ${gestion}`,
            PAGE_W / 2,
            13,
            { align: "center", maxWidth: PAGE_W - M_LEFT - M_RIGHT - 56 },
        );

        // Línea separadora
        /* pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        pdf.line(M_LEFT, 21, PAGE_W - M_RIGHT, 21); */
    };

    // ─── Dibujar thead ────────────────────────────────────────────────────────
    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.n, label: ["N°"] },
            { c: C.ci, label: ["C.I."] },
            { c: C.nombre, label: ["APELLIDOS Y NOMBRES", "P.C.D."] },
            { c: C.grado, label: ["GRADO DE", "DISCAPACIDAD"] },
            { c: C.monto, label: ["MONTO A", "PAGAR (BS.)"] },
            { c: C.obs, label: ["OBSERVACIONES"] },
        ];

        pdf.setFillColor(230, 230, 230);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);

        cols.forEach(({ c }) => pdf.rect(c.x, y, c.w, HEAD_ROW_H, "FD"));

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);

        cols.forEach(({ c, label }) => {
            const cx = c.x + c.w / 2;
            if (label.length === 1) {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 + 1, {
                    align: "center",
                });
            } else {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 - 0.5, {
                    align: "center",
                });
                pdf.text(label[1], cx, y + HEAD_ROW_H / 2 + 3, {
                    align: "center",
                });
            }
        });

        return y + HEAD_ROW_H;
    };

    // ─── Dibujar fila de datos ────────────────────────────────────────────────
    const drawDataRow = (pdf, item, rowNum, y) => {
        const estado = item.estado_actual?.estado;

        if (estado === "baja_temporal") {
            pdf.setFillColor(29, 78, 216); // azul más vivo
            pdf.setTextColor(219, 234, 254); // azul muy claro, mejor contraste
        } else if (estado === "baja_definitiva") {
            pdf.setFillColor(250, 204, 21); // amarillo más saturado
            pdf.setTextColor(153, 27, 27); // rojo oscuro, más legible sobre amarillo
        } else {
            pdf.setFillColor(255, 255, 255);
            pdf.setTextColor(0, 0, 0);
        }

        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);

        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");

        const ty = y + ROW_H - 1.2;

        // N°
        pdf.text(String(rowNum), C.n.x + C.n.w / 2, ty, { align: "center" });

        // CI
        pdf.text(String(item.ci || ""), C.ci.x + C.ci.w / 2, ty, {
            align: "center",
        });

        // ✅ Nombre: concatena apellido+nombre, si ambos vacíos usa nombre_completo
        const nombreConcatenado =
            `${item.apellido || ""} ${item.nombre || ""}`.trim();
        const nombre = (
            nombreConcatenado ||
            item.nombre_completo ||
            ""
        ).toUpperCase();
        pdf.text(nombre, C.nombre.x + 1.5, ty, { maxWidth: C.nombre.w - 3 });

        // Grado
        pdf.text("GRAVE MUY GRAVE", C.grado.x + C.grado.w / 2, ty, {
            align: "center",
            maxWidth: C.grado.w - 2,
        });

        // Monto
        pdf.text(String(item.monto || ""), C.monto.x + C.monto.w / 2, ty, {
            align: "center",
        });

        // Observaciones
        const obs =
            estado !== "activo"
                ? (item.estado_actual?.observaciones || "").toUpperCase()
                : "";
        pdf.text(obs, C.obs.x + C.obs.w / 2, ty, {
            align: "center",
            maxWidth: C.obs.w - 2,
        });

        return y + ROW_H;
    };

    // ─── Calcular altura real de una fila de totales (texto puede ser largo) ──
    const footerRowHeight = (pdf, label, maxWidth) => {
        const lines = pdf.splitTextToSize(label, maxWidth);
        return Math.max(ROW_H * 1.5, lines.length * 3.5 + 3);
    };

    // ─── Dibujar una fila de totales ─────────────────────────────────────────
    const drawFooterRow = (pdf, y, label, monto, bgColor) => {
        const spanW = C.n.w + C.ci.w + C.nombre.w + C.grado.w; // N° + CI + Nombre + Grado
        const labelX = C.n.x;
        const labelMaxW = spanW - 3;

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");
        const h = footerRowHeight(pdf, label, labelMaxW);

        // Fondo
        if (bgColor) pdf.setFillColor(...bgColor);
        else pdf.setFillColor(255, 255, 255);

        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);

        // Celda label (celdas fusionadas)
        pdf.rect(labelX, y, spanW, h, "FD");
        // Celda monto
        pdf.rect(C.monto.x, y, C.monto.w, h, "FD");
        // Celda obs
        pdf.rect(C.obs.x, y, C.obs.w, h, "FD");

        // Texto label
        pdf.setTextColor(0, 0, 0);
        const lines = pdf.splitTextToSize(label, labelMaxW);
        pdf.text(lines, labelX + 1.5, y + 3.5);

        // Texto monto
        pdf.text(monto, C.monto.x + C.monto.w / 2, y + h / 2 + 1, {
            align: "center",
        });

        return y + h;
    };

    // ─── Dibujar bloque de totales ────────────────────────────────────────────
    // ─── Dibujar bloque de totales ────────────────────────────────────────────
    const drawTotales = (pdf, totales, mes, gestion, y) => {
        const mesNombre = getMes(mes);
        const {
            monto_unitario, // ✅ ya no necesita arr
            monto_total,
            cantidad_baja_definitiva,
            monto_baja_definitiva,
            cantidad_baja_temporal,
            monto_baja_temporal,
            cantidad_activos,
            monto_activos,
        } = totales;

        y = drawFooterRow(pdf, y, "TOTAL PLANILLA GENERAL", fmt2(monto_total));

        // ✅ baja_definitiva: fallecieron
        y = drawFooterRow(
            pdf,
            y,
            `${cantidad_baja_definitiva} PERSONAS CON DISCAPACIDAD GRAVE Y MUY GRAVE FALLECIERON HASTA LA FECHA ` +
                `NO COBRARAN DEL MES DE ${mesNombre} DEL AÑO ${gestion} ` +
                `(${cantidad_baja_definitiva}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_definitiva)}`,
            fmt2(monto_baja_definitiva),
        );

        // ✅ baja_temporal: funcionarios con item
        y = drawFooterRow(
            pdf,
            y,
            `${cantidad_baja_temporal} FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) LOS CUALES NO ACCEDEN AL BONO ` +
                `DEL MES DE ${mesNombre} DEL AÑO ${gestion} ` +
                `(${cantidad_baja_temporal}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_temporal)}`,
            fmt2(monto_baja_temporal),
        );

        // ✅ activos: total a pagar
        drawFooterRow(
            pdf,
            y,
            `TOTAL PERSONAS CON DISCAPACIDAD A PAGAR MES DE ${mesNombre} ${gestion} ` +
                `(${cantidad_activos} PERSONAS)`,
            fmt2(monto_activos),
            [135, 206, 235],
        );
    };

    // ─── Estimación de espacio que ocupan los totales ─────────────────────────
    const estimarAlturaFooter = (pdf, totales, mes, gestion) => {
        const mesNombre = getMes(mes);
        const {
            monto_unitario,
            cantidad_baja_definitiva,
            monto_baja_definitiva,
            cantidad_baja_temporal,
            monto_baja_temporal,
            cantidad_activos,
        } = totales;
        const spanW = C.n.w + C.ci.w + C.nombre.w + C.grado.w - 3;

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");

        const labels = [
            "TOTAL PLANILLA GENERAL",
            `${cantidad_baja_definitiva} PERSONAS CON DISCAPACIDAD GRAVE Y MUY GRAVE FALLECIERON HASTA LA FECHA NO COBRARAN DEL MES DE ${mesNombre} DEL AÑO ${gestion} (${cantidad_baja_definitiva}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_definitiva)}`,
            `${cantidad_baja_temporal} FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) LOS CUALES NO ACCEDEN AL BONO DEL MES DE ${mesNombre} DEL AÑO ${gestion} (${cantidad_baja_temporal}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_temporal)}`,
            `TOTAL PERSONAS CON DISCAPACIDAD A PAGAR MES DE ${mesNombre} ${gestion} (${cantidad_activos} PERSONAS)`,
        ];

        return (
            labels.reduce(
                (sum, label) => sum + footerRowHeight(pdf, label, spanW),
                0,
            ) + 2
        );
    };

    // ─── Función principal ────────────────────────────────────────────────────
    const generarReporte = async (datos, gestion, mes, tipoR = "Reporte") => {
        try {
            const arr = Array.isArray(datos) ? datos : [];
            const totales = calcTotales(arr);

            const pdf = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "legal",
            });

            const maxY = PAGE_H - M_BOT;
            const footerH = estimarAlturaFooter(pdf, totales, mes, gestion);

            let pageNum = 0;
            let y = M_TOP;

            const nuevaPagina = () => {
                if (pageNum > 0) pdf.addPage("legal", "portrait");
                pageNum++;
                y = M_TOP;
                drawPageHeader(pdf, mes, gestion);
                y = drawTableHead(pdf, y);
            };

            nuevaPagina();

            for (let i = 0; i < arr.length; i++) {
                const esUltimo = i === arr.length - 1;
                // Si es el último registro necesitamos también espacio para los totales
                const espacioNecesario = ROW_H + (esUltimo ? footerH : 0);

                if (y + espacioNecesario > maxY) {
                    nuevaPagina();
                }

                y = drawDataRow(pdf, arr[i], i + 1, y);
            }

            // Si el footer no cabe en la página actual, nueva página
            if (arr.length === 0 || y + footerH > maxY) {
                nuevaPagina();
            }

            drawTotales(pdf, totales, mes, gestion, y);

            pdf.save(`Reporte_${tipoR}-${fechaArchivo()}.pdf`);
        } catch (error) {
            console.error("Error al generar el reporte:", error);
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarReporte };
};
