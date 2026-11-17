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
import { Head, router, useForm } from '@inertiajs/vue3';
import type { PaginatedData } from '@/types';

interface Course {
    id: number;
    subject_code: string;
    course_code: string;
    name: string;
    active: boolean;
}

const props = defineProps<{
    courses: PaginatedData<Course>;
    filters: {
        search: string;
        status: string;
    };
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters(): void {
    router.get(
        route('catalogs.courses.index'),
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch(search, () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }
    filterTimeout = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);

function resetFilters(): void {
    search.value = '';
    status.value = 'all';
}

const showingFormModal = ref(false);
const editingCourse = ref<Course | null>(null);
const duplicateWarning = ref<Course | null>(null);
let pairCheckTimeout: ReturnType<typeof setTimeout> | null = null;

const form = useForm({
    subject_code: '',
    course_code: '',
    name: '',
});

async function checkDuplicatePair(): Promise<void> {
    const subject = form.subject_code.trim().toUpperCase();
    const course = form.course_code.trim().toUpperCase();

    if (!subject || !course) {
        duplicateWarning.value = null;
        return;
    }

    try {
        const response = await window.axios.get<{ exists: boolean; course: Course | null }>(
            route('catalogs.courses.check-pair'),
            {
                params: {
                    subject_code: subject,
                    course_code: course,
                    ignore_id: editingCourse.value?.id,
                },
            },
        );
        duplicateWarning.value = response.data.exists ? response.data.course : null;
    } catch {
        duplicateWarning.value = null;
    }
}

watch(
    () => [form.subject_code, form.course_code],
    () => {
        if (!showingFormModal.value) {
            return;
        }
        if (pairCheckTimeout) {
            clearTimeout(pairCheckTimeout);
        }
        pairCheckTimeout = setTimeout(checkDuplicatePair, 250);
    },
);

function openCreateModal(): void {
    editingCourse.value = null;
    duplicateWarning.value = null;
    form.reset();
    form.clearErrors();
    showingFormModal.value = true;
}

function openEditModal(course: Course): void {
    editingCourse.value = course;
    duplicateWarning.value = null;
    form.clearErrors();
    form.subject_code = course.subject_code;
    form.course_code = course.course_code;
    form.name = course.name;
    showingFormModal.value = true;
}

function closeFormModal(): void {
    showingFormModal.value = false;
    editingCourse.value = null;
    duplicateWarning.value = null;
}

function submitForm(): void {
    if (editingCourse.value) {
        form.put(route('catalogs.courses.update', editingCourse.value.id), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(route('catalogs.courses.store'), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    }
}

const courseToDeactivate = ref<Course | null>(null);
const toggling = ref(false);

function handleToggle(course: Course): void {
    if (course.active) {
        courseToDeactivate.value = course;
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.courses.toggle-active', course.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}

function confirmDeactivate(): void {
    if (!courseToDeactivate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.courses.toggle-active', courseToDeactivate.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                courseToDeactivate.value = null;
            },
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Materias" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Catálogo de materias
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Cada materia se identifica globalmente por su pareja única SUBJ + CRSE.
                    </p>
                </div>

                <PrimaryButton @click="openCreateModal">
                    + Nueva materia
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="w-full sm:max-w-xs">
                            <label for="course-search" class="sr-only">Buscar materia</label>
                            <TextInput
                                id="course-search"
                                v-model="search"
                                type="search"
                                class="block w-full"
                                placeholder="Buscar por SUBJ, CRSE o nombre..."
                            />
                        </div>

                        <div class="w-full sm:w-44">
                            <label for="course-status" class="sr-only">Filtrar por estado</label>
                            <select
                                id="course-status"
                                v-model="status"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Todas</option>
                                <option value="active">Activas</option>
                                <option value="inactive">Inactivas</option>
                            </select>
                        </div>

                        <button
                            v-if="search !== '' || status !== 'all'"
                            type="button"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            @click="resetFilters"
                        >
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Tabla o estado vacío -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div v-if="courses.data.length === 0" class="p-10 text-center">
                        <h2 class="text-base font-semibold text-gray-900">
                            No se encontraron materias
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{
                                search !== '' || status !== 'all'
                                    ? 'Prueba ajustando los criterios de búsqueda o limpia los filtros.'
                                    : 'Registra las materias institucionales con su clave SUBJ y CRSE.'
                            }}
                        </p>
                        <div class="mt-4">
                            <PrimaryButton v-if="search === '' && status === 'all'" @click="openCreateModal">
                                Registrar materia
                            </PrimaryButton>
                            <SecondaryButton v-else @click="resetFilters">
                                Limpiar filtros
                            </SecondaryButton>
                        </div>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        SUBJ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        CRSE
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nombre canónico
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="course in courses.data"
                                    :key="course.id"
                                    :class="{ 'bg-gray-50/70 opacity-75': !course.active }"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">
                                        {{ course.subject_code }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-indigo-700">
                                        {{ course.course_code }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ course.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <StatusBadge
                                            :active="course.active"
                                            active-label="Activa"
                                            inactive-label="Inactiva"
                                        />
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-3">
                                        <button
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            @click="openEditModal(course)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="toggling"
                                            :class="
                                                course.active
                                                    ? 'text-amber-700 hover:text-amber-900'
                                                    : 'text-emerald-700 hover:text-emerald-900'
                                            "
                                            @click="handleToggle(course)"
                                        >
                                            {{ course.active ? 'Desactivar' : 'Reactivar' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        :links="courses.links"
                        :from="courses.from"
                        :to="courses.to"
                        :total="courses.total"
                    />
                </div>
            </div>
        </div>

        <!-- Modal alta / edición -->
        <Modal :show="showingFormModal" max-width="md" @close="closeFormModal">
            <form class="p-6" @submit.prevent="submitForm">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingCourse ? 'Editar materia' : 'Nueva materia' }}
                </h2>

                <!-- Aviso en vivo si ya existe la pareja SUBJ + CRSE -->
                <div
                    v-if="duplicateWarning"
                    role="alert"
                    class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                >
                    <span class="font-semibold">Aviso:</span> ya existe la materia
                    <span class="font-semibold">
                        {{ duplicateWarning.subject_code }} {{ duplicateWarning.course_code }}
                    </span>
                    ({{ duplicateWarning.name }}) en el catálogo.
                </div>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="course-subj" value="SUBJ (Área)" />
                            <TextInput
                                id="course-subj"
                                v-model="form.subject_code"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                placeholder="Ej. LMFM"
                                required
                                autofocus
                            />
                            <InputError class="mt-1" :message="form.errors.subject_code" />
                        </div>

                        <div>
                            <InputLabel for="course-crse" value="CRSE (Clave)" />
                            <TextInput
                                id="course-crse"
                                v-model="form.course_code"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                placeholder="Ej. HUS02"
                                required
                            />
                            <InputError class="mt-1" :message="form.errors.course_code" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="course-name" value="Nombre canónico" />
                        <TextInput
                            id="course-name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Razonamiento Crítico"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="form.processing" @click="closeFormModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-50': form.processing || duplicateWarning !== null }"
                        :disabled="form.processing || duplicateWarning !== null"
                    >
                        {{ editingCourse ? 'Guardar cambios' : 'Registrar materia' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal confirmación al desactivar -->
        <ConfirmModal
            :show="courseToDeactivate !== null"
            title="Desactivar materia"
            :message="`¿Deseas desactivar la materia ${courseToDeactivate?.subject_code ?? ''} ${courseToDeactivate?.course_code ?? ''} (${courseToDeactivate?.name ?? ''})? Seguirá conservándose en planes e historial existentes.`"
            confirm-label="Sí, desactivar"
            :processing="toggling"
            @close="courseToDeactivate = null"
            @confirm="confirmDeactivate"
        />
    </AuthenticatedLayout>
</template>
