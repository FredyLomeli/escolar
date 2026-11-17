<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
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

interface CurriculumCourseItem {
    id: number;
    curriculum_id: number;
    course_id: number;
    term_number: number;
    kind: string;
    default_delivery: string;
    weekly_hours: string | number | null;
    display_name: string | null;
    course: CourseItem;
}

interface TermRuleItem {
    id: number;
    term_number: number;
    expected_courses: number;
}

interface CurriculumDetail {
    id: number;
    program_id: number;
    version: string;
    valid_from: string | null;
    valid_to: string | null;
    active: boolean;
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
    term_rules: TermRuleItem[];
    curriculum_courses: CurriculumCourseItem[];
}

const props = defineProps<{
    curriculum: CurriculumDetail;
    availableCourses: CourseItem[];
    kindOptions: Option[];
    deliveryOptions: Option[];
}>();

const terms = [1, 2, 3, 4, 5, 6, 7, 8, 9];

const expectedByTerm = reactive<Record<number, number>>({});

function syncExpectedRules(): void {
    for (const term of terms) {
        const rule = props.curriculum.term_rules.find((item) => item.term_number === term);
        expectedByTerm[term] = rule ? rule.expected_courses : 5;
    }
}

syncExpectedRules();
watch(() => props.curriculum.term_rules, syncExpectedRules, { deep: true });

function coursesForTerm(term: number): CurriculumCourseItem[] {
    return props.curriculum.curriculum_courses.filter((item) => item.term_number === term);
}

function kindLabel(value: string): string {
    return props.kindOptions.find((option) => option.value === value)?.label ?? value;
}

function deliveryLabel(value: string): string {
    return props.deliveryOptions.find((option) => option.value === value)?.label ?? value;
}

const savingRuleTerm = ref<number | null>(null);

function saveTermRule(term: number): void {
    savingRuleTerm.value = term;
    router.put(
        route('catalogs.curricula.term-rules.update', {
            curriculum: props.curriculum.id,
            termNumber: term,
        }),
        { expected_courses: Number(expectedByTerm[term]) },
        {
            preserveScroll: true,
            onFinish: () => {
                savingRuleTerm.value = null;
            },
        },
    );
}

// Modal agregar / editar materia en la malla
const showingCourseModal = ref(false);
const editingItem = ref<CurriculumCourseItem | null>(null);
const courseSearch = ref('');

const filteredAvailableCourses = computed(() => {
    const query = courseSearch.value.trim().toLowerCase();
    if (query === '') {
        return props.availableCourses;
    }

    return props.availableCourses.filter((course) => {
        const full = `${course.subject_code} ${course.course_code} ${course.name}`.toLowerCase();
        return full.includes(query);
    });
});

const courseForm = useForm({
    course_id: '' as string | number,
    term_number: 1,
    kind: 'regular',
    default_delivery: 'in_person',
    weekly_hours: '4.0',
    display_name: '',
});

function openAddCourseModal(term: number): void {
    editingItem.value = null;
    courseSearch.value = '';
    courseForm.reset();
    courseForm.clearErrors();
    courseForm.term_number = term;
    courseForm.kind = term === 7 ? 'stay' : 'regular';
    courseForm.default_delivery = 'in_person';
    courseForm.weekly_hours = '4.0';
    courseForm.course_id = props.availableCourses[0]?.id ?? '';
    showingCourseModal.value = true;
}

function openEditCourseModal(item: CurriculumCourseItem): void {
    editingItem.value = item;
    courseForm.clearErrors();
    courseForm.course_id = item.course_id;
    courseForm.term_number = item.term_number;
    courseForm.kind = item.kind;
    courseForm.default_delivery = item.default_delivery;
    courseForm.weekly_hours = item.weekly_hours !== null ? String(item.weekly_hours) : '';
    courseForm.display_name = item.display_name ?? '';
    showingCourseModal.value = true;
}

function closeCourseModal(): void {
    showingCourseModal.value = false;
    editingItem.value = null;
}

function submitCourseForm(): void {
    if (editingItem.value) {
        courseForm.put(route('catalogs.curriculum-courses.update', editingItem.value.id), {
            preserveScroll: true,
            onSuccess: () => closeCourseModal(),
        });
    } else {
        courseForm.post(route('catalogs.curricula.courses.store', props.curriculum.id), {
            preserveScroll: true,
            onSuccess: () => closeCourseModal(),
        });
    }
}

