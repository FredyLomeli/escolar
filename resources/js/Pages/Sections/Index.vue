<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatHour, WEEKDAYS, weekdayShort } from '@/Utils/weekdays';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface PeriodSummary {
    id: number;
    code: string;
    name: string;
    weeks: number;
    status: string;
    status_label: string;
}

interface GroupTermOption {
    id: number;
    shift_id: number;
    term_number: number;
    student_count: number;
    label: string;
    program_name: string;
    shift_name: string;
}

interface TeacherOption {
    id: number;
    employee_id: string;
    full_name: string;
    origin: string;
}

interface ClassroomOption {
    id: number;
    code: string;
    name: string;
    capacity: number;
}

interface ShiftOption {
    id: number;
    modality_id: number;
    code: string;
    name: string;
    modality_name: string;
}

interface CourseOption {
    id: number;
    subject_code: string;
    course_code: string;
    name: string;
}

interface DeliveryOption {
    value: string;
    label: string;
    is_online: boolean;
}

interface StatusOption {
    value: string;
    label: string;
}

interface SectionMeetingItem {
    id?: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    classroom_id: number | null;
    classroom?: ClassroomOption | null;
}

interface SectionGroupTermItem {
    id: number;
    term_number: number;
    student_count: number;
    label: string;
    program_code: string;
    shift_name: string;
}

interface SectionItem {
    id: number;
    academic_period_id: number;
    course_id: number;
    crn: string | null;
    teacher_id: number | null;
    delivery: string;
    delivery_label: string;
    is_online: boolean;
    shift_id: number;
    start_week: number;
    end_week: number;
    students_override: number | null;
    inherited_students: number;
    effective_students: number;
    is_compacted: boolean;
    status: string;
    status_label: string;
    notes: string | null;
    course: CourseOption;
    teacher: {
        id: number;
        employee_id: string;
        full_name: string;
    } | null;
    shift: {
        id: number;
        name: string;
        modality_name: string;
    };
    group_terms: SectionGroupTermItem[];
    meetings: SectionMeetingItem[];
}

interface MeetingFormRow {
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    classroom_id: string;
}

const props = defineProps<{
    periods: PeriodSummary[];
    selectedPeriod: PeriodSummary | null;
    sections: SectionItem[];
    groupTerms: GroupTermOption[];
    teachers: TeacherOption[];
    classrooms: ClassroomOption[];
    shifts: ShiftOption[];
    courses: CourseOption[];
    deliveryOptions: DeliveryOption[];
    statusOptions: StatusOption[];
    filters: {
        search: string;
        group_term_id: number | null;
        delivery: string;
        pending: string;
    };
}>();

