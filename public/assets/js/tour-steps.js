/**
 * Guided tour content: what each page's buttons are for. Read by assets/js/tour.js.
 *
 * Each page key is the last two parts of its address (e.g. "manage/requests"). A step is:
 *   sel      CSS selector of what to point at (the first visible match)
 *   has      optional: only a match whose text contains this (e.g. a heading)
 *   closest  optional: point at this parent of the match instead (e.g. the whole card)
 *   title    short name of the thing
 *   text     what it is for, in plain words
 *   side     optional: 'top' | 'bottom' | 'left' | 'right' (default: wherever there is room)
 *   desktop  optional: true = skip on a phone, where the menu is closed
 * A step whose element is not on the page (for example a button that only shows when there are requests) is skipped.
 * To change the wording of a step, edit it here. To add a page, add a new key.
 */
window.CE_TOURS = (function () {
    var shell = [
        { sel: '.mobile-menu-btn', mobile: true, title: 'Menu button', text: 'Opens the list of pages. Press it, pick a page, and the menu closes by itself.' },
        { sel: '.sidebar .nav-section', desktop: true, side: 'right', title: 'Menu', text: 'All your pages, grouped by job. The highlighted one is the page you are on. Press any name to go there.' },
        { sel: '#searchInput', title: 'Search', text: 'Type here to narrow down the list on the page you are looking at. Clear it to see everything again.' },
        { sel: '#bellBtn', side: 'bottom', title: 'Notifications', text: 'A number appears when something new happens for you, such as a request being approved or declined. Press it to read.' },
        { sel: '#tourBtn', side: 'bottom', title: 'Page tour', text: 'Press this question mark on any page to see what that page\'s buttons do.' },
        { sel: '.sidebar-account .account-card', desktop: true, side: 'right', title: 'Your account', text: 'Your name and your role. Your role decides which pages you can use.' },
        { sel: '.logout-btn', desktop: true, side: 'right', title: 'Log out', text: 'Signs you out. You are asked to confirm first. Always log out on a shared computer.' }
    ];

    var pages = {
        /* ---------------- Borrower ---------------- */
        'borrower/dashboard': [
            { sel: '.section-title', has: 'Penalties', closest: '.card-container', title: 'Penalties & fines', text: 'What you owe, if anything. Late returns, damage and missing accessories can add fines. Keep it clear by returning items on time.' },
            { sel: '.section-title', has: 'Pending Requests', closest: '.card-container', title: 'Pending requests', text: 'Requests the staff have not decided yet. You get a notification when each one is approved or declined.' },
            { sel: '.section-title', has: 'Approved Requests', closest: '.card-container', title: 'Approved, ready at the lab desk', text: 'Go to the equipment desk with your Student ID to collect these before the pick-up window ends.' },
            { sel: '.section-title', has: 'Currently Possessed', closest: '.card-container', title: 'Items you have now', text: 'Everything you are holding, with a countdown to its due date. Return it to the desk before that time.' },
            { sel: '.section-title', has: 'Available Equipment', closest: '.card-container', title: 'Available equipment', text: 'A quick look at what you could reserve right now. The Equipment Catalog in the menu shows everything.' }
        ],
        'borrower/catalog': [
            { sel: '#catalog-grid', title: 'Equipment', text: 'Each card is one kind of equipment. Press a card\'s buttons to reserve it or save it for later.' },
            { sel: '.catalog-stock-badge', has: 'available', title: 'Available stock right now', text: 'The number of units you can reserve right now, for example "2 Available". An item marked "Out of Stock" has none free: save it and check back later.' },
            { sel: '#catalog-grid .btn-primary', title: 'Reserve', text: 'Opens the request form: choose your dates and write what the equipment is for. The staff approve or decline it.' },
            { sel: '#catalog-pager', title: 'More pages', text: 'Swipe the list sideways, or use these buttons, to see more equipment.' }
        ],
        'borrower/my_requests': [
            { sel: '#tab-pending', title: 'Pending', text: 'Requests still waiting for a decision, or approved and waiting for you to collect.' },
            { sel: '.loan-card .btn-outline-danger', title: 'Cancel request', text: 'Changed your mind? Cancel a request while it is still waiting. You are asked to confirm.' },
            { sel: '#tab-borrowed', title: 'Borrowed', text: 'Items you have collected and not returned yet, with their due dates.' },
            { sel: '#tab-history', title: 'History', text: 'Everything finished: returned, declined or cancelled.' }
        ],
        'borrower/wishlist': [
            { sel: '#saved-available', title: 'Ready to reserve', text: 'Saved items that have units free. Press Reserve on a card to ask for it.' },
            { sel: '#saved-unavailable', title: 'Not available now', text: 'Saved items you cannot reserve yet, with the reason: all borrowed, in maintenance, or no longer offered.' },
            { sel: '#select-btn', title: 'Select items', text: 'Tick several saved items to remove them together.' },
            { sel: '#clear-all-btn', title: 'Clear all', text: 'Empties your whole saved list. You are asked to confirm.' }
        ],
        'borrower/fines': [
            { sel: '#accruing-list', title: 'Still building up', text: 'Fines that grow each day because an item is overdue. Returning the item stops them.' },
            { sel: '#charged-list', title: 'Charged', text: 'Fines the staff have recorded, with the reason. Pay at the equipment desk and the staff mark it paid.' }
        ],

        /* ---------------- Staff and administrator ---------------- */
        'manage/dashboard': [
            { sel: '.stats-grid', title: 'Numbers at a glance', text: 'Pending requests, items out and more. Press a tile to open the page behind it.' },
            { sel: '#dashboard-requests-container', closest: '.card-container', title: 'Pending requests', text: 'Requests waiting for a decision. Work from the top: the most urgent come first.' },
            { sel: '#dashboard-requests-container .btn-primary', title: 'Review & Assign', text: 'Choose which physical unit goes to the borrower, then approve. Or decline it with a reason.' },
            { sel: '.section-title', has: 'Equipment Availability', closest: '.card-container', title: 'Availability', text: 'How many units are free, borrowed or in maintenance, model by model.' },
            { sel: '.section-title', has: 'Recent Logs', closest: '.card-container', title: 'Recent logs', text: 'The latest actions in the system. The full record is in Transaction Logs.' }
        ],
        'manage/requests': [
            { sel: '.requests-toolbar .filter-group', title: 'Filters', text: 'Show only urgent requests, this week\'s, or later ones.' },
            { sel: '#requests-table', closest: '.table-card', title: 'Requests', text: 'Who asked for what, and when. The table scrolls sideways if it is wide.' },
            { sel: '.btn-approve', title: 'Approve', text: 'Opens a window to choose which physical unit (by serial number) goes to this borrower. The borrower is notified.' },
            { sel: '.btn-decline', title: 'Decline', text: 'Refuses the request. You must give a reason, and the borrower sees it.' },
            { sel: '#requests-pager', title: 'Pages', text: 'Move between pages here, or swipe the table once it is at its end.' }
        ],
        'manage/checkouts': [
            { sel: '#handover-container', title: 'Ready for handover', text: 'Approved requests whose borrowers are coming to collect.' },
            { sel: '[data-handover]', title: 'Hand Over', text: 'Check the borrower\'s Student ID, then confirm. This starts the loan and sets the due date.' },
            { sel: '.requests-toolbar .filter-group', title: 'Filters', text: 'Show overdue returns, returns due today, or those on time.' },
            { sel: '#returns-container', title: 'Active returns', text: 'Items that are currently out, with their due dates.' },
            { sel: '[data-return]', title: 'Process Return', text: 'Inspect the item and record its condition: good, damaged, or missing accessories. Late returns are fined.' }
        ],
        'staff/handover': [
            { sel: '.section-title', has: 'Ready for Handover', closest: '.card-container', title: 'Ready for handover', text: 'Approved requests whose borrowers are coming to collect.' },
            { sel: '[data-handover]', title: 'Hand Over', text: 'Check the borrower\'s Student ID against what you see here, then confirm. This starts the loan and sets the due date.' }
        ],
        'staff/return': [
            { sel: '.section-title', has: 'Active Returns', closest: '.card-container', title: 'Active returns', text: 'Items that are out. Overdue ones are marked.' },
            { sel: '[data-return]', title: 'Process Return', text: 'Inspect the item and record its condition: good, damaged, or missing accessories. Late returns are fined.' }
        ],
        'manage/fines': [
            { sel: '[data-open-modal="chargeModal"]', title: 'Charge Fine', text: 'Add a fine for damage, missing accessories or something else. Choose the loan, the amount and the reason.' },
            { sel: '.requests-toolbar .filter-group', title: 'Filters', text: 'Show all fines, or only paid, unpaid or waived ones.' },
            { sel: '.pay-btn', title: 'Mark Paid', text: 'Press when the borrower has paid at the desk. The fine changes to Paid.' },
            { sel: '.waive-btn', title: 'Waive', text: 'Cancels a fine that should not be collected. It stays in the logs.' }
        ],
        'manage/catalog': [
            { sel: '[data-open-modal="categoryModal"]', title: 'Add Category', text: 'Creates a new group for equipment, such as Laptops or Cameras.' },
            { sel: '[data-open-modal="addModal"]', title: 'Add New Item', text: 'Creates a new equipment model that borrowers can reserve. Physical units are added afterwards.' },
            { sel: '.requests-toolbar .filter-group', title: 'Filters', text: 'Show active, available or archived models.' },
            { sel: '.catalog-item-card', title: 'An equipment model', text: 'Its details and how many units are free. The buttons on it change or manage this model.' },
            { sel: '.btn-reveal[href*="serialized"]', title: 'View Units', text: 'Shows the physical units (serial numbers) of this model.' },
            { sel: '[data-catalog-action="accessories"]', title: 'Accessories', text: 'The parts that come with the item, such as a charger or a bag. They are checked on return.' },
            { sel: '[data-catalog-action="archive"]', title: 'Archive', text: 'Stops a model being offered without deleting its history. You can restore it later.' }
        ],
        'manage/serialized': [
            { sel: '[data-open-modal="addUnitModal"]', title: 'Register New Unit', text: 'Adds one physical item, with its serial number, to a model.' },
            { sel: '#model-filter', title: 'Choose a model', text: 'Show the units of one model only.' },
            { sel: '.table-card', title: 'Units', text: 'Each physical item and its status. Edit a unit to set it to Available, Maintenance or Retired.' }
        ],
        'manage/logs': [
            { sel: '.requests-toolbar .filter-group', title: 'Filter by action', text: 'Show only requests, approvals, handovers, returns or fines.' },
            { sel: '.table-card', title: 'The log', text: 'A permanent record of every action. It cannot be edited. Swipe sideways to read every column.' },
            { sel: '#logs-pager', title: 'Pages', text: 'Once the table is at its end, make a new swipe, or use these buttons, to change page.' }
        ],
        'admin/users': [
            { sel: '.requests-toolbar .filter-group', title: 'Filter by role', text: 'Show everyone, or only borrowers, staff or administrators.' },
            { sel: '#users-tbody', closest: '.table-card', title: 'Users', text: 'Everyone with an account. Edit a person to change their role and, for staff, which jobs they may do.' },
            { sel: '#users-tbody button, #users-tbody .btn', title: 'Edit', text: 'Opens the user\'s details: role and permissions. Changes apply the next time they open a page.' }
        ],
        'admin/export': [
            { sel: '.export-range', title: 'Date range', text: 'Choose which dates to include. The table below updates by itself.' },
            { sel: '#f-borrower', title: 'Borrower', text: 'Limit the report to one borrower.' },
            { sel: '#f-equipment', title: 'Equipment', text: 'Limit the report to one equipment model.' },
            { sel: '.btn', has: 'Print', title: 'Print', text: 'Opens a clean printable report of everything that matches your filters.' },
            { sel: '.btn', has: 'Export to Excel', title: 'Export to Excel', text: 'Downloads everything that matches your filters as a spreadsheet, not only the page you see.' }
        ],
        'admin/settings': [
            { sel: '.section-title', has: 'Announcement', closest: '.card-container', title: 'Announcement & desk info', text: 'The banner messages for borrowers and staff, and the desk hours and contact shown on the Sign In page.' },
            { sel: '.section-title', has: 'Maintenance mode', closest: '.card-container', title: 'Maintenance mode', text: 'Pauses new reservations and shows your message. Turn it on before you do inventory or repairs.' },
            { sel: '#settings-save-btn', title: 'Save changes', text: 'Nothing is saved until you press this.' }
        ]
    };

    return { shell: shell, pages: pages };
})();
