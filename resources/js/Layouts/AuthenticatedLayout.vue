<script setup lang="ts">
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const catalogItems = [
    { label: 'Carreras y planes', routeName: 'catalogs.programs.index' },
    { label: 'Materias', routeName: 'catalogs.courses.index' },
    { label: 'Docentes', routeName: 'catalogs.teachers.index' },
    { label: 'Aulas', routeName: 'catalogs.classrooms.index' },
    { label: 'Turnos y bloques', routeName: 'catalogs.shifts.index' },
    { label: 'Parámetros', routeName: 'catalogs.settings.index' },
];
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-gray-200 bg-white">
                <!-- Menú principal de navegación -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo y título -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('dashboard')"
                                    class="flex items-center gap-2.5 text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-md"
                                >
                                    <ApplicationLogo
                                        class="block h-8 w-auto fill-current text-indigo-600"
                                    />
                                    <span class="hidden text-sm font-semibold tracking-tight lg:inline">
                                        Planeación Académica
                                    </span>
                                </Link>
                            </div>

                            <!-- Enlaces de navegación (escritorio) -->
                            <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex sm:items-stretch">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Inicio
                                </NavLink>

                                <NavLink
                                    :href="route('planning.index')"
                                    :active="route().current('planning.*')"
                                >
                                    Planeación
                                </NavLink>

                                <NavLink
                                    :href="route('groups.index')"
                                    :active="route().current('groups.*')"
                                >
                                    Grupos
                                </NavLink>

                                <!-- Desplegable Catálogos -->
                                <div class="inline-flex items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="[
                                                    'inline-flex h-16 items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none',
                                                    route().current('catalogs.*')
                                                        ? 'border-indigo-400 text-gray-900 focus:border-indigo-700'
                                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700',
                                                ]"
                                            >
                                                Catálogos
                                                <svg
                                                    class="-me-0.5 ms-1.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink
                                                v-for="item in catalogItems"
                                                :key="item.routeName"
                                                :href="route(item.routeName)"
                                                :class="{
                                                    'bg-indigo-50 font-medium text-indigo-700':
                                                        route().current(item.routeName),
                                                }"
                                            >
                                                {{ item.label }}
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <NavLink
                                    :href="route('import.index')"
                                    :active="route().current('import.*')"
                                >
                                    Importar
                                </NavLink>

                                <NavLink
                                    v-if="$page.props.auth.user?.is_admin"
                                    :href="route('users.index')"
                                    :active="route().current('users.*')"
                                >
                                    Usuarios
                                </NavLink>
                            </div>
                        </div>

                        <!-- Menú de usuario y rol (escritorio) -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-3">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    $page.props.auth.user?.is_admin
                                        ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20'
                                        : 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
                                ]"
                            >
                                {{ $page.props.auth.user?.role_label }}
                            </span>

                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-600 transition duration-150 ease-in-out hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                            >
                                                {{ $page.props.auth.user?.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Mi perfil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Cerrar sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Botón menú móvil -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                type="button"
                                aria-label="Abrir menú de navegación"
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú de navegación móvil -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Inicio
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('planning.index')"
                            :active="route().current('planning.*')"
                        >
                            Planeación
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('groups.index')"
                            :active="route().current('groups.*')"
                        >
                            Grupos
                        </ResponsiveNavLink>

                        <div class="border-t border-gray-100 pt-2">
                            <div class="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                Catálogos
                            </div>
                            <ResponsiveNavLink
                                v-for="item in catalogItems"
                                :key="item.routeName"
                                :href="route(item.routeName)"
                                :active="route().current(item.routeName)"
                            >
                                {{ item.label }}
                            </ResponsiveNavLink>
                        </div>

                        <div class="border-t border-gray-100 pt-2">
                            <ResponsiveNavLink
                                :href="route('import.index')"
                                :active="route().current('import.*')"
                            >
                                Importar
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="$page.props.auth.user?.is_admin"
                                :href="route('users.index')"
                                :active="route().current('users.*')"
                            >
                                Usuarios
                            </ResponsiveNavLink>
                        </div>
                    </div>

                    <!-- Opciones de usuario móvil -->
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="flex items-center justify-between">
                                <div class="text-base font-medium text-gray-800">
                                    {{ $page.props.auth.user?.name }}
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700"
                                >
                                    {{ $page.props.auth.user?.role_label }}
                                </span>
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user?.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Mi perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Cerrar sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Encabezado de página -->
            <header class="bg-white shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Contenido principal -->
            <main>
                <FlashBanner />
                <slot />
            </main>
        </div>
    </div>
</template>
