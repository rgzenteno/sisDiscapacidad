import { jsPDF } from "jspdf";

export const useReporteResumenPDF = () => {
    // ─── Constantes de página (A4 Portrait) ──────────────────────────────────
    const PAGE_W = 210;
    const PAGE_H = 297;
    const M_LEFT = 10;
    const M_RIGHT = 10;
    const M_BOT = 12;

    const ROW_H = 7;
    const HEAD_ROW_H = 10;
    const TOTAL_ROW_H = 7;

    let headBottomY = 0;

    // ─── Columnas ─────────────────────────────────────────────────────────────
    // Total disponible: 190 mm
    const C = {
        nro: { x: M_LEFT, w: 14 },
        nombre: { x: 24, w: 82 },
        cantidad: { x: 106, w: 36 },
        unitario: { x: 142, w: 28 },
        total: { x: 170, w: PAGE_W - M_RIGHT - 170 }, // ~30
    };

    // ─── Helpers ──────────────────────────────────────────────────────────────
    const MESES = [
        "Enero",
        "Febrero",
        "Marzo",
        "Abril",
        "Mayo",
        "Junio",
        "Julio",
        "Agosto",
        "Septiembre",
        "Octubre",
        "Noviembre",
        "Diciembre",
    ];

    const getMes = (n) => {
        const i = parseInt(n, 10) - 1;
        return i >= 0 && i < 12 ? MESES[i] : "";
    };

    const fechaArchivo = () =>
        new Date()
            .toLocaleString("sv-SE", {
                timeZone: "America/La_Paz",
                hour12: false,
            })
            .replace(/ /, "_")
            .replace(/\..+/, "");

    const fechaHoraActual = () =>
        new Date().toLocaleString("es-BO", {
            timeZone: "America/La_Paz",
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
        });

    // Primer y último día del mes para el arqueo
    const rangoMes = (gestion, mes) => {
        const m = String(mes).padStart(2, "0");
        const g = String(gestion);
        const ultimoDia = new Date(Number(g), Number(mes), 0).getDate();
        return { desde: `01/${m}/${g}`, hasta: `${ultimoDia}/${m}/${g}` };
    };

    // ─── Calcular totales ─────────────────────────────────────────────────────
    const calcTotales = (arr) => ({
        cantidad: arr.reduce(
            (s, i) => s + (parseInt(i.cantidad_pagos) || 0),
            0,
        ),
        total: arr.reduce((s, i) => s + (parseFloat(i.monto_total) || 0), 0),
    });

    // ─── Dibujar logos ────────────────────────────────────────────────────────
    const drawLogos = (pdf) => {
        pdf.setFillColor(255, 255, 255);
        pdf.rect(M_LEFT, 1, PAGE_W - M_LEFT - M_RIGHT, 16, "F");
        try {
            pdf.addImage("/images/sacaba.png", "PNG", M_LEFT, 2, 28, 13);
        } catch (_) {}
        try {
            pdf.addImage(
                "/images/sigamos.png",
                "PNG",
                PAGE_W - M_RIGHT - 20,
                3,
                20,
                11,
            );
        } catch (_) {}
    };

    // ─── Dibujar bloque de cabecera informativa ───────────────────────────────
    const drawInfoBlock = (
        pdf,
        usuario,
        gestion,
        mes,
        filtroDesde,
        filtroHasta,
        startY,
    ) => {
        const mesNombre = getMes(mes);

        // Calcular rango para "Arqueo de caja"
        let arqueoDesde, arqueoHasta;
        if (filtroDesde && filtroHasta) {
            // Si viene de filtro por rango de fechas, usarlo tal cual
            arqueoDesde = filtroDesde;
            arqueoHasta = filtroHasta;
        } else if (gestion && mes) {
            const r = rangoMes(gestion, mes);
            arqueoDesde = r.desde;
            arqueoHasta = r.hasta;
        } else {
            arqueoDesde = "--";
            arqueoHasta = "--";
        }

        let y = startY;

        pdf.setFontSize(8.5);

        // Línea 1 — Fecha y hora del Reporte
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("Fecha y hora del Reporte:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(fechaHoraActual(), M_LEFT + 54, y);
        y += 5;

        // Línea 2 — Arqueo de caja
        pdf.setFont("helvetica", "bold");
        pdf.text("Arqueo de caja:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(`${arqueoDesde} al ${arqueoHasta}`, M_LEFT + 38, y);
        y += 5;

        // Línea 3 — Usuario + Mes de Pago
        pdf.setFont("helvetica", "bold");
        pdf.text("Usuario:", M_LEFT, y);
        pdf.setFont("helvetica", "normal");
        pdf.text((usuario || "").toUpperCase(), M_LEFT + 18, y);

        pdf.setFont("helvetica", "bold");
        pdf.text("Mes de Pago:", PAGE_W / 2 + 5, y);
        pdf.setFont("helvetica", "normal");
        pdf.text(mesNombre, PAGE_W / 2 + 30, y);
        y += 6;

        return y; // Y donde empieza el thead
    };

    // ─── Dibujar thead ────────────────────────────────────────────────────────
    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.nro, label: ["N°"] },
            { c: C.nombre, label: ["Nombre Completo del Usuario"] },
            { c: C.cantidad, label: ["Cantidad", "Beneficiarios"] },
            { c: C.unitario, label: ["Monto", "Unitario (Bs)"] },
            { c: C.total, label: ["Total, Monto", "(Bs)"] },
        ];

        pdf.setFillColor(210, 210, 210);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        cols.forEach(({ c }) => pdf.rect(c.x, y, c.w, HEAD_ROW_H, "FD"));

        pdf.setFontSize(7);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);

        cols.forEach(({ c, label }) => {
            const cx = c.x + c.w / 2;
            if (label.length === 1) {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 + 1.5, {
                    align: "center",
                });
            } else {
                pdf.text(label[0], cx, y + HEAD_ROW_H / 2 - 0.5, {
                    align: "center",
                });
                pdf.text(label[1], cx, y + HEAD_ROW_H / 2 + 3.5, {
                    align: "center",
                });
            }
        });

        return y + HEAD_ROW_H;
    };

    // ─── Dibujar fila de datos ────────────────────────────────────────────────
    const drawDataRow = (pdf, item, rowNum, y) => {
        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(180, 180, 180);
        pdf.setLineWidth(0.2);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(7);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);

        const ty = y + ROW_H / 2 + 1.5;

        // N°
        pdf.text(String(rowNum), C.nro.x + C.nro.w / 2, ty, {
            align: "center",
        });

        // Nombre completo
        const nombre = `${item.nombre || ""} ${item.apellido || ""}`
            .toUpperCase()
            .trim();
        pdf.text(nombre, C.nombre.x + 2, ty, { maxWidth: C.nombre.w - 4 });

        // Cantidad beneficiarios
        pdf.text(
            String(item.cantidad_pagos ?? ""),
            C.cantidad.x + C.cantidad.w / 2,
            ty,
            { align: "center" },
        );

        // Monto unitario
        pdf.text(
            String(item.monto ?? ""),
            C.unitario.x + C.unitario.w / 2,
            ty,
            { align: "center" },
        );

        // Total monto
        pdf.text(
            Number(item.monto_total || 0).toLocaleString("es-BO"),
            C.total.x + C.total.w / 2,
            ty,
            { align: "center" },
        );

        return y + ROW_H;
    };

    // ─── Dibujar fila de totales ──────────────────────────────────────────────
    const drawTotalRow = (pdf, totales, montoUnitario, y) => {
        // Después (con borde y texto TOTAL):
        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(0, 0, 0); // borde visible
        pdf.setLineWidth(0.3);
        pdf.rect(C.nro.x, y, C.nro.w + C.nombre.w, TOTAL_ROW_H, "FD");

        pdf.setFontSize(7.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        const tyLabel = y + TOTAL_ROW_H / 2 + 1.5;
        pdf.text("TOTAL", C.nro.x + 2, tyLabel, { align: "left" });

        // Celdas con borde para los tres valores
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        pdf.rect(C.cantidad.x, y, C.cantidad.w, TOTAL_ROW_H, "D");
        pdf.rect(C.unitario.x, y, C.unitario.w, TOTAL_ROW_H, "D");
        pdf.rect(C.total.x, y, C.total.w, TOTAL_ROW_H, "D");

        pdf.setFontSize(7.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);

        const ty = y + TOTAL_ROW_H / 2 + 1.5;

        pdf.text(
            String(totales.cantidad),
            C.cantidad.x + C.cantidad.w / 2,
            ty,
            { align: "center" },
        );
        pdf.text(String(montoUnitario), C.unitario.x + C.unitario.w / 2, ty, {
            align: "center",
        });
        pdf.text(
            Number(totales.total).toLocaleString("es-BO"),
            C.total.x + C.total.w / 2,
            ty,
            { align: "center" },
        );
    };

    // ─── Función principal ────────────────────────────────────────────────────
    /**
     * @param {Array}  datos         - resumenGeneral (array de usuarios con sus totales)
     * @param {string} gestion       - año seleccionado (puede ser '' si se usó rango)
     * @param {string} mes           - número de mes '01'..'12' (puede ser '' si se usó rango)
     * @param {string} nombreUsuario - nombre del admin logueado
     * @param {string} filtroDesde   - fecha desde (si se usó rango, formato YYYY-MM-DD)
     * @param {string} filtroHasta   - fecha hasta (si se usó rango, formato YYYY-MM-DD)
     */
    const generarReporte = async (
        datos,
        gestion,
        mes,
        nombreUsuario,
        filtroDesde = "",
        filtroHasta = "",
        tipoR = "ResumenGeneral",
    ) => {
        try {
            const arr = Array.isArray(datos) ? datos : [];
            const totales = calcTotales(arr);
            // Monto unitario: todos deberían ser iguales, tomamos el primero
            const montoUnitario = arr.length > 0 ? (arr[0].monto ?? 250) : 250;

            const pdf = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "a4",
            });

            const LOGO_BOTTOM = 18;
            const maxY = PAGE_H - M_BOT;

            let pageNum = 0;
            let y = 0;

            const nuevaPagina = () => {
                if (pageNum > 0) {
                    // ✅ Dibuja la línea antes de pasar a la siguiente página
                    pdf.setDrawColor(0, 0, 0);
                    pdf.setLineWidth(0.3);
                    pdf.line(
                        C.nro.x,
                        headBottomY,
                        C.total.x + C.total.w,
                        headBottomY,
                    );
                    pdf.addPage("a4", "portrait");
                }
                pageNum++;
                drawLogos(pdf);
                y = drawInfoBlock(
                    pdf,
                    nombreUsuario,
                    gestion,
                    mes,
                    filtroDesde,
                    filtroHasta,
                    LOGO_BOTTOM + 2,
                );
                y = drawTableHead(pdf, y);
                headBottomY = y;
            };

            nuevaPagina();

            for (let i = 0; i < arr.length; i++) {
                const esUltimo = i === arr.length - 1;
                const espacioNecesario =
                    ROW_H + (esUltimo ? TOTAL_ROW_H + 2 : 0);

                if (y + espacioNecesario > maxY) {
                    nuevaPagina();
                }

                y = drawDataRow(pdf, arr[i], i + 1, y);
            }

            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.3);
            pdf.line(C.nro.x, headBottomY, C.total.x + C.total.w, headBottomY);

            // Fila de totales
            if (arr.length === 0 || y + TOTAL_ROW_H > maxY) {
                nuevaPagina();
            }
            drawTotalRow(pdf, totales, montoUnitario, y);

            pdf.save(`Reporte_${tipoR}-${fechaArchivo()}.pdf`);
        } catch (error) {
            console.error("Error al generar el reporte de resumen:", error);
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarReporte };
};
