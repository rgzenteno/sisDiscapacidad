import { watch, computed, nextTick, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useDateCalculation } from "./useDateCalculation";
import { useTutorSearch } from "./useTutorSearch";
import { useObservacionFija } from "./useObservacionFija";

export function useFormState(props, emit) {

    // =========================
    // HELPERS
    // =========================

    const getDefaultValue = (field, existingData) => {
        const val = existingData?.[field.name];

        if (field.typeInput === "file_upload") return null;

        if (field.typeInput === "checkbox_permissions") {
            return val ?? [];
        }

        if (field.typeInput === "comple") {
            return val ?? "";
        }

        if (field.typeInput === "indefinido_check") {
            return existingData?.carnet?.fecha_emision === null ? 1 : 0;
        }

        if (field.typeInput === "propio_check") {
            return 0;
        }

        if (field.type === "boolean") return false;

        if (field.type === "number" || field.typeInput === "number") {
            return val ?? "";
        }

        if (field.typeInput === "time") {
            return field.value || "";
        }

        return val ?? "";
    };

    const mapFields = () => {
        return (props.fields || []).reduce((acc, field) => {

            // CHECKBOXES (agrupados)
            if (["checkbox_pago", "check"].includes(field.typeInput)) {
                field.options.forEach(option => {
                    acc[option.value] =
                        option.value === "efectivo"
                            ? 1
                            : props.editMode
                                ? props.existingData?.[option.value] ?? 0
                                : 0;
                });
                return acc;
            }

            acc[field.name] = props.editMode
                ? getDefaultValue(field, props.existingData)
                : getDefaultValue(field, {});

            return acc;

        }, {});
    };

    // =========================
    // FORM
    // =========================

    const form = useForm({
        ...mapFields(),
        id_persona: props.data?.id_persona ?? props.existingData?.id_persona ?? null,
    });

    // =========================
    // SNAPSHOT
    // =========================

    const initialValues = ref({});
    const snapshotReady = ref(false);
    const snapshotTaken = ref(false);

    const takeSnapshot = () => {
        if (snapshotTaken.value) return;

        initialValues.value = JSON.parse(JSON.stringify(form.data()));
        snapshotReady.value = true;
        snapshotTaken.value = true;
    };

    if (!props.editMode) {
        nextTick(takeSnapshot);
    }

    // =========================
    // NORMALIZE (CLAVE)
    // =========================

    const normalize = (val) => {
        if (val === "" || val === null || val === undefined) return null;
        if (Array.isArray(val)) return JSON.stringify([...val].sort());  // arrays ordenados
        if (!isNaN(val) && val !== "") return Number(val);
        return val;
    };

    const isDirty = computed(() => {
        if (!snapshotReady.value) return false;

        const current = form.data();

        return Object.keys(initialValues.value).some(key => {
            return normalize(current[key]) !== normalize(initialValues.value[key]);
        });
    });

    const resetToInitial = () => {
        Object.assign(form, JSON.parse(JSON.stringify(initialValues.value)));
    };

    // =========================
    // LOGICA EXTRA
    // =========================

    if (props.nombreFor && props.apellidoFor && props.fechaNacimiento) {
        const nombre = props.nombreFor.trim().split(" ")[0][0].toUpperCase();
        const apellidos = props.apellidoFor
            .trim()
            .split(" ")
            .map(p => p[0].toUpperCase())
            .join("");

        const fecha = props.fechaNacimiento.replace(/-/g, "");

        form.doc = `03-${fecha}${nombre}${apellidos}`;
    }

    // Distingue "observaciones" precargado (al editar) de un cambio hecho a
    // mano por el usuario en esta sesión del formulario — solo lo segundo
    // debe frenar el autocompletado de useObservacionFija.
    const observacionesTocada = ref(false);

    useDateCalculation(form);
    useObservacionFija(form, observacionesTocada);
    const { search } = useTutorSearch(form, props, emit);

    // =========================
    // WATCHERS
    // =========================

    // permisos
    watch(() => props.permissions, (permissions) => {
        const field = props.fields.find(f => f.typeInput === "checkbox_permissions");
        if (field && permissions?.length) {
            field.options = permissions.map(p => ({
                value: p.name,
                text: p.name,
            }));
        }
    }, { immediate: true });

    // existingData (UNIFICADO)
    watch(() => props.existingData, (data) => {
        if (!data) return;

        if (props.editMode) {
            (props.fields || []).forEach(field => {

                if (field.typeInput === "propio_check") {
                    form[field.name] = 0;
                    return;
                }

                if (field.typeInput === "checkbox_permissions") {
                    form[field.name] = Array.isArray(data[field.name])
                        ? data[field.name].map(p => typeof p === "object" ? p.name : p)
                        : [];
                    return;
                }

                if (field.typeInput === "indefinido_check") {
                    form[field.name] =
                        data.carnet?.fecha_emision === null ? 1 : 0;
                    return;
                }

                form[field.name] =
                    data.carnet?.[field.name] ??
                    data[field.name] ??
                    form[field.name];
            });
        }

        if (data.id_persona) {
            form.id_persona = data.id_persona;
        }

        // tutor
        if (data.tiene_tutor) {
            form.nombre_tutor = data.nombre_tutor ?? "";
            form.apellido_tutor = data.apellido_tutor ?? "";
            form.ci_tutor = data.ci_tutor ?? "";
            form.complemento_tutor = data.complemento_tutor ?? "";
        }

        if (!snapshotTaken.value) {
            nextTick(takeSnapshot);
        }

    }, { deep: true, immediate: true });

    // indefinido emit
    watch(() => form.indefinido, val => {
        emit('indefinidoChange', !!val);
    });

    // =========================
    // METHODS
    // =========================

    const updateField = (field, value) => {
        form[field] = value;
        delete form.errors[field];
        if (field === "observaciones") observacionesTocada.value = true;
    };

    const resetForm = () => form.reset();

    // =========================
    // RETURN
    // =========================

    return {
        form,
        updateField,
        resetForm,
        search,
        isDirty,
        resetToInitial,
    };
}
