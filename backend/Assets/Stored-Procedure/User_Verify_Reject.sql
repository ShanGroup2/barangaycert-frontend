USE BeSCMS;
DROP PROCEDURE IF EXISTS User_Verify_Reject;
DELIMITER $$
CREATE PROCEDURE User_Verify_Reject(IN p_user_id INT, IN p_reason TEXT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    UPDATE users SET verification_status = 'rejected', updated_at = NOW() WHERE id = p_user_id;
    SET Record_Status = CONCAT('Verification rejected. Reason: ', IFNULL(p_reason, 'Not provided.'));
    SELECT Record_Status;
END$$
DELIMITER ;