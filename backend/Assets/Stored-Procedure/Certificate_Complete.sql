USE BeSCMS;
DROP PROCEDURE IF EXISTS Certificate_Complete;
DELIMITER $$
CREATE PROCEDURE Certificate_Complete(IN p_request_id INT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    IF EXISTS(SELECT 1 FROM certificate_requests WHERE id = p_request_id AND status = 'approved') THEN
        UPDATE certificate_requests SET status = 'completed', completed_at = NOW() WHERE id = p_request_id;
        SET Record_Status = 'Request marked as completed.';
    ELSE
        SET Record_Status = 'Error: Only approved requests can be completed.';
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;