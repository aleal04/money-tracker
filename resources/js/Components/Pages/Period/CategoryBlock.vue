<script setup lang="ts">
import { ref } from "vue";
import Icon from "@/Components/UI/Icon.vue";
import { lineKind, transferBadge } from "@/Composables/Period/lineKind";
import { useSpace } from "@/Composables/shared/useSpace";
import { canToggle } from "@/Composables/Period/usePeriodShow";
import { diffLabel, formatCRC } from "@/Composables/shared/format";
import type { Category, CategoryTotals, Line } from "@/Types/Pages/Period/Period";
import ArrastreDetail from "./ArrastreDetail.vue";
import LineForm from "./LineForm.vue";

defineProps<{
	periodId: number;
	category: Category;
	label: string;
	lines: Line[];
	totals: CategoryTotals;
	closed: boolean;
	busy: boolean;
}>();
const emit = defineEmits<{ toggle: [Line]; changed: [] }>();
const { spaces } = useSpace();
const readOnlyHint = "Se edita desde el espacio de origen";
const adding = ref(false);
const editingId = ref<number | null>(null);
const expanded = ref<Record<number, boolean>>({});
</script>

<template>
	<section class="card category-block">
		<h2 class="category-block__title">{{ label }}</h2>
		<div class="category-block__row category-block__head th">
			<span>Pagado</span><span>Nombre</span><span class="num">Aproximado</span><span class="num">Real</span><span class="num">Diferencia</span><span class="num">Acciones</span>
		</div>
		<ul class="category-block__list">
			<li v-for="l in lines" :key="l.id" class="category-block__item">
				<LineForm v-if="editingId === l.id" :period-id="periodId" :category="category" :line="l" @saved="editingId = null; emit('changed')" @cancel="editingId = null" />
				<template v-else>
					<div class="category-block__row" :class="{ 'category-block__row--carried': l.carried, 'category-block__row--pending': !l.carried && !l.paid }">
						<button
							type="button"
							class="category-block__check"
							:disabled="!canToggle(l, closed, busy)"
							:aria-pressed="l.paid"
							:aria-label="lineKind(l) === 'destination' ? readOnlyHint : `Marcar como pagado: ${l.name}`"
							:title="lineKind(l) === 'destination' ? readOnlyHint : undefined"
							@click="emit('toggle', l)"
						>
							<span class="category-block__box" :class="{ 'category-block__box--on': l.paid }"><Icon v-if="l.paid" name="check" /></span>
						</button>
						<div class="category-block__main">
							<div class="category-block__name">
								<span class="category-block__label">{{ l.name }}</span>
								<span v-if="l.carried" class="category-block__note">↪ arrastrado</span>
								<span v-if="transferBadge(l, spaces)" class="pill category-block__transfer" :title="lineKind(l) === 'destination' ? readOnlyHint : undefined">{{ transferBadge(l, spaces) }}</span>
								<span v-if="l.source === 'installments'" class="pill category-block__auto">auto</span>
							</div>
							<div class="category-block__amount-narrow">
								<span class="category-block__amount">{{ formatCRC(l.actual ?? l.estimated) }}</span>
								<span v-if="diffLabel(l.estimated, l.actual)" class="category-block__note">aprox. {{ formatCRC(l.estimated) }} · {{ diffLabel(l.estimated, l.actual) }}</span>
							</div>
						</div>
						<span class="num category-block__col">{{ formatCRC(l.estimated) }}</span>
						<span class="num category-block__col category-block__amount">{{ l.actual === null ? "—" : formatCRC(l.actual) }}</span>
						<span class="num category-block__col category-block__note">{{ diffLabel(l.estimated, l.actual) ?? "" }}</span>
						<div class="category-block__actions">
							<button v-if="!l.carried && lineKind(l) !== 'destination'" type="button" class="icon-btn" :aria-label="'Editar ' + l.name" @click="editingId = l.id"><Icon name="edit" /></button>
						</div>
					</div>
					<details v-if="lineKind(l) === 'arrastre'" class="category-block__detail" @toggle="expanded[l.id] = ($event.target as HTMLDetailsElement).open">
						<summary>Ver detalle</summary>
						<ArrastreDetail v-if="expanded[l.id]" :line-id="l.id" />
					</details>
				</template>
			</li>
		</ul>
		<LineForm v-if="adding" :period-id="periodId" :category="category" @saved="adding = false; emit('changed')" @cancel="adding = false" />
		<footer class="category-block__foot">
			<span class="category-block__totals">Total · Aprox. {{ formatCRC(totals.estimated) }} · Real <strong>{{ formatCRC(totals.actual) }}</strong></span>
			<button v-if="!adding" type="button" class="btn category-block__add" @click="adding = true"><Icon name="plus" />Agregar</button>
		</footer>
	</section>
</template>
