<script setup>
// ============ INICIO IMPORTS ============ //
import { watch, ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm } from "@inertiajs/vue3";

import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputLabel from "@/components/InputLabel.vue";
import Input from "./Input.vue";
import Dropdown from "./Dropdown.vue";
import Button from './Button.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    data: {
        type: Object,
        default: () => ({})
    },
    fields: {
        type: Array,
        default: () => []
    },
    distritos: {
        type: Array,
        default: () => []
    },
    discapacidad: {
        type: Array,
        default: () => []
    },
    text: String,
    gestion: {
        type: [Object, Array],
        default: () => []
    },
    submitLabel: String,
    submitRoute: String,
    dataGestion: {
        type: Array,
        default: () => []
    },
    personasValidas: Number,
    presupuestosAnuales: {
        type: Object,
        default: () => ({})
    },
    soli: String,
    showModalCreate: Boolean,
    idFor: Number,
    label1: String,
    label2: String,
    nombreFor: String,
    fechaNacimiento: String,
    ciFor: String,
    required: Boolean,
    claveForanea: String,
    addTutor: Boolean,
    botonOmitir: Boolean,
    tutores: {
        type: Array,
        default: () => []
    },
    editMode: {
        type: Boolean,
        default: false
    },
    existingData: {
        type: [Object, Array],
        default: () => ({})
    },
    permissions: {
        type: Array,
        default: () => []
    },
    // PARA ARCHIVOS EXCEL
    acceptedFileTypes: {
        type: String,
        default: '.xlsx,.xls,.csv'
    },
    maxFileSize: {
        type: Number,
        default: 5 // MB
    },
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits([
    'add',
    'close',
    'cancel',
    'omitir',
    'sinDatos',
    'searchTutor',
    'encontrado',
    'noEncontrado',
    'mesExiste',
    'mesDelante',
    'tutorEncontradoSms',
    'fechaInvalida',
    'openFormOption'
]);
// ============ FIN EMITS ============ //

// ============ INICIO REFS ============ //
const mostrarPresupuestoAnual = ref(false);
const search = ref(false);

// REFS PARA MANEJO DE ARCHIVOS
const fileInputRefs = ref({});
const selectedFile = ref(null);
const previewFileName = ref(null);
const isDragging = ref(false);
const fileValidation = ref({
    isValid: null,
    message: '',
    missingColumns: [],
    foundColumns: []
});
// ============ FIN REFS ============ //

const searchPermission = ref('');

const filteredPermissions = (field) => {
    if (!searchPermission.value) return field.options || [];
    return (field.options || []).filter(permission =>
        permission.text.toLowerCase().includes(searchPermission.value.toLowerCase())
    );
};

const selectAllPermissions = (field) => {
    form[field.name] = filteredPermissions(field).map(p => p.value);
};

const clearAllPermissions = (field) => {
    form[field.name] = [];
};

// ============ INICIO COMPUTED ============ //
const añosRegistrados = computed(() => props.dataGestion || []);

const visibleFields = computed(() => props.fields.filter(field => !field.hidden));
// ============ FIN COMPUTED ============ //

// ============ INICIO FORMULARIO ============ //
const form = useForm({
    ...((props.fields || []).reduce((acc, field) => {
        if (field.typeInput === 'checkbox_pago') {
            field.options.forEach(option => {
                if (option.value === 'efectivo') {
                    acc[option.value] = 1;
                } else {
                    acc[option.value] = props.editMode ?
                        props.existingData[option.value] || 0 : 0;
                }
            });
        } else if (field.typeInput === 'check') {
            field.options.forEach(option => {
                acc[option.value] = props.editMode ?
                    props.existingData[option.value] || 0 : 0;
            });
        }

        else if (field.typeInput === 'file_upload') {
            acc[field.name] = null;
        }

        else if (field.typeInput === 'checkbox_permissions') {
            acc[field.name] = props.editMode && props.existingData[field.name] ?
                props.existingData[field.name] : [];
        }

        else if (field.typeInput === 'comple') {
            acc[field.name] = props.editMode ?
                (props.existingData[field.name] ?? '') :
                '';

        } else if (field.typeInput !== 'check') {
            acc[field.name] = props.editMode && props.existingData[field.name] ?
                props.existingData[field.name] :
                field.type === 'number' ? '' :
                field.type === 'boolean' ? false :
                field.typeInput === 'time' ? field.value || '' : '';
        }
        return acc;
    }, {})),
});

// Generación automática de código de carnet
if (props.nombreFor && props.fechaNacimiento) {
    const palabras = props.nombreFor.trim().split(' ');
    const iniciales = palabras.map(palabra => palabra.charAt(0).toUpperCase()).join('');
    const fechaFormateada = props.fechaNacimiento.replace(/-/g, '');
    const resultado = `03-${fechaFormateada}${iniciales}`;
    form.doc = resultado;
}
// ============ FIN FORMULARIO ============ //

// ============ INICIO WATCHERS - PERMISOS ============ //
// Watch para cargar permisos en las opciones del campo
watch(() => props.permissions, (newPermissions) => {
    if (newPermissions && newPermissions.length > 0) {
        const permissionsField = props.fields.find(f => f.typeInput === 'checkbox_permissions');
        if (permissionsField) {
            permissionsField.options = newPermissions.map(permission => ({
                value: permission.name, // o permission.id según necesites
                text: permission.name
            }));
        }
    }
}, { immediate: true });
// ============ FIN WATCHERS - PERMISOS ============ //

// ============ INICIO WATCHERS - CAMPOS ESPECÍFICOS ============ //
// Watch para calcular fecha de vencimiento
watch(() => form.fecha_emision, (nuevaFecha) => {
    if (nuevaFecha) {
        const fechaEmision = new Date(nuevaFecha);
        const fechaVencimiento = new Date(fechaEmision);
        fechaVencimiento.setFullYear(fechaVencimiento.getFullYear() + 4);

        const year = fechaVencimiento.getFullYear();
        const month = String(fechaVencimiento.getMonth() + 1).padStart(2, '0');
        const day = String(fechaVencimiento.getDate()).padStart(2, '0');
        const fechaFormateada = `${year}-${month}-${day}`;

        form.fecha_vencimiento = fechaFormateada;
    } else {
        form.fecha_vencimiento = '';
    }
}, { immediate: true });

