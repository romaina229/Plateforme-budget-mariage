/**
 * Budget Mariage PJPM — script.js v2.1
 * ATTENTION : Ce fichier est inclus APRÈS la définition des constantes dans index.php
 * NE PAS redéclarer les constantes !
 */
'use strict';

// ✅ NE PAS redéclarer API, AUTH_API, APP_URL - elles existent déjà dans window
// Utiliser window.API, window.AUTH_API, window.APP_URL ou valeurs par défaut

if (typeof window.API === 'undefined') {
    console.error('❌ API non définie - vérifier index.php');
    window.API = window.location.origin + '/wedding/api/api.php';
}

if (typeof window.AUTH_API === 'undefined') {
    window.AUTH_API = window.location.origin + '/wedding/api/auth_api.php';
}

if (typeof window.APP_URL === 'undefined') {
    window.APP_URL = window.location.origin + '/wedding';
}

// Aliases pour le code existant
const API = window.API;
const AUTH_API = window.AUTH_API;
const APP_URL = window.APP_URL;

// ── État global ───────────────────────────────────────────────
const state = {
    expenses: [], categories: [], filtered: [],
    editingId: null, isLoggedIn: false, currentUser: null,
    weddingDate: null, filters: { category:'', status:'', search:'', min:null, max:null },
    countdown: null,
    categoriesStats: null
};

// ── Utilitaires ───────────────────────────────────────────────
function fc(amount) {
    const n = parseFloat(amount);
    if (isNaN(n)) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(n) + ' FCFA';
}
const eh = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const api = async (action, opts={}) => {
    const url = opts.auth ? `${AUTH_API}?action=${action}` : `${API}?action=${action}${opts.id?'&id='+opts.id:''}`;
    const cfg = {
        headers: { 'Content-Type':'application/json' },
        credentials: 'same-origin'   // FIX : cookie de session PHP
    };
    if (opts.body) { cfg.method='POST'; cfg.body=JSON.stringify(opts.body); }
    const r = await fetch(url, cfg);
    // FIX : session expirée → redirection login
    if (r.status === 401) { window.location.href = 'auth/login.php'; throw new Error('Non authentifié'); }
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
};
const toast = (msg, type='success') => {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.className = `toast toast-${type} show`;
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 3500);
};

// ── Authentification ──────────────────────────────────────────
async function checkAuth() {
    try {
        const d = await api('check', { auth:true });
        // FIX : auth_api retourne {success:true, data:{logged_in:bool, user:{...}}}
        const payload    = d.data || d;
        state.isLoggedIn  = payload.logged_in || false;
        state.currentUser = payload.user || null;
    } catch { state.isLoggedIn = false; }
}
function requireAuth() {
    if (!state.isLoggedIn) {
        if (confirm('Vous devez être connecté. Aller à la page de connexion ?'))
            window.location.href = 'login.php';
        return false;
    }
    return true;
}

// ── Navigation par onglets ────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
        el.hidden = true;
    });
    document.querySelectorAll('.nav-tab').forEach(el => {
        el.classList.remove('active');
        el.setAttribute('aria-selected','false');
    });

    const tab = document.getElementById(name+'-tab');
    const btn = document.getElementById('tab-'+name);
    if (tab) { tab.classList.add('active'); tab.hidden = false; }
    if (btn) { btn.classList.add('active'); btn.setAttribute('aria-selected','true'); }

    if (name === 'dashboard') { loadStats(); loadCategorySummary(); }
    if (name === 'details')   { renderExpenses(); }
    if (name === 'payments')  { renderPayments(); }
    if (name === 'stats') {
        if (state.categoriesStats && state.categoriesStats.length) {
            setTimeout(initCharts, 80);
        } else {
            api('category_stats').then(d => {
                if (d.success) { state.categoriesStats = d.data; setTimeout(initCharts, 80); }
            }).catch(console.error);
        }
    }
}

// ── Chargement des données ────────────────────────────────────
async function loadCategories() {
    try {
        const d = await api('get_categories');
        if (d.success) {
            state.categories = d.data;
            populateCategorySelects();
        }
    } catch(e) { console.error('categories:', e); }
}

