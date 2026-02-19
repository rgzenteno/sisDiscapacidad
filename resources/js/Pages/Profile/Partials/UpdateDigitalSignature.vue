<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Firma Digital</h2>
            <p class="mt-1 text-sm text-gray-600">
                Sube tu firma digital que se utilizará en los documentos.
            </p>
        </header>

        <form @submit.prevent="submitSignature" class="mt-6 space-y-6">
            <!-- Área de carga de imagen -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Imagen de Firma
                </label>

                <div
                    @drop.prevent="handleDrop"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @click="triggerFileInput"
                    :class="[
                        'border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors',
                        isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'
                    ]"
                >
                    <!-- Preview de la imagen -->
                    <div v-if="previewUrl" class="mb-4">
                        <img
                            :src="previewUrl"
                            alt="Vista previa de firma"
                            class="max-h-40 mx-auto border border-gray-200 rounded"
                        />
                    </div>

                    <!-- Imagen actual del usuario -->
                    <div v-else-if="user.digital_signature && !selectedFile" class="mb-4">
                        <img
                            :src="`/${user.digital_signature}`"
                            alt="Firma actual"
                            class="max-h-40 mx-auto border border-gray-200 rounded"
                        />
                        <p class="mt-2 text-sm text-gray-500">Firma actual</p>
                    </div>

                    <!-- Icono de upload -->
                    <div v-else>
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <div class="mt-4 text-sm text-gray-600">
                        <p class="font-semibold">
                            {{ selectedFile ? selectedFile.name : 'Arrastra tu firma aquí o haz clic para seleccionar' }}
                        </p>
                        <p class="mt-1">PNG, JPG hasta 2MB</p>
                    </div>

                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/png,image/jpeg,image/jpg"
                        @change="handleFileSelect"
                        class="hidden"
                    />
                </div>

                <p v-if="form.errors.signature" class="mt-2 text-sm text-red-600">
                    {{ form.errors.signature }}
                </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center gap-4">
                <Button :disabled="form.processing || !selectedFile"
                    :class="[
                        'px-4 py-2 bg-blue-600 text-white rounded-md font-semibold text-sm transition-colors',
                        form.processing || !selectedFile
                            ? 'opacity-50 cursor-not-allowed'
                            : 'hover:bg-blue-700'
                    ]">
                    {{ form.processing ? 'Guardando...' : 'Guardar Firma' }}
                </Button>

                <Button v-if="user.digital_signature"
                    type="button"
                    @click="deleteSignature"
                    :disabled="form.processing"
                    class="px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-sm hover:bg-red-700 transition-colors disabled:opacity-50">
                    Eliminar Firma
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">
                        Guardado correctamente.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Button from '@/components/Button.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const fileInput = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
const isDragging = ref(false);

const form = useForm({
    signature: null
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        processFile(file);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        processFile(file);
    }
};

const processFile = (file) => {
    // Validar tamaño (2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('El archivo es demasiado grande. Máximo 2MB.');
        return;
    }

    selectedFile.value = file;
    form.signature = file;

    // Crear preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const submitSignature = () => {
    form.post(route('profile.signature.update'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedFile.value = null;
            previewUrl.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    });
};

const deleteSignature = () => {
    if (confirm('¿Estás seguro de eliminar tu firma digital?')) {
        form.delete(route('profile.signature.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                selectedFile.value = null;
                previewUrl.value = null;
            }
        });
    }
};

// Limpiar preview si se cancela el formulario
watch(() => form.recentlySuccessful, (value) => {
    if (value) {
        setTimeout(() => {
            form.recentlySuccessful = false;
        }, 2000);
    }
});
</script>
