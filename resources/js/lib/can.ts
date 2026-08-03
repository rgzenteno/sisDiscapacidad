import { usePage } from "@inertiajs/vue3";

export function can(permission: string): boolean {
    const page = usePage();

    const permissions: string[] = page.props.auth?.permissions ?? [];

    return permissions.includes(permission);
}

/**
 * Verifica el rol real del usuario (jerarquía), no un permiso — un permiso
 * puede estar asignado a otro rol por error de configuración, pero el rol
 * en sí no.
 */
export function hasRole(role: string): boolean {
    const page = usePage();

    const roles: string[] = page.props.auth?.roles ?? [];

    return roles.includes(role);
}