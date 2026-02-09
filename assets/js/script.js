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

//pour definir la date du marige
// Fonction pour ouvrir le modal avec le style spécifique
function openDateModal() {
    // Vérifier si le modal existe déjà
    let modal = document.getElementById('wedding-date-modal');
    
    if (!modal) {
        // Créer le modal
        modal = document.createElement('div');
        modal.id = 'wedding-date-modal';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;';
        
        // Contenu du modal avec VOTRE style exact
        modal.innerHTML = `
            <div style="background: white; border-radius: 10px; padding: 20px; max-width: 500px; width: 90%;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; color: #8b4f8d;">
                        <i class="fas fa-calendar-alt"></i> Définir la date du mariage
                    </h2>
                    <button onclick="closeDateModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #8b4f8d;">&times;</button>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #5d2f5f;">
                        <i class="fas fa-heart" style="color: #8b4f8d; margin-right: 5px;"></i> Sélectionnez la date
                    </label>
                    <input type="date" id="wedding-date-input" style="width: 100%; padding: 10px; border: 2px solid #e8e3dd; border-radius: 5px; font-size: 1rem; color: #5d2f5f;">
                    <small style="color: #666; display: block; margin-top: 5px;">Choisissez le grand jour !</small>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background: #faf8f5; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #5d2f5f;">Aperçu :</h4>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 15px; background: white; border-radius: 5px; border: 1px solid #e8e3dd;">
                        <div style="background: #8b4f8d; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fas fa-church"></i>
                        </div>
                        <div>
                            <p style="margin: 0; font-weight: bold; color: #5d2f5f;" id="date-preview-text">Sélectionnez une date</p>
                            <p style="margin: 5px 0 0 0; color: #8b4f8d; font-weight: bold;" id="countdown-preview-text">--</p>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button onclick="closeDateModal()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">Annuler</button>
                    <button onclick="saveWeddingDate()" style="padding: 10px 20px; background: #8b4f8d; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-save" style="margin-right: 5px;"></i> Enregistrer la date
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Ajouter l'événement pour la prévisualisation
        document.getElementById('wedding-date-input').addEventListener('change', updateDatePreview);
    }
    
    // Afficher le modal
    modal.style.display = 'flex';
    
    // Pré-remplir avec la date existante si disponible
    if (window.weddingDate) {
        document.getElementById('wedding-date-input').value = window.weddingDate.toISOString().split('T')[0];
    } else {
        // Date par défaut (6 mois plus tard)
        const defaultDate = new Date();
        defaultDate.setMonth(defaultDate.getMonth() + 6);
        document.getElementById('wedding-date-input').value = defaultDate.toISOString().split('T')[0];
    }
    
    // Mettre à jour l'aperçu
    updateDatePreview();
}

// Fonction pour fermer le modal
function closeDateModal() {
    const modal = document.getElementById('wedding-date-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour mettre à jour l'aperçu
function updateDatePreview() {
    const dateInput = document.getElementById('wedding-date-input');
    const date = new Date(dateInput.value);
    
    if (date && !isNaN(date.getTime())) {
        // Formater la date
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = date.toLocaleDateString('fr-FR', options);
        
        // Calculer le compte à rebours
        const now = new Date();
        const timeDiff = date.getTime() - now.getTime();
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        
        // Mettre à jour l'aperçu
        document.getElementById('date-preview-text').textContent = formattedDate;
        
        let countdownText = '';
        if (days > 0) {
            countdownText = `${days} jour${days > 1 ? 's' : ''} restant${days > 1 ? 's' : ''}`;
        } else if (days === 0) {
            countdownText = '🎉 C\'est aujourd\'hui !';
        } else {
            countdownText = '⚠️ Date passée';
        }
        
        document.getElementById('countdown-preview-text').textContent = countdownText;
        
        // Changer la couleur
        const preview = document.getElementById('countdown-preview-text');
        if (days < 7) {
            preview.style.color = '#ff6b6b';
        } else if (days < 30) {
            preview.style.color = '#ffa726';
        } else {
            preview.style.color = '#4caf50';
        }
    }
}

// Fonction pour sauvegarder la date
async function saveWeddingDate() {
    const dateInput = document.getElementById('wedding-date-input').value;
    
    if (!dateInput) {
        alert('Veuillez sélectionner une date');
        return;
    }
    
    const selectedDate = new Date(dateInput);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Validation
    if (selectedDate <= today) {
        alert('La date doit être dans le futur');
        return;
    }
    
    try {
        const response = await fetch('api/api.php?action=save_wedding_date', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ date: dateInput })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Mettre à jour la date globale
            window.weddingDate = selectedDate;
            
            // Mettre à jour l'affichage du badge
            updateWeddingBadgeDisplay();
            
            // Fermer le modal
            closeDateModal();
            
            // Afficher un message de succès
            showSuccessMessage('Date de mariage enregistrée avec succès !');
        } else {
            alert(result.message || 'Erreur lors de l\'enregistrement');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    }
}

// Fonction pour mettre à jour l'affichage du badge
function updateWeddingBadgeDisplay() {
    if (!window.weddingDate) return;
    
    const badge = document.getElementById('wedding-date-badge');
    if (!badge) return;
    
    // Formater la date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = window.weddingDate.toLocaleDateString('fr-FR', options);
    
    // Calculer le compte à rebours
    const now = new Date();
    const timeDiff = window.weddingDate.getTime() - now.getTime();
    const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
    
    let countdownText = '';
    if (days > 30) {
        const months = Math.floor(days / 30);
        const remainingDays = days % 30;
        countdownText = `${months} mois ${remainingDays} jours`;
    } else if (days > 0) {
        countdownText = `${days} jours`;
    } else if (days === 0) {
        countdownText = '🎉 Aujourd\'hui !';
    }
    
    // Mettre à jour le badge
    badge.innerHTML = `
        <div style="background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%); color: white; padding: 12px 20px; border-radius: 10px; margin: 15px auto; max-width: 900px; border: 2px solid #d4af37;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                <div style="background: rgba(255, 255, 255, 0.2); padding: 10px; border-radius: 50%;">
                    <i class="fas fa-heart"></i>
                </div>
                <div style="flex: 1; display: flex; flex-wrap: wrap; align-items: center; gap: 10px 20px;">
                    <span style="font-weight: 600; color: #ffd700;">🎊 Date du Mariage :</span>
                    <span style="background: rgba(255, 255, 255, 0.15); padding: 5px 12px; border-radius: 20px; font-weight: bold; border: 1px dashed rgba(255, 255, 255, 0.3);">
                        ${formattedDate}
                    </span>
                    <span style="font-weight: bold; color: #ffd700;">
                        ${countdownText}
                    </span>
                </div>
                <button onclick="openDateModal()" style="background: rgba(255, 255, 255, 0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer;">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </div>
    `;
}

// Fonction pour afficher un message de succès
function showSuccessMessage(message) {
    // Créer un toast temporaire
    const toast = document.createElement('div');
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 15px 20px; border-radius: 5px; z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    toast.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 8px;"></i> ${message}`;
    
    document.body.appendChild(toast);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s';
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 500);
    }, 3000);
}

