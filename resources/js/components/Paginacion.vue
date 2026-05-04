<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: Array,
    from: { type: Number, default: 1 },
    to: { type: Number, default: 4 },
    total: { type: Number, default: 0 }
});

// ✅ 1. AGREGA ESTA FUNCIÓN AQUÍ
const normalizeLabel = (label) => {
    if (!label) return label;
    if (label.includes('pagination.previous') || label === '&laquo; Previous') return '&laquo;';
    if (label.includes('pagination.next') || label === 'Next &raquo;') return '&raquo;';
    return label;
};

const getPageNumber = (label) => {
    if (!label || typeof label !== 'string') return null;
    const num = parseInt(label);
    return isNaN(num) ? null : num;
};

const processedLinks = computed(() => {
    if (!props.links || props.links.length <= 2) return props.links;

    const result = [];

    // ✅ 2. CAMBIA ESTA LÍNEA (antes: result.push(props.links[0]);)
    result.push({ ...props.links[0], label: normalizeLabel(props.links[0].label) });

    const numericLinks = props.links.slice(1, -1).filter(link => {
        const pageNum = getPageNumber(link.label);
        return pageNum !== null;
    });

    const activeIndex = numericLinks.findIndex(link => link.active);
    const activePage = activeIndex !== -1 ? getPageNumber(numericLinks[activeIndex].label) : 1;
    const totalPages = numericLinks.length;

    if (totalPages > 0 && activePage > 2) {
        result.push(numericLinks[0]);
        if (activePage > 3) {
            result.push({ url: null, label: '...', active: false });
        }
    }

    let start = Math.max(0, activeIndex - 1);
    let end = Math.min(totalPages - 1, start + 2);

    if (end - start < 2 && start > 0) {
        start = Math.max(0, end - 2);
    }

    for (let i = start; i <= end; i++) {
        if (i < numericLinks.length) {
            result.push(numericLinks[i]);
        }
    }

    if (end < totalPages - 1) {
        result.push({ url: null, label: '...', active: false });
        result.push(numericLinks[numericLinks.length - 1]);
    }

    // ✅ 3. CAMBIA ESTAS DOS LÍNEAS (antes: result.push(props.links[props.links.length - 1]);)
    const lastLink = props.links[props.links.length - 1];
    result.push({ ...lastLink, label: normalizeLabel(lastLink.label) });

    return result;
});
</script>

<template>
    <nav v-if="links && links.length > 0" class="bg-gray-50 rounded-lg mr-1 flex flex-wrap items-center justify-between gap-2 p-2 py-1" aria-label="Navegación de tabla">
        <!-- Texto informativo -->
        <span class="text-xs sm:text-sm font-normal text-gray-900 dark:text-gray-700 flex-shrink-0">
            Mostrando <span class="font-semibold text-gray-900">{{ from }}-{{ to }}</span> de
            <span class="font-semibold text-gray-900">{{ total }}</span>
        </span>

        <!-- Contenedor de paginación -->
        <ul class="inline-flex -space-x-px rtl:space-x-reverse text-xs sm:text-sm h-6 sm:h-7 flex-shrink-0">
            <template v-for="(link, linkIndex) in processedLinks" :key="linkIndex">
                <!-- Enlaces deshabilitados -->
                <li v-if="link.url === null">
                    <span
                        class="flex items-center justify-center px-2 sm:px-3 h-7 ms-0 leading-tight text-gray-500 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                        :class="{
                            'rounded-s-lg': linkIndex === 0,
                            'rounded-e-lg': linkIndex === processedLinks.length - 1
                        }">
                        <span v-html="link.label"></span>
                    </span>
                </li>
                <!-- Enlaces activos -->
                <li v-else>
                    <Link :href="link.url"
                        :class="[
                            'flex items-center justify-center px-2 sm:px-3 h-7 leading-tight border',
                            {
                                'rounded-s-lg': linkIndex === 0,
                                'rounded-e-lg': linkIndex === processedLinks.length - 1,
                                'text-blue-600 border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white': link.active,
                                'text-gray-500 bg-white border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white': !link.active
                            }
                        ]">
                        <span v-html="link.label"></span>
                    </Link>
                </li>
            </template>
        </ul>
    </nav>
</template>
