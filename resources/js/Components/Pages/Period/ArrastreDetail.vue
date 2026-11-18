<script setup lang="ts">
import axios from "axios";
import { onMounted, ref } from "vue";
import { formatCRC } from "@/Composables/shared/format";
import { errorMessage } from "@/Composables/shared/http";
import type { Line } from "@/Types/Pages/Period/Period";

const props = defineProps<{ lineId: number }>();
const lines = ref<Line[]>([]);
const error = ref("");
const opened = ref<Record<number, boolean>>({});

onMounted(async () => {
	try {
		lines.value = (await axios.get<Line[]>(`/lines/${props.lineId}/arrastre-detail`)).data;
	} catch (e) {
		error.value = errorMessage(e);
	}
});
</script>

<template>
	<ul class="arrastre-detail">
		<li v-if="error" class="err">{{ error }}</li>
		<li v-for="l in lines" :key="l.id">
			<details v-if="l.source === 'carried'" @toggle="opened[l.id] = ($event.target as HTMLDetailsElement).open">
				<summary class="arrastre-detail__summary">{{ l.name }} · <span class="num">{{ formatCRC(l.actual ?? l.estimated) }}</span></summary>
				<ArrastreDetail v-if="opened[l.id]" :line-id="l.id" />
			</details>
			<div v-else class="arrastre-detail__row"><span>{{ l.name }}</span><span class="num">{{ formatCRC(l.actual ?? l.estimated) }}</span></div>
		</li>
	</ul>
</template>