async function loadExpenses() {
    try {
        const d = await api('get_all');
        if (d.success) {
            state.expenses  = d.data || [];
            state.filtered  = [...state.expenses];
            renderExpenses();
        }
    } catch(e) { console.error('expenses:', e); toast('Erreur chargement dépenses', 'error'); }
}

async function loadStats() {
    try {
        const d = await api('get_stats');
        if (d.success) renderStats(d.data);
    } catch(e) { console.error('stats:', e); }
}

async function loadCategorySummary() {
    // Stocke aussi dans state pour les graphiques
    try {
        const d = await api('category_stats');
        if (d.success) { state.categoriesStats = d.data; renderCategorySummary(d.data); }
    } catch(e) { console.error('cat_stats:', e); }
}

async function loadWeddingDate() {
    try {
        const d = await api('get_wedding_date');
        // FIX : api retourne {success:true, data:{date:'YYYY-MM-DD'}} ou {success:true, data:null}
        const dateStr = d.data?.date || d.date || null;
        if (d.success && dateStr) {
            state.weddingDate = new Date(dateStr + 'T00:00:00');
            renderWeddingBadge();
            startCountdown();
        } else {
            renderEmptyWeddingBadge();
        }
    } catch { renderEmptyWeddingBadge(); }
}

// ── Rendu : Stats ─────────────────────────────────────────────
function renderStats(s) {
    const pct = parseFloat(s.payment_percentage || 0).toFixed(1);
    const grid = document.getElementById('stats-grid');
    if (!grid) return;
    grid.innerHTML = `
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--primary-light)"><i class="fas fa-wallet"></i></div>
            <div class="stat-body">
                <p class="stat-label">Budget total</p>
                <p class="stat-value">${fc(s.grand_total)}</p>
                <p class="stat-sub">Montant global prévu</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#c8e6c9"><i class="fas fa-check-circle" style="color:#388e3c"></i></div>
            <div class="stat-body">
                <p class="stat-label">Montant payé</p>
                <p class="stat-value" style="color:var(--success)">${fc(s.paid_total)}</p>
                <p class="stat-sub">${pct}% du budget</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff3e0"><i class="fas fa-hourglass-half" style="color:#e65100"></i></div>
            <div class="stat-body">
                <p class="stat-label">Reste à payer</p>
                <p class="stat-value" style="color:var(--warning)">${fc(s.unpaid_total)}</p>
                <p class="stat-sub">${(100-parseFloat(pct)).toFixed(1)}% du budget</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#e8eaf6"><i class="fas fa-receipt" style="color:#3949ab"></i></div>
            <div class="stat-body">
                <p class="stat-label">Articles</p>
                <p class="stat-value">${s.total_items}</p>
                <p class="stat-sub">${s.paid_items} payés / ${s.unpaid_items} en attente</p>
            </div>
        </div>`;

    const prog = document.getElementById('progress-container');
    if (prog) prog.innerHTML = `
        <div class="progress-header">
            <span>Progression des paiements</span>
            <strong>${pct}%</strong>
        </div>
        <div class="progress-bar" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-fill" style="width:${pct}%">${pct}%</div>
        </div>`;
}

// ── Rendu : Récapitulatif catégories ──────────────────────────
function renderCategorySummary(cats) {
    const tbody = document.getElementById('category-summary-body');
    if (!tbody) return;
    let html = '', gTotal=0, gPaid=0;

    cats.forEach(c => {
        const total = parseFloat(c.total)||0, paid=parseFloat(c.paid)||0;
        const rem   = parseFloat(c.remaining)||0, pct=parseFloat(c.percentage)||0;
        const info  = state.categories.find(x=>x.id==c.id);
        const color = info?.color||'#8b4f8d', icon=info?.icon||'fas fa-folder';
        const cls   = pct>=100 ? 'badge-paid' : pct>0 ? 'badge-partial' : 'badge-unpaid';
        gTotal+=total; gPaid+=paid;
        html += `<tr>
            <td><i class="${eh(icon)}" style="color:${eh(color)};margin-right:8px"></i>${eh(c.name)}</td>
            <td class="text-right">${fc(total)}</td>
            <td class="text-right success-text">${fc(paid)}</td>
            <td class="text-right warning-text">${fc(rem)}</td>
            <td class="text-center">
                <div class="mini-progress" title="${pct.toFixed(0)}%">
                    <div class="mini-fill" style="width:${Math.min(pct,100)}%"></div>
                </div>
                <span class="badge ${cls}">${pct.toFixed(0)}%</span>
            </td>
        </tr>`;
    });

    const gPct = gTotal>0 ? ((gPaid/gTotal)*100).toFixed(1) : 0;
    html += `<tr class="total-row">
        <td><strong>TOTAL GÉNÉRAL</strong></td>
        <td class="text-right"><strong>${fc(gTotal)}</strong></td>
        <td class="text-right"><strong>${fc(gPaid)}</strong></td>
        <td class="text-right"><strong>${fc(gTotal-gPaid)}</strong></td>
        <td class="text-center"><strong>${gPct}%</strong></td>
    </tr>`;
    tbody.innerHTML = html;
}

