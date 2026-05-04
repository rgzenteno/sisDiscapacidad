import { jsPDF } from "jspdf";

export const useReportePagosIndividualPDF = () => {
    // ─── Constantes de página (A4 Portrait) ──────────────────────────────────
    const PAGE_W = 210;
    const PAGE_H = 297;
    const M_LEFT = 10;
    const M_RIGHT = 10;
    const M_BOT = 12;

    const ROW_H = 6.5; // altura de fila de datos
    const HEAD_ROW_H = 9; // altura del thead
    const TOTAL_ROW_H = 7; // si no la tienes, agrégala también
    let headBottomY = 0;

    // ─── Columnas ─────────────────────────────────────────────────────────────
    // Ancho total disponible: 210 - 10 - 10 = 190 mm
    const C = {
        nro: { x: M_LEFT, w: 10 },
        nombre: { x: 20, w: 55 },
        ci: { x: 75, w: 26 },
        distrito: { x: 101, w: 24 },
        mes: { x: 125, w: 22 },
        monto: { x: 147, w: 24 },
        fecha: { x: 171, w: PAGE_W - M_RIGHT - 171 }, // ~29
    };

    // Agrega este helper junto a los otros
    const formatFecha = (f) => {
        if (!f) return "";
        const [y, m, d] = f.split("-");
        return `${d}/${m}/${y}`;
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
        return i >= 0 && i < 12 ? MESES[i] : "Mes";
    };

    const fechaArchivo = () =>
        new Date()
            .toLocaleString("sv-SE", {
                timeZone: "America/La_Paz",
                hour12: false,
            })
            .replace(/ /, "_")
            .replace(/\..+/, "");

    const calcMontoTotal = (arr) =>
        arr.reduce((s, i) => s + (parseFloat(i.monto) || 0), 0);

    // ─── Dibujar logos (misma posición en todas las páginas) ─────────────────
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

    // ─── Bloque info justo antes del thead ───────────────────────────────────
    const drawInfoBlock = (
        pdf,
        usuario,
        gestion,
        mes,
        montoTotal,
        y,
        fechaDesde = "",
        fechaHasta = "",
    ) => {
        pdf.setFontSize(8);

        if (fechaDesde && fechaHasta) {
            // Mostrar rango de fechas
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(0, 0, 0);
            pdf.text("Período:", M_LEFT, y);
            pdf.setTextColor(220, 38, 38);
            pdf.setFont("helvetica", "normal");
            pdf.text(
                `${formatFecha(fechaDesde)}  al  ${formatFecha(fechaHasta)}`,
                M_LEFT + 16,
                y,
            );
            pdf.setTextColor(0, 0, 0);
        } else {
            // Mostrar gestión + mes
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(0, 0, 0);
            pdf.text("Gestión:", M_LEFT, y);
            pdf.setFont("helvetica", "normal");
            pdf.text(String(gestion), M_LEFT + 15, y);

            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(0, 0, 0);
            pdf.text("Meses:", M_LEFT + 34, y);
            pdf.setTextColor(220, 38, 38);
            pdf.setFont("helvetica", "bold");
            pdf.text(getMes(mes).toUpperCase(), M_LEFT + 48, y);
            pdf.setTextColor(0, 0, 0);
        }

        // "usuario: NOMBRE APELLIDO"
        pdf.setFont("helvetica", "bold");
        pdf.text("usuario:", M_LEFT + 80, y);
        pdf.setFont("helvetica", "normal");
        pdf.text((usuario || "").toUpperCase(), M_LEFT + 95, y, {
            maxWidth: 50,
        });

        // "Monto Total Pagado: X Bs" — alineado al borde derecho
        const montoStr = `${Number(montoTotal).toLocaleString("es-BO")} Bs`;
        pdf.setFont("helvetica", "bold");
        pdf.text(
            "Monto Total Pagado:",
            PAGE_W -
                M_RIGHT -
                pdf.getTextWidth(montoStr) -
                pdf.getTextWidth("Monto Total Pagado:") -
                1,
            y,
        );
        pdf.setFont("helvetica", "normal");
        pdf.text(montoStr, PAGE_W - M_RIGHT - pdf.getTextWidth(montoStr), y);

        // Línea separadora
        pdf.setDrawColor(180, 180, 180);
        pdf.setLineWidth(0.25);
        pdf.line(M_LEFT, y + 2.5, PAGE_W - M_RIGHT, y + 2.5);

        return y + 5;
    };

    // ─── Dibujar thead ────────────────────────────────────────────────────────
    const drawTableHead = (pdf, y) => {
        const cols = [
            { c: C.nro, label: ["Nro"] },
            { c: C.nombre, label: ["Nombre Completo"] },
            { c: C.ci, label: ["Cedula de", "Identidad"] },
            { c: C.distrito, label: ["Distrito"] },
            { c: C.mes, label: ["Mes de pago"] },
            { c: C.monto, label: ["Monto pago (Bs)"] },
            { c: C.fecha, label: ["Fecha de pago"] },
        ];

        pdf.setFillColor(220, 220, 220);
        pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        cols.forEach(({ c }) => pdf.rect(c.x, y, c.w, HEAD_ROW_H, "FD"));

        pdf.setFontSize(6.5);
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
    const drawDataRow = (pdf, item, rowNum, y, isOdd) => {
        pdf.setFillColor(
            isOdd ? 245 : 255,
            isOdd ? 245 : 255,
            isOdd ? 245 : 255,
        );
        pdf.setDrawColor(180, 180, 180);
        pdf.setLineWidth(0.2);
        Object.values(C).forEach((c) => pdf.rect(c.x, y, c.w, ROW_H, "FD"));

        pdf.setFontSize(6.5);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);

        const ty = y + ROW_H / 2 + 1.5;

        pdf.text(String(rowNum), C.nro.x + C.nro.w / 2, ty, {
            align: "center",
        });

        const nombre =
            `${item.apellido_persona || item.apellido || ""} ${item.nombre_persona || item.nombre || ""}`
                .toUpperCase()
                .trim();
        pdf.text(nombre, C.nombre.x + 1.5, ty, { maxWidth: C.nombre.w - 3 });

        pdf.text(
            String(item.ci_persona || item.ci || ""),
            C.ci.x + C.ci.w / 2,
            ty,
            { align: "center" },
        );

        pdf.text(
            String(item.distrito || ""),
            C.distrito.x + C.distrito.w / 2,
            ty,
            {
                align: "center",
                maxWidth: C.distrito.w - 2,
            },
        );

        const mesStr =
            item.mes_pago ||
            (item.mes && item.gestion
                ? `${String(item.mes).padStart(2, "0")}/${item.gestion}`
                : "");
        pdf.text(mesStr, C.mes.x + C.mes.w / 2, ty, { align: "center" });

        pdf.text(String(item.monto || ""), C.monto.x + C.monto.w / 2, ty, {
            align: "center",
        });

        pdf.text(String(item.fecha_pago || ""), C.fecha.x + C.fecha.w / 2, ty, {
            align: "center",
            maxWidth: C.fecha.w - 1,
        });

        return y + ROW_H;
    };

    // ─── Función principal ────────────────────────────────────────────────────
    const generarReporte = async (
        datos,
        gestion,
        mes,
        nombreUsuario,
        tipoR = "MisPagos",
        fechaDesde = "",
        fechaHasta = "",
    ) => {
        try {
            const arr = Array.isArray(datos) ? datos : [];
            const montoTotal = calcMontoTotal(arr);

            const pdf = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "a4",
            });

            const maxY = PAGE_H - M_BOT;
            // Y donde arrancan los logos (siempre arriba)
            const LOGO_BOTTOM = 17;
            // Altura del bloque info + thead
            const INFO_H = 5 + HEAD_ROW_H;

            let pageNum = 0;
            let y = 0;

            const nuevaPagina = () => {
                if (pageNum > 0) {
                    // ✅ Dibuja la línea de la página actual ANTES de pasar a la siguiente
                    pdf.setDrawColor(0, 0, 0);
                    pdf.setLineWidth(0.3);
                    pdf.line(
                        C.nro.x,
                        headBottomY,
                        C.fecha.x + C.fecha.w,
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
                    montoTotal,
                    LOGO_BOTTOM + 3,
                    fechaDesde,
                    fechaHasta,
                );
                y = drawTableHead(pdf, y);
                headBottomY = y;
            };

            nuevaPagina();

            for (let i = 0; i < arr.length; i++) {
                if (y + ROW_H > maxY) {
                    nuevaPagina();
                }
                y = drawDataRow(pdf, arr[i], i + 1, y, i % 2 === 0);
            }

            // ✅ Dibuja la línea de la última página
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.3);
            pdf.line(C.nro.x, headBottomY, C.fecha.x + C.fecha.w, headBottomY);

            pdf.save(`Reporte_${tipoR}-${fechaArchivo()}.pdf`);
        } catch (error) {
            console.error("Error al generar el reporte:", error);
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarReporte };
};
