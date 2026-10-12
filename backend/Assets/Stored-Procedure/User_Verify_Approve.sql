USE BeSCMS;
DROP PROCEDURE IF EXISTS User_Verify_Approve;
DELIMITER $$
CREATE PROCEDURE User_Verify_Approve(IN p_user_id INT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    UPDATE users SET verification_status = 'approved', updated_at = NOW() WHERE id = p_user_id;
    SET Record_Status = 'Account verified.';
    SELECT Record_Status;
END$$
DELIMITER ;