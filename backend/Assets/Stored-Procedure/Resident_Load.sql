USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_Load;
DELIMITER $$
CREATE PROCEDURE Resident_Load()
BEGIN
    DECLARE Result VARCHAR(200);
    SET Result = 'No Record Found';
    IF EXISTS(SELECT * FROM residents) THEN
        SELECT * FROM residents ORDER BY id ASC;
    ELSE
        SELECT Result;
    END IF;
END$$
DELIMITER ;