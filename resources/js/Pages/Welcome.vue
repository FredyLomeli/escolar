<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
}>();

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Planeación Académica" />

    <div class="flex min-h-screen flex-col justify-between bg-gray-50 text-gray-900">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-8 w-auto fill-current text-indigo-600" />
                    <span class="text-base font-semibold tracking-tight text-gray-900">
                        Planeación Académica
                    </span>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Ir al panel
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Iniciar sesión
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-5xl flex-1 items-center px-6 py-16">
            <div class="max-w-2xl">
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                    Gestión cuatrimestral
                </span>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Sistema de Planeación Académica
                </h1>
                <p class="mt-4 text-base leading-relaxed text-gray-600">
                    Organiza cada cuatrimestre de forma guiada: asigna docentes y aulas, detecta choques de horario y
                    capacidades en tiempo real, y revisa propuestas basadas en el historial institucional.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Ir al tablero principal
                    </Link>
                    <Link
                        v-else-if="canLogin"
                        :href="route('login')"
                        class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Iniciar sesión
                    </Link>
                </div>
            </div>
        </main>

        <footer class="border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-500">
            © {{ currentYear }} Sistema de Planeación Académica · Todos los derechos reservados · escolar.frexal.net
        </footer>
    </div>
</template>
