import { jsPDF } from "jspdf";

export const useReporteArqueoGeneralPDF = () => {
    const PAGE_W = 297;
    const PAGE_H = 210;
    const M_LEFT = 10;
    const M_RIGHT = 10;
    const M_BOT = 12;

    const ROW_H = 9;
    const HEAD_ROW_H = 16;
    const TOTAL_ROW_H = 9;

    let headBottomY = 0;

    const C = {
        nro: { x: M_LEFT, w: 12 },
        detalle: { x: 22, w: 55 },
        presupuesto: { x: 75, w: 45 },
        beneficiarios: { x: 120, w: 32 },
        pagado: { x: 150, w: 35 },
        numPagos: { x: 185, w: 32 },
        noPagados: { x: 216, w: 46 },
        saldo: { x: 261, w: PAGE_W - M_RIGHT - 260 },
    };

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

    const fechaActual = () => {
        const options = { year: "numeric", month: "long", day: "numeric" };
        let dateStr = new Date().toLocaleDateString("es-ES", options);
        return dateStr.replace(/\b\w/g, (c) => c.toUpperCase());
    };

    const formatCurrency = (amount) =>
        new Intl.NumberFormat("es-BO", {
            style: "decimal",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount || 0);

    // ─── Calcular totales ─────────────────────────────────────────────────────
    const calcTotales = (arr) => ({
        // ✅ presupuesto = suma de (monto_mes × beneficiarios) por fila
        presupuesto: arr.reduce(
            (s, i) =>
                s +
                (parseFloat(i.presupuesto_mes) || 0) *
                (parseInt(i.cantidad_habilitadas) || 0),
            0,
        ),
        beneficiarios: arr.reduce(
            (s, i) => s + (parseInt(i.cantidad_habilitadas) || 0),
            0,
        ),
        pagado: arr.reduce(
            (s, i) => s + (parseFloat(i.total_pagado_contexto) || 0),
            0,
        ),
        numPagos: arr.reduce(
            (s, i) => s + (parseInt(i.cantidad_total_pagos) || 0),
            0,
        ),
        noPagados: arr.reduce(
            (s, i) => s + (parseInt(i.cantidad_no_pagados) || 0),
            0,
        ),
        // ✅ saldo = suma de (presupuesto_fila - pagado) por fila
        saldo: arr.reduce((s, i) => {
            const presupuestoFila =
                (parseFloat(i.presupuesto_mes) || 0) *
                (parseInt(i.cantidad_habilitadas) || 0);
            return (
                s + presupuestoFila - (parseFloat(i.total_pagado_contexto) || 0)
            );
        }, 0),
    });

    // ─── Dibujar header ───────────────────────────────────────────────────────
    const drawHeader = async (pdf, gestion, nombreUsuario, startY) => {
        const HEADER_H = 27;
        const y = startY;

        pdf.setFillColor(255, 255, 255);
        pdf.rect(M_LEFT, y, PAGE_W - M_LEFT - M_RIGHT, HEADER_H, "F");

        try {
            const imgData = await fetch("/images/sacaba.png")
                .then(r => r.blob())
                .then(b => new Promise(res => {
                    const reader = new FileReader();
                    reader.onload = () => res(reader.result);
                    reader.readAsDataURL(b);
                }));
            pdf.addImage(imgData, "PNG", M_LEFT, y + 1, 39, 19);
        } catch (_) { }

        pdf.setFontSize(20);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(200, 0, 0);
        pdf.text("ARQUEO GENERAL DE TESORERÍA", PAGE_W / 2, y + 12, {
            align: "center",
        });
        pdf.text(`GESTIÓN ${gestion}`, PAGE_W / 2, y + 22, { align: "center" });

        const blockX = PAGE_W - M_RIGHT - 48;
        const blockY = y + 2;

        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.4);
        pdf.line(blockX - 2, blockY + 1, blockX - 2, blockY + 13);

        pdf.setFontSize(7.5);
        pdf.setTextColor(0, 0, 0);

        pdf.setFont("helvetica", "bold");
        pdf.text("USUARIO:", blockX + 1, blockY + 5);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            (nombreUsuario || "")
                .toLowerCase()
                .replace(/\b\w/g, (c) => c.toUpperCase()),
            blockX + 15,
            blockY + 5,
            { maxWidth: 30 },
        );

        pdf.setFont("helvetica", "bold");
        pdf.text("FECHA:", blockX + 1, blockY + 11);
        pdf.setFont("helvetica", "normal");
        pdf.text(fechaActual(), blockX + 15, blockY + 11);

        return y + HEADER_H + 2;
    };

    // ─── Dibujar thead ────────────────────────────────────────────────────────
    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.nro, lines: ["N°"] },
            { c: C.detalle, lines: ["Detalle"] },
            {
                c: C.presupuesto,
                lines: [
                    "Solicitud de desembolsos de",
                    "Recursos Humanos en Bs.",
                ],
            },
            { c: C.beneficiarios, lines: ["Número de", "beneficiarios PCD."] },
            { c: C.pagado, lines: ["Monto total pagado", "a PCD. en Bs."] },
            { c: C.numPagos, lines: ["Número de pagos a", "PCD beneficiadas"] },
            {
                c: C.noPagados,
                lines: [
                    "Cantidad de personas que",
                    "no han cobrado el bono PCD.",
                ],
            },
            { c: C.saldo, lines: ["Total de saldos", "no cancelados"] },
        ];

        pdf.setFillColor(230, 230, 230);
        pdf.setDrawColor(51, 51, 51);
        pdf.setLineWidth(0.3);
        cols.forEach(({ c }) => pdf.rect(c.x, y, c.w, HEAD_ROW_H, "FD"));

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);

        cols.forEach(({ c, lines }) => {
            const cx = c.x + c.w / 2;
            const lineH = 3.8;
            const totalH = lines.length * lineH;
            const startTextY = y + (HEAD_ROW_H - totalH) / 2 + lineH - 0.5;
            lines.forEach((line, idx) => {
                pdf.text(line, cx, startTextY + idx * lineH, {
                    align: "center",
                });
            });
        });

        return y + HEAD_ROW_H;
    };

    // ─── Dibujar fila de datos ────────────────────────────────────────────────
    const drawDataRow = (pdf, item, rowNum, y) => {
        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(180, 180, 180);
        pdf.setLineWidth(0.2);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);

        const ty = y + ROW_H / 2 + 1.5;

        // N°
        pdf.text(String(rowNum), C.nro.x + C.nro.w / 2, ty, {
            align: "center",
        });

        // Detalle
        const detalle =
            `Solicitud Económica ${getMes(item.mes)} ${item.gestion}`.toUpperCase();
        pdf.text(detalle, C.detalle.x + 1.5, ty, { maxWidth: C.detalle.w - 3 });

        // ✅ Presupuesto = monto_mes × beneficiarios
        const presupuestoFila =
            (parseFloat(item.presupuesto_mes) || 0) *
            (parseInt(item.cantidad_habilitadas) || 0);
        pdf.text(
            formatCurrency(presupuestoFila),
            C.presupuesto.x + C.presupuesto.w - 1.5,
            ty,
            { align: "right" },
        );

        // Beneficiarios (centro)
        pdf.text(
            String(item.cantidad_habilitadas ?? ""),
            C.beneficiarios.x + C.beneficiarios.w / 2,
            ty,
            { align: "center" },
        );

        // Total pagado (derecha)
        pdf.text(
            formatCurrency(item.total_pagado_contexto),
            C.pagado.x + C.pagado.w - 1.5,
            ty,
            { align: "right" },
        );

        // Número de pagos (centro)
        pdf.text(
            String(item.cantidad_total_pagos ?? ""),
            C.numPagos.x + C.numPagos.w / 2,
            ty,
            { align: "center" },
        );

        // No pagados (centro)
        pdf.text(
            String(item.cantidad_no_pagados ?? ""),
            C.noPagados.x + C.noPagados.w / 2,
            ty,
            { align: "center" },
        );

        // ✅ Saldo = presupuestoFila - total_pagado
        const saldo =
            presupuestoFila - (parseFloat(item.total_pagado_contexto) || 0);
        pdf.text(formatCurrency(saldo), C.saldo.x + C.saldo.w - 1.5, ty, {
            align: "right",
        });

        return y + ROW_H;
    };

    // ─── Dibujar fila de totales ──────────────────────────────────────────────
    const drawTotalRow = (pdf, totales, y) => {
        pdf.setFillColor(242, 242, 242);
        pdf.setDrawColor(180, 180, 180);
        pdf.setLineWidth(0.3);
        pdf.rect(C.nro.x, y, C.nro.w + C.detalle.w, TOTAL_ROW_H, "FD");

        pdf.setFontSize(7.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        const ty = y + TOTAL_ROW_H / 2 + 1.5;
        pdf.text("TOTAL GENERAL", C.nro.x + 2, ty);

        const totalCols = [
            {
                c: C.presupuesto,
                value: formatCurrency(totales.presupuesto),
                align: "right",
                pad: -1.5,
            },
            {
                c: C.beneficiarios,
                value: String(totales.beneficiarios),
                align: "center",
                pad: 0,
            },
            {
                c: C.pagado,
                value: formatCurrency(totales.pagado),
                align: "right",
                pad: -1.5,
            },
            {
                c: C.numPagos,
                value: String(totales.numPagos),
                align: "center",
                pad: 0,
            },
            {
                c: C.noPagados,
                value: String(totales.noPagados),
                align: "center",
                pad: 0,
            },
            {
                c: C.saldo,
                value: formatCurrency(totales.saldo),
                align: "right",
                pad: -1.5,
            },
        ];

        totalCols.forEach(({ c, value, align, pad }) => {
            pdf.setFillColor(242, 242, 242);
            pdf.rect(c.x, y, c.w, TOTAL_ROW_H, "FD");
            const textX = align === "right" ? c.x + c.w + pad : c.x + c.w / 2;
            pdf.text(value, textX, ty, { align });
        });
    };

    // ─── Función principal ────────────────────────────────────────────────────
    const generarReporte = async (
        datos,
        nombreUsuario,
        tipoR = "ArqueoGeneral",
    ) => {
        try {
            const arr = Array.isArray(datos) ? datos : [];
            if (arr.length === 0) {
                console.warn("useReporteArqueoGeneralPDF: no hay datos.");
                return;
            }

            const gestion = arr[0]?.gestion ?? "";

            // ✅ Normalizar a 12 meses — los que faltan quedan en 0
            const mesMap = new Map(
                arr.map((item) => [parseInt(item.mes, 10), item]),
            );

            const arr12 = Array.from({ length: 12 }, (_, i) => {
                const numMes = i + 1;
                return (
                    mesMap.get(numMes) ?? {
                        mes: numMes,
                        gestion: gestion,
                        presupuesto_mes: 0,
                        cantidad_habilitadas: 0,
                        total_pagado_contexto: 0,
                        cantidad_total_pagos: 0,
                        cantidad_no_pagados: 0,
                    }
                );
            });

            const totales = calcTotales(arr12); // ← usar arr12, no arr

            const pdf = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: "a4",
            });

            const maxY = PAGE_H - M_BOT;
            let pageNum = 0;
            let y = 0;

            const nuevaPagina = async () => {
                if (pageNum > 0) {
                    pdf.setDrawColor(0, 0, 0);
                    pdf.setLineWidth(0.3);
                    pdf.line(
                        C.nro.x,
                        headBottomY,
                        C.saldo.x + C.saldo.w,
                        headBottomY,
                    );
                    pdf.addPage("a4", "landscape");
                }
                pageNum++;
                y = await drawHeader(pdf, gestion, nombreUsuario, 5);
                y = drawTableHead(pdf, y);
                headBottomY = y;
            };

            await nuevaPagina();

            for (let i = 0; i < arr12.length; i++) {
                // ← arr12
                const esUltimo = i === arr12.length - 1;
                const espacioNecesario =
                    ROW_H + (esUltimo ? TOTAL_ROW_H + 2 : 0);

                if (y + espacioNecesario > maxY) {
                    await nuevaPagina();
                }

                y = drawDataRow(pdf, arr12[i], i + 1, y); // ← arr12
            }

            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.3);
            pdf.line(C.nro.x, headBottomY, C.saldo.x + C.saldo.w, headBottomY);

            if (y + TOTAL_ROW_H > maxY) {
                await nuevaPagina();
            }
            drawTotalRow(pdf, totales, y);

            pdf.save(`Reporte_${tipoR}-${fechaArchivo()}.pdf`);
        } catch (error) {
            console.error(
                "Error al generar el reporte de arqueo general:",
                error,
            );
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarReporte };
};
