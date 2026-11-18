<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import Icon from "@/Components/UI/Icon.vue";

defineProps<{ templateCount: number; installmentCount: number }>();
defineEmits<{ create: [] }>();
</script>

<template>
	<section class="card welcome-steps">
		<div>
			<h2 class="welcome-steps__title">Bienvenido a Finanzas</h2>
			<p class="welcome-steps__sub">Tres pasos para dejar de usar el Excel:</p>
		</div>
		<ol class="welcome-steps__list">
			<li class="welcome-steps__item">
				<span class="welcome-steps__num">1</span>
				<div class="welcome-steps__text">
					<p class="welcome-steps__label">Creá tus plantillas</p>
					<p class="welcome-steps__sub">Pagos que se repiten: Kolbi, Claro, CONAPE, préstamos… <span v-if="templateCount">({{ templateCount }} creadas)</span></p>
				</div>
				<Link href="/templates" class="btn btn-ghost">Ir a plantillas<Icon name="right" /></Link>
			</li>
			<li class="welcome-steps__item">
				<span class="welcome-steps__num">2</span>
				<div class="welcome-steps__text">
					<p class="welcome-steps__label">Agregá tus cuotas</p>
					<p class="welcome-steps__sub">Tus tarjetas y las compras en Tasa 0 o Minicuotas. <span v-if="installmentCount">({{ installmentCount }} registradas)</span></p>
				</div>
				<Link href="/installments" class="btn btn-ghost">Ir a cuotas<Icon name="right" /></Link>
			</li>
			<li class="welcome-steps__item">
				<span class="welcome-steps__num">3</span>
				<div class="welcome-steps__text">
					<p class="welcome-steps__label">Creá tu primera quincena</p>
					<p class="welcome-steps__sub">Se llena sola con tus plantillas y la cuota de cada tarjeta.</p>
				</div>
				<button type="button" class="btn btn-primary" @click="$emit('create')"><Icon name="plus" />Crear quincena</button>
			</li>
		</ol>
	</section>
</template>
