TRUNCATE TABLE `fee`;
INSERT INTO `fee` (`id`, `type`, `name`, `fixed`, `percentage`, `is_default`, `fee_max`) VALUES
(1, 'tf', 'basic', 1.08, 2.5, 1, '9.95'),
(2, 'cc', 'basic with cc', 0.3, 2.9, 0, NULL),
(3, 'tf', 'No tixpro fees', 0, 0, 0, NULL),
(4, 'cc', 'No credit card fees', 0, 0, 0, NULL),
(5, 'tf', 'TixPRO Fee (BBD)', 2, 0, 0, '9.95'),
(6, 'cc', 'CC Fee (BBD)', 0.6, 2.9, 1, NULL),
(7, 'tf', '', 1.62, 0, 0, '9.95'),
(8, 'tf', '', 1.62, 0, 0, '9.95'),
(9, 'tf', '', 1.62, 0, 0, '9.95'),
(10, 'tf', '', 1.62, 0, 0, '9.95'),
(11, 'tf', 'Dan''s Food and Beverage', 1, 0, 0, '9.95'),
(12, 'tf', 'Fun Walk', 1.5, 0, 0, '9.95');