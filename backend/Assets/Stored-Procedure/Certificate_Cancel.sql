USE BeSCMS;
DROP PROCEDURE IF EXISTS Certificate_Cancel;
DELIMITER $$
CREATE PROCEDURE Certificate_Cancel(IN p_request_id INT)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    IF EXISTS(SELECT 1 FROM certificate_requests WHERE id = p_request_id AND status = 'pending') THEN
        UPDATE certificate_requests SET status = 'cancelled', cancelled_at = NOW() WHERE id = p_request_id;
        SET Record_Status = 'Request cancelled.';
    ELSE
        SET Record_Status = 'Error: Only pending requests can be cancelled.';
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;