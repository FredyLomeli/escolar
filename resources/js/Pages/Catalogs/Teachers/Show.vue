<script setup lang="ts">
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatHour, WEEKDAYS } from '@/Utils/weekdays';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface Option {
    value: string;
    label: string;
}

interface CourseItem {
    id: number;
    subject_code: string;
    course_code: string;
    name: string;
}

interface TeacherCourseItem {
    id: number;
    teacher_id: number;
    course_id: number;
    source: string;
    course: CourseItem;
}

interface TeacherAvailabilityItem {
    id: number;
    teacher_id: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    type: 'available' | 'unavailable';
}

interface TeacherDetail {
    id: number;
    employee_id: string;
    full_name: string;
    email: string | null;
    origin: string;
    max_weekly_hours: number | null;
    active: boolean;
    teacher_courses: TeacherCourseItem[];
    availabilities: TeacherAvailabilityItem[];
}

const props = defineProps<{
    teacher: TeacherDetail;
    availableCourses: CourseItem[];
    originOptions: Option[];
    availabilityTypeOptions: Option[];
    sourceOptions: Option[];
}>();

function originLabel(value: string): string {
    return props.originOptions.find((opt) => opt.value === value)?.label ?? value;
}

function sourceLabel(value: string): string {
    return props.sourceOptions.find((opt) => opt.value === value)?.label ?? value;
}

// Editar datos básicos del docente
const showingEditModal = ref(false);
const editForm = useForm({
    employee_id: props.teacher.employee_id,
    full_name: props.teacher.full_name,
    email: props.teacher.email ?? '',
    origin: props.teacher.origin,
    max_weekly_hours: props.teacher.max_weekly_hours !== null ? String(props.teacher.max_weekly_hours) : '',
});

function openEditModal(): void {
    editForm.clearErrors();
    editForm.employee_id = props.teacher.employee_id;
    editForm.full_name = props.teacher.full_name;
    editForm.email = props.teacher.email ?? '';
    editForm.origin = props.teacher.origin;
    editForm.max_weekly_hours =
        props.teacher.max_weekly_hours !== null ? String(props.teacher.max_weekly_hours) : '';
    showingEditModal.value = true;
}

function submitEdit(): void {
    editForm.put(route('catalogs.teachers.update', props.teacher.id), {
        preserveScroll: true,
        onSuccess: () => {
            showingEditModal.value = false;
        },
    });
}

// Perfil de materias
const courseSearch = ref('');
const filteredCourses = computed(() => {
    const query = courseSearch.value.trim().toLowerCase();
    if (query === '') {
        return props.availableCourses;
    }

    return props.availableCourses.filter((course) =>
        `${course.subject_code} ${course.course_code} ${course.name}`.toLowerCase().includes(query),
    );
});

const courseForm = useForm({
    course_id: props.availableCourses[0]?.id ?? ('' as string | number),
});

function addCourse(): void {
    if (!courseForm.course_id) {
        return;
    }

    courseForm.post(route('catalogs.teachers.courses.store', props.teacher.id), {
        preserveScroll: true,
        onSuccess: () => {
            courseSearch.value = '';
            courseForm.course_id = props.availableCourses[0]?.id ?? '';
        },
    });
}

function removeCourse(teacherCourseId: number): void {
    router.delete(route('catalogs.teacher-courses.destroy', teacherCourseId), {
        preserveScroll: true,
    });
}

// Disponibilidad semanal
const showingAvailabilityModal = ref(false);
const availabilityForm = useForm({
    days_of_week: [1, 2, 3, 4, 5] as number[],
    starts_at: '08:00',
    ends_at: '14:00',
    type: 'available',
});

function openAvailabilityModal(defaultDay?: number): void {
    availabilityForm.clearErrors();
    availabilityForm.days_of_week = defaultDay ? [defaultDay] : [1, 2, 3, 4, 5];
    availabilityForm.starts_at = '08:00';
    availabilityForm.ends_at = '14:00';
    availabilityForm.type = 'available';
    showingAvailabilityModal.value = true;
}

function toggleDay(day: number): void {
    const idx = availabilityForm.days_of_week.indexOf(day);
    if (idx === -1) {
        availabilityForm.days_of_week.push(day);
        availabilityForm.days_of_week.sort((a, b) => a - b);
    } else {
        availabilityForm.days_of_week.splice(idx, 1);
    }
}

function submitAvailability(): void {
    availabilityForm.post(route('catalogs.teachers.availabilities.store', props.teacher.id), {
        preserveScroll: true,
        onSuccess: () => {
            showingAvailabilityModal.value = false;
        },
    });
}

