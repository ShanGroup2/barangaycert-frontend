USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_Export;
DELIMITER $$
CREATE PROCEDURE Resident_Export()
BEGIN
    SELECT full_name, birthdate, address, contact_number, gender, registered_voter, added_by
    FROM residents ORDER BY full_name;
END$$
DELIMITER ;