import { jsPDF } from "jspdf";
import QRCode from "qrcode";

export const useComprobantePDF = () => {
    const capitalizar = (texto) => {
        return texto
            .toLowerCase()
            .split(" ")
            .map(
                (palabra) => palabra.charAt(0).toUpperCase() + palabra.slice(1),
            )
            .join(" ");
    };

    const getMesNombre = (mes) => {
        const mesesAbrev = {
            1: "Enero",
            2: "Febrero",
            3: "Marzo",
            4: "Abril",
            5: "Mayo",
            6: "Junio",
            7: "Julio",
            8: "Agosto",
            9: "Septiembre",
            10: "Octubre",
            11: "Noviembre",
            12: "Diciembre",
        };
        return mesesAbrev[mes] || "MES";
    };

    const generateQRCode = async (data) => {
    const nombreCompleto = capitalizar(
        `${data.beneficiario.apellido} ${data.beneficiario.nombre}`,
    );

    const uuid = data.beneficiario.uuid.toUpperCase();
    const uuidParts = uuid.split("-");
    const shortUuid = `${uuidParts[0]}-${uuidParts[1]}`; // A0FEE046-E15A

    const datosQR = `GAMS - Sacaba
Unidad Municipal de Atención a Personas con Discapacidad

Comprobante de Pago
Beneficiario: ${nombreCompleto}
CI: ${data.beneficiario.ci}
Distrito: ${data.beneficiario.distrito}
Monto: Bs. ${data.monto},00
Fecha: ${data.fechaPago}
Estado: Pago Realizado
Código: ${shortUuid}
`;

        try {
            return await QRCode.toDataURL(datosQR, {
                width: 250,
                margin: 2,
                color: { dark: "#000000", light: "#ffff" },
            });
        } catch (error) {
            console.error("Error generando QR:", error);
            return null;
        }
    };

    const dibujarComprobante = async (
        pdf,
        datos,
        qrCodeImage,
        firmaDigital,
        yOffset = 0,
    ) => {
        const colorGris = [245, 245, 245];
        const colorBorde = [200, 200, 200];
        const colorAmarillo = [255, 235, 59];
        const colorAzul = [19, 50, 106];

        // ========== IMAGEN DE FONDO ==========
        try {
            pdf.addImage(
                "/images/fondoComprobante.png",
                "PNG",
                20,
                9 + yOffset,
                175,
                85,
            );
        } catch (e) {
            console.warn("Imagen de fondo no encontrada");
        }

        // ========== HEADER ==========
        // Logo
        try {
            pdf.addImage("/images/sacaba.png", "PNG", 22, 11 + yOffset, 47, 20);
        } catch (e) {
            console.warn("Logo no encontrado");
        }

        // COMPROBANTE DE PAGO (título principal centrado)
        pdf.setFontSize(19);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("COMPROBANTE DE PAGO", 120, 19 + yOffset, { align: "center" });

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            "Ayuda Económica a Personas con Discapacidad",
            120,
            25 + yOffset,
            { align: "center" },
        );

        // Badges Gestión y Mes (sin fondo, solo texto)
        pdf.setFontSize(12);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text(
            `PERIODO: ${datos.mes.toUpperCase()} / ${datos.gestion}`,
            120,
            33 + yOffset,
            { align: "center" },
        );

        // QR Code (más pequeño)
        if (qrCodeImage) {
            pdf.addImage(qrCodeImage, "PNG", 167, 11 + yOffset, 26, 26);
        }

        // Badge UMADIS con efecto de sombra
        /* pdf.setFontSize(17);
        pdf.setFont("helvetica", "bold");

        pdf.setTextColor(150, 150, 150);
        pdf.setGState(new pdf.GState({ opacity: 0.5 }));
        pdf.text("UMADIS", 52.5, 32 + yOffset + 1.5, { align: "center" });

        pdf.setGState(new pdf.GState({ opacity: 1.0 }));
        pdf.setTextColor(colorAzul[0], colorAzul[1], colorAzul[2]);
        pdf.text("UMADIS", 54, 32.5 + yOffset, { align: "center" }); */

        // ========== INFO PRINCIPAL ==========
        let infoY = 35 + yOffset;
        infoY += 5.5;

        // Primera fila
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.text("N° de Comprobante:", 21, infoY + 1.5);
        pdf.setFont("helvetica", "normal");
        pdf.text(datos.numeroBoleta, 52.5, infoY + 1.5);

        pdf.setFont("helvetica", "bold");
        pdf.text("Fecha de Emisión:", 71, infoY + 1.5);
        pdf.setFont("helvetica", "normal");
        pdf.text(datos.fechaPago, 100, infoY + 1.5);

        // Código Único (centrado y en dos líneas si es necesario)
        pdf.setFont("helvetica", "bold");
        pdf.text("Código:", 158.5, infoY + 1.5);
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(8);

        const uuid = datos.beneficiario.uuid.toUpperCase();
        // Extraer solo los dos primeros segmentos del UUID
        const uuidParts = uuid.split("-");
        const shortUuid = `${uuidParts[0]}-${uuidParts[1]}`; // A0FEE046-E15A

        pdf.text(shortUuid, 171, infoY + 1.5);
        // Línea horizontal después de la primera fila

        infoY += 5.5;

        // Segunda línea para código único
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.text("Distrito Municipal:", 21, infoY + 2);
        pdf.setFont("helvetica", "normal");
        pdf.text(datos.beneficiario.distrito, 52.5, infoY + 2);

        pdf.setFont("helvetica", "bold");
        pdf.text("Medio de Pago:", 71, infoY + 2);
        pdf.setFont("helvetica", "normal");
        pdf.text("Efectivo", 100, infoY + 2);

        const usuario = capitalizar(datos.usuarioPagador);
        const maxWidth = 40; // Ancho máximo permitido
        const xPosition = 192; // Posición final a la derecha

        pdf.setFont("helvetica", "normal");
        const usuarioWidth = pdf.getTextWidth(usuario);
        const actualWidth = Math.min(usuarioWidth, maxWidth); // El ancho real que ocupará

        pdf.setFont("helvetica", "bold");
        const labelWidth = pdf.getTextWidth("Usuario:");

        // Posicionar "Usuario:" justo antes del valor del usuario
        pdf.text("Usuario:", xPosition - actualWidth - 1, infoY + 3.5, {
            align: "right",
        });

        pdf.setFont("helvetica", "normal");
        pdf.text(usuario, xPosition, infoY + 3.5, {
            maxWidth: maxWidth,
            align: "right",
        });

        infoY += 3;

        // ========== TABLA DE DATOS ==========
        let tableY = infoY + 3;

        // Beneficiario
        pdf.setFillColor(colorGris[0], colorGris[1], colorGris[2]);
        pdf.setDrawColor(colorBorde[0], colorBorde[1], colorBorde[2]);
        pdf.setLineWidth(0.3);
        pdf.rect(20, tableY, 70, 6, "D");
        pdf.rect(90, tableY, 105, 6, "D");

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("Beneficiario(a):", 21, tableY + 4);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            `${datos.beneficiario.apellido} ${datos.beneficiario.nombre}`.toUpperCase(),
            92,
            tableY + 4,
        );

        tableY += 6;
        /* pdf.setDrawColor(0, 0, 0);
        pdf.setLineWidth(0.3);
        pdf.line(12, tableY, 198, tableY); */

        // Cédula
        pdf.rect(20, tableY, 70, 6, "D");
        pdf.rect(90, tableY, 105, 6, "D");
        pdf.setFont("helvetica", "bold");
        pdf.text("Cédula de Identidad:", 21, tableY + 4);
        pdf.setFont("helvetica", "normal");
        pdf.text(`${datos.beneficiario.ci} CBBA`, 92, tableY + 4);

        tableY += 6;
        /* pdf.line(12, tableY, 198, tableY); */

        // Condición
        pdf.rect(20, tableY, 70, 6, "D");
        pdf.rect(90, tableY, 105, 6, "D");
        pdf.setFont("helvetica", "bold");
        pdf.text("Condición:", 21, tableY + 4);
        pdf.setFont("helvetica", "normal");
        pdf.text("Persona con Discapacidad", 92, tableY + 4);

        tableY += 6;
        /* pdf.line(12, tableY, 198, tableY); */

        // Tipo de Discapacidad
        pdf.rect(20, tableY, 70, 6, "D");
        pdf.rect(90, tableY, 105, 6, "D");
        pdf.setFont("helvetica", "bold");
        pdf.text("Tipo de Discapacidad:", 21, tableY + 4);
        pdf.setFont("helvetica", "normal");
        pdf.text(datos.beneficiario.discapacidad, 92, tableY + 4);

        tableY += 6;
        /* pdf.line(12, tableY, 198, tableY); */

        // Tutor Legal
        pdf.rect(20, tableY, 70, 6, "D");
        pdf.rect(90, tableY, 105, 6, "D");
        pdf.setFont("helvetica", "bold");
        pdf.text("Tutor Legal:", 21, tableY + 4);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            `${datos.tutor.apellido.toUpperCase()} ${datos.tutor.nombre.toUpperCase()}` ||
                "La beneficiaria actúa por cuenta propia",
            92,
            tableY + 4,
        );

        tableY += 6;
        /* pdf.line(12, tableY, 198, tableY); */

        // Monto Otorgado
        pdf.rect(20, tableY, 70, 6, "D");
        pdf.rect(90, tableY, 105, 6, "D");
        pdf.setFont("helvetica", "bold");
        pdf.text("Monto Otorgado:", 21, tableY + 4);
        pdf.text(`Bs. ${datos.monto}`, 92, tableY + 4);

        tableY += 6;
        /* pdf.line(12, tableY, 198, tableY); */

        // Monto Literal

        pdf.setFillColor(255, 255, 255);
        const radius = 4;

        // Primer rect - solo esquina inferior izquierda redondeada
        pdf.lines(
            [
                [70, 0], // línea superior
                [0, 6], // línea derecha
                [-68 + radius, 0], // línea inferior (menos el radio)
                [-7, 0.5, -5.7, -3, -6, -radius], // curva inferior izquierda
                [0, -6 + radius], // línea izquierda
            ],
            20,
            tableY,
            [1, 1],
            "D",
        );

        // Segundo rect - solo esquina inferior derecha redondeada
        pdf.lines(
            [
                [105, 0], // línea superior
                [0, 1], // línea derecha (menos el radio)
                [0, 4, 0, 5.3, -7, 5], // curva inferior derecha
                [-102 + radius, 0], // línea inferior
                [0, -6], // línea izquierda
            ],
            90,
            tableY,
            [1, 1],
            "D",
        );
        pdf.setFont("helvetica", "bold");

        pdf.text("Monto Literal:", 21, tableY + 4);

        pdf.setFont("helvetica", "normal");
        pdf.text(datos.montoTexto.toUpperCase(), 92, tableY + 4);

        tableY += 6;
        /* pdf.line(12, tableY, 198, tableY); */

        // ========== BASE LEGAL ==========
        tableY += 4.5;
        pdf.setFontSize(8.8);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        pdf.text("Base Legal:", 21, tableY);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            "Ley N° 977 de Inserción Laboral y Ayuda Económica para Personas con Discapacidad, reglamentos y normativa",
            39,
            tableY,
        );
        tableY += 4;
        pdf.text("municipal vigente.", 21, tableY);

        // ========== FIRMAS ==========
        let signY = tableY + 21;

        pdf.setFillColor(colorGris[0], colorGris[1], colorGris[2]);
        pdf.setDrawColor(colorBorde[0], colorBorde[1], colorBorde[2]);
        pdf.setLineWidth(0.3);

        // Usando roundedRect(x, y, width, height, radiusX, radiusY, style)
        pdf.roundedRect(27, tableY + 2.5, 80, 16, 2, 2, "FD");
        pdf.roundedRect(109, tableY + 2.5, 80, 16, 2, 2, "FD");

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.text("Firma o Huella de Beneficiario(a) o Tutor", 37, signY + 2);
        pdf.text("Firma y Sello del Responsable de Pago", 120, signY + 2);

        if (firmaDigital) {
            try {
                pdf.addImage(
                    `/${firmaDigital}`,
                    "PNG",
                    125,
                    signY - 23,
                    53,
                    23,
                );
            } catch (e) {
                console.warn("Firma digital no encontrada", e);
            }
        }

        // Calcular el punto central del texto "Firma y Sello..."
        const centroFirma = 110 + 40;

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");
        pdf.text(
            `${capitalizar(datos.usuarioPagador)}`,
            centroFirma,
            signY + 6,
            { align: "center" },
        );

        pdf.setFontSize(7);
        pdf.setFont("helvetica", "normal");
        pdf.text(`${datos.cargo.toUpperCase()}`, centroFirma, signY + 9, {
            align: "center",
        });

        // ========== BORDE PRINCIPAL ==========
        pdf.setDrawColor("#13326A");
        pdf.setLineWidth(0.4);
        pdf.roundedRect(19.9, 8.9 + yOffset, 175.3, 85.3, 3, 3, "D");
    };

    const generarComprobante = async (
        datosRecibo,
        item,
        usuarioPagador,
        firmaDigital,
    ) => {
        try {
            // Obtener la firma del usuario (prioridad: parámetro, luego del objeto usuario)
            const firma = firmaDigital || usuarioPagador.digital_signature;

            const pdf = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "letter",
            });

            // Datos para el comprobante
            const datos = {
                numeroBoleta: item.pago.numero_boleta,
                gestion: item.gestion.gestion,
                mes: getMesNombre(item.mes.mes),
                monto: item.mes.monto,
                montoTexto: "Doscientos cincuenta 00/100 Bolivianos",
                fechaPago: new Date(item.pago.fecha_pago).toLocaleDateString(
                    "es-BO",
                    {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                    },
                ),
                fechaActual: new Date().toLocaleDateString("es-BO", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                }),
                usuarioPagador: `${usuarioPagador.nombre} ${usuarioPagador.apellido}`,
                cargo: usuarioPagador.cargo,
                beneficiario: {
                    uuid: datosRecibo.id_persona,
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
                    condicion: "La beneficiaria actúa por cuenta propia",
                },
            };

            // Generar QR
            const qrCodeImage = await generateQRCode(datos);

            // Generar PRIMER comprobante
            await dibujarComprobante(pdf, datos, qrCodeImage, firma, 0);

            // LÍNEA DE CORTE
            pdf.setLineDash([2, 1.5]);
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.1);
            pdf.line(1.5, 140, 214.5, 140);
            pdf.setLineDash([]);

            // Generar SEGUNDO comprobante
            await dibujarComprobante(pdf, datos, qrCodeImage, firma, 140);

            // ========== SOLO VISTA PREVIA ==========
            const pdfBlob = pdf.output("blob");
            const blobUrl = URL.createObjectURL(pdfBlob);

            // Abrir vista previa en nueva pestaña
            window.open(blobUrl, "_blank");

            // Limpiar el Blob URL después de 10 segundos
            setTimeout(() => {
                URL.revokeObjectURL(blobUrl);
            }, 10000);
        } catch (error) {
            console.error("Error al generar PDF:", error);
            alert("Error al generar el PDF: " + error.message);
        }
    };
    return { generarComprobante };
};
