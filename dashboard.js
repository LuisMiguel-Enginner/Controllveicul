// Aguardar carregamento da página
document.addEventListener('DOMContentLoaded', function() {
    
    // Configurar gráfico de veículos (Pendente, Em andamento, Concluído)
    const ctx = document.getElementById('vehicleChart');
    
    if (ctx) {
        const defaultKey = 'all';
        const ranges = window.dashboardStatusRanges || null;
        const statusData = (ranges && ranges[defaultKey]) ? ranges[defaultKey] : (window.dashboardStatusData || {
            'Pendente': 0,
            'Em andamento': 0,
            'Concluído': 0,
        });

        const labels = ['Pendente', 'Em andamento', 'Concluído'];
        const values = labels.map(label => statusData[label] || 0);

        const gradient1 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient1.addColorStop(0, 'rgba(255, 193, 7, 0.9)');
        gradient1.addColorStop(1, 'rgba(255, 193, 7, 0.2)');
        
        const gradient2 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient2.addColorStop(0, 'rgba(0, 255, 255, 0.9)');
        gradient2.addColorStop(1, 'rgba(0, 255, 255, 0.2)');
        
        const gradient3 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient3.addColorStop(0, 'rgba(0, 255, 136, 0.9)');
        gradient3.addColorStop(1, 'rgba(0, 255, 136, 0.2)');

        const bgColors = [gradient1, gradient2, gradient3];
        const borderColors = ['#ffc107', '#00ffff', '#00ff88'];

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Veículos por status',
                        data: values,
                        backgroundColor: bgColors,
                        borderColor: borderColors,
                        borderWidth: 2,
                        borderRadius: 10,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#b0b8c4',
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(26, 35, 50, 0.95)',
                        titleColor: '#00ffff',
                        bodyColor: '#ffffff',
                        borderColor: '#00ffff',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.y + ' veículos';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#b0b8c4',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0, 255, 255, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#b0b8c4',
                            font: {
                                size: 12
                            },
                            precision: 0,
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        const periodFilter = document.getElementById('chart-period-filter');
        if (periodFilter && ranges) {
            const updateChart = (key) => {
                const counts = ranges[key] || statusData;
                const newValues = labels.map(label => counts[label] || 0);
                chart.data.datasets[0].data = newValues;
                chart.update();
            };
            updateChart(periodFilter.value || defaultKey);
            periodFilter.addEventListener('change', function() {
                updateChart(this.value || defaultKey);
            });
        }
    }
    
    // Adicionar gradiente SVG para o círculo de progresso
    const progressCircle = document.querySelector('.progress-circle svg');
    if (progressCircle) {
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        gradient.setAttribute('id', 'gradient');
        gradient.setAttribute('x1', '0%');
        gradient.setAttribute('y1', '0%');
        gradient.setAttribute('x2', '100%');
        gradient.setAttribute('y2', '100%');
        
        const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop1.setAttribute('offset', '0%');
        stop1.setAttribute('style', 'stop-color:#ff0000;stop-opacity:1');
        
        const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop2.setAttribute('offset', '100%');
        stop2.setAttribute('style', 'stop-color:#ffc107;stop-opacity:1');
        
        gradient.appendChild(stop1);
        gradient.appendChild(stop2);
        defs.appendChild(gradient);
        progressCircle.insertBefore(defs, progressCircle.firstChild);
    }
    
    // Animação de contagem nos stats
    animateValue('stat-value', 0, 45280, 2000);
    
    // Função para animar valores numéricos
    function animateValue(className, start, end, duration) {
        const elements = document.querySelectorAll('.' + className);
        elements.forEach(element => {
            const endValue = parseInt(element.textContent.replace(/[^0-9]/g, ''));
            if (!isNaN(endValue)) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const currentValue = Math.floor(progress * (endValue - start) + start);
                    
                    // Formatar valores conforme necessário
                    if (element.textContent.includes('R$')) {
                        element.textContent = 'R$ ' + currentValue.toLocaleString('pt-BR');
                    } else if (element.textContent.includes(',')) {
                        element.textContent = currentValue + ',' + element.textContent.split(',')[1];
                    } else {
                        element.textContent = currentValue;
                    }
                    
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }
        });
    }
    
    // Adicionar efeito de hover nos cards de estatísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Efeito de loading nas linhas da tabela
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.5s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, index * 100);
    });
    
    // Efeito de aparecer na timeline
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 200);
    });
    
    // Botões de ação da tabela (efeito visual)
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });

    // Modal de confirmação para exclusão de veículo
    const confirmModal = document.getElementById('confirm-delete-modal');
    const confirmText = document.getElementById('confirm-delete-text');
    const confirmCancel = document.getElementById('confirm-delete-cancel');
    const confirmOk = document.getElementById('confirm-delete-ok');
    let pendingDeleteForm = null;

    if (confirmModal && confirmText && confirmCancel && confirmOk) {
        const deleteButtons = document.querySelectorAll('.action-btn.delete');

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                // Se for botão dentro de form de exclusão, abre modal em vez de enviar direto
                const form = this.closest('form');
                if (form && form.getAttribute('action')) {
                    const action = form.getAttribute('action');
                    if (action.includes('excluir_veiculo.php')) {
                        e.preventDefault();
                        pendingDeleteForm = form;
                        const placa = this.getAttribute('data-placa') || '';
                        confirmText.textContent = placa
                            ? `Tem certeza que deseja excluir o veículo ${placa}?`
                            : 'Tem certeza que deseja excluir este veículo?';
                        confirmModal.classList.add('open');
                    } else if (action.includes('excluir_usuario.php')) {
                        e.preventDefault();
                        pendingDeleteForm = form;
                        const usuario = this.getAttribute('data-usuario') || '';
                        confirmText.textContent = usuario
                            ? `Tem certeza que deseja excluir o usuário ${usuario}?`
                            : 'Tem certeza que deseja excluir este usuário?';
                        confirmModal.classList.add('open');
                    }
                }
            });
        });

        confirmCancel.addEventListener('click', function () {
            confirmModal.classList.remove('open');
            pendingDeleteForm = null;
        });

        confirmOk.addEventListener('click', function () {
            if (pendingDeleteForm) {
                confirmModal.classList.remove('open');
                const formToSubmit = pendingDeleteForm;
                pendingDeleteForm = null;
                formToSubmit.submit();
            }
        });
    }

    // Toast de notificação (sucesso / erro)
    const toast = document.getElementById('toast-notification');
    if (toast) {
        // Mostrar com animação
        setTimeout(() => {
            toast.classList.add('show');
        }, 50);

        // Fechar ao clicar no X
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                toast.classList.remove('show');
            });
        }

        // Auto-esconder depois de alguns segundos
        setTimeout(() => {
            toast.classList.remove('show');
        }, 5000);
    }

    // Painel de notificações (sino do header)
    const notifBtn = document.querySelector('.notification-btn');
    const notifPanel = document.getElementById('notification-panel');

    if (notifBtn && notifPanel) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifPanel.classList.toggle('open');
        });

        document.addEventListener('click', function () {
            notifPanel.classList.remove('open');
        });

        notifPanel.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // Submenu do Barracão (Injetron leves / Barracão pesados)
    const submenuTriggers = document.querySelectorAll('.nav-item-with-children');
    submenuTriggers.forEach(trigger => {
        trigger.addEventListener('click', function () {
            const submenuId = this.getAttribute('data-submenu');
            const submenu = document.getElementById(submenuId);
            if (!submenu) return;

            const isOpen = submenu.classList.contains('open');

            // Fecha outros submenus
            document.querySelectorAll('.nav-submenu').forEach(sm => sm.classList.remove('open'));
            document.querySelectorAll('.nav-item-with-children').forEach(tr => tr.classList.remove('open'));

            // Abre/fecha o submenu clicado
            if (!isOpen) {
                submenu.classList.add('open');
                this.classList.add('open');
            }
        });
    });
    
    const tableHeaderTitle = document.querySelector('.table-section .table-header h3');
    if (tableHeaderTitle && /Lista de Veículos/i.test(tableHeaderTitle.textContent || '')) {
        const statusFilter = document.querySelector('.table-section .table-header select.chart-filter');
        const searchInput = document.querySelector('.table-section .table-header input[type="text"]');
        const rows = Array.from(document.querySelectorAll('.table-section .data-table tbody tr'));
        const getCellText = (row, idx) => {
            const cell = row.cells[idx];
            return cell ? (cell.textContent || '').toLowerCase() : '';
        };
        const filterRows = () => {
            const statusVal = (statusFilter && statusFilter.value) ? statusFilter.value : '';
            const query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
            rows.forEach(row => {
                const placa = getCellText(row, 0);
                const modelo = getCellText(row, 1);
                const montadora = getCellText(row, 2);
                const combustivel = getCellText(row, 4);
                const statusText = getCellText(row, 9);
                const matchesStatus = !statusVal || statusText.includes(statusVal.toLowerCase());
                const matchesQuery = !query || placa.includes(query) || modelo.includes(query) || montadora.includes(query) || combustivel.includes(query);
                row.style.display = (matchesStatus && matchesQuery) ? '' : 'none';
            });
        };
        if (statusFilter) statusFilter.addEventListener('change', filterRows);
        if (searchInput) searchInput.addEventListener('input', filterRows);
    }
    
    // Pesquisa na página de Empresas
    (function() {
        const empresaSearch = document.getElementById('empresa-search');
        if (!empresaSearch) return;
        // Resumo
        const resumoSection = Array.from(document.querySelectorAll('.table-section')).find(sec => {
            const h = sec.querySelector('.table-header h3');
            return h && /Empresas que já passaram/i.test(h.textContent || '');
        });
        const resumoRows = resumoSection ? Array.from(resumoSection.querySelectorAll('table.data-table tbody tr')) : [];
        // Veículos por Empresa
        const veiculosSection = Array.from(document.querySelectorAll('.table-section')).find(sec => {
            const h = sec.querySelector('.table-header h3');
            return h && /Veículos por Empresa/i.test(h.textContent || '');
        });
        const veiculosRows = veiculosSection ? Array.from(veiculosSection.querySelectorAll('table.data-table tbody tr')) : [];
        const filterEmpresas = () => {
            const q = (empresaSearch.value || '').trim().toLowerCase();
            resumoRows.forEach(row => {
                const empresa = (row.cells[0] && row.cells[0].textContent || '').toLowerCase();
                row.style.display = !q || empresa.includes(q) ? '' : 'none';
            });
            veiculosRows.forEach(row => {
                const texts = Array.from(row.cells).map(c => (c.textContent || '').toLowerCase());
                const match = !q || texts.some(t => t.includes(q));
                row.style.display = match ? '' : 'none';
            });
        };
        empresaSearch.addEventListener('input', filterEmpresas);
    })();
});