const selectedPeriodId = ref<string>(props.selectedPeriod ? String(props.selectedPeriod.id) : '');
const search = ref(props.filters.search);
const groupTermFilter = ref<string>(props.filters.group_term_id ? String(props.filters.group_term_id) : '');
const deliveryFilter = ref<string>(props.filters.delivery);
const pendingFilter = ref<string>(props.filters.pending);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilters(): void {
    router.get(
        route('sections.index'),
        {
            period_id: selectedPeriodId.value || undefined,
            search: search.value || undefined,
            group_term_id: groupTermFilter.value !== '' ? groupTermFilter.value : undefined,
            delivery: deliveryFilter.value !== 'all' ? deliveryFilter.value : undefined,
            pending: pendingFilter.value !== 'all' ? pendingFilter.value : undefined,
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

watch([selectedPeriodId, groupTermFilter, deliveryFilter, pendingFilter], applyFilters);

function resetFilters(): void {
    search.value = '';
    groupTermFilter.value = '';
    deliveryFilter.value = 'all';
    pendingFilter.value = 'all';
}

const hasActiveFilters = computed(
    () =>
        search.value !== '' ||
        groupTermFilter.value !== '' ||
        deliveryFilter.value !== 'all' ||
        pendingFilter.value !== 'all',
);

const weekdayOptions = WEEKDAYS.filter((day) => day.value <= 6);

// Modal crear / editar sección
const showingFormModal = ref(false);
const editingSection = ref<SectionItem | null>(null);

const sectionForm = useForm({
    academic_period_id: props.selectedPeriod?.id ?? 0,
    group_term_id: '',
    course_id: '',
    crn: '',
    teacher_id: '',
    delivery: 'in_person',
    shift_id: '',
    start_week: 1,
    end_week: props.selectedPeriod?.weeks ?? 15,
    students_override: '',
    status: 'draft',
    notes: '',
    meetings: [] as MeetingFormRow[],
});

const selectedGroupForCreate = computed(() =>
    props.groupTerms.find((item) => String(item.id) === sectionForm.group_term_id) ?? null,
);

const currentInheritedStudents = computed(() => {
    if (editingSection.value) {
        return editingSection.value.inherited_students;
    }

    return selectedGroupForCreate.value?.student_count ?? 0;
});

const isSelectedDeliveryOnline = computed(
    () => props.deliveryOptions.find((option) => option.value === sectionForm.delivery)?.is_online ?? false,
);

watch(
    () => sectionForm.group_term_id,
    (newGroupTermId) => {
        if (editingSection.value !== null) {
            return;
        }
        const matched = props.groupTerms.find((item) => String(item.id) === newGroupTermId);
        if (matched) {
            sectionForm.shift_id = String(matched.shift_id);
        }
    },
);

function openCreateModal(): void {
    if (!props.selectedPeriod) {
        return;
    }

    const defaultGroup =
        props.groupTerms.find((item) => String(item.id) === groupTermFilter.value) ?? props.groupTerms[0] ?? null;

    editingSection.value = null;
    sectionForm.clearErrors();
    sectionForm.academic_period_id = props.selectedPeriod.id;
    sectionForm.group_term_id = defaultGroup ? String(defaultGroup.id) : '';
    sectionForm.course_id = props.courses[0] ? String(props.courses[0].id) : '';
    sectionForm.crn = '';
    sectionForm.teacher_id = '';
    sectionForm.delivery = 'in_person';
    sectionForm.shift_id = defaultGroup ? String(defaultGroup.shift_id) : props.shifts[0] ? String(props.shifts[0].id) : '';
    sectionForm.start_week = 1;
    sectionForm.end_week = props.selectedPeriod.weeks;
    sectionForm.students_override = '';
    sectionForm.status = 'draft';
    sectionForm.notes = '';
    sectionForm.meetings = [];
    showingFormModal.value = true;
}

function openEditModal(section: SectionItem): void {
    editingSection.value = section;
    sectionForm.clearErrors();
    sectionForm.academic_period_id = section.academic_period_id;
    sectionForm.group_term_id = '';
    sectionForm.course_id = String(section.course_id);
    sectionForm.crn = section.crn ?? '';
    sectionForm.teacher_id = section.teacher_id !== null ? String(section.teacher_id) : '';
    sectionForm.delivery = section.delivery;
    sectionForm.shift_id = String(section.shift_id);
    sectionForm.start_week = section.start_week;
    sectionForm.end_week = section.end_week;
    sectionForm.students_override = section.students_override !== null ? String(section.students_override) : '';
    sectionForm.status = section.status;
    sectionForm.notes = section.notes ?? '';
    sectionForm.meetings = section.meetings.map((meeting) => ({
        day_of_week: meeting.day_of_week,
        starts_at: formatHour(meeting.starts_at),
        ends_at: formatHour(meeting.ends_at),
        classroom_id: meeting.classroom_id !== null ? String(meeting.classroom_id) : '',
    }));
    showingFormModal.value = true;
}

function addMeeting(): void {
    sectionForm.meetings.push({
        day_of_week: 1,
        starts_at: '07:00',
        ends_at: '09:00',
        classroom_id: '',
    });
}

function removeMeeting(index: number): void {
    sectionForm.meetings.splice(index, 1);
}

function setWeekRange(start: number, end: number): void {
    sectionForm.start_week = start;
    sectionForm.end_week = end;
}

function meetingError(index: number, field: string): string | undefined {
    const errors = sectionForm.errors as Record<string, string | undefined>;
    return errors[`meetings.${index}.${field}`];
}

function submitSectionForm(): void {
    if (editingSection.value) {
        sectionForm.put(route('sections.update', editingSection.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showingFormModal.value = false;
            },
        });
        return;
    }

    sectionForm.post(route('sections.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showingFormModal.value = false;
        },
    });
}

// Confirmación de eliminación
const sectionToDelete = ref<SectionItem | null>(null);
const deletingSection = ref(false);

function confirmDelete(section: SectionItem): void {
    sectionToDelete.value = section;
}

function executeDelete(): void {
    if (!sectionToDelete.value) {
        return;
    }

    deletingSection.value = true;
    router.delete(route('sections.destroy', sectionToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            sectionToDelete.value = null;
        },
        onFinish: () => {
            deletingSection.value = false;
        },
    });
}
</script>

