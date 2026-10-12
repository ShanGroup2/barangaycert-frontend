USE BeSCMS;
DROP PROCEDURE IF EXISTS Resident_Update;
DELIMITER $$
CREATE PROCEDURE Resident_Update(
    IN p_id INT,
    IN p_full_name VARCHAR(255),
    IN p_birthdate DATE,
    IN p_address TEXT,
    IN p_contact_number VARCHAR(20),
    IN p_gender ENUM('male','female'),
    IN p_registered_voter ENUM('yes','no'),
    IN p_updated_by VARCHAR(100)
)
BEGIN
    DECLARE Record_Status VARCHAR(100);
    IF EXISTS(SELECT 1 FROM residents WHERE id = p_id) THEN
        UPDATE residents SET full_name = p_full_name, birthdate = p_birthdate,
               address = p_address, contact_number = p_contact_number,
               gender = p_gender, registered_voter = p_registered_voter, last_updated_by = p_updated_by,
               updated_at = NOW() WHERE id = p_id;
        SET Record_Status = 'Resident updated.';
    ELSE
        SET Record_Status = 'Error: Resident ID not found.';
    END IF;
    SELECT Record_Status;
END$$
DELIMITER ;