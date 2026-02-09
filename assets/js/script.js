// Variables globales
const API_BASE = 'api/';
let currentExpenses = [];
let currentCategories = [];
let editingExpenseId = null;
let filteredExpenses = [];
let activeFilters = {
    category: '',
    status: '',
    search: '',
    minPrice: null,
    maxPrice: null
};
let isUserLoggedIn = false;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    loadCategories();
    loadExpenses();
    loadStats();
    
    // Gérer le changement de catégorie pour afficher le champ nouvelle catégorie
    document.getElementById('category-select').addEventListener('change', function() {
        const newCategoryGroup = document.getElementById('new-category-group');
        if (this.value === 'new') {
            newCategoryGroup.style.display = 'block';
            document.getElementById('new-category').required = true;
        } else {
            newCategoryGroup.style.display = 'none';
            document.getElementById('new-category').required = false;
        }
    });
});

// Vérifier l'authentification
async function checkAuthentication() {
    try {
        const response = await fetch(`${API_BASE}auth_api.php?action=check`);
        const result = await response.json();
        isUserLoggedIn = result.logged_in || false;
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'authentification:', error);
        isUserLoggedIn = false;
    }
}

// Fonction de déconnexion
async function logout() {
    //if (!confirm('Voulez-vous vraiment vous déconnecter ?')) {
     //   return;
    //}
    
    try {
        const response = await fetch(`${API_BASE}auth_api.php?action=logout`);
        const result = await response.json();
        
        if (result.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
    }
}

// Vérifier l'authentification avant une action
function requireAuth() {
    if (!isUserLoggedIn) {
        if (confirm('Vous devez être connecté pour effectuer cette action. Voulez-vous vous connecter maintenant ?')) {
            window.location.href = 'login.php';
        }
        return false;
    }
    return true;
}

// Mettre à jour les stats du footer
function updateFooterStats() {
    const paidTotal = currentExpenses.filter(e => e.paid == 1).length;
    const unpaidTotal = currentExpenses.filter(e => e.paid == 0).length;
    const total = currentExpenses.length;
    
   // document.getElementById('footer-stats').innerHTML = `
      //  <strong>${total}</strong> dépenses au total<br>
      //  <span style="color: var(--success)">✓ ${paidTotal} payées</span> | 
      //  <span style="color: var(--warning)">✗ ${unpaidTotal} en attente</span>
    //`;
}

// Changer d'onglet
function switchTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Désactiver tous les boutons
    document.querySelectorAll('.nav-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activer l'onglet sélectionné
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
    
    // Recharger les données selon l'onglet
    if (tabName === 'dashboard') {
        loadStats();
        loadCategorySummary();
    } else if (tabName === 'details') {
        loadExpenses();
    } else if (tabName === 'payments') {
        loadPaymentStatus();
    }
}

// Charger les catégories
async function loadCategories() {
    try {
        const response = await fetch(`${API_BASE}api.php?action=get_categories`);
        const result = await response.json();
        
        if (result.success) {
            currentCategories = result.data;
            populateCategorySelect();
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors du chargement des catégories', 'error');
    }
}

// Remplir le select des catégories
function populateCategorySelect() {
    const select = document.getElementById('category-select');
    select.innerHTML = '<option value="">Sélectionner...</option>';
    
    currentCategories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        select.appendChild(option);
    });
    
    // Ajouter l'option nouvelle catégorie
    const newOption = document.createElement('option');
    newOption.value = 'new';
    newOption.textContent = '➕ Nouvelle catégorie';
    select.appendChild(newOption);
    
    // Remplir aussi le select de filtres
    populateFilterCategorySelect();
}

// Remplir le select de filtres par catégorie
function populateFilterCategorySelect() {
    const filterSelect = document.getElementById('filter-category');
    if (!filterSelect) return;
    
    filterSelect.innerHTML = '<option value="">Toutes les catégories</option>';
    
    currentCategories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        filterSelect.appendChild(option);
    });
}