// ── Rendu : Tableau dépenses ──────────────────────────────────
function renderExpenses() {
    const tbody = document.getElementById('expenses-body');
    if (!tbody) return;
    const data = state.filtered;
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty-row"><i class="fas fa-inbox"></i><br>Aucune dépense</td></tr>';
        return;
    }

    let html='', prevCat='', catTotal=0;
    data.forEach((e, i) => {
        const total = e.quantity * e.unit_price * e.frequency;
        if (e.category_name !== prevCat) {
            if (prevCat) html += `<tr class="subtotal-row"><td colspan="5">Sous-total ${eh(prevCat)}</td><td class="text-right">${fc(catTotal)}</td><td colspan="2"></td></tr>`;
            const info  = state.categories.find(c=>c.id==e.category_id);
            const color = info?.color||'#8b4f8d', icon=info?.icon||'fas fa-folder';
            html += `<tr class="category-header"><td colspan="8"><i class="${eh(icon)}" style="color:${eh(color)};margin-right:8px"></i><strong>${eh(e.category_name)}</strong></td></tr>`;
            prevCat = e.category_name; catTotal = 0;
        }
        catTotal += total;
        const paid = e.paid==1;
        html += `<tr class="${paid?'row-paid':''}">
            <td></td>
            <td>${eh(e.name)}${e.notes?`<small class="row-note"><br>${eh(e.notes)}</small>`:''}</td>
            <td class="text-center">${e.quantity}</td>
            <td class="text-right">${fc(e.unit_price)}</td>
            <td class="text-center">${e.frequency}</td>
            <td class="text-right"><strong>${fc(total)}</strong></td>
            <td class="text-center"><span class="badge ${paid?'badge-paid':'badge-unpaid'}">${paid?'Payé':'En attente'}</span></td>
            <td class="text-center">
                <div class="action-buttons">
                    <button class="btn btn-sm ${paid?'btn-warning':'btn-success'}" onclick="togglePaid(${e.id})" title="${paid?'Annuler paiement':'Marquer payé'}">
                        <i class="fas fa-${paid?'undo':'check'}"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editExpense(${e.id})" title="Modifier">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteExpense(${e.id})" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
        if (i === data.length-1)
            html += `<tr class="subtotal-row"><td colspan="5">Sous-total ${eh(prevCat)}</td><td class="text-right">${fc(catTotal)}</td><td colspan="2"></td></tr>`;
    });

    const gTotal = data.reduce((s,e)=>s+(e.quantity*e.unit_price*e.frequency),0);
    const label  = data.length!==state.expenses.length ? 'TOTAL (FILTRÉ)' : 'TOTAL GÉNÉRAL';
    html += `<tr class="total-row"><td colspan="5"><strong>${label}</strong></td><td class="text-right"><strong>${fc(gTotal)}</strong></td><td colspan="2"></td></tr>`;
    tbody.innerHTML = html;
    updateFilterResults();
}

// ── Rendu : Paiements ─────────────────────────────────────────
function renderPayments() {
    const paid   = state.expenses.filter(e=>e.paid==1);
    const unpaid = state.expenses.filter(e=>e.paid==0);

    const pBody = document.getElementById('paid-expenses-body');
    const uBody = document.getElementById('unpaid-expenses-body');
    if (!pBody||!uBody) return;

    if (!paid.length) {
        pBody.innerHTML = '<tr><td colspan="7" class="empty-row"><i class="fas fa-check-double"></i><br>Aucune dépense payée</td></tr>';
    } else {
        let h='', t=0;
        paid.forEach(e => {
            const tot = e.quantity*e.unit_price*e.frequency; t+=tot;
            h+=`<tr><td>${eh(e.category_name)}</td><td>${eh(e.name)}</td><td class="text-center">${e.quantity}</td><td class="text-right">${fc(e.unit_price)}</td><td class="text-right">${fc(tot)}</td><td class="text-center">${e.payment_date||'—'}</td>
            <td class="text-center"><button class="btn btn-sm btn-warning" onclick="togglePaid(${e.id})"><i class="fas fa-undo"></i> Annuler</button></td></tr>`;
        });
        h+=`<tr class="total-row"><td colspan="4"><strong>TOTAL PAYÉ</strong></td><td class="text-right"><strong>${fc(t)}</strong></td><td colspan="2"></td></tr>`;
        pBody.innerHTML = h;
    }

    if (!unpaid.length) {
        uBody.innerHTML = '<tr><td colspan="6" class="empty-row success-text"><i class="fas fa-trophy"></i><br>Tout est payé ! Félicitations !</td></tr>';
    } else {
        let h='', t=0;
        unpaid.forEach(e => {
            const tot = e.quantity*e.unit_price*e.frequency; t+=tot;
            h+=`<tr><td>${eh(e.category_name)}</td><td>${eh(e.name)}</td><td class="text-center">${e.quantity}</td><td class="text-right">${fc(e.unit_price)}</td><td class="text-right">${fc(tot)}</td>
            <td class="text-center"><button class="btn btn-sm btn-success" onclick="togglePaid(${e.id})"><i class="fas fa-check"></i> Payer</button></td></tr>`;
        });
        h+=`<tr class="total-row"><td colspan="4"><strong>TOTAL RESTANT</strong></td><td class="text-right"><strong>${fc(t)}</strong></td><td></td></tr>`;
        uBody.innerHTML = h;
    }
}

// ── Sélects catégories ────────────────────────────────────────
function populateCategorySelects() {
    const sel  = document.getElementById('category-select');
    const fsel = document.getElementById('filter-category');
    const opts = state.categories.map(c => `<option value="${c.id}">${eh(c.name)}</option>`).join('');

    if (sel)  sel.innerHTML  = '<option value="">Sélectionner…</option>' + opts + '<option value="new">➕ Nouvelle catégorie</option>';
    if (fsel) fsel.innerHTML = '<option value="">Toutes les catégories</option>' + opts;
}

// ── Modal dépense ─────────────────────────────────────────────
function openModal() {
    if (!requireAuth()) return;
    state.editingId = null;
    document.getElementById('modal-title').textContent = 'Nouvelle dépense';
    document.getElementById('submit-btn-text').textContent = 'Ajouter';
    document.getElementById('expense-form').reset();
    document.getElementById('expense-id').value = '';
    document.getElementById('new-category-group').style.display = 'none';
    document.getElementById('modal-total').style.display = 'none';
    showModal('expense-modal');
}

function closeModal() { hideModal('expense-modal'); state.editingId = null; }

async function editExpense(id) {
    if (!requireAuth()) return;
    try {
        const d = await api('get_by_id', { id });
        if (!d.success) return toast('Dépense introuvable', 'error');
        const e = d.data;
        state.editingId = id;
        document.getElementById('modal-title').textContent = 'Modifier la dépense';
        document.getElementById('submit-btn-text').textContent = 'Mettre à jour';
        document.getElementById('expense-id').value = id;
        document.getElementById('category-select').value = e.category_id;
        document.getElementById('expense-name').value = e.name;
        document.getElementById('quantity').value = e.quantity;
        document.getElementById('unit-price').value = e.unit_price;
        document.getElementById('frequency').value = e.frequency;
        document.getElementById('paid').checked = e.paid==1;
        document.getElementById('payment-date').value = e.payment_date||'';
        document.getElementById('notes').value = e.notes||'';
        document.getElementById('new-category-group').style.display = 'none';
        updateModalTotal();
        showModal('expense-modal');
    } catch { toast('Erreur chargement', 'error'); }
}

async function handleSubmit(ev) {
    ev.preventDefault();
    const btn  = document.getElementById('submit-btn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';

    const catVal = document.getElementById('category-select').value;
    const body   = {
        category_id: catVal !== 'new' ? catVal : null,
        name:        document.getElementById('expense-name').value.trim(),
        quantity:    parseInt(document.getElementById('quantity').value),
        unit_price:  parseFloat(document.getElementById('unit-price').value),
        frequency:   parseInt(document.getElementById('frequency').value),
        paid:        document.getElementById('paid').checked,
        payment_date:document.getElementById('payment-date').value || null,
        notes:       document.getElementById('notes').value.trim() || null,
    };
    if (catVal === 'new') {
        const newCat = document.getElementById('new-category').value.trim();
        if (!newCat) { toast('Saisissez un nom de catégorie','error'); btn.disabled=false; btn.innerHTML=orig; return; }
        body.new_category = newCat;
    }

    try {
        const action = state.editingId ? `update&id=${state.editingId}` : 'add';
        const d = await api(action, { body });
        if (d.success) {
            toast(d.message, 'success');
            closeModal();
            await Promise.all([loadCategories(), loadExpenses(), loadStats()]);
            loadCategorySummary();
        } else {
            toast(d.message || 'Erreur', 'error');
        }
    } catch { toast('Erreur réseau', 'error'); }
    finally { btn.disabled=false; btn.innerHTML=orig; }
}

// ── CRUD ──────────────────────────────────────────────────────
async function togglePaid(id) {
    if (!requireAuth()) return;
    try {
        const d = await api('toggle_paid', { id });
        if (d.success) {
            toast(d.message, 'success');
            await Promise.all([loadExpenses(), loadStats()]);
            loadCategorySummary();
            // Rafraîchir la vue paiements si active
            if (!document.getElementById('payments-tab').hidden) renderPayments();
        } else toast(d.message, 'error');
    } catch { toast('Erreur réseau', 'error'); }
}

async function deleteExpense(id) {
    if (!requireAuth()) return;
    if (!confirm('Supprimer cette dépense ? Cette action est irréversible.')) return;
    try {
        const d = await api('delete', { id });
        if (d.success) {
            toast(d.message, 'success');
            await Promise.all([loadExpenses(), loadStats()]);
            loadCategorySummary();
        } else toast(d.message, 'error');
    } catch { toast('Erreur réseau', 'error'); }
}

// ── Filtres ───────────────────────────────────────────────────
function applyFilters() {
    const f = state.filters;
    f.category = document.getElementById('filter-category')?.value || '';
    f.status   = document.getElementById('filter-status')?.value   || '';
    f.search   = (document.getElementById('filter-search')?.value  || '').toLowerCase().trim();
    f.min      = parseFloat(document.getElementById('filter-min')?.value)  || null;
    f.max      = parseFloat(document.getElementById('filter-max')?.value)  || null;

    state.filtered = state.expenses.filter(e => {
        const total = e.quantity * e.unit_price * e.frequency;
        if (f.category && e.category_id != f.category) return false;
        if (f.status === 'paid'   && e.paid != 1) return false;
        if (f.status === 'unpaid' && e.paid != 0) return false;
        if (f.search && !e.name.toLowerCase().includes(f.search)) return false;
        if (f.min !== null && total < f.min) return false;
        if (f.max !== null && total > f.max) return false;
        return true;
    });

    renderExpenses();
    updateFilterCount();
}

function resetFilters() {
    ['filter-category','filter-status','filter-search','filter-min','filter-max'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    state.filters = { category:'', status:'', search:'', min:null, max:null };
    state.filtered = [...state.expenses];
    renderExpenses();
    updateFilterCount();
}

function updateFilterCount() {
    const f   = state.filters;
    const cnt = [f.category, f.status, f.search, f.min!==null?'x':null, f.max!==null?'x':null].filter(Boolean).length;
    const el  = document.getElementById('filter-count');
    if (!el) return;
    el.textContent = cnt;
    el.style.display = cnt ? 'inline-flex' : 'none';

    const btn = document.getElementById('toggle-filters-btn');
    if (btn) btn.setAttribute('aria-expanded', cnt ? 'true' : 'false');
}

function updateFilterResults() {
    const el = document.getElementById('filter-results-text');
    if (!el) return;
    const total = state.expenses.length, filtered = state.filtered.length;
    el.innerHTML = filtered === total
        ? `Affichage de <strong>${total}</strong> dépense(s)`
        : `Affichage de <strong>${filtered}</strong> sur <strong>${total}</strong> dépense(s)`;
}

function toggleFilters() {
    const panel = document.getElementById('filters-panel');
    if (!panel) return;
    const open = panel.style.display === 'none';
    panel.style.display = open ? 'block' : 'none';
    document.getElementById('toggle-filters-btn')?.setAttribute('aria-expanded', open ? 'true' : 'false');
}

// ── Calcul temps réel dans le modal ──────────────────────────
function updateModalTotal() {
    const q   = parseFloat(document.getElementById('quantity')?.value) || 0;
    const pu  = parseFloat(document.getElementById('unit-price')?.value) || 0;
    const fr  = parseFloat(document.getElementById('frequency')?.value) || 0;
    const tot = q * pu * fr;
    const el  = document.getElementById('modal-total');
    const val = document.getElementById('modal-total-value');
    if (el) el.style.display = tot > 0 ? 'flex' : 'none';
    if (val) val.textContent = fc(tot);
}

// ── Nouvelle catégorie dans le modal ──────────────────────────
function handleCategoryChange() {
    const sel   = document.getElementById('category-select');
    const group = document.getElementById('new-category-group');
    const inp   = document.getElementById('new-category');
    if (!group || !inp) return;
    const isNew = sel.value === 'new';
    group.style.display = isNew ? 'block' : 'none';
    inp.required = isNew;
}

// ── Date du mariage ───────────────────────────────────────────
function openDateModal() {
    const inp = document.getElementById('wedding-date-input');
    if (inp && state.weddingDate)
        inp.value = state.weddingDate.toISOString().split('T')[0];
    updateDatePreview();
    showModal('wedding-date-modal');
}
function closeDateModal() { hideModal('wedding-date-modal'); }

function updateDatePreview() {
    const val = document.getElementById('wedding-date-input')?.value;
    if (!val) return;
    const date = new Date(val + 'T00:00:00');
    if (isNaN(date)) return;
    const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    document.getElementById('date-preview-text').textContent = date.toLocaleDateString('fr-FR', opts);
    const days = Math.floor((date - new Date()) / 86400000);
    const cdEl = document.getElementById('countdown-preview-text');
    let txt='', color='';
    if      (days > 30)  { txt=`${Math.floor(days/30)} mois ${days%30} jours`; color='#4caf50'; }
    else if (days > 0)   { txt=`${days} jour${days>1?'s':''}`; color='#ff9800'; }
    else if (days === 0) { txt='🎉 C\'est aujourd\'hui !'; color='#4caf50'; }
    else                 { txt='⚠️ Date passée'; color='#f44336'; }
    if (cdEl) { cdEl.textContent=txt; cdEl.style.color=color; }
}

async function saveWeddingDate() {
    const val = document.getElementById('wedding-date-input')?.value;
    if (!val) return toast('Sélectionnez une date', 'error');
    // FIX : on autorise aujourd'hui et le futur (cohérence avec api.php corrigé)
    const d     = new Date(val + 'T00:00:00');
    const today = new Date(); today.setHours(0,0,0,0);
    if (d < today) return toast("La date doit être aujourd'hui ou dans le futur", 'error');
    try {
        const r = await api('save_wedding_date', { body:{ date:val } });
        if (r.success) {
            state.weddingDate = d;
            renderWeddingBadge();
            startCountdown();
            closeDateModal();
            toast('Date enregistrée !', 'success');
        } else toast(r.message || 'Erreur', 'error');
    } catch { toast('Erreur réseau', 'error'); }
}

function renderWeddingBadge() {
    const ct = document.getElementById('wedding-date-container');
    if (!ct || !state.weddingDate) return;
    ct.innerHTML = `<div id="wedding-badge" class="wedding-badge">
        <span class="wb-icon"><i class="fas fa-heart"></i></span>
        <div class="wb-body">
            <span class="wb-label">🎊 Date du Mariage :</span>
            <span class="wb-date" id="wb-date"></span>
            <span class="wb-countdown" id="wb-countdown"></span>
        </div>
        <button class="wb-edit" onclick="openDateModal()" aria-label="Modifier la date"><i class="fas fa-pencil-alt"></i></button>
    </div>`;
    updateBadge();
}

function renderEmptyWeddingBadge() {
    const ct = document.getElementById('wedding-date-container');
    if (!ct) return;
    ct.innerHTML = `<div class="wedding-badge wedding-badge-empty" onclick="openDateModal()" style="cursor:pointer">
        <i class="fas fa-calendar-plus"></i>
        <span>Cliquez pour définir la date de votre mariage</span>
    </div>`;
}

function updateBadge() {
    if (!state.weddingDate) return;
    const dateEl = document.getElementById('wb-date');
    const cdEl   = document.getElementById('wb-countdown');
    if (!dateEl || !cdEl) return;

    const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
    dateEl.textContent = state.weddingDate.toLocaleDateString('fr-FR', opts);

    const ms   = state.weddingDate - new Date();
    if (ms <= 0) { cdEl.textContent='🎉 Jour J !'; cdEl.style.color='#4caf50'; return; }

    const days = Math.floor(ms/86400000);
    const hrs  = Math.floor((ms%86400000)/3600000);
    const min  = Math.floor((ms%3600000)/60000);
    let txt='', color='';
    if      (days > 30)  { txt=`${Math.floor(days/30)} mois ${days%30}j`; color='#4caf50'; }
    else if (days > 6)   { txt=`${days}j ${hrs}h`; color='#ff9800'; }
    else if (days > 0)   { txt=`${days}j ${hrs}h ${min}m`; color='#ff6b6b'; }
    else                 { txt=`${hrs}h ${min}m`; color='#ff6b6b'; }
    cdEl.textContent=txt; cdEl.style.color=color;
}

function startCountdown() {
    clearInterval(state.countdown);
    state.countdown = setInterval(updateBadge, 60000);
}

// ── Modals génériques ─────────────────────────────────────────
function showModal(id) {
    const m = document.getElementById(id);
    if (m) { m.style.display='flex'; m.focus?.(); document.body.style.overflow='hidden'; }
}
function hideModal(id) {
    const m = document.getElementById(id);
    if (m) { m.style.display='none'; document.body.style.overflow=''; }
}

// ── Fermer modals en cliquant dehors ──────────────────────────
document.addEventListener('click', e => {
    ['expense-modal','wedding-date-modal'].forEach(id => {
        const m = document.getElementById(id);
        if (m && e.target === m) hideModal(id);
    });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') ['expense-modal','wedding-date-modal'].forEach(hideModal);
});

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    // Authentification
    await checkAuth();

    // Chargement initial
    await Promise.all([loadCategories(), loadExpenses(), loadStats()]);
    loadCategorySummary();
    loadWeddingDate();

    // Écouter changements catégorie dans le modal
    document.getElementById('category-select')?.addEventListener('change', handleCategoryChange);

    // Calcul temps réel du montant dans le modal
    ['quantity','unit-price','frequency'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateModalTotal);
    });

    // Changement date dans modal date mariage
    document.getElementById('wedding-date-input')?.addEventListener('change', updateDatePreview);
});


// ── Export CSV / PDF ──────────────────────────────────────────
function exportData(format, type) {
    const url = `api/export_api.php?format=${encodeURIComponent(format)}&type=${encodeURIComponent(type)}`;
    if (format === 'csv') {
        const a = document.createElement('a');
        a.href = url;
        a.download = '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        toast('Export CSV en cours de téléchargement…', 'success');
    } else {
        window.open(url, '_blank');
        toast('Aperçu PDF ouvert dans un nouvel onglet', 'info');
    }
}

// ── Graphiques : initialisation ───────────────────────────────
function initCharts() {
    if (typeof Charts === 'undefined') return;
    const cats = state.categoriesStats;
    if (!cats || !cats.length) return;
    Charts.initAll(cats);
    if (typeof Charts.renderSummaryChart === 'function') {
        Charts.renderSummaryChart(cats);
    }
}
