<script setup>
import { onMounted } from 'vue';
import { computed } from 'vue'
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
    monto: Number,
    gestion: String
});

// Función para obtener la fecha actual en formato legible
const getCurrentDate = () => {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    let dateStr = new Date().toLocaleDateString('es-ES', options);

    // Capitalizar la primera letra del mes
    dateStr = dateStr.replace(/\b\w/g, char => char.toUpperCase());

    return dateStr;
};

const formatCurrency = (amount) => {
    return `${new Intl.NumberFormat('es-BO', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)}`;
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
            orientation: 'landscape'
        }
    };
    /* portrait */
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

/* const getMonthNameFromDate = (dateString) => {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const year = dateString.substring(0, 4);
    const monthNumber = dateString.substring(5, 7);
    const monthName = months[parseInt(monthNumber) - 1] || 'Mes inválido';

    return `${monthName} - ${year}`;
}; */

function getMonthNameFromDate(monthNumber) {
    const months = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    // Convertir a número entero
    const index = parseInt(monthNumber, 10) - 1;

    // Validar que esté en rango 0–11
    if (index >= 0 && index < 12) {
        return months[index];
    }
    return 'Mes inválido';
}

/* const getMonthNameFrom = (dateString) => {
    return dateString.substring(0, 4);
}; */

// Función auxiliar para calcular totales para cualquier propiedad numérica
const calcularTotal = (propiedad) => {
    return props.datos.reduce((acumulador, item) => {
        // Asegurarse de que el valor es un número antes de sumar
        const valor = Number(item[propiedad]) || 0;
        return acumulador + valor;
    }, 0);
};

// Computed Properties para cada total
const totalGeneralPresupuesto = computed(() => calcularTotal('presupuesto_mes'));
const totalGeneralBeneficiarios = computed(() => calcularTotal('cantidad_habilitadas'));
const totalGeneralPagado = computed(() => calcularTotal('total_pagado_contexto'));
const totalGeneralPagos = computed(() => calcularTotal('cantidad_total_pagos'));
const totalGeneralNoPagados = computed(() => calcularTotal('cantidad_no_pagados'));
const totalSaldoDisponible = computed(() => {
    return props.datos.reduce((total, item) => {
        const saldoItem = (item.presupuesto_mes || 0) - (item.total_pagado_contexto || 0);
        return total + saldoItem;
    }, 0);
});
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
                    <div style="font-weight: bold; font-size: 25px; color: red; text-transform: uppercase; line-height: 1.2;">
                        <!-- Tamaño reducido y ajuste de línea -->
                        ARQUEO GENERAL DE TESORERÍA - GESTIÓN {{ datos[0].gestion }}
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
                            <span style="font-weight: bold; white-space: nowrap;">FECHA: </span>
                            <span style="margin-left: 3px;">{{ getCurrentDate() }}</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Main Content Table -->
        <table style="width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 0;">
            <thead>
                <tr style="background-color: #e6e6e6; font-weight: bold; text-transform: uppercase;">
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 2%;">N°</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 15%;">Detalle</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Solicitud de desembolsos de Recursos Humanos en Bs.</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Número de beneficiarios PCD.</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Monto total pagado a PCD. en Bs.</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Número de pagos a PCD beneficiadas</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 7%;">Cantidad de personas que no han cobrado el bono PCD.</th>
                    <th style="border: 1px solid #333; padding-bottom: 10px; text-align: center; width: 8%;">Total de saldos no cancelados</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in props.datos" :key="item.id_gestion">
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">{{ index + 1 }}</td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: left; vertical-align: middle; text-transform: uppercase;">
                        Solicitud Económica <span class="ms-2">{{ getMonthNameFromDate(item.mes) }} {{ item.gestion }}</span>
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: right; vertical-align: middle;">
                        {{ formatCurrency(item.presupuesto_mes) || 0 }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">
                        {{ item.cantidad_habilitadas }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: right; vertical-align: middle;">
                        {{ formatCurrency(item.total_pagado_contexto) }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">
                        {{ item.cantidad_total_pagos }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">
                        {{ item.cantidad_no_pagados }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: right; vertical-align: middle;">
                        {{ formatCurrency((item.presupuesto_mes || 0) - (item.total_pagado_contexto || 0)) }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <!-- Usamos colspan="2" para unir las primeras dos celdas (N° y Detalle) -->
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;" colspan="2">
                        TOTAL GENERAL
                    </td>

                    <!-- Ahora mostramos los totales calculados para cada columna -->
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: right; vertical-align: middle;">
                        {{ formatCurrency(totalGeneralPresupuesto) }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">
                        {{ totalGeneralBeneficiarios }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: right; vertical-align: middle;">
                        {{ formatCurrency(totalGeneralPagado) }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">
                        {{ totalGeneralPagos }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: center; vertical-align: middle;">
                        {{ totalGeneralNoPagados }}
                    </td>
                    <td style="border: 1px solid #ddd; font-size: 10px; padding-bottom: 12px; text-align: right; vertical-align: middle;">
                        {{ formatCurrency(totalSaldoDisponible) }}
                    </td>
                </tr>
            </tfoot>
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
