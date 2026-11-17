<script setup lang="ts">
withDefaults(
    defineProps<{
        active: boolean;
        activeLabel?: string;
        inactiveLabel?: string;
    }>(),
    {
        activeLabel: 'Activo',
        inactiveLabel: 'Inactivo',
    },
);
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
            active
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                : 'bg-gray-100 text-gray-600 ring-gray-500/20',
        ]"
    >
        {{ active ? activeLabel : inactiveLabel }}
    </span>
</template>
