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

interface Classroom {
    id: number;
    code: string;
    name: string;
    capacity: number;
    active: boolean;
}

const props = defineProps<{
    classrooms: PaginatedData<Classroom>;
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
        route('catalogs.classrooms.index'),
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
const editingClassroom = ref<Classroom | null>(null);

const form = useForm({
    code: '',
    name: '',
    capacity: 25 as number | string,
});

function openCreateModal(): void {
    editingClassroom.value = null;
    form.reset();
    form.clearErrors();
    form.capacity = 25;
    showingFormModal.value = true;
}

function openEditModal(classroom: Classroom): void {
    editingClassroom.value = classroom;
    form.clearErrors();
    form.code = classroom.code;
    form.name = classroom.name;
    form.capacity = classroom.capacity;
    showingFormModal.value = true;
}

function closeFormModal(): void {
    showingFormModal.value = false;
    editingClassroom.value = null;
}

function submitForm(): void {
    if (editingClassroom.value) {
        form.put(route('catalogs.classrooms.update', editingClassroom.value.id), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    } else {
        form.post(route('catalogs.classrooms.store'), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
        });
    }
}

const classroomToDeactivate = ref<Classroom | null>(null);
const toggling = ref(false);

function handleToggle(classroom: Classroom): void {
    if (classroom.active) {
        classroomToDeactivate.value = classroom;
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.classrooms.toggle-active', classroom.id),
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
    if (!classroomToDeactivate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.classrooms.toggle-active', classroomToDeactivate.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                classroomToDeactivate.value = null;
            },
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Aulas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Catálogo de aulas
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Espacios físicos y su capacidad máxima para validar el cupo de cada sección.
                    </p>
                </div>

                <PrimaryButton @click="openCreateModal">
                    + Nueva aula
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="w-full sm:max-w-xs">
                            <label for="classroom-search" class="sr-only">Buscar aula</label>
                            <TextInput
                                id="classroom-search"
                                v-model="search"
                                type="search"
                                class="block w-full"
                                placeholder="Buscar por código o nombre..."
                            />
                        </div>

                        <div class="w-full sm:w-44">
                            <label for="classroom-status" class="sr-only">Filtrar por estado</label>
                            <select
                                id="classroom-status"
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
                    <div v-if="classrooms.data.length === 0" class="p-10 text-center">
                        <h2 class="text-base font-semibold text-gray-900">
                            No se encontraron aulas
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{
                                search !== '' || status !== 'all'
                                    ? 'Prueba ajustando los criterios de búsqueda o limpia los filtros.'
                                    : 'Registra la primera aula con su capacidad máxima para usarla en la planeación.'
                            }}
                        </p>
                        <div class="mt-4">
                            <PrimaryButton v-if="search === '' && status === 'all'" @click="openCreateModal">
                                Registrar aula
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
                                        Código
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nombre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Capacidad
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
                                    v-for="classroom in classrooms.data"
                                    :key="classroom.id"
                                    :class="{ 'bg-gray-50/70 opacity-75': !classroom.active }"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">
                                        {{ classroom.code }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ classroom.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ classroom.capacity }} alumnos
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <StatusBadge
                                            :active="classroom.active"
                                            active-label="Activa"
                                            inactive-label="Inactiva"
                                        />
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-3">
                                        <button
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            @click="openEditModal(classroom)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="toggling"
                                            :class="
                                                classroom.active
                                                    ? 'text-amber-700 hover:text-amber-900'
                                                    : 'text-emerald-700 hover:text-emerald-900'
                                            "
                                            @click="handleToggle(classroom)"
                                        >
                                            {{ classroom.active ? 'Desactivar' : 'Reactivar' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        :links="classrooms.links"
                        :from="classrooms.from"
                        :to="classrooms.to"
                        :total="classrooms.total"
                    />
                </div>
            </div>
        </div>

        <!-- Modal alta / edición -->
        <Modal :show="showingFormModal" max-width="md" @close="closeFormModal">
            <form class="p-6" @submit.prevent="submitForm">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingClassroom ? 'Editar aula' : 'Nueva aula' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="classroom-code" value="Código único" />
                        <TextInput
                            id="classroom-code"
                            v-model="form.code"
                            type="text"
                            class="mt-1 block w-full uppercase"
                            placeholder="Ej. 204 o CTC 01"
                            required
                            autofocus
                        />
                        <InputError class="mt-1" :message="form.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="classroom-name" value="Nombre descriptivo" />
                        <TextInput
                            id="classroom-name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Aula 204 o Centro de Cómputo 01"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="classroom-capacity" value="Capacidad máxima (alumnos)" />
                        <input
                            id="classroom-capacity"
                            v-model="form.capacity"
                            type="number"
                            min="1"
                            max="500"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.capacity" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="form.processing" @click="closeFormModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                        {{ editingClassroom ? 'Guardar cambios' : 'Registrar aula' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal confirmación al desactivar -->
        <ConfirmModal
            :show="classroomToDeactivate !== null"
            title="Desactivar aula"
            :message="`¿Deseas desactivar el aula ${classroomToDeactivate?.code ?? ''} (${classroomToDeactivate?.name ?? ''})? No se eliminará para conservar el historial de horarios.`"
            confirm-label="Sí, desactivar"
            :processing="toggling"
            @close="classroomToDeactivate = null"
            @confirm="confirmDeactivate"
        />
    </AuthenticatedLayout>
</template>
