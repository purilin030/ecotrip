-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 10:16 AM
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
-- Database: `ecotrip`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(10) NOT NULL,
  `CategoryName` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`) VALUES
(1, 'Energy Saving\r\n'),
(2, 'Waste Reduction'),
(3, 'Transportation'),
(4, 'Recycling'),
(5, 'Shopping'),
(6, 'Nature');

-- --------------------------------------------------------

--
-- Table structure for table `challenge`
--

CREATE TABLE `challenge` (
  `Challenge_ID` int(10) NOT NULL,
  `Category_ID` int(10) NOT NULL,
  `City_ID` int(10) NOT NULL,
  `User_ID` int(10) NOT NULL,
  `Title` text NOT NULL,
  `Detailed_Description` text NOT NULL,
  `preview_description` text NOT NULL,
  `Difficulty` text NOT NULL,
  `Points` int(4) NOT NULL,
  `Start_date` date NOT NULL,
  `End_date` date NOT NULL,
  `photo_upload` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenge`
--

INSERT INTO `challenge` (`Challenge_ID`, `Category_ID`, `City_ID`, `User_ID`, `Title`, `Detailed_Description`, `preview_description`, `Difficulty`, `Points`, `Start_date`, `End_date`, `photo_upload`, `status`) VALUES
(1, 1, 1, 15, 'No-Power Friday', 'Challenge Overview:\r\nTake a break from the digital world and help the grid! We are challenging you to turn off all non-essential lights and electronics for 4 hours this Friday evening.\r\n\r\nRules &amp; Requirements:\r\n1. Duration: You must maintain the &quot;power down&quot; state for at least 4 continuous hours between 6:00 PM and 11:00 PM.\r\n2. Allowed Devices: Fridges and medical equipment may remain on. Phones should be on airplane mode or powered off.\r\n3. Proof: Take a timestamped photo of your dark living room or your family playing a board game by candlelight.\r\n\r\nAdditional Notice:\r\n- Candles are encouraged for atmosphere but please practice fire safety.\r\n- This is a great opportunity to read a physical book or meditate!', 'Disconnect to reconnect! Spend your Friday evening without using non-essential electricity for 4 hours.', 'Easy', 200, '2025-12-05', '2025-12-05', '1764651633_692e72719fe84.jpeg', 'Active'),
(2, 2, 6, 15, 'Zero Waste Lunch', 'Challenge Overview:\r\nDid you know the average person generates 4.4lbs of waste daily? Today, your mission is to have a completely zero-waste lunch. No plastic wrap, no disposable forks, and no plastic bags.\r\n\r\nRules &amp; Requirements:\r\n1. Containers: Use glass, metal, or durable plastic tupperware.\r\n2. Cutlery: Bring your own fork/spoon or eat finger foods.\r\n3. Food Scraps: Any organic leftovers must be composted or finished (zero food waste!).\r\n\r\nHow to Claim Points:\r\n- Upload a photo of your packed lunch showing your reusable setup.\r\n- Self-declaration that no trash was thrown in the bin after the meal.', 'Say no to single-use plastics. Pack a lunch using only reusable containers and cutlery.', 'Easy', 150, '2025-12-03', '2025-12-31', '1764651670_692e729655a7d.webp', 'Active'),
(3, 3, 2, 15, 'Green Commute Challenge', 'Challenge Overview:\r\nReduce carbon emissions by changing how you move. For this challenge, private cars and ride-hailing services (Grab/Uber) are off-limits for your daily commute.\r\n\r\nRules &amp; Requirements:\r\n1. Eligible Methods: Cycling, walking, bus, MRT/LRT, or carpooling (3+ people in one car).\r\n2. Minimum Distance: The commute must be at least 2km one way.\r\n3. Safety: If cycling, helmets are mandatory. Obey all traffic laws.\r\n\r\nProof of Completion:\r\n- Submit a screenshot of your travel route from a fitness app (Strava, Garmin) or a photo of your public transport ticket/card usage.', 'Ditch the car! Cycle, walk, or take public transport to work/school for a whole week.', 'Medium', 300, '2025-12-10', '2025-12-17', '1764651699_692e72b30c420.jpg', 'Active'),
(4, 4, 3, 15, 'E-Waste Rescue', 'Challenge Overview:\r\nElectronic waste is toxic to our landfills. We are looking for heroes to hunt down old electronics in their drawers and ensure they are recycled responsibly.\r\n\r\nAccepted Items:\r\n- Old mobile phones and tablets.\r\n- Power banks and batteries.\r\n- Charging cables and wires.\r\n- Laptops (Hard drives must be wiped).\r\n\r\nRules &amp; Requirements:\r\n1. Items must be dropped off at an official E-Waste Collection Center (check the map in the app).\r\n2. Do not place items in general recycling bins.\r\n\r\nProof Required:\r\n- Take a selfie at the collection bin depositing your items.', 'Don&#039;t bin it! Find your old cables, batteries, and phones and drop them at a certified collection center.', 'Medium', 500, '2025-12-15', '2025-12-30', '1764651718_692e72c6d093a.png', 'Active'),
(5, 5, 4, 15, 'Local Hero', 'Challenge Overview:\r\nImported food has a high carbon footprint due to transportation. Be a Local Hero by shopping at your local wet market or farmers&#039; market instead of the supermarket.\r\n\r\nRules &amp; Requirements:\r\n1. Purchase at least 3 types of vegetables or fruits grown in Malaysia.\r\n2. Bring your own reusable grocery bag (no plastic bags allowed).\r\n\r\nAdditional Notice:\r\n- Chat with the vendors! Ask them where their produce comes from.\r\n- Wash all produce thoroughly before consumption.\r\n\r\nProof:\r\n- Photo of your haul in a reusable bag at the market.', 'Support local farmers and reduce logistics emissions by buying locally grown produce.', 'Easy', 250, '2026-01-01', '2026-01-07', '1764651736_692e72d82ccb6.png', 'Active'),
(6, 6, 5, 15, 'Tree Planting Day', 'Challenge Overview:\r\nThis is a high-impact event! We are aiming to plant 500 saplings in Shah Alam to help cool the city and increase biodiversity. This requires physical effort and teamwork.\r\n\r\nEvent Details:\r\n- Time: 8:00 AM - 12:00 PM\r\n- Venue: Shah Alam Lake Gardens (Meeting point: Main Entrance)\r\n- Attire: Sports shoes, long pants, and a hat.\r\n\r\nRules &amp; Requirements:\r\n1. Registration is mandatory via the external Google Form (Link below).\r\n2. Participants must stay for the full duration of the planting session.\r\n3. Tools and gloves will be provided.\r\n\r\nSafety Notice:\r\n- Stay hydrated. Water stations are provided.\r\n- Be careful when handling shovels and hoes.', 'Get your hands dirty! Join us for a community tree planting event at the city park.', 'Hard', 1000, '2025-12-20', '2025-12-20', '1764651759_692e72ef9e348.jpeg', 'Active'),
(7, 5, 3, 15, 'Sustainable Fashionista', 'Challenge Overview:\r\nFast fashion is a major polluter. This week, we challenge you to ignore the malls and find a treasure at a thrift store, second-hand shop, or bundle shop in Johor Bahru.\r\n\r\nRules &amp; Requirements:\r\n1. Purchase at least one item of clothing from a second-hand source.\r\n2. The item must be intended for your own use or as a gift.\r\n3. Buying from online thrift stores (like Carousell) is allowed if the seller is local.\r\n\r\nAdditional Notice:\r\n- Wash all second-hand clothes thoroughly before wearing.\r\n- Try to avoid synthetic fibers if possible to reduce microplastic shedding.\r\n\r\nProof of Submission:\r\n- Upload a photo of your &quot;new&quot; outfit and the receipt or shop front.', 'Style doesn&#039;t need to cost the Earth. Buy a second-hand outfit instead of new fast fashion.', 'Easy', 250, '2026-01-10', '2026-01-17', '1764651776_692e730050ab8.jpeg', 'Active'),
(8, 2, 2, 15, 'Compost Champion', 'Challenge Overview:\r\nOrganic waste makes up a huge portion of our landfills, producing harmful methane gas. Start a simple home composting system for your kitchen scraps this month.\r\n\r\nRules &amp; Requirements:\r\n1. Set up a compost bin or pile (can be a simple container under the sink or a garden pile).\r\n2. Collect fruit peels, vegetable scraps, coffee grounds, and eggshells for 7 days.\r\n3. Do not include meat, dairy, or oily foods.\r\n\r\nAdditional Notice:\r\n- Ensure a good mix of &quot;greens&quot; (wet scraps) and &quot;browns&quot; (dry leaves/cardboard) to prevent odors.\r\n- Your plants will love the soil you create!\r\n\r\nProof of Submission:\r\n- Photo of your compost setup with visible organic waste inside.', 'Turn your trash into treasure! Start composting your kitchen scraps for one week.', 'Hard', 600, '2026-02-01', '2026-02-07', '1764651792_692e731025f19.jpeg', 'Active'),
(9, 1, 1, 15, 'Stairs, Not Elevators', 'Challenge Overview:\r\nElevators consume significant electricity in high-rise cities like KL. Burn calories, not electricity, by taking the stairs for an entire work week.\r\n\r\nRules &amp; Requirements:\r\n1. Avoid using elevators or escalators for anything less than 5 floors.\r\n2. This applies to your office, apartment, or shopping malls.\r\n3. If you have medical conditions, please skip this challenge.\r\n\r\nAdditional Notice:\r\n- Wear comfortable shoes.\r\n- This contributes to your daily cardio goals!\r\n\r\nProof of Submission:\r\n- A selfie in the stairwell or a fitness tracker screenshot showing your &quot;floors climbed&quot; count.', 'Burn calories, not electricity. Take the stairs instead of the elevator for 5 days.', 'Medium', 300, '2026-01-20', '2026-01-25', '1764651817_692e73293026c.jpg', 'Active'),
(10, 3, 3, 15, 'Carpool Crew', 'Challenge Overview:\r\nReduce traffic congestion and emissions by sharing a ride. Organize a carpool with colleagues or friends for your commute.\r\n\r\nRules &amp; Requirements:\r\n1. Share a ride with at least 2 other people (3 people total in the car).\r\n2. The trip must be for a commute to work, school, or a major event.\r\n3. Ride-hailing services (GrabShare) count if you select the shared option.\r\n\r\nAdditional Notice:\r\n- Coordinate pick-up times in advance to avoid being late.\r\n- Split the cost of fuel or toll for fairness.\r\n\r\nProof of Submission:\r\n- A group selfie inside the car (safely taken when stopped!) or a screenshot of your shared ride booking.', 'Share the ride, split the cost. Organize a carpool with 3+ people for your commute.', 'Medium', 350, '2026-03-05', '2026-03-05', '1764651834_692e733a29b2c.jpg', 'Active'),
(11, 4, 1, 15, 'Plastic Bottle Brick', 'Challenge Overview:\r\nEcobricks are a way to sequester non-recyclable soft plastics. Stuff a plastic bottle tight with clean, dry plastic wrappers until it becomes a solid building block.\r\n\r\nRules &amp; Requirements:\r\n1. Clean and dry a 1.5L PET bottle.\r\n2. Stuff it with non-recyclable soft plastics (food wrappers, plastic bags) using a stick.\r\n3. The bottle must be rock hard and not dent when squeezed.\r\n\r\nAdditional Notice:\r\n- Do not put paper, glass, or metal inside.\r\n- These bricks can be donated to community garden projects.\r\n\r\nProof of Submission:\r\n- Photo of you standing on your completed Ecobrick to prove its density.', 'Turn soft plastic waste into a solid building block for community projects.', 'Hard', 500, '2026-02-15', '2026-02-28', '1764651853_692e734d79cab.jpeg', 'Active'),
(12, 6, 2, 15, 'Balcony Gardener', 'Challenge Overview:\r\nBring nature back into the city! Plant edible herbs or bee-friendly flowers on your balcony, porch, or windowsill.\r\n\r\nRules &amp; Requirements:\r\n1. Plant at least 3 pots of plants (e.g., Basil, Chili, or Aloe Vera).\r\n2. You must care for them for at least 2 weeks.\r\n3. Using recycled containers as pots earns bonus karma points!\r\n\r\nAdditional Notice:\r\n- Check how much sunlight your balcony gets before choosing plants.\r\n- Water them early in the morning or late evening to reduce evaporation.\r\n\r\nProof of Submission:\r\n- A &quot;Before&quot; and &quot;After&quot; photo of your green space.', 'Grow your own food or flowers. Start a small garden in your home or balcony.', 'Medium', 400, '2026-04-01', '2026-04-14', '1764651872_692e73604b183.jpeg', 'Active'),
(18, 6, 2, 15, 'Beach Cleanup', 'Challenge Overview: \r\n· Protect our marine life by keeping our coastlines clean. Spend a morning collecting litter at the Batu Ferringhi beach to prevent plastic from entering the ocean.\r\n\r\nRules &amp; Requirements:\r\n· Spend at least 1 hour collecting man-made trash (plastic, glass, paper).\r\n· Use proper trash bags and separate recyclable items if possible.\r\n· Do not disturb natural debris like driftwood or shells.\r\n\r\nAdditional Notice:\r\n· Wear protective gloves to handle trash safely.\r\n· Do not pick up sharp objects or hazardous waste (alert local authorities instead).\r\n\r\nProof of Submission:\r\n· A photo of you holding your filled trash bags with the beach in the background.', 'Clean the beach', 'Easy', 200, '2026-05-25', '2026-06-01', '1764651933_692e739d0db95.jpg', 'Active'),
(19, 6, 1, 15, 'Urban Farming', 'Challenge Overview: \r\n· Bring nature back into the concrete jungle! We challenge you to start a small edible garden in your home, balcony, or community space to promote food security and greenery.\r\n\r\nRules &amp; Requirements:\r\n· Plant at least 3 pots of edible herbs (e.g., Basil, Mint, Chili) or vegetables.\r\n· You can use recycled containers (plastic bottles, tin cans) as pots.\r\n· You must actively care for the plants for at least one week.\r\n\r\nAdditional Notice:\r\n·Ensure your plants get adequate sunlight and water.\r\n·Avoid using chemical pesticides; try organic alternatives.\r\n\r\nProof of Submission:\r\n·A &quot;Before&quot; photo of your empty space and an &quot;After&quot; photo showing your new planted pots.', 'Learn to grow food', 'Medium', 300, '2026-07-01', '2026-07-30', '1764651978_692e73ca9892b.jpeg', 'Active'),
(20, 3, 3, 15, 'Car Free Day', 'Challenge Overview: \r\n· Reduce your carbon footprint and traffic congestion by leaving your private car at home for a full day. Experience your city through walking, cycling, or public transit.\r\n\r\nRules &amp; Requirements:\r\n· Do not use a private car or ride-hailing app (Grab/Uber) for your primary commute.\r\n· You must use alternative methods: Walk, Bicycle, Bus, or Train/MRT.\r\n· The challenge applies to your travel between 7:00 AM and 7:00 PM.\r\n\r\nAdditional Notice:\r\n· Wear comfortable shoes and stay hydrated if walking or cycling.\r\n· Check the weather forecast before starting your journey.\r\n\r\nProof of Submission:\r\n· A selfie on the bus/train or a screenshot of your step count/cycling route from a fitness app.', 'Ditch your car', 'Easy', 150, '2026-08-01', '2026-08-08', '1764652099_692e74437ae01.avif', 'Active'),
(21, 4, 5, 1, 'E-Waste Collection', 'Bring old phones and laptops.', 'Recycle old electronics', 'Hard', 500, '2025-09-15', '2025-09-20', 'ewaste.jpg', 'Draft');

