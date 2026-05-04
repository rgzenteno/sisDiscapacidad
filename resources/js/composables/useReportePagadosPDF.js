import { jsPDF } from "jspdf";

export const useReportePagadosPDF = () => {
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
        ci: { x: 18, w: 18 },
        nombre: { x: 36, w: 58 },
        grado: { x: 94, w: 28 },
        boleta: { x: 122, w: 26 },
        monto: { x: 148, w: 20 },
        obs: { x: 168, w: PAGE_W - M_RIGHT - 168 },
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
    const calcTotales = (arr, montoMes = 0) => {
        const bajasDefinitivas = arr.filter((i) => i.estado_actual === "baja_definitiva");
        const bajasTemporales = arr.filter((i) => i.estado_actual === "baja_temporal");
        const activos = arr.filter((i) => i.estado_actual === "activo");
        const pagados = arr.filter((i) => i.numero_boleta !== null && i.numero_boleta !== "");
        const sinPago = arr.filter((i) => i.estado_actual === "activo" && (!i.numero_boleta));
        const monto = montoMes || parseFloat(arr[0]?.monto_pago || 0);

        return {
            monto_unitario: monto,
            monto_total: arr.length * monto,
            cantidad_baja_definitiva: bajasDefinitivas.length,
            monto_baja_definitiva: bajasDefinitivas.length * monto,
            cantidad_baja_temporal: bajasTemporales.length,
            monto_baja_temporal: bajasTemporales.length * monto,
            cantidad_activos: activos.length,
            monto_activos: activos.length * monto,
            cantidad_pagados: pagados.length,
            monto_pagados: pagados.length * monto,
            cantidad_sin_pago: sinPago.length,
            monto_sin_pago: sinPago.length * monto,
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
        } catch (_) { }

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
        } catch (_) { }

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
            { c: C.boleta, label: ["N°", "BOLETA"] },
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
    const drawDataRow = (pdf, item, rowNum, y, montoUnitario) => {
        const estado = item.estado_actual;
        const esPagado = !!item.numero_boleta;

        if (estado === "baja_definitiva") {
            pdf.setFillColor(255, 255, 0);
            pdf.setTextColor(255, 0, 0);
        } else if (estado === "baja_temporal") {
            pdf.setFillColor(0, 32, 96);
            pdf.setTextColor(0, 176, 240);
        } else if (esPagado) {
            pdf.setFillColor(187, 247, 208);
            pdf.setTextColor(21, 128, 61);
        } else {
            pdf.setFillColor(255, 255, 255);
            pdf.setTextColor(0, 0, 0);
        }

        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", esPagado || estado !== "activo" ? "bold" : "normal");
        const ty = y + ROW_H - 1.2;

        // N°
        pdf.text(String(rowNum), C.n.x + C.n.w / 2, ty, { align: "center" });

        // CI
        pdf.text(String(item.ci_persona || ""), C.ci.x + C.ci.w / 2, ty, { align: "center" });

        // Nombre
        const apellido = (item.apellido_persona || "").trim();
        const nombreP = (item.nombre_persona || "").trim();
        const nombreConcatenado = apellido || nombreP
            ? `${apellido} ${nombreP}`.trim()
            : (item.nombre_completo || "").trim();
        pdf.text(nombreConcatenado.toUpperCase(), C.nombre.x + 1.5, ty, { maxWidth: C.nombre.w - 3 });

        // Grado
        pdf.text("GRAVE MUY GRAVE", C.grado.x + C.grado.w / 2, ty, {
            align: "center",
            maxWidth: C.grado.w - 2,
        });

        // Boleta
        pdf.text(String(item.numero_boleta || ""), C.boleta.x + C.boleta.w / 2, ty, { align: "center" });

        // Monto — vacío si no pagó
        pdf.text(fmt2(montoUnitario || item.monto_pago || 0), C.monto.x + C.monto.w / 2, ty, { align: "center" });

        // Observaciones
        const obs = estado === "baja_temporal"
            ? "PADRE FUNCIONARIO TRABAJANDO CON ITEM"
            : estado === "baja_definitiva"
                ? "FALLECIO"
                : esPagado ? "PAGADO" : "";
        pdf.text(obs, C.obs.x + C.obs.w / 2, ty, {
            align: "center",
            maxWidth: C.obs.w - 2,
        });

        return y + ROW_H;
    };

    // ─── Calcular altura real de una fila de totales (texto puede ser largo) ──
    const footerRowHeight = (pdf, label, maxWidth) => {
        const lines = pdf.splitTextToSize(label, maxWidth);
        return Math.max(ROW_H * 1.2, lines.length * 3.5 + 1);
    };

    // ─── Dibujar una fila de totales ─────────────────────────────────────────
    const drawFooterRow = (pdf, y, label, monto, bgColor, skipMonto = false) => {
        const spanW = C.n.w + C.ci.w + C.nombre.w + C.grado.w;
        const labelX = C.n.x;
        const labelMaxW = spanW + C.boleta.w - 3;

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");
        const h = footerRowHeight(pdf, label, labelMaxW);

        if (bgColor) pdf.setFillColor(...bgColor);
        else pdf.setFillColor(255, 255, 255);

        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);

        const spanWconBoleta = spanW + C.boleta.w; // une label + boleta
        pdf.rect(labelX, y, spanWconBoleta, h, "FD");
        if (!skipMonto) pdf.rect(C.monto.x, y, C.monto.w, h, "FD");
        // obs: solo bordes horizontales (unión vertical)
        pdf.setLineWidth(0.2);

        pdf.setTextColor(0, 0, 0);
        const lines = pdf.splitTextToSize(label, labelMaxW);
        pdf.text(lines, labelX + 1.5, y + 3.5);

        if (!skipMonto) pdf.text(monto, C.monto.x + C.monto.w / 2, y + h / 2 + 1, { align: "center" });

        return y + h;
    };

    // ─── Dibujar bloque de totales ────────────────────────────────────────────
    const drawTotales = (pdf, totales, mes, gestion, y) => {
        const mesNombre = getMes(mes);
        const {
            monto_unitario,
            monto_total,
            cantidad_baja_definitiva,
            monto_baja_definitiva,
            cantidad_baja_temporal,
            monto_baja_temporal,
            cantidad_activos,
            monto_activos,
            cantidad_pagados,
            monto_pagados,
            cantidad_sin_pago,
            monto_sin_pago,
        } = totales;

        const totalPersonas = cantidad_baja_definitiva + cantidad_baja_temporal + (totales.cantidad_pagados ?? 0) + (totales.cantidad_sin_pago ?? 0);
        const yInicioObs = y;

        y = drawFooterRow(
            pdf, y,
            `TOTAL PLANILLA GENERAL: ${totalPersonas} PERSONAS (${fmt2(monto_unitario)} C/U)`,
            fmt2(monto_total),
        );

        const yInicioBajas = y;

        y = drawFooterRow(
            pdf, y,
            `${cantidad_baja_definitiva} PERSONAS FALLECIERON HASTA LA FECHA, NO COBRARÁN DEL MES DE ${mesNombre} ${gestion} (${cantidad_baja_definitiva}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_definitiva)}`,
            fmt2(monto_baja_definitiva),
            undefined,
            true,
        );

        y = drawFooterRow(
            pdf, y,
            `${cantidad_baja_temporal} FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) NO ACCEDEN AL BONO DEL MES DE ${mesNombre} ${gestion} (${cantidad_baja_temporal}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_temporal)}`,
            fmt2(monto_baja_temporal),
            undefined,
            true,
        );

        const yFinBajas = y;

        y = drawFooterRow(
            pdf, y,
            `TOTAL PAGADOS MES DE ${mesNombre} ${gestion} (${cantidad_pagados} PERSONAS) (${cantidad_pagados}*${fmt2(monto_unitario)}) = ${fmt2(monto_pagados)}`,
            fmt2(monto_pagados),
            [187, 247, 208],
        );

        y = drawFooterRow(
            pdf, y,
            `TOTAL PENDIENTES DE PAGO MES DE ${mesNombre} ${gestion} (${cantidad_sin_pago} PERSONAS) (${cantidad_sin_pago}*${fmt2(monto_unitario)}) = ${fmt2(monto_sin_pago)}`,
            fmt2(monto_sin_pago),
            [254, 243, 199],
        );


        // ── Fila de suma de las 4 celdas unidas ──────────────────────────────────
        const sumaTotal = monto_baja_definitiva + monto_baja_temporal + monto_pagados + monto_sin_pago;
        const totalAPagar = monto_pagados + monto_sin_pago;
        const spanW = C.n.w + C.ci.w + C.nombre.w + C.grado.w;
        const h = 6;

        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);

        const hBajas = yFinBajas - yInicioBajas;
        const montoBajas = monto_baja_definitiva + monto_baja_temporal;
        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);
        pdf.rect(C.monto.x, yInicioBajas, C.monto.w, hBajas, "FD");
        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text(fmt2(montoBajas), C.monto.x + C.monto.w / 2, yInicioBajas + hBajas / 2 + 1, { align: "center" });

        const spanWconBoleta = spanW + C.boleta.w;
        pdf.setFillColor(135, 206, 235);
        pdf.rect(C.n.x, y, spanWconBoleta, h, "FD");
        pdf.setFillColor(135, 206, 235);
        pdf.rect(C.monto.x, y, C.monto.w, h, "FD");

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text(
            `TOTAL A PAGAR MES DE ${getMes(mes)} ${gestion} (PLANILLA - BAJAS)`,
            C.n.x + 1.5, y + h / 2 + 1,
            { maxWidth: spanW - 3 }
        );
        pdf.text(fmt2(totalAPagar), C.monto.x + C.monto.w / 2, y + h / 2 + 1, { align: "center" });

        const hTotalObs = y + h - yInicioObs;
        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.2);
        pdf.rect(C.obs.x, yInicioObs, C.obs.w, hTotalObs, "FD");
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
        } = totales;

        const totalPersonas = cantidad_baja_definitiva + cantidad_baja_temporal + (totales.cantidad_pagados ?? 0) + (totales.cantidad_sin_pago ?? 0);
        const spanW = C.n.w + C.ci.w + C.nombre.w + C.grado.w - 3;

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "bold");

        const labels = [
            `TOTAL PLANILLA GENERAL: ${totalPersonas} PERSONAS (${fmt2(monto_unitario)} C/U)`,
            `${cantidad_baja_definitiva} PERSONAS FALLECIERON HASTA LA FECHA, NO COBRARÁN DEL MES DE ${mesNombre} ${gestion} (${cantidad_baja_definitiva}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_definitiva)}`,
            `${cantidad_baja_temporal} FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) NO ACCEDEN AL BONO DEL MES DE ${mesNombre} ${gestion} (${cantidad_baja_temporal}*${fmt2(monto_unitario)}) = ${fmt2(monto_baja_temporal)}`,
            `TOTAL PAGADOS MES DE ${mesNombre} ${gestion} (${totales.cantidad_pagados ?? 0} PERSONAS) (${totales.cantidad_pagados ?? 0}*${fmt2(monto_unitario)}) = ${fmt2(totales.monto_pagados ?? 0)}`,
            `TOTAL PENDIENTES DE PAGO MES DE ${mesNombre} ${gestion} (${totales.cantidad_sin_pago ?? 0} PERSONAS) (${totales.cantidad_sin_pago ?? 0}*${fmt2(monto_unitario)}) = ${fmt2(totales.monto_sin_pago ?? 0)}`,
            "SUMA TOTAL (BAJA DEFINITIVA + BAJA TEMPORAL + PAGADOS + PENDIENTES)",
        ];

        return (
            labels.reduce(
                (sum, label) => sum + footerRowHeight(pdf, label, spanW),
                0,
            ) + 2 + 6
        );
    };

    // ─── Función principal ────────────────────────────────────────────────────
    const generarReporte = async (datos, gestion, mes, tipoR = "Reporte", montoMes = 0) => {
        try {
            const arr = Array.isArray(datos) ? datos : [];
            const totales = calcTotales(arr, montoMes);

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

                y = drawDataRow(pdf, arr[i], i + 1, y, montoMes);
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
