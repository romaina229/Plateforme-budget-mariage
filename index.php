<?php
session_start();
require_once 'auth/AuthManager.php';

$isLoggedIn = AuthManager::isLoggedIn();
$currentUser = $isLoggedIn ? AuthManager::getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/logo.png" type="image/png">
    <title>Budget Mariage - Réussir l'organisation de son mariage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="app-container">   
        <div id="wedding-date-container"></div><br>
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active fade-in">
            <div class="stats-grid" id="stats-grid">
                <!-- Stats will be loaded here -->
            </div>

            <div class="table-container">
                <div class="progress-container" id="progress-container">
                    <!-- Progress bar will be loaded here -->
                </div>

                <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 20px; color: var(--primary);">
                    Récapitulatif par Catégorie
                </h2>
                <div class="table-responsive">
                    <table id="category-summary-table">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th style="text-align: right;">Montant Total</th>
                                <th style="text-align: right;">Montant Payé</th>
                                <th style="text-align: right;">Reste</th>
                                <th style="text-align: center;">Statut</th>
                            </tr>
                        </thead>
                        <tbody id="category-summary-body">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Details Tab -->
        <div id="details-tab" class="tab-content">
            <div class="filters-section">
                <div class="filters-header">
                    <button class="btn btn-primary" onclick="openModal()">
                        + Ajouter une Dépense
                    </button>
                    <button class="btn btn-secondary" onclick="toggleFilters()">
                        🔍 Filtres <span id="filter-count"></span>
                    </button>
                </div>
                
                <div id="filters-panel" class="filters-panel" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Filtrer par Catégorie</label>
                            <select id="filter-category" onchange="applyFilters()">
                                <option value="">Toutes les catégories</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Filtrer par Statut</label>
                            <select id="filter-status" onchange="applyFilters()">
                                <option value="">Tous les statuts</option>
                                <option value="paid">Payé</option>
                                <option value="unpaid">Non payé</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rechercher</label>
                            <input type="text" id="filter-search" placeholder="Nom de la dépense..." oninput="applyFilters()">
                        </div>
                        <div class="form-group">
                            <label>Prix Min (FCFA)</label>
                            <input type="number" id="filter-min" placeholder="0" oninput="applyFilters()">
                        </div>
                        <div class="form-group">
                            <label>Prix Max (FCFA)</label>
                            <input type="number" id="filter-max" placeholder="1000000" oninput="applyFilters()">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button class="btn btn-warning" onclick="resetFilters()" style="width: 100%;">
                                ↻ Réinitialiser
                            </button>
                        </div>
                    </div>
                    <div class="filter-results">
                        <span id="filter-results-text"></span>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 20px; color: var(--primary);">
                    Détail des Dépenses
                </h2>
                <div class="table-responsive">
                    <table id="expenses-table">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Nature des dépenses</th>
                                <th style="text-align: center;">Quantité</th>
                                <th style="text-align: right;">CU</th>
                                <th style="text-align: center;">Fréquence</th>
                                <th style="text-align: right;">Montant</th>
                                <th style="text-align: center;">Statut</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="expenses-body">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Tab -->
        <div id="payments-tab" class="tab-content">
            <div class="table-container">
                <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 20px; color: var(--primary);">
                    Éléments Payés
                </h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Nom</th>
                                <th style="text-align: center;">Quantité</th>
                                <th style="text-align: right;">Prix Unitaire</th>
                                <th style="text-align: right;">Montant Total</th>
                                <th style="text-align: center;">Date Paiement</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="paid-expenses-body">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-container" style="margin-top: 30px;">
                <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 20px; color: var(--warning);">
                    Éléments Non Payés
                </h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Nom</th>
                                <th style="text-align: center;">Quantité</th>
                                <th style="text-align: right;">Prix Unitaire</th>
                                <th style="text-align: right;">Montant Total</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="unpaid-expenses-body">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="expense-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Nouvelle Dépense</h2>
                <button class="btn btn-sm btn-danger" onclick="closeModal()">✕</button>
            </div>
            <form id="expense-form" onsubmit="handleSubmit(event)">
                <input type="hidden" id="expense-id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Catégorie *</label>
                        <select id="category-select" required>
                            <option value="">Sélectionner...</option>
                        </select>
                    </div>
                    <div class="form-group" id="new-category-group" style="display: none;">
                        <label>Nouvelle Catégorie *</label>
                        <input type="text" id="new-category" placeholder="Nom de la catégorie">
                    </div>
                    <div class="form-group">
                        <label>Nom de la dépense *</label>
                        <input type="text" id="expense-name" required>
                    </div>
                    <div class="form-group">
                        <label>Quantité *</label>
                        <input type="number" id="quantity" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Prix Unitaire (FCFA) *</label>
                        <input type="number" id="unit-price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Fréquence *</label>
                        <input type="number" id="frequency" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label>Date de paiement</label>
                        <input type="date" id="payment-date">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <input type="text" id="notes" placeholder="Notes optionnelles">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="paid" style="margin-right: 8px;">
                            Déjà payé
                        </label>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <span id="submit-btn-text">Ajouter</span>
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <?php include 'includes/footer.php'; ?>