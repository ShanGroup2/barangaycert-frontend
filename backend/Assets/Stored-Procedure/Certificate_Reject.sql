USE BeSCMS;
DROP PROCEDURE IF EXISTS Certificate_Reject;
DELIMITER $$
CREATE PROCEDURE Certificate_Reject(IN p_request_id INT, IN p_admin_notes TEXT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    IF EXISTS(SELECT 1 FROM certificate_requests WHERE id = p_request_id AND status = 'pending') THEN
        UPDATE certificate_requests SET status = 'rejected', admin_notes = p_admin_notes,
               rejected_at = NOW() WHERE id = p_request_id;
        SET Record_Status = 'Request rejected.';
    ELSE
        SET Record_Status = 'Error: Request not pending.';
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;