// Watch para calcular presupuesto
watch(() => form.monto, (nuevoMonto) => {
    if (nuevoMonto) {
        const resultado = props.personasValidas * parseInt(nuevoMonto);

        if (mostrarPresupuestoAnual.value) {
            form.presupuesto = resultado;
        } else {
            form.presupuesto = resultado;
        }

        if (form.errors.presupuesto) {
            delete form.errors.presupuesto;
        }
    } else {
        form.monto = '';
        form.presupuesto = '';
    }
});

// Watchers para limpiar errores
watch(() => form.presupuesto_anual, (nuevoValor) => {
    if (nuevoValor && form.errors.presupuesto_anual) {
        delete form.errors.presupuesto_anual;
    }
});

watch(() => form.presupuesto, (nuevoValor) => {
    if (nuevoValor && form.errors.presupuesto) {
        delete form.errors.presupuesto;
    }
});
// ============ FIN WATCHERS - CAMPOS ESPECÍFICOS ============ //

// ============ INICIO WATCHERS - DATOS EXISTENTES ============ //
// Watch para actualizar campos con datos existentes
watch(() => props.existingData, (newData) => {
    if (props.editMode && newData) {
        (props.fields || []).forEach(field => {
            const fieldName = field.name;

            // Si hay un objeto carnet, intentar obtener el valor de ahí primero
            if (newData.carnet && newData.carnet[fieldName] !== undefined) {
                form[fieldName] = newData.carnet[fieldName];
            } else if (newData[fieldName] !== undefined) {
                form[fieldName] = newData[fieldName];
            }
        });
    }
}, { deep: true, immediate: true });

// Watch para cargar datos del tutor automáticamente
watch(() => props.existingData, (newData) => {
    if (newData && newData.tiene_tutor === true) {
        if (newData.nombre_tutor && newData.apellido_tutor) {
            form.nombre_tutor = newData.nombre_tutor;
            form.apellido_tutor = newData.apellido_tutor;

            if (newData.ci_tutor) {
                form.ci_tutor = newData.ci_tutor;
            }
            if (newData.complemento_tutor) {
                form.complemento_tutor = newData.complemento_tutor;
            }
        }
    }
}, {
    immediate: true,
    deep: true
});
// ============ FIN WATCHERS - DATOS EXISTENTES ============ //

// ============ INICIO WATCHERS - BÚSQUEDA TUTOR ============ //
// Watch para el campo CI del tutor
watch(() => form.ci_tutor, (newCI, oldCI) => {
    if (newCI && newCI.length >= 6) {
        const tutorEncontrado = props.tutores.find(tutor => tutor.ci_tutor === newCI);

        if (tutorEncontrado) {
            searchTutor(newCI);
            emit('tutorEncontradoSms');
        } else if (search.value === true) {
            const ciValue = form.ci_tutor;
            form.reset();
            form.ci_tutor = ciValue;
            search.value = false;
        }
    } else if (search.value === true) {
        const ciValue = form.ci_tutor;
        form.reset();
        form.ci_tutor = ciValue;
        search.value = false;
    }
});
// ============ FIN WATCHERS - BÚSQUEDA TUTOR ============ //

// ============ INICIO WATCHERS - VALIDACIÓN DE ARCHIVOS ============ //
// Watch para validar columnas del Excel cuando se carga un archivo
watch(() => selectedFile.value, async (newFile) => {
    if (newFile) {
        // Buscar el field que tiene las columnas requeridas
        const fileField = props.fields.find(f => f.typeInput === 'file_upload');

        if (fileField && fileField.requiredColumns && fileField.requiredColumns.length > 0) {
            const validation = await validateExcelColumns(newFile, fileField.requiredColumns);
            fileValidation.value = validation;
        } else {
            // Si no hay columnas requeridas definidas, marcar como válido
            fileValidation.value = {
                isValid: true,
                message: '✓ Archivo cargado correctamente',
                missingColumns: [],
                foundColumns: []
            };
        }
    }
}, { immediate: true });
// ============ FIN WATCHERS - VALIDACIÓN DE ARCHIVOS ============ //

// Busca este watcher en tu Form.vue (alrededor de la línea donde tienes los otros watchers)
watch(() => props.existingData, (newData) => {
    if (props.editMode && newData) {
        (props.fields || []).forEach(field => {
            const fieldName = field.name;

            // ✅ AGREGAR ESTE BLOQUE ESPECIAL PARA PERMISOS
            if (field.typeInput === 'checkbox_permissions' && newData[fieldName]) {
                // Si vienen como objetos, extraer solo los nombres
                form[fieldName] = Array.isArray(newData[fieldName]) ?
                    newData[fieldName].map(p => typeof p === 'object' ? p.name : p) :
                    [];
                return;
            }
            // FIN BLOQUE ESPECIAL

            // Si hay un objeto carnet, intentar obtener el valor de ahí primero
            if (newData.carnet && newData.carnet[fieldName] !== undefined) {
                form[fieldName] = newData.carnet[fieldName];
            } else if (newData[fieldName] !== undefined) {
                form[fieldName] = newData[fieldName];
            }
        });
    }
}, { deep: true, immediate: true });

// ============ INICIO FUNCIONES UTILIDADES ============ //
const formatCurrency = (amount) => {
    return amount.toLocaleString('es-BO');
};

const getComplementField = () => {
    return props.fields.find(field => field.typeInput === 'comple' && field.typeCi === 'ci');
};

const getSelectedOptionText = (field) => {
    if (!form[field.name] || form[field.name] === '') return null;
    const selectedOption = field.options.find(option =>
        option.value.toString() === form[field.name].toString()
    );
    return selectedOption ? selectedOption.text : form[field.name];
};

