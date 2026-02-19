import { jsPDF } from "jspdf";
import QRCode from "qrcode";

export const useComprobantePDF = () => {
    const getMesNombre = (numeroBoleta) => {
        const mesesAbrev = {
            ENE: "ENERO",
            FEB: "FEBRERO",
            MAR: "MARZO",
            ABR: "ABRIL",
            MAY: "MAYO",
            JUN: "JUNIO",
            JUL: "JULIO",
            AGO: "AGOSTO",
            SEP: "SEPTIEMBRE",
            OCT: "OCTUBRE",
            NOV: "NOVIEMBRE",
            DIC: "DICIEMBRE",
        };
        const mesAbrev = numeroBoleta?.substring(0, 3);
        return mesesAbrev[mesAbrev] || "MES";
    };

    const generateQRCode = async (data) => {
        const datosQR = `Boleta: ${data.numeroBoleta}
Nombre: ${data.beneficiario.nombre} ${data.beneficiario.apellido}
CI: ${data.beneficiario.ci}
Tutor: ${data.tutor.nombre} ${data.tutor.apellido}
CI Tutor: ${data.tutor.ci}
Distrito: ${data.beneficiario.distrito}
Discapacidad: ${data.beneficiario.discapacidad}
Monto: ${data.monto}
Fecha de Pago: ${data.fechaPago}
Gestión: ${data.gestion}`;

        try {
            return await QRCode.toDataURL(datosQR, {
                width: 250,
                margin: 1,
                color: { dark: "#000000", light: "#FFFFFF" },
            });
        } catch (error) {
            console.error("Error generando QR:", error);
            return null;
        }
    };

    // Función auxiliar para dibujar rectángulos redondeados
    const roundRect = (doc, x, y, w, h, r) => {
        doc.roundedRect(x, y, w, h, r, r, "F");
    };

    const roundedRectTop = (doc, x, y, w, h, r) => {
        doc.lines(
            [
                [r, 0], // línea superior derecha
                [w - r, 0], // hasta esquina superior derecha
                [r, r, r, 0, r, 0], // curva superior derecha
                [0, h], // línea derecha
                [-w, 0], // línea inferior
                [0, -h], // línea izquierda
                [r, -r, 0, -r, r, -r], // curva superior izquierda
            ],
            x + r,
            y,
            [1, 1],
            "F",
        );
    };

    // Función para dibujar una tarjeta con borde redondeado
    const drawCard = (
        doc,
        x,
        y,
        w,
        h,
        fillColor = null,
        strokeColor = [224, 224, 224],
    ) => {
        if (fillColor) {
            doc.setFillColor(...fillColor);
        }
        doc.setDrawColor(...strokeColor);
        doc.setLineWidth(0.4);
        doc.roundedRect(x, y, w, h, 3, 3, fillColor ? "FD" : "D");
    };

    const dibujarComprobante = async (
        pdf,
        datos,
        qrCodeImage,
        firmaDigital,
        yOffset = 0,
    ) => {
        const colorAzul = [19, 50, 106];
        const colorGris = [233, 234, 234];
        const colorGrisClaro = [190, 190, 190];
        const colorBlanco = [255, 255, 255];

        // ========== HEADER MODERNO ==========
        // Logo container (izquierda)
        drawCard(pdf, 15, 10 + yOffset, 45, 27, colorBlanco);

        try {
            pdf.addImage("/images/sacaba.png", "PNG", 17, 12 + yOffset, 40, 15);
        } catch (e) {
            console.warn("Logo no encontrado");
        }

        // Badge UMADIS con efecto de sombra
        pdf.setFontSize(20);
        pdf.setFont("helvetica", "bold");

        pdf.setTextColor(190, 190, 190);
        pdf.setGState(new pdf.GState({ opacity: 0.5 }));
        pdf.text("UMADIS", 36.5, 33.5 + yOffset + 1.5, { align: "center" });

        pdf.setGState(new pdf.GState({ opacity: 1.0 }));
        pdf.setTextColor(colorAzul[0], colorAzul[1], colorAzul[2]);
        pdf.text("UMADIS", 37.5, 34 + yOffset, { align: "center" });

        // Título central
        pdf.setTextColor(26, 26, 26);
        pdf.setFontSize(16);
        pdf.setFont("helvetica", "bold");
        pdf.text("COMPROBANTE DE PAGO", 105, 17 + yOffset, { align: "center" });

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(74, 74, 74);
        pdf.text(
            "Ayuda Económica para Personas con Discapacidad",
            105,
            23 + yOffset,
            { align: "center" },
        );

        // Badges de Gestión y Mes
        const badgeY = 27 + yOffset;

        // Badge Gestión
        const gestionWidth = 40;
        const gestionHeight = 7;
        drawCard(pdf, 65, badgeY, gestionWidth, gestionHeight, colorGrisClaro);

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text(
            "GESTIÓN: " + String(datos.gestion),
            65 + gestionWidth / 2,
            badgeY + 5,
            {
                align: "center",
            },
        );

        // Badge Mes (mismo tamaño y estilo)
        const mesWidth = 40;
        const mesHeight = 7;
        drawCard(pdf, 106, badgeY, mesWidth, mesHeight, colorGrisClaro);

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("MES: " + datos.mes, 106 + mesWidth / 2, badgeY + 5, {
            align: "center",
        });

        // QR Code (derecha)
        drawCard(pdf, 161, 6.5 + yOffset, 33, 33, colorBlanco);
        if (qrCodeImage) {
            pdf.addImage(qrCodeImage, "PNG", 163, 8 + yOffset, 29, 30);
        }

        // ========== INFO CARDS (3 columnas) ==========
        let infoY = 40 + yOffset;

        // Card 1: Usuario Pagador
        drawCard(pdf, 15, infoY, 58, 12, colorGris);
        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(102, 102, 102);
        pdf.text("Usuario Pagador", 44, infoY + 4, { align: "center" });
        pdf.setFontSize(9);
        pdf.setTextColor(26, 26, 26);
        pdf.text(datos.usuarioPagador, 44, infoY + 9, { align: "center" });

        // Card 2: N° Boleta
        drawCard(pdf, 76, infoY, 58, 12, colorGris);
        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(102, 102, 102);
        pdf.text("N° BOLETA", 105, infoY + 4, { align: "center" });
        pdf.setFontSize(9);
        pdf.setTextColor(26, 26, 26);
        pdf.text(datos.numeroBoleta, 105, infoY + 9, { align: "center" });

        // Card 3: Monto (destacado)
        drawCard(pdf, 137, infoY, 58, 12, colorAzul);
        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(255, 255, 255);
        pdf.text("Monto Otorgado", 166, infoY + 4, { align: "center" });
        pdf.setFontSize(11);
        pdf.text(`${datos.monto} Bs.`, 166, infoY + 9.5, { align: "center" });

        // ========== DATOS BENEFICIARIO Y TUTOR (2 columnas) ==========
        let dataY = 55 + yOffset;

        // BENEFICIARIO (izquierda)
        drawCard(pdf, 15, dataY, 88, 32, colorBlanco, [224, 224, 224]);

        // Header azul con rounded-top
        pdf.setFillColor(...colorAzul);
        pdf.roundedRect(15, dataY, 88, 6, 2, 2, "F");
        pdf.rect(15, dataY + 4, 88, 2, "F"); // ✅ Cubre las esquinas inferiores
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.text("DATOS DEL BENEFICIARIO", 59, dataY + 4.5, {
            align: "center",
        });

        // Campos
        let fieldY = dataY + 10;
        const fields = [
            {
                label: "Nombre Completo:",
                value: `${datos.beneficiario.apellido} ${datos.beneficiario.nombre}`,
            },
            { label: "Cédula de Identidad:", value: datos.beneficiario.ci },
            {
                label: "Tipo de Discapacidad:",
                value: datos.beneficiario.discapacidad,
            },
            { label: "Distrito:", value: datos.beneficiario.distrito },
        ];

        fields.forEach((field, index) => {
            pdf.setFontSize(7.5);
            pdf.setTextColor(60, 59, 59);
            pdf.setFont("helvetica", "bold");
            pdf.text(field.label, 17, fieldY);

            pdf.setFont("helvetica", "normal");
            pdf.setTextColor(26, 26, 26);
            pdf.text(field.value, 48, fieldY);

            if (index < fields.length - 1) {
                pdf.setDrawColor(240, 240, 240);
                pdf.setLineWidth(0.1);
                pdf.line(17, fieldY + 1.5, 101, fieldY + 1.5);
            }

            fieldY += 5.5;
        });

        // TUTOR (derecha)
        drawCard(pdf, 107, dataY, 88, 32, colorBlanco, [224, 224, 224]);

        // Header azul con rounded-top
        pdf.setFillColor(...colorAzul);
        pdf.roundedRect(107, dataY, 88, 6, 2, 2, "F");
        pdf.rect(107, dataY + 4, 88, 2, "F"); // ✅ CORREGIDO: Cubre las esquinas inferiores
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.text("DATOS DEL TUTOR", 151, dataY + 4.5, { align: "center" });

        // Campos tutor
        fieldY = dataY + 10;
        const tutorFields = [
            {
                label: "Nombre Completo:",
                value: `${datos.tutor.apellido} ${datos.tutor.nombre}`,
            },
            { label: "Cédula de Identidad:", value: datos.tutor.ci },
        ];

        tutorFields.forEach((field, index) => {
            pdf.setFontSize(7.5);
            pdf.setTextColor(60, 59, 59);
            pdf.setFont("helvetica", "bold");
            pdf.text(field.label, 109, fieldY);

            pdf.setFont("helvetica", "normal");
            pdf.setTextColor(26, 26, 26);
            pdf.text(field.value, 145, fieldY);

            if (index < tutorFields.length - 1) {
                pdf.setDrawColor(240, 240, 240);
                pdf.setLineWidth(0.1);
                pdf.line(109, fieldY + 1.5, 193, fieldY + 1.5);
            }

            fieldY += 5.5;
        });
        // ========== DETALLES DE PAGO ==========
        let paymentY = 94 + yOffset;
        drawCard(pdf, 15, paymentY, 180, 13, colorGris, [224, 224, 224]);

        const paymentFields = [
            {
                label: "Monto en Literal:",
                value: `${datos.montoTexto} BOLIVIANOS`,
            },
            { label: "Fecha de Pago:", value: datos.fechaPago },
        ];

        let payY = paymentY + 4.5;
        paymentFields.forEach((field, index) => {
            pdf.setFontSize(7.5);
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(74, 74, 74);
            pdf.text(field.label, 17, payY);

            pdf.setFont("helvetica", "normal");
            pdf.setTextColor(26, 26, 26);
            pdf.text(field.value, 55, payY);

            if (index < paymentFields.length - 1) {
                pdf.setDrawColor(208, 208, 208);
                pdf.setLineWidth(0.1);
                pdf.setLineDash([1, 1]);
                pdf.line(17, payY + 1.5, 193, payY + 1.5);
                pdf.setLineDash([]);
            }

            payY += 5.5;
        });

        // ========== FIRMAS ==========
        let signY = 111 + yOffset;

        // Firma Beneficiario/Tutor (izquierda)
        pdf.setFillColor(0, 0, 0, 0.1);
        pdf.roundedRect(15, signY, 85, 12, 2, 2, "F");
        pdf.setFontSize(7.5);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(74, 74, 74);
        pdf.text("Firma o Huella del Beneficiario o Tutor", 57.5, signY + 16, {
            align: "center",
        });

        // Firma Responsable (derecha)
        pdf.setFillColor(0, 0, 0, 0.1);
        pdf.roundedRect(110, signY, 85, 12, 2, 2, "F");

        if (firmaDigital) {
            try {
                pdf.addImage(`/${firmaDigital}`, "PNG", 120, signY + 1, 65, 10);
            } catch (e) {
                console.warn("Firma digital no encontrada");
            }
        }

        pdf.text("Firma y Sello del Responsable de Pago", 152.5, signY + 16, {
            align: "center",
        });

        // ========== FOOTER ==========
        let footerY = 127 + yOffset;
        pdf.setDrawColor(26, 26, 26);
        pdf.setLineWidth(0.3);
        pdf.line(15, footerY, 195, footerY);

        pdf.setFontSize(7);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(102, 102, 102);
        pdf.text(
            "Ley N° 977 de Inserción Laboral y Ayuda Económica para Personas con Discapacidad",
            105,
            footerY + 3,
            { align: "center" },
        );
    };

    const generarComprobante = async (
        datosRecibo,
        item,
        usuarioPagador,
        firmaDigital,
    ) => {
        try {
            const pdf = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "letter",
            });

            // Datos para el comprobante
            const datos = {
                numeroBoleta: item.pago.numero_boleta,
                gestion: item.gestion.gestion,
                mes: getMesNombre(item.pago.numero_boleta),
                monto: item.mes.monto,
                montoTexto: "Doscientos cincuenta 00/100",
                fechaPago: item.pago.fecha_pago,
                usuarioPagador: `${usuarioPagador.nombre} ${usuarioPagador.apellido}`,
                beneficiario: {
                    nombre: datosRecibo.nombre_persona,
                    apellido: datosRecibo.apellido_persona,
                    ci: datosRecibo.ci_persona,
                    distrito: datosRecibo.distrito,
                    discapacidad: datosRecibo.carnet.discapacidad,
                },
                tutor: {
                    nombre: datosRecibo.tutor.nombre_tutor,
                    apellido: datosRecibo.tutor.apellido_tutor,
                    ci: datosRecibo.tutor.ci_tutor,
                },
            };

            // Generar QR
            const qrCodeImage = await generateQRCode(datos);

            // Generar PRIMER comprobante
            await dibujarComprobante(pdf, datos, qrCodeImage, firmaDigital, 0);

            // LÍNEA DE CORTE
            pdf.setLineDash([2, 2]);
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.4);
            pdf.line(15, 135, 195, 135);
            pdf.setLineDash([]);

            pdf.setFontSize(14);
            pdf.text("✂", 105, 137, { align: "center" });

            // Generar SEGUNDO comprobante
            await dibujarComprobante(
                pdf,
                datos,
                qrCodeImage,
                firmaDigital,
                135,
            );

            // Abrir PDF
            const pdfBlob = pdf.output("blob");
            const blobUrl = URL.createObjectURL(pdfBlob);
            window.open(blobUrl, "_blank");

            setTimeout(() => URL.revokeObjectURL(blobUrl), 300000);
        } catch (error) {
            console.error("Error al generar PDF:", error);
            alert("Error al generar el PDF: " + error.message);
        }
    };

    return { generarComprobante };
};
