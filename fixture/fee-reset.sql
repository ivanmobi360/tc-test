TRUNCATE TABLE `fee`;
INSERT INTO `fee` (`id`, `type`, `name`, `fixed`, `percentage`, `is_default`, `fee_max`, `module_id`) VALUES
(1, 'tf', 'basic', 1.06, 2.5, 1, '9.95', NULL),
(2, 'cc', 'basic with cc', 0.3, 2.9, 0, NULL, NULL),
(3, 'tf', 'No tixpro fees', 0, 0, 0, NULL, NULL),
(4, 'cc', 'No credit card fees', 0, 0, 0, NULL, NULL),
(5, 'tf', 'TixPRO Fee (BBD)', 2, 0, 0, '9.95', NULL),
(6, 'cc', 'CC Fee (BBD)', 0.6, 2.9, 0, NULL, NULL),
(7, 'tf', '', 1.62, 0, 0, '9.95', NULL),
(8, 'tf', '', 1.62, 0, 0, '9.95', NULL),
(9, 'tf', '', 1.62, 0, 0, '9.95', NULL),
(10, 'tf', '', 1.62, 0, 0, '9.95', NULL),
(11, 'tf', 'Dan''s Food and Beverage', 1, 0, 0, '9.95', NULL),
(12, 'tf', 'Fun Walk', 1.5, 0, 0, '9.95', NULL),
(13, 'tf', '', 2, 2.5, 0, '9.95', NULL),
(14, 'tf', 'Outrageous TF', 0.76, 0, 0, '9.95', NULL),
(15, 'cc', 'Outrageous CC', 0.7, 3.6, 0, NULL, NULL),
(16, 'tf', 'new default', 1.06, 2.5, 0, '9.95', NULL),
(17, 'cc', 'CC FEE', 0.7, 3.6, 1, NULL, NULL),
(18, 'tf', '', 1, 0, 0, '9.95', NULL),
(19, 'tf', '', 1.15, 0, 0, NULL, NULL),
(20, 'tf', '', 2.25, 0, 0, NULL, NULL),
(21, 'tf', '', 1.06, 0, 0, NULL, NULL),
(22, 'tf', 'MissBB Coronation', 1.49, 0, 0, NULL, NULL),
(23, 'tf', 'Glow', 0.21, 0, 0, NULL, NULL),
(24, 'tf', 'Diner with George', 2.55, 0, 0, NULL, NULL);
