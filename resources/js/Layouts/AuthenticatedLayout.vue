<!-- AuthenticatedLayout.vue -->
<script setup>
import FlashMessage from '@/components/FlashMessage.vue'
</script>

<template>

    <div>
        <FlashMessage />

        <nav>

        </nav>

        <main>
            <slot />
        </main>
    </div>
</template>