<template>
    <Head title="Clases del periodo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <Link
                            :href="route('groups.index', selectedPeriod ? { period_id: selectedPeriod.id } : {})"
                            class="font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            ← Volver a grupos
                        </Link>
                    </div>
                    <h1 class="mt-1 text-xl font-semibold leading-tight text-gray-900">
                        Clases y secciones del periodo
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Asigna docente, tipo de entrega, rango de semanas y sesiones con horario y aula.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div v-if="periods.length > 0" class="flex items-center gap-2">
                        <label
                            for="sections-period-selector"
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            Periodo:
                        </label>
                        <select
                            id="sections-period-selector"
                            v-model="selectedPeriodId"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="period in periods" :key="period.id" :value="String(period.id)">
                                {{ period.code }} — {{ period.name }}
                            </option>
                        </select>
                    </div>

                    <PrimaryButton
                        :disabled="!selectedPeriod || groupTerms.length === 0 || courses.length === 0"
                        @click="openCreateModal"
                    >
                        + Nueva clase
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
                        Crea primero un periodo desde el asistente para comenzar a editar sus clases.
                    </p>
                    <div class="mt-4">
                        <Link :href="route('periods.wizard')">
                            <PrimaryButton>Crear primer periodo</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <template v-else>
                    <!-- Barra de filtros -->
                    <div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
                        <div>
                            <label for="section-search" class="sr-only">Buscar clase</label>
                            <TextInput
                                id="section-search"
                                v-model="search"
                                type="search"
                                class="block w-full"
                                placeholder="Buscar materia, clave o CRN..."
                            />
                        </div>

                        <div>
                            <label for="section-group-filter" class="sr-only">Filtrar por grupo</label>
                            <select
                                id="section-group-filter"
                                v-model="groupTermFilter"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todos los grupos</option>
                                <option v-for="gt in groupTerms" :key="gt.id" :value="String(gt.id)">
                                    {{ gt.label }} ({{ gt.shift_name }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="section-delivery-filter" class="sr-only">Filtrar por entrega</label>
                            <select
                                id="section-delivery-filter"
                                v-model="deliveryFilter"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Todas las entregas</option>
                                <option v-for="option in deliveryOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="section-pending-filter" class="sr-only">Filtrar por pendientes</label>
                            <select
                                id="section-pending-filter"
                                v-model="pendingFilter"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Cualquier estado</option>
                                <option value="without_teacher">Sin docente (presenciales)</option>
                                <option value="without_schedule">Sin horario o aula</option>
                            </select>

                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                @click="resetFilters"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Listado -->
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div v-if="sections.length === 0" class="p-10 text-center">
                            <h2 class="text-base font-semibold text-gray-900">
                                No hay clases que coincidan
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{
                                    hasActiveFilters
                                        ? 'Ajusta los filtros para ver otras clases del periodo.'
                                        : 'Registra grupos con generación desde su plan o crea una clase manualmente.'
                                }}
                            </p>
                            <div class="mt-4 flex justify-center gap-3">
                                <SecondaryButton v-if="hasActiveFilters" @click="resetFilters">
                                    Limpiar filtros
                                </SecondaryButton>
                                <PrimaryButton
                                    v-else-if="groupTerms.length > 0 && courses.length > 0"
                                    @click="openCreateModal"
                                >
                                    + Nueva clase
                                </PrimaryButton>
                            </div>
                        </div>

                        <template v-else>
                            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50/70 px-5 py-3 text-sm text-gray-700">
                                <span class="font-semibold">{{ sections.length }} clase(s)</span>
                                <span class="text-xs text-gray-500">
                                    Periodo {{ selectedPeriod.code }} · {{ selectedPeriod.weeks }} semanas
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Materia y CRN
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Grupo(s)
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Docente
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Entrega y semanas
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Sesiones
                                            </th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Alumnos
                                            </th>
                                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="section in sections" :key="section.id" class="align-top">
                                            <td class="px-5 py-3.5 text-sm">
                                                <div class="font-semibold text-gray-900">
                                                    {{ section.course.subject_code }} {{ section.course.course_code }}
                                                    — {{ section.course.name }}
                                                </div>
                                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                                        {{ section.crn ? `CRN ${section.crn}` : 'Sin CRN' }}
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                            section.status === 'confirmed'
                                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                                                : 'bg-amber-50 text-amber-800 ring-amber-600/20',
                                                        ]"
                                                    >
                                                        {{ section.status_label }}
                                                    </span>
                                                    <span
                                                        v-if="section.is_compacted"
                                                        class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20"
                                                    >
                                                        Compactada
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-5 py-3.5 text-sm">
                                                <div class="flex flex-wrap gap-1">
                                                    <span
                                                        v-for="gt in section.group_terms"
                                                        :key="gt.id"
                                                        class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10"
                                                    >
                                                        {{ gt.label }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-5 py-3.5 text-sm">
                                                <template v-if="section.teacher">
                                                    <div class="font-medium text-gray-900">
                                                        {{ section.teacher.full_name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        ID {{ section.teacher.employee_id }}
                                                    </div>
                                                </template>
                                                <span
                                                    v-else-if="section.is_online"
                                                    class="text-xs font-medium text-gray-500"
                                                >
                                                    Sin docente (en línea)
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20"
                                                >
                                                    Pendiente de docente
                                                </span>
                                            </td>

                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-700">
                                                <div class="font-medium text-gray-900">
                                                    {{ section.delivery_label }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ section.shift.name }} · Sem. {{ section.start_week }}–{{ section.end_week }}
                                                </div>
                                            </td>

                                            <td class="px-5 py-3.5 text-sm">
                                                <div v-if="section.meetings.length === 0" class="text-xs font-medium text-amber-700">
                                                    Sin horario asignado
                                                </div>
                                                <ul v-else class="space-y-1 text-xs text-gray-700">
                                                    <li v-for="(meeting, idx) in section.meetings" :key="meeting.id ?? idx">
                                                        <span class="font-semibold">{{ weekdayShort(meeting.day_of_week) }}</span>
                                                        {{ meeting.starts_at }}–{{ meeting.ends_at }}
                                                        <span class="text-gray-400">·</span>
                                                        <span :class="meeting.classroom ? 'text-gray-700' : 'font-medium text-amber-700'">
                                                            {{ meeting.classroom ? meeting.classroom.code : 'Sin aula' }}
                                                        </span>
                                                    </li>
                                                </ul>
                                            </td>

                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-800">
                                                <div class="font-semibold">
                                                    {{ section.effective_students }}
                                                </div>
                                                <div v-if="section.students_override !== null" class="text-xs text-indigo-600">
                                                    Ajuste manual
                                                </div>
                                            </td>

                                            <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm">
                                                <div class="inline-flex items-center gap-2">
                                                    <SecondaryButton @click="openEditModal(section)">
                                                        Editar
                                                    </SecondaryButton>
                                                    <button
                                                        type="button"
                                                        class="text-xs font-medium text-red-600 hover:text-red-800"
                                                        @click="confirmDelete(section)"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </div>
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

        <!-- Modal Crear / Editar Sección -->
        <Modal :show="showingFormModal" max-width="2xl" @close="showingFormModal = false">
            <form class="p-6" @submit.prevent="submitSectionForm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ editingSection ? 'Editar clase' : 'Nueva clase en el periodo' }}
                        </h2>
                        <p v-if="editingSection" class="mt-1 text-sm text-gray-600">
                            {{ editingSection.course.subject_code }} {{ editingSection.course.course_code }}
                            — {{ editingSection.course.name }}
                            ({{ editingSection.group_terms.map((gt) => gt.label).join(', ') }})
                        </p>
                        <p v-else class="mt-1 text-sm text-gray-600">
                            Periodo: {{ selectedPeriod?.code }} — {{ selectedPeriod?.name }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <!-- Grupo y materia al crear -->
                    <div v-if="!editingSection" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="section-group-term" value="Grupo del periodo" />
                            <select
                                id="section-group-term"
                                v-model="sectionForm.group_term_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="gt in groupTerms" :key="gt.id" :value="String(gt.id)">
                                    {{ gt.label }} — {{ gt.program_name }} ({{ gt.shift_name }})
                                </option>
                            </select>
                            <InputError :message="sectionForm.errors.group_term_id" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="section-course" value="Materia" />
                            <select
                                id="section-course"
                                v-model="sectionForm.course_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="course in courses" :key="course.id" :value="String(course.id)">
                                    {{ course.subject_code }} {{ course.course_code }} — {{ course.name }}
                                </option>
                            </select>
                            <InputError :message="sectionForm.errors.course_id" class="mt-1" />
                        </div>
                    </div>

                    <!-- Docente y entrega -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="section-delivery" value="Modalidad de entrega" />
                            <select
                                id="section-delivery"
                                v-model="sectionForm.delivery"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="option in deliveryOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="sectionForm.errors.delivery" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="section-teacher" value="Docente" />
                            <select
                                id="section-teacher"
                                v-model="sectionForm.teacher_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Sin docente asignado</option>
                                <option v-for="teacher in teachers" :key="teacher.id" :value="String(teacher.id)">
                                    {{ teacher.full_name }} ({{ teacher.employee_id }})
                                </option>
                            </select>
                            <p v-if="isSelectedDeliveryOnline" class="mt-1 text-xs text-gray-500">
                                En materias en línea el docente es opcional si se asigna desde otro campus.
                            </p>
                            <InputError :message="sectionForm.errors.teacher_id" class="mt-1" />
                        </div>
                    </div>

                    <!-- Turno, CRN, Estado, Alumnos ajustados -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div>
                            <InputLabel for="section-shift" value="Turno" />
                            <select
                                id="section-shift"
                                v-model="sectionForm.shift_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="shift in shifts" :key="shift.id" :value="String(shift.id)">
                                    {{ shift.name }} ({{ shift.modality_name }})
                                </option>
                            </select>
                            <InputError :message="sectionForm.errors.shift_id" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="section-crn" value="CRN (opcional)" />
                            <TextInput
                                id="section-crn"
                                v-model="sectionForm.crn"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ej. 45210"
                                maxlength="32"
                            />
                            <InputError :message="sectionForm.errors.crn" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="section-status" value="Estado" />
                            <select
                                id="section-status"
                                v-model="sectionForm.status"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                            <InputError :message="sectionForm.errors.status" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="section-students-override" value="Alumnos (opcional)" />
                            <TextInput
                                id="section-students-override"
                                v-model="sectionForm.students_override"
                                type="number"
                                min="0"
                                max="500"
                                class="mt-1 block w-full"
                                :placeholder="`Grupo: ${currentInheritedStudents}`"
                            />
                            <InputError :message="sectionForm.errors.students_override" class="mt-1" />
                        </div>
                    </div>

                    <!-- Rango de semanas -->
                    <div class="rounded-md border border-gray-200 bg-gray-50/70 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                Semanas en que se imparte (1 a {{ selectedPeriod?.weeks ?? 15 }})
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    type="button"
                                    class="rounded bg-white px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-100"
                                    @click="setWeekRange(1, selectedPeriod?.weeks ?? 15)"
                                >
                                    Completo (1–{{ selectedPeriod?.weeks ?? 15 }})
                                </button>
                                <button
                                    v-if="(selectedPeriod?.weeks ?? 15) >= 5"
                                    type="button"
                                    class="rounded bg-white px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-100"
                                    @click="setWeekRange(1, 5)"
                                >
                                    Módulo 1 (1–5)
                                </button>
                                <button
                                    v-if="(selectedPeriod?.weeks ?? 15) >= 10"
                                    type="button"
                                    class="rounded bg-white px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-100"
                                    @click="setWeekRange(6, 10)"
                                >
                                    Módulo 2 (6–10)
                                </button>
                                <button
                                    v-if="(selectedPeriod?.weeks ?? 15) >= 11"
                                    type="button"
                                    class="rounded bg-white px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-100"
                                    @click="setWeekRange(11, selectedPeriod?.weeks ?? 15)"
                                >
                                    Módulo 3 (11–{{ selectedPeriod?.weeks ?? 15 }})
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="section-start-week" value="Semana inicial" />
                                <input
                                    id="section-start-week"
                                    v-model.number="sectionForm.start_week"
                                    type="number"
                                    min="1"
                                    :max="selectedPeriod?.weeks ?? 15"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError :message="sectionForm.errors.start_week" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="section-end-week" value="Semana final" />
                                <input
                                    id="section-end-week"
                                    v-model.number="sectionForm.end_week"
                                    type="number"
                                    min="1"
                                    :max="selectedPeriod?.weeks ?? 15"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError :message="sectionForm.errors.end_week" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Sesiones semanales -->
                    <div class="rounded-md border border-gray-200 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Sesiones semanales
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Define día, horario y aula (opcional) para cada sesión de la semana.
                                </p>
                            </div>
                            <SecondaryButton type="button" @click="addMeeting">
                                + Agregar sesión
                            </SecondaryButton>
                        </div>

                        <div v-if="sectionForm.meetings.length === 0" class="mt-3 rounded bg-gray-50 p-3 text-center text-xs text-gray-500">
                            Sin sesiones configuradas todavía.
                        </div>

                        <div v-else class="mt-3 space-y-2.5">
                            <div
                                v-for="(meeting, index) in sectionForm.meetings"
                                :key="index"
                                class="rounded-md border border-gray-200 bg-gray-50/50 p-2.5"
                            >
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-12 sm:items-center">
                                    <div class="sm:col-span-3">
                                        <select
                                            v-model.number="meeting.day_of_week"
                                            aria-label="Día de la semana"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option v-for="day in weekdayOptions" :key="day.value" :value="day.value">
                                                {{ day.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <input
                                            v-model="meeting.starts_at"
                                            type="time"
                                            aria-label="Hora de inicio"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        />
                                    </div>

                                    <div class="sm:col-span-2">
                                        <input
                                            v-model="meeting.ends_at"
                                            type="time"
                                            aria-label="Hora de fin"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        />
                                    </div>

                                    <div class="sm:col-span-4">
                                        <select
                                            v-model="meeting.classroom_id"
                                            aria-label="Aula"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Sin aula asignada</option>
                                            <option
                                                v-for="classroom in classrooms"
                                                :key="classroom.id"
                                                :value="String(classroom.id)"
                                            >
                                                {{ classroom.code }} — {{ classroom.name }} ({{ classroom.capacity }} lug.)
                                            </option>
                                        </select>
                                    </div>

                                    <div class="text-right sm:col-span-1">
                                        <DangerButton type="button" class="!px-2 !py-1.5 text-xs" @click="removeMeeting(index)">
                                            Quitar
                                        </DangerButton>
                                    </div>
                                </div>

                                <InputError :message="meetingError(index, 'day_of_week')" class="mt-1" />
                                <InputError :message="meetingError(index, 'starts_at')" class="mt-1" />
                                <InputError :message="meetingError(index, 'ends_at')" class="mt-1" />
                                <InputError :message="meetingError(index, 'classroom_id')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div>
                        <InputLabel for="section-notes" value="Notas internas (opcional)" />
                        <textarea
                            id="section-notes"
                            v-model="sectionForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Observaciones sobre asignación, aula especial o compactación..."
                        />
                        <InputError :message="sectionForm.errors.notes" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="sectionForm.processing" @click="showingFormModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :class="{ 'opacity-25': sectionForm.processing }"
                        :disabled="sectionForm.processing"
                    >
                        {{
                            sectionForm.processing
                                ? 'Guardando...'
                                : editingSection
                                  ? 'Guardar cambios'
                                  : 'Crear clase'
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal confirmar eliminación -->
        <ConfirmModal
            :show="sectionToDelete !== null"
            title="Eliminar clase"
            :message="
                sectionToDelete
                    ? `¿Deseas eliminar la clase ${sectionToDelete.course.subject_code} ${sectionToDelete.course.course_code} (${sectionToDelete.course.name}) y sus sesiones programadas?`
                    : ''
            "
            confirm-label="Eliminar clase"
            :processing="deletingSection"
            @close="sectionToDelete = null"
            @confirm="executeDelete"
        />
    </AuthenticatedLayout>
</template>
