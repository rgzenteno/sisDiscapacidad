<script setup>
import { onMounted, computed } from 'vue'
import html2pdf from 'html2pdf.js'
import html2canvas from 'html2canvas'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    datos: {
        type: [Array, Object],
        default: () => []
    },
    gestion: Number,
    mes: Number,
    tipo: Boolean,
    tipoR: String,
    monto: Number
})

// Método para obtener el nombre del mes
function getMonthNameFromNumber(monthNumber) {
    const months = [
        'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
        'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
    ];

    // Convertir a número entero
    const index = parseInt(monthNumber, 10) - 1;

    // Validar que esté en rango 0–11
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

const REGISTROS_POR_PAGINA = 68

const fecha = new Date()
    .toLocaleString('sv-SE', { timeZone: 'America/La_Paz', hour12: false })
    .replace(/ /, '_')
    .replace(/\..+/, '')

// Calcular totales
const totales = computed(() => {
    const datosArray = Array.isArray(props.datos) ? props.datos : []

    const activos = datosArray.filter(item => item.estado_actual ?.estado === 'activo')
    const bajas = datosArray.filter(item => item.estado_actual ?.estado !== 'activo')

    return {
        total_personas: datosArray.length,
        monto_total: datosArray.reduce((sum, item) => sum + (parseFloat(item.monto) || 0), 0).toFixed(2),
        cantidad_activos: activos.length,
        monto_activos: activos.reduce((sum, item) => sum + (parseFloat(item.monto) || 0), 0).toFixed(2),
        cantidad_bajas: bajas.length,
        monto_bajas: bajas.reduce((sum, item) => sum + (parseFloat(item.monto) || 0), 0).toFixed(2)
    }
})

// Dividir datos en páginas
const datosPaginados = computed(() => {
    const datosArray = Array.isArray(props.datos) ? props.datos : []
    const paginas = []

    for (let i = 0; i < datosArray.length; i += REGISTROS_POR_PAGINA) {
        paginas.push({
            datos: datosArray.slice(i, i + REGISTROS_POR_PAGINA),
            indiceInicio: i
        })
    }

    return paginas
})

const generatePDF = async () => {
    const container = document.querySelector('.print-container')
    const header = document.getElementById('pdf-header')

    if (!container || !header) return

    await waitForImages(header)

    const headerClone = cloneHeader(header)
    document.body.appendChild(headerClone)

    const headerCanvas = await html2canvas(headerClone, {
        scale: 3,
        useCORS: true,
        backgroundColor: '#ffffff'
    })

    const headerImg = headerCanvas.toDataURL('image/png')
    document.body.removeChild(headerClone)

    const options = {
        margin: [17, 10, 6, 10],
        filename: `Reporte_${props.tipoR}-${fecha}.pdf`,
        html2canvas: {
            scale: 3,
            useCORS: true
        },
        jsPDF: {
            unit: 'mm',
            format: 'legal',
            orientation: 'portrait'
        },
        pagebreak: {
            mode: 'css', // Cambia de 'avoid-all' a 'css'
            before: '.page-break'
        }
    }

    await html2pdf()
        .set(options)
        .from(container)
        .toPdf()
        .get('pdf')
        .then(pdf => {
            const totalPages = pdf.internal.getNumberOfPages()
            const pageWidth = pdf.internal.pageSize.getWidth()

            for (let i = 1; i <= totalPages; i++) {
                pdf.setPage(i)
                pdf.addImage(
                    headerImg, 'PNG', 10, 1,
                    pageWidth - 20, 15
                )
            }
        })
        .save()
}

const estadoClase = (estado) => {
    switch (estado) {
        case 'activo':
            return 'bg-activo'
        case 'baja_temporal':
            return 'bg-baja-temporal'
        case 'baja_definitiva':
            return 'bg-baja-definitiva'
        default:
            return ''
    }
}

const waitForImages = async (element) => {
    const images = element.querySelectorAll('img')
    await Promise.all(
        Array.from(images).map(img => {
            if (img.complete) return Promise.resolve()
            return new Promise(resolve => {
                img.onload = img.onerror = resolve
            })
        })
    )
}

const cloneHeader = (header) => {
    const clone = header.cloneNode(true)
    clone.style.position = 'absolute'
    clone.style.top = '-9999px'
    clone.style.left = '-9999px'
    return clone
}

onMounted(() => {
    generatePDF()
})
</script>
<template>
<AuthenticatedLayout>
    <div class="print-container">

        <!-- HEADER (SE CONVIERTE EN IMAGEN) -->
        <div id="pdf-header" class="pdf-header">
            <table class="header-table">
                <tr>
                    <td class="header-logo">
                        <img src="/images/sacaba.png" crossorigin="anonymous" class="logo-left">
                    </td>

                    <td class="header-title">
                        PLANILLA DE PAGO DE BONO MENSUAL A FAVOR DE LAS PERSONAS CON DISCAPACIDAD
                        <br>
                        GRAVE Y MUY GRAVE MES DE {{ getMonthNameFromNumber(props.mes) }} GESTIÓN {{ props.gestion }}
                    </td>

                    <td class="header-logo">
                        <img src="/images/sigamos.png" crossorigin="anonymous" class="logo-right">
                    </td>
                </tr>
            </table>
        </div>

        <!-- CONTENIDO PAGINADO -->
        <template v-for="(pagina, indexPagina) in datosPaginados" :key="indexPagina">

            <!-- Salto de página ENTRE tablas -->
            <div v-if="indexPagina > 0" style="page-break-before: always; height: 17px;"></div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-n">N°</th>
                        <th class="w-ci">C.I.</th>
                        <th class="w-nombre">APELLIDOS Y NOMBRES P.C.D.</th>
                        <th class="w-grado">GRADO DE DISCAPACIDAD</th>
                        <th class="w-monto">MONTO A PAGAR (BS.)</th>
                        <th class="w-obs">OBSERVACIONES</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(item, index) in pagina.datos" :key="item.id_personas" :class="estadoClase(item.estado_actual.estado)">
                        <td class="td-center">
                            {{ pagina.indiceInicio + index + 1 }}
                        </td>
                        <td class="td-center">
                            {{ item.ci }}
                        </td>
                        <td class="td-left">
                            {{ item.apellido }} {{ item.nombre }}
                        </td>
                        <td class="td-center">
                            GRAVE MUY GRAVE
                        </td>
                        <td class="td-center">
                            {{ item.monto }}
                        </td>
                        <td class="td-center">
                            {{ item.estado_actual?.estado === 'activo' ? '' : (item.estado_actual?.observaciones || '') }}
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="indexPagina === datosPaginados.length - 1">
                    <tr class="total-general">
                        <td colspan="4" class="td-left">TOTAL PLANILLA GENERAL</td>
                        <td class="td-center">{{ totales.monto_total }}</td>
                        <td class="td-center"></td>
                    </tr>

                    <tr class="total-fallecidos">
                        <td colspan="4" class="td-left">
                            {{ totales.cantidad_bajas }} PERSONAS CON DISCAPACIDAD GRAVE Y MUY GRAVE FALLECIERON HASTA LA FECHA NO COBRARAN DEL MES DE {{ getMonthNameFromNumber(props.mes).toUpperCase() }} DEL AÑO {{ props.gestion }} ({{ totales.cantidad_bajas }}*250) = {{ (totales.cantidad_bajas * 250).toFixed(2) }}
                        </td>
                        <td class="td-center">{{ totales.monto_bajas }}</td>
                        <td class="td-center"></td>
                    </tr>

                    <tr class="total-funcionarios">
                        <td colspan="4" class="td-left">
                            4 FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) LOS CUALES NO ACCEDEN AL BONO DEL MES DE {{ getMonthNameFromNumber(props.mes).toUpperCase() }} DEL AÑO {{ props.gestion }} (4*250) = 1,000.00
                        </td>
                        <td class="td-center">1,000.00</td>
                        <td class="td-center"></td>
                    </tr>

                    <tr class="total-pagar">
                        <td colspan="4" class="td-left">
                            TOTAL PERSONAS CON DISCAPACIDAD A PAGAR MES DE {{ getMonthNameFromNumber(props.mes).toUpperCase() }} {{ props.gestion }}
                        </td>
                        <td class="td-center">{{ (parseFloat(totales.monto_total) - parseFloat(totales.monto_bajas) - 1000).toFixed(2) }}</td>
                        <td class="td-center"></td>
                    </tr>
                </tfoot>
            </table>

        </template>

    </div>