const selectOption = (field, value) => {
    form[field.name] = value;
};
// ============ FIN FUNCIONES UTILIDADES ============ //

// ============ INICIO FUNCIONES BÚSQUEDA TUTOR ============ //
const searchTutor = (ciValue) => {
    const tutorEncontrado = props.tutores.find(tutor => tutor.ci_tutor === ciValue);
    if (tutorEncontrado) {
        search.value = true;
        Object.keys(tutorEncontrado).forEach(key => {
            if (form[key] !== undefined) {
                form[key] = tutorEncontrado[key];
            }
        });
    }
};
// ============ FIN FUNCIONES BÚSQUEDA TUTOR ============ //

// ============ INICIO FUNCIONES VALIDACIÓN ============ //
const validarDuplicados = () => {
    if (props.submitRoute === 'dropdown.store' && form.distrito) {
        const distritoForm = form.distrito.toString().trim().toLowerCase();
        const existe = props.distritos ?.some(
            d => d.distrito ?.toString().trim().toLowerCase() === distritoForm
        );

        if (existe) {
            emit('encontrado');
            form.reset();
            return false;
        }
    }

    if (props.submitRoute === 'dropdown.addDis' && form.discapacidad) {
        const discapacidadForm = form.discapacidad.toString().trim().toLowerCase();
        const existe = props.discapacidad ?.some(
            d => d.discapacidad ?.toString().trim().toLowerCase() === discapacidadForm
        );

        if (existe) {
            emit('encontrado');
            form.reset();
            return false;
        }
    }

    return true;
};

const validarFechaMinima = () => {
    let fechaValida = true;

    props.fields.forEach((field) => {
        if (field.type === 'date' && form[field.name]) {
            if (props.data ?.fecha_inicio) {
                const fechaMinima = new Date(props.data.fecha_inicio);
                const fechaNueva = new Date(form[field.name]);

                if (fechaNueva < fechaMinima) {
                    form.errors[field.name] = `La fecha no puede ser anterior al ${fechaMinima.toLocaleDateString('es-BO')}`;
                    fechaValida = false;
                }
            }
        }
    });

    return fechaValida;
};

const validateForm = () => {
    form.errors = {};

    // AGREGAR VALIDACIÓN DE ARCHIVO
    if (fileValidation.value.isValid === false) {
        const fileField = props.fields.find(f => f.typeInput === 'file_upload');
        if (fileField) {
            form.errors[fileField.name] = 'El archivo no contiene las columnas requeridas';
        }
    }

     // Validar presupuesto anual y presupuesto si es necesario
    if (props.gestion && props.gestion.length > 0) {
        if (mostrarPresupuestoAnual.value && (!form.presupuesto_anual || form.presupuesto_anual.trim() === '')) {
            form.errors.presupuesto_anual = 'El presupuesto anual es obligatorio.';
        }
        if (!form.presupuesto || form.presupuesto === '') {
            form.errors.presupuesto = 'El presupuesto es obligatorio.';
        }
    }

    props.fields.forEach((field) => {
        if (!field.name) return;

        const fieldValue = form[field.name];

        // AGREGAR ESTA VALIDACIÓN ESPECÍFICA PARA CHECKBOXES DE PERMISOS
        if (field.typeInput === 'checkbox_permissions' && field.required) {
            if (!fieldValue || !Array.isArray(fieldValue) || fieldValue.length === 0) {
                form.errors[field.name] = 'Debe seleccionar al menos un permiso.';
            }
        }
        // FIN VALIDACIÓN
        else if (field.required && (!fieldValue || (typeof fieldValue === 'string' && !fieldValue.trim()))) {
            form.errors[field.name] = `${field.label || field.placeholder || field.name} es obligatorio.`;
        }
    });

    return Object.keys(form.errors).length === 0;
};

/* const handleFieldError = (fieldType) => {
    if (fieldType === 'email') {
        form.errors['email'] = 'Email es obligatorio';
    } else if (fieldType === 'telefono') {
        form.errors['telefono'] = 'Número es obligatorio';
    }
}; */

/* const Email = () => {
    handleFieldError('email');
}; */

const Numero = () => {
    handleFieldError('telefono');
};
// ============ FIN FUNCIONES VALIDACIÓN ============ //

// ============ INICIO FUNCIONES MANEJO DE ARCHIVOS ============ //
const triggerFileInput = (fieldName) => {
    const inputElement = document.getElementById(`file-input-${fieldName}`);
    if (inputElement) {
        inputElement.click();
    }
};

const handleFileSelect = (event, fieldName) => {
    const file = event.target.files[0];
    if (file) {
        processFile(file, fieldName);
    }
};

const handleFileDrop = (event, fieldName) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        processFile(file, fieldName);
    }
};

const processFile = (file, fieldName) => {
    // Obtener el field para validar extensiones permitidas
    const field = props.fields.find(f => f.name === fieldName);
    const allowedTypes = field?.acceptedTypes || props.acceptedFileTypes;
    const maxSize = field?.maxSize || props.maxFileSize;

    // Validar tipo de archivo
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    const allowedExtensions = allowedTypes.split(',').map(ext => ext.trim());

    if (!allowedExtensions.includes(fileExtension)) {
        alert(`Tipo de archivo no permitido. Use: ${allowedTypes}`);
        return;
    }

    // Validar tamaño (convertir MB a bytes)
    if (file.size > maxSize * 1024 * 1024) {
        alert(`El archivo es demasiado grande. Máximo ${maxSize}MB.`);
        return;
    }

    selectedFile.value = file;
    previewFileName.value = file.name;
    form[fieldName] = file;

    // Resetear validación
    fileValidation.value = {
        isValid: null,
        message: 'Validando archivo...',
        missingColumns: [],
        foundColumns: []
    };

    // Limpiar error si existía
    if (form.errors[fieldName]) {
        delete form.errors[fieldName];
    }
};

const removeFile = (fieldName) => {
    selectedFile.value = null;
    previewFileName.value = null;
    form[fieldName] = null;
    fileValidation.value = {
        isValid: null,
        message: '',
        missingColumns: [],
        foundColumns: []
    };

    // Limpiar el input file
    const inputElement = document.getElementById(`file-input-${fieldName}`);
    if (inputElement) {
        inputElement.value = '';
    }
};

