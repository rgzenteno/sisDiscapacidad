<script setup>
import InputLabel from "@/components/InputLabel.vue";
import DateField from "./DateField.vue";
import flatpickr from "flatpickr";
import Rutas from "@/components/Rutas.vue";
import { onMounted, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";

// Definir las referencias
const datepicker = ref(null); // Ref para el input del datepicker

onMounted(() => {
    if (datepicker.value) {
        flatpickr(datepicker.value, {
            locale: Spanish,
            theme: 'dark',
        });
    }
});

const props = defineProps({
    fields: Array,
    submitRoute: String,
});

// Emitir eventos hacia el componente padre
const emit = defineEmits(['add', 'close']);

// Función para cerrar el modal
const cerrarForm = () => {
    emit('close');
    emit('cancel');
}

const omitir = () => {
    emit('omitir');
}

// Inicializa el formulario con campos dinámicos
const form = useForm((props.fields || []).reduce((acc, field) => {
  console.log('Field:', field); // Añade este log
  acc[field.name] = ''; // Inicializa cada campo como vacío
  return acc;
}, {}));

const submit = () => {
    axios.post(route(props.submitRoute), form)
        .then((response) => {
            form.reset();
            emit('add');
        })
        .catch((error) => {
            console.error("Error al enviar el formulario:", error.response.data);  // Más detalles del error
        });
    /* form.post(route(props.submitRoute), {
        onSuccess: (response) => {
            form.reset();
            emit('add');
            emit('close');
        },
        onError: (error) => {
            console.error("Error al enviar el formulario:", error);
        }
    }); */
};
</script>

<template>
<main id="Form" class="flex-1 overflow-x-hidden overflow-y-auto rounded m-1 mt-0 ml-0">
    
    <section class="flex">
        <div class="w-full pb-0 px-4 mx-auto lg:px-2">
            <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
                <div class="flex-row items-center justify-between p-4 space-y-3 sm:flex sm:space-y-0 sm:space-x-4">
                    
                </div>
            </div>
        </div>
    </section>
    <div class="p-3">
        <h3 class=""></h3>
    </div>
    <!-- Form body -->
    <div class="flex justify-center">
        <form @submit.prevent="submit" class="flex flex-col md:pl-5 md:pr-5 p-4 md:pb-5">
            <div v-for="(field, index) in fields" :key="index" class="grid gap-4 grid-cols-2">
                <div class="col-span-2 p-0 mb-2">
                    <InputLabel :for="field.name" :value="field.label" class="block text-sm font-medium text-gray-900 dark:text-white" />
                    <div class="relative max-w-sm">
                        
                        <!-- Calendario / date-->
                        <DateField v-if="field.typeInput === 'date'" id="default-datepicker" v-model="form[field.name]" type="field.type" placeholder="Seleccionar fecha" />
                        

                        <!-- Numero / number -->
                        <input v-if="field.typeInput === 'number'" :type="field.type" :id="field.name" :placeholder="'Ingrese ' + field.placeholder" v-model="form[field.name]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" :required="field.required">

                        <!-- Texto / text-->
                        <input v-if="field.typeInput === 'text'" :type="field.type" :id="field.name" :placeholder="'Ingrese ' + field.placeholder" v-model="form[field.name]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" :required="field.required" :readonly="field.readonly" />

                        <!-- Dropdown / select -->
                        <select v-if="field.typeInput === 'select'" :id="field.name" v-model="form[field.name]" :required="field.required" aria-required="true" aria-label="Dropdown for {{ field.name }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                            <option hidden value>Selecionar {{ field.name }}</option>
                            <option v-for="option in field.options" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>

                        <!-- párrafo / aria-->
                        <textarea v-if="field.typeInput === 'textarea'" v-model="form[field.name]" :id="field.name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" :required="field.required" aria-required="true" :placeholder="'Ingrese ' + field.placeholder" aria-label="Ingresa {{ field.label }} (máximo {{ field.maxLength }} caracteres)" :maxlength="field.maxLength"></textarea>

                        <!-- Texto / text-->                      
                        <input v-if="field.typeInput === 'id'" v-model="props.nombreFor" class="capitalize bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" :readonly="field.readonly" />

                    </div>
                </div>
            </div>
            <div class="flex items-center">
                <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Agregar
                </button>
                <a @click="cerrarForm()" class="cursor-pointer ml-1 text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Cancelar
                </a>
                <a v-if="props.botonOmitir" @click="omitir()" class="cursor-pointer ml-1 text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Omitir
                </a>
            </div>
        </form>
    </div>
</main>
</template>
