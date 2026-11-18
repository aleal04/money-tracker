<script setup lang="ts">
import { useCreatePeriod } from "@/Composables/Period/useCreatePeriod";

defineProps<{ cancellable?: boolean }>();
defineEmits<{ cancel: [] }>();
const { startDate, endDate, errors, busy, create } = useCreatePeriod();
</script>

<template>
	<form class="form-panel" @submit.prevent="create">
		<label class="field">Inicio
			<input v-model="startDate" type="date" class="input" />
			<span v-if="errors.startDate" role="alert" class="err">{{ errors.startDate }}</span>
		</label>
		<label class="field">Fin
			<input v-model="endDate" type="date" class="input" />
			<span v-if="errors.endDate" role="alert" class="err">{{ errors.endDate }}</span>
		</label>
		<p v-if="errors.form" role="alert" class="alert form-wide">{{ errors.form }}</p>
		<div class="create-period__actions">
			<button :disabled="busy" class="btn btn-primary">Crear periodo</button>
			<button v-if="cancellable" type="button" :disabled="busy" class="btn btn-ghost" @click="$emit('cancel')">Cancelar</button>
		</div>
	</form>
</template>
