<script setup lang="ts">
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatHour, WEEKDAYS, weekdayShort } from '@/Utils/weekdays';
import { Head, router, useForm } from '@inertiajs/vue3';

interface Modality {
    id: number;
    code: string;
    name: string;
}

interface ShiftWindowItem {
    id: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
}

interface ReservedBlockItem {
    id: number;
    name: string;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
}

interface ShiftItem {
    id: number;
    modality_id: number;
    code: string;
    name: string;
    active: boolean;
    modality: Modality;
    windows: ShiftWindowItem[];
    reserved_blocks: ReservedBlockItem[];
}

const props = defineProps<{
    shifts: ShiftItem[];
    modalities: Modality[];
    filters: {
        modality_id: number | null;
        status: string;
    };
}>();

const modalityFilter = ref<string>(props.filters.modality_id ? String(props.filters.modality_id) : '');
const statusFilter = ref<string>(props.filters.status);

function applyFilters(): void {
    router.get(
        route('catalogs.shifts.index'),
        {
            modality_id: modalityFilter.value !== '' ? modalityFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch([modalityFilter, statusFilter], applyFilters);

function resetFilters(): void {
    modalityFilter.value = '';
    statusFilter.value = 'all';
}

// Modal Turno
const showingShiftModal = ref(false);
const editingShift = ref<ShiftItem | null>(null);

const shiftForm = useForm({
    modality_id: props.modalities[0]?.id ?? 1,
    code: '',
    name: '',
});

function openCreateShift(): void {
    editingShift.value = null;
    shiftForm.reset();
    shiftForm.clearErrors();
    shiftForm.modality_id = props.modalities[0]?.id ?? 1;
    showingShiftModal.value = true;
}

function openEditShift(shift: ShiftItem): void {
    editingShift.value = shift;
    shiftForm.clearErrors();
    shiftForm.modality_id = shift.modality_id;
    shiftForm.code = shift.code;
    shiftForm.name = shift.name;
    showingShiftModal.value = true;
}

function closeShiftModal(): void {
    showingShiftModal.value = false;
    editingShift.value = null;
}

function submitShift(): void {
    if (editingShift.value) {
        shiftForm.put(route('catalogs.shifts.update', editingShift.value.id), {
            preserveScroll: true,
            onSuccess: () => closeShiftModal(),
        });
    } else {
        shiftForm.post(route('catalogs.shifts.store'), {
            preserveScroll: true,
            onSuccess: () => closeShiftModal(),
        });
    }
}

// Activar / Desactivar turno
const shiftToDeactivate = ref<ShiftItem | null>(null);
const toggling = ref(false);

function handleToggleShift(shift: ShiftItem): void {
    if (shift.active) {
        shiftToDeactivate.value = shift;
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.shifts.toggle-active', shift.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}

function confirmDeactivateShift(): void {
    if (!shiftToDeactivate.value) {
        return;
    }

    toggling.value = true;
    router.patch(
        route('catalogs.shifts.toggle-active', shiftToDeactivate.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                shiftToDeactivate.value = null;
            },
            onFinish: () => {
                toggling.value = false;
            },
        },
    );
}

// Modal Ventana Horaria
const windowTargetShift = ref<ShiftItem | null>(null);
const windowForm = useForm({
    days_of_week: [1, 2, 3, 4, 5] as number[],
    starts_at: '08:00',
    ends_at: '14:00',
});

function openWindowModal(shift: ShiftItem): void {
    windowTargetShift.value = shift;
    windowForm.clearErrors();
    windowForm.days_of_week = shift.modality.code === 'executive' ? [6] : [1, 2, 3, 4, 5];
    windowForm.starts_at = '08:00';
    windowForm.ends_at = '14:00';
}

function submitWindow(): void {
    if (!windowTargetShift.value) {
        return;
    }

    windowForm.post(route('catalogs.shifts.windows.store', windowTargetShift.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            windowTargetShift.value = null;
        },
    });
}

function deleteWindow(windowId: number): void {
    router.delete(route('catalogs.shifts.windows.destroy', windowId), {
        preserveScroll: true,
    });
}

// Modal Bloque Reservado
const blockTargetShift = ref<ShiftItem | null>(null);
const blockForm = useForm({
    name: 'Inglés',
    days_of_week: [1, 2, 3, 4] as number[],
    starts_at: '12:30',
    ends_at: '14:00',
});

function openBlockModal(shift: ShiftItem): void {
    blockTargetShift.value = shift;
    blockForm.clearErrors();
    blockForm.name = 'Inglés';
    blockForm.days_of_week = [1, 2, 3, 4];
    blockForm.starts_at = '12:30';
    blockForm.ends_at = '14:00';
}

function submitBlock(): void {
    if (!blockTargetShift.value) {
        return;
    }

    blockForm.post(route('catalogs.shifts.reserved-blocks.store', blockTargetShift.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            blockTargetShift.value = null;
        },
    });
}

function deleteBlock(blockId: number): void {
    router.delete(route('catalogs.shifts.reserved-blocks.destroy', blockId), {
        preserveScroll: true,
    });
}

function toggleDay(list: number[], day: number): void {
    const idx = list.indexOf(day);
    if (idx === -1) {
        list.push(day);
        list.sort((a, b) => a - b);
    } else {
        list.splice(idx, 1);
    }
}
</script>

<template>
    <Head title="Turnos y bloques reservados" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Turnos y bloques reservados
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Ventanas horarias por turno y bloques protegidos (como inglés) que no pueden ocuparse con otras materias.
                    </p>
                </div>

                <PrimaryButton @click="openCreateShift">
                    + Nuevo turno
                </PrimaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="w-full sm:w-52">
                        <label for="shift-modality-filter" class="sr-only">Filtrar por modalidad</label>
                        <select
                            id="shift-modality-filter"
                            v-model="modalityFilter"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todas las modalidades</option>
                            <option v-for="mod in modalities" :key="mod.id" :value="String(mod.id)">
                                {{ mod.name }}
                            </option>
                        </select>
                    </div>

                    <div class="w-full sm:w-44">
                        <label for="shift-status-filter" class="sr-only">Filtrar por estado</label>
                        <select
                            id="shift-status-filter"
                            v-model="statusFilter"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>

                    <button
                        v-if="modalityFilter !== '' || statusFilter !== 'all'"
                        type="button"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        @click="resetFilters"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <!-- Estado vacío -->
                <div
                    v-if="shifts.length === 0"
                    class="rounded-lg border border-gray-200 bg-white p-10 text-center shadow-sm"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        No se encontraron turnos
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{
                            modalityFilter !== '' || statusFilter !== 'all'
                                ? 'Prueba cambiando los filtros seleccionados.'
                                : 'Registra un turno para configurar sus ventanas horarias y bloques reservados.'
                        }}
                    </p>
                    <div class="mt-4">
                        <PrimaryButton v-if="modalityFilter === '' && statusFilter === 'all'" @click="openCreateShift">
                            Registrar turno
                        </PrimaryButton>
                        <SecondaryButton v-else @click="resetFilters">
                            Limpiar filtros
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Tarjetas de turnos -->
                <div v-else class="space-y-4">
                    <div
                        v-for="shift in shifts"
                        :key="shift.id"
                        :class="[
                            'overflow-hidden rounded-lg border bg-white shadow-sm',
                            shift.active ? 'border-gray-200' : 'border-gray-200 bg-gray-50/70 opacity-80',
                        ]"
                    >
                        <!-- Cabecera del turno -->
                        <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h2 class="text-base font-semibold text-gray-900">
                                    {{ shift.name }}
                                </h2>
                                <code class="rounded bg-white px-2 py-0.5 text-xs text-gray-600 ring-1 ring-inset ring-gray-200">
                                    {{ shift.code }}
                                </code>
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                        shift.modality.code === 'regular'
                                            ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
                                            : 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    ]"
                                >
                                    {{ shift.modality.name }}
                                </span>
                                <StatusBadge :active="shift.active" />
                            </div>

                            <div class="flex items-center gap-4 text-sm font-medium">
                                <button
                                    type="button"
                                    class="text-indigo-600 hover:text-indigo-900"
                                    @click="openEditShift(shift)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    :disabled="toggling"
                                    :class="
                                        shift.active
                                            ? 'text-amber-700 hover:text-amber-900'
                                            : 'text-emerald-700 hover:text-emerald-900'
                                    "
                                    @click="handleToggleShift(shift)"
                                >
                                    {{ shift.active ? 'Desactivar' : 'Reactivar' }}
                                </button>
                            </div>
                        </div>

                        <!-- Ventanas horarias y Bloques reservados -->
                        <div class="grid gap-6 p-5 md:grid-cols-2">
                            <!-- Ventanas horarias -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">
                                            Ventanas horarias permitidas
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            Salir de estas franjas genera advertencia en planeación.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                        @click="openWindowModal(shift)"
                                    >
                                        + Agregar horario
                                    </button>
                                </div>

                                <p v-if="shift.windows.length === 0" class="mt-3 text-xs italic text-gray-500">
                                    Sin ventanas horarias configuradas.
                                </p>

                                <div v-else class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="win in shift.windows"
                                        :key="win.id"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700"
                                    >
                                        <span>
                                            {{ weekdayShort(win.day_of_week) }}
                                            {{ formatHour(win.starts_at) }}–{{ formatHour(win.ends_at) }}
                                        </span>
                                        <button
                                            type="button"
                                            title="Quitar ventana"
                                            class="text-gray-400 hover:text-red-600"
                                            @click="deleteWindow(win.id)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <!-- Bloques reservados -->
                            <div>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">
                                            Bloques reservados
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            Horarios protegidos (ej. Inglés); bloquean el empalme de clases.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100"
                                        @click="openBlockModal(shift)"
                                    >
                                        + Agregar bloque
                                    </button>
                                </div>

                                <p v-if="shift.reserved_blocks.length === 0" class="mt-3 text-xs italic text-gray-500">
                                    Sin bloques reservados en este turno.
                                </p>

                                <div v-else class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="block in shift.reserved_blocks"
                                        :key="block.id"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-900"
                                    >
                                        <span>
                                            {{ block.name }} · {{ weekdayShort(block.day_of_week) }}
                                            {{ formatHour(block.starts_at) }}–{{ formatHour(block.ends_at) }}
                                        </span>
                                        <button
                                            type="button"
                                            title="Quitar bloque reservado"
                                            class="text-amber-600 hover:text-red-700"
                                            @click="deleteBlock(block.id)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Turno -->
        <Modal :show="showingShiftModal" max-width="md" @close="closeShiftModal">
            <form class="p-6" @submit.prevent="submitShift">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingShift ? 'Editar turno' : 'Nuevo turno' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="shift-modality" value="Modalidad" />
                        <select
                            id="shift-modality"
                            v-model="shiftForm.modality_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option v-for="mod in modalities" :key="mod.id" :value="mod.id">
                                {{ mod.name }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="shiftForm.errors.modality_id" />
                    </div>

                    <div>
                        <InputLabel for="shift-code" value="Código (identificador)" />
                        <TextInput
                            id="shift-code"
                            v-model="shiftForm.code"
                            type="text"
                            class="mt-1 block w-full lowercase"
                            placeholder="Ej. matutino o ejecutivo-sabado"
                            required
                        />
                        <InputError class="mt-1" :message="shiftForm.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="shift-name" value="Nombre del turno" />
                        <TextInput
                            id="shift-name"
                            v-model="shiftForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Escolarizado Matutino"
                            required
                        />
                        <InputError class="mt-1" :message="shiftForm.errors.name" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="shiftForm.processing" @click="closeShiftModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': shiftForm.processing }" :disabled="shiftForm.processing">
                        {{ editingShift ? 'Guardar cambios' : 'Registrar turno' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal Ventana Horaria -->
        <Modal :show="windowTargetShift !== null" max-width="md" @close="windowTargetShift = null">
            <form class="p-6" @submit.prevent="submitWindow">
                <h2 class="text-lg font-semibold text-gray-900">
                    Agregar ventana horaria
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Turno: <span class="font-medium">{{ windowTargetShift?.name }}</span>
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="Días" />
                            <div class="flex gap-2 text-xs">
                                <button
                                    type="button"
                                    class="text-indigo-600 hover:underline"
                                    @click="windowForm.days_of_week = [1, 2, 3, 4, 5]"
                                >
                                    L–V
                                </button>
                                <button
                                    type="button"
                                    class="text-indigo-600 hover:underline"
                                    @click="windowForm.days_of_week = [6]"
                                >
                                    Sábado
                                </button>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="day in WEEKDAYS"
                                :key="day.value"
                                type="button"
                                :class="[
                                    'rounded-md border px-2.5 py-1 text-xs font-medium transition',
                                    windowForm.days_of_week.includes(day.value)
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                ]"
                                @click="toggleDay(windowForm.days_of_week, day.value)"
                            >
                                {{ day.short }}
                            </button>
                        </div>
                        <InputError class="mt-1" :message="windowForm.errors.days_of_week" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="win-start" value="Hora inicio" />
                            <input
                                id="win-start"
                                v-model="windowForm.starts_at"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="windowForm.errors.starts_at" />
                        </div>

                        <div>
                            <InputLabel for="win-end" value="Hora fin" />
                            <input
                                id="win-end"
                                v-model="windowForm.ends_at"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="windowForm.errors.ends_at" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="windowForm.processing" @click="windowTargetShift = null">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': windowForm.processing }" :disabled="windowForm.processing">
                        Agregar horario
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal Bloque Reservado -->
        <Modal :show="blockTargetShift !== null" max-width="md" @close="blockTargetShift = null">
            <form class="p-6" @submit.prevent="submitBlock">
                <h2 class="text-lg font-semibold text-gray-900">
                    Agregar bloque reservado
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Turno: <span class="font-medium">{{ blockTargetShift?.name }}</span>
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="block-name" value="Nombre del bloque" />
                        <TextInput
                            id="block-name"
                            v-model="blockForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Ej. Inglés"
                            required
                        />
                        <InputError class="mt-1" :message="blockForm.errors.name" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="Días" />
                            <button
                                type="button"
                                class="text-xs text-indigo-600 hover:underline"
                                @click="blockForm.days_of_week = [1, 2, 3, 4]"
                            >
                                L–J
                            </button>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="day in WEEKDAYS"
                                :key="day.value"
                                type="button"
                                :class="[
                                    'rounded-md border px-2.5 py-1 text-xs font-medium transition',
                                    blockForm.days_of_week.includes(day.value)
                                        ? 'border-amber-600 bg-amber-600 text-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                ]"
                                @click="toggleDay(blockForm.days_of_week, day.value)"
                            >
                                {{ day.short }}
                            </button>
                        </div>
                        <InputError class="mt-1" :message="blockForm.errors.days_of_week" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="block-start" value="Hora inicio" />
                            <input
                                id="block-start"
                                v-model="blockForm.starts_at"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="blockForm.errors.starts_at" />
                        </div>

                        <div>
                            <InputLabel for="block-end" value="Hora fin" />
                            <input
                                id="block-end"
                                v-model="blockForm.ends_at"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="blockForm.errors.ends_at" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="blockForm.processing" @click="blockTargetShift = null">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': blockForm.processing }" :disabled="blockForm.processing">
                        Agregar bloque
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirmación desactivar turno -->
        <ConfirmModal
            :show="shiftToDeactivate !== null"
            title="Desactivar turno"
            :message="`¿Deseas desactivar el turno ${shiftToDeactivate?.name ?? ''}? Los grupos históricos vinculados conservarán su referencia.`"
            confirm-label="Sí, desactivar"
            :processing="toggling"
            @close="shiftToDeactivate = null"
            @confirm="confirmDeactivateShift"
        />
    </AuthenticatedLayout>
</template>
