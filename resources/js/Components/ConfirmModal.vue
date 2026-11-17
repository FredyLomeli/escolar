<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        message: string;
        confirmLabel?: string;
        cancelLabel?: string;
        danger?: boolean;
        processing?: boolean;
    }>(),
    {
        confirmLabel: 'Confirmar',
        cancelLabel: 'Cancelar',
        danger: true,
        processing: false,
    },
);

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">
                {{ title }}
            </h2>

            <p class="mt-2 text-sm text-gray-600">
                {{ message }}
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton :disabled="processing" @click="emit('close')">
                    {{ cancelLabel }}
                </SecondaryButton>

                <DangerButton
                    v-if="danger"
                    :class="{ 'opacity-50': processing }"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </DangerButton>
                <PrimaryButton
                    v-else
                    :class="{ 'opacity-50': processing }"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
