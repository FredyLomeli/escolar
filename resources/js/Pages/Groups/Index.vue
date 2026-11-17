<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface PeriodSummary {
    id: number;
    code: string;
    name: string;
    status: string;
    status_label: string;
}

interface CurriculumOption {
    id: number;
    program_id: number;
    version: string;
}

interface ProgramOption {
    id: number;
    code: string;
    name: string;
    modality_id: number;
    modality: {
        id: number;
        code: string;
        name: string;
    };
    curricula: CurriculumOption[];
}

interface ShiftOption {
    id: number;
    modality_id: number;
    code: string;
    name: string;
}

interface GroupTermItem {
    id: number;
    student_group_id: number;
    academic_period_id: number;
    term_number: number;
    student_count: number;
    student_count_confirmed: boolean;
    sections_count: number;
    student_group: {
        id: number;
        label: string | null;
        status: string;
        program: {
            id: number;
            code: string;
            name: string;
            modality: {
                id: number;
                code: string;
                name: string;
            };
        };
        curriculum: {
            id: number;
            version: string;
        };
        shift: {
            id: number;
            name: string;
        };
    };
}

interface EditableRow {
    id: number;
    student_count: number;
    student_count_confirmed: boolean;
}

const props = defineProps<{
    periods: PeriodSummary[];
    selectedPeriod: PeriodSummary | null;
    groupTerms: GroupTermItem[];
    programs: ProgramOption[];
    shifts: ShiftOption[];
    filters: {
        search: string;
        program_id: number | null;
        confirmed: string;
    };
}>();

