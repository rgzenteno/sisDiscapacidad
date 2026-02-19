<script setup>
// ============ INICIO IMPORTS ============ //
import { watch, computed } from 'vue';
import InputLabel from '@/components/InputLabel.vue';
// ============ FIN IMPORTS ============ //

// ============ INICIO PROPS ============ //
const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Array,
        default: () => []
    },
    error: {
        type: String,
        default: ''
    },
    props: {
        type: Object,
        required: true
    }
});
// ============ FIN PROPS ============ //

// ============ INICIO EMITS ============ //
const emit = defineEmits(['update:modelValue']);
// ============ FIN EMITS ============ //

// ============ INICIO WATCHERS ============ //
// Watch para cargar permisos desde props.permissions
watch(() => props.props.permissions, (newPermissions) => {
    if (newPermissions && newPermissions.length > 0) {
        if (!props.field.options) {
            props.field.options = [];
        }
        props.field.options = newPermissions.map(permission => ({
            value: permission.name,
            text: permission.name,
            super_only: permission.super_only || false  // 👈 Agregar esto
        }));
    }
}, { immediate: true });
// ============ FIN WATCHERS ============ //

// ============ INICIO COMPUTED ============ //
// Organizar permisos en estructura padre-hijo
const permissionsHierarchy = computed(() => {
    if (!props.field.options || props.field.options.length === 0) {
        return [];
    }

    const groups = {};

    // Identificar padres e hijos
    props.field.options.forEach(permission => {
        const parts = permission.value.split('-');

        if (parts.length === 1) {
            // Es un padre (ej: "general", "beneficiario")
            if (!groups[permission.value]) {
                groups[permission.value] = {
                    parent: permission,
                    children: []
                };
            } else {
                groups[permission.value].parent = permission; // 👈 Asignar padre si ya existe el grupo
            }
        } else {
            // Es un hijo (ej: "importar-general", "crear-beneficiario")
            const parentName = parts[parts.length - 1]; // "general", "beneficiario"

            if (!groups[parentName]) {
                groups[parentName] = {
                    parent: null,
                    children: []
                };
            }
            groups[parentName].children.push(permission);
        }
    });

    // Filtrar grupos que tienen padre (ya sea con o sin hijos)
    const result = Object.entries(groups)
        .filter(([key, group]) => group.parent) // 👈 SOLO filtrar por si tiene padre
        .map(([key, group]) => group);

    return result;
});
// ============ FIN COMPUTED ============ //

// ============ INICIO MÉTODOS ============ //
const updatePermissions = (permissionValue) => {
    const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const index = currentValue.indexOf(permissionValue);

    if (index > -1) {
        currentValue.splice(index, 1);
    } else {
        currentValue.push(permissionValue);
    }

    emit('update:modelValue', currentValue);
};

const toggleParentWithChildren = (group) => {
    const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const parent = group.parent?.value;

    if (!parent) return;

    const index = currentValue.indexOf(parent);
    const childValues = group.children.map(child => child.value);

    let newValue;
    if (index > -1) {
        // Deseleccionar padre Y todos los hijos
        newValue = currentValue.filter(v => v !== parent && !childValues.includes(v));
    } else {
        // Seleccionar padre Y todos los hijos
        newValue = [...currentValue, parent, ...childValues.filter(cv => !currentValue.includes(cv))];
    }

    emit('update:modelValue', newValue);
};

const formatPermissionText = (text) => {
    if (!text) return '';
    const cleaned = text.split('-')[0];
    // Capitalizar primera letra
    return cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
};

const toggleChild = (childValue, parentValue, group) => {
    const currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const index = currentValue.indexOf(childValue);

    let newValue;
    if (index > -1) {
        // Deseleccionar hijo solamente
        newValue = currentValue.filter(v => v !== childValue);
    } else {
        // Seleccionar hijo
        newValue = [...currentValue, childValue];

        // Solo auto-seleccionar el padre si NO es super_only
        if (parentValue && !newValue.includes(parentValue)) {
            // Verificar si el padre es super_only
            const isSuperOnly = group.parent?.super_only || false;

            if (!isSuperOnly) {
                newValue.push(parentValue);
            }
        }
    }

    emit('update:modelValue', newValue);
};

const isSelected = (permissionValue) => {
    return Array.isArray(props.modelValue) && props.modelValue.includes(permissionValue);
};

