-- ztfb
SELECT `hsab`.* FROM (
	SELECT
		`code`, `type`, COUNT(*) AS `count`
	FROM
		`hsab`
	WHERE
		`date` >= (SELECT MIN(`date`) FROM (SELECT `date` FROM `hsab` WHERE `date` <> CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT 2) AS `t`)
		AND IF (`date` = (SELECT MIN(`date`) FROM (SELECT `date` FROM `hsab` WHERE `date` <> CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT 2) AS `t`),
			`price` = `zt`, `price` = `zt` AND `zf` = 0 AND `date` = (SELECT MIN(`date`) FROM (SELECT `date` FROM `hsab` WHERE `date` <> CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT 1) AS `t`))
		GROUP BY `code`
) AS `t`
LEFT JOIN `hsab` ON `t`.`code`=`hsab`.`code` AND `t`.`type`=`hsab`.`type`
WHERE count = 2
	AND `date` >= (SELECT MIN(`date`) FROM (SELECT `date` FROM `hsab` WHERE `date` <> CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT 8) AS `t`)
	AND IF (`hsab`.`code` IN (SELECT `code` FROM `hsab` WHERE `date` >= (SELECT MIN(`date`) FROM (SELECT `date` FROM `hsab` WHERE `date` <> CURDATE() GROUP BY `date` ORDER BY `date` DESC LIMIT 8) AS `t`) AND LEFT (`name`, 1) IN ('N','*')),
		false, true)