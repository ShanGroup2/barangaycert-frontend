USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_GetByIdentity;
DELIMITER $$
CREATE PROCEDURE Resident_GetByIdentity(
    IN p_full_name VARCHAR(255),
    IN p_birthdate DATE,
    IN p_contact_number VARCHAR(20)
)
BEGIN
    SELECT id, full_name, birthdate, contact_number
    FROM residents
    WHERE full_name = p_full_name
      AND birthdate = p_birthdate
      AND contact_number = p_contact_number
    LIMIT 1;
END$$
DELIMITER ;
