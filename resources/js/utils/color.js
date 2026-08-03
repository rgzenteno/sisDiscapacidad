// Utilidades de color puras (sin dependencia de Vue), usadas para derivar
// tonos a partir de un único hex (ver composables/useColorSistema.js).

export function hexToRgb(hex) {
    const limpio = hex.replace('#', '');
    const numero = parseInt(limpio, 16);
    return {
        r: (numero >> 16) & 255,
        g: (numero >> 8) & 255,
        b: numero & 255,
    };
}

export function darkenRgb({ r, g, b }, factor) {
    return {
        r: Math.round(r * factor),
        g: Math.round(g * factor),
        b: Math.round(b * factor),
    };
}

export function rgbToChannels({ r, g, b }) {
    return `${r}, ${g}, ${b}`;
}

export function lightenRgb({ r, g, b }, ratio) {
    return {
        r: Math.round(r + (255 - r) * ratio),
        g: Math.round(g + (255 - g) * ratio),
        b: Math.round(b + (255 - b) * ratio),
    };
}

/**
 * Escala de tintes/sombras estilo Tailwind (50-900) derivada de un único
 * hex — mismo propósito que darkenRgb, pero cubre todos los matices que
 * antes venían del azul fijo de Tailwind (bg-blue-50, text-blue-700, etc.)
 * en vistas con muchos estados (fondos claros, texto, bordes, hover).
 * 500 y 900 quedan idénticos a rgbToChannels(rgb) y darkenRgb(rgb, 0.42)
 * respectivamente, para no duplicar esos dos tonos ya usados en otras vistas.
 */
const TINTES = { 50: 0.94, 100: 0.86, 200: 0.7, 300: 0.52, 400: 0.28 };
const SOMBRAS = { 600: 0.85, 700: 0.68, 800: 0.55, 900: 0.42, 950: 0.3 };

export function generarEscala(rgb) {
    const escala = { 500: rgbToChannels(rgb) };
    for (const [parada, ratio] of Object.entries(TINTES)) {
        escala[parada] = rgbToChannels(lightenRgb(rgb, ratio));
    }
    for (const [parada, factor] of Object.entries(SOMBRAS)) {
        escala[parada] = rgbToChannels(darkenRgb(rgb, factor));
    }
    return escala;
}
