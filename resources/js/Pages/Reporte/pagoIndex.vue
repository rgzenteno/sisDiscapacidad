<script setup>
import { ref, onMounted } from 'vue'
import html2canvas from 'html2canvas'
import html2pdf from 'html2pdf.js'
import { jsPDF } from 'jspdf'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import QRCode from 'qrcode'

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    gestion: Number,
    mes: Number,
    monto: Number,
    pago: String,
    numeroBoleta: String,
    userSignature: String,
})

const qrCode = ref('')

const formData = ref({
    fecha: new Date().toISOString().split('T')[0],
    montoTexto: 'Doscientos cincuenta 00/100',
})

const getMesNombre = () => {
    const mesesAbrev = {
        'ENE': 'ENERO',
        'FEB': 'FEBRERO',
        'MAR': 'MARZO',
        'ABR': 'ABRIL',
        'MAY': 'MAYO',
        'JUN': 'JUNIO',
        'JUL': 'JULIO',
        'AGO': 'AGOSTO',
        'SEP': 'SEPTIEMBRE',
        'OCT': 'OCTUBRE',
        'NOV': 'NOVIEMBRE',
        'DIC': 'DICIEMBRE'
    }
    const mesAbrev = props.numeroBoleta?.substring(0, 3)
    return mesesAbrev[mesAbrev] || 'MES'
}

const generateQRCode = async () => {
    const datosQR = `Boleta: ${props.numeroBoleta}
Nombre: ${props.data.nombre_persona} ${props.data.apellido_persona}
CI: ${props.data.ci_persona}
Tutor: ${props.data.tutor.nombre_tutor} ${props.data.tutor.apellido_tutor}
CI Tutor: ${props.data.tutor.ci_tutor}
Distrito: ${props.data.distrito}
Discapacidad: ${props.data.carnet.discapacidad}
Monto: ${props.monto}
Fecha de Pago: ${props.pago}
Gestión: ${props.gestion}`

    try {
        qrCode.value = await QRCode.toDataURL(datosQR, {
            width: 250,
            margin: 1,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        })
    } catch (error) {
        console.error('Error generando QR:', error)
    }
}

const generatePDF = async () => {
    const element = document.getElementById('comprobante-container')

    if (!element) {
        alert('No se encontró el elemento del comprobante')
        return
    }

    try {
        // Esperar un momento para asegurar que todo esté renderizado
        await new Promise(resolve => setTimeout(resolve, 500))

        const canvas = await html2canvas(element, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
            backgroundColor: '#ffffff'
        })

        if (!canvas || canvas.width === 0 || canvas.height === 0) {
            throw new Error('Canvas inválido')
        }

        const imgData = canvas.toDataURL('image/png', 1.0)

        const pdf = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'letter'
        })

        const imgWidth = 215.9
        const imgHeight = (canvas.height * imgWidth) / canvas.width

        pdf.addImage(imgData, 'PNG', 0, 5, imgWidth, imgHeight)

        const pdfBlob = pdf.output('blob')
        const blobUrl = URL.createObjectURL(pdfBlob)
        window.open(blobUrl, '_blank')

        setTimeout(() => URL.revokeObjectURL(blobUrl), 300000)

    } catch (error) {
        console.error('Error al generar el PDF:', error)
        alert('Error al generar el PDF: ' + error.message)
    }
}

const createReporte = () => {
    generatePDF()
}

defineExpose({
    createReporte
})

onMounted(() => {
    generateQRCode()
})
</script>

