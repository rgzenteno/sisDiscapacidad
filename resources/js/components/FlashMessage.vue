<script setup>
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const show = ref(false)

const message = computed(() => {
    return page.props.flash?.success || page.props.flash?.error || null
})

const type = computed(() => {
    if (page.props.flash?.success) return 'success'
    if (page.props.flash?.error) return 'error'
    return null
})

const bgColor = computed(() => {
    return type.value === 'success' 
        ? 'bg-green-100 border-green-400 text-green-700' 
        : 'bg-red-100 border-red-400 text-red-700'
})

watch(message, (newVal) => {
    if (newVal) {
        show.value = true
        setTimeout(() => {
            show.value = false
        }, 5000)
    }
})
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
    >
        <div 
            v-if="show && message"
            class="fixed top-4 right-4 z-50 max-w-md"
        >
            <div 
                :class="bgColor"
                class="p-4 rounded-lg shadow-lg border flex items-start"
            >
                <div class="flex-1">
                    {{ message }}
                </div>
                <button 
                    @click="show = false"
                    class="ml-4 text-xl font-bold opacity-70 hover:opacity-100"
                >
                    ×
                </button>
            </div>
        </div>
    </Transition>
</template>