// FUNCIÓN PARA VALIDAR COLUMNAS DEL EXCEL
// FUNCIÓN PARA VALIDAR COLUMNAS DEL EXCEL
const validateExcelColumns = async (file, requiredColumns) => {
    return new Promise((resolve) => {
        const reader = new FileReader();

        reader.onload = async (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });

                // Leer la primera hoja
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];

                // Convertir a JSON todas las filas
                const jsonData = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1,
                    defval: '',
                    blankrows: false
                });

                if (jsonData.length === 0) {
                    resolve({
                        isValid: false,
                        message: 'El archivo Excel está vacío',
                        missingColumns: requiredColumns,
                        foundColumns: []
                    });
                    return;
                }

                // Buscar la fila de encabezados (hasta la fila 5)
                let headers = [];
                let headerRowIndex = -1;

                for (let i = 0; i < Math.min(5, jsonData.length); i++) {
                    const row = jsonData[i];

                    // Verificar si esta fila contiene encabezados
                    // Normalizamos y limpiamos cada celda
                    const normalizedRow = row.map(cell =>
                        cell?.toString()
                            .trim()
                            .toLowerCase()
                            .replace(/\s+/g, ' ') // Múltiples espacios a uno solo
                            .replace(/\./g, '') // Quitar puntos
                    ).filter(cell => cell !== '');

                    // Si encontramos columnas similares a las requeridas, es la fila de encabezados
                    const requiredColsNormalized = requiredColumns.map(col =>
                        col.toLowerCase()
                            .replace(/\s+/g, ' ')
                            .replace(/\./g, '')
                    );

                    const matchCount = normalizedRow.filter(cell =>
                        requiredColsNormalized.some(reqCol =>
                            cell.includes(reqCol) || reqCol.includes(cell)
                        )
                    ).length;

                    // Si coinciden al menos el 50% de las columnas, es la fila de encabezados
                    if (matchCount >= requiredColsNormalized.length * 0.5) {
                        headers = normalizedRow;
                        headerRowIndex = i;
                        break;
                    }
                }

                if (headerRowIndex === -1 || headers.length === 0) {
                    resolve({
                        isValid: false,
                        message: 'No se encontraron encabezados válidos en las primeras 5 filas',
                        missingColumns: requiredColumns,
                        foundColumns: []
                    });
                    return;
                }

                // Normalizar columnas requeridas
                const requiredColsNormalized = requiredColumns.map(col =>
                    col.toLowerCase()
                        .replace(/\s+/g, ' ')
                        .replace(/\./g, '')
                );

                // Verificar qué columnas se encontraron
                const foundColumns = [];
                const missingColumns = [];

                requiredColsNormalized.forEach((reqCol, index) => {
                    const found = headers.some(header => {
                        // Comparación flexible: si el header contiene la columna requerida o viceversa
                        return header.includes(reqCol) || reqCol.includes(header);
                    });

                    if (found) {
                        foundColumns.push(requiredColumns[index]);
                    } else {
                        missingColumns.push(requiredColumns[index]);
                    }
                });

                const isValid = missingColumns.length === 0;

                // Calcular filas de datos (total - filas de encabezado - filas vacías)
                const dataRows = jsonData.length - headerRowIndex - 1;

                resolve({
                    isValid,
                    message: isValid
                        ? `✓ Todas las columnas requeridas están presentes (Encabezados en fila ${headerRowIndex + 1})`
                        : `✗ Faltan columnas requeridas`,
                    missingColumns,
                    foundColumns,
                    totalRows: dataRows > 0 ? dataRows : 0,
                    headerRow: headerRowIndex + 1
                });

            } catch (error) {
                console.error('Error al procesar Excel:', error);
                resolve({
                    isValid: false,
                    message: 'Error al leer el archivo Excel',
                    missingColumns: requiredColumns,
                    foundColumns: []
                });
            }
        };

        reader.onerror = () => {
            resolve({
                isValid: false,
                message: 'Error al cargar el archivo',
                missingColumns: requiredColumns,
                foundColumns: []
            });
        };

        reader.readAsArrayBuffer(file);
    });
};
// ============ FIN FUNCIONES MANEJO DE ARCHIVOS ============ //

// ============ INICIO FUNCIONES MANEJO FORMULARIO ============ //
const handleSuccess = () => {
    form.reset();
    emit('add');
};

const Cancelar = () => {
    emit('cancel');
    form.reset();
};

const Omitir = () => {
    form.reset();
    emit('omitir');
};