<template>
    <AuthenticatedLayout>
        <div class="print-container">
            <div id="comprobante-container" class="container-carta">
                <!-- PRIMER COMPROBANTE -->
                <div class="pdf-content">
                    <!-- Header Moderno -->
                    <div class="header-modern">
                        <div class="logo-container">
                            <img src="/images/sacaba.png" alt="Logo Sacaba" class="logo">
                            <div class="umadis-badge">UMADIS</div>
                        </div>
                        <div class="title-container">
                            <h1 class="main-title">COMPROBANTE DE PAGO</h1>
                            <p class="subtitle">Ayuda Económica para Personas con Discapacidad</p>
                            <div class="gestion-mes-container">
                                <div class="gestion-badge">
                                    <span class="badge-label-small">GESTIÓN: {{ props.gestion }}</span>
                                    <!-- <span class="badge-number"></span> -->
                                </div>
                                <div class="mes-badge">
                                    <span class="badge-label-small">MES: {{ getMesNombre() }}</span>
                                    <!-- <span class="badge-number"></span> -->
                                </div>
                            </div>
                            <!--  <div class="boleta-badge">
                            <span class="badge-label-small">N° BOLETA:</span>
                            <span class="badge-number">{{ props.numeroBoleta }}</span>
                        </div> -->
                        </div>
                        <div class="qr-container">
                            <div class="qr-wrapper"> <img :src="qrCode" alt="QR Code" class="qr-code"> </div>
                        </div>
                    </div>

                    <!-- Info Cards -->
                    <div class="info-grid">
                        <div class="info-card"> <span class="card-label">Usuario Pagador</span> <span
                                class="card-value">{{ $page.props.auth.user.nombre }} {{ $page.props.auth.user.apellido
                                }}</span> </div>
                        <div class="info-card"> <span class="card-label">N° BOLETA</span> <span class="card-value">{{
                                props.numeroBoleta }}</span> </div>
                        <div class="info-card highlighted"> <span class="card-label">Monto Otorgado</span> <span
                                class="card-value amount">{{ props.monto }} Bs.</span> </div>
                    </div>

                    <!-- Datos Principales -->
                    <div class="main-grid">
                        <!-- Beneficiario -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">Datos del Beneficiario</h3>
                            </div>
                            <div class="section-body">
                                <div class="field-row"> <span class="field-label">Nombre Completo: </span> <span
                                        class="field-value">{{ props.data.apellido_persona }} {{
                                        props.data.nombre_persona }}</span> </div>
                                <div class="field-row"> <span class="field-label">Cédula de Identidad: </span> <span
                                        class="field-value">{{ props.data.ci_persona }}</span> </div>
                                <div class="field-row"> <span class="field-label">Tipo de Discapacidad: </span> <span
                                        class="field-value ">{{ props.data.carnet.discapacidad }}</span> </div>
                                <div class="field-row"> <span class="field-label">Distrito: </span> <span
                                        class="field-value">{{ props.data.distrito }}</span> </div>
                                <div class="field-row"> <span class="field-label"></span> <span
                                        class="field-value"></span> </div>
                            </div>
                        </div>

                        <!-- Tutor -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">Datos del Tutor</h3>
                            </div>
                            <div class="section-body">
                                <div class="field-row"> <span class="field-label">Nombre Completo: </span> <span
                                        class="field-value">{{ props.data.tutor.apellido_tutor }} {{
                                        props.data.tutor.nombre_tutor }}</span> </div>
                                <div class="field-row"> <span class="field-label">Cédula de Identidad: </span> <span
                                        class="field-value">{{ props.data.tutor.ci_tutor }}</span> </div>
                                <div class="field-row"> <span class="field-label"></span> <span
                                        class="field-value"></span> </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles de Pago -->
                    <div class="payment-card">
                        <div class="payment-field"> <span class="payment-label">Monto en Literal:</span> <span
                                class="payment-value">{{ formData.montoTexto }} BOLIVIANOS</span> </div>
                        <div class="payment-field"> <span class="payment-label">Fecha de Pago:</span> <span
                                class="payment-value">{{ props.pago }}</span> </div>
                        <div class="payment-field"> <span class="payment-label"></span> <span
                                class="payment-value"></span> </div>
                    </div>

                    <!-- Firmas -->
                    <div class="signature-section">
                        <div class="signature-box">
                            <div class="signature-area"></div>
                            <p class="signature-text">Firma o Huella del Beneficiario o Tutor</p>
                        </div>
                        <div class="signature-box">
                            <div class="signature-area">
                                <img v-if="$page.props.auth.user.digital_signature"
                                    :src="`/${$page.props.auth.user.digital_signature}`" alt="Firma del Responsable"
                                    class="signature-image" />
                            </div>
                            <p class="signature-text">Firma y Sello del Responsable de Pago</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer-modern">
                        <div class="footer-line"></div>
                        <p class="footer-text">Ley N° 977 de Inserción Laboral y Ayuda Económica para Personas con
                            Discapacidad</p>
                    </div>
                </div>

                <!-- LÍNEA DIVISORIA CON TIJERAS -->
                <div class="linea-corte">
                    <span>✂</span>
                </div>

                <!-- SEGUNDO COMPROBANTE -->
                <div class="pdf-content">
                    <!-- Header Moderno -->
                    <div class="header-modern">
                        <div class="logo-container">
                            <img src="/images/sacaba.png" alt="Logo Sacaba" class="logo">
                            <div class="umadis-badge">UMADIS</div>
                        </div>
                        <div class="title-container">
                            <h1 class="main-title">COMPROBANTE DE PAGO</h1>
                            <p class="subtitle">Ayuda Económica para Personas con Discapacidad</p>
                            <div class="gestion-mes-container">
                                <div class="gestion-badge">
                                    <span class="badge-label-small">GESTIÓN: <span class="badge-number">{{ props.gestion }}</span></span>
                                </div>
                                <div class="mes-badge">
                                    <span class="badge-label-small">MES: <span class="badge-number">{{ getMesNombre() }}</span></span>
                                </div>
                            </div>
                            <!-- <div class="boleta-badge">
                            <span class="badge-label-small"></span>
                            <span class="badge-number">{{ props.numeroBoleta }}</span>
                        </div> -->
                        </div>
                        <div class="qr-container">
                            <div class="qr-wrapper"> <img :src="qrCode" alt="QR Code" class="qr-code"> </div>
                        </div>
                    </div>

                    <!-- Info Cards -->
                    <div class="info-grid">
                        <div class="info-card"> <span class="card-label">Usuario Pagador</span> <span
                                class="card-value">{{ $page.props.auth.user.nombre }} {{ $page.props.auth.user.apellido
                                }}</span> </div>
                        <div class="info-card"> <span class="card-label">N° BOLETA</span> <span class="card-value">{{
                                props.numeroBoleta }}</span> </div>
                        <div class="info-card highlighted"> <span class="card-label">Monto Otorgado</span> <span
                                class="card-value amount">{{ props.monto }} Bs.</span> </div>
                    </div>

                    <!-- Datos Principales -->
                    <div class="main-grid">
                        <!-- Beneficiario -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">Datos del Beneficiario</h3>
                            </div>
                            <div class="section-body">
                                <div class="field-row"> <span class="field-label">Nombre Completo: </span> <span
                                        class="field-value">{{ props.data.apellido_persona }} {{
                                        props.data.nombre_persona }}</span> </div>
                                <div class="field-row"> <span class="field-label">Cédula de Identidad: </span> <span
                                        class="field-value">{{ props.data.ci_persona }}</span> </div>
                                <div class="field-row"> <span class="field-label">Tipo de Discapacidad: </span> <span
                                        class="field-value">{{ props.data.carnet.discapacidad }}</span> </div>
                                <div class="field-row"> <span class="field-label">Distrito: </span> <span
                                        class="field-value">{{ props.data.distrito }}</span> </div>
                                <div class="field-row"> <span class="field-label"></span> <span
                                        class="field-value"></span> </div>
                            </div>
                        </div>

                        <!-- Tutor -->
                        <div class="section-card">
                            <div class="section-header">
                                <h3 class="section-title">Datos del Tutor</h3>
                            </div>
                            <div class="section-body">
                                <div class="field-row"> <span class="field-label">Nombre Completo: </span> <span
                                        class="field-value">{{ props.data.tutor.apellido_tutor }} {{
                                        props.data.tutor.nombre_tutor }}</span> </div>
                                <div class="field-row"> <span class="field-label">Cédula de Identidad: </span> <span
                                        class="field-value">{{ props.data.tutor.ci_tutor }}</span> </div>
                                <div class="field-row"> <span class="field-label"></span> <span
                                        class="field-value"></span> </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles de Pago -->
                    <div class="payment-card">
                        <div class="payment-field"> <span class="payment-label">Monto en Literal:</span> <span
                                class="payment-value">{{ formData.montoTexto }} BOLIVIANOS</span> </div>
                        <div class="payment-field"> <span class="payment-label">Fecha de Pago:</span> <span
                                class="payment-value">{{ props.pago }}</span> </div>
                        <div class="payment-field"> <span class="payment-label"></span> <span
                                class="payment-value"></span> </div>
                    </div>

                    <!-- Firmas -->
                    <div class="signature-section">
                        <div class="signature-box">
                            <div class="signature-area"></div>
                            <p class="signature-text">Firma o Huella del Beneficiario o Tutor</p>
                        </div>
                        <div class="signature-box">
                            <div class="signature-area">
                                <img v-if="$page.props.auth.user.digital_signature"
                                    :src="`/${$page.props.auth.user.digital_signature}`" alt="Firma del Responsable"
                                    class="signature-image" />
                            </div>
                            <p class="signature-text">Firma y Sello del Responsable de Pago</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="footer-modern">
                        <div class="footer-line"></div>
                        <p class="footer-text">Ley N° 977 de Inserción Laboral y Ayuda Económica para Personas con
                            Discapacidad</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Contenedor principal para hoja carta */