const isParentSelected = (group) => {
    return group.parent && isSelected(group.parent.value);
};

const areAllChildrenSelected = (group) => {
    if (group.children.length === 0) return false;
    return group.children.every(child => isSelected(child.value));
};

const selectAll = () => {
    const allPermissions = (props.field.options || []).map(p => p.value);
    emit('update:modelValue', allPermissions);
};

const clearAll = () => {
    emit('update:modelValue', []);
};
// ============ FIN MÉTODOS ============ //
</script>

<template>
    <div class="w-full col-span-full">
        <!-- Label -->
        <div class="flex mb-2" v-show="field.label !== ''">
            <InputLabel :for="field.name" :value="field.label"
                class="block text-sm font-medium text-gray-900 dark:text-white" />
            <span :style="{ visibility: field.required === true ? 'visible' : 'hidden' }" class="ms-1 text-red-600">
                *
            </span>
        </div>

        <!-- Header con acciones rápidas -->
        <div class="mb-3 space-y-3">
            <!-- Botones de selección rápida -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" @click="selectAll"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Seleccionar todos
                </button>

                <button type="button" @click="clearAll"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar selección
                </button>

                <!-- Contador inline -->
                <div
                    class="ml-auto flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">
                        {{ modelValue ? modelValue.length : 0 }} / {{ field.options ? field.options.length : 0 }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Grid de grupos de permisos -->
        <div
            class="relative border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 p-6 max-h-96 overflow-y-auto">
            <!-- Grid responsivo mejorado -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <!-- Cada grupo (padre + hijos) -->
                <div v-for="(group, index) in permissionsHierarchy" :key="index"
                    class="border rounded-lg overflow-hidden transition-all duration-200 flex flex-col"
                    :class="isParentSelected(group)
                        ? 'border-blue-400 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 dark:border-blue-400'
                        : 'border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">

                    <!-- Header compacto (Padre) -->
                    <div v-if="group.parent" @click="toggleParentWithChildren(group)"
                        class="px-4 py-3 cursor-pointer transition-colors flex items-center justify-between" :class="isParentSelected(group)
                            ? 'bg-blue-100/50 dark:bg-blue-900/30'
                            : 'bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700'">

                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <input type="checkbox" :checked="isParentSelected(group)"
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer pointer-events-none flex-shrink-0" />

                            <h3 class="uppercase text-sm font-semibold text-gray-900 dark:text-gray-100 truncate"
                                :class="isParentSelected(group) ? 'text-blue-700 dark:text-blue-300' : ''">
                                {{ group.parent.text }}
                            </h3>
                        </div>
                    </div>

                    <!-- Permisos hijos en lista compacta -->
                    <div v-if="group.children.length > 0" class="px-3 py-2 space-y-1 flex-1 overflow-y-auto max-h-64">
                        <div v-for="child in group.children" :key="child.value"
                            class="flex items-center gap-2.5 py-2 px-3 rounded-md cursor-pointer transition-all group"
                            :class="isSelected(child.value)
                                ? 'bg-blue-100 dark:bg-blue-900/30'
                                : 'hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                            @click="toggleChild(child.value, group.parent?.value, group)">

                            <input type="checkbox" :checked="isSelected(child.value)"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer pointer-events-none flex-shrink-0" />

                            <span class="text-sm text-gray-700 dark:text-gray-300 flex-1 leading-tight"
                                :class="isSelected(child.value) ? 'font-medium text-blue-900 dark:text-blue-100' : ''">
                                {{ formatPermissionText(child.text) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Estado vacío -->
                <div v-if="permissionsHierarchy.length === 0"
                    class="col-span-full flex flex-col items-center justify-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                    <svg class="w-16 h-16 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                        No hay permisos disponibles
                    </p>
                </div>
            </div>

            <!-- Mensaje cuando no hay permisos -->
            <div v-if="permissionsHierarchy.length === 0" class="flex flex-col items-center justify-center py-16">
                <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <p class="text-base text-gray-600 dark:text-gray-400 font-medium">
                    No hay permisos disponibles
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                    Los permisos aparecerán aquí cuando estén configurados
                </p>
            </div>
        </div>

        <!-- Mensaje de error si es requerido -->
        <div v-if="error"
            class="mt-2 flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm text-red-600 dark:text-red-400 font-medium">
                {{ error }}
            </span>
        </div>
    </div>
</template>
