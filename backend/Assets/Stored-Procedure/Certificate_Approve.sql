USE BeSCMS;
DROP PROCEDURE IF EXISTS Certificate_Approve;
DELIMITER $$
CREATE PROCEDURE Certificate_Approve(IN p_request_id INT, IN p_admin_notes TEXT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    IF EXISTS(SELECT 1 FROM certificate_requests WHERE id = p_request_id AND status = 'pending') THEN
        UPDATE certificate_requests SET status = 'approved', admin_notes = p_admin_notes,
               approved_at = NOW() WHERE id = p_request_id;
        SET Record_Status = 'Request approved. Certificate ready to print.';
    ELSE
        SET Record_Status = 'Error: Request not pending.';
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;