.container-carta {
    width: 190mm;
    height: 270mm;
    background: rgb(255, 255, 255);
    font-family: 'Segoe UI', -apple-system, sans-serif;
    padding: 0;
    box-sizing: border-box;
    position: relative;
    margin: 0 auto;
    overflow: hidden;
}

/* Cada comprobante ocupa la mitad de la hoja */
.pdf-content {
    width: 100%;
    height: 133mm;
    background: rgb(255, 255, 255);
    padding: 4mm 8mm;
    box-sizing: border-box;
    position: relative;
    overflow: hidden;
}

/* Header Moderno */
.header-modern {
    display: grid;
    grid-template-columns: 100px 1fr 100px;
    gap: 12px;
    align-items: center;
    margin-bottom: 2px;
}

.logo-container {
    background: #e9eaea;
    border-radius: 12px;
    /* padding: 0 8px; */
    padding-top: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    /* display: flex;
    flex-direction: column; */
    /* align-items: center; */
    justify-content: center;
    width: 140px;
    height: 100px;/*
    position: relative; */
}

.logo {
    width: 100%;
    height: 50px;/*
    object-fit: contain; */
}

.umadis-badge {
    color: rgb(19, 50, 106);
    background-color: red;
    font-size: 20px;
    padding-top: -20px;
    font-weight: 800;/*
    padding: 0px 12px; *//*
    border-radius: 12px; */
    text-shadow: -5px 5px 4px rgba(107, 107, 107, 0.79);
}

