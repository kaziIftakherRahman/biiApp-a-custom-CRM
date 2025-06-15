<?php
session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit(); }
if ($_SESSION['role'] !== 'admin') { die("Access Denied: You do not have permission to view this page."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | biiApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css"/>

    <style>
        .stat-card { background-color: #1e293b; border: 1px solid #334155; border-radius: 0.5rem; padding: 1.5rem; text-align: center; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card.clickable:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1); cursor: pointer; }
        .stat-title { color: #94a3b8; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; }
        .stat-value { color: #e2e8f0; font-size: 2.25rem; font-weight: 700; letter-spacing: -0.025em; }
        .loader { font-size: 1.5rem; color: #64748b; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: #1e293b; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 600px; }
        /* Pagination Styles */
        .pagination-btn { padding: 8px 12px; border: 1px solid #334155; border-radius: 4px; background-color: #334155; cursor: pointer; margin: 0 2px; }
        .pagination-btn:hover:not(:disabled) { background-color: #475569; }
        .pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination-btn.active { background-color: #0ea5e9; border-color: #0ea5e9; }
    </style>
</head>
<body class="bg-slate-900 text-white p-4 lg:p-8">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8"><h1 class="text-3xl font-bold text-sky-400">Dashboard</h1><div class="text-right"><p class="text-lg">Welcome, <span class="font-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>!</p><a href="logout.php" class="text-sm text-red-400 hover:text-red-500">Logout</a></div></div>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8"><div class="stat-card"><div class="stat-title">Total Main Balance</div><div class="stat-value" id="total-balance"><span class="loader">...</span></div></div><div class="stat-card clickable" id="filter-cashin"><div class="stat-title">Total Money In</div><div class="stat-value text-sky-400" id="total-money-in"><span class="loader">...</span></div></div><div class="stat-card clickable" id="filter-cashout"><div class="stat-title">Total Money Out</div><div class="stat-value text-amber-400" id="total-money-out"><span class="loader">...</span></div></div><div class="stat-card"><div class="stat-title">Today's Net Change</div><div class="stat-value" id="today-net-change"><span class="loader">...</span></div></div><div class="stat-card clickable" id="manage-due-btn"><div class="stat-title">Total Due</div><div class="stat-value text-red-400" id="total-due"><span class="loader">...</span></div></div></div>
        <div class="bg-slate-800 p-4 rounded-lg flex items-center justify-center space-x-4 mb-8"><button id="addBalanceBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Add Balance (In)</button><button id="outBalanceBtn" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">Out Balance (Out)</button><button id="addDueBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Add Due</button></div>

        <div class="bg-slate-800 p-6 rounded-lg mb-8">
             <div class="flex flex-col md:flex-row justify-between md:items-center mb-4">
                <h2 id="transaction-title" class="text-2xl font-bold text-sky-400 mb-4 md:mb-0">All Transactions</h2>
                <div class="flex items-center space-x-2">
                    <input type="text" id="date-picker" placeholder="Filter by date" class="bg-slate-700 border border-slate-600 rounded px-3 py-2 w-64 text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <button id="reset-filter-btn" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded">Reset</button>
                </div>
            </div>
            <div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="border-b border-slate-600"><th class="p-3">Date</th><th class="p-3">Type</th><th class="p-3">Description</th><th class="p-3 text-right">Amount</th><th class="p-3">User</th></tr></thead><tbody id="transaction-table-body"></tbody></table></div>
             <div id="pagination-controls" class="flex justify-center items-center mt-6"></div>
        </div>
        
        <div class="bg-slate-800 p-6 rounded-lg"><h2 class="text-2xl font-bold text-sky-400 mb-4">"Admin Rifat" Transactions</h2><div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="border-b border-slate-600"><th class="p-3">Date</th><th class="p-3">Type</th><th class="p-3 text-right">Amount</th></tr></thead><tbody id="rifat-table-body"></tbody></table></div></div>
    </div>

    <div id="due-modal" class="modal-overlay hidden"><div class="modal-content"><div class="flex justify-between items-center mb-4"><h3 class="text-2xl font-bold text-sky-400">Manage Outstanding Dues</h3><button id="close-due-modal" class="text-3xl text-slate-400 hover:text-white">&times;</button></div><div class="overflow-y-auto max-h-96"><table class="w-full text-left"><thead><tr class="border-b border-slate-600"><th class="p-3">Company</th><th class="p-3 text-right">Amount Due</th><th class="p-3 text-center">Action</th></tr></thead><tbody id="due-table-body"></tbody></table></div></div></div>

<script>
$(document).ready(function() {
    // --- STATE MANAGEMENT ---
    let currentFilters = {}; // Store current filters for pagination

    // --- UTILITY FUNCTIONS ---
    const formatCurrency = (num) => parseFloat(num).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    const formatDate = (dateString) => new Date(dateString).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short', hour12: true });

    // --- DATA FETCHING & RENDERING ---
    function loadAllData() { loadStats(); fetchTransactions(1, {}); loadRifatTransactions(); }

    function loadStats() { $.getJSON("dashboard_logic.php?action=get_stats", function(data) { $('#total-balance').text(formatCurrency(data.totalBalance)); $('#total-money-in').text(formatCurrency(data.totalMoneyIn)); $('#total-money-out').text(formatCurrency(data.totalMoneyOut)); $('#total-due').text(formatCurrency(data.totalDue)); const netChange = parseFloat(data.todayNetChange); $('#today-net-change').text(formatCurrency(netChange)).removeClass('text-green-400 text-red-400'); if (netChange > 0) $('#today-net-change').addClass('text-green-400'); else if (netChange < 0) $('#today-net-change').addClass('text-red-400'); }); }

    // FETCH TRANSACTIONS IS UPDATED TO HANDLE THE NEW RESPONSE
    function fetchTransactions(page, filters) {
        currentFilters = filters; // Update global filters
        $('#transaction-table-body').html('<tr><td colspan="5" class="text-center p-8"><span class="loader">Loading...</span></td></tr>');
        
        const params = { action: 'get_transactions', page: page, ...filters };
        $.getJSON("dashboard_logic.php", params, function(response) {
            const tableBody = $('#transaction-table-body');
            tableBody.empty();
            
            if (response.transactions && response.transactions.length > 0) {
                response.transactions.forEach(tx => {
                    let amountClass = '', amountPrefix = '', typeClass = 'bg-slate-500/20 text-slate-300';
                    if (['sale', 'manual_cash_in', 'due_cleared'].includes(tx.type.trim())) { amountClass = 'text-green-400'; amountPrefix = '+'; typeClass = 'bg-green-500/20 text-green-300'; }
                    if (tx.type.trim() === 'manual_cash_out') { amountClass = 'text-red-400'; amountPrefix = '-'; typeClass = 'bg-yellow-500/20 text-yellow-300'; }
                    tableBody.append(`<tr class="border-b border-slate-700 hover:bg-slate-700/50"><td class="p-3">${formatDate(tx.created_at)}</td><td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ${typeClass}">${tx.type.replace(/_/g, ' ')}</span></td><td class="p-3 text-slate-400">${tx.description || ''}</td><td class="p-3 text-right font-mono ${amountClass}">${amountPrefix}${formatCurrency(tx.amount)}</td><td class="p-3">${tx.user_id}</td></tr>`);
                });
            } else {
                tableBody.html('<tr><td colspan="5" class="text-center p-8">No transactions found.</td></tr>');
            }
            renderPagination(response.pagination);
        });
    }

    // NEW FUNCTION TO RENDER PAGINATION CONTROLS
    function renderPagination(pagination) {
        const { currentPage, totalPages } = pagination;
        const container = $('#pagination-controls');
        container.empty();
        if (totalPages <= 1) return;

        let buttons = '';
        // Previous Button
        buttons += `<button class="pagination-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>&laquo; Prev</button>`;
        // Page Number Buttons
        for (let i = 1; i <= totalPages; i++) {
            buttons += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        // Next Button
        buttons += `<button class="pagination-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>Next &raquo;</button>`;
        container.html(buttons);
    }
    
    // Unchanged functions
    function loadRifatTransactions() { $.getJSON("dashboard_logic.php?action=get_rifat_transactions", function(data) { const tableBody = $('#rifat-table-body'); tableBody.empty(); if (data.length === 0) { tableBody.html('<tr><td colspan="3" class="text-center p-8">No transactions for rifat25.</td></tr>'); return; } data.forEach(tx => { let amountClass = tx.type === 'manual_cash_in' ? 'text-green-400' : 'text-red-400'; let amountPrefix = tx.type === 'manual_cash_in' ? '+' : '-'; tableBody.append(`<tr class="border-b border-slate-700 hover:bg-slate-700/50"><td class="p-3">${formatDate(tx.created_at)}</td><td class="p-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ${tx.type === 'manual_cash_in' ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'}">${tx.type.replace(/_/g, ' ')}</span></td><td class="p-3 text-right font-mono ${amountClass}">${amountPrefix}${formatCurrency(tx.amount)}</td></tr>`); }); }); }
    function handlePostAction(url, postData) { $.post(url, postData, (res) => { if(res.success) { alert(res.message); loadAllData(); } else { alert("Error: " + res.message); } }, 'json').fail(() => alert("A critical error occurred.")); }

    // --- EVENT HANDLERS ---
    const picker = new Litepicker({ element: document.getElementById('date-picker'), singleMode: false, format: 'YYYY-MM-DD', setup: (picker) => { picker.on('selected', (d1, d2) => { $('#transaction-title').text(`Transactions from ${d1.format('MMM D')} to ${d2.format('MMM D')}`); fetchTransactions(1, { start_date: d1.format('YYYY-MM-DD'), end_date: d2.format('YYYY-MM-DD') }); }); } });
    
    // UPDATED to call fetchTransactions with page 1
    $('#reset-filter-btn').on('click', () => { $('#transaction-title').text('All Transactions'); picker.clearSelection(); fetchTransactions(1, {}); });
    $('#filter-cashin').on('click', () => { $('#transaction-title').text('Money In Transactions'); fetchTransactions(1, { filter_type: 'cash_in' }); });
    $('#filter-cashout').on('click', () => { $('#transaction-title').text('Money Out Transactions'); fetchTransactions(1, { filter_type: 'cash_out' }); });
    
    // NEW PAGINATION CLICK HANDLER
    $('#pagination-controls').on('click', 'button:not(:disabled)', function() {
        const page = $(this).data('page');
        fetchTransactions(page, currentFilters);
    });
    
    // Other handlers are unchanged
    $('#addBalanceBtn').on('click', () => { const amount = prompt("Amount to ADD:"); if (amount && !isNaN(amount) && amount > 0) handlePostAction("dashboard_logic.php?action=add_balance", { amount }); });
    $('#outBalanceBtn').on('click', () => { const amount = prompt("Amount to REMOVE:"); if (amount && !isNaN(amount) && amount > 0) handlePostAction("dashboard_logic.php?action=out_balance", { amount }); });
    $('#addDueBtn').on('click', () => { const company = prompt("Company name for due:"); if (company) { const amount = prompt(`Due amount for ${company}:`); if (amount && !isNaN(amount) && amount > 0) handlePostAction("dashboard_logic.php?action=add_due", { company, amount }); } });
    $('#manage-due-btn').on('click', function() { $('#due-table-body').html('<tr><td colspan="3" class="text-center p-8"><span class="loader">Loading dues...</span></td></tr>'); $('#due-modal').removeClass('hidden'); $.getJSON("dashboard_logic.php?action=get_outstanding_dues", function(data) { const dueBody = $('#due-table-body'); dueBody.empty(); if(data.length === 0) { dueBody.html('<tr><td colspan="3" class="text-center p-8">No outstanding dues found.</td></tr>'); return; } data.forEach(due => { dueBody.append(`<tr class="border-b border-slate-700"><td class="p-3 font-semibold">${due.company_name}</td><td class="p-3 text-right font-mono">${formatCurrency(due.due_amount)}</td><td class="p-3 text-center"><button class="pay-due-btn bg-sky-600 hover:bg-sky-700 text-white font-bold py-1 px-3 rounded text-sm" data-due-id="${due.id}" data-due-name="${due.company_name}" data-due-amount="${due.due_amount}">Pay</button></td></tr>`); }); }); });
    $('#close-due-modal').on('click', () => $('#due-modal').addClass('hidden'));
    $('#due-table-body').on('click', '.pay-due-btn', function() { const dueId = $(this).data('due-id'); const dueName = $(this).data('due-name'); const maxAmount = parseFloat($(this).data('due-amount')); const paymentAmount = prompt(`Enter payment amount for ${dueName} (Max: ${maxAmount.toFixed(2)}):`); if (paymentAmount && !isNaN(paymentAmount) && paymentAmount > 0) { if(paymentAmount > maxAmount) { alert('Payment amount cannot be greater than the due amount.'); return; } handlePostAction("dashboard_logic.php?action=pay_due", { due_id: dueId, amount: paymentAmount }); $('#due-modal').addClass('hidden'); } });

    // --- INITIAL LOAD ---
    loadAllData();
});
</script>

</body>
</html>