function deleteAvailability(availabilityId: number): void {
    router.delete(route('catalogs.teacher-availabilities.destroy', availabilityId), {
        preserveScroll: true,
    });
}

function availabilitiesForDay(day: number): TeacherAvailabilityItem[] {
    return props.teacher.availabilities.filter((item) => item.day_of_week === day);
}
</script>

<template>
    <Head :title="`Docente · ${teacher.full_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs font-medium text-indigo-600">
                        <Link :href="route('catalogs.teachers.index')" class="hover:underline">
                            ← Volver a Docentes
                        </Link>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-semibold leading-tight text-gray-900">
                            {{ teacher.full_name }}
                        </h1>
                        <code class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-700">
                            ID {{ teacher.employee_id }}
                        </code>
                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">
                            {{ originLabel(teacher.origin) }}
                        </span>
                        <StatusBadge :active="teacher.active" />
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        <template v-if="teacher.email">{{ teacher.email }} · </template>
                        Tope semanal: {{ teacher.max_weekly_hours ? `${teacher.max_weekly_hours} horas` : 'Sin límite definido' }}
                    </p>
                </div>

                <SecondaryButton @click="openEditModal">
                    Editar datos
                </SecondaryButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Bloque 1: Perfil de materias -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                Perfil de materias que puede impartir
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Materias habilitadas manualmente o registradas desde su historial para las propuestas de docentes.
                            </p>
                        </div>

                        <form
                            v-if="availableCourses.length > 0"
                            class="flex flex-col gap-2 sm:flex-row sm:items-center"
                            @submit.prevent="addCourse"
                        >
                            <TextInput
                                v-model="courseSearch"
                                type="search"
                                class="w-full sm:w-48 text-sm"
                                placeholder="Filtrar materia..."
                            />
                            <select
                                v-model="courseForm.course_id"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-72"
                                required
                            >
                                <option v-for="course in filteredCourses" :key="course.id" :value="course.id">
                                    {{ course.subject_code }} {{ course.course_code }} — {{ course.name }}
                                </option>
                            </select>
                            <PrimaryButton
                                type="submit"
                                :disabled="courseForm.processing || filteredCourses.length === 0"
                            >
                                + Agregar
                            </PrimaryButton>
                        </form>
                    </div>

                    <InputError class="mt-2" :message="courseForm.errors.course_id" />

                    <div
                        v-if="teacher.teacher_courses.length === 0"
                        class="mt-5 rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500"
                    >
                        Aún no tiene materias asignadas en su perfil. Selecciona una materia arriba para agregarla.
                    </div>

                    <div v-else class="mt-5 flex flex-wrap gap-2">
                        <span
                            v-for="item in teacher.teacher_courses"
                            :key="item.id"
                            class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-800"
                        >
                            <span class="font-semibold text-indigo-700">
                                {{ item.course.subject_code }} {{ item.course.course_code }}
                            </span>
                            <span>{{ item.course.name }}</span>
                            <span class="rounded bg-white px-1.5 py-0.5 text-[11px] font-medium text-gray-500 ring-1 ring-inset ring-gray-200">
                                {{ sourceLabel(item.source) }}
                            </span>
                            <button
                                type="button"
                                title="Quitar materia del perfil"
                                class="text-gray-400 hover:text-red-600"
                                @click="removeCourse(item.id)"
                            >
                                ×
                            </button>
                        </span>
                    </div>
                </section>

                <!-- Bloque 2: Cuadrícula semanal de disponibilidad -->
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                Cuadrícula semanal de disponibilidad
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Marca las franjas en las que el docente está disponible (verde) o no disponible (rojo).
                            </p>
                        </div>

                        <PrimaryButton @click="openAvailabilityModal()">
                            + Agregar franja
                        </PrimaryButton>
                    </div>

                    <div
                        v-if="teacher.availabilities.length === 0"
                        class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                    >
                        Este docente aún no tiene disponibilidad capturada. En planeación se mostrará una advertencia
                        informativa hasta que registres sus horarios.
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                        <div
                            v-for="day in WEEKDAYS"
                            :key="day.value"
                            class="flex flex-col rounded-lg border border-gray-200 bg-gray-50/50"
                        >
                            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-100/70 px-3 py-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    {{ day.label }}
                                </span>
                                <button
                                    type="button"
                                    :title="`Agregar disponibilidad en ${day.label}`"
                                    class="rounded px-1.5 py-0.5 text-xs font-bold text-indigo-600 hover:bg-indigo-100"
                                    @click="openAvailabilityModal(day.value)"
                                >
                                    +
                                </button>
                            </div>

                            <div class="flex-1 space-y-2 p-2.5">
                                <p
                                    v-if="availabilitiesForDay(day.value).length === 0"
                                    class="py-4 text-center text-xs italic text-gray-400"
                                >
                                    Sin registro
                                </p>

                                <div
                                    v-for="slot in availabilitiesForDay(day.value)"
                                    :key="slot.id"
                                    :class="[
                                        'flex items-center justify-between rounded-md border px-2.5 py-1.5 text-xs font-medium',
                                        slot.type === 'available'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                            : 'border-red-200 bg-red-50 text-red-800',
                                    ]"
                                >
                                    <div>
                                        <div>{{ formatHour(slot.starts_at) }}–{{ formatHour(slot.ends_at) }}</div>
                                        <div class="text-[10px] opacity-80">
                                            {{ slot.type === 'available' ? 'Disponible' : 'No disponible' }}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        title="Eliminar franja"
                                        class="ms-1 text-current opacity-60 hover:opacity-100"
                                        @click="deleteAvailability(slot.id)"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal Editar Docente -->
        <Modal :show="showingEditModal" max-width="md" @close="showingEditModal = false">
            <form class="p-6" @submit.prevent="submitEdit">
                <h2 class="text-lg font-semibold text-gray-900">
                    Editar datos del docente
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="edit-employee-id" value="ID docente" />
                        <TextInput
                            id="edit-employee-id"
                            v-model="editForm.employee_id"
                            type="text"
                            class="mt-1 block w-full font-mono"
                            required
                        />
                        <InputError class="mt-1" :message="editForm.errors.employee_id" />
                    </div>

                    <div>
                        <InputLabel for="edit-full-name" value="Nombre completo" />
                        <TextInput
                            id="edit-full-name"
                            v-model="editForm.full_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-1" :message="editForm.errors.full_name" />
                    </div>

                    <div>
                        <InputLabel for="edit-email" value="Correo electrónico" />
                        <TextInput
                            id="edit-email"
                            v-model="editForm.email"
                            type="email"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-1" :message="editForm.errors.email" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="edit-origin" value="Origen" />
                            <select
                                id="edit-origin"
                                v-model="editForm.origin"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="opt in originOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="editForm.errors.origin" />
                        </div>

                        <div>
                            <InputLabel for="edit-max-hours" value="Tope hrs/semana" />
                            <input
                                id="edit-max-hours"
                                v-model="editForm.max_weekly_hours"
                                type="number"
                                min="1"
                                max="60"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-1" :message="editForm.errors.max_weekly_hours" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="editForm.processing" @click="showingEditModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': editForm.processing }" :disabled="editForm.processing">
                        Guardar cambios
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal Agregar Disponibilidad -->
        <Modal :show="showingAvailabilityModal" max-width="md" @close="showingAvailabilityModal = false">
            <form class="p-6" @submit.prevent="submitAvailability">
                <h2 class="text-lg font-semibold text-gray-900">
                    Agregar franja de disponibilidad
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel for="avail-type" value="Tipo de franja" />
                        <select
                            id="avail-type"
                            v-model="availabilityForm.type"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option v-for="opt in availabilityTypeOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="availabilityForm.errors.type" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="Días" />
                            <div class="flex gap-2 text-xs">
                                <button
                                    type="button"
                                    class="text-indigo-600 hover:underline"
                                    @click="availabilityForm.days_of_week = [1, 2, 3, 4, 5]"
                                >
                                    L–V
                                </button>
                                <button
                                    type="button"
                                    class="text-indigo-600 hover:underline"
                                    @click="availabilityForm.days_of_week = [6]"
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
                                    availabilityForm.days_of_week.includes(day.value)
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                ]"
                                @click="toggleDay(day.value)"
                            >
                                {{ day.short }}
                            </button>
                        </div>
                        <InputError class="mt-1" :message="availabilityForm.errors.days_of_week" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="avail-start" value="Hora inicio" />
                            <input
                                id="avail-start"
                                v-model="availabilityForm.starts_at"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="availabilityForm.errors.starts_at" />
                        </div>

                        <div>
                            <InputLabel for="avail-end" value="Hora fin" />
                            <input
                                id="avail-end"
                                v-model="availabilityForm.ends_at"
                                type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-1" :message="availabilityForm.errors.ends_at" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton
                        type="button"
                        :disabled="availabilityForm.processing"
                        @click="showingAvailabilityModal = false"
                    >
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-50': availabilityForm.processing }"
                        :disabled="availabilityForm.processing"
                    >
                        Guardar franja
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