.title-container {
    text-align: center;
}

.main-title {
    font-size: 20px;
    font-weight: 800;
    margin: 0;
    letter-spacing: 1.2px;
    color: #1a1a1a;
}

.subtitle {
    font-size: 14px;
    margin: 0px 0 6px 0;
    color: #4a4a4a;
    font-weight: 500;
}

.gestion-mes-container {
    display: flex;
    justify-content: center;
    gap: 8;
}

.gestion-badge,
.mes-badge {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    background: #e9eaea;
    padding: 0px 12px;
    padding-bottom: 13px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
}

.boleta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0f0f0;
    padding: 0px 14px;
    border-radius: 20px;
    position: relative;
    z-index: 0;
}

.badge-label-small {
    font-size: 14px;
    color: rgb(0, 0, 0);
    font-weight: 700;
    letter-spacing: 0.8px;
    line-height: 1;
}

.boleta-badge .badge-label-small {
    font-size: 10px;
    color: #1e40af;
    /* font-weight: 700; */
}

.badge-number {
    font-size: 12px;
    font-weight: 800;
    color: white;
    letter-spacing: 0.5px;
    line-height: 1;
}

.boleta-badge .badge-number {
    font-size: 13px;
    color: #1e40af;
}

.qr-container {
    display: flex;
    justify-content: center;
}