-- --------------------------------------------------------

--
-- Table structure for table `city`
--

CREATE TABLE `city` (
  `CityID` int(10) NOT NULL,
  `CityName` text NOT NULL,
  `State` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `city`
--

INSERT INTO `city` (`CityID`, `CityName`, `State`) VALUES
(1, 'Kuala Lumpur', 'Wilayah Persekutuan'),
(2, 'Penang', 'Pulau Pinang'),
(3, 'Johor Bahru', 'Johor'),
(4, 'Ipoh', 'Perak'),
(5, 'Shah Alam', 'Selangor'),
(6, 'Global', '');

-- --------------------------------------------------------

--
-- Table structure for table `moderation`
--

CREATE TABLE `moderation` (
  `Moderation_ID` int(10) NOT NULL,
  `Submission_ID` int(10) NOT NULL,
  `User_ID` int(10) NOT NULL,
  `Action` text NOT NULL,
  `Action_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moderation`
--

INSERT INTO `moderation` (`Moderation_ID`, `Submission_ID`, `User_ID`, `Action`, `Action_date`) VALUES
(6, 20, 24, 'Approved', '2025-12-02');

-- --------------------------------------------------------

--
-- Table structure for table `pointsledger`
--

CREATE TABLE `pointsledger` (
  `LedgeID` int(11) UNSIGNED NOT NULL,
  `Points_Earned` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `Points_Spend` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `Earned_Date` date NOT NULL,
  `Spend_Date` date DEFAULT NULL,
  `RedeemRecord_ID` int(11) DEFAULT NULL,
  `User_ID` int(10) NOT NULL,
  `Submission_ID` int(10) NOT NULL,
  `Team_ID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redeemrecord`
--

CREATE TABLE `redeemrecord` (
  `Reward_ID` int(10) NOT NULL,
  `RedeemRecord_ID` int(10) NOT NULL,
  `Reward_Name` text NOT NULL,
  `Redeem_Quantity` int(4) NOT NULL,
  `Redeem_By` int(10) NOT NULL,
  `Redeem_Date` date NOT NULL,
  `Status` enum('Delivered','On the Way','Pending','') NOT NULL DEFAULT 'Pending',
  `DeliveryDate` datetime NOT NULL DEFAULT current_timestamp(),
  `Proof_Photo` varchar(255) DEFAULT NULL,
  `Admin_Note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `redeemrecord`
--

INSERT INTO `redeemrecord` (`Reward_ID`, `RedeemRecord_ID`, `Reward_Name`, `Redeem_Quantity`, `Redeem_By`, `Redeem_Date`, `Status`, `DeliveryDate`, `Proof_Photo`, `Admin_Note`) VALUES
(1, 1, 'Grocer Voucher', 1, 18, '2025-11-27', '', '2025-12-03 08:49:28', NULL, NULL),
(1, 2, 'Grocer Voucher', 1, 18, '2025-11-27', '', '2025-12-03 08:49:28', NULL, NULL),
(1, 3, 'Grocer Voucher', 1, 21, '2025-12-02', '', '2025-12-03 08:49:28', NULL, NULL),
(1, 4, 'Grocer Voucher', 1, 21, '2025-12-02', '', '2025-12-03 08:49:28', NULL, NULL),
(2, 5, 'Tree Planting Cert', 1, 21, '2025-12-02', '', '2025-12-03 08:49:28', NULL, NULL),
(1, 6, 'Grocer Voucher', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 09:01:05', NULL, 'ECO-123-456'),
(2, 7, 'Tree Planting Cert', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 09:01:10', NULL, 'ECO-123-456'),
(1, 8, 'Grocer Voucher', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 13:53:37', NULL, NULL),
(1, 9, 'Grocer Voucher', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 13:53:39', NULL, '123'),
(2, 10, 'Tree Planting Cert', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 13:58:22', NULL, 'Auto-Generated by System'),
(2, 11, 'Tree Planting Cert', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 14:15:29', NULL, 'Auto-Generated by System'),
(2, 12, 'Tree Planting Cert', 1, 22, '2025-12-03', 'Delivered', '2025-12-03 14:16:33', NULL, 'Auto-Generated by System'),
(2, 13, 'Tree Planting Cert', 1, 22, '2025-12-03', 'Pending', '2025-12-03 14:23:17', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reward`
--

CREATE TABLE `reward` (
  `Reward_ID` int(10) NOT NULL,
  `Reward_name` text NOT NULL,
  `Reward_Photo` varchar(255) NOT NULL,
  `Points_Required` int(4) NOT NULL,
  `Stock` int(4) NOT NULL,
  `Type` enum('Virtual','Physical','','') NOT NULL,
  `Description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward`
--

INSERT INTO `reward` (`Reward_ID`, `Reward_name`, `Reward_Photo`, `Points_Required`, `Stock`, `Type`, `Description`) VALUES
(1, 'Grocer Voucher', '', 20, 93, 'Virtual', 'Voucher to buy grocer'),
(2, 'Tree Planting Cert', '', 1000, 994, 'Physical', 'We Plant A tree using your name');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `Submission_ID` int(10) NOT NULL,
  `Challenge_ID` int(10) NOT NULL,
  `User_ID` int(10) NOT NULL,
  `Team_ID` int(10) DEFAULT NULL,
  `Caption` text NOT NULL,
  `Photo` varchar(255) NOT NULL,
  `image_hash` varchar(255) NOT NULL,
  `Submission_date` date NOT NULL,
  `Status` text NOT NULL,
  `Verification_note` varchar(100) DEFAULT NULL,
  `QR_Code` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`Submission_ID`, `Challenge_ID`, `User_ID`, `Team_ID`, `Caption`, `Photo`, `image_hash`, `Submission_date`, `Status`, `Verification_note`, `QR_Code`) VALUES
(20, 7, 24, 9, 'ssss', '../uploads/1764655698_24_Screenshot20251201211242.png', '15c6b8d15f57abb0cb31babf0d13969081b4435ec7545577e4ce212f91a213f1', '2025-12-02', 'Approved', '', '../qr_code/qr_20_1764655706.png'),
(21, 1, 24, 9, 'ggfgf', '../uploads/1764655736_24_Screenshot20251202124239.png', '892d200e000e8a97c23fcd9a135893b50e9930e9d77a648a0f2a3f42117f433b', '2025-12-02', 'Pending', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `Team_ID` int(10) NOT NULL,
  `Owner_ID` int(10) NOT NULL,
  `Team_code` varchar(255) NOT NULL,
  `Team_name` varchar(20) NOT NULL,
  `Team_Bio` varchar(255) DEFAULT NULL,
  `Total_members` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`Team_ID`, `Owner_ID`, `Team_code`, `Team_name`, `Team_Bio`, `Total_members`) VALUES
(9, 24, '0B6161', 'Banana Team', 'We love Banana! Come and Join us!', 2),
(10, 28, 'A56021', 'Watermelon Team', 'Watermelon is the best! Join us if you agree!', 1),
(11, 30, '3DD714', 'Ave mujica', '一辈子', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int(10) NOT NULL,
  `First_Name` text NOT NULL,
  `Last_Name` text NOT NULL,
  `Caption` text DEFAULT NULL COMMENT '(OPTIONAL)',
  `User_DOB` text NOT NULL,
  `Avatar` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Phone_num` int(20) NOT NULL,
  `Team_ID` int(10) DEFAULT NULL,
  `Point` int(6) UNSIGNED DEFAULT 0,
  `RedeemPoint` int(20) UNSIGNED NOT NULL DEFAULT 0,
  `Password` varchar(256) NOT NULL,
  `Register_Date` varchar(20) NOT NULL,
  `Role` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `First_Name`, `Last_Name`, `Caption`, `User_DOB`, `Avatar`, `Email`, `Phone_num`, `Team_ID`, `Point`, `RedeemPoint`, `Password`, `Register_Date`, `Role`) VALUES
(15, 'John', 'Sam', NULL, '', '', 'jiunhong1234@gmail.c', 0, NULL, 210, 0, '827ccb0eea8a706c4c34a16891f84e7b', '2025-11-20 09:37:58', 0),
(22, 'Baba', 'Lim', NULL, '', '', 'bbl@gmail.com', 0, NULL, 100, 0, '202cb962ac59075b964b07152d234b70', '2025-11-27 02:50:28', 0),
(24, 'Wong', 'Jiun Hong', '', '2005-07-29', '/ecotrip/avatars/20251202_070755_3016.png', 'jiunhong222@gmail.com', 123456789, 9, 3512, 0, '202cb962ac59075b964b07152d234b70', '2025-11-27 03:24:51', 1),
(26, 'Banana', 'Guy', '', '', 'uploads/20251130_102923_1611.jpg', 'bnnguy@gmail.com', 0, NULL, 867, 0, '202cb962ac59075b964b07152d234b70', '2025-11-30 10:27:02', 0),
(27, 'Phang', 'Zhen Thong', '', '', 'uploads/20251130_105705_4579.jpg', 'pzt@gmail.com', 0, 9, 4567, 3567, '202cb962ac59075b964b07152d234b70', '2025-11-30 10:50:09', 0),
(28, 'Chong', 'Yung Onn', NULL, '', '', 'cyo@gmail.com', 0, 10, 521, 0, '202cb962ac59075b964b07152d234b70', '2025-11-30 10:50:23', 0),
(29, 'Young', 'Luo Siong', '', '', '../avatars/20251201_163745_7662.png', 'yls@gmail.com', 0, NULL, 124, 0, '202cb962ac59075b964b07152d234b70', '2025-11-30 10:50:35', 0),
(30, 'Ali', 'Baba', NULL, '', '', 'abb@gmail.com', 0, 11, 241, 0, '202cb962ac59075b964b07152d234b70', '2025-11-30 10:51:28', 0);

-- --------------------------------------------------------

--
-- Table structure for table `verification_token`
--

CREATE TABLE `verification_token` (
  `Token_ID` int(10) NOT NULL,
  `User_ID` int(10) NOT NULL,
  `Token` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `challenge`
--
ALTER TABLE `challenge`
  ADD PRIMARY KEY (`Challenge_ID`),
  ADD KEY `challenge_ibfk_1` (`Category_ID`),
  ADD KEY `challenge_ibfk_3` (`User_ID`),
  ADD KEY `City_ID` (`City_ID`);

--
-- Indexes for table `city`
--
ALTER TABLE `city`
  ADD PRIMARY KEY (`CityID`);

--
-- Indexes for table `moderation`
--
ALTER TABLE `moderation`
  ADD PRIMARY KEY (`Moderation_ID`),
  ADD KEY `moderation_ibfk_1` (`Submission_ID`),
  ADD KEY `moderation_ibfk_2` (`User_ID`);

--
-- Indexes for table `pointsledger`
--
ALTER TABLE `pointsledger`
  ADD PRIMARY KEY (`LedgeID`),
  ADD KEY `Submission_ID` (`Submission_ID`),
  ADD KEY `Team_ID` (`Team_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `RedeemRecord_ID` (`RedeemRecord_ID`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`Submission_ID`),
  ADD KEY `Challenge_ID` (`Challenge_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Team_ID` (`Team_ID`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`Team_ID`),
  ADD UNIQUE KEY `owner_id_unique` (`Owner_ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `email_unique` (`Email`),
  ADD KEY `user_ibfk_1` (`Team_ID`);

--
-- Indexes for table `verification_token`
--
ALTER TABLE `verification_token`
  ADD PRIMARY KEY (`Token_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `challenge`
--
ALTER TABLE `challenge`
  MODIFY `Challenge_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `city`
--
ALTER TABLE `city`
  MODIFY `CityID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `moderation`
--
ALTER TABLE `moderation`
  MODIFY `Moderation_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `Submission_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `Team_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `verification_token`
--
ALTER TABLE `verification_token`
  MODIFY `Token_ID` int(10) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `challenge`
--
ALTER TABLE `challenge`
  ADD CONSTRAINT `challenge_ibfk_1` FOREIGN KEY (`City_ID`) REFERENCES `city` (`CityID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `challenge_ibfk_2` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`CategoryID`) ON UPDATE CASCADE;

--
-- Constraints for table `moderation`
--
ALTER TABLE `moderation`
  ADD CONSTRAINT `moderation_ibfk_1` FOREIGN KEY (`Submission_ID`) REFERENCES `submissions` (`Submission_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `moderation_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pointsledger`
--
ALTER TABLE `pointsledger`
  ADD CONSTRAINT `pointsledger_ibfk_4` FOREIGN KEY (`Submission_ID`) REFERENCES `submissions` (`Submission_ID`),
  ADD CONSTRAINT `pointsledger_ibfk_5` FOREIGN KEY (`Team_ID`) REFERENCES `team` (`Team_ID`),
  ADD CONSTRAINT `pointsledger_ibfk_6` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_4` FOREIGN KEY (`Challenge_ID`) REFERENCES `challenge` (`Challenge_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_5` FOREIGN KEY (`Team_ID`) REFERENCES `team` (`Team_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_6` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `team`
--
ALTER TABLE `team`
  ADD CONSTRAINT `team_ibfk_1` FOREIGN KEY (`Owner_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`Team_ID`) REFERENCES `team` (`Team_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `verification_token`
--
ALTER TABLE `verification_token`
  ADD CONSTRAINT `verification_token_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- 1. 添加 Status 列 (用于上下架)
ALTER TABLE `reward` ADD COLUMN `Status` ENUM('Active', 'Inactive') DEFAULT 'Active';
 
-- 3. (可选) 如果你还没有加 Account_Status 给用户表 (用于封号)，也加上
ALTER TABLE `user` ADD COLUMN `Account_Status` VARCHAR(20) DEFAULT 'Active';