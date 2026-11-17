<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface DeliveryOption {
    value: string;
    label: string;
}

interface SettingItem {
    id: number;
    key: string;
    label: string;
    input_type: 'number' | 'boolean' | 'markers';
    value: number | boolean | Record<string, string>;
    description: string | null;
}

interface MarkerRow {
    marker: string;
    delivery: string;
}

const props = defineProps<{
    settings: SettingItem[];
    deliveryOptions: DeliveryOption[];
}>();

const editingSetting = ref<SettingItem | null>(null);
const markerRows = ref<MarkerRow[]>([]);

const form = useForm<{
    value: number | boolean | Record<string, string>;
    description: string;
}>({
    value: 0,
    description: '',
});

function deliveryLabel(value: string): string {
    return props.deliveryOptions.find((option) => option.value === value)?.label ?? value;
}

function formatDisplayValue(setting: SettingItem): string {
    if (setting.input_type === 'boolean') {
        return setting.value ? 'Sí (permitido)' : 'No (bloqueado)';
    }

    if (setting.input_type === 'markers' && typeof setting.value === 'object' && setting.value !== null) {
        return Object.entries(setting.value)
            .map(([marker, delivery]) => `${marker} → ${deliveryLabel(delivery)}`)
            .join(' · ');
    }

    return String(setting.value);
}

function openEditModal(setting: SettingItem): void {
    editingSetting.value = setting;
    form.clearErrors();
    form.description = setting.description ?? '';

    if (setting.input_type === 'markers' && typeof setting.value === 'object' && setting.value !== null) {
        markerRows.value = Object.entries(setting.value).map(([marker, delivery]) => ({
            marker,
            delivery,
        }));
        form.value = { ...setting.value };
    } else if (setting.input_type === 'boolean') {
        markerRows.value = [];
        form.value = Boolean(setting.value);
    } else {
        markerRows.value = [];
        form.value = Number(setting.value);
    }
}

function closeEditModal(): void {
    editingSetting.value = null;
    markerRows.value = [];
}

function addMarkerRow(): void {
    markerRows.value.push({
        marker: '',
        delivery: props.deliveryOptions[0]?.value ?? 'online_async',
    });
}

function removeMarkerRow(index: number): void {
    markerRows.value.splice(index, 1);
}

function submitEdit(): void {
    if (!editingSetting.value) {
        return;
    }

    if (editingSetting.value.input_type === 'markers') {
        const mapped: Record<string, string> = {};
        for (const row of markerRows.value) {
            const key = row.marker.trim();
            if (key !== '') {
                mapped[key] = row.delivery;
            }
        }
        form.value = mapped;
    } else if (editingSetting.value.input_type === 'number') {
        form.value = Number(form.value);
    }

    form.put(route('catalogs.settings.update', editingSetting.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}
</script>

<template>
    <Head title="Parámetros del sistema" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold leading-tight text-gray-900">
                    Parámetros del sistema
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    Reglas configurables del motor de planeación, tamaños objetivo y mapeos editables.
                </p>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="settings.length === 0"
                    class="rounded-lg border border-gray-200 bg-white p-10 text-center shadow-sm"
                >
                    <h2 class="text-base font-semibold text-gray-900">
                        No hay parámetros registrados
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Ejecuta los seeders iniciales para cargar la configuración por defecto del sistema.
                    </p>
                </div>

                <div v-else class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div
                        v-for="setting in settings"
                        :key="setting.id"
                        class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-sm font-semibold text-gray-900">
                                    {{ setting.label }}
                                </h2>
                                <code class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ setting.key }}
                                </code>
                            </div>
                            <p v-if="setting.description" class="text-sm text-gray-600">
                                {{ setting.description }}
                            </p>
                            <p class="text-sm font-medium text-indigo-700">
                                Valor actual: {{ formatDisplayValue(setting) }}
                            </p>
                        </div>

                        <div class="shrink-0">
                            <SecondaryButton @click="openEditModal(setting)">
                                Editar
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal edición de parámetro -->
        <Modal :show="editingSetting !== null" max-width="lg" @close="closeEditModal">
            <form v-if="editingSetting" class="p-6" @submit.prevent="submitEdit">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingSetting.label }}
                </h2>
                <p class="mt-1 text-xs text-gray-500">
                    Clave técnica: <code>{{ editingSetting.key }}</code>
                </p>

                <div class="mt-5 space-y-4">
                    <!-- Tipo número -->
                    <div v-if="editingSetting.input_type === 'number'">
                        <InputLabel for="setting-number" value="Valor numérico" />
                        <input
                            id="setting-number"
                            v-model="form.value"
                            type="number"
                            min="1"
                            max="200"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.value" />
                    </div>

                    <!-- Tipo booleano -->
                    <div v-else-if="editingSetting.input_type === 'boolean'">
                        <label class="flex items-center gap-3 rounded-md border border-gray-200 p-3">
                            <input
                                v-model="form.value as boolean"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm font-medium text-gray-800">
                                Habilitar esta opción
                            </span>
                        </label>
                        <InputError class="mt-1" :message="form.errors.value" />
                    </div>

                    <!-- Tipo marcadores Excel -->
                    <div v-else-if="editingSetting.input_type === 'markers'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <InputLabel value="Mapeo de marcadores" />
                            <button
                                type="button"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                @click="addMarkerRow"
                            >
                                + Agregar marcador
                            </button>
                        </div>

                        <div
                            v-for="(row, index) in markerRows"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <TextInput
                                v-model="row.marker"
                                type="text"
                                class="w-28"
                                placeholder="Ej. @"
                                required
                            />
                            <span class="text-sm text-gray-400">→</span>
                            <select
                                v-model="row.delivery"
                                class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="option in deliveryOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <button
                                v-if="markerRows.length > 1"
                                type="button"
                                class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                @click="removeMarkerRow(index)"
                            >
                                Quitar
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.value" />
                    </div>

                    <div>
                        <InputLabel for="setting-description" value="Descripción" />
                        <TextInput
                            id="setting-description"
                            v-model="form.description"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-1" :message="form.errors.description" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" :disabled="form.processing" @click="closeEditModal">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                        Guardar parámetro
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
