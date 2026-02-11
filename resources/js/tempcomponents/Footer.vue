<template>
    <footer class="bg-white border border-gray-200 rounded-lg shadow mr-1 mb-1 dark:bg-gray-900">
        <!-- En móvil: padding reducido, en desktop: padding original -->
        <div class="w-full mx-auto max-w-screen-xl p-2 py-1 md:p-4 md:py-1.5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-center">
                <!-- Texto más pequeño en móvil -->
                <span class="text-xs md:text-sm text-gray-700 text-center md:text-left dark:text-gray-300">
                    © {{ new Date().getFullYear() }} UMADIS - GOBIERNO AUTÓNOMO MUNICIPAL DE SACABA.
                </span>
            </div>
        </div>
    </footer>
    </template>
