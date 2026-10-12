USE BeSCMS;
DROP PROCEDURE IF EXISTS User_Login;
DELIMITER $$
CREATE PROCEDURE User_Login(IN p_email VARCHAR(255), IN p_password VARCHAR(255))
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_role VARCHAR(20);
    DECLARE v_verification_status VARCHAR(20);
    DECLARE v_password_hash VARCHAR(255);
    SELECT id, role, verification_status, password
    INTO v_user_id, v_role, v_verification_status, v_password_hash
    FROM users WHERE email = p_email;
    IF v_user_id IS NOT NULL AND p_password = v_password_hash THEN
        SELECT v_user_id AS user_id, v_role AS role, v_verification_status AS verification_status;
    ELSE
        SELECT 'Invalid email or password' AS error;
    END IF;
END$$
DELIMITER ;