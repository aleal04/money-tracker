<script setup lang="ts">
import { computed } from "vue";
import SelectInput from "@/Components/UI/Inputs/SelectInput.vue";
import { useLineForm } from "@/Composables/Period/useLineForm";
import { canSendToSpace, targetOptions } from "@/Composables/Period/lineKind";
import { useSpace } from "@/Composables/shared/useSpace";
import type { Category, Line } from "@/Types/Pages/Period/Period";

const props = defineProps<{ periodId: number; category: Category; line?: Line }>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const { spaces } = useSpace();
const { current, target, name, estimated, actual, paid, confirmingDelete, errors, busy, submit, remove } = useLineForm(props, () => emit("saved"));
const targets = computed(() => [{ label: "Ninguno", value: "" }, ...targetOptions(spaces.value, current.value?.id).map((s) => ({ label: s.name, value: s.id }))]);
</script>

<template>
	<form class="form-panel line-form" @submit.prevent="submit">
		<label class="field">Nombre
			<input v-model="name" class="input" />
			<span v-if="errors.name" role="alert" class="err">{{ errors.name }}</span>
		</label>
		<label class="field">Aproximado
			<input v-model="estimated" inputmode="decimal" class="input" />
			<span v-if="errors.estimated" role="alert" class="err">{{ errors.estimated }}</span>
		</label>
		<label class="field">Real (opcional)
			<input v-model="actual" inputmode="decimal" class="input" />
			<span v-if="errors.actual" role="alert" class="err">{{ errors.actual }}</span>
		</label>
		<label v-if="canSendToSpace(category, line) && targetOptions(spaces, current?.id).length" class="field">Enviar a espacio
			<SelectInput v-model="target" :options="targets" />
			<span v-if="errors.targetSpaceId" role="alert" class="err">{{ errors.targetSpaceId }}</span>
		</label>
		<label v-if="!line" class="line-form__paid">
			<input v-model="paid" type="checkbox" /> Pagado
		</label>
		<p v-if="errors.form || errors.category" role="alert" class="err line-form__wide">{{ errors.form ?? errors.category }}</p>
		<div class="line-form__actions line-form__wide">
			<button :disabled="busy" class="btn btn-primary">Guardar</button>
			<button type="button" :disabled="busy" class="btn btn-ghost" @click="emit('cancel')">Cancelar</button>
		</div>
		<!-- An arrastre line is removed by reopening the previous period, not by deleting it. -->
		<template v-if="line && line.source !== 'carried'">
			<button v-if="!confirmingDelete" type="button" :disabled="busy" class="btn btn-danger line-form__wide" @click="confirmingDelete = true">Eliminar</button>
			<div v-else class="line-form__confirm line-form__wide">
				<span>¿Eliminar esta línea?</span>
				<button type="button" :disabled="busy" class="btn btn-danger-solid" @click="remove">Sí, eliminar</button>
				<button type="button" :disabled="busy" class="btn btn-ghost" @click="confirmingDelete = false">No</button>
			</div>
		</template>
	</form>
</template>
