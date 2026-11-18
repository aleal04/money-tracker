<script setup lang="ts">
import { computed } from "vue";
import { es } from "date-fns/locale";
import { VueDatePicker } from "@vuepic/vue-datepicker";
import { useTheme } from "@/Composables/shared/useTheme";

defineOptions({ name: "DatePicker" });

/**
 * Date field with the same v-model shape as the API: a "yyyy-MM-dd" string (null when empty).
 *
 * @property {string} inputId - Unique id of the text input; use it to associate a label.
 * @property {string | null} [modelValue] - The selected date as "yyyy-MM-dd".
 * @property {string} [placeholder] - Text shown while empty.
 * @property {boolean} [clearable] - Shows the clear button.
 * @property {boolean} [disabled] - Disables the field.
 */
const props = defineProps<{
	inputId?: string;
	modelValue?: string | null;
	placeholder?: string;
	clearable?: boolean;
	disabled?: boolean;
}>();
const emit = defineEmits<(e: "update:modelValue", value: string | null) => void>();

const { theme } = useTheme();
const selectedDate = computed({
	get: () => props.modelValue ?? null,
	set: (value: string | null) => emit("update:modelValue", value),
});
</script>

<template>
	<VueDatePicker
		v-model="selectedDate"
		class="date-picker"
		model-type="yyyy-MM-dd"
		:locale="es"
		:formats="{ input: 'dd/MM/yyyy' }"
		:week-start="1"
		:time-config="{ enableTimePicker: false }"
		:dark="theme === 'dark'"
		:placeholder="placeholder ?? 'dd/mm/aaaa'"
		:clearable="clearable ?? false"
		:disabled="disabled"
		:menu-id="inputId ? `${inputId}-menu` : undefined"
		:input-attrs="{ id: inputId }"
		auto-apply
	/>
</template>
