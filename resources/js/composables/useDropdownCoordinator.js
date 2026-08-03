import { ref } from 'vue';

// Estado compartido (singleton por módulo ES) entre todas las instancias de
// Dropdown.vue de la app: solo el dropdown cuyo id coincide con
// activeDropdownId puede estar abierto. Al abrir uno, los demás se cierran.
const activeDropdownId = ref(null);
let contador = 0;

export function useDropdownCoordinator() {
    const id = ++contador;
    return { activeDropdownId, id };
}
