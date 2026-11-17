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

interface Option {
    value: string;
    label: string;
}

interface TeacherItem {
    id: number;
    employee_id: string;
    full_name: string;
    email: string | null;
    origin: string;
    max_weekly_hours: number | null;
    active: boolean;
    teacher_courses_count: number;
    availabilities_count: number;
}

const props = defineProps<{
    teachers: PaginatedData<TeacherItem>;
    originOptions: Option[];
    filters: {
        search: string;
        origin: string;
        status: string;
    };
}>();

const search = ref(props.filters.search);
const originFilter = ref(props.filters.origin);
const statusFilter = ref(props.filters.status);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters(): void {
    router.get(
        route('catalogs.teachers.index'),
        {
            search: search.value || undefined,
            origin: originFilter.value !== 'all' ? originFilter.value : undefined,
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

watch([originFilter, statusFilter], applyFilters);

function resetFilters(): void {
    search.value = '';
    originFilter.value = 'all';
    statusFilter.value = 'all';
}

function originLabel(value: string): string {
    return props.originOptions.find((opt) => opt.value === value)?.label ?? value;
}

const showingFormModal = ref(false);
const editingTeacher = ref<TeacherItem | null>(null);

const form = useForm({
    employee_id: '',
    full_name: '',
    email: '',
    origin: 'campus',
    max_weekly_hours: '' as string | number,
});

function openCreateModal(): void {
    editingTeacher.value = null;
    form.reset();
    form.clearErrors();
    form.origin = 'campus';
    showingFormModal.value = true;
}

function openEditModal(teacher: TeacherItem): void {
    editingTeacher.value = teacher;
    form.clearErrors();
    form.employee_id = teacher.employee_id;
    form.full_name = teacher.full_name;
    form.email = teacher.email ?? '';
    form.origin = teacher.origin;
    form.max_weekly_hours = teacher.max_weekly_hours !== null ? String(teacher.max_weekly_hours) : '';
    showingFormModal.value = true;
}

function closeFormModal(): void {
    showingFormModal.value = false;
    editingTeacher.value = null;
}

function submitForm(): void {
    if (editingTeacher.value) {
        form.put(route('catalogs.teachers.update', editingTeacher.value.id), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(route('catalogs.teachers.store'), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    }
}

const teacherToDeactivate = ref<TeacherItem | null>(null);
const toggling = ref(false);

function handleToggle(teacher: TeacherItem): void {
    if (teacher.active) {
        teacherToDeactivate.value = teacher;
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.teachers.toggle-active', teacher.id),
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
    if (!teacherToDeactivate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.teachers.toggle-active', teacherToDeactivate.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                teacherToDeactivate.value = null;
            },
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Docentes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Catálogo de docentes
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Identificados por su ID docente único; configura su perfil de materias y disponibilidad semanal.
                    </p>
                </div>

                <PrimaryButton @click="openCreateModal">
                    + Nuevo docente
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
                    <div class="w-full sm:max-w-xs">
                        <label for="teacher-search" class="sr-only">Buscar docente</label>
                        <TextInput
                            id="teacher-search"
                            v-model="search"
                            type="search"
                            class="block w-full"
                            placeholder="Buscar por ID docente, nombre o correo..."
                        />
                    </div>

                    <div class="w-full sm:w-40">
                        <label for="teacher-origin" class="sr-only">Filtrar por origen</label>
                        <select
                            id="teacher-origin"
                            v-model="originFilter"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">Todos los orígenes</option>
                            <option v-for="opt in originOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="w-full sm:w-40">
                        <label for="teacher-status" class="sr-only">Filtrar por estado</label>
                        <select
                            id="teacher-status"
                            v-model="statusFilter"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>

                    <button
                        v-if="search !== '' || originFilter !== 'all' || statusFilter !== 'all'"
                        type="button"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        @click="resetFilters"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <!-- Tabla o estado vacío -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div v-if="teachers.data.length === 0" class="p-10 text-center">
                        <h2 class="text-base font-semibold text-gray-900">
                            No se encontraron docentes
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{
                                search !== '' || originFilter !== 'all' || statusFilter !== 'all'
                                    ? 'Prueba ajustando los criterios de búsqueda o limpia los filtros.'
                                    : 'Registra el primer docente con su ID único para asignarle materias y horarios disponibles.'
                            }}
                        </p>
                        <div class="mt-4">
                            <PrimaryButton
                                v-if="search === '' && originFilter === 'all' && statusFilter === 'all'"
                                @click="openCreateModal"
                            >
                                Registrar docente
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
                                        ID docente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nombre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Origen
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Tope hrs/sem
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Perfil y disponibilidad
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
                                    v-for="teacher in teachers.data"
                                    :key="teacher.id"
                                    :class="{ 'bg-gray-50/70 opacity-75': !teacher.active }"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-sm font-semibold text-gray-900">
                                        {{ teacher.employee_id }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">{{ teacher.full_name }}</div>
                                        <div v-if="teacher.email" class="text-xs text-gray-500">{{ teacher.email }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 font-medium ring-1 ring-inset',
                                                teacher.origin === 'campus'
                                                    ? 'bg-sky-50 text-sky-700 ring-sky-600/20'
                                                    : 'bg-gray-100 text-gray-700 ring-gray-500/20',
                                            ]"
                                        >
                                            {{ originLabel(teacher.origin) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ teacher.max_weekly_hours ? `${teacher.max_weekly_hours} h` : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-600">
                                        <span>{{ teacher.teacher_courses_count }} materias</span>
                                        <span class="mx-1 text-gray-300">·</span>
                                        <span :class="{ 'font-medium text-amber-700': teacher.availabilities_count === 0 }">
                                            {{
                                                teacher.availabilities_count > 0
                                                    ? `${teacher.availabilities_count} franjas`
                                                    : 'Sin disponibilidad'
                                            }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <StatusBadge :active="teacher.active" />
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-3">
                                        <Link
                                            :href="route('catalogs.teachers.show', teacher.id)"
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                        >
                                            Perfil y horarios →
                                        </Link>
                                        <button
                                            type="button"
                                            class="text-gray-700 hover:text-gray-900"
                                            @click="openEditModal(teacher)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="toggling"
                                            :class="
                                                teacher.active
                                                    ? 'text-amber-700 hover:text-amber-900'
                                                    : 'text-emerald-700 hover:text-emerald-900'
                                            "
                                            @click="handleToggle(teacher)"
                                        >
                                            {{ teacher.active ? 'Desactivar' : 'Reactivar' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        :links="teachers.links"
                        :from="teachers.from"
                        :to="teachers.to"
                        :total="teachers.total"
                    />
                </div>
            </div>
        </div>

        <!-- Modal alta / edición -->
        <Modal :show="showingFormModal" max-width="md" @close="closeFormModal">
            <form class="p-6" @submit.prevent="submitForm">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingTeacher ? 'Editar docente' : 'Nuevo docente' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="teacher-employee-id" value="ID docente (único)" />
                        <TextInput
                            id="teacher-employee-id"
                            v-model="form.employee_id"
                            type="text"
                            class="mt-1 block w-full font-mono"
                            placeholder="Ej. 00100011 o 247112"
                            required
                            autofocus
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Si ingresas solo números, se completarán ceros a la izquierda hasta 8 dígitos.
                        </p>
                        <InputError class="mt-1" :message="form.errors.employee_id" />
                    </div>

                    <div>
                        <InputLabel for="teacher-full-name" value="Nombre completo" />
                        <TextInput
                            id="teacher-full-name"
                            v-model="form.full_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Mtra. Sofía Mendoza Ríos"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.full_name" />
                    </div>

                    <div>
                        <InputLabel for="teacher-email" value="Correo electrónico (opcional)" />
                        <TextInput
                            id="teacher-email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            placeholder="docente@ejemplo.edu.mx"
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="teacher-form-origin" value="Origen" />
                            <select
                                id="teacher-form-origin"
                                v-model="form.origin"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="opt in originOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.origin" />
                        </div>

                        <div>
                            <InputLabel for="teacher-max-hours" value="Tope hrs/semana (opc.)" />
                            <input
                                id="teacher-max-hours"
                                v-model="form.max_weekly_hours"
                                type="number"
                                min="1"
                                max="60"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Ej. 24"
                            />
                            <InputError class="mt-1" :message="form.errors.max_weekly_hours" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="form.processing" @click="closeFormModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                        {{ editingTeacher ? 'Guardar cambios' : 'Registrar docente' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirmación desactivar -->
        <ConfirmModal
            :show="teacherToDeactivate !== null"
            title="Desactivar docente"
            :message="`¿Deseas desactivar a ${teacherToDeactivate?.full_name ?? ''} (ID ${teacherToDeactivate?.employee_id ?? ''})? Conservará su historial de materias impartidas.`"
            confirm-label="Sí, desactivar"
            :processing="toggling"
            @close="teacherToDeactivate = null"
            @confirm="confirmDeactivate"
        />
    </AuthenticatedLayout>
</template>
