import './bootstrap';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale);

// Livewire 3 embarque et démarre déjà sa propre instance Alpine.js (exposée en
// window.Alpine via @livewireScripts) sur toute page utilisant un composant
// Livewire full-page. On ne réimporte donc pas alpinejs ni n'appelle Alpine.start()
// ici pour éviter une double initialisation — les directives/plugins custom
// s'enregistrent via l'événement 'alpine:init' ci-dessous.
document.addEventListener('alpine:init', () => {
    // Encapsule Chart.js pour le dashboard Analytics du portail Super Admin
    // (§15.9) : le composant Livewire ne fait que passer les données ("croissance"
    // écoles/mois), tout le rendu du graphique reste côté Alpine.
    Alpine.data('analyticsChart', (croissance) => ({
        chart: null,

        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: this.toChartData(croissance),
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        },

        update(croissance) {
            const data = this.toChartData(croissance);
            this.chart.data.labels = data.labels;
            this.chart.data.datasets = data.datasets;
            this.chart.update();
        },

        toChartData(croissance) {
            return {
                labels: croissance.map((point) => point.mois),
                datasets: [{
                    label: 'Nouvelles écoles',
                    data: croissance.map((point) => point.nouvelles_ecoles),
                    borderColor: '#18181b',
                    backgroundColor: 'rgba(24, 24, 27, 0.1)',
                    tension: 0.3,
                    fill: true,
                }],
            };
        },
    }));
});
