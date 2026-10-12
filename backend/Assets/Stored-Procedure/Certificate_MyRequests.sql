DELIMITER //

DROP PROCEDURE IF EXISTS Certificate_MyRequests //
CREATE PROCEDURE Certificate_MyRequests(
  IN p_user_id INT,
  IN p_status VARCHAR(50)
)
BEGIN
  SELECT *
  FROM certificate_requests
  WHERE user_id = p_user_id
    AND (p_status IS NULL OR p_status = '' OR status = p_status)
  ORDER BY requested_at DESC;
END //

DELIMITER ;
