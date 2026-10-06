INSERT INTO categories() VALUES(category_name);

INSERT INTO checkouts(
    reservation_id, 
    item_id, 
    issued_by, 
    id_verified, 
    checked_out_at, 
    due_at, 
    returned_at, 
    received_by, 
    return_condition, 
    damage_notes, 
    inspection_remarks) 
    VALUES();
INSERT INTO equipment_catalog(
    model_name, 
    category_id, 
    max_loan_days, 
    late_fine_rate, 
    description, 
    is_archived, 
    created_at) 
    VALUES();

INSERT INTO fines(
    reservation_id, 
    amount_due, 
    payment_status, 
    paid_at) 
    VALUES();

INSERT INTO reservations(
    catalog_id, 
    item_id, 
    borrower_id, 
    status, 
    purpose, 
    start_at, 
    end_at, 
    pickup_at, 
    decline_reason, 
    terms_accepted, 
    requested_at, 
    reviewed_by, 
    reviewed_at) 
    VALUES();

INSERT INTO serialized_items(
    catalog_id, 
    serial_number, 
    status, 
    storage_location, 
    condition_notes, 
    created_at) 
    VALUES();

INSERT INTO staff_permissions(
    user_id, 
    can_approve_requests, 
    can_inspect_returns, 
    can_confirm_pickup, 
    can_manage_catalog, 
    granted_by, 
    updated_at) 
    VALUES();

INSERT INTO transaction_logs(
    reservation_id,
    item_id,
    fine_id,
    actor_id,
    action_type,
    remarks,
    serial_snapshot,
    action_timestamp ) 
    VALUES();

INSERT INTO users(
    last_name, 
    first_name, 
    middle_name, 
    email, 
    password_hash, 
    role, 
    id_number, 
    phone, 
    created_at) 
    VALUES();

INSERT INTO wishlist_items(
    user_id, 
    catalog_id, 
    created_at) 
    VALUES();
 