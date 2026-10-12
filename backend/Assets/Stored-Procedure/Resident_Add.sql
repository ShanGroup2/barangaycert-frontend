USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_Add;
DELIMITER $$
CREATE PROCEDURE Resident_Add(
    IN p_full_name VARCHAR(255),
    IN p_birthdate DATE,
    IN p_address TEXT,
    IN p_contact_number VARCHAR(20),
    IN p_gender ENUM('male','female'),
    IN p_registered_voter ENUM('yes','no'),
    IN p_added_by VARCHAR(100)
)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    INSERT INTO residents (full_name, birthdate, address, contact_number, gender, registered_voter, added_by)
    VALUES (p_full_name, p_birthdate, p_address, p_contact_number, p_gender, p_registered_voter, p_added_by);
    SET Record_Status = 'Resident added successfully.';
    SELECT Record_Status;
END$$
DELIMITER ;