const selectedPeriodId = ref<string>(props.selectedPeriod ? String(props.selectedPeriod.id) : '');
const search = ref(props.filters.search);
const programFilter = ref<string>(props.filters.program_id ? String(props.filters.program_id) : '');
const confirmedFilter = ref(props.filters.confirmed);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters(): void {
    router.get(
        route('groups.index'),
        {
            period_id: selectedPeriodId.value || undefined,
            search: search.value || undefined,
            program_id: programFilter.value !== '' ? programFilter.value : undefined,
            confirmed: confirmedFilter.value !== 'all' ? confirmedFilter.value : undefined,
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

watch([selectedPeriodId, programFilter, confirmedFilter], applyFilters);

function resetFilters(): void {
    search.value = '';
    programFilter.value = '';
    confirmedFilter.value = 'all';
}

// Edición masiva de matrícula
const editableRows = reactive<Record<number, EditableRow>>({});
const hasUnsavedChanges = ref(false);
const savingBulk = ref(false);

function syncEditableRows(): void {
    for (const key of Object.keys(editableRows)) {
        delete editableRows[Number(key)];
    }
    for (const item of props.groupTerms) {
        editableRows[item.id] = {
            id: item.id,
            student_count: item.student_count,
            student_count_confirmed: item.student_count_confirmed,
        };
    }
    hasUnsavedChanges.value = false;
}

syncEditableRows();
watch(() => props.groupTerms, syncEditableRows, { deep: true });

function markDirty(): void {
    hasUnsavedChanges.value = true;
}

function confirmAllVisible(): void {
    for (const item of props.groupTerms) {
        if (editableRows[item.id]) {
            editableRows[item.id].student_count_confirmed = true;
        }
    }
    hasUnsavedChanges.value = true;
}

function saveBulk(): void {
    if (!props.selectedPeriod || props.groupTerms.length === 0) {
        return;
    }

    savingBulk.value = true;
    const items = props.groupTerms.map((groupTerm) => ({
        id: groupTerm.id,
        student_count: Number(editableRows[groupTerm.id]?.student_count ?? groupTerm.student_count),
        student_count_confirmed: Boolean(
            editableRows[groupTerm.id]?.student_count_confirmed ?? groupTerm.student_count_confirmed,
        ),
    }));

    router.patch(
        route('groups.bulk-update'),
        {
            academic_period_id: props.selectedPeriod.id,
            items,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                hasUnsavedChanges.value = false;
            },
            onFinish: () => {
                savingBulk.value = false;
            },
        },
    );
}

const unconfirmedCount = computed(() =>
    props.groupTerms.filter((item) => !editableRows[item.id]?.student_count_confirmed).length,
);

// Modal Nuevo Grupo
const showingCreateModal = ref(false);

const createForm = useForm({
    academic_period_id: props.selectedPeriod?.id ?? 0,
    program_id: props.programs[0]?.id ?? 0,
    curriculum_id: props.programs[0]?.curricula[0]?.id ?? 0,
    shift_id: 0,
    term_number: 1,
    label: '',
    student_count: 20,
    student_count_confirmed: false,
    generate_sections: true,
    suggest_teachers: true,
});

const selectedProgram = computed(() =>
    props.programs.find((prog) => prog.id === Number(createForm.program_id)) ?? null,
);

const availableCurricula = computed(() => selectedProgram.value?.curricula ?? []);

const availableShifts = computed(() => {
    if (!selectedProgram.value) {
        return props.shifts;
    }
    return props.shifts.filter((shift) => shift.modality_id === selectedProgram.value?.modality_id);
});

watch(
    () => createForm.program_id,
    () => {
        createForm.curriculum_id = availableCurricula.value[0]?.id ?? 0;
        if (!availableShifts.value.some((shift) => shift.id === Number(createForm.shift_id))) {
            createForm.shift_id = availableShifts.value[0]?.id ?? 0;
        }
    },
);

function openCreateModal(): void {
    if (!props.selectedPeriod) {
        return;
    }
    createForm.clearErrors();
    createForm.academic_period_id = props.selectedPeriod.id;
    createForm.program_id = props.programs[0]?.id ?? 0;
    createForm.curriculum_id = availableCurricula.value[0]?.id ?? 0;
    createForm.shift_id = availableShifts.value[0]?.id ?? 0;
    createForm.term_number = 1;
    createForm.label = '';
    createForm.student_count = 20;
    createForm.student_count_confirmed = false;
    createForm.generate_sections = true;
    createForm.suggest_teachers = true;
    showingCreateModal.value = true;
}

function submitCreateGroup(): void {
    createForm.post(route('groups.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showingCreateModal.value = false;
        },
    });
}
</script>

<template>
    <Head title="Grupos del periodo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-xl font-semibold leading-tight text-gray-900">
                        Grupos del periodo
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Consulta los grupos activos del cuatrimestre, ajusta la matrícula estimada y confirma alumnos.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div v-if="periods.length > 0" class="flex items-center gap-2">
                        <label for="groups-period-selector" class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Periodo:
                        </label>
                        <select
                            id="groups-period-selector"
                            v-model="selectedPeriodId"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="period in periods" :key="period.id" :value="String(period.id)">
                                {{ period.code }} — {{ period.name }}
                            </option>
                        </select>
                    </div>

                    <Link v-if="selectedPeriod" :href="route('sections.index', { period_id: selectedPeriod.id })">
                        <SecondaryButton>Ver todas las clases</SecondaryButton>
                    </Link>

                    <PrimaryButton :disabled="!selectedPeriod || programs.length === 0" @click="openCreateModal">
                        + Nuevo grupo
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- Sin periodos -->
                <div
                    v-if="!selectedPeriod"
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        No hay periodos académicos creados
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Primero crea un periodo desde el asistente para poder registrar grupos.
                    </p>
                    <div class="mt-4">
                        <Link :href="route('periods.wizard')">
                            <PrimaryButton>Crear primer periodo</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <template v-else>
                    <!-- Barra de filtros -->
                    <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
                        <div class="w-full sm:max-w-xs">
                            <label for="group-search" class="sr-only">Buscar grupo</label>
                            <TextInput
                                id="group-search"
                                v-model="search"
                                type="search"
                                class="block w-full"
                                placeholder="Buscar por etiqueta o carrera..."
                            />
                        </div>

                        <div class="w-full sm:w-52">
                            <label for="group-program-filter" class="sr-only">Filtrar por carrera</label>
                            <select
                                id="group-program-filter"
                                v-model="programFilter"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todas las carreras</option>
                                <option v-for="prog in programs" :key="prog.id" :value="String(prog.id)">
                                    {{ prog.code }} — {{ prog.name }}
                                </option>
                            </select>
                        </div>

                        <div class="w-full sm:w-44">
                            <label for="group-confirmed-filter" class="sr-only">Filtrar por confirmación</label>
                            <select
                                id="group-confirmed-filter"
                                v-model="confirmedFilter"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Todos</option>
                                <option value="unconfirmed">Por confirmar</option>
                                <option value="confirmed">Confirmados</option>
                            </select>
                        </div>

                        <button
                            v-if="search !== '' || programFilter !== '' || confirmedFilter !== 'all'"
                            type="button"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            @click="resetFilters"
                        >
                            Limpiar filtros
                        </button>
                    </div>

                    <!-- Tabla de grupos y edición masiva -->
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div v-if="groupTerms.length === 0" class="p-10 text-center">
                            <h2 class="text-base font-semibold text-gray-900">
                                No hay grupos en este periodo
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{
                                    search !== '' || programFilter !== '' || confirmedFilter !== 'all'
                                        ? 'Prueba ajustando los filtros de búsqueda.'
                                        : 'Registra un grupo nuevo (ej. primer ingreso) o crea el periodo avanzando desde un ciclo anterior.'
                                }}
                            </p>
                            <div class="mt-4 flex justify-center gap-3">
                                <PrimaryButton
                                    v-if="search === '' && programFilter === '' && confirmedFilter === 'all'"
                                    @click="openCreateModal"
                                >
                                    + Nuevo grupo
                                </PrimaryButton>
                                <SecondaryButton v-else @click="resetFilters">
                                    Limpiar filtros
                                </SecondaryButton>
                            </div>
                        </div>

                        <template v-else>
                            <!-- Barra de guardado masivo -->
                            <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-700">
                                    <span class="font-semibold">{{ groupTerms.length }} grupo(s)</span>
                                    <span class="text-gray-300">·</span>
                                    <span
                                        :class="unconfirmedCount > 0 ? 'font-medium text-amber-700' : 'text-emerald-700'"
                                    >
                                        {{
                                            unconfirmedCount > 0
                                                ? `${unconfirmedCount} pendiente(s) de confirmar matrícula`
                                                : 'Todos confirmados'
                                        }}
                                    </span>
                                    <span
                                        v-if="hasUnsavedChanges"
                                        class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
                                    >
                                        Cambios sin guardar
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <SecondaryButton
                                        v-if="unconfirmedCount > 0"
                                        type="button"
                                        :disabled="savingBulk"
                                        @click="confirmAllVisible"
                                    >
                                        Marcar todos confirmados
                                    </SecondaryButton>

                                    <PrimaryButton
                                        type="button"
                                        :class="{ 'opacity-50': savingBulk || !hasUnsavedChanges }"
                                        :disabled="savingBulk || !hasUnsavedChanges"
                                        @click="saveBulk"
                                    >
                                        {{ savingBulk ? 'Guardando...' : 'Guardar matrícula' }}
                                    </PrimaryButton>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Grupo
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Carrera y plan
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Cuatrimestre
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Turno
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Clases
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Alumnos
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Matrícula confirmada
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="item in groupTerms" :key="item.id">
                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm font-semibold text-gray-900">
                                                {{
                                                    item.student_group.label ||
                                                    `${item.student_group.program.code} · ${item.term_number}.º`
                                                }}
                                            </td>
                                            <td class="px-5 py-3.5 text-sm text-gray-700">
                                                <div class="font-medium text-gray-900">
                                                    {{ item.student_group.program.code }} — {{ item.student_group.program.name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Plan {{ item.student_group.curriculum.version }} ·
                                                    {{ item.student_group.program.modality.name }}
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm font-semibold text-indigo-700">
                                                {{ item.term_number }}.º
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-700">
                                                {{ item.student_group.shift.name }}
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-600">
                                                <Link
                                                    :href="route('sections.index', { period_id: selectedPeriod.id, group_term_id: item.id })"
                                                    class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline"
                                                >
                                                    {{ item.sections_count }} clase(s) →
                                                </Link>
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-3.5">
                                                <input
                                                    v-if="editableRows[item.id]"
                                                    v-model.number="editableRows[item.id].student_count"
                                                    type="number"
                                                    min="0"
                                                    max="500"
                                                    aria-label="Número de alumnos"
                                                    class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    @input="markDirty"
                                                />
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-3.5">
                                                <label v-if="editableRows[item.id]" class="inline-flex cursor-pointer items-center gap-2">
                                                    <input
                                                        v-model="editableRows[item.id].student_count_confirmed"
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                        @change="markDirty"
                                                    />
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                            editableRows[item.id].student_count_confirmed
                                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                                                : 'bg-amber-50 text-amber-800 ring-amber-600/20',
                                                        ]"
                                                    >
                                                        {{
                                                            editableRows[item.id].student_count_confirmed
                                                                ? 'Confirmado'
                                                                : 'Por confirmar'
                                                        }}
                                                    </span>
                                                </label>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal Nuevo Grupo -->
        <Modal :show="showingCreateModal" max-width="lg" @close="showingCreateModal = false">
            <form class="p-6" @submit.prevent="submitCreateGroup">
                <h2 class="text-lg font-semibold text-gray-900">
                    Registrar nuevo grupo
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Periodo: <span class="font-medium">{{ selectedPeriod?.code }} — {{ selectedPeriod?.name }}</span>
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="new-group-program" value="Carrera" />
                        <select
                            id="new-group-program"
                            v-model.number="createForm.program_id"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option v-for="prog in programs" :key="prog.id" :value="prog.id">
                                {{ prog.code }} — {{ prog.name }} ({{ prog.modality.name }})
                            </option>
                        </select>
                        <InputError class="mt-1" :message="createForm.errors.program_id" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="new-group-curriculum" value="Plan de estudios" />
                            <select
                                id="new-group-curriculum"
                                v-model.number="createForm.curriculum_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="plan in availableCurricula" :key="plan.id" :value="plan.id">
                                    Plan {{ plan.version }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="createForm.errors.curriculum_id" />
                        </div>

                        <div>
                            <InputLabel for="new-group-shift" value="Turno" />
                            <select
                                id="new-group-shift"
                                v-model.number="createForm.shift_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="shift in availableShifts" :key="shift.id" :value="shift.id">
                                    {{ shift.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="createForm.errors.shift_id" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="new-group-term" value="Cuatrimestre" />
                            <select
                                id="new-group-term"
                                v-model.number="createForm.term_number"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="term in 9" :key="term" :value="term">{{ term }}.º</option>
                            </select>
                            <InputError class="mt-1" :message="createForm.errors.term_number" />
                        </div>

                        <div>
                            <InputLabel for="new-group-students" value="Alumnos estimados" />
                            <input
                                id="new-group-students"
                                v-model.number="createForm.student_count"
                                type="number"
                                min="0"
                                max="500"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="createForm.errors.student_count" />
                        </div>

                        <div>
                            <InputLabel for="new-group-label" value="Etiqueta (opcional)" />
                            <TextInput
                                id="new-group-label"
                                v-model="createForm.label"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ej. ADME-2027-M"
                            />
                            <InputError class="mt-1" :message="createForm.errors.label" />
                        </div>
                    </div>

                    <div class="space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3.5">
                        <label class="flex items-center gap-2.5 text-sm text-gray-800">
                            <input
                                v-model="createForm.student_count_confirmed"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                            />
                            <span>Marcar matrícula como confirmada desde ahora</span>
                        </label>

                        <label class="flex items-center gap-2.5 text-sm text-gray-800">
                            <input
                                v-model="createForm.generate_sections"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                            />
                            <span>Generar automáticamente las clases del plan para este cuatrimestre</span>
                        </label>

                        <label
                            v-if="createForm.generate_sections"
                            class="ms-6 flex items-center gap-2.5 text-xs text-gray-600"
                        >
                            <input
                                v-model="createForm.suggest_teachers"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                            />
                            <span>Sugerir el último docente histórico en cada materia</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="createForm.processing" @click="showingCreateModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-50': createForm.processing || availableCurricula.length === 0 || availableShifts.length === 0 }"
                        :disabled="createForm.processing || availableCurricula.length === 0 || availableShifts.length === 0"
                    >
                        {{ createForm.processing ? 'Creando...' : 'Registrar grupo' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
