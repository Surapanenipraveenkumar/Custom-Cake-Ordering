-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2026 at 04:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `custom-cake`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT 'Home',
  `full_address` text NOT NULL,
  `pincode` varchar(10) DEFAULT '',
  `landmark` varchar(255) DEFAULT '',
  `phone` varchar(20) DEFAULT '',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `user_id`, `label`, `full_address`, `pincode`, `landmark`, `phone`, `is_default`, `created_at`) VALUES
(1, 1, 'Home', 'manikyam vari street', '', 'pandu house back', '6305693096', 1, '2025-12-31 06:53:27'),
(2, 1, 'Home', '104,', '', 'manikya vari Street', '6305693096', 0, '2025-12-31 07:59:01'),
(3, 1, 'Home', '104, Manikya vari Street', '', 'pandu house back', '6305693096', 0, '2025-12-31 11:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `ai_cake_images`
--

CREATE TABLE `ai_cake_images` (
  `image_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_input` text DEFAULT NULL,
  `ai_prompt` text DEFAULT NULL,
  `generated_image_url` text DEFAULT NULL,
  `status` enum('generated','failed') DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bakers`
--

CREATE TABLE `bakers` (
  `baker_id` int(11) NOT NULL,
  `shop_name` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `years_experience` int(11) DEFAULT 0,
  `is_online` tinyint(1) DEFAULT 1,
  `fcm_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bakers`
--

INSERT INTO `bakers` (`baker_id`, `shop_name`, `email`, `phone`, `address`, `password`, `owner_name`, `description`, `profile_image`, `latitude`, `longitude`, `specialty`, `years_experience`, `is_online`, `fcm_token`) VALUES
(2, 'Sweet Bakery', 'mahesh@gmail.com', '9876543210', 'Bangalore, Karnataka', '$2y$10$jCWN7uujd.kdtsFzKJ61tOWE1PMiHIOstaKfIlsbd5fvhil/zaYMy', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, NULL),
(3, 'vishnu sweet bakery', 'vishnu@gmail.com', '6305693096', 'chennai', 'e10adc3949ba59abbe56e057f20f883e', 'vishnu', 'We specialize in custom cakes for all occasions.', 'uploads/profiles/baker_1767102033_6953d651dedbc.jpg', NULL, NULL, 'Custom Cakes', 0, 1, 'elSpd9cjSouOWP8DBEkjAI:APA91bHMsvlQgjH1LXFRcvolDYvC6IpvjV5kPj_k57-6lVJQPWJ8acxWCLAwVQRIZ-raBIpbi2Tb0Lo6ZDH5Y2wOtCCjxjAruNR2sETiAZhCZLLhT-yhG3c'),
(4, 'rama krishna Bakery', 'ram@gmail.com', '6305693096', 'chennai', 'e10adc3949ba59abbe56e057f20f883e', 'Rama krishna', NULL, NULL, 13.02515410, 80.01044190, NULL, 0, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cakes`
--

CREATE TABLE `cakes` (
  `cake_id` int(11) NOT NULL,
  `baker_id` int(11) NOT NULL,
  `cake_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `availability` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cakes`
--

INSERT INTO `cakes` (`cake_id`, `baker_id`, `cake_name`, `description`, `price`, `image`, `created_at`, `availability`) VALUES
(18, 2, 'pineapple cake', 'rich flavour', 100.00, 'uploads/cakes/cake_1767105589_6953e4359c6fb.jpg', '2025-12-30 14:39:50', 1),
(19, 3, 'chocolate cake', 'beautiful chocolate cake', 100.00, 'uploads/cakes/cake_1767111806_6953fc7ed2b9c.jpg', '2025-12-30 16:23:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cake_colours`
--

CREATE TABLE `cake_colours` (
  `id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `colour` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cake_colours`
--

INSERT INTO `cake_colours` (`id`, `cake_id`, `colour`) VALUES
(1, 10, 'White'),
(2, 10, 'Brown'),
(3, 5, 'Brown'),
(8, 13, 'Purple'),
(9, 13, 'Red'),
(16, 17, 'Purple'),
(17, 17, 'Yellow'),
(18, 18, 'Pink'),
(19, 18, 'White'),
(20, 18, 'Purple'),
(30, 19, 'Pink'),
(31, 19, 'Blue');

-- --------------------------------------------------------

--
-- Table structure for table `cake_flavours`
--

CREATE TABLE `cake_flavours` (
  `id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `flavour` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cake_flavours`
--

INSERT INTO `cake_flavours` (`id`, `cake_id`, `flavour`) VALUES
(1, 10, 'Chocolate'),
(2, 5, 'Chocolate'),
(5, 13, 'Strawberry'),
(6, 13, 'Red Velvet'),
(10, 17, 'Strawberry'),
(11, 17, 'Red Velvet'),
(12, 18, 'Strawberry'),
(13, 18, 'Red Velvet'),
(24, 19, 'Chocolate'),
(25, 19, 'Vanilla'),
(26, 19, 'Strawberry');

-- --------------------------------------------------------

--
-- Table structure for table `cake_shapes`
--

CREATE TABLE `cake_shapes` (
  `id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `shape` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cake_shapes`
--

INSERT INTO `cake_shapes` (`id`, `cake_id`, `shape`) VALUES
(1, 10, 'Round'),
(2, 5, 'Round'),
(3, 5, 'Heart'),
(6, 13, 'Round'),
(7, 13, 'Square'),
(11, 17, 'Heart'),
(12, 17, 'Rectangle'),
(13, 18, 'Round'),
(14, 18, 'Square'),
(23, 19, 'Round'),
(24, 19, 'Square');

-- --------------------------------------------------------

--
-- Table structure for table `cake_toppings`
--

CREATE TABLE `cake_toppings` (
  `id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `topping` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cake_toppings`
--

INSERT INTO `cake_toppings` (`id`, `cake_id`, `topping`) VALUES
(1, 10, 'Cherries'),
(2, 10, 'Chocolate Chips'),
(3, 5, 'Nuts'),
(4, 5, 'Choco Chips'),
(9, 13, 'Chocolate Chips'),
(10, 13, 'Edible Flowers'),
(17, 17, 'Fruits'),
(18, 17, 'Chocolate Chips'),
(19, 18, 'Chocolate Chips'),
(20, 18, 'Fondant Figures'),
(32, 19, 'Fruits'),
(33, 19, 'Chocolate Chips'),
(34, 19, 'Fondant Figures');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `weight` varchar(50) DEFAULT '',
  `shape` varchar(50) DEFAULT '',
  `color` varchar(50) DEFAULT '',
  `flavor` varchar(50) DEFAULT '',
  `toppings` text DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `baker_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_type` enum('baker','customer') NOT NULL,
  `message` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `baker_id`, `user_id`, `sender_type`, `message`, `image_url`, `created_at`) VALUES
(9, 4, 1, 'customer', 'hi', NULL, '2026-01-01 18:34:54'),
(10, 4, 1, 'customer', 'hi', NULL, '2026-01-01 18:59:26'),
(11, 4, 1, 'customer', NULL, 'uploads/chat/chat_6956c474569149.22230340.jpg', '2026-01-01 19:01:22'),
(12, 4, 1, 'customer', 'hi', NULL, '2026-01-01 19:15:57'),
(13, 4, 1, 'customer', 'hi', NULL, '2026-01-01 19:36:54'),
(14, 4, 1, 'baker', 'hi', NULL, '2026-01-01 19:44:14'),
(15, 4, 1, 'customer', 'hi', NULL, '2026-01-02 09:25:58'),
(16, 4, 1, 'customer', NULL, 'uploads/chat/chat_69578f2b75cd78.34689235.jpg', '2026-01-02 09:26:03'),
(17, 4, 1, 'baker', 'hi', NULL, '2026-01-02 09:27:21'),
(18, 4, 1, 'baker', NULL, 'uploads/chat/chat_69578fa18d0bd4.78793516.jpg', '2026-01-02 09:28:01'),
(19, 4, 1, 'customer', 'hi', NULL, '2026-01-03 06:23:29'),
(20, 4, 1, 'customer', 'hi', NULL, '2026-01-06 06:46:49'),
(21, 4, 1, 'customer', 'hi', NULL, '2026-01-07 17:09:05'),
(22, 4, 1, 'customer', NULL, 'uploads/chat/chat_695e9338ece808.74919604.jpg', '2026-01-07 17:09:12'),
(23, 4, 1, 'customer', 'hi', NULL, '2026-01-07 17:13:21'),
(24, 4, 1, 'customer', NULL, 'uploads/chat/chat_695e943b609b43.81187987.jpg', '2026-01-07 17:13:31'),
(25, 4, 1, 'customer', 'hi', NULL, '2026-01-07 17:22:37'),
(26, 4, 1, 'customer', NULL, 'uploads/chat/chat_695e96683f4a44.37329558.jpg', '2026-01-07 17:22:48');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_persons`
--

CREATE TABLE `delivery_persons` (
  `delivery_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `vehicle` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `service_area` varchar(255) DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `license_image` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_persons`
--

INSERT INTO `delivery_persons` (`delivery_id`, `name`, `email`, `phone`, `vehicle`, `password`, `is_online`, `service_area`, `vehicle_number`, `fcm_token`, `latitude`, `longitude`, `license_image`) VALUES
(1, 'Arun Kumar', 'arun.deliver@gmail.com', '9876543210', 'Bike', '$2y$10$2LnNgPtLaFsFKG.nIyjVteNjmrdUwx5t0448/DzjJINyz.Bl9D.Aa', 0, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'rama krishna', 'pushpa@gmail.c', '6305693096', 'Motorcycle', '$2y$10$SrKhtwq9pfwQdrkltg1Rw.LhWsYv3LdkGt5Zp4GU7bLYqLdiTlkeq', 0, 'chennai', '6305693096', 'elSpd9cjSouOWP8DBEkjAI:APA91bHMsvlQgjH1LXFRcvolDYvC6IpvjV5kPj_k57-6lVJQPWJ8acxWCLAwVQRIZ-raBIpbi2Tb0Lo6ZDH5Y2wOtCCjxjAruNR2sETiAZhCZLLhT-yhG3c', NULL, NULL, NULL),
(3, 'Test Delivery', 'delivery@test.com', '9876543210', 'Motorcycle', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'sai', 'sai@gmail.com', '6305693066', 'Motorcycle', '$2y$10$iCW3oKnau7JRHkXyT4/SG.kTb55x1.rTguJ4aKbhh5loUU90DuC3e', 0, NULL, NULL, 'elSpd9cjSouOWP8DBEkjAI:APA91bHMsvlQgjH1LXFRcvolDYvC6IpvjV5kPj_k57-6lVJQPWJ8acxWCLAwVQRIZ-raBIpbi2Tb0Lo6ZDH5Y2wOtCCjxjAruNR2sETiAZhCZLLhT-yhG3c', NULL, NULL, 'http://10.231.138.84/Custom-Cake-Ordering/uploads/license/license_5_1767682260.jpg'),
(6, 'Surapaneni Praveen Kumar', 'ss@gmail.com', '6305693096', 'Bicycle', '$2y$10$gKjj2MU//mkT3aFiMrcqqO.NxbhKfEQJCI8VCo9nY4DuFNIUOVzeK', 1, NULL, NULL, 'elSpd9cjSouOWP8DBEkjAI:APA91bHMsvlQgjH1LXFRcvolDYvC6IpvjV5kPj_k57-6lVJQPWJ8acxWCLAwVQRIZ-raBIpbi2Tb0Lo6ZDH5Y2wOtCCjxjAruNR2sETiAZhCZLLhT-yhG3c', NULL, NULL, 'http://10.231.138.84/Custom-Cake-Ordering/uploads/license/license_6_1767806264.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`favorite_id`, `user_id`, `cake_id`, `created_at`) VALUES
(5, 1, 19, '2026-01-07 04:55:43');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `message_type` varchar(20) DEFAULT 'text',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_type` enum('customer','baker','delivery') NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_type`, `user_id`, `type`, `title`, `message`, `order_id`, `is_read`, `created_at`) VALUES
(1, 'customer', 1, 'delivery_update', 'Delivery Assigned! 🚚', 'A delivery partner has been assigned to your order #9', 9, 1, '2026-01-03 09:03:08'),
(2, 'customer', 1, 'delivery_update', 'Out for Delivery! 🚚', 'Your order #9 has been picked up and is on its way!', 9, 1, '2026-01-03 09:03:32'),
(3, 'customer', 1, 'order_status', 'Order Update', 'Your order #24 status: in_progress', 24, 0, '2026-01-04 09:45:36'),
(4, 'customer', 1, 'order_status', 'Order Update', 'Your order #23 status: in_progress', 23, 0, '2026-01-04 09:45:39'),
(5, 'customer', 1, 'order_status', 'Order Update', 'Your order #15 status: in_progress', 15, 0, '2026-01-04 09:45:43'),
(6, 'customer', 1, 'order_status', 'Order Update', 'Your order #24 status: in_progress', 24, 0, '2026-01-04 09:46:16'),
(7, 'customer', 1, 'order_status', 'Order Update', 'Your order #23 status: in_progress', 23, 0, '2026-01-04 09:46:17'),
(8, 'customer', 1, 'order_status', 'Order Update', 'Your order #22 status: in_progress', 22, 0, '2026-01-04 09:46:18'),
(9, 'customer', 1, 'order_status', 'Order Update', 'Your order #19 status: in_progress', 19, 0, '2026-01-04 09:46:20'),
(10, 'customer', 1, 'order_status', 'Order Update', 'Your order #17 status: in_progress', 17, 0, '2026-01-04 09:46:22'),
(11, 'customer', 1, 'order_status', 'Order Update', 'Your order #16 status: in_progress', 16, 0, '2026-01-04 09:46:22'),
(12, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #31 from praveen', 31, 0, '2026-01-04 09:57:11'),
(13, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #31 has been placed successfully!', 31, 1, '2026-01-04 09:57:11'),
(14, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #32 from praveen', 32, 0, '2026-01-04 09:57:38'),
(15, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #32 has been placed successfully!', 32, 0, '2026-01-04 09:57:38'),
(16, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #33 from praveen', 33, 0, '2026-01-04 10:02:41'),
(17, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #33 has been placed successfully!', 33, 0, '2026-01-04 10:02:41'),
(18, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #34 from praveen', 34, 0, '2026-01-05 04:24:20'),
(19, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #34 has been placed successfully!', 34, 0, '2026-01-05 04:24:20'),
(20, 'customer', 1, 'order_status', 'Order Update', 'Your order #34 status: in_progress', 34, 0, '2026-01-05 04:24:54'),
(21, 'customer', 1, 'order_status', 'Order Update', 'Your order #33 status: in_progress', 33, 0, '2026-01-05 04:24:56'),
(22, 'customer', 1, 'order_status', 'Order Update', 'Your order #32 status: in_progress', 32, 0, '2026-01-05 04:24:57'),
(23, 'customer', 1, 'order_status', 'Order Update', 'Your order #34 status: in_progress', 34, 0, '2026-01-05 04:25:16'),
(24, 'customer', 1, 'order_status', 'Order Update', 'Your order #33 status: in_progress', 33, 0, '2026-01-05 04:25:18'),
(25, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #35 from praveen', 35, 0, '2026-01-05 07:04:37'),
(26, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #35 has been placed successfully!', 35, 1, '2026-01-05 07:04:37'),
(27, 'baker', 2, 'new_order', 'New Order!', 'You have a new order #36 from praveen', 36, 0, '2026-01-05 07:11:28'),
(28, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #36 has been placed successfully!', 36, 0, '2026-01-05 07:11:28'),
(29, 'baker', 2, 'new_order', 'New Order!', 'You have a new order #37 from praveen', 37, 0, '2026-01-05 07:12:11'),
(30, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #37 has been placed successfully!', 37, 1, '2026-01-05 07:12:11'),
(31, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #38 from praveen', 38, 0, '2026-01-05 08:50:31'),
(32, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #38 has been placed successfully!', 38, 0, '2026-01-05 08:50:31'),
(33, 'customer', 1, 'order_status', 'Order Ready!', 'Your order #34 is ready for pickup/delivery!', 34, 0, '2026-01-05 10:33:26'),
(34, 'customer', 1, 'order_status', 'Order Ready!', 'Your order #33 is ready for pickup/delivery!', 33, 0, '2026-01-05 10:33:29'),
(35, 'customer', 1, 'order_status', 'Order Ready!', 'Your order #33 is ready for pickup/delivery!', 33, 0, '2026-01-05 10:33:45'),
(36, 'customer', 1, 'order_status', 'Order Update', 'Your order #30 status: in_progress', 30, 0, '2026-01-05 10:33:47'),
(37, 'customer', 1, 'order_status', 'Order Update', 'Your order #29 status: in_progress', 29, 0, '2026-01-05 10:33:49'),
(38, 'customer', 1, 'order_status', 'Baking in Progress!', 'Your order #31 is being prepared!', 31, 0, '2026-01-05 10:58:50'),
(39, 'customer', 1, 'order_status', 'Order Ready!', 'Your order #32 is ready for pickup/delivery!', 32, 0, '2026-01-05 10:58:52'),
(40, 'customer', 1, 'order_status', 'Baking in Progress!', 'Your order #31 is being prepared!', 31, 0, '2026-01-05 10:59:47'),
(41, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #39 from praveen', 39, 0, '2026-01-05 11:00:13'),
(42, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #39 has been placed successfully!', 39, 0, '2026-01-05 11:00:13'),
(43, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #39', 39, 0, '2026-01-05 11:27:11'),
(44, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #34', 34, 0, '2026-01-05 11:27:22'),
(45, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #34', 34, 0, '2026-01-05 11:27:23'),
(46, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #33', 33, 0, '2026-01-05 11:27:25'),
(47, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #31 from vishnu sweet bakery is ready for pickup!', 31, 0, '2026-01-05 12:19:43'),
(48, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #30 from vishnu sweet bakery is ready for pickup!', 30, 0, '2026-01-05 12:19:52'),
(49, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #30 from vishnu sweet bakery is ready for pickup!', 30, 0, '2026-01-05 12:19:53'),
(50, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #30 from vishnu sweet bakery is ready for pickup!', 30, 0, '2026-01-05 12:19:53'),
(51, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #30 from vishnu sweet bakery is ready for pickup!', 30, 0, '2026-01-05 12:19:53'),
(52, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #29 from vishnu sweet bakery is ready for pickup!', 29, 0, '2026-01-05 12:19:53'),
(53, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #29 from vishnu sweet bakery is ready for pickup!', 29, 0, '2026-01-05 12:19:53'),
(54, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #28 from vishnu sweet bakery is ready for pickup!', 28, 0, '2026-01-05 12:19:54'),
(55, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #28 from vishnu sweet bakery is ready for pickup!', 28, 0, '2026-01-05 12:19:54'),
(56, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #19 from vishnu sweet bakery is ready for pickup!', 19, 0, '2026-01-05 12:22:46'),
(57, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #19', 19, 0, '2026-01-05 12:23:04'),
(58, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #19', 19, 0, '2026-01-05 12:23:06'),
(59, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #19', 19, 0, '2026-01-05 12:23:42'),
(60, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #27 from vishnu sweet bakery is ready for pickup!', 27, 0, '2026-01-05 12:28:01'),
(61, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #15 from vishnu sweet bakery is ready for pickup!', 15, 0, '2026-01-05 12:29:29'),
(62, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #15', 15, 0, '2026-01-05 12:29:47'),
(63, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #24 from vishnu sweet bakery is ready for pickup!', 24, 0, '2026-01-05 12:41:44'),
(64, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #23 from vishnu sweet bakery is ready for pickup!', 23, 0, '2026-01-05 12:41:47'),
(65, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #24', 24, 0, '2026-01-05 12:42:23'),
(66, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #24', 24, 0, '2026-01-05 12:42:24'),
(67, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #23', 23, 0, '2026-01-05 12:43:00'),
(68, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #40 from praveen', 40, 0, '2026-01-05 12:54:23'),
(69, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #40 has been placed successfully!', 40, 0, '2026-01-05 12:54:23'),
(70, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #40 from vishnu sweet bakery is ready for pickup!', 40, 0, '2026-01-05 12:55:06'),
(71, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #40', 40, 0, '2026-01-05 12:55:41'),
(72, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #40 has been delivered. Enjoy!', 40, 0, '2026-01-05 13:14:50'),
(73, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #39', 39, 0, '2026-01-05 13:54:45'),
(74, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #39 has been delivered. Enjoy!', 39, 0, '2026-01-05 13:54:57'),
(75, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #34', 34, 0, '2026-01-05 14:18:09'),
(76, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #33', 33, 0, '2026-01-05 14:42:47'),
(77, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #34 has been delivered. Enjoy!', 34, 0, '2026-01-05 14:42:50'),
(78, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #19', 19, 1, '2026-01-05 14:47:28'),
(79, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #19 has been delivered. Enjoy!', 19, 1, '2026-01-05 14:47:38'),
(80, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #19 has been delivered. Enjoy!', 19, 1, '2026-01-05 14:47:39'),
(81, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #21 from vishnu sweet bakery is ready for pickup!', 21, 0, '2026-01-06 00:41:52'),
(82, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #16 from vishnu sweet bakery is ready for pickup!', 16, 0, '2026-01-06 00:41:58'),
(83, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #17 from vishnu sweet bakery is ready for pickup!', 17, 0, '2026-01-06 00:42:00'),
(84, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #22 from vishnu sweet bakery is ready for pickup!', 22, 0, '2026-01-06 00:42:01'),
(85, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #41 from praveen', 41, 0, '2026-01-06 01:11:22'),
(86, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #41 has been placed successfully!', 41, 0, '2026-01-06 01:11:22'),
(87, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #42 from praveen', 42, 0, '2026-01-06 01:15:04'),
(88, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #42 has been placed successfully!', 42, 0, '2026-01-06 01:15:04'),
(89, 'baker', 3, 'order_cancelled', 'Order Cancelled', 'Order #41 has been cancelled by the customer.', 41, 0, '2026-01-06 01:26:54'),
(90, 'customer', 1, 'order_cancelled', 'Order Cancelled', 'Your order #41 has been cancelled successfully.', 41, 1, '2026-01-06 01:26:54'),
(91, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #43 from praveen', 43, 0, '2026-01-06 13:07:36'),
(92, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #43 has been placed successfully!', 43, 0, '2026-01-06 13:07:36'),
(93, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #44 from praveen', 44, 0, '2026-01-06 13:13:14'),
(94, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #44 has been placed successfully!', 44, 0, '2026-01-06 13:13:14'),
(95, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #44 from vishnu sweet bakery is ready for pickup!', 44, 0, '2026-01-06 13:14:37'),
(96, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #44', 44, 0, '2026-01-06 13:14:59'),
(97, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #44 has been delivered. Enjoy!', 44, 0, '2026-01-06 13:15:22'),
(98, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #17', 17, 0, '2026-01-06 13:19:06'),
(99, 'baker', 2, 'order_cancelled', 'Order Cancelled', 'Order #26 has been cancelled by the customer.', 26, 0, '2026-01-06 13:38:41'),
(100, 'customer', 1, 'order_cancelled', 'Order Cancelled', 'Your order #26 has been cancelled successfully.', 26, 0, '2026-01-06 13:38:41'),
(101, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #45 from praveen', 45, 0, '2026-01-07 06:11:18'),
(102, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #45 has been placed successfully!', 45, 0, '2026-01-07 06:11:18'),
(103, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #46 from praveen', 46, 0, '2026-01-07 17:06:43'),
(104, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #46 has been placed successfully!', 46, 0, '2026-01-07 17:06:43'),
(105, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #47 from praveen', 47, 0, '2026-01-07 17:12:15'),
(106, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #47 has been placed successfully!', 47, 0, '2026-01-07 17:12:15'),
(107, 'baker', 3, 'order_cancelled', 'Order Cancelled', 'Order #46 has been cancelled by the customer.', 46, 0, '2026-01-07 17:12:38'),
(108, 'customer', 1, 'order_cancelled', 'Order Cancelled', 'Your order #46 has been cancelled successfully.', 46, 1, '2026-01-07 17:12:38'),
(109, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #47 from vishnu sweet bakery is ready for pickup!', 47, 0, '2026-01-07 17:16:36'),
(110, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #47', 47, 0, '2026-01-07 17:17:02'),
(111, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #47 has been delivered. Enjoy!', 47, 0, '2026-01-07 17:17:15'),
(112, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #48 from praveen', 48, 0, '2026-01-07 17:19:59'),
(113, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #48 has been placed successfully!', 48, 0, '2026-01-07 17:19:59'),
(114, 'baker', 3, 'order_cancelled', 'Order Cancelled', 'Order #45 has been cancelled by the customer.', 45, 0, '2026-01-07 17:20:29'),
(115, 'customer', 1, 'order_cancelled', 'Order Cancelled', 'Your order #45 has been cancelled successfully.', 45, 0, '2026-01-07 17:20:29'),
(116, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #49 from praveen', 49, 0, '2026-01-07 17:21:46'),
(117, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #49 has been placed successfully!', 49, 0, '2026-01-07 17:21:46'),
(118, 'baker', 3, 'order_cancelled', 'Order Cancelled', 'Order #48 has been cancelled by the customer.', 48, 0, '2026-01-07 17:22:23'),
(119, 'customer', 1, 'order_cancelled', 'Order Cancelled', 'Your order #48 has been cancelled successfully.', 48, 1, '2026-01-07 17:22:23'),
(120, 'delivery', 3, 'new_delivery', 'New Delivery Available!', 'Order #49 from vishnu sweet bakery is ready for pickup!', 49, 0, '2026-01-07 17:25:38'),
(121, 'customer', 1, 'delivery_update', 'Delivery Assigned!', 'A delivery partner has been assigned to your order #49', 49, 0, '2026-01-07 17:25:56'),
(122, 'customer', 1, 'delivery_update', 'Order Delivered!', 'Your order #49 has been delivered. Enjoy!', 49, 0, '2026-01-07 17:26:19'),
(123, 'baker', 3, 'new_order', 'New Order!', 'You have a new order #50 from praveen', 50, 0, '2026-01-07 17:43:25'),
(124, 'customer', 1, 'order_placed', 'Order Placed!', 'Your order #50 has been placed successfully!', 50, 0, '2026-01-07 17:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `baker_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `cake_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_address` text DEFAULT NULL,
  `delivery_date` varchar(50) DEFAULT NULL,
  `delivery_time` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `delivery_status` varchar(50) DEFAULT 'pending',
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `ready_for_delivery` tinyint(1) DEFAULT 0,
  `ready_for_delivery_at` datetime DEFAULT NULL,
  `assigned_delivery_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `baker_id`, `total_amount`, `cake_id`, `quantity`, `status`, `created_at`, `delivery_address`, `delivery_date`, `delivery_time`, `payment_method`, `delivery_id`, `delivery_status`, `picked_up_at`, `delivered_at`, `ready_for_delivery`, `ready_for_delivery_at`, `assigned_delivery_id`) VALUES
(6, 1, 3, 300.00, NULL, 1, 'delivered', '2025-12-31 11:27:40', 'Pickup from store', '31 Dec 2025', '04:57 pm', 'UPI', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(7, 1, 3, 100.00, NULL, 1, 'cancelled', '2025-12-31 13:55:07', 'Pickup from store', '31 Dec 2025', '07:24 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(8, 1, 2, 100.00, NULL, 1, 'delivered', '2025-12-31 15:44:24', 'Pickup from store', '31 Dec 2025', '09:14 pm', 'UPI', 2, 'delivered', '2026-01-02 04:59:50', '2026-01-02 05:00:16', 1, '2026-01-02 04:26:30', NULL),
(9, 1, 2, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-01 05:00:21', 'manikyam vari street', '01 Jan 2026', '10:30 am', 'UPI', 5, 'assigned', NULL, NULL, 1, '2026-01-03 13:46:26', NULL),
(10, 1, 2, 100.00, NULL, 1, 'delivered', '2026-01-02 09:43:30', 'Pickup from store', '02 Jan 2026', '03:13 am', 'UPI', 2, 'delivered', '2026-01-03 11:59:36', '2026-01-03 11:59:50', 1, '2026-01-03 11:49:27', NULL),
(11, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-03 06:59:52', 'Pickup from store', '03 Jan 2026', '12:29 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(12, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-03 07:04:13', 'Pickup from store', '03 Jan 2026', '12:34 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(13, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-03 07:40:17', 'Pickup from store', '22 Jan 2026', '05:09 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(14, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-03 08:02:12', 'Pickup from store', '03 Jan 2026', '01:31 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(15, 1, 3, 100.00, NULL, 1, 'out_for_delivery', '2026-01-03 08:39:22', 'Pickup from store', '03 Jan 2026', '02:09 pm', 'Online Payment (Razorpay)', 6, 'pending', NULL, NULL, 1, '2026-01-05 17:59:29', 3),
(16, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-03 08:52:20', 'Pickup from store', '03 Jan 2026', '02:22 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 1, '2026-01-06 06:11:58', 3),
(17, 1, 3, 150.00, NULL, 1, 'out_for_delivery', '2026-01-03 08:59:04', 'manikyam vari street', '03 Jan 2026', '02:28 pm', 'Online Payment (Razorpay)', 5, 'assigned', NULL, NULL, 1, '2026-01-06 06:12:00', 3),
(18, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-03 09:00:24', 'Pickup from store', '03 Jan 2026', '02:30 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(19, 1, 3, 100.00, NULL, 1, 'delivered', '2026-01-03 09:23:46', 'Pickup from store', '03 Jan 2026', '02:53 pm', 'Cash on Delivery', 6, 'delivered', NULL, '2026-01-05 20:17:39', 1, '2026-01-05 17:52:46', 3),
(20, 1, 3, 100.00, NULL, 1, 'pending', '2026-01-03 09:30:17', 'Pickup from store', '03 Jan 2026', '03:00 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(21, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-03 09:31:12', 'Pickup from store', '03 Jan 2026', '03:01 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 1, '2026-01-06 06:11:52', 3),
(22, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:26:22', 'Pickup from store', '04 Jan 2026', '02:55 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 1, '2026-01-06 06:12:01', 3),
(23, 1, 3, 100.00, NULL, 1, 'out_for_delivery', '2026-01-04 09:30:05', 'Pickup from store', '04 Jan 2026', '02:59 pm', 'Online Payment (Razorpay)', 6, 'assigned', NULL, NULL, 1, '2026-01-05 18:11:47', 3),
(24, 1, 3, 100.00, NULL, 1, 'out_for_delivery', '2026-01-04 09:31:14', 'Pickup from store', '04 Jan 2026', '03:00 pm', 'Online Payment (Razorpay)', 6, 'assigned', NULL, NULL, 1, '2026-01-05 18:11:44', 3),
(25, 1, 2, 150.00, NULL, 1, 'pending', '2026-01-04 09:43:26', 'manikyam vari street', '04 Jan 2026', '03:13 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(26, 1, 2, 100.00, NULL, 1, 'cancelled', '2026-01-04 09:43:53', 'Pickup from store', '04 Jan 2026', '03:13 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(27, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:47:15', '', '', '', 'online', NULL, 'pending', NULL, NULL, 1, '2026-01-05 17:58:01', 3),
(28, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:49:58', '', '', '', 'online', NULL, 'pending', NULL, NULL, 1, '2026-01-05 17:49:54', 3),
(29, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:53:08', '', '', '', 'online', NULL, 'pending', NULL, NULL, 1, '2026-01-05 17:49:53', 3),
(30, 1, 3, 300.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:55:52', '', '', '', 'online', NULL, 'pending', NULL, NULL, 1, '2026-01-05 17:49:53', 3),
(31, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:57:11', '', '', '', 'online', NULL, 'pending', NULL, NULL, 1, '2026-01-05 17:49:43', 3),
(32, 1, 3, 100.00, NULL, 1, 'ready_for_pickup', '2026-01-04 09:57:38', '', '', '', 'online', NULL, 'pending', NULL, NULL, 1, '2026-01-05 16:32:29', NULL),
(33, 1, 3, 100.00, NULL, 1, 'out_for_delivery', '2026-01-04 10:02:41', 'Pickup from store', '04 Jan 2026', '03:32 pm', 'Online Payment (Razorpay)', 6, 'assigned', NULL, NULL, 1, '2026-01-05 16:14:15', NULL),
(34, 1, 3, 100.00, NULL, 1, 'delivered', '2026-01-05 04:24:20', 'Pickup from store', '05 Jan 2026', '09:53 am', 'Online Payment (Razorpay)', 6, 'delivered', NULL, '2026-01-05 20:12:50', 1, '2026-01-05 16:14:08', NULL),
(35, 1, 3, 150.00, NULL, 1, 'cancelled', '2026-01-05 07:04:37', 'manikyam vari street', '05 Jan 2026', '12:34 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(36, 1, 2, 100.00, NULL, 1, 'cancelled', '2026-01-05 07:11:28', 'Pickup from store', '05 Jan 2026', '12:41 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(37, 1, 2, 100.00, NULL, 1, 'cancelled', '2026-01-05 07:12:11', 'Pickup from store', '05 Jan 2026', '12:42 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(38, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-05 08:50:31', 'Pickup from store', '05 Jan 2026', '02:20 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(39, 1, 3, 100.00, NULL, 1, 'delivered', '2026-01-05 11:00:12', 'Pickup from store', '05 Jan 2026', '04:30 pm', 'Cash on Delivery', 6, 'delivered', NULL, '2026-01-05 19:24:57', 1, '2026-01-05 16:32:17', NULL),
(40, 1, 3, 150.00, NULL, 1, 'delivered', '2026-01-05 12:54:23', 'manikyam vari street', '05 Jan 2026', '06:24 pm', 'Online Payment (Razorpay)', 6, 'delivered', NULL, NULL, 1, '2026-01-05 18:25:06', 3),
(41, 1, 3, 100.00, NULL, 1, 'cancelled', '2026-01-06 01:11:22', 'Pickup from store', '06 Jan 2026', '06:41 am', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(42, 1, 3, 100.00, NULL, 1, 'ready', '2026-01-06 01:15:04', 'Pickup from store', '06 Jan 2026', '06:44 am', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(43, 1, 3, 100.00, NULL, 1, 'ready', '2026-01-06 13:07:36', 'Pickup from store', '06 Jan 2026', '06:37 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(44, 1, 3, 150.00, NULL, 1, 'delivered', '2026-01-06 13:13:14', 'manikyam vari street', '06 Jan 2026', '06:43 pm', 'Cash on Delivery', 5, 'delivered', NULL, '2026-01-06 18:45:22', 1, '2026-01-06 18:44:37', 3),
(45, 1, 3, 350.00, NULL, 1, 'cancelled', '2026-01-07 06:11:18', 'manikyam vari street', '07 Jan 2026', '11:40 am', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(46, 1, 3, 250.00, NULL, 1, 'cancelled', '2026-01-07 17:06:43', 'manikyam vari street', '07 Jan 2026', '10:36 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(47, 1, 3, 150.00, NULL, 1, 'delivered', '2026-01-07 17:12:15', 'manikyam vari street', '07 Jan 2026', '10:42 pm', 'Online Payment (Razorpay)', 6, 'delivered', NULL, '2026-01-07 22:47:15', 1, '2026-01-07 22:46:36', 3),
(48, 1, 3, 250.00, NULL, 1, 'cancelled', '2026-01-07 17:19:59', '104, Manikya vari Street', '07 Jan 2026', '10:49 pm', 'Online Payment (Razorpay)', NULL, 'pending', NULL, NULL, 0, NULL, NULL),
(49, 1, 3, 250.00, NULL, 1, 'delivered', '2026-01-07 17:21:46', 'manikyam vari street', '07 Jan 2026', '10:51 pm', 'Online Payment (Razorpay)', 6, 'delivered', NULL, '2026-01-07 22:56:19', 1, '2026-01-07 22:55:38', 3),
(50, 1, 3, 100.00, NULL, 1, 'pending', '2026-01-07 17:43:25', 'Pickup from store', '07 Jan 2026', '11:13 pm', 'Cash on Delivery', NULL, 'pending', NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `cake_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `cake_id`, `quantity`, `price`, `created_at`) VALUES
(7, 7, 19, 1, 100.00, '2025-12-31 13:55:07'),
(8, 8, 18, 1, 100.00, '2025-12-31 15:44:24'),
(9, 9, 18, 1, 100.00, '2026-01-01 05:00:21'),
(10, 10, 18, 1, 100.00, '2026-01-02 09:43:30'),
(11, 11, 19, 1, 100.00, '2026-01-03 06:59:50'),
(12, 12, 19, 1, 100.00, '2026-01-03 07:04:13'),
(13, 13, 19, 1, 100.00, '2026-01-03 07:40:17'),
(14, 14, 19, 1, 100.00, '2026-01-03 08:02:12'),
(15, 15, 19, 1, 100.00, '2026-01-03 08:39:22'),
(16, 16, 19, 1, 100.00, '2026-01-03 08:52:20'),
(17, 17, 19, 1, 100.00, '2026-01-03 08:59:04'),
(18, 18, 19, 1, 100.00, '2026-01-03 09:00:24'),
(19, 19, 19, 1, 100.00, '2026-01-03 09:23:46'),
(20, 20, 19, 1, 100.00, '2026-01-03 09:30:17'),
(21, 21, 19, 1, 100.00, '2026-01-03 09:31:12'),
(22, 22, 19, 1, 100.00, '2026-01-04 09:26:22'),
(23, 23, 19, 1, 100.00, '2026-01-04 09:30:05'),
(24, 24, 19, 1, 100.00, '2026-01-04 09:31:14'),
(25, 25, 18, 1, 100.00, '2026-01-04 09:43:26'),
(26, 26, 18, 1, 100.00, '2026-01-04 09:43:53'),
(27, 27, 19, 1, 100.00, '2026-01-04 09:47:15'),
(28, 28, 19, 1, 100.00, '2026-01-04 09:49:58'),
(29, 29, 19, 1, 100.00, '2026-01-04 09:53:08'),
(30, 30, 19, 1, 100.00, '2026-01-04 09:55:52'),
(31, 30, 19, 1, 100.00, '2026-01-04 09:55:52'),
(32, 30, 19, 1, 100.00, '2026-01-04 09:55:52'),
(33, 31, 19, 1, 100.00, '2026-01-04 09:57:11'),
(34, 32, 19, 1, 100.00, '2026-01-04 09:57:38'),
(35, 33, 19, 1, 100.00, '2026-01-04 10:02:41'),
(36, 34, 19, 1, 100.00, '2026-01-05 04:24:20'),
(37, 35, 19, 1, 100.00, '2026-01-05 07:04:37'),
(38, 36, 18, 1, 100.00, '2026-01-05 07:11:28'),
(39, 37, 18, 1, 100.00, '2026-01-05 07:12:11'),
(40, 38, 19, 1, 100.00, '2026-01-05 08:50:31'),
(41, 39, 19, 1, 100.00, '2026-01-05 11:00:13'),
(42, 40, 19, 1, 100.00, '2026-01-05 12:54:23'),
(43, 41, 19, 1, 100.00, '2026-01-06 01:11:22'),
(44, 42, 19, 1, 100.00, '2026-01-06 01:15:04'),
(45, 43, 19, 1, 100.00, '2026-01-06 13:07:36'),
(46, 44, 19, 1, 100.00, '2026-01-06 13:13:14'),
(47, 45, 19, 3, 100.00, '2026-01-07 06:11:18'),
(48, 46, 19, 1, 100.00, '2026-01-07 17:06:43'),
(49, 46, 19, 1, 100.00, '2026-01-07 17:06:43'),
(50, 47, 19, 1, 100.00, '2026-01-07 17:12:15'),
(51, 48, 19, 2, 100.00, '2026-01-07 17:19:59'),
(52, 49, 19, 2, 100.00, '2026-01-07 17:21:46'),
(53, 50, 19, 1, 100.00, '2026-01-07 17:43:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fcm_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `address`, `password`, `created_at`, `fcm_token`) VALUES
(1, 'praveen', 'praveen@gmail.com', '9876543210', 'Chennai', '$2y$10$/CQAZRHjhMwuWJeDzp.NCu4WTyACnSIgUxsQJi8KM19kSvFUe9/wa', '2025-12-31 13:47:45', 'elSpd9cjSouOWP8DBEkjAI:APA91bHMsvlQgjH1LXFRcvolDYvC6IpvjV5kPj_k57-6lVJQPWJ8acxWCLAwVQRIZ-raBIpbi2Tb0Lo6ZDH5Y2wOtCCjxjAruNR2sETiAZhCZLLhT-yhG3c'),
(2, 'praveen', 'praveen1@gmail.com', '9876543210', 'Chennai', '$2y$10$bna359DIIScdFyVT9rIJBuV27ggJzm.MMnAJ2SGDWzS5N7oCWHnOS', '2025-12-31 13:47:45', NULL),
(3, 'sasi', 'sasi@gmail.com', '9876543210', 'Chennai', '$2y$10$ALPIhSLzeFciUhHpLQC.BeP7Hr6k1RvfYxjSmZLdOQVDsHHrXHSci', '2025-12-31 13:47:45', NULL),
(4, 'sai', 'sai@gmail.com', '9848001113', 'kavali', '$2y$10$9SpTYmQIKvMP82ZA8dGEkuV0EsSGO2OYwFzZ7rZWx89vzpkTdqARC', '2025-12-31 13:47:45', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`);

--
-- Indexes for table `ai_cake_images`
--
ALTER TABLE `ai_cake_images`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes for table `bakers`
--
ALTER TABLE `bakers`
  ADD PRIMARY KEY (`baker_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cakes`
--
ALTER TABLE `cakes`
  ADD PRIMARY KEY (`cake_id`);

--
-- Indexes for table `cake_colours`
--
ALTER TABLE `cake_colours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cake_id` (`cake_id`);

--
-- Indexes for table `cake_flavours`
--
ALTER TABLE `cake_flavours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cake_id` (`cake_id`);

--
-- Indexes for table `cake_shapes`
--
ALTER TABLE `cake_shapes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cake_id` (`cake_id`);

--
-- Indexes for table `cake_toppings`
--
ALTER TABLE `cake_toppings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cake_id` (`cake_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `cake_id` (`cake_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `baker_id` (`baker_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `delivery_persons`
--
ALTER TABLE `delivery_persons`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`cake_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_type`,`user_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ai_cake_images`
--
ALTER TABLE `ai_cake_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bakers`
--
ALTER TABLE `bakers`
  MODIFY `baker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cakes`
--
ALTER TABLE `cakes`
  MODIFY `cake_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `cake_colours`
--
ALTER TABLE `cake_colours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `cake_flavours`
--
ALTER TABLE `cake_flavours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `cake_shapes`
--
ALTER TABLE `cake_shapes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `cake_toppings`
--
ALTER TABLE `cake_toppings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `delivery_persons`
--
ALTER TABLE `delivery_persons`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cake_colours`
--
ALTER TABLE `cake_colours`
  ADD CONSTRAINT `cake_colours_ibfk_1` FOREIGN KEY (`cake_id`) REFERENCES `cakes` (`cake_id`) ON DELETE CASCADE;

--
-- Constraints for table `cake_flavours`
--
ALTER TABLE `cake_flavours`
  ADD CONSTRAINT `cake_flavours_ibfk_1` FOREIGN KEY (`cake_id`) REFERENCES `cakes` (`cake_id`) ON DELETE CASCADE;

--
-- Constraints for table `cake_shapes`
--
ALTER TABLE `cake_shapes`
  ADD CONSTRAINT `cake_shapes_ibfk_1` FOREIGN KEY (`cake_id`) REFERENCES `cakes` (`cake_id`) ON DELETE CASCADE;

--
-- Constraints for table `cake_toppings`
--
ALTER TABLE `cake_toppings`
  ADD CONSTRAINT `cake_toppings_ibfk_1` FOREIGN KEY (`cake_id`) REFERENCES `cakes` (`cake_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`cake_id`) REFERENCES `cakes` (`cake_id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`baker_id`) REFERENCES `bakers` (`baker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
