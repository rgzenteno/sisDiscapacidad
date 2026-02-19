<script setup>
import { computed } from 'vue'
import { onMounted } from 'vue';
import html2pdf from 'html2pdf.js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const createReporte = () => {
    generatePDF();
};

defineExpose({
    createReporte
});

const props = defineProps({
    datos: {
        type: [Object, Array],
        default: () => ({})
    },
    tipo: Boolean,
    tipoR: String,
    monto: Number
});

// Función para obtener la fecha actual en formato legible
const getCurrentDate = () => {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = new Date().toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return dateStr;
};

const getMonthNameFromDate = (dateString) => {
    const months = [
        'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
        'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
    ];

    const year = dateString.substring(0, 4);
    const monthNumber = dateString.substring(5, 7);
    const monthName = months[parseInt(monthNumber) - 1] || 'Mes inválido';

    return `${monthName} - ${year}`;
};

const fecha = new Date().toLocaleString('sv-SE', { timeZone: 'America/La_Paz', hour12: false }).replace(/ /, '_').replace(/\..+/, '');

// Función para generar el PDF
const generatePDF = async () => {
    const element = document.querySelector('.print-container');

    // Eliminar elementos que no deben aparecer en el PDF
    const originalStyles = document.querySelectorAll('style, link[rel="stylesheet"]');
    originalStyles.forEach(el => el.setAttribute('data-original-display', el.style.display));
    originalStyles.forEach(el => el.style.display = 'none');

    const options = {
        margin: [10, 5, 10, 5],
        filename: `Reporte_${props.tipoR}-${fecha}.pdf`,
        image: {
            type: 'jpeg',
            quality: 0.98
        },
        html2canvas: {
            scale: 2,
            letterRendering: true,
            useCORS: true,
            allowTaint: true,
            scrollX: 0,
            scrollY: 0,
            windowWidth: document.documentElement.offsetWidth
        },
        jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
        }
    };

    try {
        await html2pdf().set(options).from(element).toPdf().get('pdf').then((pdf) => {
            const totalPages = pdf.internal.getNumberOfPages();
            for (let i = 1; i <= totalPages; i++) {
                pdf.setPage(i);
                pdf.setFontSize(10);
                pdf.setTextColor(100);
            }
        }).save();
    } catch (error) {
        console.error('Error al generar el PDF:', error);
    } finally {
        // Restaurar estilos originales
        originalStyles.forEach(el => el.style.display = el.getAttribute('data-original-display'));
    }
};

const formatPhone = (phone) => {
    return phone ? phone.toString().replace(/(\d{3})(\d{3})(\d{2})/, '$1 $2 $3') : '';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-BO', options);
};

// Descargar el PDF automáticamente cuando el componente se monta
onMounted(() => {
    generatePDF();
});
</script>

<template>
<AuthenticatedLayout>
    <div class="print-container" style="font-family: Arial, sans-serif; width: 100%;">
        <!-- Header Compacto -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 0px;">
            <tr style="vertical-align: top;">
                <!-- Cambiado a vertical-align: top -->
                <!-- Logo izquierdo -->
                <td style="width: 20%; text-align: center; vertical-align: top; padding: 2px 5px;">
                    <!-- Reducido padding vertical -->
                    <img src="/images/sacaba.png" alt="Logo Sacaba" style="width: 40mm; height: auto;"> <!-- Tamaño reducido -->
                </td>

                <!-- Título central -->
                <td style="width: 60%; text-align: center; vertical-align: top; padding: 2px 5px;">
                    <!-- Reducido padding vertical -->
                    <div style="font-weight: bold; font-size: 14px; text-transform: uppercase; line-height: 1.2;">
                        <!-- Tamaño reducido y ajuste de línea -->
                        GOBIERNO AUTÓNOMO MUNICIPAL DE SACABA
                    </div>
                    <div style="font-size: 12px; margin-top: 3px; line-height: 1.2;">
                        <!-- Tamaño reducido -->
                        Sistema de Pago a Personas con Discapacidad
                    </div>
                </td>

                <!-- Información usuario - Ajustado para alinear arriba -->
                <td style="width: 20%; text-align: center; vertical-align: top; padding: 2px 5px;">
                    <!-- Reducido padding vertical -->
                    <div style="font-size: 10px; text-align: left; border-left: 1px solid #000; padding-left: 8px;">
                        <div style="margin-bottom: 3px; display: flex; align-items: flex-start;">
                            <!-- Alineación superior -->
                            <span style="font-weight: bold; white-space: nowrap;">USUARIO: </span>
                            <span style="text-transform: capitalize; margin-left: 3px;">
                                {{ $page.props.auth.user.nombre.toLowerCase() }} {{ $page.props.auth.user.apellido.toLowerCase() }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: flex-start;">
                            <!-- Alineación superior -->
                            <span style="font-weight: bold; text-transform: uppercase; white-space: nowrap;">FECHA: </span>
                            <span style="margin-left: 3px;">{{ getCurrentDate() }}</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Título del reporte separado -->
        <div style="text-align: center; font-weight: bold; font-size: 13px; 
                    border-top: 1px solid #000;
                    padding: 4px 0; margin-bottom: 10px;">
            <p v-if="tipoR === 'pago'">REPORTE DE PAGO {{ getMonthNameFromDate(props.datos[0].gestion) }}</p>
            <p v-else>REPORTE GENERAL DE DATOS</p>
        </div>

        <div v-if="props.monto" style="text-align: right; font-weight: bold; font-size: 13px; 
                    border-top: 1px solid #000;
                    padding: 4px 0; margin-bottom: 10px;">
            <span>Total Pagado: Bs. {{ props.monto }}</span>
        </div>

        <!-- Main Content Table -->
        <table style="width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 0;">
            <thead>
                <tr style="background-color: #e6e6e6; font-weight: bold; text-transform: uppercase;">
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 4%;">N°</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 15%;">Apellido y Nombre</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">C.I.</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Distrito</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 15%;">Tutor</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Teléfono</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%; white-space: nowrap;">Fecha Habilitado</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 12%;">Observaciones</th>
                    <th v-if="props.tipo === true" style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%; white-space: nowrap;">Fecha Pago</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in props.datos" :key="item.id_personas">
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: center; vertical-align: middle;">{{ index + 1 }}</td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: left; vertical-align: middle; text-transform: capitalize;">
                        {{ item.apellido_persona }} {{ item.nombre_persona }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: center; vertical-align: middle;">
                        {{ item.ci_persona }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: center; vertical-align: middle; text-transform: uppercase;">
                        {{ item.distrito }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: left; vertical-align: middle; text-transform: capitalize;">
                        {{ item.nombre_tutor }} {{ item.apellido_tutor }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: center; vertical-align: middle;">
                        {{ formatPhone(item.telefono) }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: center; vertical-align: middle;">
                        {{ formatDate(item.fecha_habilitado) }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: left; vertical-align: middle; font-style: italic;">
                        {{ item.observaciones_habilitado || 'Ninguna' }}
                    </td>
                    <td v-if="props.tipo === true" style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 5px; text-align: center; vertical-align: middle;">
                        {{ formatDate(item.fecha_pago) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</AuthenticatedLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    .print-container,
    .print-container * {
        visibility: visible;
    }

    .print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
