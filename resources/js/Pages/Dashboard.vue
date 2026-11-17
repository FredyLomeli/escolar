<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const quickLinks = [
    {
        title: 'Catálogos base',
        description: 'Carreras, planes de estudio, materias, docentes, aulas y turnos.',
        href: 'catalogs.programs.index',
        badge: 'Fase 1',
    },
    {
        title: 'Grupos del periodo',
        description: 'Generaciones activas, cuatrimestres y matrícula estimada.',
        href: 'groups.index',
        badge: 'Fase 2',
    },
    {
        title: 'Tablero de planeación',
        description: 'Cuadrícula semanal por grupo, docente y aula con alertas en vivo.',
        href: 'planning.index',
        badge: 'Fase 4',
    },
    {
        title: 'Importar historial',
        description: 'Carga única de archivos de Excel actuales (Matutino, Vespertino y Sabatino).',
        href: 'import.index',
        badge: 'Fase 5',
    },
];
</script>

<template>
    <Head title="Inicio" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Inicio — Panel del periodo
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Bienvenido, {{ $page.props.auth.user?.name }} ({{ $page.props.auth.user?.role_label }}).
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Estado inicial -->
                <div class="rounded-lg border border-indigo-100 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                Base del sistema lista (Fase 0)
                            </span>
                            <h2 class="mt-3 text-lg font-semibold text-gray-900">
                                Aún no hay un periodo académico configurado
                            </h2>
                            <p class="mt-1 max-w-2xl text-sm text-gray-600">
                                La estructura principal, la autenticación por roles y la navegación ya están operativas.
                                El siguiente paso del plan será habilitar los catálogos institucionales (Fase 1) para
                                capturar carreras, planes de estudio, materias, docentes, aulas y turnos.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Accesos rápidos a las secciones -->
                <div>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">
                        Secciones del sistema
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="item in quickLinks"
                            :key="item.href"
                            :href="route(item.href)"
                            class="group flex flex-col justify-between rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-base font-semibold text-gray-900 group-hover:text-indigo-600">
                                        {{ item.title }}
                                    </span>
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                        {{ item.badge }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">
                                    {{ item.description }}
                                </p>
                            </div>
                            <span class="mt-4 inline-flex items-center text-xs font-medium text-indigo-600 group-hover:text-indigo-700">
                                Abrir sección →
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
