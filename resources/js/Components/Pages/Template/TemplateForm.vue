<script setup lang="ts">
import { CATEGORIES } from "@/Constants/Pages/Period/categories";
import { useTemplateForm } from "@/Composables/Template/useTemplateForm";
import type { Template } from "@/Types/Pages/Template/Template";

const props = defineProps<{ template?: Template }>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const { name, category, estimated, freq, confirmingDelete, errors, busy, submit, remove } = useTemplateForm(props, () => emit("saved"));
</script>

<template>
	<form class="form-panel" @submit.prevent="submit">
		<label class="field">Nombre
			<input v-model="name" class="input" />
			<span v-if="errors.name" role="alert" class="err">{{ errors.name }}</span>
		</label>
		<label class="field">Categoría
			<select v-model="category" class="input">
				<option v-for="c in CATEGORIES" :key="c.key" :value="c.key">{{ c.label }}</option>
			</select>
			<span v-if="errors.category" role="alert" class="err">{{ errors.category }}</span>
		</label>
		<label class="field">Monto
			<input v-model="estimated" inputmode="decimal" class="input" />
			<span v-if="errors.estimated" role="alert" class="err">{{ errors.estimated }}</span>
		</label>
		<label class="field">Frecuencia
			<select v-model="freq" class="input">
				<option value="quincenal">Cada quincena</option>
				<option value="mensual_dividido">Mensual ÷ 2</option>
				<option value="mensual_en_quincena_1">Mensual en I quincena</option>
				<option value="mensual_en_quincena_2">Mensual en II quincena</option>
			</select>
			<span v-if="errors.frequency || errors.half" role="alert" class="err">{{ errors.frequency ?? errors.half }}</span>
		</label>
		<p v-if="errors.form" role="alert" class="err form-wide">{{ errors.form }}</p>
		<div class="form-actions form-wide">
			<button :disabled="busy" class="btn btn-primary">Guardar</button>
			<button type="button" :disabled="busy" class="btn btn-ghost" @click="emit('cancel')">Cancelar</button>
		</div>
		<template v-if="template">
			<button v-if="!confirmingDelete" type="button" :disabled="busy" class="btn btn-danger form-wide" @click="confirmingDelete = true">Eliminar</button>
			<div v-else class="form-confirm form-wide">
				<span>¿Eliminar esta plantilla?</span>
				<button type="button" :disabled="busy" class="btn btn-danger-solid" @click="remove">Sí, eliminar</button>
				<button type="button" :disabled="busy" class="btn btn-ghost" @click="confirmingDelete = false">No</button>
			</div>
		</template>
	</form>
</template>