// Quitar materia de la malla
const itemToRemove = ref<CurriculumCourseItem | null>(null);
const removing = ref(false);

function confirmRemoveCourse(): void {
    if (!itemToRemove.value) {
        return;
    }

    removing.value = true;
    router.delete(route('catalogs.curriculum-courses.destroy', itemToRemove.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            itemToRemove.value = null;
        },
        onFinish: () => {
            removing.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Malla ${curriculum.program.code} (${curriculum.version})`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-medium text-indigo-600">
                        <Link :href="route('catalogs.programs.index')" class="hover:underline">
                            ← Volver a Carreras y planes
                        </Link>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-semibold leading-tight text-gray-900">
                            Malla curricular · {{ curriculum.program.code }} (Plan {{ curriculum.version }})
                        </h1>
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                curriculum.program.modality.code === 'regular'
                                    ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
                                    : 'bg-purple-50 text-purple-700 ring-purple-600/20',
                            ]"
                        >
                            {{ curriculum.program.modality.name }}
                        </span>
                        <StatusBadge :active="curriculum.active" />
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ curriculum.program.name }} · Total asignadas:
                        <span class="font-semibold text-gray-900">{{ curriculum.curriculum_courses.length }}</span> materias
                    </p>
                </div>

                <!-- Navegación rápida por cuatrimestre -->
                <div class="flex flex-wrap gap-1.5">
                    <a
                        v-for="term in terms"
                        :key="term"
                        :href="`#cuatrimestre-${term}`"
                        class="rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:border-indigo-300 hover:text-indigo-700"
                    >
                        {{ term }}.º
                    </a>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div
                    v-for="term in terms"
                    :id="`cuatrimestre-${term}`"
                    :key="term"
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                >
                    <!-- Cabecera del cuatrimestre -->
                    <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-base font-semibold text-gray-900">
                                {{ term }}.º cuatrimestre
                            </h2>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    coursesForTerm(term).length === expectedByTerm[term]
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                        : 'bg-amber-50 text-amber-800 ring-amber-600/20',
                                ]"
                            >
                                {{ coursesForTerm(term).length }} / {{ expectedByTerm[term] }} materias
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <form class="flex items-center gap-2" @submit.prevent="saveTermRule(term)">
                                <label :for="`expected-${term}`" class="text-xs font-medium text-gray-600">
                                    Esperadas:
                                </label>
                                <input
                                    :id="`expected-${term}`"
                                    v-model.number="expectedByTerm[term]"
                                    type="number"
                                    min="0"
                                    max="20"
                                    class="w-16 rounded-md border-gray-300 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <button
                                    type="submit"
                                    :disabled="savingRuleTerm === term"
                                    class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                >
                                    {{ savingRuleTerm === term ? 'Guardando...' : 'Guardar regla' }}
                                </button>
                            </form>

                            <button
                                type="button"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
                                @click="openAddCourseModal(term)"
                            >
                                + Agregar materia
                            </button>
                        </div>
                    </div>

                    <!-- Materias del cuatrimestre -->
                    <div v-if="coursesForTerm(term).length === 0" class="px-5 py-6 text-center text-sm text-gray-500">
                        Sin materias asignadas en este cuatrimestre.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Clave
                                    </th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Materia
                                    </th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Tipo
                                    </th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Entrega por defecto
                                    </th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Hrs/sem
                                    </th>
                                    <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="item in coursesForTerm(term)" :key="item.id">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-semibold text-gray-900">
                                        {{ item.course.subject_code }} {{ item.course.course_code }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-800">
                                        <div>{{ item.course.name }}</div>
                                        <div v-if="item.display_name" class="text-xs text-gray-500">
                                            En este plan: {{ item.display_name }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-xs">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 font-medium ring-1 ring-inset',
                                                item.kind === 'stay'
                                                    ? 'bg-purple-50 text-purple-700 ring-purple-600/20'
                                                    : item.kind === 'additional'
                                                      ? 'bg-sky-50 text-sky-700 ring-sky-600/20'
                                                      : 'bg-gray-100 text-gray-700 ring-gray-500/20',
                                            ]"
                                        >
                                            {{ kindLabel(item.kind) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-600">
                                        {{ deliveryLabel(item.default_delivery) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-600">
                                        {{ item.weekly_hours ? `${item.weekly_hours} h` : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-medium space-x-3">
                                        <button
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            @click="openEditCourseModal(item)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="text-red-600 hover:text-red-900"
                                            @click="itemToRemove = item"
                                        >
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Agregar / Editar materia en la malla -->
        <Modal :show="showingCourseModal" max-width="lg" @close="closeCourseModal">
            <form class="p-6" @submit.prevent="submitCourseForm">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingItem ? 'Editar materia en el plan' : 'Agregar materia al plan' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div v-if="!editingItem">
                        <div class="flex items-center justify-between">
                            <InputLabel for="curriculum-course-search" value="Materia del catálogo" />
                            <Link
                                :href="route('catalogs.courses.index')"
                                class="text-xs font-medium text-indigo-600 hover:underline"
                            >
                                Ir al catálogo de materias
                            </Link>
                        </div>

                        <div v-if="availableCourses.length === 0" class="mt-2 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                            Todas las materias activas del catálogo ya están asignadas a este plan. Registra una nueva en el
                            catálogo de materias si necesitas añadir más.
                        </div>

                        <template v-else>
                            <TextInput
                                id="curriculum-course-search"
                                v-model="courseSearch"
                                type="search"
                                class="mt-1 block w-full text-sm"
                                placeholder="Filtrar materia por clave o nombre..."
                            />
                            <select
                                v-model="courseForm.course_id"
                                class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option
                                    v-for="course in filteredAvailableCourses"
                                    :key="course.id"
                                    :value="course.id"
                                >
                                    {{ course.subject_code }} {{ course.course_code }} — {{ course.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="courseForm.errors.course_id" />
                        </template>
                    </div>

                    <div v-else class="rounded-md bg-gray-50 p-3 text-sm text-gray-800">
                        <span class="font-semibold">
                            {{ editingItem.course.subject_code }} {{ editingItem.course.course_code }}
                        </span>
                        — {{ editingItem.course.name }}
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="cc-term" value="Cuatrimestre" />
                            <select
                                id="cc-term"
                                v-model="courseForm.term_number"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="term in terms" :key="term" :value="term">
                                    {{ term }}.º cuatrimestre
                                </option>
                            </select>
                            <InputError class="mt-1" :message="courseForm.errors.term_number" />
                        </div>

                        <div>
                            <InputLabel for="cc-kind" value="Tipo" />
                            <select
                                id="cc-kind"
                                v-model="courseForm.kind"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option v-for="opt in kindOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="courseForm.errors.kind" />
                        </div>

                        <div>
                            <InputLabel for="cc-hours" value="Horas semanales (opc.)" />
                            <input
                                id="cc-hours"
                                v-model="courseForm.weekly_hours"
                                type="number"
                                step="0.5"
                                min="0.5"
                                max="40"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-1" :message="courseForm.errors.weekly_hours" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="cc-delivery" value="Entrega por defecto" />
                        <select
                            id="cc-delivery"
                            v-model="courseForm.default_delivery"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option v-for="opt in deliveryOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="courseForm.errors.default_delivery" />
                    </div>

                    <div>
                        <InputLabel for="cc-display-name" value="Nombre comercial en esta carrera (opcional)" />
                        <TextInput
                            id="cc-display-name"
                            v-model="courseForm.display_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Solo si en esta carrera se conoce con otro nombre distinto al canónico"
                        />
                        <InputError class="mt-1" :message="courseForm.errors.display_name" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="courseForm.processing" @click="closeCourseModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-50': courseForm.processing || (!editingItem && availableCourses.length === 0) }"
                        :disabled="courseForm.processing || (!editingItem && availableCourses.length === 0)"
                    >
                        {{ editingItem ? 'Guardar cambios' : 'Agregar a la malla' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Confirmar quitar materia del plan -->
        <ConfirmModal
            :show="itemToRemove !== null"
            title="Quitar materia del plan"
            :message="`¿Deseas quitar ${itemToRemove?.course.subject_code ?? ''} ${itemToRemove?.course.course_code ?? ''} (${itemToRemove?.course.name ?? ''}) de este plan? La materia seguirá existiendo en el catálogo general.`"
            confirm-label="Sí, quitar del plan"
            :processing="removing"
            @close="itemToRemove = null"
            @confirm="confirmRemoveCourse"
        />
    </AuthenticatedLayout>
</template>
