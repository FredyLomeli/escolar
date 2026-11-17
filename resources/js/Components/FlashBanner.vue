<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const dismissed = ref(false);

const successMessage = computed(() => page.props.flash?.success ?? null);
const errorMessage = computed(() => page.props.flash?.error ?? null);

watch([successMessage, errorMessage], () => {
    dismissed.value = false;
});
</script>

<template>
    <div v-if="!dismissed && (successMessage || errorMessage)" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
        <div
            v-if="successMessage"
            role="status"
            class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm"
        >
            <span>{{ successMessage }}</span>
            <button
                type="button"
                aria-label="Cerrar notificación"
                class="ms-4 text-emerald-600 hover:text-emerald-800"
                @click="dismissed = true"
            >
                ✕
            </button>
        </div>

        <div
            v-else-if="errorMessage"
            role="alert"
            class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm"
        >
            <span>{{ errorMessage }}</span>
            <button
                type="button"
                aria-label="Cerrar alerta"
                class="ms-4 text-red-600 hover:text-red-800"
                @click="dismissed = true"
            >
                ✕
            </button>
        </div>
    </div>
</template>