// Charger les statistiques
async function loadStats() {
    try {
        const response = await fetch(`${API_BASE}api.php?action=get_stats`);
        const result = await response.json();
        
        if (result.success) {
            displayStats(result.data);
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors du chargement des statistiques', 'error');
    }
}

// Afficher les statistiques
function displayStats(stats) {
    const percentage = stats.payment_percentage.toFixed(1);
    
    const statsHTML = `
        <div class="stat-card">
            <h3>Budget Total</h3>
            <div class="value">${formatCurrency(stats.grand_total)}</div>
            <div class="subtitle">Montant total prévu</div>
        </div>
        <div class="stat-card">
            <h3>Montant Payé</h3>
            <div class="value" style="color: var(--success)">${formatCurrency(stats.paid_total)}</div>
            <div class="subtitle">${percentage}% du budget</div>
        </div>
        <div class="stat-card">
            <h3>Reste à Payer</h3>
            <div class="value" style="color: var(--warning)">${formatCurrency(stats.unpaid_total)}</div>
            <div class="subtitle">${(100 - percentage).toFixed(1)}% du budget</div>
        </div>
        <div class="stat-card">
            <h3>Nombre d'Articles</h3>
            <div class="value">${stats.total_items}</div>
            <div class="subtitle">${stats.paid_items} payés / ${stats.unpaid_items} en attente</div>
        </div>
    `;
    
    document.getElementById('stats-grid').innerHTML = statsHTML;
    
    // Afficher la barre de progression
    const progressHTML = `
        <div class="progress-label">
            <span>Progression des Paiements</span>
            <span>${percentage}%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: ${percentage}%">
                ${percentage}%
            </div>
        </div>
    `;
    
    document.getElementById('progress-container').innerHTML = progressHTML;
}

// Charger le résumé par catégorie
async function loadCategorySummary() {
    try {
        const response = await fetch(`${API_BASE}api.php?action=category_stats`);
        const result = await response.json();
        
        if (result.success) {
            displayCategorySummary(result.data);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Afficher le résumé par catégorie
function displayCategorySummary(categories) {
    const tbody = document.getElementById('category-summary-body');
    let html = '';
    let grandTotal = 0;
    let grandPaid = 0;
    
    categories.forEach(cat => {
        // Convertir en nombre pour éviter NaN
        const total = parseFloat(cat.total) || 0;
        const paid = parseFloat(cat.paid) || 0;
        const remaining = parseFloat(cat.remaining) || 0;
        const percentage = parseFloat(cat.percentage) || 0;
        
        const badgeClass = paid === total && total > 0 ? 'badge-paid' : 'badge-unpaid';
        
        // Récupérer la couleur et l'icône depuis la catégorie
        const categoryInfo = currentCategories.find(c => c.id == cat.id);
        const color = categoryInfo?.color || '#8b4f8d';
        const icon = categoryInfo?.icon || 'fas fa-folder';
        
        html += `
            <tr>
                <td>
                    <i class="${icon}" style="color: ${color}; margin-right: 8px;"></i>
                    <strong>${cat.name}</strong>
                </td>
                <td style="text-align: right">${formatCurrency(total)}</td>
                <td style="text-align: right; color: var(--success)">${formatCurrency(paid)}</td>
                <td style="text-align: right; color: var(--warning)">${formatCurrency(remaining)}</td>
                <td style="text-align: center">
                    <span class="badge ${badgeClass}">${percentage.toFixed(0)}%</span>
                </td>
            </tr>
        `;
        
        grandTotal += total;
        grandPaid += paid;
    });
    
    const grandPercentage = grandTotal > 0 ? ((grandPaid / grandTotal) * 100).toFixed(1) : 0;
    
    html += `
        <tr class="total-row">
            <td><strong>TOTAL GÉNÉRAL</strong></td>
            <td style="text-align: right"><strong>${formatCurrency(grandTotal)}</strong></td>
            <td style="text-align: right"><strong>${formatCurrency(grandPaid)}</strong></td>
            <td style="text-align: right"><strong>${formatCurrency(grandTotal - grandPaid)}</strong></td>
            <td style="text-align: center"><strong>${grandPercentage}%</strong></td>
        </tr>
    `;
    
    tbody.innerHTML = html;
}

// Charger toutes les dépenses
async function loadExpenses() {
    try {
        const response = await fetch(`${API_BASE}api.php?action=get_all`);
        const result = await response.json();
        
        if (result.success) {
            currentExpenses = result.data;
            filteredExpenses = [...currentExpenses];
            displayExpenses();
            //updateFooterStats();
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors du chargement des dépenses', 'error');
    }
}

// Afficher les dépenses dans le tableau détaillé
function displayExpenses() {
    const tbody = document.getElementById('expenses-body');
    let html = '';
    let currentCategory = '';
    let categoryTotal = 0;
    
    filteredExpenses.forEach((expense, index) => {
        const total = expense.quantity * expense.unit_price * expense.frequency;
        
        // Afficher l'en-tête de catégorie
        if (expense.category_name !== currentCategory) {
            // Afficher le sous-total de la catégorie précédente
            if (currentCategory !== '') {
                html += `
                    <tr class="subtotal-row">
                        <td colspan="5"><strong>Sous-total ${currentCategory}</strong></td>
                        <td style="text-align: right"><strong>${formatCurrency(categoryTotal)}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                `;
            }
            
            currentCategory = expense.category_name;
            categoryTotal = 0;
            
            // Récupérer la couleur et l'icône depuis la catégorie
            const categoryInfo = currentCategories.find(c => c.id == expense.category_id);
            const color = categoryInfo?.color || '#8b4f8d';
            const icon = categoryInfo?.icon || 'fas fa-folder';
            
            html += `
                <tr class="category-header">
                    <td colspan="8">
                        <i class="${icon}" style="color: ${color}; margin-right: 8px;"></i>
                        ${expense.category_name}
                    </td>
                </tr>
            `;
        }
        
        categoryTotal += total;
        
        const badgeClass = expense.paid == 1 ? 'badge-paid' : 'badge-unpaid';
        const badgeText = expense.paid == 1 ? 'Payé' : 'Non payé';
        const toggleIcon = expense.paid == 1 ? '✗' : '✓';
        const toggleClass = expense.paid == 1 ? 'btn-warning' : 'btn-success';
        const toggleTitle = expense.paid == 1 ? 'Marquer comme non payé' : 'Marquer comme payé';
        
        html += `
            <tr>
                <td></td>
                <td>${expense.name}</td>
                <td style="text-align: center">${expense.quantity}</td>
                <td style="text-align: right">${formatCurrency(expense.unit_price)}</td>
                <td style="text-align: center">${expense.frequency}</td>
                <td style="text-align: right"><strong>${formatCurrency(total)}</strong></td>
                <td style="text-align: center">
                    <span class="badge ${badgeClass}">${badgeText}</span>
                </td>
                <td style="text-align: center">
                    <div class="action-buttons">
                        <button class="btn btn-sm ${toggleClass}" 
                                onclick="togglePaid(${expense.id})" 
                                title="${toggleTitle}">
                            ${toggleIcon}
                        </button>
                        <button class="btn btn-sm btn-primary" 
                                onclick="editExpense(${expense.id})" 
                                title="Modifier">
                            ✎
                        </button>
                        <button class="btn btn-sm btn-danger" 
                                onclick="deleteExpense(${expense.id})" 
                                title="Supprimer">
                            🗑
                        </button>
                    </div>
                </td>
            </tr>
        `;
        
        // Afficher le sous-total de la dernière catégorie
        if (index === filteredExpenses.length - 1) {
            html += `
                <tr class="subtotal-row">
                    <td colspan="5"><strong>Sous-total ${currentCategory}</strong></td>
                    <td style="text-align: right"><strong>${formatCurrency(categoryTotal)}</strong></td>
                    <td colspan="2"></td>
                </tr>
            `;
        }
    });
    
    // Calculer le total général des dépenses filtrées
    const grandTotal = filteredExpenses.reduce((sum, exp) => 
        sum + (exp.quantity * exp.unit_price * exp.frequency), 0);
    
    html += `
        <tr class="total-row">
            <td colspan="5"><strong>TOTAL${filteredExpenses.length !== currentExpenses.length ? ' (FILTRÉ)' : ' GÉNÉRAL'}</strong></td>
            <td style="text-align: right"><strong>${formatCurrency(grandTotal)}</strong></td>
            <td colspan="2"></td>
        </tr>
    `;
    
    tbody.innerHTML = html || '<tr><td colspan="8" style="text-align: center; padding: 40px;">Aucune dépense ne correspond aux filtres sélectionnés</td></tr>';
    
    // Mettre à jour le texte des résultats de filtre
    updateFilterResults();
}

// Charger le statut des paiements
async function loadPaymentStatus() {
    const paidExpenses = currentExpenses.filter(e => e.paid == 1);
    const unpaidExpenses = currentExpenses.filter(e => e.paid == 0);
    
    // Afficher les dépenses payées
    let paidHTML = '';
    let paidTotal = 0;
    
    paidExpenses.forEach(expense => {
        const total = expense.quantity * expense.unit_price * expense.frequency;
        paidTotal += total;
        
        paidHTML += `
            <tr>
                <td>${expense.category_name}</td>
                <td>${expense.name}</td>
                <td style="text-align: center">${expense.quantity}</td>
                <td style="text-align: right">${formatCurrency(expense.unit_price)}</td>
                <td style="text-align: right"><strong>${formatCurrency(total)}</strong></td>
                <td style="text-align: center">${expense.payment_date || '-'}</td>
                <td style="text-align: center">
                    <button class="btn btn-sm btn-warning" onclick="togglePaid(${expense.id})">
                        Annuler paiement
                    </button>
                </td>
            </tr>
        `;
    });
    
    paidHTML += `
        <tr class="total-row">
            <td colspan="4"><strong>TOTAL PAYÉ</strong></td>
            <td style="text-align: right"><strong>${formatCurrency(paidTotal)}</strong></td>
            <td colspan="2"></td>
        </tr>
    `;
    
    document.getElementById('paid-expenses-body').innerHTML = paidHTML;
    
    // Afficher les dépenses non payées
    let unpaidHTML = '';
    let unpaidTotal = 0;
    
    unpaidExpenses.forEach(expense => {
        const total = expense.quantity * expense.unit_price * expense.frequency;
        unpaidTotal += total;
        
        unpaidHTML += `
            <tr>
                <td>${expense.category_name}</td>
                <td>${expense.name}</td>
                <td style="text-align: center">${expense.quantity}</td>
                <td style="text-align: right">${formatCurrency(expense.unit_price)}</td>
                <td style="text-align: right"><strong>${formatCurrency(total)}</strong></td>
                <td style="text-align: center">
                    <button class="btn btn-sm btn-success" onclick="togglePaid(${expense.id})">
                        Marquer payé
                    </button>
                </td>
            </tr>
        `;
    });
    
    unpaidHTML += `
        <tr class="total-row">
            <td colspan="4"><strong>TOTAL RESTANT</strong></td>
            <td style="text-align: right"><strong>${formatCurrency(unpaidTotal)}</strong></td>
            <td></td>
        </tr>
    `;
    
    document.getElementById('unpaid-expenses-body').innerHTML = unpaidHTML;
}

// Ouvrir le modal
function openModal() {
    if (!requireAuth()) return;
    
    editingExpenseId = null;
    document.getElementById('modal-title').textContent = 'Nouvelle Dépense';
    document.getElementById('submit-btn-text').textContent = 'Ajouter';
    document.getElementById('expense-form').reset();
    document.getElementById('expense-id').value = '';
    document.getElementById('new-category-group').style.display = 'none';
    document.getElementById('expense-modal').style.display = 'flex';
}

// Fermer le modal
function closeModal() {
    document.getElementById('expense-modal').style.display = 'none';
    editingExpenseId = null;
}

// Éditer une dépense
async function editExpense(id) {
    if (!requireAuth()) return;
    
    try {
        const response = await fetch(`${API_BASE}api.php?action=get_by_id&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            const expense = result.data;
            editingExpenseId = id;
            
            document.getElementById('modal-title').textContent = 'Modifier la Dépense';
            document.getElementById('submit-btn-text').textContent = 'Mettre à jour';
            document.getElementById('expense-id').value = id;
            document.getElementById('category-select').value = expense.category_id;
            document.getElementById('expense-name').value = expense.name;
            document.getElementById('quantity').value = expense.quantity;
            document.getElementById('unit-price').value = expense.unit_price;
            document.getElementById('frequency').value = expense.frequency;
            document.getElementById('paid').checked = expense.paid == 1;
            document.getElementById('payment-date').value = expense.payment_date || '';
            document.getElementById('notes').value = expense.notes || '';
            
            document.getElementById('expense-modal').style.display = 'flex';
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors du chargement de la dépense', 'error');
    }
}

// Soumettre le formulaire
async function handleSubmit(event) {
    event.preventDefault();
    
    const formData = {
        category_id: document.getElementById('category-select').value,
        name: document.getElementById('expense-name').value,
        quantity: parseInt(document.getElementById('quantity').value),
        unit_price: parseFloat(document.getElementById('unit-price').value),
        frequency: parseInt(document.getElementById('frequency').value),
        paid: document.getElementById('paid').checked,
        payment_date: document.getElementById('payment-date').value || null,
        notes: document.getElementById('notes').value || null
    };
    
    // Gérer la nouvelle catégorie
    if (formData.category_id === 'new') {
        formData.new_category = document.getElementById('new-category').value;
        delete formData.category_id;
    }
    
    try {
        let url = `${API_BASE}api.php?action=add`;
        if (editingExpenseId) {
            url = `${API_BASE}api.php?action=update&id=${editingExpenseId}`;
        }
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeModal();
            await loadCategories();
            await loadExpenses();
            await loadStats();
            if (document.getElementById('dashboard-tab').classList.contains('active')) {
                loadCategorySummary();
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de l\'enregistrement', 'error');
    }
}

// Basculer le statut de paiement
async function togglePaid(id) {
    if (!requireAuth()) return;
    
    try {
        const response = await fetch(`${API_BASE}api.php?action=toggle_paid&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            await loadExpenses();
            await loadStats();
            if (document.getElementById('dashboard-tab').classList.contains('active')) {
                loadCategorySummary();
            } else if (document.getElementById('payments-tab').classList.contains('active')) {
                loadPaymentStatus();
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de la mise à jour', 'error');
    }
}

// Supprimer une dépense
async function deleteExpense(id) {
    if (!requireAuth()) return;
    
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}api.php?action=delete&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            await loadExpenses();
            await loadStats();
            if (document.getElementById('dashboard-tab').classList.contains('active')) {
                loadCategorySummary();
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur lors de la suppression', 'error');
    }
}

// Afficher un toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Formater les montants
function formatCurrency(amount) {
    // Convertir en nombre et gérer les valeurs invalides
    const numAmount = parseFloat(amount);
    
    // Si ce n'est pas un nombre valide, retourner 0 FCFA
    if (isNaN(numAmount) || numAmount === null || numAmount === undefined) {
        return '0 FCFA';
    }
    
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numAmount) + ' FCFA';
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('expense-modal');
    if (event.target === modal) {
        closeModal();
    }
}

// Toggle filters panel
function toggleFilters() {
    const panel = document.getElementById('filters-panel');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

// Apply filters
function applyFilters() {
    activeFilters.category = document.getElementById('filter-category').value;
    activeFilters.status = document.getElementById('filter-status').value;
    activeFilters.search = document.getElementById('filter-search').value.toLowerCase();
    activeFilters.minPrice = document.getElementById('filter-min').value ? parseFloat(document.getElementById('filter-min').value) : null;
    activeFilters.maxPrice = document.getElementById('filter-max').value ? parseFloat(document.getElementById('filter-max').value) : null;
    
    // Filter expenses
    filteredExpenses = currentExpenses.filter(expense => {
        // Filter by category
        if (activeFilters.category && expense.category_id != activeFilters.category) {
            return false;
        }
        
        // Filter by status
        if (activeFilters.status === 'paid' && expense.paid != 1) {
            return false;
        }
        if (activeFilters.status === 'unpaid' && expense.paid != 0) {
            return false;
        }
        
        // Filter by search
        if (activeFilters.search && !expense.name.toLowerCase().includes(activeFilters.search)) {
            return false;
        }
        
        // Filter by price range
        const totalPrice = expense.quantity * expense.unit_price * expense.frequency;
        if (activeFilters.minPrice !== null && totalPrice < activeFilters.minPrice) {
            return false;
        }
        if (activeFilters.maxPrice !== null && totalPrice > activeFilters.maxPrice) {
            return false;
        }
        
        return true;
    });
    
    displayExpenses();
    updateFilterCount();
}

// Update filter count badge
function updateFilterCount() {
    const filterCount = document.getElementById('filter-count');
    let count = 0;
    
    if (activeFilters.category) count++;
    if (activeFilters.status) count++;
    if (activeFilters.search) count++;
    if (activeFilters.minPrice !== null) count++;
    if (activeFilters.maxPrice !== null) count++;
    
    if (count > 0) {
        filterCount.textContent = count;
        filterCount.style.display = 'inline';
    } else {
        filterCount.style.display = 'none';
    }
}

// Update filter results text
function updateFilterResults() {
    const resultsText = document.getElementById('filter-results-text');
    if (!resultsText) return;
    
    const filtered = filteredExpenses.length;
    const total = currentExpenses.length;
    
    if (filtered === total) {
        resultsText.innerHTML = `Affichage de <strong>${total}</strong> dépense(s)`;
    } else {
        resultsText.innerHTML = `Affichage de <strong>${filtered}</strong> sur <strong>${total}</strong> dépense(s)`;
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('filter-category').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-search').value = '';
    document.getElementById('filter-min').value = '';
    document.getElementById('filter-max').value = '';
    
    activeFilters = {
        category: '',
        status: '',
        search: '',
        minPrice: null,
        maxPrice: null
    };
    
    filteredExpenses = [...currentExpenses];
    displayExpenses();
    updateFilterCount();
}
