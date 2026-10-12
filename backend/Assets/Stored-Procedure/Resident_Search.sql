USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_Search;
DELIMITER $$
CREATE PROCEDURE Resident_Search(IN searchKey VARCHAR(255))
BEGIN
    DECLARE Result VARCHAR(200);
    SET Result = CONCAT('No Record Found for SearchKey: ', searchKey);
    IF EXISTS(SELECT * FROM residents WHERE full_name LIKE CONCAT('%', searchKey, '%') OR address LIKE CONCAT('%', searchKey, '%')) THEN
        SELECT * FROM residents
        WHERE full_name LIKE CONCAT('%', searchKey, '%') OR address LIKE CONCAT('%', searchKey, '%')
        ORDER BY full_name;
    ELSE
        SELECT Result;
    END IF;
END$$
DELIMITER ;