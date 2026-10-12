USE BeSCMS;
DROP PROCEDURE IF EXISTS Certificate_Submit;
DELIMITER $$
CREATE PROCEDURE Certificate_Submit(
    IN p_user_id INT,
    IN p_certificate_type VARCHAR(100),
    IN p_purpose TEXT,
    IN p_quantity INT
)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    INSERT INTO certificate_requests (user_id, certificate_type, purpose, quantity, status, requested_at)
    VALUES (p_user_id, p_certificate_type, p_purpose, p_quantity, 'pending', NOW());
    SET Record_Status = 'Request submitted. Awaiting approval.';
    SELECT Record_Status;
END$$
DELIMITER ;