.qr-wrapper {
    background: #e9eaea;
    border-radius: 12px;
    padding: 6px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.qr-code {
    width: 100px;
    height: 100px;
    display: block;
    border-radius: 4px;
}

.qr-label {
    font-size: 10px;
    color: #4a4a4a;
    font-weight: 600;
    display: block;
    margin-top: -2px;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    margin-bottom: 8px;
}

.info-card {
    background: #e9eaea;
    border-radius: 10px;
    padding: 0px 10px;
    padding-bottom: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    margin-top: -1px;
}

.info-card.highlighted {
    background: rgb(19, 50, 106);
}

.info-card.highlighted .card-label {
    color: #fff;
}

.info-card.highlighted .card-value {
    color: #fff;
}

.card-label {
    font-size: 11px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
}

.card-value {
    font-size: 12px;
    color: #1a1a1a;
    font-weight: 800;
    text-transform: capitalize;
    line-height: 1.2;
    text-align: center;
    margin-top: -3px;
}

.card-value.amount {
    font-size: 14px;
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 6px;
}

.section-card {
    background: white;
    border-radius: 12px;
    border: 1.5px solid #e0e0e0;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.section-header {
    background: rgb(19, 50, 106);
    padding: 10px 10px;
}

.section-title {
    font-size: 12px;
    font-weight: 700;
    color: white;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: -10px;
}

.section-body {
    padding: 2px 10px;
    padding-bottom: 0px;
}

.field-row {
    display: flex;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f0;
    min-height: 16px;
}

.field-row:last-child {
    border-bottom: none;
}

.field-label {
    font-size: 11px;
    color: #3c3b3b;
    font-weight: 600;
    line-height: 1.3;
}

.field-value {
    padding-left: 3px;
    font-size: 11px;
    color: #1a1a1a;
    font-weight: 700;
    text-transform: capitalize;
    text-align: right;
    line-height: 1.3;
}

.field-value.highlight {
    background: rgb(19, 50, 106);
    color: white;
    padding: 0px 8px;
    padding-bottom: 8px;
    border-radius: 10px;
    font-size: 10px;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Payment Card */
.payment-card {
    background: #e9eaea;
    border-radius: 10px;
    padding: 2px 12px;
    padding-bottom: 0;
    margin-bottom: 15px;
    border: 1.5px solid #e0e0e0;
}

.payment-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 0;
    border-bottom: 1px dashed #d0d0d0;
    min-height: 14px;
}

.payment-field:last-child {
    border-bottom: none;
}

.payment-label {
    font-size: 11px;
    color: #4a4a4a;
    font-weight: 600;
    text-transform: uppercase;
    line-height: 1.3;
}

.payment-value {
    font-size: 11px;
    color: #1a1a1a;
    font-weight: 700;
    text-transform: uppercase;
    line-height: 1.3;
}

.linea-corte {
    height: 4mm;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0;
}

.linea-corte::before {
    content: '';
    position: absolute;
    left: 8mm;
    right: 8mm;
    top: 50%;
    transform: translateY(-50%);
    border-top: 1.5px dashed #000;
}

.linea-corte span {
    position: relative;
    z-index: 1;
    background: white;
    padding: 0 8mm;
    font-size: 18px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Signature Section */
.signature-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.signature-box {
    text-align: center;
}

.signature-area {
    height: 45px;
    background: #5959592b;
    border-radius: 8px;
    margin-bottom: 2px;
}

.signature-line {
    height: 2px;
    background: #1a1a1a;
    margin-bottom: 3px;
}

.signature-text {
    font-size: 11px;
    color: #4a4a4a;
    font-weight: 700;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.signature-image {
    max-width: 100%;
    height: auto;
    max-height: 90px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
    position: relative;
    top: -20px;
}

/* Footer */
.footer-modern {
    margin-top: 1px;
}

.footer-line {
    height: 1px;
    background: #1a1a1a;
    margin-bottom: 3px;
}

.footer-text {
    font-size: 10px;
    color: #666;
    text-align: center;
    margin: 0;
    font-weight: 600;
}

@media print {
    .container-carta {
        box-shadow: none;
    }

    .divider-line {
        page-break-inside: avoid;
    }
}
</style>