const submit = async () => {
    // Validar duplicados primero
    if (!validarDuplicados()) {
        return;
    }

    // Validar fechas
    if (!validarFechaMinima()) {
        emit('fechaInvalida');
        return;
    }

    if (!validateForm()) {
        emit('sinDatos');
        return;
    }

    if (props.data.id_persona && props.data.id_estado) {
        form.id_persona = props.data.id_persona;
        form.id_estado = props.data.id_estado;
    }

    if (props.data.tipo === 'gestion') {
        if (props.data.id) {
            form.id_gestion = props.data.id;
        }
        if (props.data.mes) {
            form.mes = props.data.mes;
        }
        if (props.data.gestion) {
            form.gestion = props.data.gestion;
        }
    }

    if (props.claveForanea === 'id_persona') {
        form[props.claveForanea] = props.idFor;
    }

    if (props.claveForanea === 'id_tutor') {
        form.id_tutor = props.idFor;
    }

    const isEditing = props.editMode && Object.keys(props.existingData).length > 0;

    try {
        if (isEditing) {
            // MODO EDICIÓN - SIN ARCHIVOS
            await form.put(route(props.submitRoute, props.idFor), {
                onSuccess: () => {
                    handleSuccess();
                },
                onError: (error) => {
                    console.error("Error en actualización:", error.response?.data);
                }
            });
        } else {
            // MODO CREACIÓN - VERIFICAR SI HAY ARCHIVOS
            const hasFiles = props.fields.some(field =>
                field.typeInput === 'file_upload' && form[field.name]
            );

            if (hasFiles) {
                // ENVIAR CON FormData SI HAY ARCHIVOS
                const formData = new FormData();

                Object.keys(form.data()).forEach(key => {
                    if (form[key] !== null && form[key] !== undefined) {
                        formData.append(key, form[key]);
                    }
                });

                await form.post(route(props.submitRoute), {
                    data: formData,
                    forceFormData: true,
                    onSuccess: async () => {
                        if (props.addTutor) {
                            try {
                                await form.put(route('persona.updateTutor', props.idFor), {
                                    onSuccess: () => {
                                        handleSuccess();
                                    },
                                    onError: (error) => {
                                        console.error("Error al actualizar tutor:", error);
                                    }
                                });
                                return;
                            } catch (error) {
                                console.error('Error al actualizar tutor:', error);
                                return;
                            }
                        }
                        handleSuccess();
                    },
                    onError: (errors) => {
                        if (errors.ci_persona) {
                            emit('close', form.ci_persona);
                        }
                    }
                });
            } else {
                // ENVIAR NORMAL SIN ARCHIVOS
                await form.post(route(props.submitRoute), {
                    onSuccess: async () => {
                        if (props.addTutor) {
                            try {
                                await form.put(route('persona.updateTutor', props.idFor), {
                                    onSuccess: () => {
                                        handleSuccess();
                                    },
                                    onError: (error) => {
                                        console.error("Error al actualizar tutor:", error);
                                    }
                                });
                                return;
                            } catch (error) {
                                console.error('Error al actualizar tutor:', error);
                                return;
                            }
                        }
                        handleSuccess();
                    },
                    onError: (errors) => {
                        if (errors.ci_persona) {
                            emit('close', form.ci_persona);
                        }
                    }
                });
            }
        }
    } catch (error) {
        console.error("Error general:", error);
    }
};
// ============ FIN FUNCIONES MANEJO FORMULARIO ============ //

// ============ INICIO LIFECYCLE HOOKS ============ //
const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        Cancelar();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
// ============ FIN LIFECYCLE HOOKS ============ //
</script>

    <template>
