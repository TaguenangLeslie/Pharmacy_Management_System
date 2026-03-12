<?php
/**
 * Language Translations (English/French)
 */

$translations = [
    'en' => [
        'dashboard' => 'Dashboard',
        'inventory' => 'Inventory',
        'pos' => 'Point of Sale',
        'suppliers' => 'Suppliers',
        'reports' => 'Reports',
        'users' => 'Users',
        'settings' => 'Settings',
        'customers' => 'Customers',
        'expenses' => 'Expenses',
        'prescriptions' => 'Prescriptions',
        'audit_logs' => 'Audit Logs',
        'logout' => 'Logout',
        'welcome' => 'Welcome Back',
        'today_sales' => "Today's Sales",
        'low_stock' => 'Low Stock Alert',
        'expiring_soon' => 'Expiring Soon',
        'total_medicines' => 'Total Medicines',
        'recent_sales' => 'Recent Sales',
        'medicine_name' => 'Medicine Name',
        'generic_name' => 'Generic Name',
        'category' => 'Category',
        'price' => 'Price',
        'stock' => 'Stock',
        'expiry' => 'Expiry',
        'actions' => 'Actions',
        'confirm_sale' => 'Confirm Sale',
        'total' => 'Total',
        'add_medicine' => 'Add New Medicine',
        'medicine_inventory' => 'Medicine Inventory',
        'payment_method' => 'Payment Method',
        'grand_total' => 'Grand Total'
    ],
    'fr' => [
        'dashboard' => 'Tableau de bord',
        'inventory' => 'Inventaire',
        'pos' => 'Point de vente',
        'suppliers' => 'Fournisseurs',
        'reports' => 'Rapports',
        'users' => 'Utilisateurs',
        'settings' => 'Paramètres',
        'customers' => 'Clients',
        'expenses' => 'Dépenses',
        'prescriptions' => 'Ordonnances',
        'audit_logs' => "Journaux d'Audit",
        'logout' => 'Déconnexion',
        'welcome' => 'Bienvenue',
        'today_sales' => "Ventes d'aujourd'hui",
        'low_stock' => 'Alerte stock bas',
        'expiring_soon' => 'Expire bientôt',
        'total_medicines' => 'Total des médicaments',
        'recent_sales' => 'Ventes récentes',
        'medicine_name' => 'Nom du médicament',
        'generic_name' => 'Nom générique',
        'category' => 'Catégories',
        'price' => 'Prix',
        'stock' => 'Stock',
        'expiry' => 'Expiration',
        'actions' => 'Actions',
        'confirm_sale' => 'Confirmer la vente',
        'total' => 'Total',
        'add_medicine' => 'Ajouter un nouveau médicament',
        'medicine_inventory' => 'Inventaire des médicaments',
        'payment_method' => 'Mode de paiement',
        'grand_total' => 'Total général'
    ]
];

/**
 * Get translation for a key
 */
function __($key) {
    global $translations;
    $lang = $_SESSION['lang'] ?? 'en';
    return $translations[$lang][$key] ?? $key;
}
