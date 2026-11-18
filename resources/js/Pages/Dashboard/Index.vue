<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import CardsPanel from "@/Components/Pages/Dashboard/CardsPanel.vue";
import PendingPanel from "@/Components/Pages/Dashboard/PendingPanel.vue";
import WelcomeSteps from "@/Components/Pages/Dashboard/WelcomeSteps.vue";
import CreatePeriodForm from "@/Components/Pages/Period/CreatePeriodForm.vue";
import SpacesPanel from "@/Components/Pages/Dashboard/SpacesPanel.vue";
import SummaryCards from "@/Components/Pages/Period/SummaryCards.vue";
import Icon from "@/Components/UI/Icon.vue";
import { greeting, useDashboard } from "@/Composables/Dashboard/useDashboard";

const { data, error, busy, creating, pending, rows, current, setSpace, installmentCount, pay, createNext } = useDashboard();
</script>

<template>
	<div class="dashboard-index">
		<Head title="Inicio" />
		<p v-if="error" role="alert" class="alert">{{ error }}</p>
		<template v-if="data">
			<header class="card dashboard-index__header">
				<div class="dashboard-index__heading">
					<h1 class="dashboard-index__title">Inicio</h1>
					<p class="dashboard-index__sub">
						{{ greeting(new Date().getHours()) }}<template v-if="data.current"> · {{ data.current.period.label }} ({{ data.current.period.closed ? "Cerrado" : "Abierto" }})</template>
					</p>
				</div>
				<button type="button" :disabled="busy" class="btn btn-accent" @click="createNext"><Icon name="plus" />Nuevo periodo</button>
			</header>
			<section v-if="creating" class="card dashboard-index__create">
				<h2 class="dashboard-index__create-title">Nuevo periodo</h2>
				<CreatePeriodForm cancellable @cancel="creating = false" />
			</section>
			<SpacesPanel v-if="rows.length" :rows="rows" :selected-id="current?.id ?? null" @select="setSpace" />
			<WelcomeSteps v-if="!data.current && !creating" :template-count="data.templateCount" :installment-count="installmentCount" @create="creating = true" />
			<template v-if="data.current">
				<SummaryCards :summary="data.current.summary" />
				<div class="dashboard-index__grid">
					<PendingPanel :period-id="data.current.period.id" :pending="pending" :busy="busy" @pay="pay" />
					<CardsPanel :cards="data.cards" />
				</div>
			</template>
		</template>
	</div>
</template>
