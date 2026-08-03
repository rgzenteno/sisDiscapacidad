<script setup>
// ============================================================================
// Overlay de carga global — se monta una sola vez en AppLayout.vue y se
// controla desde cualquier parte del sistema con el composable Preloader
// (usePreloader.js): Preloader.show(...) / setMessage(...) / hide().
// ============================================================================
import { computed, watch, onUnmounted } from 'vue';
import { Preloader } from '@/composables/usePreloader';

const visible = computed(() => Preloader.state.visible);
const mensaje = computed(() => Preloader.state.message);

// Bloquea el scroll del body mientras el overlay está activo — el usuario no
// debe poder interactuar con nada de lo que queda debajo.
watch(visible, (esVisible) => {
    document.body.style.overflow = esVisible ? 'hidden' : '';
}, { immediate: true });

onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Transition name="preloader-fade">
        <div v-if="visible" class="preloader-overlay">
            <div class="preloader-brand">
                <div class="preloader-logo-wrap">
                    <span class="preloader-glow" aria-hidden="true"></span>
                    <img src="/logo.png" alt="" class="preloader-logo" />
                </div>
                <span class="preloader-wordmark">UMADIS</span>
            </div>

            <p class="preloader-message" role="status" aria-live="polite">
                {{ mensaje }}
                <span class="preloader-dots" aria-hidden="true"><span></span><span></span><span></span></span>
            </p>
        </div>
    </Transition>
</template>

<style scoped>
.preloader-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    touch-action: none;
}

.preloader-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.preloader-logo-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
}

.preloader-logo {
    position: relative;
    z-index: 1;
    width: 56px;
    height: auto;
    animation: preloader-pulse 1.4s ease-in-out infinite;
}

.preloader-glow {
    position: absolute;
    inset: -35%;
    border-radius: 9999px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0) 70%);
    animation: preloader-glow-pulse 1.4s ease-in-out infinite;
}

.preloader-wordmark {
    font-size: 2.25rem;
    line-height: 1;
    font-weight: 700;
    color: #f8fafc;
    letter-spacing: 0.02em;
    text-shadow: -3px 3px 4px rgba(0, 0, 0, 0.45);
}

.preloader-message {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 0.875rem;
    color: #cbd5e1;
    text-align: center;
    padding: 0 1rem;
}

.preloader-dots span {
    display: inline-block;
    opacity: 0.2;
    animation: preloader-dot-blink 1.4s infinite both;
}

.preloader-dots span:nth-child(1) {
    animation-delay: 0s;
}

.preloader-dots span:nth-child(2) {
    animation-delay: 0.2s;
}

.preloader-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

.preloader-dots span::before {
    content: '.';
}

@keyframes preloader-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

@keyframes preloader-glow-pulse {
    0%, 100% { transform: scale(0.85); opacity: 0.4; }
    50% { transform: scale(1.15); opacity: 0.85; }
}

@keyframes preloader-dot-blink {
    0%, 80%, 100% { opacity: 0.2; }
    40% { opacity: 1; }
}

.preloader-fade-enter-active {
    transition: opacity 0.25s ease;
}

.preloader-fade-leave-active {
    transition: opacity 0.35s ease;
}

.preloader-fade-enter-from,
.preloader-fade-leave-to {
    opacity: 0;
}

/* Pantallas chicas: logo y texto apilados */
@media (max-width: 420px) {
    .preloader-brand {
        flex-direction: column;
        gap: 0.5rem;
    }
}

/* Respeta prefers-reduced-motion: logo estático, sin pulso ni halo */
@media (prefers-reduced-motion: reduce) {
    .preloader-logo {
        animation: none;
        transition: opacity 0.3s ease;
    }
    .preloader-glow {
        animation: none;
        opacity: 0.4;
    }
    .preloader-dots span {
        animation: none;
        opacity: 1;
    }
    .preloader-fade-enter-active,
    .preloader-fade-leave-active {
        transition: opacity 0.15s linear;
    }
}
</style>
