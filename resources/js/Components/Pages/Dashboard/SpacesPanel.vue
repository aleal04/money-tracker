<script setup lang="ts">
import { formatCRC } from "@/Composables/shared/format";
import type { SpaceRow } from "@/Composables/Dashboard/spacesOverview";

defineProps<{ rows: SpaceRow[]; selectedId: number | null }>();
defineEmits<{ select: [number] }>();
</script>

<template>
	<section class="card spaces-panel">
		<h2 class="spaces-panel__title">Tus espacios</h2>
		<ul class="spaces-panel__list">
			<li v-for="r in rows" :key="r.id">
				<button type="button" class="spaces-panel__item" :class="{ 'spaces-panel__item--on': r.id === selectedId }" :aria-pressed="r.id === selectedId" @click="$emit('select', r.id)">
					<span class="spaces-panel__main">
						<span class="spaces-panel__name">{{ r.name }}</span>
						<span class="spaces-panel__freq">{{ r.frequency }}</span>
					</span>
					<span v-if="r.restante === null" class="spaces-panel__none">sin periodos</span>
					<span v-else class="spaces-panel__amount" :class="{ 'spaces-panel__amount--neg': r.restante < 0 }">{{ formatCRC(r.restante) }}</span>
				</button>
			</li>
		</ul>
	</section>
</template>
