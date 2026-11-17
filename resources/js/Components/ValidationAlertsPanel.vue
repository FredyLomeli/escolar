<script setup lang="ts">
import { computed } from 'vue';
import type { FindingSeverity, ValidationFinding } from '@/Types/validation';

const props = withDefaults(
    defineProps<{
        findings: ValidationFinding[];
        emptyMessage?: string;
        maxItems?: number | null;
    }>(),
    {
        emptyMessage: 'Sin alertas registradas.',
        maxItems: null,
    },
);

const visibleFindings = computed(() => {
    if (props.maxItems !== null && props.maxItems > 0) {
        return props.findings.slice(0, props.maxItems);
    }

    return props.findings;
});

function containerClass(severity: FindingSeverity): string {
    switch (severity) {
        case 'error':
            return 'border-red-200 bg-red-50/70 text-red-900';
        case 'warning':
            return 'border-amber-200 bg-amber-50/70 text-amber-900';
        default:
            return 'border-sky-200 bg-sky-50/70 text-sky-900';
    }
}

function badgeClass(severity: FindingSeverity): string {
    switch (severity) {
        case 'error':
            return 'bg-red-100 text-red-800 ring-red-600/20';
        case 'warning':
            return 'bg-amber-100 text-amber-800 ring-amber-600/20';
        default:
            return 'bg-sky-100 text-sky-800 ring-sky-600/20';
    }
}

function severityLabel(severity: FindingSeverity): string {
    switch (severity) {
        case 'error':
            return 'Error';
        case 'warning':
            return 'Advertencia';
        default:
            return 'Sugerencia';
    }
}
</script>

<template>
    <div v-if="findings.length === 0" class="rounded-lg border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-sm text-emerald-800">
        {{ emptyMessage }}
    </div>

    <div v-else class="space-y-2">
        <div
            v-for="(finding, index) in visibleFindings"
            :key="`${finding.code}-${index}`"
            :class="['flex flex-col gap-2 rounded-lg border px-3.5 py-2.5 text-sm sm:flex-row sm:items-center sm:justify-between', containerClass(finding.severity)]"
        >
            <div class="space-y-0.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        :class="[
                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
                            badgeClass(finding.severity),
                        ]"
                    >
                        {{ severityLabel(finding.severity) }}
                    </span>
                    <span class="font-mono text-[11px] font-semibold opacity-75">
                        {{ finding.code }}
                    </span>
                    <span v-if="finding.entity" class="text-xs font-medium opacity-85">
                        · {{ finding.entity.label }}
                    </span>
                </div>
                <p class="text-sm leading-snug">
                    {{ finding.message }}
                </p>
            </div>

            <div v-if="$slots.action" class="shrink-0">
                <slot name="action" :finding="finding" />
            </div>
        </div>
    </div>
</template>