</AuthenticatedLayout>
</template>

<style>
/* =========================
   HEADER
========================= */
.pdf-header {
    position: absolute;
    left: -9999px;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
}

.header-logo {
    width: 20%;
    text-align: center;
}

.logo-left {
    width: 30mm;
}

.logo-right {
    width: 20mm;
}

.header-title {
    width: 60%;
    text-align: center;
    font-size: 8px;
    font-weight: bold;
}

/* =========================
   TABLAS
========================= */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
    padding: 0;
    margin: 0;
}

.data-table {
    border: thin solid #000;
}

.data-table td {
    border: thin solid #000;
    padding: 3px;
    padding-bottom: 5px;
    padding-top: 0;
    text-transform: uppercase;
    vertical-align: middle;
}

.data-table thead th {
    background-color: #e6e6e6;
    border: thin solid #000;
    padding: 3px;
    padding-bottom: 5px;
    padding-top: 0;
    text-align: center;
    text-transform: uppercase;
}

.total-general td,
.total-fallecidos td,
.total-funcionarios td {
    font-weight: bold;
}

.total-pagar td {
    font-weight: bold;
    font-size: 9px;
}

/* =========================
   ANCHOS
========================= */
.w-n {
    width: 4%;
}

.w-ci {
    width: 8%;
}

.w-nombre {
    width: 20%;
}

.w-grado {
    width: 10%;
}

.w-monto {
    width: 8%;
}

.w-obs {
    width: 20%;
}

/* =========================
   ALINEACIÓN
========================= */
.td-center {
    text-align: center;
}

.td-left {
    text-align: left;
    padding-left: 8px;
}

.td-right {
    text-align: right;
}

/* =========================
   ESTADOS
========================= */
.bg-activo {
    font-weight: bold;
}

.bg-baja-temporal {
    background-color: #1e3a8a;
    color: #93c5fd;
}

.bg-baja-definitiva {
    background-color: #fde047;
    color: #dc2626;
    font-weight: bold;
}

/* =========================
   TOTALES
========================= */
.totales-table {
    margin-top: 10px;
    border: 1px solid #000;
    border-collapse: collapse;
}

.totales-table td {
    border: 1px solid #000;
    padding: 5px;
    font-size: 8px;
}

.td-left-total {
    text-align: left;
    padding-left: 8px;
    font-weight: bold;
}

.td-empty {
    background-color: transparent;
}

.total-monto {
    text-align: center;
    font-weight: bold;
}

.total-monto-final {
    text-align: center;
    font-weight: bold;
    font-size: 9px;
}

.total-general {
    background-color: #ffffff;
}

.total-fallecidos {
    background-color: #ffffff;
}

.total-funcionarios {
    background-color: #ffffff;
}

.total-pagar {
    background-color: #87ceeb;
}

/* =========================
   SALTO DE PÁGINA
========================= */
.page-break {
    page-break-before: always;
    display: block;
    height: 0;
}
</style>
