<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

interface SourcePeriod {
    id: number;
    code: string;
    name: string;
    year: number;
    term_of_year: number;
    weeks: number;
    status: string;
    status_label: string;
}

interface AdvancingGroup {
    student_group_id: number;
    label: string;
    program_code: string;
    program_name: string;
    shift_name: string;
    current_term: number;
    next_term: number;
    student_count: number;
    estimated_sections: number;
}

interface GraduatingGroup {
    student_group_id: number;
    label: string;
    program_code: string;
    program_name: string;
    shift_name: string;
    current_term: number;
    student_count: number;
}

interface PreviewPayload {
    advancing: AdvancingGroup[];
    graduating: GraduatingGroup[];
}

const props = defineProps<{
    sourcePeriods: SourcePeriod[];
    initialSourceId: number | null;
    initialPreview: PreviewPayload;
    suggested: {
        code: string;
        name: string;
        year: number;
        term_of_year: number;
        starts_on: string;
        ends_on: string;
        weeks: number;
    };
}>();

const step = ref(1);
const mode = ref<'advance' | 'empty'>(props.sourcePeriods.length > 0 ? 'advance' : 'empty');
const preview = ref<PreviewPayload>(props.initialPreview);
const loadingPreview = ref(false);

const form = useForm({
    source_period_id: props.initialSourceId as number | null,
    code: props.suggested.code,
    name: props.suggested.name,
    year: props.suggested.year,
    term_of_year: props.suggested.term_of_year,
    starts_on: props.suggested.starts_on,
    ends_on: props.suggested.ends_on,
    weeks: props.suggested.weeks,
    copy_meetings: false,
    suggest_teachers: true,
    student_count_overrides: {} as Record<number, number>,
    excluded_group_ids: [] as number[],
});

function syncStudentOverrides(data: PreviewPayload): void {
    const map: Record<number, number> = {};
    for (const item of data.advancing) {
        map[item.student_group_id] = item.student_count;
    }
    form.student_count_overrides = map;
    form.excluded_group_ids = [];
}

syncStudentOverrides(props.initialPreview);

async function fetchPreview(periodId: number | null): Promise<void> {
    if (!periodId) {
        preview.value = { advancing: [], graduating: [] };
        syncStudentOverrides(preview.value);
        return;
    }

    loadingPreview.value = true;
    try {
        const response = await window.axios.get<PreviewPayload>(route('periods.preview', periodId));
        preview.value = response.data;
        syncStudentOverrides(response.data);
    } finally {
        loadingPreview.value = false;
    }
}

watch(mode, (newMode) => {
    if (newMode === 'empty') {
        form.source_period_id = null;
        preview.value = { advancing: [], graduating: [] };
        syncStudentOverrides(preview.value);
    } else if (props.sourcePeriods.length > 0) {
        form.source_period_id = props.sourcePeriods[0].id;
        void fetchPreview(form.source_period_id);
    }
});

watch(
    () => form.source_period_id,
    (newId) => {
        if (mode.value === 'advance' && newId) {
            void fetchPreview(newId);
        }
    },
);

const codeManuallyEdited = ref(false);

function applyTermPreset(year: number, termOfYear: number): void {
    const presets: Record<number, { suffix: string; label: string; start: string; end: string }> = {
        1: { suffix: '20', label: `Ene – May ${year}`, start: `${year}-01-11`, end: `${year}-05-01` },
        2: { suffix: '40', label: `May – Ago ${year}`, start: `${year}-05-11`, end: `${year}-08-21` },
        3: { suffix: '60', label: `Ago – Dic ${year}`, start: `${year}-08-24`, end: `${year}-12-11` },
    };

    const preset = presets[termOfYear] ?? presets[1];
    if (!codeManuallyEdited.value) {
        form.code = `${year}${preset.suffix}`;
    }
    form.name = preset.label;
    form.starts_on = preset.start;
    form.ends_on = preset.end;
}

watch(
    () => [form.year, form.term_of_year],
    ([newYear, newTerm]) => {
        applyTermPreset(Number(newYear), Number(newTerm));
    },
);

