import { jsPDF } from "jspdf";

export const useReporteArqueoCajaPDF = (getData, getNombreUsuario) => {
    // ─── Constantes de página (A4 Portrait) ──────────────────────────────────
    const PAGE_W = 210;
    const PAGE_H = 297;
    const M_LEFT = 14;
    const M_RIGHT = 14;
    const M_BOT = 20;

    const ROW_H    = 8;
    const HEAD_ROW_H = 10;
    const TOTAL_ROW_H = 9;

    // ─── Columnas ─────────────────────────────────────────────────────────────
    // Ancho disponible: 210 - 14 - 14 = 182mm
    const C = {
        nro:         { x: M_LEFT,      w: 12  },
        descripcion: { x: M_LEFT + 12, w: 62  },
        cantidad:    { x: M_LEFT + 74, w: 42  },
        unitario:    { x: M_LEFT + 116,w: 34  },
        total:       { x: M_LEFT + 150,w: 32  },
    };

    // ─── Helpers ──────────────────────────────────────────────────────────────
    const MESES = [
        "Enero","Febrero","Marzo","Abril","Mayo","Junio",
        "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre",
    ];

    const getMes = (n) => {
        const i = parseInt(n, 10) - 1;
        return i >= 0 && i < 12 ? MESES[i] : "";
    };

    const fechaArchivo = () =>
        new Date()
            .toLocaleString("sv-SE", { timeZone: "America/La_Paz", hour12: false })
            .replace(/ /, "_").replace(/\..+/, "");

    const fechaHoraActual = () =>
        new Date().toLocaleString("es-BO", {
            timeZone: "America/La_Paz",
            day: "2-digit", month: "2-digit", year: "numeric",
            hour: "2-digit", minute: "2-digit", hour12: false,
        });

    const rangoMes = (gestion, mes) => {
        const m   = String(mes).padStart(2, "0");
        const g   = String(gestion);
        const ultimo = new Date(Number(g), Number(mes), 0).getDate();
        return {
            desde: `01/${m}/${g}`,
            hasta: `${String(ultimo).padStart(2,"0")}/${m}/${g}`,
        };
    };

    const codigoArqueo = (gestion, mes) => {
        const m = String(mes).padStart(2, "0");
        return `ARQ-${gestion}-${m}-001`;
    };

    const fmt = (n) => Number(n || 0).toLocaleString("es-BO");

    // ─── Logos ────────────────────────────────────────────────────────────────
    const drawLogos = (pdf) => {
        pdf.setFillColor(255, 255, 255);
        pdf.rect(0, 0, PAGE_W, 22, "F");
        try { pdf.addImage("/images/sacaba.png",  "PNG", M_LEFT,              2, 28, 14); } catch (_) {}
        try { pdf.addImage("/images/sigamos.png", "PNG", PAGE_W - M_RIGHT - 22, 3, 22, 12); } catch (_) {}
    };

    // ─── Cabecera de texto ────────────────────────────────────────────────────
    const drawHeader = (pdf, data, nombreUsuario, startY) => {
        let y = startY;
        const cx = PAGE_W / 2;

        // Título principal centrado
        pdf.setFontSize(11);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("REPORTE DE ARQUEO DE CAJA", cx, y, { align: "center" });
        y += 5.5;

        // Subtítulo
        pdf.setFontSize(8.5);
        pdf.setFont("helvetica", "bold");
        pdf.text(
            "Sistema Municipal de Gestión de Beneficios para Personas con Discapacidad",
            cx, y, { align: "center" }
        );
        y += 6;

        // Código de arqueo
        pdf.setFontSize(8.5);
        pdf.setFont("helvetica", "bold");
        pdf.text("Código de Arqueo:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(codigoArqueo(data.gestion, data.mes), M_LEFT + 40, y);
        y += 5;

        // Fecha y hora
        pdf.setFont("helvetica", "bold");
        pdf.text("Fecha y hora del Reporte:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(fechaHoraActual(), M_LEFT + 40, y);
        y += 5;

        // Periodo de arqueo
        const rango = rangoMes(data.gestion, data.mes);
        pdf.setFont("helvetica", "bold");
        pdf.text("Periodo de Arqueo:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(`${rango.desde} al ${rango.hasta}`, M_LEFT + 40, y);
        y += 5;

        // Usuario responsable
        pdf.setFont("helvetica", "bold");
        pdf.text("Usuario Responsable:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text((nombreUsuario || "").toUpperCase(), M_LEFT + 40, y);
        y += 5;

        // Mes de pago
        pdf.setFont("helvetica", "bold");
        pdf.text("Mes de Pago:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(getMes(data.mes), M_LEFT + 40, y);
        y += 5;

        return y;
    };

    // ─── Thead ────────────────────────────────────────────────────────────────
    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.nro,         label: ["N°"] },
            { c: C.descripcion, label: ["Descripción"] },
            { c: C.cantidad,    label: ["Cantidad de", "Beneficiarios"] },
            { c: C.unitario,    label: ["Monto Unitario", "(Bs)"] },
            { c: C.total,       label: ["Total (Bs)"] },
        ];

        pdf.setFillColor(220, 220, 220);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.4);
        cols.forEach(({ c }) => pdf.rect(c.x, y, c.w, HEAD_ROW_H, "FD"));

        pdf.setFontSize(7.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);

        cols.forEach(({ c, label }) => {
            const cx = c.x + c.w / 2;
            if (label.length === 1) {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 + 1.5, { align: "center" });
            } else {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 - 0.8, { align: "center" });
                pdf.text(label[1], cx, y + HEAD_ROW_H / 2 + 3.2, { align: "center" });
            }
        });

        return y + HEAD_ROW_H;
    };

    // ─── Fila de datos ────────────────────────────────────────────────────────
    const drawDataRow = (pdf, nro, descripcion, cantidad, unitario, total, y, isOdd) => {
        pdf.setFillColor(isOdd ? 248 : 255, isOdd ? 248 : 255, isOdd ? 248 : 255);
        pdf.setDrawColor(150, 150, 150);
        pdf.setLineWidth(0.25);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);
        const ty = y + ROW_H / 2 + 1.5;

        pdf.text(String(nro),         C.nro.x + C.nro.w / 2,           ty, { align: "center" });
        pdf.text(descripcion,         C.descripcion.x + 3,              ty);
        pdf.text(String(cantidad),    C.cantidad.x + C.cantidad.w / 2,  ty, { align: "center" });
        pdf.text(String(unitario),    C.unitario.x + C.unitario.w / 2,  ty, { align: "center" });
        pdf.text(fmt(total),          C.total.x + C.total.w / 2,        ty, { align: "center" });

        return y + ROW_H;
    };

    // ─── Fila de total general ────────────────────────────────────────────────
    const drawTotalRow = (pdf, cantidadTotal, totalGeneral, y) => {
    pdf.setDrawColor(0, 0, 0);
    pdf.setLineWidth(0.4);

    // ← setFillColor antes de CADA rect
    pdf.setFillColor(240, 240, 240);
    pdf.rect(C.nro.x, y, C.nro.w + C.descripcion.w, TOTAL_ROW_H, "FD");

    pdf.setFillColor(240, 240, 240);  // ← agregar
    pdf.rect(C.cantidad.x, y, C.cantidad.w, TOTAL_ROW_H, "FD");

    pdf.setFillColor(240, 240, 240);  // ← agregar
    pdf.rect(C.unitario.x, y, C.unitario.w, TOTAL_ROW_H, "FD");

    pdf.setFillColor(240, 240, 240);  // ← agregar
    pdf.rect(C.total.x, y, C.total.w, TOTAL_ROW_H, "FD");

    pdf.setFontSize(8.5);
    pdf.setFont("helvetica", "bold");
    pdf.setTextColor(0, 0, 0);  // ← asegurarse que el texto es negro, no heredado
    const ty = y + TOTAL_ROW_H / 2 + 1.5;
    const cxLabel = C.nro.x + (C.nro.w + C.descripcion.w) / 2;
    pdf.text("Total general", cxLabel, ty, { align: "center" });
    pdf.text(String(cantidadTotal), C.cantidad.x + C.cantidad.w / 2, ty, { align: "center" });
    pdf.text(fmt(totalGeneral),     C.total.x + C.total.w / 2,       ty, { align: "center" });
};

    // ─── Firma ────────────────────────────────────────────────────────────────
    const drawFirma = (pdf, y) => {
        const cx = PAGE_W / 2;
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);

        // Línea de firma
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        pdf.line(cx - 35, y + 16, cx + 35, y + 16);

        pdf.text("Firma y Sello Cajer@", cx, y + 22, { align: "center" });
    };

    // ─── Función principal ────────────────────────────────────────────────────
    const generarReporte = () => {
        try {
            const data          = getData();
            const nombreUsuario = getNombreUsuario();

            const pagados    = parseInt(data.cantidad_total_pagos  || 0);
            const noPagados  = parseInt(data.cantidad_no_pagados   || 0);
            const unitario   = parseFloat(data.monto               || 0);
            const totalPag   = pagados   * unitario;
            const totalNoPag = noPagados * unitario;
            const totalGral  = totalPag + totalNoPag;
            const cantGral   = pagados + noPagados;

            const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });

            drawLogos(pdf);
            let y = drawHeader(pdf, data, nombreUsuario, 24);
            y = drawTableHead(pdf, y);

            y = drawDataRow(pdf, 1, "Total Pagado",    pagados,   unitario, totalPag,   y, true);
            y = drawDataRow(pdf, 2, "Total No Pagado", noPagados, unitario, totalNoPag, y, false);

            drawTotalRow(pdf, cantGral, totalGral, y);
            y += TOTAL_ROW_H;

            drawFirma(pdf, y + 10);

            pdf.save(`Arqueo_Caja-${fechaArchivo()}.pdf`);
        } catch (error) {
            console.error("Error al generar arqueo de caja:", error);
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarReporte };
};
