<script setup lang="ts">
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface PeriodSummary {
    id: number;
    code: string;
    name: string;
    year: number;
    term_of_year: number;
    starts_on: string;
    ends_on: string;
    weeks: number;
    status: 'draft' | 'published' | 'closed';
    status_label: string;
}

interface PeriodStats {
    total_sections: number;
    sections_without_teacher: number;
    sections_without_schedule: number;
    active_groups: number;
    unconfirmed_groups: number;
}

interface Recommendation {
    tone: 'warning' | 'info' | 'success';
    title: string;
    description: string;
    action_label: string;
    href: string;
}

const props = defineProps<{
    periods: PeriodSummary[];
    selectedPeriod: PeriodSummary | null;
    stats: PeriodStats | null;
    recommendations: Recommendation[];
}>();

const selectedPeriodId = ref<string>(props.selectedPeriod ? String(props.selectedPeriod.id) : '');

watch(
    () => props.selectedPeriod?.id,
    (id) => {
        selectedPeriodId.value = id ? String(id) : '';
    },
);

function changePeriod(): void {
    if (!selectedPeriodId.value) {
        return;
    }
    router.get(route('dashboard'), { period_id: selectedPeriodId.value }, { preserveState: false });
}

function statusBadgeClass(status: string): string {
    switch (status) {
        case 'published':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
        case 'closed':
            return 'bg-gray-100 text-gray-700 ring-gray-500/20';
        default:
            return 'bg-amber-50 text-amber-800 ring-amber-600/20';
    }
}

const quickCatalogLinks = [
    { label: 'Carreras y planes', routeName: 'catalogs.programs.index' },
    { label: 'Materias', routeName: 'catalogs.courses.index' },
    { label: 'Docentes', routeName: 'catalogs.teachers.index' },
    { label: 'Aulas', routeName: 'catalogs.classrooms.index' },
    { label: 'Turnos y bloques', routeName: 'catalogs.shifts.index' },
];
</script>

<template>
    <Head title="Inicio" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Inicio — Panel del periodo
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Bienvenido, {{ $page.props.auth.user?.name }} ({{ $page.props.auth.user?.role_label }}).
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div v-if="periods.length > 0" class="flex items-center gap-2">
                        <label for="dashboard-period-selector" class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Periodo:
                        </label>
                        <select
                            id="dashboard-period-selector"
                            v-model="selectedPeriodId"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="changePeriod"
                        >
                            <option v-for="period in periods" :key="period.id" :value="String(period.id)">
                                {{ period.code }} — {{ period.name }}
                            </option>
                        </select>
                        <span
                            v-if="selectedPeriod"
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                statusBadgeClass(selectedPeriod.status),
                            ]"
                        >
                            {{ selectedPeriod.status_label }}
                        </span>
                    </div>

                    <Link
                        :href="
                            route('periods.wizard', {
                                source_period_id: selectedPeriod?.id ?? undefined,
                            })
                        "
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                    >
                        + Crear siguiente periodo
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Estado vacío sin periodos -->
                <div
                    v-if="!selectedPeriod || !stats"
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm"
                >
                    <h2 class="text-lg font-semibold text-gray-900">
                        Aún no hay periodos académicos registrados
                    </h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-gray-600">
                        Crea tu primer periodo académico para comenzar a registrar grupos y planear secciones de clases.
                    </p>
                    <div class="mt-6">
                        <Link :href="route('periods.wizard')">
                            <PrimaryButton>Crear primer periodo</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <template v-else>
                    <!-- Tarjetas de resumen del periodo -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Clases / Secciones totales
                            </p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                {{ stats.total_sections }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Clases programables del periodo (1 CRN c/u) · {{ selectedPeriod.weeks }} sem. lectivas
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Sin docente (presenciales)
                            </p>
                            <p
                                :class="[
                                    'mt-2 text-3xl font-bold',
                                    stats.sections_without_teacher > 0 ? 'text-amber-600' : 'text-emerald-600',
                                ]"
                            >
                                {{ stats.sections_without_teacher }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Las materias en línea no requieren docente
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Sin horario o aula
                            </p>
                            <p
                                :class="[
                                    'mt-2 text-3xl font-bold',
                                    stats.sections_without_schedule > 0 ? 'text-amber-600' : 'text-emerald-600',
                                ]"
                            >
                                {{ stats.sections_without_schedule }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Secciones sin sesión o sin salón asignado
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Grupos del periodo
                            </p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                {{ stats.active_groups }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{
                                    stats.unconfirmed_groups > 0
                                        ? `${stats.unconfirmed_groups} con alumnos por confirmar`
                                        : 'Matrícula confirmada en todos los grupos'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Siguiente paso recomendado -->
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-gray-900">
                            Siguiente paso recomendado
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Periodo {{ selectedPeriod.code }} ({{ selectedPeriod.starts_on }} al {{ selectedPeriod.ends_on }})
                        </p>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="(item, index) in recommendations"
                                :key="index"
                                :class="[
                                    'flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between',
                                    item.tone === 'warning'
                                        ? 'border-amber-200 bg-amber-50/70'
                                        : item.tone === 'success'
                                          ? 'border-emerald-200 bg-emerald-50/70'
                                          : 'border-indigo-200 bg-indigo-50/70',
                                ]"
                            >
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        {{ item.title }}
                                    </h3>
                                    <p class="mt-0.5 text-sm text-gray-700">
                                        {{ item.description }}
                                    </p>
                                </div>

                                <Link
                                    :href="item.href"
                                    class="inline-flex shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                >
                                    {{ item.action_label }} →
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Accesos rápidos a catálogos -->
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Accesos directos a catálogos
                    </h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <Link
                            v-for="cat in quickCatalogLinks"
                            :key="cat.routeName"
                            :href="route(cat.routeName)"
                            class="rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            {{ cat.label }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
