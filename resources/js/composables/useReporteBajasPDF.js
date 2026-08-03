import { jsPDF } from "jspdf";

export const useReporteBajasPDF = () => {
    const PAGE_W = 210, PAGE_H = 297, M_LEFT = 10, M_RIGHT = 10, M_BOT = 12;
    const ROW_H = 6.5, HEAD_ROW_H = 9;
    let headBottomY = 0;

    const C = {
        nro: { x: M_LEFT, w: 10 },
        ci: { x: 20, w: 28 },
        nombre: { x: 48, w: 80 },
        grado: { x: 128, w: 40 },
        monto: { x: 168, w: PAGE_W - M_RIGHT - 168 },
    };

    const MESES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    const getMes = (n) => { const i = parseInt(n, 10) - 1; return i >= 0 && i < 12 ? MESES[i] : "Mes"; };

    const fechaArchivo = () =>
        new Date().toLocaleString("sv-SE", { timeZone: "America/La_Paz", hour12: false })
            .replace(/ /, "_").replace(/\..+/, "");

    const calcMontoTotal = (arr) =>
        arr.reduce((s, i) => s + (parseFloat(i.monto) || 0), 0);

    /** Sufijo del título según el filtro "Tipo de Baja" aplicado en el tab. */
    const sufijoBaja = (estadoBaja) => {
        if (estadoBaja === 'baja_temporal') return 'DE LOS FUNCIONARIOS';
        if (estadoBaja === 'baja_definitiva') return 'QUE FALLECIERON';
        return 'CON BAJAS';
    };

    const periodoTexto = (gestion, mes) => `AL MES DE ${getMes(mes).toUpperCase()} GESTION ${gestion}`;

    const drawLogos = (pdf) => {
        pdf.setFillColor(255, 255, 255);
        pdf.rect(M_LEFT, 1, PAGE_W - M_LEFT - M_RIGHT, 16, "F");
        try { pdf.addImage("/images/sacaba.png", "PNG", M_LEFT, 2, 28, 13); } catch (_) { }
        try { pdf.addImage("/images/sigamos.png", "PNG", PAGE_W - M_RIGHT - 20, 3, 20, 11); } catch (_) { }
    };

    const drawTitleBlock = (pdf, gestion, mes, estadoBaja, y, distrito = "", verRetro = false) => {
        const maxWidth = PAGE_W - M_LEFT - M_RIGHT;

        const linea1 = "PLANILLA NO PAGADOS BONO MENSUAL EN FAVOR DE LAS PERSONAS CON DISCAPACIDAD";
        const linea2 = `GRAVE MUY GRAVE ${sufijoBaja(estadoBaja)} ${periodoTexto(gestion, mes)}${verRetro ? " (RETROACTIVOS)" : ""}`;

        pdf.setFont("helvetica", "bold");
        pdf.setFontSize(9.5);
        pdf.setTextColor(0, 0, 0);

        const lineas = [
            ...pdf.splitTextToSize(linea1, maxWidth),
            ...pdf.splitTextToSize(linea2, maxWidth),
        ];
        lineas.forEach((linea, i) => {
            pdf.text(linea, PAGE_W / 2, y + i * 4, { align: "center" });
        });
        let yFinal = y + (lineas.length - 1) * 4;

        if (distrito) {
            pdf.setFont("helvetica", "normal");
            pdf.setFontSize(8);
            pdf.setTextColor(37, 99, 235);
            pdf.text(`Distrito: ${distrito.toUpperCase()}`, PAGE_W / 2, yFinal + 4, { align: "center" });
            pdf.setTextColor(0, 0, 0);
            yFinal += 6;
        } else {
            yFinal += 3;
        }

        pdf.setDrawColor(180, 180, 180);
        pdf.setLineWidth(0.25);
        pdf.line(M_LEFT, yFinal, PAGE_W - M_RIGHT, yFinal);

        return yFinal + 3;
    };

    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.nro, label: ["Nro"] },
            { c: C.ci, label: ["C.I."] },
            { c: C.nombre, label: ["Nombre Completo"] },
            { c: C.grado, label: ["Grado de", "Discapacidad"] },
            { c: C.monto, label: ["Monto No", "Pagado (Bs)"] },
        ];
        pdf.setFillColor(220, 220, 220); pdf.setDrawColor(0, 0, 0); pdf.setLineWidth(0.3);
        cols.forEach(({ c }) => pdf.rect(c.x, y, c.w, HEAD_ROW_H, "FD"));
        pdf.setFontSize(6.5); pdf.setFont("helvetica", "bold"); pdf.setTextColor(0, 0, 0);
        cols.forEach(({ c, label }) => {
            const cx = c.x + c.w / 2;
            if (label.length === 1) {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 + 1.5, { align: "center" });
            } else {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 - 0.5, { align: "center" });
                pdf.text(label[1], cx, y + HEAD_ROW_H / 2 + 3.5, { align: "center" });
            }
        });
        return y + HEAD_ROW_H;
    };

    const drawDataRow = (pdf, item, rowNum, y, isOdd) => {
        pdf.setFillColor(isOdd ? 245 : 255, isOdd ? 245 : 255, isOdd ? 245 : 255);
        pdf.setDrawColor(180, 180, 180); pdf.setLineWidth(0.2);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));
        pdf.setFontSize(6.5); pdf.setFont("helvetica", "normal"); pdf.setTextColor(0, 0, 0);
        const ty = y + ROW_H / 2 + 1;

        pdf.text(String(rowNum), C.nro.x + C.nro.w / 2, ty, { align: "center" });
        pdf.text(String(item.ci_persona || ""), C.ci.x + C.ci.w / 2, ty, { align: "center" });

        const nombre = item.nombre_persona
            ? `${item.apellido_persona || ""} ${item.nombre_persona || ""}`.toUpperCase().trim()
            : (item.nombre_completo || "").toUpperCase().trim();
        pdf.text(nombre, C.nombre.x + 1.5, ty, { maxWidth: C.nombre.w - 3 });

        pdf.text("GRAVE Y MUY GRAVE", C.grado.x + C.grado.w / 2, ty, { align: "center", maxWidth: C.grado.w - 2 });
        pdf.text(String(item.monto ?? ""), C.monto.x + C.monto.w / 2, ty, { align: "center" });

        return y + ROW_H;
    };

    const drawTotalRow = (pdf, montoTotal, y, label) => {
        const labelW = (C.grado.x + C.grado.w) - C.nro.x;

        pdf.setFillColor(187, 247, 208);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        pdf.rect(C.nro.x, y, labelW, ROW_H, "FD");
        pdf.rect(C.monto.x, y, C.monto.w, ROW_H, "FD");

        pdf.setFont("helvetica", "bold");
        pdf.setFontSize(7);
        pdf.setTextColor(0, 0, 0);

        const ty = y + ROW_H / 2 + 1;
        pdf.text(label, C.nro.x + 2, ty, { maxWidth: labelW - 3 });

        const montoStr = `${Number(montoTotal).toLocaleString("es-BO")} Bs`;
        pdf.text(montoStr, C.monto.x + C.monto.w / 2, ty, { align: "center" });

        return y + ROW_H;
    };

    const generarReporte = (datos, gestion, mes, estadoBaja = "", distrito = "", verRetro = false) => {
        try {
            const arr = Array.isArray(datos) ? datos : [];
            const montoTotal = calcMontoTotal(arr);

            const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
            const maxY = PAGE_H - M_BOT;
            const LOGO_BOTTOM = 17;
            let pageNum = 0;
            let y = 0;

            const nuevaPagina = () => {
                if (pageNum > 0) {
                    pdf.setDrawColor(0, 0, 0);
                    pdf.setLineWidth(0.3);
                    pdf.line(C.nro.x, headBottomY, C.monto.x + C.monto.w, headBottomY);
                    pdf.addPage("a4", "portrait");
                }
                pageNum++;
                drawLogos(pdf);
                y = drawTitleBlock(pdf, gestion, mes, estadoBaja, LOGO_BOTTOM + 3, distrito, verRetro);
                y = drawTableHead(pdf, y);
                headBottomY = y;
            };

            nuevaPagina();

            for (let i = 0; i < arr.length; i++) {
                if (y + ROW_H > maxY) nuevaPagina();
                y = drawDataRow(pdf, arr[i], i + 1, y, i % 2 === 0);
            }

            if (y + ROW_H > maxY) nuevaPagina();
            const labelTotal = `TOTAL NO PAGADOS ${periodoTexto(gestion, mes)}`;
            y = drawTotalRow(pdf, montoTotal, y, labelTotal);

            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.3);
            pdf.line(C.nro.x, headBottomY, C.monto.x + C.monto.w, headBottomY);

            pdf.save(`Reporte_Bajas-${fechaArchivo()}.pdf`);
        } catch (error) {
            console.error(error);
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarReporte };
};
