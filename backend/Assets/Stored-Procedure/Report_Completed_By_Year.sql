USE BeSCMS;
DROP PROCEDURE IF EXISTS Report_Completed_By_Year;
DELIMITER $$
CREATE PROCEDURE Report_Completed_By_Year(IN p_year INT)
BEGIN
    DECLARE Result VARCHAR(200);
    SET Result = CONCAT('No completed transactions for year ', p_year);
    IF EXISTS(SELECT 1 FROM certificate_requests WHERE status = 'completed' AND YEAR(completed_at) = p_year) THEN
        SELECT r.full_name AS resident_name,
               cr.certificate_type,
               cr.purpose,
               cr.quantity,
               cr.completed_at,
               cr.admin_notes
        FROM certificate_requests cr
        JOIN users u ON cr.user_id = u.id
        JOIN residents r ON u.resident_id = r.id
        WHERE cr.status = 'completed' AND YEAR(cr.completed_at) = p_year
        ORDER BY cr.completed_at DESC;
    ELSE
        SELECT Result;
    END IF;
END$$
DELIMITER ;