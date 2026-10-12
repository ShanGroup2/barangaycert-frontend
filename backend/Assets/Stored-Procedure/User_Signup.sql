USE BeSCMS;
DROP PROCEDURE IF EXISTS User_Signup;
DELIMITER $$
CREATE PROCEDURE User_Signup(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255)
)
BEGIN
    DECLARE v_resident_id INT;
    DECLARE v_user_exists INT;
    DECLARE Record_Status VARCHAR(100);
    SELECT id INTO v_resident_id FROM residents WHERE full_name = p_full_name LIMIT 1;
    IF v_resident_id IS NULL THEN
        SET Record_Status = 'Error: Name not found, you need to register at barangay as resident!.';
    ELSE
        SELECT COUNT(*) INTO v_user_exists FROM users WHERE resident_id = v_resident_id;
        IF v_user_exists > 0 THEN
            SET Record_Status = 'Error: User already registered.';
        ELSE
            INSERT INTO users (resident_id, email, password, role, verification_status)
            VALUES (v_resident_id, p_email, p_password, 'resident', 'pending');
            SET Record_Status = 'Account created successfully. You may now log in.';
        END IF;
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;