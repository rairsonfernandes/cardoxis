/**
 * CARDOXIS - Admin Dashboard JavaScript
 * Version: 8.0.0 (Production Ready)
 */

(function() {
    'use strict';
    
    // Inicializar gráficos
    function initCharts() {
        // Gráfico de Manutenções por Mês
        var maintenanceCtx = document.getElementById('maintenanceChart');
        if (maintenanceCtx && window.maintenancesByMonth) {
            maintenanceCtx = maintenanceCtx.getContext('2d');
            var months = window.maintenancesByMonth.map(function(item) { return item.month; });
            var counts = window.maintenancesByMonth.map(function(item) { return parseInt(item.count) || 0; });
            
            new Chart(maintenanceCtx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Manutenções',
                        data: counts,
                        backgroundColor: '#0052CC',
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
        
        // Gráfico de Receita por Mês
        var revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx && window.revenueByMonth) {
            revenueCtx = revenueCtx.getContext('2d');
            var revMonths = window.revenueByMonth.map(function(item) { return item.month; });
            var revValues = window.revenueByMonth.map(function(item) { return parseFloat(item.total) || 0; });
            
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: revMonths,
                    datasets: [{
                        label: 'Receita (€)',
                        data: revValues,
                        borderColor: '#00875A',
                        backgroundColor: 'rgba(0,135,90,0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
        
        // Gráfico de Veículos por Marca
        var brandCtx = document.getElementById('brandChart');
        if (brandCtx && window.vehiclesByBrand) {
            brandCtx = brandCtx.getContext('2d');
            var brands = window.vehiclesByBrand.map(function(item) { return item.brand; });
            var brandCounts = window.vehiclesByBrand.map(function(item) { return parseInt(item.count) || 0; });
            
            new Chart(brandCtx, {
                type: 'pie',
                data: {
                    labels: brands,
                    datasets: [{
                        data: brandCounts,
                        backgroundColor: ['#0052CC', '#00875A', '#6554C0', '#FF8B00', '#DE350B', '#00A3BF']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
        
        // Gráfico de Distribuição de Planos
        var planCtx = document.getElementById('planChart');
        if (planCtx && window.plansDistribution) {
            planCtx = planCtx.getContext('2d');
            var plans = window.plansDistribution.map(function(item) { return item.name; });
            var planCounts = window.plansDistribution.map(function(item) { return parseInt(item.count) || 0; });
            
            new Chart(planCtx, {
                type: 'doughnut',
                data: {
                    labels: plans,
                    datasets: [{
                        data: planCounts,
                        backgroundColor: ['#0052CC', '#00875A', '#6554C0']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }
    
    // Inicializar
    function init() {
        console.log('[CARDOXIS] Admin Dashboard inicializado');
        initCharts();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();