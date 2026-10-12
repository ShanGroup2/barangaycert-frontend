USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_Delete;
DELIMITER $$
CREATE PROCEDURE Resident_Delete(IN p_id INT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    DECLARE v_user_exists INT;
    SELECT COUNT(*) INTO v_user_exists FROM users WHERE resident_id = p_id;
    IF v_user_exists > 0 THEN
        SET Record_Status = 'Cannot delete resident with linked user account.';
    ELSE
        DELETE FROM residents WHERE id = p_id;
        SET Record_Status = 'Resident deleted.';
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;