function formatReadableDate(iso: string): string {
    if (!iso) {
        return '—';
    }
    return format(parseISO(iso), "d 'de' MMMM yyyy", { locale: es });
}

function isExcluded(groupId: number): boolean {
    return form.excluded_group_ids.includes(groupId);
}

function toggleGroupInclusion(groupId: number): void {
    const idx = form.excluded_group_ids.indexOf(groupId);
    if (idx === -1) {
        form.excluded_group_ids.push(groupId);
    } else {
        form.excluded_group_ids.splice(idx, 1);
    }
}

const includedAdvancingGroups = computed(() =>
    preview.value.advancing.filter((item) => !isExcluded(item.student_group_id)),
);

const totalEstimatedSections = computed(() =>
    includedAdvancingGroups.value.reduce((acc, item) => acc + item.estimated_sections, 0),
);

const steps = [
    { number: 1, title: 'Origen y datos' },
    { number: 2, title: 'Grupos que avanzan' },
    { number: 3, title: 'Opciones' },
    { number: 4, title: 'Resumen y creación' },
];

function nextStep(): void {
    if (step.value < 4) {
        step.value += 1;
    }
}

function prevStep(): void {
    if (step.value > 1) {
        step.value -= 1;
    }
}

function submitWizard(): void {
    form.post(route('periods.store'), {
        onError: () => {
            if (
                form.errors.code ||
                form.errors.name ||
                form.errors.starts_on ||
                form.errors.ends_on ||
                form.errors.weeks
            ) {
                step.value = 1;
            }
        },
    });
}
</script>