// Fonction pour charger et initialiser le badge
async function loadWeddingDate() {
    try {
        const response = await fetch('api/api.php?action=get_wedding_date');
        const result = await response.json();
        
        if (result.success && result.date) {
            window.weddingDate = new Date(result.date);
            createWeddingBadge();
        } else {
            createEmptyWeddingBadge();
        }
    } catch (error) {
        console.error('Erreur:', error);
        createEmptyWeddingBadge();
    }
}

// Fonction pour créer le badge quand il y a une date
function createWeddingBadge() {
    const container = document.getElementById('wedding-date-container');
    if (!container) return;
    
    container.innerHTML = `
        <div id="wedding-date-badge">
            <!-- Le badge sera rempli par updateWeddingBadgeDisplay() -->
        </div>
    `;
    
    updateWeddingBadgeDisplay();
}

// Fonction pour créer le badge vide
function createEmptyWeddingBadge() {
    const container = document.getElementById('wedding-date-container');
    if (!container) return;
    
    container.innerHTML = `
        <div id="wedding-date-badge">
            <div style="background: linear-gradient(135deg, #8b4f8d 0%, #5d2f5f 100%); color: white; padding: 12px 20px; border-radius: 10px; margin: 15px auto; max-width: 900px; border: 2px solid #d4af37; text-align: center; cursor: pointer;" onclick="openDateModal()">
                <i class="fas fa-calendar-plus" style="margin-right: 10px;"></i>
                <strong>Cliquez pour définir la date de votre mariage</strong>
            </div>
        </div>
    `;
}

// Initialiser quand la page est chargée
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si Font Awesome est chargé, sinon le charger
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(faLink);
    }
    
    // Charger la date
    loadWeddingDate();
    
    // Fermer le modal en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('wedding-date-modal');
        if (modal && modal.style.display === 'flex') {
            if (event.target === modal) {
                closeDateModal();
            }
        }
    });
    
    // Fermer avec la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDateModal();
        }
    });
});

