<script setup lang="ts">
import axios from "axios";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import { MONTHS } from "@/Constants/Pages/Period/categories";
import { fieldErrors } from "@/Composables/shared/http";

defineProps<{ cancellable?: boolean }>();
defineEmits<{ cancel: [] }>();
const now = new Date();
const year = ref(now.getFullYear());
const month = ref(now.getMonth() + 1);
const half = ref<1 | 2>(now.getDate() <= 15 ? 1 : 2);
const errors = ref<Record<string, string>>({});
const busy = ref(false);

async function create() {
	busy.value = true;
	errors.value = {};
	try {
		const res = await axios.post<{ period: { id: number } }>("/periods/store", {
			year: year.value,
			month: month.value,
			half: half.value,
		});
		router.visit(`/periods/${res.data.period.id}`);
	} catch (e) {
		errors.value = fieldErrors(e);
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<form class="form-panel" @submit.prevent="create">
		<label class="field">Año
			<input v-model.number="year" type="number" inputmode="numeric" class="input" />
			<span v-if="errors.year" role="alert" class="err">{{ errors.year }}</span>
		</label>
		<label class="field">Mes
			<select v-model.number="month" class="input">
				<option v-for="(m, i) in MONTHS" :key="m" :value="i + 1">{{ m }}</option>
			</select>
			<span v-if="errors.month" role="alert" class="err">{{ errors.month }}</span>
		</label>
		<label class="field">Quincena
			<select v-model.number="half" class="input">
				<option :value="1">I</option>
				<option :value="2">II</option>
			</select>
			<span v-if="errors.half" role="alert" class="err">{{ errors.half }}</span>
		</label>
		<p v-if="errors.form" role="alert" class="alert">{{ errors.form }}</p>
		<div class="create-period__actions">
			<button :disabled="busy" class="btn btn-primary">Crear quincena</button>
			<button v-if="cancellable" type="button" :disabled="busy" class="btn btn-ghost" @click="$emit('cancel')">Cancelar</button>
		</div>
	</form>
</template>