<template>
    <Head title="Crear siguiente periodo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link :href="route('dashboard')" class="text-xs font-medium text-indigo-600 hover:underline">
                        ← Volver al inicio
                    </Link>
                    <h1 class="mt-1 text-xl font-semibold leading-tight text-gray-900">
                        Asistente: Crear siguiente periodo
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Avanza los grupos activos al siguiente cuatrimestre y genera sus secciones desde el plan de estudios.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Indicador de pasos -->
                <nav aria-label="Pasos del asistente" class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <button
                        v-for="item in steps"
                        :key="item.number"
                        type="button"
                        :class="[
                            'flex items-center gap-2 rounded-lg border px-3 py-2.5 text-left text-xs font-semibold transition',
                            step === item.number
                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                : step > item.number
                                  ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                  : 'border-gray-200 bg-white text-gray-500',
                        ]"
                        @click="step = item.number"
                    >
                        <span
                            :class="[
                                'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px]',
                                step === item.number
                                    ? 'bg-indigo-600 text-white'
                                    : step > item.number
                                      ? 'bg-emerald-600 text-white'
                                      : 'bg-gray-200 text-gray-600',
                            ]"
                        >
                            {{ item.number }}
                        </span>
                        <span class="truncate">{{ item.title }}</span>
                    </button>
                </nav>

                <form class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="submitWizard">
                    <!-- PASO 1: Origen y datos -->
                    <div v-show="step === 1" class="space-y-5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                1. Periodo origen y datos del nuevo cuatrimestre
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Elige si partirás de un periodo anterior (recomendado) o crearás uno vacío.
                            </p>
                        </div>

                        <div v-if="sourcePeriods.length > 0" class="grid gap-3 sm:grid-cols-2">
                            <label
                                :class="[
                                    'flex cursor-pointer items-start gap-3 rounded-lg border p-3.5',
                                    mode === 'advance' ? 'border-indigo-600 bg-indigo-50/40' : 'border-gray-200',
                                ]"
                            >
                                <input v-model="mode" type="radio" value="advance" class="mt-1 text-indigo-600" />
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Avanzar desde periodo anterior</div>
                                    <div class="text-xs text-gray-600">Promueve grupos activos y genera sus materias.</div>
                                </div>
                            </label>

                            <label
                                :class="[
                                    'flex cursor-pointer items-start gap-3 rounded-lg border p-3.5',
                                    mode === 'empty' ? 'border-indigo-600 bg-indigo-50/40' : 'border-gray-200',
                                ]"
                            >
                                <input v-model="mode" type="radio" value="empty" class="mt-1 text-indigo-600" />
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Crear periodo vacío</div>
                                    <div class="text-xs text-gray-600">Sin grupos previos; se registran manualmente después.</div>
                                </div>
                            </label>
                        </div>

                        <div v-if="mode === 'advance' && sourcePeriods.length > 0">
                            <InputLabel for="wizard-source" value="Periodo origen" />
                            <select
                                id="wizard-source"
                                v-model="form.source_period_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="period in sourcePeriods" :key="period.id" :value="period.id">
                                    {{ period.code }} — {{ period.name }} ({{ period.status_label }})
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.source_period_id" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="wizard-code" value="Código único del periodo" />
                                <TextInput
                                    id="wizard-code"
                                    v-model="form.code"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej. 202720"
                                    required
                                    @input="codeManuallyEdited = true"
                                />
                                <p class="mt-1 text-[11px] text-gray-500">
                                    Convención: terminación 20 (Ene–May), 40 (May–Ago), 60 (Ago–Dic).
                                </p>
                                <InputError class="mt-1" :message="form.errors.code" />
                            </div>

                            <div>
                                <InputLabel for="wizard-name" value="Nombre visible" />
                                <TextInput
                                    id="wizard-name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej. Ene – May 2027"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.name" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel for="wizard-year" value="Año" />
                                <input
                                    id="wizard-year"
                                    v-model.number="form.year"
                                    type="number"
                                    min="2020"
                                    max="2100"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.year" />
                            </div>

                            <div>
                                <InputLabel for="wizard-term" value="Cuatrimestre del año" />
                                <select
                                    id="wizard-term"
                                    v-model.number="form.term_of_year"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="1">1 · Ene – May (terminación 20)</option>
                                    <option :value="2">2 · May – Ago (terminación 40)</option>
                                    <option :value="3">3 · Ago – Dic (terminación 60)</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.term_of_year" />
                            </div>

                            <div>
                                <InputLabel for="wizard-weeks" value="Semanas lectivas" />
                                <input
                                    id="wizard-weeks"
                                    v-model.number="form.weeks"
                                    type="number"
                                    min="1"
                                    max="30"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <p class="mt-1 text-[11px] text-gray-500">
                                    14 escolarizado / 15 ejecutivo (sin contar vacaciones).
                                </p>
                                <InputError class="mt-1" :message="form.errors.weeks" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="wizard-starts" value="Fecha de inicio" />
                                <input
                                    id="wizard-starts"
                                    v-model="form.starts_on"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.starts_on" />
                            </div>

                            <div>
                                <InputLabel for="wizard-ends" value="Fecha de fin" />
                                <input
                                    id="wizard-ends"
                                    v-model="form.ends_on"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.ends_on" />
                            </div>
                        </div>
                    </div>

                    <!-- PASO 2: Revisar grupos que avanzan y egresan -->
                    <div v-show="step === 2" class="space-y-5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                2. Revisión de grupos y matrícula estimada
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Ajusta la estimación de alumnos de cada grupo antes de generar sus secciones.
                            </p>
                        </div>

                        <div v-if="loadingPreview" class="py-8 text-center text-sm text-gray-500">
                            Cargando grupos del periodo origen...
                        </div>

                        <div
                            v-else-if="mode === 'empty' || (preview.advancing.length === 0 && preview.graduating.length === 0)"
                            class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-600"
                        >
                            No hay grupos previos por avanzar. El periodo se creará vacío y podrás agregar grupos nuevos
                            en la pantalla de <strong>Grupos</strong>.
                        </div>

                        <template v-else>
                            <div v-if="preview.advancing.length > 0" class="overflow-x-auto rounded-md border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">
                                                Incluir
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">
                                                Grupo / Carrera
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">
                                                Avance
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">
                                                Secciones del plan
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500">
                                                Alumnos estimados
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr
                                            v-for="group in preview.advancing"
                                            :key="group.student_group_id"
                                            :class="{ 'bg-gray-50 opacity-50': isExcluded(group.student_group_id) }"
                                        >
                                            <td class="px-4 py-3">
                                                <input
                                                    type="checkbox"
                                                    :checked="!isExcluded(group.student_group_id)"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                    @change="toggleGroupInclusion(group.student_group_id)"
                                                />
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <div class="font-semibold text-gray-900">{{ group.label }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ group.program_code }} · {{ group.shift_name }}
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-indigo-700">
                                                {{ group.current_term }}.º → {{ group.next_term }}.º
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                {{ group.estimated_sections }} materias
                                            </td>
                                            <td class="px-4 py-3">
                                                <input
                                                    v-model.number="form.student_count_overrides[group.student_group_id]"
                                                    type="number"
                                                    min="0"
                                                    max="500"
                                                    :disabled="isExcluded(group.student_group_id)"
                                                    class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-if="preview.graduating.length > 0"
                                class="rounded-md border border-emerald-200 bg-emerald-50/70 p-4"
                            >
                                <h3 class="text-sm font-semibold text-emerald-900">
                                    Grupos que concluyen (pasan a Egresado)
                                </h3>
                                <p class="mt-0.5 text-xs text-emerald-800">
                                    Estaban en 9.º cuatrimestre; quedarán marcados como egresados y no recibirán secciones nuevas:
                                </p>
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    <li
                                        v-for="grad in preview.graduating"
                                        :key="grad.student_group_id"
                                        class="rounded bg-white px-2.5 py-1 text-xs font-medium text-emerald-900 ring-1 ring-inset ring-emerald-200"
                                    >
                                        {{ grad.label }} ({{ grad.program_code }} · {{ grad.student_count }} alumnos)
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </div>

                    <!-- PASO 3: Opciones -->
                    <div v-show="step === 3" class="space-y-5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                3. Opciones de generación automática
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Configura qué información tomará como base el sistema al crear las secciones del nuevo periodo.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                                <input
                                    v-model="form.suggest_teachers"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600"
                                />
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        Sugerir docente según el historial
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Asigna automáticamente al último docente activo que impartió cada materia presencial o Clase Conecta.
                                        Las materias en línea externas (@ / VV) quedan sin docente asignado.
                                    </div>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                                <input
                                    v-model="form.copy_meetings"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600"
                                />
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        Copiar sesiones del periodo anterior (cuando coincida grupo y materia)
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Si un grupo repite o continúa con la misma materia del ciclo previo, copia sus días, horas y aula.
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- PASO 4: Resumen -->
                    <div v-show="step === 4" class="space-y-5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                4. Resumen y confirmación
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Revisa los datos antes de generar el nuevo periodo en estado <strong>En planeación</strong>.
                            </p>
                        </div>

                        <dl class="grid gap-4 rounded-lg border border-gray-200 bg-gray-50/70 p-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-500">Periodo</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ form.code }} — {{ form.name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-500">Fechas y duración</dt>
                                <dd class="mt-1 text-sm text-gray-800">
                                    {{ formatReadableDate(form.starts_on) }} al {{ formatReadableDate(form.ends_on) }}
                                    ({{ form.weeks }} semanas)
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-500">Grupos que avanzan</dt>
                                <dd class="mt-1 text-sm font-semibold text-indigo-700">
                                    {{ includedAdvancingGroups.length }} grupo(s) · ~{{ totalEstimatedSections }} secciones
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-gray-500">Grupos que egresan</dt>
                                <dd class="mt-1 text-sm text-emerald-700">
                                    {{ preview.graduating.length }} grupo(s) en 9.º cuatrimestre
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Controles de navegación del asistente -->
                    <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-5">
                        <div>
                            <SecondaryButton v-if="step > 1" type="button" :disabled="form.processing" @click="prevStep">
                                ← Anterior
                            </SecondaryButton>
                        </div>

                        <div class="flex items-center gap-3">
                            <Link
                                :href="route('dashboard')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                            >
                                Cancelar
                            </Link>

                            <PrimaryButton v-if="step < 4" type="button" @click="nextStep">
                                Siguiente →
                            </PrimaryButton>

                            <PrimaryButton
                                v-else
                                type="submit"
                                :class="{ 'opacity-50': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Creando periodo...' : 'Crear periodo y generar secciones' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
