// ============ INICIO IMPORTS ============ //
import { ref, watch } from 'vue';
// ============ FIN IMPORTS ============ //

/**
 * Composable para el presupuesto del mes (PDF).
 *
 * El presupuesto YA NO se autocompleta al escribir el monto: se calcula
 * explícitamente contra el backend (gestion.mes.preview, solo lectura) y
 * recién ahí se llena form.presupuesto — así el usuario ve el desglose real
 * (activos, nuevos, bajas, depurados, duplicados, ausentes) antes de que el
 * número quede cargado, en vez de un total ciego que nadie revisa.
 *
 * El monto por persona ya no se tipea en este formulario: lo toma el
 * backend del parámetro "Monto - Persona" configurado en Configuración
 * (ver ConfiguracionController) — acá solo se muestra de forma informativa.
 *
 * @param {Object} form - Formulario de Inertia
 * @param {Object} props - Props del componente (propsConRegistros de Form.vue)
 * @returns {Object} - { mostrarPresupuestoAnual, previsualizacion, cargandoPreview, errorPreview, calcularPreview }
 */
export function usePresupuestoCalculation(form, props) {
    const mostrarPresupuestoAnual = ref(false);

    // ============ PREVISUALIZACIÓN (backend) — desglose real antes de subir ============ //
    const previsualizacion = ref(null);
    const cargandoPreview = ref(false);
    const errorPreview = ref('');

    const calcularPreview = async () => {
        errorPreview.value = '';
        previsualizacion.value = null;

        if (form.archivo_validacion?.isValid !== true) {
            errorPreview.value = 'Cargue el archivo PDF válido antes de calcular.';
            return;
        }

        let registros = [];
        try {
            registros = JSON.parse(form.registros_extraidos || '[]');
        } catch {
            registros = [];
        }

        if (registros.length === 0) {
            errorPreview.value = 'No se encontraron registros en el PDF.';
            return;
        }

        cargandoPreview.value = true;
        try {
            const { data } = await axios.post(route('gestion.mes.preview'), {
                id_gestion: props.data?.id,
                mes: props.data?.mes,
                registros,
            });
            previsualizacion.value = data;
            form.presupuesto = data.presupuesto_sugerido;
            if (form.errors.presupuesto) delete form.errors.presupuesto;
        } catch (e) {
            errorPreview.value = e.response?.data?.message ?? 'No se pudo calcular la vista previa.';
        } finally {
            cargandoPreview.value = false;
        }
    };

    // Si cambia el archivo, el presupuesto y la previsualización ya no
    // corresponden a los datos actuales — se limpian y hay que volver a
    // calcular antes de poder guardar.
    watch(() => form.registros_extraidos, () => {
        if (!props.editMode) {
            previsualizacion.value = null;
            form.presupuesto = '';
        }
    });

    // ============ WATCHER — mostrar campo presupuesto anual ============ //
    watch(() => props.dataGestion, (dataGestion) => {
        if (props.gestion && dataGestion) {
            const existeGestion = dataGestion.some(g => g.gestion === props.gestion.gestion);
            mostrarPresupuestoAnual.value = !existeGestion;
        }
    }, { immediate: true });

    return {
        mostrarPresupuestoAnual,
        previsualizacion,
        cargandoPreview,
        errorPreview,
        calcularPreview,
    };
}
