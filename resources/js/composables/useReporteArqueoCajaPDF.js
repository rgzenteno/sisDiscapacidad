import { jsPDF } from "jspdf";

export const useReporteArqueoCajaPDF = (getData, getNombreUsuario, getCargo) => {
    // ─── Constantes de página (A4 Portrait) ──────────────────────────────────
    const PAGE_W = 210;
    const PAGE_H = 297;
    const M_LEFT = 14;
    const M_RIGHT = 14;
    const M_BOT = 20;

    const ROW_H = 8;
    const HEAD_ROW_H = 10;
    const TOTAL_ROW_H = 9;

    // ─── Columnas ─────────────────────────────────────────────────────────────
    // Ancho disponible: 210 - 14 - 14 = 182mm. Sin columna "N°" (no aporta
    // nada con solo 2-3 filas fijas) ni "Monto Unitario" (mismo valor en
    // todas las filas, se muestra una sola vez arriba en la cabecera).
    const C = {
        descripcion: { x: M_LEFT, w: 92 },
        cantidad: { x: M_LEFT + 92, w: 48 },
        total: { x: M_LEFT + 140, w: 42 },
    };

    // ─── Helpers ──────────────────────────────────────────────────────────────
    const MESES = [
        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre",
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
        const m = String(mes).padStart(2, "0");
        const g = String(gestion);
        const ultimo = new Date(Number(g), Number(mes), 0).getDate();
        return {
            desde: `01/${m}/${g}`,
            hasta: `${String(ultimo).padStart(2, "0")}/${m}/${g}`,
        };
    };

    const codigoArqueo = (gestion, mes) => {
        const m = String(mes).padStart(2, "0");
        return `ARQ-${gestion}-${m}-001`;
    };

    const fmt = (n) => Number(n).toLocaleString("es-BO");

    // ─── Logos ────────────────────────────────────────────────────────────────
    const drawLogos = (pdf) => {
        pdf.setFillColor(255, 255, 255);
        pdf.rect(0, 0, PAGE_W, 22, "F");
        try { pdf.addImage("/images/sacaba.png", "PNG", M_LEFT, 4, 40, 19); } catch (_) { }
        try { pdf.addImage("/images/sigamos.png", "PNG", PAGE_W - M_RIGHT - 30, 3, 38, 17); } catch (_) { }
    };

    // ─── Cabecera de texto ────────────────────────────────────────────────────
    // Dos columnas: izquierda con los datos del reporte (fecha, periodo,
    // usuario, cargo, mes), derecha con los datos de presupuesto (monto x
    // beneficiario, presupuesto asignado) — mismo estilo label/valor que la
    // izquierda, en vez de las cards separadas de antes (que repetían monto
    // pagado/restante, ya visibles en la tabla de abajo).
    const RIGHT_COL_X = 112;
    const RIGHT_COL_VALUE_X = RIGHT_COL_X + 44;

    const drawHeader = (pdf, data, nombreUsuario, cargo, unitario, presupuestoAsignado, startY) => {
        let y = startY + 7;
        const cx = PAGE_W / 2;

        // Título principal centrado
        pdf.setFontSize(11);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("REPORTE DE ARQUEO DE CAJA", cx, y, { align: "center" });
        y += 7;

        // Subtítulo
        pdf.setFontSize(8.5);
        pdf.setFont("helvetica", "bold");
        pdf.text(
            "Sistema Municipal de Gestión de Beneficios para Personas con Discapacidad",
            cx, y, { align: "center" }
        );
        y += 9;

        // Fecha y hora (izq.) / Monto x Beneficiario (der.)
        pdf.setFont("helvetica", "bold");
        pdf.text("Fecha y hora del Reporte:", M_LEFT, y);
        pdf.text("Monto x Beneficiario:", RIGHT_COL_X, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(fechaHoraActual(), M_LEFT + 40, y);
        pdf.text(`Bs ${fmt(unitario)}`, RIGHT_COL_VALUE_X, y);
        y += 5;

        // Periodo de arqueo (izq.) / Presupuesto Asignado (der.)
        const rango = rangoMes(data.gestion, data.mes);
        pdf.setFont("helvetica", "bold");
        pdf.text("Periodo de Arqueo:", M_LEFT, y);
        pdf.text("Presupuesto Asignado:", RIGHT_COL_X, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(`${rango.desde} al ${rango.hasta}`, M_LEFT + 40, y);
        pdf.text(`Bs ${fmt(presupuestoAsignado)}`, RIGHT_COL_VALUE_X, y);
        y += 5;

        // Usuario responsable
        pdf.setFont("helvetica", "bold");
        pdf.text("Usuario Responsable:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text((nombreUsuario || "").toUpperCase(), M_LEFT + 40, y);
        y += 5;

        // Cargo
        if (cargo) {
            pdf.setFont("helvetica", "bold");
            pdf.text("Cargo:", M_LEFT, y);
            pdf.setFont("helvetica", "normal");
            pdf.text(String(cargo).toUpperCase(), M_LEFT + 40, y);
            y += 5;
        }

        // Mes de pago
        pdf.setFont("helvetica", "bold");
        pdf.text("Mes de Pago:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(getMes(data.mes), M_LEFT + 40, y);
        y += 10;

        return y;
    };

    // ─── Thead ────────────────────────────────────────────────────────────────
    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.descripcion, label: ["Descripción"] },
            { c: C.cantidad, label: ["Cantidad de", "Beneficiarios"] },
            { c: C.total, label: ["Total (Bs)"] },
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
    const drawDataRow = (pdf, descripcion, cantidad, total, y, isOdd) => {
        pdf.setFillColor(isOdd ? 248 : 255, isOdd ? 248 : 255, isOdd ? 248 : 255);
        pdf.setDrawColor(150, 150, 150);
        pdf.setLineWidth(0.25);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);
        const ty = y + ROW_H / 2 + 1.5;

        pdf.text(descripcion, C.descripcion.x + 3, ty);
        pdf.text(String(cantidad), C.cantidad.x + C.cantidad.w / 2, ty, { align: "center" });
        pdf.text(fmt(total), C.total.x + C.total.w / 2, ty, { align: "center" });

        return y + ROW_H;
    };

    // ─── Fila de total general ────────────────────────────────────────────────
    const drawTotalRow = (pdf, cantidadTotal, totalGeneral, y) => {
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.4);

        // ← setFillColor antes de CADA rect
        pdf.setFillColor(240, 240, 240);
        pdf.rect(C.descripcion.x, y, C.descripcion.w, TOTAL_ROW_H, "FD");

        pdf.setFillColor(240, 240, 240);
        pdf.rect(C.cantidad.x, y, C.cantidad.w, TOTAL_ROW_H, "FD");

        pdf.setFillColor(240, 240, 240);
        pdf.rect(C.total.x, y, C.total.w, TOTAL_ROW_H, "FD");

        pdf.setFontSize(8.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        const ty = y + TOTAL_ROW_H / 2 + 1.5;
        const cxLabel = C.descripcion.x + C.descripcion.w / 2;
        pdf.text("Total general", cxLabel, ty, { align: "center" });
        pdf.text(String(cantidadTotal), C.cantidad.x + C.cantidad.w / 2, ty, { align: "center" });
        pdf.text(fmt(totalGeneral), C.total.x + C.total.w / 2, ty, { align: "center" });
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
            const data = getData();
            const nombreUsuario = getNombreUsuario();
            const cargo = getCargo ? getCargo() : null;

            const anulados = parseInt(data.cantidad_anulados || 0);
            const pagados = parseInt(data.cantidad_total_pagos) - anulados;
            const unitario = parseFloat(data.monto_pago);
            const presupuestoAsignado = parseFloat(data.presupuesto_asignado || 0);

            // "No pagados" no viene del backend: se deriva del presupuesto
            // asignado — cuántos beneficiarios le corresponden en total a este
            // cajero (presupuesto / monto unitario) menos los que ya tiene
            // pagados o anulados. Así el total general siempre cierra contra
            // el presupuesto asignado.
            const totalAsignados = unitario > 0 ? Math.round(presupuestoAsignado / unitario) : 0;
            const noPagados = Math.max(0, totalAsignados - pagados - anulados);

            const totalPag = parseFloat(data.total_pagado);
            const totalNoPag = noPagados * unitario;
            const totalGral = totalPag + totalNoPag;
            const cantGral = pagados + anulados + noPagados;

            const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });

            drawLogos(pdf);
            let y = drawHeader(pdf, data, nombreUsuario, cargo, unitario, presupuestoAsignado, 24);
            y = drawTableHead(pdf, y);

            let fila = 0;
            y = drawDataRow(pdf, "Total Pagado", pagados, totalPag, y, fila++ % 2 === 0);
            // La fila "Anulado" solo tiene sentido si hay al menos un anulado.
            if (anulados > 0) {
                y = drawDataRow(pdf, "Anulado", anulados, 0, y, fila++ % 2 === 0);
            }
            y = drawDataRow(pdf, "Total No Pagado", noPagados, totalNoPag, y, fila++ % 2 === 0);

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
