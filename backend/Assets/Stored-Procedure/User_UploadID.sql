USE BeSCMS;
DROP PROCEDURE IF EXISTS User_UploadID;
DELIMITER $$
CREATE PROCEDURE User_UploadID(IN p_user_id INT, IN p_filename VARCHAR(255))
BEGIN
    DECLARE Record_Status VARCHAR(100);
    UPDATE users SET verification_doc = p_filename, verification_status = 'pending' WHERE id = p_user_id;
    SET Record_Status = 'ID uploaded. Pending admin verification.';
    SELECT Record_Status;
END$$
DELIMITER ;