<script setup lang="ts">
import { toRef } from "vue";
import { Head } from "@inertiajs/vue3";
import CategoryBlock from "@/Components/Pages/Period/CategoryBlock.vue";
import ClosePeriodDialog from "@/Components/Pages/Period/ClosePeriodDialog.vue";
import PeriodDatesForm from "@/Components/Pages/Period/PeriodDatesForm.vue";
import SummaryCards from "@/Components/Pages/Period/SummaryCards.vue";
import Icon from "@/Components/UI/Icon.vue";
import { CATEGORIES } from "@/Constants/Pages/Period/categories";
import { pendingIn, usePeriodShow } from "@/Composables/Period/usePeriodShow";
import { dateRange } from "@/Composables/shared/format";

const props = defineProps<{ periodId: number }>();
const { data, error, busy, closing, tab, setTab, linesOf, load, toggle, reopen, go, createNext, closed, editingDates, startDate, endDate, dateErrors, editDates, saveDates } = usePeriodShow(toRef(props, "periodId"));
</script>

<template>
	<div class="period-show">
		<Head :title="data?.period.label ?? 'Periodo'" />
		<p v-if="error" role="alert" class="alert">{{ error }}</p>
		<template v-if="data">
			<header class="card period-show__header">
				<div class="period-show__heading">
					<h1 class="period-show__title">{{ data.period.label }}</h1>
					<p class="period-show__sub">
						{{ dateRange(data.period.startDate, data.period.endDate) }} · {{ data.lines.length }} líneas · {{ data.period.closed ? "Cerrado" : "Abierto" }}
						<button v-if="!editingDates" type="button" class="icon-btn period-show__edit" aria-label="Editar fechas" @click="editDates"><Icon name="edit" /></button>
					</p>
				</div>
				<div class="period-show__actions">
					<div class="period-show__nav">
						<button type="button" :disabled="data.prevId === null" aria-label="Periodo anterior" class="icon-btn" @click="go(data.prevId)"><Icon name="left" /></button>
						<button type="button" :disabled="data.nextId === null" aria-label="Periodo siguiente" class="icon-btn" @click="go(data.nextId)"><Icon name="right" /></button>
					</div>
					<button v-if="data.period.closed" type="button" :disabled="busy" class="btn btn-ghost" @click="reopen"><Icon name="unlock" />Reabrir</button>
					<button v-else type="button" :disabled="busy" class="btn btn-primary" @click="closing = true"><Icon name="lock" />Cerrar periodo</button>
					<button type="button" :disabled="busy" class="btn btn-accent" @click="createNext"><Icon name="plus" />Nuevo periodo</button>
				</div>
				<PeriodDatesForm v-if="editingDates" v-model:start="startDate" v-model:end="endDate" :errors="dateErrors" :busy="busy" @save="saveDates" @cancel="editingDates = false" />
			</header>
			<SummaryCards :summary="data.summary" />
			<nav role="tablist" aria-label="Categorías" class="card period-show__tabs">
				<button
					v-for="c in CATEGORIES"
					:key="c.key"
					type="button"
					role="tab"
					:aria-selected="tab === c.key"
					class="period-show__tab"
					:class="{ 'period-show__tab--active': tab === c.key }"
					@click="setTab(c.key)"
				>
					{{ c.label }}
					<span class="pill period-show__count">{{ linesOf(c.key).length }}</span>
					<span v-if="pendingIn(data.lines, c.key)" class="period-show__dot" :title="`${pendingIn(data.lines, c.key)} sin pagar`" />
				</button>
			</nav>
			<template v-for="c in CATEGORIES" :key="c.key">
				<CategoryBlock
					v-if="tab === c.key"
					role="tabpanel"
					:period-id="periodId"
					:category="c.key"
					:label="c.label"
					:lines="linesOf(c.key)"
					:totals="data.summary.byCategory[c.key]"
					:closed="data.period.closed"
					:busy="busy"
					@toggle="toggle"
					@changed="load"
				/>
			</template>
			<ClosePeriodDialog v-if="closing" :period-id="periodId" @closed="closed" @cancel="closing = false" />
		</template>
	</div>
</template>
