-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-05-31 01:26:00
-- 服务器版本： 5.7.44-log
-- PHP 版本： 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `authphp`
--

-- --------------------------------------------------------

--
-- 表的结构 `card_keys`
--

CREATE TABLE `card_keys` (
  `id` int(11) NOT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `card_key` varchar(32) NOT NULL,
  `card_type` varchar(20) NOT NULL DEFAULT '普通卡',
  `duration` int(11) NOT NULL COMMENT '有效期(天)',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-未使用 1-已使用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `use_time` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT '使用者ID',
  `user_ip` varchar(50) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL COMMENT '授权的域名'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `proxy_users`
--

CREATE TABLE `proxy_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1' COMMENT '1-启用,0-禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT '日志类型',
  `action` varchar(255) NOT NULL COMMENT '操作内容',
  `details` text COMMENT '详细信息',
  `ip` varchar(50) DEFAULT NULL COMMENT '操作IP',
  `user_agent` varchar(255) DEFAULT NULL COMMENT '浏览器信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `system_logs`
--

INSERT INTO `system_logs` (`id`, `type`, `action`, `details`, `ip`, `user_agent`, `created_at`) VALUES
(1, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '39.187.105.207', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Mobile/15E148 Safari/604.1', '2025-05-29 08:37:41'),
(2, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '141.98.75.210', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2025-05-29 08:57:46'),
(3, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '141.98.75.210', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2025-05-29 08:58:01'),
(4, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '39.187.105.207', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Mobile/15E148 Safari/604.1', '2025-05-29 09:02:09'),
(5, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '39.187.105.207', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Mobile/15E148 Safari/604.1', '2025-05-29 09:04:43'),
(6, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '39.187.105.207', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Mobile/15E148 Safari/604.1', '2025-05-29 10:33:37'),
(7, 'login', '管理员登录', '{\"username\":\"admin\",\"success\":true}', '141.98.75.210', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2025-05-30 17:13:58');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e');

--
-- 转储表的索引
--

--
-- 表的索引 `card_keys`
--
ALTER TABLE `card_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_key` (`card_key`),
  ADD KEY `fk_proxy_id` (`proxy_id`);

--
-- 表的索引 `proxy_users`
--
ALTER TABLE `proxy_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `created_at` (`created_at`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `card_keys`
--
ALTER TABLE `card_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- 使用表AUTO_INCREMENT `proxy_users`
--
ALTER TABLE `proxy_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 限制导出的表
--

--
-- 限制表 `card_keys`
--
ALTER TABLE `card_keys`
  ADD CONSTRAINT `fk_proxy_id` FOREIGN KEY (`proxy_id`) REFERENCES `proxy_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
