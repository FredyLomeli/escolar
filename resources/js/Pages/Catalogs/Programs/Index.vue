<script setup lang="ts">
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import type { PaginatedData } from '@/types';

interface Modality {
    id: number;
    code: string;
    name: string;
}

interface CurriculumSummary {
    id: number;
    program_id: number;
    version: string;
    valid_from: string | null;
    valid_to: string | null;
    active: boolean;
    curriculum_courses_count: number;
}

interface ProgramItem {
    id: number;
    code: string;
    name: string;
    modality_id: number;
    active: boolean;
    modality: Modality;
    curricula: CurriculumSummary[];
}

const props = defineProps<{
    programs: PaginatedData<ProgramItem>;
    modalities: Modality[];
    filters: {
        search: string;
        modality_id: number | null;
        status: string;
    };
}>();

const search = ref(props.filters.search);
const modalityFilter = ref<string>(props.filters.modality_id ? String(props.filters.modality_id) : '');
const statusFilter = ref<string>(props.filters.status);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters(): void {
    router.get(
        route('catalogs.programs.index'),
        {
            search: search.value || undefined,
            modality_id: modalityFilter.value !== '' ? modalityFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch(search, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([modalityFilter, statusFilter], applyFilters);

function resetFilters(): void {
    search.value = '';
    modalityFilter.value = '';
    statusFilter.value = 'all';
}

function formatDate(date: string | null): string {
    if (!date) {
        return '—';
    }
    return date.slice(0, 10);
}

// Modal Carrera
const showingProgramModal = ref(false);
const editingProgram = ref<ProgramItem | null>(null);

const programForm = useForm({
    code: '',
    name: '',
    modality_id: props.modalities[0]?.id ?? 1,
});

function openCreateProgram(): void {
    editingProgram.value = null;
    programForm.reset();
    programForm.clearErrors();
    programForm.modality_id = props.modalities[0]?.id ?? 1;
    showingProgramModal.value = true;
}

function openEditProgram(program: ProgramItem): void {
    editingProgram.value = program;
    programForm.clearErrors();
    programForm.code = program.code;
    programForm.name = program.name;
    programForm.modality_id = program.modality_id;
    showingProgramModal.value = true;
}

function closeProgramModal(): void {
    showingProgramModal.value = false;
    editingProgram.value = null;
}

function submitProgram(): void {
    if (editingProgram.value) {
        programForm.put(route('catalogs.programs.update', editingProgram.value.id), {
            preserveScroll: true,
            onSuccess: () => closeProgramModal(),
        });
    } else {
        programForm.post(route('catalogs.programs.store'), {
            preserveScroll: true,
            onSuccess: () => closeProgramModal(),
        });
    }
}

// Desactivar / reactivar carrera
const programToDeactivate = ref<ProgramItem | null>(null);
const toggling = ref(false);

function handleToggleProgram(program: ProgramItem): void {
    if (program.active) {
        programToDeactivate.value = program;
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.programs.toggle-active', program.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}

function confirmDeactivateProgram(): void {
    if (!programToDeactivate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.programs.toggle-active', programToDeactivate.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                programToDeactivate.value = null;
            },
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}

// Modal Plan de estudios
const curriculumProgram = ref<ProgramItem | null>(null);
const editingCurriculum = ref<CurriculumSummary | null>(null);

const curriculumForm = useForm({
    version: '',
    valid_from: '',
    valid_to: '',
    clone_from_curriculum_id: '' as string | number,
});

function openCreateCurriculum(program: ProgramItem): void {
    curriculumProgram.value = program;
    editingCurriculum.value = null;
    curriculumForm.reset();
    curriculumForm.clearErrors();
    curriculumForm.clone_from_curriculum_id = program.curricula[0]?.id ?? '';
}

function openEditCurriculum(program: ProgramItem, curriculum: CurriculumSummary): void {
    curriculumProgram.value = program;
    editingCurriculum.value = curriculum;
    curriculumForm.clearErrors();
    curriculumForm.version = curriculum.version;
    curriculumForm.valid_from = curriculum.valid_from ? curriculum.valid_from.slice(0, 10) : '';
    curriculumForm.valid_to = curriculum.valid_to ? curriculum.valid_to.slice(0, 10) : '';
    curriculumForm.clone_from_curriculum_id = '';
}

function closeCurriculumModal(): void {
    curriculumProgram.value = null;
    editingCurriculum.value = null;
}

function submitCurriculum(): void {
    if (!curriculumProgram.value) {
        return;
    }

    if (editingCurriculum.value) {
        curriculumForm.put(route('catalogs.curricula.update', editingCurriculum.value.id), {
            preserveScroll: true,
            onSuccess: () => closeCurriculumModal(),
        });
    } else {
        curriculumForm.post(route('catalogs.programs.curricula.store', curriculumProgram.value.id), {
            onSuccess: () => closeCurriculumModal(),
        });
    }
}

const curriculumToDeactivate = ref<CurriculumSummary | null>(null);

function handleToggleCurriculum(curriculum: CurriculumSummary): void {
    if (curriculum.active) {
        curriculumToDeactivate.value = curriculum;
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.curricula.toggle-active', curriculum.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}

function confirmDeactivateCurriculum(): void {
    if (!curriculumToDeactivate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.curricula.toggle-active', curriculumToDeactivate.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                curriculumToDeactivate.value = null;
            },
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Carreras y planes de estudio" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Carreras y planes de estudio
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Administra los programas académicos, sus versiones de planes y accede a la malla por cuatrimestre.
                    </p>
                </div>

                <PrimaryButton @click="openCreateProgram">
                    + Nueva carrera
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
                    <div class="w-full sm:max-w-xs">
                        <label for="program-search" class="sr-only">Buscar carrera</label>
                        <TextInput
                            id="program-search"
                            v-model="search"
                            type="search"
                            class="block w-full"
                            placeholder="Buscar por código o nombre..."
                        />
                    </div>

                    <div class="w-full sm:w-48">
                        <label for="program-modality" class="sr-only">Filtrar por modalidad</label>
                        <select
                            id="program-modality"
                            v-model="modalityFilter"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todas las modalidades</option>
                            <option v-for="mod in modalities" :key="mod.id" :value="String(mod.id)">
                                {{ mod.name }}
                            </option>
                        </select>
                    </div>

                    <div class="w-full sm:w-40">
                        <label for="program-status" class="sr-only">Filtrar por estado</label>
                        <select
                            id="program-status"
                            v-model="statusFilter"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">Todas</option>
                            <option value="active">Activas</option>
                            <option value="inactive">Inactivas</option>
                        </select>
                    </div>

                    <button
                        v-if="search !== '' || modalityFilter !== '' || statusFilter !== 'all'"
                        type="button"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        @click="resetFilters"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <!-- Estado vacío -->
                <div
                    v-if="programs.data.length === 0"
                    class="rounded-lg border border-gray-200 bg-white p-10 text-center shadow-sm"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        No se encontraron carreras
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{
                            search !== '' || modalityFilter !== '' || statusFilter !== 'all'
                                ? 'Prueba cambiando los filtros de búsqueda.'
                                : 'Registra la primera carrera y su plan de estudios para armar su malla curricular.'
                        }}
                    </p>
                    <div class="mt-4">
                        <PrimaryButton
                            v-if="search === '' && modalityFilter === '' && statusFilter === 'all'"
                            @click="openCreateProgram"
                        >
                            Registrar carrera
                        </PrimaryButton>
                        <SecondaryButton v-else @click="resetFilters">
                            Limpiar filtros
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Listado de carreras -->
                <div v-else class="space-y-4">
                    <div
                        v-for="program in programs.data"
                        :key="program.id"
                        :class="[
                            'overflow-hidden rounded-lg border bg-white shadow-sm',
                            program.active ? 'border-gray-200' : 'border-gray-200 bg-gray-50/70 opacity-80',
                        ]"
                    >
                        <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="rounded bg-indigo-600 px-2.5 py-0.5 text-xs font-bold tracking-wide text-white">
                                    {{ program.code }}
                                </span>
                                <h2 class="text-base font-semibold text-gray-900">
                                    {{ program.name }}
                                </h2>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                        program.modality.code === 'regular'
                                            ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
                                            : 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    ]"
                                >
                                    {{ program.modality.name }}
                                </span>
                                <StatusBadge
                                    :active="program.active"
                                    active-label="Activa"
                                    inactive-label="Inactiva"
                                />
                            </div>

                            <div class="flex flex-wrap items-center gap-4 text-sm font-medium">
                                <button
                                    type="button"
                                    class="text-indigo-600 hover:text-indigo-900"
                                    @click="openCreateCurriculum(program)"
                                >
                                    + Nuevo plan
                                </button>
                                <button
                                    type="button"
                                    class="text-gray-700 hover:text-gray-900"
                                    @click="openEditProgram(program)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    :disabled="toggling"
                                    :class="
                                        program.active
                                            ? 'text-amber-700 hover:text-amber-900'
                                            : 'text-emerald-700 hover:text-emerald-900'
                                    "
                                    @click="handleToggleProgram(program)"
                                >
                                    {{ program.active ? 'Desactivar' : 'Reactivar' }}
                                </button>
                            </div>
                        </div>

                        <!-- Planes de estudio -->
                        <div class="p-5">
                            <div v-if="program.curricula.length === 0" class="flex items-center justify-between text-sm text-gray-500">
                                <span class="italic">Esta carrera aún no tiene planes de estudio registrados.</span>
                                <button
                                    type="button"
                                    class="font-medium text-indigo-600 hover:text-indigo-800"
                                    @click="openCreateCurriculum(program)"
                                >
                                    Crear primer plan
                                </button>
                            </div>

                            <div v-else class="divide-y divide-gray-100 rounded-md border border-gray-200">
                                <div
                                    v-for="curriculum in program.curricula"
                                    :key="curriculum.id"
                                    class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="text-sm font-semibold text-gray-900">
                                            Plan {{ curriculum.version }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            Vigencia: {{ formatDate(curriculum.valid_from) }}
                                            <template v-if="curriculum.valid_to">
                                                al {{ formatDate(curriculum.valid_to) }}
                                            </template>
                                        </span>
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                            {{ curriculum.curriculum_courses_count }} materias
                                        </span>
                                        <StatusBadge :active="curriculum.active" />
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
                                        <Link
                                            :href="route('catalogs.curricula.show', curriculum.id)"
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                        >
                                            Administrar malla →
                                        </Link>
                                        <button
                                            type="button"
                                            class="text-xs text-gray-600 hover:text-gray-900"
                                            @click="openEditCurriculum(program, curriculum)"
                                        >
                                            Editar versión
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="toggling"
                                            :class="[
                                                'text-xs',
                                                curriculum.active
                                                    ? 'text-amber-700 hover:text-amber-900'
                                                    : 'text-emerald-700 hover:text-emerald-900',
                                            ]"
                                            @click="handleToggleCurriculum(curriculum)"
                                        >
                                            {{ curriculum.active ? 'Desactivar' : 'Reactivar' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <Pagination
                            :links="programs.links"
                            :from="programs.from"
                            :to="programs.to"
                            :total="programs.total"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Carrera -->
        <Modal :show="showingProgramModal" max-width="md" @close="closeProgramModal">
            <form class="p-6" @submit.prevent="submitProgram">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingProgram ? 'Editar carrera' : 'Nueva carrera' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="program-code" value="Código corto" />
                        <TextInput
                            id="program-code"
                            v-model="programForm.code"
                            type="text"
                            class="mt-1 block w-full uppercase"
                            placeholder="Ej. ADME, DISGD, EJAD"
                            required
                            autofocus
                        />
                        <InputError class="mt-1" :message="programForm.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="program-name" value="Nombre de la carrera" />
                        <TextInput
                            id="program-name"
                            v-model="programForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Licenciatura en Administración de Empresas"
                            required
                        />
                        <InputError class="mt-1" :message="programForm.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="program-modality-id" value="Modalidad" />
                        <select
                            id="program-modality-id"
                            v-model="programForm.modality_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option v-for="mod in modalities" :key="mod.id" :value="mod.id">
                                {{ mod.name }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="programForm.errors.modality_id" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="programForm.processing" @click="closeProgramModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': programForm.processing }" :disabled="programForm.processing">
                        {{ editingProgram ? 'Guardar cambios' : 'Registrar carrera' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal Plan de estudios -->
        <Modal :show="curriculumProgram !== null" max-width="md" @close="closeCurriculumModal">
            <form class="p-6" @submit.prevent="submitCurriculum">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingCurriculum ? 'Editar plan de estudios' : 'Nuevo plan de estudios' }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Carrera: <span class="font-medium">{{ curriculumProgram?.code }} — {{ curriculumProgram?.name }}</span>
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="curriculum-version" value="Versión del plan" />
                        <TextInput
                            id="curriculum-version"
                            v-model="curriculumForm.version"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. 2027"
                            required
                        />
                        <InputError class="mt-1" :message="curriculumForm.errors.version" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="curriculum-valid-from" value="Vigencia desde (opcional)" />
                            <input
                                id="curriculum-valid-from"
                                v-model="curriculumForm.valid_from"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-1" :message="curriculumForm.errors.valid_from" />
                        </div>

                        <div>
                            <InputLabel for="curriculum-valid-to" value="Vigencia hasta (opcional)" />
                            <input
                                id="curriculum-valid-to"
                                v-model="curriculumForm.valid_to"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-1" :message="curriculumForm.errors.valid_to" />
                        </div>
                    </div>

                    <div v-if="!editingCurriculum && (curriculumProgram?.curricula.length ?? 0) > 0">
                        <InputLabel for="curriculum-clone" value="Clonar materias y reglas desde (opcional)" />
                        <select
                            id="curriculum-clone"
                            v-model="curriculumForm.clone_from_curriculum_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Empezar con malla vacía (reglas por defecto)</option>
                            <option
                                v-for="plan in curriculumProgram?.curricula"
                                :key="plan.id"
                                :value="plan.id"
                            >
                                Plan {{ plan.version }} ({{ plan.curriculum_courses_count }} materias)
                            </option>
                        </select>
                        <InputError class="mt-1" :message="curriculumForm.errors.clone_from_curriculum_id" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="curriculumForm.processing" @click="closeCurriculumModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-50': curriculumForm.processing }"
                        :disabled="curriculumForm.processing"
                    >
                        {{ editingCurriculum ? 'Guardar cambios' : 'Crear y editar malla' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirmación desactivar carrera -->
        <ConfirmModal
            :show="programToDeactivate !== null"
            title="Desactivar carrera"
            :message="`¿Deseas desactivar la carrera ${programToDeactivate?.code ?? ''} (${programToDeactivate?.name ?? ''})? Se conservarán sus planes e historial.`"
            confirm-label="Sí, desactivar"
            :processing="toggling"
            @close="programToDeactivate = null"
            @confirm="confirmDeactivateProgram"
        />

        <!-- Confirmación desactivar plan -->
        <ConfirmModal
            :show="curriculumToDeactivate !== null"
            title="Desactivar plan de estudios"
            :message="`¿Deseas desactivar el Plan ${curriculumToDeactivate?.version ?? ''}? Los grupos asignados conservarán su historial.`"
            confirm-label="Sí, desactivar"
            :processing="toggling"
            @close="curriculumToDeactivate = null"
            @confirm="confirmDeactivateCurriculum"
        />
    </AuthenticatedLayout>
</template>
