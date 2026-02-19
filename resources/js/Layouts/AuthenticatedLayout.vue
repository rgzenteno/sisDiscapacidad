<!-- AuthenticatedLayout.vue -->
<script setup>
import FlashMessage from '@/components/FlashMessage.vue'
// ... tus otros imports
</script>

<template>
    <!-- ✅ UN SOLO elemento raíz (div) -->
    <div>
        <FlashMessage />
        
        <nav>
            <!-- tu navegación -->
        </nav>
        
        <main>
            <slot />
        </main>
    </div>
</template>