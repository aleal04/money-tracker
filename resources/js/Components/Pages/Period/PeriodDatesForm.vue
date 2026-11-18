<script setup lang="ts">
import { onMounted, useTemplateRef } from "vue";
import DatePicker from "@/Components/UI/Inputs/DatePicker.vue";

defineProps<{ errors: Record<string, string>; busy: boolean }>();
const start = defineModel<string>("start", { required: true });
const end = defineModel<string>("end", { required: true });
defineEmits<{ save: []; cancel: [] }>();
const form = useTemplateRef<HTMLFormElement>("form");
onMounted(() => form.value?.querySelector("input")?.focus());
</script>

<template>
	<form ref="form" class="form-panel period-dates-form" @submit.prevent="$emit('save')">
		<label class="field">Inicio
			<DatePicker v-model="start" input-id="period-dates-start" />
			<span v-if="errors.startDate" role="alert" class="err">{{ errors.startDate }}</span>
		</label>
		<label class="field">Fin
			<DatePicker v-model="end" input-id="period-dates-end" />
			<span v-if="errors.endDate" role="alert" class="err">{{ errors.endDate }}</span>
		</label>
		<p v-if="errors.form" role="alert" class="err form-wide">{{ errors.form }}</p>
		<div class="form-actions form-wide">
			<button :disabled="busy" class="btn btn-primary">Guardar fechas</button>
			<button type="button" :disabled="busy" class="btn btn-ghost" @click="$emit('cancel')">Cancelar</button>
		</div>
	</form>
</template>