<div class="fixed inset-0 bg-slate-900/75 flex items-center justify-center z-40 px-4 py-8 overflow-y-auto backdrop-blur-sm">
    <div :class="['relative w-full bg-white dark:bg-gray-800 rounded-3xl shadow-2xl transform transition-all duration-300 my-8', fields && fields.length >= 6 || visibleFields.some(f => f.typeInput === 'checkbox_permissions') ? 'max-w-2xl' : 'max-w-md']">
        <!-- Modal Header -->
        <div class="grid grid-cols-[1fr_auto] gap-6 px-4 pt-4 border-b border-gray-100 dark:border-gray-600/50 rounded-t-3xl bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 sticky top-0 z-10">
            <!-- Contenido principal -->
            <div class="min-w-0">
                <!-- Fila 1: Avatar (opcional), título y subtítulo -->
                <div class="grid grid-cols-[auto_1fr] gap-4 items-center mb-2">
                    <!-- Avatar / Ícono -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-400 shadow-md ring-1 ring-indigo-100 flex-shrink-0">
                        <slot name="icon">
                            <!-- Fallback si no mandas nada desde el padre -->
                            <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4h-4Z" clip-rule="evenodd" />
                            </svg>
                        </slot>
                    </div>

                    <!-- Título y subtítulo -->
                    <div class="min-w-0">
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight truncate">
                            <slot name="label1">Título por defecto</slot>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-blue-300 truncate">
                            <slot name="label2">Subtítulo por defecto</slot>
                        </p>
                    </div>
                </div>
            </div>
            <!-- Acciones -->
            <div class="flex items-start gap-3 flex-shrink-0">
                <button type="button" @click="Cancelar" class="absolute top-3 right-3 p-2 rounded-full bg-white shadow hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>
        </div>
        <div :class="['overflow-y-auto',fields && visibleFields.length >= 6 || visibleFields.some(f => f.typeInput === 'checkbox_permissions') ? 'p-6 max-h-[calc(100vh-200px)]' : 'p-6 max-h-[70vh]']">
            <GuestLayout>
                <div class="flex justify-center items-center w-full">
                    <div class="w-full">
                        <form @submit.prevent="submit" class="flex flex-col w-full">
                            <!-- Dynamic grid container based on fields length -->
                            <div :class="['grid', visibleFields.length >= 6 ? 'gap-2 grid-cols-1 lg:grid-cols-2 mb-2' : 'gap-2 grid-cols-1 ']">
                                <div v-for="(field, index) in visibleFields.filter(f => !(f.typeCi === 'ci' && f.typeInput === 'comple'))" :key="index" class="w-full">
                                    <div class="flex" v-show="field.label !== ''">
                                        <InputLabel :for="field.name" :value="field.label" class="block text-sm font-medium text-gray-900 dark:text-white" />
                                        <span :style="{ visibility: field.required === true ? 'visible' : 'hidden' }" class="ms-1 text-red-600">*</span>
                                    </div>
                                    <div class="relative">
                                        <!-- Label / text-->
                                        <div v-if="field.typeInput === 'label'">
                                            <p v-for="(texto, index) in field.textos" :key="index" class="block text-sm font-medium text-gray-900 dark:text-white text-justify">
                                                {{ texto }}
                                            </p>
                                        </div>

                                        <!-- Texto / text-->
                                        <Input v-if="field.typeInput === 'text'" :input-type="field.type" v-model="form[field.name]" :customClass="field.nameStyle" :maxlength="field.range" :placeholder="'Ingrese ' + field.placeholder" :readonly="field.readonly" :errors="form.errors[field.name]" />

                                        <!-- Cedula + complemeto -->
                                        <div v-if="field.typeCi === 'ci' && field.typeInput === 'cedula'" class="relative">
                                            <div class="flex items-center">
                                                <!-- Campo Cédula -->
                                                <Input :input-type="field.type" class="flex-1" v-model="form[field.name]" :customClass="field.nameStyle" :maxlength="field.range" :placeholder="'Ingrese ' + field.placeholder" :errors="form.errors[field.name] && !form[field.name]" />
                                                <!-- Separador -->
                                                <span class="text-gray-400 mx-2">-</span>
                                                <!-- Campo Complemento -->
                                                <Input :input-type="getComplementField().type" class="w-20" v-model="form[getComplementField().name]" :customClass="getComplementField().nameStyle" :maxlength="getComplementField().range" placeholder="Ext" :errors="form.errors[getComplementField().name] && !form[getComplementField().name]" />
                                            </div>
                                        </div>

                                        <!-- Agregar este bloque después del campo de nombre del rol -->

                                        <div v-if="field.typeInput === 'checkbox_permissions'" class="w-full col-span-full">

                                            <!-- Header con barra de búsqueda y acciones rápidas -->
                                            <div class="mb-3 space-y-3">
                                                <!-- Barra de búsqueda -->
                                                <!-- <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   v-model="searchPermission"
                   placeholder="Buscar permisos..."
                   class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
        </div> -->

                                                <!-- Botones de selección rápida -->
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <button type="button" @click="selectAllPermissions(field)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 rounded-lg transition-colors duration-200">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Seleccionar todos
                                                    </button>

                                                    <button type="button" @click="clearAllPermissions(field)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        Limpiar selección
                                                    </button>

                                                    <!-- Contador inline -->
                                                    <div class="ml-auto flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                        </svg>
                                                        <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">
                                                            {{ form[field.name] ? form[field.name].length : 0 }} / {{ filteredPermissions(field).length }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Grid de checkboxes para permisos -->

                                            <div class="relative border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 p-4 max-h-64 overflow-y-auto">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                                                    <label v-for="permission in filteredPermissions(field)" :key="permission.value" class="group relative flex items-start p-3 rounded-lg border-2 transition-all duration-200 cursor-pointer hover:shadow-md" :class="form[field.name] && form[field.name].includes(permission.value)
                       ? 'bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-400 dark:from-blue-900/30 dark:to-indigo-900/30 dark:border-blue-500 shadow-sm'
                       : 'bg-gray-50 border-gray-200 hover:border-gray-300 dark:bg-gray-700/50 dark:border-gray-600 dark:hover:border-gray-500'">

                                                        <!-- Checkbox customizado -->
                                                        <div class="flex items-start gap-3 w-full">
                                                            <div class="relative flex items-center justify-center flex-shrink-0 mt-0.5">
                                                                <input type="checkbox" :value="permission.value" v-model="form[field.name]" class="peer w-5 h-5 text-blue-600 bg-white border-2 border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:bg-gray-700 dark:border-gray-500 cursor-pointer transition-all">

                                                                <!-- Icono de check que aparece cuando está seleccionado -->
                                                                <svg class="absolute w-3 h-3 text-white pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </div>

                                                            <div class="flex-1 min-w-0">
                                                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100 break-words leading-tight" :class="form[field.name] && form[field.name].includes(permission.value) ? 'text-blue-900 dark:text-blue-100' : ''">
                                                                    {{ permission.text }}
                                                                </span>

                                                                <!-- Badge de estado -->
                                                                <!-- <span v-if="form[field.name] && form[field.name].includes(permission.value)"
                              class="inline-block mt-1 px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded">
                            Seleccionado
                        </span> -->
                                                            </div>
                                                        </div>

                                                        <!-- Indicador visual lateral -->
                                                        <div v-if="form[field.name] && form[field.name].includes(permission.value)" class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-indigo-500 rounded-l-lg">
                                                        </div>
                                                    </label>
                                                </div>

                                                <!-- Mensaje cuando no hay resultados de búsqueda -->
                                                <div v-if="filteredPermissions(field).length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                                                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-3">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                                        No se encontraron permisos
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                        Intenta con otro término de búsqueda
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Mensaje de error si es requerido -->
                                            <div v-if="form.errors[field.name]" class="mt-2 flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                                                    {{ form.errors[field.name] }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Campo para cargar archivos (Excel, CSV, etc.) -->
<div v-if="field.typeInput === 'file_upload'" class="w-full col-span-full">
    <div
        @drop.prevent="handleFileDrop($event, field.name)"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @click="triggerFileInput(field.name)"
        :class="[
            'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-all duration-200',
            isDragging
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : fileValidation.isValid === true
                    ? 'border-green-500 bg-green-50 dark:bg-green-900/20 dark:border-green-600'
                    : fileValidation.isValid === false
                        ? 'border-red-500 bg-red-50 dark:bg-red-900/20 dark:border-red-600'
                        : form.errors[field.name]
                            ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                            : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500'
        ]"
    >
        <!-- Icono y preview del archivo -->
        <div class="flex flex-col items-center justify-center">
            <!-- Vista previa cuando hay archivo seleccionado -->
            <div v-if="previewFileName && form[field.name]" class="mb-4 w-full">
                <div class="inline-flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-700 rounded-lg border shadow-sm"
                     :class="fileValidation.isValid === true
                         ? 'border-green-300 dark:border-green-600'
                         : fileValidation.isValid === false
                             ? 'border-red-300 dark:border-red-600'
                             : 'border-gray-200 dark:border-gray-600'">

                    <!-- Icono de Excel con estado -->
                    <div class="relative">
                        <svg class="w-10 h-10" :class="fileValidation.isValid === true
                                 ? 'text-green-600'
                                 : fileValidation.isValid === false
                                     ? 'text-red-600'
                                     : 'text-gray-600'"
                             fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                            <path d="M14 2v6h6M9.5 13.5l1.5 2.5 1.5-2.5M9.5 17.5l1.5-2.5 1.5 2.5"/>
                        </svg>

                        <!-- Badge de validación -->
                        <div v-if="fileValidation.isValid !== null"
                             class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center"
                             :class="fileValidation.isValid
                                 ? 'bg-green-500'
                                 : 'bg-red-500'">
                            <svg v-if="fileValidation.isValid" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg v-else class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>

                    <div class="text-left flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate max-w-xs">
                            {{ previewFileName }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ (form[field.name].size / 1024).toFixed(2) }} KB
                            <span v-if="fileValidation.totalRows" class="ml-2">
                                • {{ fileValidation.totalRows }} {{ fileValidation.totalRows === 1 ? 'fila' : 'filas' }}
                            </span>
                        </p>
                    </div>

                    <!-- Botón para eliminar archivo -->
                    <button
                        type="button"
                        @click.stop="removeFile(field.name)"
                        class="p-1.5 rounded-full hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                    >
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Mensaje de validación -->
                <div v-if="fileValidation.message" class="mt-3 p-3 rounded-lg border"
                     :class="fileValidation.isValid === true
                         ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700'
                         : fileValidation.isValid === false
                             ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700'
                             : 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700'">
                    <div class="flex items-start gap-2">
                        <svg v-if="fileValidation.isValid === true" class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg v-else-if="fileValidation.isValid === false" class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg v-else class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <div class="flex-1">
                            <p class="text-sm font-medium"
                               :class="fileValidation.isValid === true
                                   ? 'text-green-700 dark:text-green-300'
                                   : fileValidation.isValid === false
                                       ? 'text-red-700 dark:text-red-300'
                                       : 'text-blue-700 dark:text-blue-300'">
                                {{ fileValidation.message }}
                            </p>

                            <!-- Mostrar columnas encontradas y faltantes -->
                            <div v-if="fileValidation.isValid === false && fileValidation.foundColumns.length > 0" class="mt-2 space-y-1">
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                    Columnas encontradas:
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="col in fileValidation.foundColumns" :key="col"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200">
                                        {{ col }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="fileValidation.missingColumns.length > 0" class="mt-2 space-y-1">
                                <p class="text-xs font-medium text-red-600 dark:text-red-400">
                                    Columnas faltantes:
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="col in fileValidation.missingColumns" :key="col"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                        {{ col }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Icono de upload cuando no hay archivo -->
            <div v-else>
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                          stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Texto informativo -->
            <div class="mt-4">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ form[field.name] ? 'Cambiar archivo' : (field.placeholder || 'Arrastra tu archivo aquí o haz clic para seleccionar') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ field.acceptedTypes || acceptedFileTypes }} hasta {{ field.maxSize || maxFileSize }}MB
                </p>

                <!-- Mostrar columnas requeridas -->
                <div v-if="field.requiredColumns && field.requiredColumns.length > 0 && !form[field.name]" class="mt-3">
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        Columnas requeridas:
                    </p>
                    <div class="flex flex-wrap gap-1 justify-center">
                        <span v-for="col in field.requiredColumns" :key="col"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            {{ col }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Input file oculto -->
            <input
                :id="`file-input-${field.name}`"
                type="file"
                :accept="field.acceptedTypes || acceptedFileTypes"
                @change="handleFileSelect($event, field.name)"
                class="hidden"
            />
        </div>
    </div>

    <!-- Mensaje de error -->
    <p v-if="form.errors[field.name]" class="mt-2 flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm text-red-600 dark:text-red-400 font-medium">
            {{ form.errors[field.name] }}
        </span>
    </p>
</div>

                                        <!-- Campo Presupuesto Anual (solo si es primer registro del año) -->
                                        <div v-if="mostrarPresupuestoAnual === true && field.name === 'presupuesto'" class="w-full">
                                            <!-- Campo Presupuesto Anual -->
                                            <div class="mb-4">
                                                <div class="flex">
                                                    <InputLabel for="presupuesto_anual" value="Presupuesto Anual (Bs.)" class="block text-sm font-medium text-gray-900 dark:text-white" />
                                                    <span class="ms-1 text-red-600">*</span>
                                                </div>
                                                <Input input-type="number" v-model="form.presupuesto_anual" placeholder="Ingrese el presupuesto anual" :customClass="field.nameStyle" :errors="form.errors.presupuesto_anual" />
                                            </div>

                                            <!-- Campo Presupuesto Mensual (calculado automáticamente) -->
                                            <div>
                                                <div class="flex">
                                                    <InputLabel :for="field.name" value="Presupuesto Mensual (Bs.)" class="block text-sm font-medium text-gray-900 dark:text-white" />
                                                    <span class="ms-1 text-red-600">*</span>
                                                </div>
                                                <Input :input-type="field.type" v-model="form[field.name]" placeholder="Se calculará automáticamente al ingresar el monto" :errors="form.errors.presupuesto" class="!bg-gray-100" />
                                                <p class="text-sm text-gray-500 mt-1">
                                                    Presupuesto sugerido: {{ props.personasValidas * (parseInt(form.monto) || 0) }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Campo Presupuesto Mensual (calculado automáticamente cuando ya existe el año) -->
                                        <div v-else-if="field.name === 'presupuesto' && !mostrarPresupuestoAnual" class="w-full">
                                            <div class="flex">
                                                <InputLabel :for="field.name" value="Presupuesto Mensual (Bs.)" class="block text-sm font-medium text-gray-900 dark:text-white" />
                                                <span class="ms-1 text-red-600">*</span>
                                            </div>
                                            <Input :input-type="field.type" v-model="form[field.name]" placeholder="Se calculará automáticamente al ingresar el monto" :errors="form.errors.presupuesto" class="!bg-gray-100" />

                                            <p class="text-sm text-gray-500 mt-1">
                                                Presupuesto sugerido: {{ props.personasValidas * (parseInt(form.monto) || 0) }}
                                            </p>
                                        </div>

                                        <!-- Dropdown / select -->
                                        <Dropdown v-if="field.typeInput === 'select'" align="left" width="60">
                                            <template #trigger>
                                                <button type="button" :class="[
                                                                'inline-flex items-center justify-between w-full gap-3 p-2 text-sm font-medium rounded-lg shadow-sm transition-all focus:ring-1 focus:outline-none',
                                                                form.errors[field.name] && !form[field.name]
                                                                    ? 'border-red-500 text-red-500 bg-red-50 hover:bg-red-100 focus:ring-red-300'
                                                                    : (form[field.name] && form[field.name].trim() !== ''
                                                                        ? 'border-gray-300 text-gray-800 bg-gray-50 hover:bg-gray-100 hover:border-blue-400 focus:ring-blue-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'
                                                                        : 'border-gray-300 text-gray-500 bg-gray-50 hover:bg-gray-100 hover:border-blue-400 focus:ring-blue-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600'
                                                                    ),
                                                                'border cursor-pointer'
                                                            ]" :aria-required="field.required" :aria-label="'Dropdown for ' + field.name">
                                                    <span class="truncate">
                                                        {{ getSelectedOptionText(field) || `Seleccione ${field.placeholder}` }}
                                                    </span>
                                                    <svg class="w-2.5 h-2.5 ms-3 transition-transform duration-200 flex-shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                                                    </svg>
                                                </button>
                                            </template>

                                            <template #content>
                                                <div class="w-full max-w-sm bg-white rounded-lg border border-gray-200 shadow-lg dark:bg-gray-800 dark:border-gray-700">
                                                    <!-- Lista scrolleable con altura limitada -->
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <ul class="py-1">
                                                            <!-- Mensaje cuando no hay opciones -->
                                                            <li v-if="!field.options || field.options.length === 0" class="px-4 py-3">
                                                                <div class="flex items-center gap-3 text-slate-400 dark:text-gray-500">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                                    </svg>
                                                                    <span class="text-sm">No hay opciones disponibles</span>
                                                                </div>
                                                            </li>

                                                            <!-- Opciones del select -->
                                                            <li v-for="option in field.options" :key="option.value">
                                                                <button type="button" @click="selectOption(field, option.value)" class="flex items-center justify-between w-full px-4 py-2 text-sm text-left hover:bg-gray-100 transition-colors duration-150 dark:hover:bg-gray-700" :class="form[field.name] && form[field.name].toString() === option.value.toString() ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900 dark:text-blue-200' : 'text-gray-700 dark:text-gray-300'">
                                                                    <span>{{ option.text }}</span>
                                                                    <svg v-if="form[field.name] && form[field.name].toString() === option.value.toString()" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>

                                                    <!-- Botón para agregar nueva opción (fuera del scroll) -->
                                                    <div v-if="field.add" class="border-t border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                                                        <a @click="emit('openFormOption')" class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-colors duration-150 dark:text-blue-400 dark:hover:bg-gray-600 dark:hover:text-blue-300">
                                                            <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center dark:bg-blue-900">
                                                                <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                                                </svg>
                                                            </div>
                                                            <span>Agregar nueva opción</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </template>
                                        </Dropdown>

                                        <div v-if="field.name === 'fecha_emision'" class="mt-1 text-sm text-gray-500">
                                            <div class="mt-2">
                                                <div class="flex">
                                                    <InputLabel for="fecha_vencimiento" value="Fecha de Vencimiento" class="block text-sm font-medium text-gray-900 dark:text-white" />
                                                    <!-- <span class="ms-1 text-red-600">*</span> -->
                                                </div>
                                                <Input v-model="form.fecha_vencimiento" placeholder="Se calculará automáticamente al ingresar fecha de emisión" title="Se calculará automáticamente al ingresar fecha de emisión" :customClass="field.nameStyle" class="w-full text-gray-900 text-sm rounded-lg cursor-not-allowed dark:bg-gray-600 dark:border-gray-500 dark:text-white" readonly />
                                            </div>
                                        </div>

                                        <!-- Tiempo / time-->
                                        <input v-if="field.typeInput === 'time'" :type="field.type" :id="field.name" v-model="form[field.name]" :placeholder="'Ingrese ' + field.placeholder" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500" :readonly="field.readonly" />

                                        <!-- Campos extra-->
                                        <!-- Texto / ID-->
                                        <input v-if="field.typeInput === 'id'" v-model="props.nombreFor" class="w-full cursor-not-allowed capitalize font-bold bg-blue-100 border border-blue-400 text-blue-500 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500" :readonly="field.readonly" />

                                        <!-- Texto / Verificar-->
                                        <input v-if="field.typeInput === 'verificar'" :type="field.type" :id="field.name" v-model="form.carnet" :placeholder="'Ingrese ' + field.placeholder" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500" :readonly="field.readonly" />

                                        <!-- párrafo / aria-->
                                        <textarea v-if="field.typeInput === 'textarea'" v-model="form[field.name]" :id="field.name" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white focus:ring-gray-500 focus:border-gray-500" aria-required="true" :placeholder="'Ingrese ' + field.placeholder" :aria-label="'Ingresa ' + field.label + ' (máximo ' + field.maxLength + ' caracteres)'" :maxlength="field.maxLength"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons container -->
                            <div :class="[
                                    'sticky bottom-0 border-t border-gray-200 dark:border-gray-600 ',
                                    fields && fields.length >= 6 ? 'pt-4 -mx-6 px-6 pb-0' : 'pt-3 -mx-4 px-4 pb-0'
                                ]">
                                <div class="flex items-center justify-end space-x-3">
                                    <Button class="text-white bg-blue-600 hover:bg-blue-500" :type="existingData ? 'submit' : 'button'" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        {{ props.soli ? props.soli : (existingData ? (editMode ? 'Actualizar' : (search === true ? 'Siguiente' : 'Guardar')) : 'Siguiente') }}
                                    </Button>
                                    <Button @click.prevent="Cancelar()" class="text-slate-700 bg-white hover:bg-slate-100">
                                        Cancelar
                                    </Button>
                                    <Button v-if="props.botonOmitir" @click.prevent="Omitir()" class="border-red-700 text-red-700 bg-white hover:bg-red-100">
                                        Omitir
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </GuestLayout>
        </div>
    </div>
</div>
</template>

<style>
.error {
    color: red;
    font-size: 0.9rem;
}
</style>
