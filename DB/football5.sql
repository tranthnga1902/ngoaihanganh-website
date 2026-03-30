-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 11, 2025 at 01:30 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `football5`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateHeadToHeadStats` (IN `p_team1_id` INT, IN `p_team2_id` INT, IN `p_season_id` INT)   BEGIN
    DECLARE v_matches_played INT DEFAULT 0;
    DECLARE v_team1_wins INT DEFAULT 0;
    DECLARE v_team2_wins INT DEFAULT 0;
    DECLARE v_draws INT DEFAULT 0;
    DECLARE v_team1_goals INT DEFAULT 0;
    DECLARE v_team2_goals INT DEFAULT 0;
    
    -- Đảm bảo team1_id < team2_id để tránh trùng lặp
    IF p_team1_id > p_team2_id THEN
        CALL UpdateHeadToHeadStats(p_team2_id, p_team1_id, p_season_id);
    ELSE
        -- Tính số trận đấu giữa 2 đội
        SELECT COUNT(*) INTO v_matches_played
        FROM Matches 
        WHERE season_id = p_season_id 
        AND status = 'Completed'
        AND ((home_team_id = p_team1_id AND away_team_id = p_team2_id) 
             OR (home_team_id = p_team2_id AND away_team_id = p_team1_id));
        
        -- Tính thắng thua và bàn thắng
        SELECT 
            SUM(CASE 
                WHEN (home_team_id = p_team1_id AND home_team_score > away_team_score) 
                    OR (away_team_id = p_team1_id AND away_team_score > home_team_score) 
                THEN 1 ELSE 0 END) as team1_wins,
            SUM(CASE 
                WHEN (home_team_id = p_team2_id AND home_team_score > away_team_score) 
                    OR (away_team_id = p_team2_id AND away_team_score > home_team_score) 
                THEN 1 ELSE 0 END) as team2_wins,
            SUM(CASE 
                WHEN home_team_score = away_team_score 
                THEN 1 ELSE 0 END) as draws,
            SUM(CASE 
                WHEN home_team_id = p_team1_id THEN home_team_score 
                WHEN away_team_id = p_team1_id THEN away_team_score 
                ELSE 0 END) as team1_goals,
            SUM(CASE 
                WHEN home_team_id = p_team2_id THEN home_team_score 
                WHEN away_team_id = p_team2_id THEN away_team_score 
                ELSE 0 END) as team2_goals
        INTO v_team1_wins, v_team2_wins, v_draws, v_team1_goals, v_team2_goals
        FROM Matches 
        WHERE season_id = p_season_id 
        AND status = 'Completed'
        AND ((home_team_id = p_team1_id AND away_team_id = p_team2_id) 
             OR (home_team_id = p_team2_id AND away_team_id = p_team1_id));
        
        -- Cập nhật hoặc chèn thống kê đối đầu
        INSERT INTO HeadToHead (
            team1_id, team2_id, season_id, matches_played,
            team1_wins, team2_wins, draws, team1_goals, team2_goals
        ) VALUES (
            p_team1_id, p_team2_id, p_season_id, v_matches_played,
            v_team1_wins, v_team2_wins, v_draws, v_team1_goals, v_team2_goals
        )
        ON DUPLICATE KEY UPDATE
            matches_played = v_matches_played,
            team1_wins = v_team1_wins,
            team2_wins = v_team2_wins,
            draws = v_draws,
            team1_goals = v_team1_goals,
            team2_goals = v_team2_goals;
    END IF;
    
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdatePlayerStatsForSeason` (IN `p_player_id` INT, IN `p_season_id` INT)   BEGIN
    DECLARE v_matches_played INT DEFAULT 0;
    DECLARE v_goals INT DEFAULT 0;
    DECLARE v_assists INT DEFAULT 0;
    DECLARE v_yellow_cards INT DEFAULT 0;
    DECLARE v_red_cards INT DEFAULT 0;
    DECLARE v_clean_sheets INT DEFAULT 0;
    DECLARE v_penalties_scored INT DEFAULT 0;
    DECLARE v_penalties_missed INT DEFAULT 0;
    DECLARE v_saves INT DEFAULT 0;
    
    -- Tính số trận đã chơi
    SELECT COUNT(DISTINCT me.match_id) INTO v_matches_played
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id AND m.season_id = p_season_id;
    
    -- Tính số bàn thắng
    SELECT COUNT(*) INTO v_goals
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'goal' 
    AND m.season_id = p_season_id;
    
    -- Tính số kiến tạo
    SELECT COUNT(*) INTO v_assists
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'assist' 
    AND m.season_id = p_season_id;
    
    -- Tính số thẻ vàng
    SELECT COUNT(*) INTO v_yellow_cards
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'yellow_card' 
    AND m.season_id = p_season_id;
    
    -- Tính số thẻ đỏ
    SELECT COUNT(*) INTO v_red_cards
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'red_card' 
    AND m.season_id = p_season_id;
    
    -- Tính số trận giữ sạch lưới (dành cho thủ môn)
    SELECT COUNT(*) INTO v_clean_sheets
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    JOIN players p ON me.player_id = p.player_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'clean_sheet' 
    AND p.position = 'Thủ môn'
    AND m.season_id = p_season_id;
    
    -- Tính số penalty ghi được
    SELECT COUNT(*) INTO v_penalties_scored
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'penalty_scored' 
    AND m.season_id = p_season_id;
    
    -- Tính số penalty hỏng
    SELECT COUNT(*) INTO v_penalties_missed
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'penalty_missed' 
    AND m.season_id = p_season_id;
    
    -- Tính số pha cứu thua (dành cho thủ môn)
    SELECT COUNT(*) INTO v_saves
    FROM matchevents me 
    JOIN matches m ON me.match_id = m.match_id
    JOIN players p ON me.player_id = p.player_id
    WHERE me.player_id = p_player_id 
    AND me.event_type = 'save' 
    AND p.position = 'Thủ môn'
    AND m.season_id = p_season_id;
    
    -- Cập nhật hoặc thêm mới thống kê cầu thủ
    INSERT INTO playerstats (
        player_id, season_id, matches_played, goals, assists, 
        yellow_cards, red_cards, clean_sheets, penalties_scored, 
        penalties_missed, saves, total_goals
    ) VALUES (
        p_player_id, p_season_id, v_matches_played, v_goals, v_assists,
        v_yellow_cards, v_red_cards, v_clean_sheets, v_penalties_scored, 
        v_penalties_missed, v_saves, (v_goals + v_penalties_scored)
    )
    ON DUPLICATE KEY UPDATE
        matches_played = v_matches_played,
        goals = v_goals,
        assists = v_assists,
        yellow_cards = v_yellow_cards,
        red_cards = v_red_cards,
        clean_sheets = v_clean_sheets,
        penalties_scored = v_penalties_scored,
        penalties_missed = v_penalties_missed,
        saves = v_saves,
        total_goals = (v_goals + v_penalties_scored);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateStandings` (IN `p_season_id` INT)   BEGIN
    -- Xóa dữ liệu cũ của mùa giải
    DELETE FROM standings WHERE season_id = p_season_id;
    
    -- Thêm dữ liệu mới từ teamstats với xếp hạng
    INSERT INTO standings (
        season_id, team_id, matches_played, wins, draws, losses,
        goals_for, goals_against, points
    )
    SELECT 
        season_id, team_id, matches_played, wins, draws, losses,
        goals_for, goals_against, points
    FROM teamstats 
    WHERE season_id = p_season_id
    ORDER BY points DESC, (goals_for - goals_against) DESC, goals_for DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateTeamStatsForSeason` (IN `p_team_id` INT, IN `p_season_id` INT)   BEGIN
    DECLARE v_matches_played INT DEFAULT 0;
    DECLARE v_wins INT DEFAULT 0;
    DECLARE v_draws INT DEFAULT 0;
    DECLARE v_losses INT DEFAULT 0;
    DECLARE v_goals_for INT DEFAULT 0;
    DECLARE v_goals_against INT DEFAULT 0;
    DECLARE v_clean_sheets INT DEFAULT 0;
    DECLARE v_points INT DEFAULT 0;
    
    -- Tính số trận đã chơi
    SELECT COUNT(*) INTO v_matches_played
    FROM matches 
    WHERE (home_team_id = p_team_id OR away_team_id = p_team_id) 
    AND season_id = p_season_id 
    AND status = 'Completed';
    
    -- Tính số trận thắng
    SELECT COUNT(*) INTO v_wins
    FROM matches 
    WHERE season_id = p_season_id 
    AND status = 'Completed'
    AND ((home_team_id = p_team_id AND home_team_score > away_team_score) 
    OR (away_team_id = p_team_id AND away_team_score > home_team_score));
    
    -- Tính số trận hòa
    SELECT COUNT(*) INTO v_draws
    FROM matches 
    WHERE (home_team_id = p_team_id OR away_team_id = p_team_id) 
    AND season_id = p_season_id 
    AND status = 'Completed'
    AND home_team_score = away_team_score;
    
    -- Tính số trận thua
    SELECT COUNT(*) INTO v_losses
    FROM matches 
    WHERE season_id = p_season_id 
    AND status = 'Completed'
    AND ((home_team_id = p_team_id AND home_team_score < away_team_score) 
    OR (away_team_id = p_team_id AND away_team_score < home_team_score));
    
    -- Tính số bàn thắng ghi được
    SELECT 
        COALESCE(SUM(CASE WHEN home_team_id = p_team_id THEN home_team_score ELSE 0 END), 0) +
        COALESCE(SUM(CASE WHEN away_team_id = p_team_id THEN away_team_score ELSE 0 END), 0)
    INTO v_goals_for
    FROM matches 
    WHERE (home_team_id = p_team_id OR away_team_id = p_team_id) 
    AND season_id = p_season_id 
    AND status = 'Completed';
    
    -- Tính số bàn thắng bị thủng lưới
    SELECT 
        COALESCE(SUM(CASE WHEN home_team_id = p_team_id THEN away_team_score ELSE 0 END), 0) +
        COALESCE(SUM(CASE WHEN away_team_id = p_team_id THEN home_team_score ELSE 0 END), 0)
    INTO v_goals_against
    FROM matches 
    WHERE (home_team_id = p_team_id OR away_team_id = p_team_id) 
    AND season_id = p_season_id 
    AND status = 'Completed';
    
    -- Tính số trận giữ sạch lưới
    SELECT COUNT(*) INTO v_clean_sheets
    FROM matches 
    WHERE season_id = p_season_id 
    AND status = 'Completed'
    AND ((home_team_id = p_team_id AND away_team_score = 0) 
    OR (away_team_id = p_team_id AND home_team_score = 0));
    
    -- Tính điểm số
    SET v_points = (v_wins * 3) + v_draws;
    
    -- Cập nhật hoặc thêm mới thống kê đội bóng
    INSERT INTO teamstats (
        team_id, season_id, matches_played, wins, draws, losses,
        goals_for, goals_against, clean_sheets, points
    ) VALUES (
        p_team_id, p_season_id, v_matches_played, v_wins, v_draws, v_losses,
        v_goals_for, v_goals_against, v_clean_sheets, v_points
    )
    ON DUPLICATE KEY UPDATE
        matches_played = v_matches_played,
        wins = v_wins,
        draws = v_draws,
        losses = v_losses,
        goals_for = v_goals_for,
        goals_against = v_goals_against,
        clean_sheets = v_clean_sheets,
        points = v_points;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Giải thưởng'),
(2, 'Phát sóng'),
(3, 'Cộng đồng'),
(4, 'Bóng đá giả tưởng'),
(5, 'Lịch thi đấu'),
(6, 'Thông cáo báo chí'),
(7, 'Thảo luận chiến thuật'),
(8, 'Chuyển nhượng'),
(9, 'Thanh thiếu niên'),
(10, 'Tất cả');

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `goal_id` int NOT NULL,
  `match_id` int DEFAULT NULL,
  `player_id` int DEFAULT NULL,
  `team_id` int DEFAULT NULL,
  `goal_time` int DEFAULT NULL,
  `goal_type` enum('Normal','Penalty','FreeKick','OwnGoal') CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT 'Normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `goals`
--

INSERT INTO `goals` (`goal_id`, `match_id`, `player_id`, `team_id`, `goal_time`, `goal_type`) VALUES
(3, 2, 37, 2, 60, 'Normal'),
(4, 2, 35, 2, 65, 'Normal'),
(5, 3, 73, 4, 25, 'Normal'),
(6, 4, 274, 14, 56, 'Normal'),
(7, 4, 275, 14, 87, 'Normal'),
(8, 5, 379, 19, 45, 'Normal'),
(9, 5, 40, 6, 70, 'Normal'),
(10, 6, 113, 6, 86, 'Normal');

-- --------------------------------------------------------

--
-- Table structure for table `headtohead`
--

CREATE TABLE `headtohead` (
  `h2h_id` int NOT NULL,
  `team1_id` int NOT NULL,
  `team2_id` int NOT NULL,
  `season_id` int DEFAULT NULL,
  `matches_played` int DEFAULT '0',
  `team1_wins` int DEFAULT '0',
  `team2_wins` int DEFAULT '0',
  `draws` int DEFAULT '0',
  `team1_goals` int DEFAULT '0',
  `team2_goals` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `headtohead`
--

INSERT INTO `headtohead` (`h2h_id`, `team1_id`, `team2_id`, `season_id`, `matches_played`, `team1_wins`, `team2_wins`, `draws`, `team1_goals`, `team2_goals`) VALUES
(1, 13, 20, 1, 2, 0, 1, 1, 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `managers`
--

CREATE TABLE `managers` (
  `manager_id` int NOT NULL,
  `team_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `information` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `managers`
--

INSERT INTO `managers` (`manager_id`, `team_id`, `name`, `nationality`, `birth_date`, `start_date`, `photo_url`, `information`) VALUES
(1, NULL, 'Erik ten Hag', 'Netherlands', '1970-02-02', '2022-05-23', 'uploads/managers/ten_hag.png', 'Sinh ngày 27 tháng 1 năm 1985, là một huấn luyện viên và cựu cầu thủ bóng đá chuyên nghiệp người Bồ Đào Nha. Ông hiện đang là huấn luyện viên trưởng của câu lạc bộ bóng đá Manchester United ở giải Ngoại hạng Anh.'),
(2, NULL, 'Arne Slot', 'Netherlands', '1978-09-17', '2024-06-01', 'uploads/managers/slot.png', 'Sinh ngày 27 tháng 1 năm 1985, là một huấn luyện viên và cựu cầu thủ bóng đá chuyên nghiệp người Bồ Đào Nha. Ông hiện đang là huấn luyện viên trưởng của câu lạc bộ bóng đá Manchester United ở giải Ngoại hạng Anh.'),
(3, NULL, 'Enzo Maresca', 'Italy', '1980-02-10', '2024-06-03', 'uploads/managers/maresca.png', 'Sinh ngày 10 tháng 2 năm 1980, ông bắt đầu sự nghiệp chuyên nghiệp của mình với câu lạc bộ Anh West Bromwich Albion là huấn luyện viên trưởng của câu lạc bộ Chelsea tại Premier League. '),
(4, 4, 'Mikel Arteta', 'Spain', '1982-03-26', '2019-12-20', 'uploads/managers/arteta.png', 'Sinh ngày 26 tháng 3 năm 1982, là một HLV bóng đá người Tây Ban Nha. Ông hiện là HLV trưởng của Arsenal, từng là cầu thủ chuyên nghiệp.'),
(5, 7, 'Unai Emery', 'Spain', '1971-11-03', '2022-10-24', 'uploads/managers/emery.png', 'Sinh ngày 3 tháng 11 năm 1971, là một HLV bóng đá người Tây Ban Nha. Ông dẫn dắt Aston Villa và nổi tiếng với thành công ở Europa League.'),
(6, 6, 'Andoni Iraola', 'Spain', '1982-06-22', '2023-07-01', 'uploads/managers/iraola.png', 'Sinh ngày 22 tháng 6 năm 1982, là HLV người Tây Ban Nha. Ông hiện dẫn dắt Bournemouth với phong cách bóng đá tấn công.'),
(7, 8, 'Thomas Frank', 'Denmark', '1973-10-09', '2018-10-16', 'uploads/managers/frank.png', 'Sinh ngày 9 tháng 10 năm 1973, là HLV người Đan Mạch. Ông đã giúp Brentford thăng hạng và trụ vững tại Ngoại hạng Anh.'),
(8, 14, 'Fabian Hürzeler', 'Germany', '1993-02-26', '2024-06-15', 'uploads/managers/hurzeler.png', 'Sinh ngày 26 tháng 2 năm 1993, là HLV trẻ tuổi người Đức. Ông hiện là HLV của Brighton & Hove Albion.'),
(9, 9, 'Oliver Glasner', 'Austria', '1974-08-28', '2024-02-19', 'uploads/managers/glasner.png', 'Sinh ngày 28 tháng 8 năm 1974, là HLV người Áo. Ông hiện là HLV của Crystal Palace.'),
(10, 10, 'Sean Dyche', 'England', '1971-06-28', '2023-01-30', 'uploads/managers/dyche.png', 'Sinh ngày 28 tháng 6 năm 1971, là HLV người Anh. Ông dẫn dắt Everton với phong cách thực dụng.'),
(11, 11, 'Marco Silva', 'Portugal', '1977-07-12', '2021-07-01', 'uploads/managers/silva.png', 'Sinh ngày 12 tháng 7 năm 1977, là HLV người Bồ Đào Nha. Ông hiện dẫn dắt Fulham.'),
(12, 12, 'Kieran McKenna', 'Northern Ireland', '1986-05-14', '2021-12-16', '/images/managers/mckenna.png', 'Sinh ngày 14 tháng 5 năm 1986, là HLV trẻ người Bắc Ireland. Ông đã giúp Ipswich Town thăng hạng.'),
(13, 13, 'Steve Cooper', 'Wales', '1979-12-10', '2024-07-01', 'uploads/managers/cooper.png', 'Sinh ngày 10 tháng 12 năm 1979, là HLV người xứ Wales. Ông hiện dẫn dắt Leicester City.'),
(14, 5, 'Pep Guardiola', 'Spain', '1971-01-18', '2016-07-01', 'uploads/managers/guardiola.png', 'Sinh ngày 18 tháng 1 năm 1971, là HLV người Tây Ban Nha. Ông là một trong những HLV xuất sắc nhất thế giới, dẫn dắt Manchester City.'),
(15, 19, 'Eddie Howe', 'England', '1977-11-29', '2021-11-08', 'uploads/managers/howe.png', 'Sinh ngày 29 tháng 11 năm 1977, là HLV người Anh. Ông dẫn dắt Newcastle United với phong cách bóng đá tấn công.'),
(16, 20, 'Nuno Espírito Santo', 'Portugal', '1974-01-25', '2023-12-20', 'uploads/managers/nuno.png', 'Sinh ngày 25 tháng 1 năm 1974, thường được biết đến với tên gọi đơn giản là Nuno hay Nuno Santo, là một huấn luyện viên bóng đá người Bồ Đào Nha và là cựu cầu thủ bóng đá từng chơi ở vị trí thủ môn.'),
(17, 15, 'Russell Martin', 'Scotland', '1986-01-04', '2023-06-21', 'uploads/managers/martin.png', 'Sinh ngày 4 tháng 1 năm 1986, là HLV người Scotland. Ông giúp Southampton thăng hạng và hiện dẫn dắt đội.'),
(18, 16, 'Ange Postecoglou', 'Australia', '1965-08-27', '2023-07-01', 'uploads/managers/postecoglou.png', 'Sinh ngày 27 tháng 8 năm 1965, là HLV người Úc gốc Hy Lạp. Ông hiện dẫn dắt Tottenham Hotspur.'),
(19, 17, 'Julen Lopetegui', 'Spain', '1966-08-28', '2024-07-01', 'uploads/managers/lopetegui.png', 'Sinh ngày 28 tháng 8 năm 1966, là HLV người Tây Ban Nha. Ông hiện là HLV của West Ham United.'),
(20, 18, 'Gary O Neil', 'England', '1983-05-18', '2023-08-09', 'uploads/managers/oneil.png', 'Sinh ngày 18 tháng 5 năm 1983, là HLV người Anh. Ông hiện dẫn dắt Wolverhampton Wanderers.');

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `match_id` int NOT NULL,
  `home_team_id` int DEFAULT NULL,
  `away_team_id` int DEFAULT NULL,
  `season_id` int DEFAULT NULL,
  `match_date` datetime NOT NULL,
  `stadium_id` int DEFAULT NULL,
  `home_team_score` int DEFAULT '0',
  `away_team_score` int DEFAULT '0',
  `status` enum('Scheduled','Completed','Postponed') CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT 'Scheduled',
  `round` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`match_id`, `home_team_id`, `away_team_id`, `season_id`, `match_date`, `stadium_id`, `home_team_score`, `away_team_score`, `status`, `round`) VALUES
(1, 1, 11, 1, '2024-08-16 02:00:00', 1, 1, 0, 'Completed', '1'),
(2, 12, 2, 1, '2024-08-17 18:30:00', 27, 0, 2, 'Completed', '1'),
(3, 4, 18, 1, '2024-08-17 21:00:00', 4, 2, 0, 'Completed', '1'),
(4, 10, 14, 1, '2024-08-17 21:00:00', 25, 0, 3, 'Completed', '1'),
(5, 19, 15, 1, '2024-08-17 21:00:00', 33, 1, 0, 'Completed', '1'),
(6, 20, 6, 1, '2024-08-17 21:00:00', 35, 1, 1, 'Completed', '1'),
(7, 17, 7, 1, '2024-08-17 23:30:00', 31, 1, 2, 'Completed', '1'),
(8, 8, 9, 1, '2024-08-18 20:00:00', 23, 2, 1, 'Completed', '1'),
(9, 3, 5, 1, '2024-08-18 22:30:00', 3, 0, 2, 'Completed', '1'),
(10, 13, 16, 1, '2024-08-19 02:00:00', 28, 1, 1, 'Completed', '1'),
(262, 14, 6, 1, '2025-02-25 02:30:00', 34, 2, 1, 'Completed', '27'),
(263, 9, 7, 1, '2025-02-25 02:30:00', 24, 4, 1, 'Completed', '27'),
(264, 18, 11, 1, '2025-02-25 02:30:00', 32, 1, 2, 'Completed', '27'),
(265, 3, 15, 1, '2025-02-25 03:15:00', 3, 4, 0, 'Completed', '27'),
(266, 16, 5, 1, '2025-02-26 02:30:00', 30, 0, 1, 'Completed', '27'),
(267, 20, 4, 1, '2025-02-26 02:30:00', 35, 0, 0, 'Completed', '27'),
(268, 1, 12, 1, '2025-02-26 02:30:00', 1, 3, 2, 'Completed', '27'),
(269, 8, 10, 1, '2025-02-26 02:30:00', 23, 1, 1, 'Completed', '27'),
(270, 2, 19, 1, '2025-02-26 03:15:00', 2, 2, 0, 'Completed', '27'),
(271, 17, 13, 1, '2025-02-27 03:00:00', 31, 2, 0, 'Completed', '27'),
(272, 20, 5, 1, '2025-03-08 19:30:00', 35, 1, 0, 'Completed', '28'),
(273, 2, 15, 1, '2025-03-08 22:00:00', 2, 3, 1, 'Completed', '28'),
(274, 14, 11, 1, '2025-03-08 22:00:00', 34, 2, 1, 'Completed', '28'),
(275, 9, 12, 1, '2025-03-08 22:00:00', 24, 1, 0, 'Completed', '28'),
(276, 8, 7, 1, '2025-03-08 00:30:00', 23, 0, 1, 'Completed', '28'),
(277, 18, 10, 1, '2025-03-08 03:00:00', 32, 1, 1, 'Completed', '28'),
(278, 3, 13, 1, '2025-03-09 21:00:00', 3, 1, 0, 'Completed', '28'),
(279, 16, 6, 1, '2025-03-09 21:00:00', 30, 2, 2, 'Completed', '28'),
(280, 1, 4, 1, '2025-03-09 23:30:00', 1, 1, 1, 'Completed', '28'),
(281, 17, 19, 1, '2025-03-10 03:00:00', 31, 0, 1, 'Completed', '28'),
(282, 10, 17, 1, '2025-03-15 22:00:00', 25, 1, 1, 'Completed', '29'),
(283, 15, 18, 1, '2025-03-15 22:00:00', 29, 1, 2, 'Completed', '29'),
(284, 12, 20, 1, '2025-03-15 22:00:00', 27, 2, 4, 'Completed', '29'),
(285, 5, 14, 1, '2025-03-15 22:00:00', 5, 2, 2, 'Completed', '29'),
(286, 6, 8, 1, '2025-03-15 00:30:00', 21, 1, 2, 'Completed', '29'),
(287, 4, 3, 1, '2025-03-16 20:30:00', 4, 1, 0, 'Completed', '29'),
(288, 11, 16, 1, '2025-03-16 20:30:00', 26, 2, 0, 'Completed', '29'),
(289, 13, 1, 1, '2025-03-16 02:00:00', 28, 0, 3, 'Completed', '29'),
(290, 4, 11, 1, '2025-04-01 01:45:00', 4, 2, 1, 'Completed', '30'),
(291, 18, 17, 1, '2025-04-01 01:45:00', 32, 1, 0, 'Completed', '30'),
(292, 20, 1, 1, '2025-04-01 02:00:00', 35, 1, 0, 'Completed', '30'),
(293, 19, 8, 1, '2025-04-02 01:45:00', 33, 2, 1, 'Completed', '30'),
(294, 5, 13, 1, '2025-04-02 01:45:00', 5, 2, 0, 'Completed', '30'),
(295, 15, 9, 1, '2025-04-02 01:45:00', 29, 1, 1, 'Completed', '30'),
(296, 6, 12, 1, '2025-04-02 01:45:00', 21, 1, 2, 'Completed', '30'),
(297, 14, 7, 1, '2025-04-02 01:45:00', 34, 0, 3, 'Completed', '30'),
(298, 2, 10, 1, '2025-04-02 02:00:00', 2, 1, 0, 'Completed', '30'),
(299, 3, 16, 1, '2025-04-03 02:00:00', 3, 1, 0, 'Completed', '30'),
(300, 10, 4, 1, '2025-04-05 18:30:00', 25, 1, 1, 'Completed', '31'),
(301, 9, 14, 1, '2025-04-05 21:00:00', 24, 2, 1, 'Completed', '31'),
(302, 12, 18, 1, '2025-04-05 21:00:00', 27, 1, 2, 'Completed', '31'),
(303, 17, 6, 1, '2025-04-05 21:00:00', 31, 2, 2, 'Completed', '31'),
(304, 7, 20, 1, '2025-04-05 23:30:00', 22, 2, 1, 'Completed', '31'),
(305, 11, 2, 1, '2025-04-06 20:00:00', 26, 3, 2, 'Completed', '31'),
(306, 8, 3, 1, '2025-04-06 20:00:00', 23, 0, 0, 'Completed', '31'),
(307, 16, 15, 1, '2025-04-06 20:00:00', 30, 3, 1, 'Completed', '31'),
(308, 1, 5, 1, '2025-04-06 22:30:00', 1, 0, 0, 'Completed', '31'),
(309, 13, 19, 1, '2025-04-07 02:00:00', 28, 0, 3, 'Completed', '31'),
(310, 5, 9, 1, '2025-04-12 18:30:00', 5, 5, 2, 'Completed', '32'),
(311, 14, 13, 1, '2025-04-12 21:00:00', 34, 2, 2, 'Completed', '32'),
(312, 15, 7, 1, '2025-04-12 21:00:00', 29, 0, 3, 'Completed', '32'),
(313, 20, 10, 1, '2025-04-12 21:00:00', 35, 0, 1, 'Completed', '32'),
(314, 4, 8, 1, '2025-04-12 23:30:00', 4, 1, 1, 'Completed', '32'),
(315, 2, 17, 1, '2025-04-13 20:00:00', 2, 2, 1, 'Completed', '32'),
(316, 3, 12, 1, '2025-04-13 20:00:00', 3, 2, 2, 'Completed', '32'),
(317, 18, 16, 1, '2025-04-13 20:00:00', 32, 4, 2, 'Completed', '32'),
(318, 19, 1, 1, '2025-04-13 22:30:00', 33, 4, 1, 'Completed', '32'),
(319, 6, 11, 1, '2025-04-14 02:00:00', 21, 1, 0, 'Completed', '32'),
(320, 19, 9, 1, '2025-04-16 01:30:00', 33, 5, 0, 'Completed', '29'),
(321, 10, 5, 1, '2025-04-19 21:00:00', 25, 0, 2, 'Completed', '33'),
(322, 17, 15, 1, '2025-04-19 21:00:00', 31, 1, 1, 'Completed', '33'),
(323, 9, 6, 1, '2025-04-19 21:00:00', 24, 0, 0, 'Completed', '33'),
(324, 8, 14, 1, '2025-04-19 21:00:00', 23, 4, 2, 'Completed', '33'),
(325, 7, 19, 1, '2025-04-19 23:30:00', 22, 4, 1, 'Completed', '33'),
(326, 11, 3, 1, '2025-04-20 20:00:00', 26, 1, 2, 'Completed', '33'),
(327, 12, 4, 1, '2025-04-20 20:00:00', 27, 0, 4, 'Completed', '33'),
(328, 1, 18, 1, '2025-04-20 20:00:00', 1, 0, 1, 'Completed', '33'),
(329, 13, 2, 1, '2025-04-20 22:30:00', 28, 0, 1, 'Completed', '33'),
(330, 16, 20, 1, '2025-04-21 02:00:00', 30, 1, 2, 'Completed', '33'),
(331, 5, 7, 1, '2025-04-22 02:00:00', 5, 2, 1, 'Completed', '34'),
(332, 4, 9, 1, '2025-04-23 02:00:00', 4, 2, 2, 'Completed', '34'),
(333, 3, 10, 1, '2025-04-26 18:30:00', 3, 1, 0, 'Completed', '34'),
(334, 14, 17, 1, '2025-04-26 21:00:00', 34, 3, 2, 'Completed', '34'),
(335, 18, 13, 1, '2025-04-26 21:00:00', 32, 3, 0, 'Completed', '34'),
(336, 15, 11, 1, '2025-04-26 21:00:00', 29, 1, 2, 'Completed', '34'),
(337, 19, 12, 1, '2025-04-26 21:00:00', 33, 3, 0, 'Completed', '34'),
(338, 6, 1, 1, '2025-04-27 20:00:00', 21, 1, 1, 'Completed', '34'),
(339, 2, 16, 1, '2025-04-27 22:30:00', 2, 5, 1, 'Completed', '34'),
(340, 20, 8, 1, '2025-05-01 01:30:00', 35, 0, 2, 'Completed', '34'),
(341, 5, 18, 1, '2025-05-02 02:00:00', 5, 1, 0, 'Completed', '35'),
(342, 7, 11, 1, '2025-05-03 18:30:00', 22, 1, 0, 'Completed', '35'),
(343, 13, 15, 1, '2025-05-03 21:00:00', 28, 1, 0, 'Completed', '35'),
(344, 10, 12, 1, '2025-05-03 21:00:00', 25, 2, 2, 'Completed', '35'),
(345, 4, 6, 1, '2025-05-03 23:30:00', 4, 1, 2, 'Completed', '35'),
(346, 14, 19, 1, '2025-05-04 20:00:00', 34, 1, 1, 'Completed', '35'),
(347, 17, 16, 1, '2025-05-04 20:00:00', 31, 1, 1, 'Completed', '35'),
(348, 8, 1, 1, '2025-05-04 20:00:00', 23, 4, 3, 'Completed', '35'),
(349, 3, 2, 1, '2025-05-04 22:30:00', 3, 3, 1, 'Completed', '35'),
(350, 9, 20, 1, '2025-05-05 02:00:00', 24, 1, 1, 'Completed', '35'),
(351, 12, 8, 1, '2025-05-10 21:00:00', 27, 0, 1, 'Completed', '36'),
(352, 11, 10, 1, '2025-05-10 21:00:00', 26, 1, 3, 'Completed', '36'),
(353, 18, 14, 1, '2025-05-10 21:00:00', 32, 0, 2, 'Completed', '36'),
(354, 15, 5, 1, '2025-05-10 21:00:00', 29, 0, 0, 'Completed', '36'),
(355, 6, 7, 1, '2025-05-10 23:30:00', 21, 0, 1, 'Completed', '36'),
(356, 19, 3, 1, '2025-05-11 18:00:00', 33, 2, 0, 'Completed', '36'),
(357, 20, 13, 1, '2025-05-11 20:15:00', 35, 2, 2, 'Completed', '36'),
(358, 16, 9, 1, '2025-05-11 20:15:00', 30, 0, 2, 'Completed', '36'),
(359, 1, 17, 1, '2025-05-11 20:15:00', 1, 0, 2, 'Completed', '36'),
(360, 2, 4, 1, '2025-05-11 22:30:00', 2, 3, 2, 'Completed', '36'),
(361, 19, 3, 1, '2025-05-11 18:00:00', 33, 0, 1, 'Completed', '37'),
(363, 20, 13, 1, '2025-05-11 20:15:00', 35, 1, 0, 'Completed', '37'),
(366, 7, 16, 1, '2025-05-17 01:30:00', 22, 0, 0, 'Scheduled', '37'),
(367, 3, 1, 1, '2025-05-17 02:15:00', 3, 0, 0, 'Scheduled', '37'),
(368, 10, 15, 1, '2025-05-18 18:00:00', 25, 0, 0, 'Scheduled', '37'),
(370, 8, 11, 1, '2025-05-18 21:00:00', 23, 0, 0, 'Scheduled', '37'),
(371, 13, 12, 1, '2025-05-18 21:00:00', 28, 0, 0, 'Scheduled', '37'),
(373, 14, 2, 1, '2025-05-20 02:00:00', 34, 0, 0, 'Scheduled', '38'),
(374, 9, 18, 1, '2025-05-21 02:00:00', 24, 0, 0, 'Scheduled', '38'),
(375, 5, 6, 1, '2025-05-21 02:00:00', 5, 0, 0, 'Scheduled', '38'),
(377, 11, 5, 1, '2025-05-25 22:00:00', 26, 1, 1, 'Completed', '38'),
(379, 2, 9, 1, '2025-05-25 22:00:00', 2, 2, 0, 'Completed', '2'),
(380, 1, 7, 1, '2025-05-25 22:00:00', 1, 1, 0, 'Completed', '2'),
(381, 19, 10, 1, '2025-05-25 22:00:00', 33, 0, 1, 'Completed', '2'),
(382, 20, 3, 1, '2025-05-25 22:00:00', 35, 2, 4, 'Completed', '2'),
(383, 15, 4, 1, '2025-05-25 22:00:00', 29, 1, 0, 'Completed', '2'),
(386, 8, 4, 1, '2025-06-19 06:57:00', 2, 0, 0, 'Scheduled', '38'),
(387, 4, 7, 1, '2025-06-19 09:48:00', 4, 0, 0, 'Scheduled', '38');

--
-- Triggers `matches`
--
DELIMITER $$
CREATE TRIGGER `tr_matches_after_update` AFTER UPDATE ON `matches` FOR EACH ROW BEGIN
    -- Chỉ cập nhật nếu tỷ số thay đổi hoặc trạng thái chuyển thành Hoàn thành
    IF (OLD.home_team_score != NEW.home_team_score 
        OR OLD.away_team_score != NEW.away_team_score
        OR OLD.status != NEW.status) THEN
        
        -- Cập nhật thống kê cho cả hai đội
        CALL UpdateTeamStatsForSeason(NEW.home_team_id, NEW.season_id);
        CALL UpdateTeamStatsForSeason(NEW.away_team_id, NEW.season_id);
        
        -- Cập nhật thống kê đối đầu
        IF NEW.home_team_id < NEW.away_team_id THEN
            CALL UpdateHeadToHeadStats(NEW.home_team_id, NEW.away_team_id, NEW.season_id);
        ELSE
            CALL UpdateHeadToHeadStats(NEW.away_team_id, NEW.home_team_id, NEW.season_id);
        END IF;
        
        -- Cập nhật bảng xếp hạng
        CALL UpdateStandings(NEW.season_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `matchevents`
--

CREATE TABLE `matchevents` (
  `event_id` int NOT NULL,
  `match_id` int NOT NULL,
  `team_id` int NOT NULL,
  `player_id` int DEFAULT NULL,
  `event_type` enum('goal','assist','yellow_card','red_card','clean_sheet','penalty_scored','penalty_missed','save','own_goal') DEFAULT NULL,
  `minute` int NOT NULL,
  `is_home` tinyint(1) NOT NULL,
  `note` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matchevents`
--

INSERT INTO `matchevents` (`event_id`, `match_id`, `team_id`, `player_id`, `event_type`, `minute`, `is_home`, `note`) VALUES
(1, 351, 8, 151, 'goal', 17, 0, NULL),
(2, 351, 12, 228, 'yellow_card', 16, 1, NULL),
(3, 351, 8, 149, 'yellow_card', 16, 0, NULL),
(4, 351, 8, 153, 'yellow_card', 69, 0, NULL),
(5, 351, 8, 154, 'yellow_card', 82, 0, NULL),
(6, 351, 12, 232, 'yellow_card', 82, 1, NULL),
(7, 351, 8, 148, 'yellow_card', 90, 0, NULL),
(8, 352, 11, 211, 'goal', 16, 1, NULL),
(9, 352, 10, 185, 'goal', 45, 0, NULL),
(10, 352, 10, 184, 'goal', 69, 0, NULL),
(11, 352, 10, 183, 'goal', 73, 0, NULL),
(12, 352, 11, 210, 'yellow_card', 55, 1, NULL),
(13, 353, 14, 274, 'goal', 27, 0, NULL),
(14, 353, 14, 275, 'goal', 84, 0, NULL),
(15, 353, 18, 353, 'yellow_card', 26, 1, NULL),
(16, 353, 14, 280, 'yellow_card', 45, 0, NULL),
(17, 355, 13, 246, 'goal', 15, 0, NULL),
(18, 355, 20, 385, 'goal', 24, 1, NULL),
(19, 355, 20, 389, 'goal', 55, 1, NULL),
(20, 355, 13, 407, 'goal', 80, 0, NULL),
(21, 355, 13, 244, 'yellow_card', 38, 0, NULL),
(22, 355, 13, 257, 'yellow_card', 57, 0, NULL),
(23, 355, 20, 408, 'yellow_card', 59, 1, NULL),
(24, 355, 20, 384, 'yellow_card', 68, 1, NULL),
(25, 355, 20, 397, 'yellow_card', 72, 1, NULL),
(26, 355, 13, 407, 'yellow_card', 78, 0, NULL),
(27, 356, 19, 372, 'goal', 2, 1, NULL),
(28, 356, 19, 377, 'goal', 90, 1, NULL),
(29, 356, 3, 54, 'red_card', 35, 0, NULL),
(30, 356, 3, 51, 'yellow_card', 45, 0, NULL),
(31, 356, 19, 366, 'yellow_card', 52, 1, NULL),
(32, 356, 19, 374, 'yellow_card', 63, 1, NULL),
(33, 356, 19, 377, 'yellow_card', 84, 1, NULL),
(34, 356, 19, 369, 'yellow_card', 88, 1, NULL),
(35, 356, 3, 48, 'yellow_card', 90, 0, NULL),
(36, 357, 13, 246, 'goal', 15, 0, NULL),
(37, 357, 20, 385, 'goal', 24, 1, NULL),
(38, 357, 20, 389, 'goal', 55, 1, NULL),
(39, 357, 13, 407, 'goal', 80, 0, NULL),
(40, 357, 13, 244, 'yellow_card', 38, 0, NULL),
(42, 357, 20, 408, 'yellow_card', 59, 1, NULL),
(43, 357, 20, 384, 'yellow_card', 68, 1, NULL),
(44, 357, 20, 397, 'yellow_card', 72, 1, NULL),
(45, 357, 13, 407, 'yellow_card', 78, 0, NULL),
(46, 358, 9, 168, 'goal', 45, 0, NULL),
(47, 358, 9, 168, 'goal', 47, 0, NULL),
(48, 358, 9, 167, 'yellow_card', 46, 0, NULL),
(49, 358, 16, 315, 'yellow_card', 36, 1, NULL),
(50, 359, 17, 328, 'goal', 25, 0, NULL),
(51, 359, 17, 331, 'goal', 57, 0, NULL),
(52, 359, 17, 323, 'yellow_card', 57, 0, NULL),
(53, 360, 2, 36, 'goal', 20, 1, NULL),
(54, 360, 2, 33, 'goal', 21, 1, NULL),
(55, 360, 2, 39, 'yellow_card', 60, 1, NULL),
(56, 360, 4, 401, 'yellow_card', 34, 0, NULL),
(57, 360, 4, 78, 'yellow_card', 43, 0, NULL),
(58, 360, 4, 76, 'goal', 47, 0, NULL),
(59, 360, 4, 401, 'goal', 70, 0, NULL),
(60, 360, 4, 401, 'red_card', 79, 0, NULL),
(61, 341, 5, 97, 'goal', 67, 1, NULL),
(62, 342, 7, 133, 'goal', 54, 1, NULL),
(65, 344, 10, 195, 'goal', 45, 1, NULL),
(66, 344, 12, 231, 'goal', 78, 0, NULL),
(67, 345, 4, 70, 'goal', 30, 1, NULL),
(68, 345, 6, 114, 'goal', 65, 0, NULL),
(69, 345, 6, 115, 'goal', 82, 0, NULL),
(70, 346, 19, 380, 'goal', 50, 0, NULL),
(71, 346, 14, 271, 'goal', 68, 1, NULL),
(72, 347, 17, 331, 'goal', 25, 1, NULL),
(73, 347, 16, 317, 'goal', 80, 0, NULL),
(74, 348, 8, 141, 'goal', 12, 1, NULL),
(75, 348, 8, 141, 'goal', 37, 1, NULL),
(76, 348, 8, 154, 'goal', 66, 1, NULL),
(77, 348, 8, 153, 'goal', 90, 1, NULL),
(78, 349, 3, 52, 'goal', 18, 1, NULL),
(79, 349, 3, 51, 'goal', 45, 1, NULL),
(80, 349, 3, 47, 'goal', 72, 1, NULL),
(81, 349, 2, 35, 'goal', 88, 0, NULL),
(82, 350, 9, 169, 'goal', 34, 1, NULL),
(83, 350, 20, 388, 'goal', 76, 0, NULL),
(84, 341, 5, 84, 'yellow_card', 54, 1, NULL),
(85, 342, 7, 125, 'yellow_card', 72, 1, NULL),
(87, 344, 10, 188, 'yellow_card', 82, 1, NULL),
(88, 345, 6, 167, 'yellow_card', 39, 0, NULL),
(89, 346, 14, 265, 'yellow_card', 60, 1, NULL),
(90, 347, 16, 306, 'yellow_card', 78, 0, NULL),
(91, 348, 1, 13, 'yellow_card', 42, 0, NULL),
(92, 349, 3, 56, 'yellow_card', 55, 1, NULL),
(93, 350, 9, 176, 'yellow_card', 68, 1, NULL),
(94, 331, 5, 94, 'goal', 7, 1, NULL),
(95, 331, 7, 133, 'goal', 18, 0, NULL),
(96, 331, 5, 99, 'goal', 90, 1, NULL),
(97, 332, 4, 70, 'goal', 32, 1, NULL),
(98, 332, 9, 168, 'goal', 68, 0, NULL),
(99, 332, 4, 75, 'goal', 75, 1, NULL),
(100, 332, 9, 167, 'goal', 82, 0, NULL),
(101, 333, 3, 51, 'goal', 55, 1, NULL),
(102, 334, 14, 271, 'goal', 12, 1, NULL),
(103, 334, 17, 331, 'goal', 30, 0, NULL),
(104, 334, 14, 270, 'goal', 61, 1, NULL),
(105, 334, 17, 326, 'goal', 74, 0, NULL),
(106, 334, 14, 271, 'goal', 89, 1, NULL),
(107, 335, 18, 350, 'goal', 20, 1, NULL),
(108, 335, 18, 353, 'goal', 50, 1, NULL),
(109, 335, 18, 352, 'goal', 85, 1, NULL),
(110, 336, 15, 335, 'goal', 43, 1, NULL),
(111, 336, 11, 110, 'goal', 58, 0, NULL),
(112, 336, 11, 112, 'goal', 87, 0, NULL),
(113, 337, 19, 373, 'goal', 26, 1, NULL),
(114, 337, 19, 377, 'goal', 63, 1, NULL),
(115, 337, 19, 380, 'goal', 79, 1, NULL),
(116, 338, 6, 102, 'goal', 35, 1, NULL),
(117, 338, 1, 3, 'goal', 66, 0, NULL),
(118, 339, 2, 35, 'goal', 10, 1, NULL),
(119, 339, 2, 34, 'goal', 28, 1, NULL),
(120, 339, 2, 33, 'goal', 48, 1, NULL),
(121, 339, 2, 36, 'goal', 72, 1, NULL),
(122, 339, 2, 28, 'goal', 90, 1, NULL),
(123, 339, 16, 317, 'goal', 57, 0, NULL),
(124, 340, 8, 160, 'goal', 33, 0, NULL),
(125, 340, 8, 154, 'goal', 77, 0, NULL),
(126, 331, 5, 84, 'yellow_card', 24, 1, NULL),
(127, 331, 7, 125, 'yellow_card', 45, 0, NULL),
(128, 331, 5, 92, 'yellow_card', 66, 1, NULL),
(129, 332, 4, 74, 'yellow_card', 38, 1, NULL),
(130, 332, 9, 167, 'yellow_card', 59, 0, NULL),
(131, 333, 3, 52, 'yellow_card', 72, 1, NULL),
(132, 334, 14, 265, 'yellow_card', 17, 1, NULL),
(133, 334, 17, 333, 'yellow_card', 36, 0, NULL),
(134, 334, 14, 275, 'yellow_card', 48, 1, NULL),
(135, 335, 18, 346, 'yellow_card', 62, 1, NULL),
(136, 336, 15, 284, 'yellow_card', 29, 1, NULL),
(137, 336, 11, 220, 'yellow_card', 54, 0, NULL),
(138, 337, 19, 371, 'yellow_card', 81, 1, NULL),
(139, 338, 6, 108, 'yellow_card', 40, 1, NULL),
(140, 338, 1, 13, 'yellow_card', 65, 0, NULL),
(141, 339, 2, 24, 'yellow_card', 22, 1, NULL),
(142, 339, 16, 306, 'yellow_card', 51, 0, NULL),
(143, 340, 8, 150, 'yellow_card', 74, 0, NULL),
(144, 310, 5, 97, 'goal', 12, 1, NULL),
(145, 310, 5, 93, 'goal', 28, 1, NULL),
(146, 310, 5, 95, 'goal', 45, 1, NULL),
(147, 310, 5, 92, 'goal', 63, 1, NULL),
(148, 310, 5, 91, 'goal', 78, 1, NULL),
(149, 310, 9, 168, 'goal', 34, 0, NULL),
(150, 310, 9, NULL, 'goal', 70, 0, NULL),
(151, 311, 14, 271, 'goal', 22, 1, NULL),
(152, 311, 14, NULL, 'goal', 55, 1, NULL),
(153, 311, 13, 316, 'goal', 38, 0, NULL),
(154, 311, 13, 253, 'goal', 72, 0, NULL),
(155, 312, 7, 133, 'goal', 18, 0, NULL),
(156, 312, 7, 134, 'goal', 39, 0, NULL),
(157, 312, 7, NULL, 'goal', 58, 0, NULL),
(158, 313, 10, 195, 'goal', 65, 0, NULL),
(159, 314, 4, 70, 'goal', 30, 1, NULL),
(160, 314, 8, NULL, 'goal', 75, 0, NULL),
(161, 315, 2, 35, 'goal', 10, 1, NULL),
(162, 315, 2, 34, 'goal', 48, 1, NULL),
(163, 315, 17, 331, 'goal', 67, 0, NULL),
(164, 316, 3, NULL, 'goal', 25, 1, NULL),
(165, 316, 3, 51, 'goal', 55, 1, NULL),
(166, 316, 12, 231, 'goal', 42, 0, NULL),
(167, 316, 12, 233, 'goal', 78, 0, NULL),
(168, 317, 18, NULL, 'goal', 20, 1, NULL),
(169, 317, 18, 353, 'goal', 50, 1, NULL),
(170, 317, 18, 352, 'goal', 85, 1, NULL),
(171, 317, 16, 317, 'goal', 43, 0, NULL),
(172, 317, 16, 318, 'goal', 58, 0, NULL),
(173, 318, 19, NULL, 'goal', 26, 1, NULL),
(174, 318, 19, 377, 'goal', 63, 1, NULL),
(175, 318, 19, 380, 'goal', 79, 1, NULL),
(176, 318, 1, NULL, 'goal', 55, 0, NULL),
(177, 319, 6, NULL, 'goal', 35, 1, NULL),
(178, 320, 19, 379, 'goal', 10, 1, NULL),
(179, 320, 19, NULL, 'goal', 28, 1, NULL),
(180, 320, 19, 364, 'goal', 48, 1, NULL),
(181, 320, 19, 365, 'goal', 72, 1, NULL),
(182, 320, 19, 371, 'goal', 90, 1, NULL),
(183, 310, 5, 92, 'yellow_card', 22, 1, NULL),
(184, 310, 9, 171, 'yellow_card', 45, 0, NULL),
(185, 311, 14, 265, 'yellow_card', 38, 1, NULL),
(186, 311, 13, 251, 'yellow_card', 59, 0, NULL),
(187, 312, 7, 125, 'yellow_card', 72, 0, NULL),
(188, 313, 10, 185, 'yellow_card', 17, 0, NULL),
(189, 314, 4, 74, 'yellow_card', 36, 1, NULL),
(190, 314, 8, 150, 'yellow_card', 48, 0, NULL),
(191, 315, 2, 24, 'yellow_card', 62, 1, NULL),
(192, 315, 17, 326, 'yellow_card', 29, 0, NULL),
(193, 316, 3, NULL, 'yellow_card', 54, 1, NULL),
(194, 316, 12, 227, 'yellow_card', 81, 0, NULL),
(195, 317, 18, 346, 'yellow_card', 40, 1, NULL),
(196, 317, 16, 306, 'yellow_card', 65, 0, NULL),
(197, 318, 19, 371, 'yellow_card', 22, 1, NULL),
(198, 318, 1, 13, 'yellow_card', 51, 0, NULL),
(199, 319, 6, 108, 'yellow_card', 74, 1, NULL),
(200, 320, 19, 366, 'yellow_card', 33, 1, NULL),
(201, 320, 9, 167, 'yellow_card', 77, 0, NULL),
(202, 321, 5, 97, 'goal', 23, 0, NULL),
(203, 321, 5, 93, 'goal', 67, 0, NULL),
(204, 322, 17, 331, 'goal', 35, 1, NULL),
(205, 322, 15, 335, 'goal', 78, 0, NULL),
(206, 323, 9, 168, 'yellow_card', 42, 1, NULL),
(207, 323, 6, NULL, 'yellow_card', 55, 0, NULL),
(208, 324, 8, NULL, 'goal', 12, 1, NULL),
(209, 324, 8, 154, 'goal', 37, 1, NULL),
(210, 324, 8, 153, 'goal', 66, 1, NULL),
(211, 324, 8, 150, 'goal', 90, 1, NULL),
(212, 324, 14, 271, 'goal', 45, 0, NULL),
(213, 324, 14, NULL, 'goal', 72, 0, NULL),
(214, 325, 7, 133, 'goal', 18, 1, NULL),
(215, 325, 7, 134, 'goal', 39, 1, NULL),
(216, 325, 7, NULL, 'goal', 58, 1, NULL),
(217, 325, 7, 129, 'goal', 82, 1, NULL),
(218, 325, 19, NULL, 'goal', 65, 0, NULL),
(219, 326, 11, NULL, 'goal', 30, 1, NULL),
(220, 326, 3, NULL, 'goal', 50, 0, NULL),
(221, 326, 3, 51, 'goal', 75, 0, NULL),
(222, 327, 4, 70, 'goal', 22, 0, NULL),
(223, 327, 4, 75, 'goal', 48, 0, NULL),
(224, 327, 4, 71, 'goal', 63, 0, NULL),
(225, 327, 4, 74, 'goal', 80, 0, NULL),
(226, 328, 18, NULL, 'goal', 55, 0, NULL),
(227, 329, 2, 35, 'goal', 67, 0, NULL),
(228, 330, 16, 317, 'goal', 40, 1, NULL),
(229, 330, 20, 388, 'goal', 60, 0, NULL),
(230, 330, 20, 385, 'goal', 85, 0, NULL),
(231, 321, 10, 185, 'yellow_card', 15, 1, NULL),
(232, 321, 5, 92, 'yellow_card', 38, 0, NULL),
(233, 322, 17, 326, 'yellow_card', 22, 1, NULL),
(234, 322, 15, 284, 'yellow_card', 49, 0, NULL),
(235, 323, 9, 171, 'yellow_card', 31, 1, NULL),
(236, 323, 6, 108, 'yellow_card', 58, 0, NULL),
(237, 324, 8, 149, 'yellow_card', 19, 1, NULL),
(238, 324, 14, NULL, 'yellow_card', 46, 0, NULL),
(239, 325, 7, 125, 'yellow_card', 27, 1, NULL),
(240, 325, 19, 377, 'yellow_card', 53, 0, NULL),
(241, 326, 11, NULL, 'yellow_card', 35, 1, NULL),
(242, 326, 3, NULL, 'yellow_card', 62, 0, NULL),
(243, 327, 4, 63, 'yellow_card', 44, 0, NULL),
(244, 327, 12, 227, 'yellow_card', 71, 1, NULL),
(245, 328, 1, 13, 'yellow_card', 29, 1, NULL),
(246, 328, 18, 346, 'yellow_card', 57, 0, NULL),
(247, 329, 13, 251, 'yellow_card', 33, 1, NULL),
(248, 329, 2, 24, 'yellow_card', 68, 0, NULL),
(249, 330, 16, 306, 'yellow_card', 21, 1, NULL),
(250, 330, 20, 386, 'yellow_card', 50, 0, NULL),
(251, 282, 10, 195, 'goal', 35, 1, NULL),
(252, 282, 17, 331, 'goal', 78, 0, NULL),
(253, 283, 15, 335, 'goal', 43, 1, NULL),
(254, 283, 18, NULL, 'goal', 55, 0, NULL),
(255, 283, 18, 353, 'goal', 85, 0, NULL),
(256, 284, 12, 231, 'goal', 22, 1, NULL),
(257, 284, 12, 233, 'goal', 48, 1, NULL),
(258, 284, 20, 388, 'goal', 30, 0, NULL),
(259, 284, 20, 385, 'goal', 50, 0, NULL),
(260, 284, 20, NULL, 'goal', 63, 0, NULL),
(261, 284, 20, 386, 'goal', 80, 0, NULL),
(262, 285, 5, 97, 'goal', 12, 1, NULL),
(263, 285, 5, 93, 'goal', 45, 1, NULL),
(264, 285, 14, 271, 'goal', 55, 0, NULL),
(265, 285, 14, NULL, 'goal', 72, 0, NULL),
(266, 286, 6, NULL, 'goal', 35, 1, NULL),
(267, 286, 8, NULL, 'goal', 50, 0, NULL),
(268, 286, 8, 154, 'goal', 78, 0, NULL),
(269, 287, 4, 70, 'goal', 30, 1, NULL),
(270, 288, 11, NULL, 'goal', 58, 1, NULL),
(271, 288, 11, NULL, 'goal', 87, 1, NULL),
(272, 289, 1, NULL, 'goal', 26, 0, NULL),
(273, 289, 1, 11, 'goal', 63, 0, NULL),
(274, 289, 1, 13, 'goal', 79, 0, NULL),
(275, 282, 10, 185, 'yellow_card', 17, 1, NULL),
(276, 282, 17, 326, 'yellow_card', 29, 0, NULL),
(277, 283, 15, 284, 'yellow_card', 38, 1, NULL),
(278, 283, 18, 346, 'yellow_card', 62, 0, NULL),
(279, 284, 12, 227, 'yellow_card', 54, 1, NULL),
(280, 284, 20, 386, 'yellow_card', 81, 0, NULL),
(281, 285, 5, 92, 'yellow_card', 22, 1, NULL),
(282, 285, 14, 265, 'yellow_card', 38, 0, NULL),
(283, 286, 6, 108, 'yellow_card', 40, 1, NULL),
(284, 286, 8, 150, 'yellow_card', 74, 0, NULL),
(285, 287, 4, 74, 'yellow_card', 36, 1, NULL),
(286, 287, 3, NULL, 'yellow_card', 72, 0, NULL),
(287, 288, 11, NULL, 'yellow_card', 54, 1, NULL),
(288, 288, 16, 306, 'yellow_card', 65, 0, NULL),
(289, 289, 13, 251, 'yellow_card', 59, 1, NULL),
(290, 289, 1, 13, 'yellow_card', 51, 0, NULL),
(291, 290, 4, 70, 'goal', 36, 1, NULL),
(292, 290, 4, 75, 'goal', 72, 1, NULL),
(293, 290, 11, 212, 'goal', 90, 0, NULL),
(294, 291, 18, NULL, 'goal', 50, 1, NULL),
(295, 292, 20, 390, 'goal', 67, 1, NULL),
(296, 293, 19, NULL, 'goal', 22, 1, NULL),
(297, 293, 19, 377, 'goal', 55, 1, NULL),
(298, 293, 8, NULL, 'goal', 70, 0, NULL),
(299, 294, 5, 97, 'goal', 28, 1, NULL),
(300, 294, 5, 93, 'goal', 63, 1, NULL),
(301, 295, 15, 335, 'goal', 45, 1, NULL),
(302, 295, 9, 168, 'goal', 78, 0, NULL),
(303, 296, 6, NULL, 'goal', 30, 1, NULL),
(304, 296, 12, 231, 'goal', 48, 0, NULL),
(305, 296, 12, 233, 'goal', 75, 0, NULL),
(306, 297, 7, 133, 'goal', 12, 0, NULL),
(307, 297, 7, 134, 'goal', 44, 0, NULL),
(308, 297, 7, NULL, 'goal', 67, 0, NULL),
(309, 298, 2, 35, 'goal', 38, 1, NULL),
(310, 299, 3, NULL, 'goal', 55, 1, NULL),
(311, 290, 4, 64, 'yellow_card', 22, 1, NULL),
(312, 290, 11, NULL, 'yellow_card', 41, 0, NULL),
(313, 291, 18, 346, 'yellow_card', 35, 1, NULL),
(314, 292, 20, 392, 'yellow_card', 50, 1, NULL),
(315, 293, 19, 371, 'yellow_card', 29, 1, NULL),
(316, 293, 8, 150, 'yellow_card', 65, 0, NULL),
(317, 294, 5, 92, 'yellow_card', 72, 1, NULL),
(318, 295, 15, 167, 'yellow_card', 38, 1, NULL),
(319, 296, 6, 108, 'yellow_card', 58, 1, NULL),
(320, 297, 7, 125, 'yellow_card', 19, 0, NULL),
(321, 298, 2, 24, 'yellow_card', 47, 1, NULL),
(322, 299, 3, NULL, 'yellow_card', 66, 1, NULL),
(323, 272, 20, 396, 'goal', 83, 1, NULL),
(324, 273, 2, 35, 'goal', 18, 1, NULL),
(325, 273, 2, 34, 'goal', 42, 1, NULL),
(326, 273, 2, 33, 'goal', 70, 1, NULL),
(327, 273, 15, 335, 'goal', 88, 0, NULL),
(328, 274, 14, 271, 'goal', 30, 1, NULL),
(329, 274, 14, NULL, 'goal', 63, 1, NULL),
(330, 274, 11, 212, 'goal', 75, 0, NULL),
(331, 275, 9, 168, 'goal', 55, 1, NULL),
(332, 276, 7, 133, 'goal', 65, 0, NULL),
(333, 277, 18, NULL, 'goal', 33, 1, NULL),
(334, 277, 10, 195, 'goal', 79, 0, NULL),
(335, 278, 3, NULL, 'goal', 50, 1, NULL),
(336, 279, 16, 317, 'goal', 22, 1, NULL),
(337, 279, 16, 318, 'goal', 48, 1, NULL),
(338, 279, 6, NULL, 'goal', 55, 0, NULL),
(339, 279, 6, 108, 'goal', 77, 0, NULL),
(340, 280, 1, NULL, 'goal', 40, 1, NULL),
(341, 280, 4, 70, 'goal', 68, 0, NULL),
(342, 281, 19, NULL, 'goal', 72, 1, NULL),
(343, 272, 20, 392, 'yellow_card', 45, 1, NULL),
(344, 273, 2, 24, 'yellow_card', 38, 1, NULL),
(345, 274, 14, NULL, 'yellow_card', 50, 1, NULL),
(346, 275, 9, 167, 'yellow_card', 61, 1, NULL),
(347, 276, 7, 125, 'yellow_card', 30, 0, NULL),
(348, 277, 18, 346, 'yellow_card', 72, 1, NULL),
(349, 278, 3, NULL, 'yellow_card', 67, 1, NULL),
(350, 279, 16, 306, 'yellow_card', 29, 1, NULL),
(351, 280, 1, 13, 'yellow_card', 53, 1, NULL),
(352, 281, 17, 326, 'yellow_card', 79, 1, NULL),
(353, 1, 1, NULL, 'goal', 87, 1, NULL),
(354, 2, 2, 35, 'goal', 18, 1, NULL),
(355, 2, 2, 34, 'goal', 42, 1, NULL),
(356, 3, 19, NULL, 'goal', 55, 1, NULL),
(357, 4, 14, 271, 'goal', 30, 1, NULL),
(358, 4, 14, NULL, 'goal', 63, 1, NULL),
(359, 5, 9, NULL, 'goal', 29, 1, NULL),
(360, 5, 9, 173, 'goal', 60, 1, NULL),
(361, 5, 9, NULL, 'goal', 90, 1, NULL),
(362, 6, 3, NULL, 'goal', 50, 1, NULL),
(363, 6, 3, 58, 'goal', 65, 1, NULL),
(364, 6, 3, 51, 'goal', 80, 1, NULL),
(365, 7, 5, 97, 'goal', 38, 0, NULL),
(366, 8, 1, NULL, 'goal', 40, 1, NULL),
(367, 8, 1, 11, 'goal', 55, 1, NULL),
(368, 8, 1, 13, 'goal', 78, 1, NULL),
(369, 9, 8, NULL, 'goal', 65, 1, NULL),
(370, 9, 10, 195, 'goal', 79, 0, NULL),
(371, 10, 17, 331, 'goal', 55, 1, NULL),
(372, 10, 17, 326, 'goal', 80, 1, NULL),
(373, 1, 1, 12, 'yellow_card', 45, 1, NULL),
(374, 2, 2, 24, 'yellow_card', 38, 1, NULL),
(375, 3, 19, 377, 'yellow_card', 50, 1, NULL),
(376, 4, 14, NULL, 'yellow_card', 50, 1, NULL),
(377, 5, 9, 167, 'yellow_card', 61, 1, NULL),
(378, 6, 3, NULL, 'yellow_card', 67, 1, NULL),
(379, 7, 5, 92, 'yellow_card', 72, 0, NULL),
(380, 8, 1, 13, 'yellow_card', 53, 1, NULL),
(381, 9, 8, 150, 'yellow_card', 74, 1, NULL),
(382, 10, 17, 326, 'yellow_card', 79, 1, NULL),
(383, 1, 1, NULL, 'assist', 87, 1, NULL),
(384, 2, 2, 23, 'assist', 18, 1, NULL),
(385, 2, 2, 29, 'assist', 42, 1, NULL),
(386, 3, 19, 364, 'assist', 55, 1, NULL),
(387, 4, 14, NULL, 'assist', 30, 1, NULL),
(388, 4, 14, 271, 'assist', 63, 1, NULL),
(389, 5, 9, 168, 'assist', 29, 1, NULL),
(390, 5, 9, NULL, 'assist', 60, 1, NULL),
(391, 6, 3, 58, 'assist', 50, 1, NULL),
(392, 6, 3, NULL, 'assist', 65, 1, NULL),
(393, 7, 5, 93, 'assist', 38, 0, NULL),
(394, 8, 1, 11, 'assist', 55, 1, NULL),
(395, 9, 8, 154, 'assist', 65, 1, NULL),
(396, 10, 17, 326, 'assist', 55, 1, NULL),
(397, 2, 2, 21, 'clean_sheet', 90, 1, NULL),
(398, 4, 14, 262, 'clean_sheet', 90, 1, NULL),
(399, 6, 5, 88, 'clean_sheet', 90, 0, NULL),
(400, 9, 8, 144, 'clean_sheet', 90, 1, NULL),
(401, 10, 17, 322, 'clean_sheet', 90, 1, NULL),
(402, 2, 2, 35, 'penalty_scored', 18, 1, NULL),
(403, 6, 5, 97, 'penalty_scored', 38, 0, NULL),
(404, 8, 1, 11, 'penalty_scored', 55, 1, NULL),
(405, 3, 19, NULL, 'penalty_missed', 55, 1, NULL),
(406, 5, 9, 168, 'penalty_missed', 29, 1, NULL),
(407, 7, 5, 93, 'penalty_missed', 38, 0, NULL),
(408, 262, 14, NULL, 'assist', 30, 1, NULL),
(409, 262, 14, 271, 'assist', 63, 1, NULL),
(410, 262, 14, 262, 'save', 45, 1, NULL),
(411, 263, 9, 168, 'assist', 29, 1, NULL),
(412, 263, 9, NULL, 'assist', 60, 1, NULL),
(413, 263, 9, 173, 'penalty_scored', 90, 1, NULL),
(414, 263, 9, NULL, 'clean_sheet', 90, 1, NULL),
(415, 264, 11, 204, 'assist', 18, 0, NULL),
(416, 264, 11, NULL, 'assist', 42, 0, NULL),
(417, 264, 11, 200, 'save', 50, 0, NULL),
(418, 265, 3, 51, 'assist', 50, 1, NULL),
(419, 265, 3, NULL, 'assist', 65, 1, NULL),
(420, 265, 3, 47, 'clean_sheet', 90, 1, NULL),
(421, 266, 5, 93, 'assist', 38, 0, NULL),
(422, 266, 5, 88, 'clean_sheet', 90, 0, NULL),
(423, 267, 20, 382, 'clean_sheet', 90, 1, NULL),
(424, 267, 4, 62, 'clean_sheet', 90, 0, NULL),
(425, 268, 1, 11, 'assist', 55, 1, NULL),
(426, 268, 1, 13, 'assist', 78, 1, NULL),
(427, 268, 1, 2, 'save', 12, 1, NULL),
(428, 269, 8, 154, 'assist', 65, 1, NULL),
(429, 269, 8, 144, 'clean_sheet', 90, 1, NULL),
(430, 270, 2, 35, 'assist', 18, 1, NULL),
(431, 270, 2, 23, 'assist', 42, 1, NULL),
(432, 270, 2, 35, 'penalty_scored', 18, 1, NULL),
(433, 270, 2, 21, 'clean_sheet', 90, 1, NULL),
(434, 271, 17, 326, 'assist', 55, 1, NULL),
(435, 271, 17, 331, 'assist', 80, 1, NULL),
(436, 271, 17, 322, 'clean_sheet', 90, 1, NULL),
(437, 272, 20, 385, 'assist', 35, 1, NULL),
(438, 272, 20, 382, 'clean_sheet', 90, 1, NULL),
(439, 273, 2, 35, 'assist', 22, 1, NULL),
(440, 273, 2, 23, 'assist', 55, 1, NULL),
(441, 273, 2, 35, 'penalty_scored', 18, 1, NULL),
(442, 274, 14, NULL, 'assist', 30, 1, NULL),
(443, 274, 14, 271, 'assist', 63, 1, NULL),
(444, 275, 9, 168, 'assist', 29, 1, NULL),
(445, 275, 9, NULL, 'clean_sheet', 90, 1, NULL),
(446, 276, 7, NULL, 'assist', 50, 0, NULL),
(447, 276, 7, 123, 'clean_sheet', 90, 0, NULL),
(448, 277, 18, NULL, 'assist', 65, 1, NULL),
(449, 277, 10, 196, 'assist', 80, 0, NULL),
(450, 278, 3, 51, 'assist', 50, 1, NULL),
(451, 278, 3, 47, 'clean_sheet', 90, 1, NULL),
(452, 279, 16, 316, 'assist', 38, 1, NULL),
(453, 279, 6, NULL, 'assist', 55, 0, NULL),
(454, 280, 1, 11, 'assist', 55, 1, NULL),
(455, 280, 4, 71, 'assist', 78, 0, NULL),
(456, 281, 19, 364, 'assist', 55, 0, NULL),
(457, 281, 19, 363, 'clean_sheet', 90, 0, NULL),
(458, 282, 10, 196, 'assist', 55, 1, NULL),
(459, 282, 17, NULL, 'assist', 78, 0, NULL),
(460, 283, 18, NULL, 'assist', 35, 0, NULL),
(461, 283, 18, 353, 'assist', 65, 0, NULL),
(462, 284, 20, 385, 'assist', 22, 1, NULL),
(463, 284, 20, 396, 'assist', 55, 1, NULL),
(464, 285, 5, 93, 'assist', 38, 1, NULL),
(465, 285, 14, NULL, 'assist', 65, 0, NULL),
(466, 286, 8, 154, 'assist', 50, 0, NULL),
(467, 286, 8, NULL, 'assist', 75, 0, NULL),
(468, 287, 4, 71, 'assist', 55, 1, NULL),
(469, 288, 11, 204, 'assist', 30, 1, NULL),
(470, 288, 11, NULL, 'assist', 65, 1, NULL),
(471, 288, 11, 200, 'clean_sheet', 90, 1, NULL),
(472, 289, 1, 11, 'assist', 45, 1, NULL),
(473, 289, 1, 13, 'assist', 70, 1, NULL),
(474, 289, 1, 2, 'clean_sheet', 90, 1, NULL),
(483, 361, 3, 47, 'penalty_scored', 85, 0, NULL),
(486, 382, 3, 42, 'goal', 45, 0, NULL),
(487, 382, 3, 42, 'penalty_scored', 45, 0, NULL),
(488, 382, 20, 400, 'yellow_card', 68, 0, NULL),
(489, 382, 20, 384, 'goal', 12, 1, NULL),
(491, 382, 3, 45, 'red_card', 68, 0, NULL),
(492, 382, 3, 45, 'yellow_card', 68, 0, NULL),
(493, 382, 3, 42, 'assist', 24, 0, NULL),
(494, 382, 20, 390, 'yellow_card', 12, 1, NULL),
(495, 382, 20, 393, 'save', 58, 1, NULL),
(496, 382, 20, 387, 'save', 78, 1, '0'),
(497, 382, 20, 390, 'own_goal', 15, 1, NULL),
(498, 382, 3, 45, 'goal', 45, 0, NULL),
(499, 382, 20, 390, 'yellow_card', 78, 1, NULL),
(500, 382, 3, 50, 'own_goal', 78, 0, 'xin chào '),
(501, 382, 20, 391, 'penalty_missed', 45, 1, ''),
(502, 360, 2, 35, 'penalty_scored', 15, 1, ''),
(503, 383, 15, 297, 'goal', 15, 0, NULL),
(504, 381, 19, 376, 'goal', 12, 0, NULL),
(508, 380, 1, 19, 'goal', 12, 0, NULL),
(509, 379, 2, 29, 'goal', 78, 0, NULL),
(521, 343, 13, 255, 'penalty_scored', 1, 0, NULL),
(522, 377, 11, 214, 'goal', 12, 1, ''),
(523, 377, 5, 83, 'goal', 4, 0, ''),
(525, 363, 20, 388, 'penalty_scored', 25, 0, NULL);

--
-- Triggers `matchevents`
--
DELIMITER $$
CREATE TRIGGER `tr_matchevents_after_delete` AFTER DELETE ON `matchevents` FOR EACH ROW BEGIN
    DECLARE v_season_id INT;
    DECLARE v_home_team_id INT;
    DECLARE v_away_team_id INT;
    
    -- Lấy thông tin mùa giải và đội bóng
    SELECT m.season_id, m.home_team_id, m.away_team_id 
    INTO v_season_id, v_home_team_id, v_away_team_id
    FROM matches m 
    WHERE m.match_id = OLD.match_id;
    
    -- Cập nhật thống kê cầu thủ
    IF OLD.player_id IS NOT NULL THEN
        CALL UpdatePlayerStatsForSeason(OLD.player_id, v_season_id);
    END IF;
    
    -- Cập nhật thống kê đội bóng
    CALL UpdateTeamStatsForSeason(v_home_team_id, v_season_id);
    CALL UpdateTeamStatsForSeason(v_away_team_id, v_season_id);
    
    -- Cập nhật bảng xếp hạng
    CALL UpdateStandings(v_season_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_matchevents_after_insert` AFTER INSERT ON `matchevents` FOR EACH ROW BEGIN
    DECLARE v_season_id INT;
    DECLARE v_home_team_id INT;
    DECLARE v_away_team_id INT;
    DECLARE v_player_team_id INT;
    
    -- Lấy thông tin mùa giải và đội bóng
    SELECT m.season_id, m.home_team_id, m.away_team_id 
    INTO v_season_id, v_home_team_id, v_away_team_id
    FROM matches m 
    WHERE m.match_id = NEW.match_id;
    
    -- Lấy đội bóng của cầu thủ
    IF NEW.player_id IS NOT NULL THEN
        SELECT team_id INTO v_player_team_id
        FROM players 
        WHERE player_id = NEW.player_id;
    END IF;
    
    -- Cập nhật thống kê cầu thủ nếu có cầu thủ liên quan
    IF NEW.player_id IS NOT NULL THEN
        CALL UpdatePlayerStatsForSeason(NEW.player_id, v_season_id);
    END IF;
    
    -- Cập nhật thống kê cho cả hai đội
    CALL UpdateTeamStatsForSeason(v_home_team_id, v_season_id);
    CALL UpdateTeamStatsForSeason(v_away_team_id, v_season_id);
    
    -- Cập nhật bảng xếp hạng
    CALL UpdateStandings(v_season_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_matchevents_after_update` AFTER UPDATE ON `matchevents` FOR EACH ROW BEGIN
    DECLARE v_season_id INT;
    DECLARE v_home_team_id INT;
    DECLARE v_away_team_id INT;
    
    -- Lấy thông tin mùa giải và đội bóng
    SELECT m.season_id, m.home_team_id, m.away_team_id 
    INTO v_season_id, v_home_team_id, v_away_team_id
    FROM matches m 
    WHERE m.match_id = NEW.match_id;
    
    -- Cập nhật thống kê cầu thủ cho cả cầu thủ cũ và mới
    IF OLD.player_id IS NOT NULL THEN
        CALL UpdatePlayerStatsForSeason(OLD.player_id, v_season_id);
    END IF;
    
    IF NEW.player_id IS NOT NULL AND NEW.player_id != OLD.player_id THEN
        CALL UpdatePlayerStatsForSeason(NEW.player_id, v_season_id);
    END IF;
    
    -- Cập nhật thống kê đội bóng
    CALL UpdateTeamStatsForSeason(v_home_team_id, v_season_id);
    CALL UpdateTeamStatsForSeason(v_away_team_id, v_season_id);
    
    -- Cập nhật bảng xếp hạng
    CALL UpdateStandings(v_season_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `news_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci NOT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci NOT NULL,
  `publish_date` datetime NOT NULL,
  `author` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `views` int DEFAULT '0',
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`news_id`, `title`, `content`, `publish_date`, `author`, `image_url`, `views`, `category_id`) VALUES
(1, 'Slot cảm ơn người hâm mộ và cộng đồng bóng đá đã phản hồi về sự cố diễu hành', 'Arne Slot đã bày tỏ lòng biết ơn đối với các dịch vụ khẩn cấp vì phản ứng của họ đối với sự cố nghiêm trọng xảy ra trong cuộc diễu hành trao Cúp Ngoại hạng Anh của câu lạc bộ vào thứ Hai.\r\n\r\nTrong một lá thư đọc tại lễ trao giải thường niên của Hiệp hội các nhà quản lý giải đấu (LMA), nơi ông được vinh danh là Huấn luyện viên xuất sắc nhất mùa giải 2024/25 của Barclays, huấn luyện viên trưởng của Liverpool đã bày tỏ lòng biết ơn tới tất cả những người đã giúp đỡ ngay sau vụ việc kinh hoàng khi một chiếc ô tô lao vào đám đông ở trung tâm thành phố. \r\n\r\nHơn 50 người bị thương, trong đó có một số người bị thương nghiêm trọng. Cảnh sát Merseyside cho biết 11 người vẫn đang nằm viện nhưng tất cả đều trong tình trạng ổn định.\r\n\r\nSlot đã rút khỏi sự kiện LMA để bày tỏ sự đoàn kết với những người bị ảnh hưởng bởi sự cố xảy ra hôm thứ Hai.', '2025-05-27 09:00:00', 'Trịnh Thùy Linh', 'uploads/news/slot.png', 94, 1),
(2, 'Paul Mitchell sẽ rời Newcastle United', 'Paul gia nhập câu lạc bộ vào tháng 7 năm 2024, tái hợp anh với CEO của Magpies là Darren Eales, người trước đó đã chiêu mộ Paul đến Tottenham Hotspur vào năm 2014.\r\nVì lý do sức khỏe, Darren sẽ từ chức CEO trong tương lai gần.\r\nTrong nhiệm kỳ của Paul với tư cách là Giám đốc thể thao, câu lạc bộ đã giành được danh hiệu quốc nội lớn đầu tiên sau 70 năm bằng cách nâng cao Cúp Carabao vào tháng 3 năm 2025 và giành quyền tham dự Champions League cho mùa giải 2025/26.\r\nPaul Mitchell cho biết: \"Tôi muốn cảm ơn tất cả mọi người tại Newcastle United vì sự ủng hộ của họ trong năm qua, bao gồm Eddie Howe, Becky Langley, các cầu thủ, nhân viên, chủ sở hữu và người hâm mộ. Thật vinh dự khi được trở thành một phần của câu lạc bộ và làm việc với một số người tuyệt vời.\r\n\"Tôi ra đi vào thời điểm phù hợp với tôi và câu lạc bộ, đặc biệt là khi Darren Eales - người mà tôi đã cộng tác rất chặt chẽ trong suốt sự nghiệp của mình - sẽ sớm ra đi.\r\n\"Câu lạc bộ đang được quản lý rất tốt cả trong và ngoài sân cỏ, và đang ở vị thế tuyệt vời để tiếp tục phát triển.\r\n\"Tôi xin chúc tất cả mọi người liên quan đến Newcastle United một tương lai tươi sáng và thành công.\"\r\nPaul rời đi cùng lời chúc tốt đẹp nhất của câu lạc bộ và Ban quản trị muốn gửi lời cảm ơn chân thành đến anh vì sự chuyên nghiệp và tận tụy trong công việc.\r\n', '2025-05-27 10:30:00', 'Vũ Minh Hòa', 'uploads/news/Paul.png', 94, 8),
(3, 'Lời tri ân của CEO Liverpool dành cho các dịch vụ khẩn cấp và người hâm mộ sau sự cố diễu hành', 'Tổng giám đốc điều hành của Liverpool, Billy Hogan, đã gửi một thông điệp tới người hâm mộ câu lạc bộ sau sự cố kinh hoàng xảy ra trong lễ diễu hành trao cúp vô địch Premier League.\r\n\r\nHogan cho biết: \"Thay mặt toàn thể chúng tôi tại Câu lạc bộ bóng đá Liverpool , tôi xin gửi lời chia buồn sâu sắc nhất tới tất cả những người bị ảnh hưởng bởi vụ việc kinh hoàng này trên phố Water vào tối qua.\r\n\r\n\"Cuối tuần này là một tuần lễ ăn mừng, cảm xúc và niềm vui lan tỏa khắp thành phố trong toàn bộ cộng đồng người hâm mộ, và nó đã kết thúc bằng cảnh tượng đau thương không thể tưởng tượng nổi với sự cố kinh hoàng này.\r\n\r\n\"Tôi muốn bày tỏ lòng biết ơn đối với các dịch vụ khẩn cấp và các cơ quan đối tác của chúng tôi – Cảnh sát Merseyside, Dịch vụ cứu thương Tây Bắc và St John, và Cứu hỏa và Cứu hộ Merseyside – những người đã giải quyết vụ việc, và bây giờ là đội ngũ nhân viên bệnh viện trên khắp thành phố đang chăm sóc những người bị thương, trong đó có bốn trẻ em.\r\n\r\n\"Tôi cũng muốn cảm ơn những người ủng hộ đã chứng kiến ​​sự kiện này và giúp đỡ lẫn nhau khi có thể.\r\n\r\n\"Chúng tôi tiếp tục làm việc với các dịch vụ khẩn cấp và chính quyền địa phương để hỗ trợ cuộc điều tra đang diễn ra của họ và một lần nữa chúng tôi muốn hỏi nếu bất kỳ ai có thêm thông tin về vụ việc, vui lòng liên hệ với Cảnh sát Merseyside.\"', '2025-05-27 14:00:00', 'Nguyễn Thu Hường', 'uploads/news/Liverpool_thanks.png', 5, 2),
(4, 'FPL Pod: Tổng kết mùa giải 2024/25', 'Khi chiến dịch Premier League tuyệt vời sắp kết thúc, chúng ta hãy cùng nhìn lại mùa giải Fantasy Premier League đầy biến động và trao giải thưởng đầu tiên.\r\n\r\nKelly Somers và nhóm FPL Pod thảo luận về những thăng trầm của họ, ai xứng đáng nhận được giải thưởng “Tài năng, tầm nhìn và kế hoạch” do Juls truyền cảm hứng và hướng tới những thay đổi tiềm năng về giá cầu thủ trong mùa giải tới.', '2025-05-27 11:00:00', 'Trần Thúy Nga', 'uploads/news/podcast.png', 1, 2),
(5, 'Tuyên bố của Premier League', 'Giải bóng đá Ngoại hạng Anh đã đưa ra tuyên bố sau:\r\n\r\n\"Mọi người ở Premier League đều bàng hoàng trước sự kiện kinh hoàng xảy ra ở Liverpool tối nay, và chúng tôi xin gửi lời chia buồn sâu sắc nhất tới những người bị thương và bị ảnh hưởng.\r\n\r\n\"Chúng tôi đã liên lạc với Liverpool FC và bày tỏ sự hỗ trợ hết mình sau sự cố nghiêm trọng này.\"\r\n\r\nThông tin này được đưa ra sau tuyên bố của Liverpool FC như sau: \"Chúng tôi đang liên hệ trực tiếp với Cảnh sát Merseyside về vụ việc trên phố Water xảy ra vào cuối lễ diễu hành trao cúp vào tối nay.\r\n\r\n\"Chúng tôi xin gửi lời chia buồn và cầu nguyện tới những người bị ảnh hưởng bởi sự cố nghiêm trọng này.\r\n\r\n\"Chúng tôi sẽ tiếp tục hỗ trợ hết mình cho các dịch vụ khẩn cấp và chính quyền địa phương đang giải quyết sự cố này.\"', '2025-05-26 08:00:00', 'Trịnh Thùy Linh', 'uploads/news/statement.png', 0, 2),
(6, 'Đánh giá Ngoại hạng Anh: Những điều chúng ta học được vào cuối tuần', 'Nhà báo bóng đá Alex Keble nêu bật các chủ đề nóng và bài học chiến thuật từ Vòng 38, bao gồm:\r\n\r\n- Sai lầm của Villa khiến đội mất vị trí trong top năm khi Man Utd cho thấy dấu hiệu của ngày tốt lành sắp tới \r\n- Chelsea đánh bại Forest nhưng cả hai nhóm người hâm mộ đều nên vui mừng\r\n- Newcastle chen chân vào UCL khi Everton đặt nền móng cho một mùa giải 2025/26 mạnh mẽ\r\n- Man City đảm bảo một suất tham dự UCL nhưng liệu có thực sự \"giống như một danh hiệu\"?  \r\n- Saints tạo nên lịch sử không mong muốn trong khi các ưu tiên của Arsenal trong mùa hè rất rõ ràng\r\n- Salah dẫn đầu lễ kỷ niệm, nhưng liệu việc tụt hạng sau chức vô địch có phải là mối lo ngại? \r\n- West Ham kết thúc trong sự phấn khích nhưng Potter phải đối mặt với mùa hè lớn \r\n- Brighton không may mắn khi vắng mặt tại châu Âu nhưng kỷ nguyên Hurzeler mới chỉ bắt đầu \r\n- Cherries ăn mừng mùa giải tốt nhất, trong khi Leicester cúi đầu trong tiếng rên rỉ\r\n- Wolves đã thu hẹp khoảng cách với Brentford dưới thời Vitor Pereira', '2025-05-26 12:00:00', 'Vũ Minh Hòa', 'uploads/news/review.png', 96, 2),
(7, 'Salah tạo nên lịch sử với giải thưởng Chiếc giày vàng và Cầu thủ kiến tạo', 'Ngôi sao của Liverpool, Mohamed Salah, đã giành giải thưởng Chiếc giày vàng và Cầu thủ kiến ​​thiết xuất sắc nhất mùa giải 2024/25, qua đó tạo nên lịch sử. \r\n\r\nGiải thưởng Chiếc giày vàng được trao cho cầu thủ ghi nhiều bàn thắng nhất trong một mùa giải Premier League. Salah đã ghi bàn thắng thứ 29 của mình trong mùa giải trong trận hòa 1-1 của Liverpool với Crystal Palace . Anh kết thúc chiến dịch với sáu bàn thắng nhiều hơn cầu thủ ghi nhiều bàn thắng thứ hai, Alexander Isak của Newcastle United . \r\n\r\nĐây là mùa giải thứ tư Salah giành danh hiệu vua phá lưới Premier League, ngang bằng kỷ lục giành Chiếc giày vàng của  huyền thoại Arsenal Thierry Henry .', '2025-05-26 10:30:00', 'Nguyễn Thu Hường', 'uploads/news/Salah.png', 94, 1);

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `player_id` int NOT NULL,
  `team_id` int DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci NOT NULL,
  `position` enum('Thủ môn','Hậu vệ','Tiền vệ','Tiền đạo') CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci NOT NULL,
  `nationality` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `nationality_flag_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `jersey_number` int DEFAULT NULL,
  `photo_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`player_id`, `team_id`, `name`, `position`, `nationality`, `nationality_flag_url`, `birth_date`, `jersey_number`, `photo_url`, `height`, `weight`) VALUES
(1, 1, 'Dermot Mee', 'Thủ môn', 'Bắc Ireland', 'uploads/flags/gb-nir.png', '2002-11-20', 45, 'uploads/players/1_1.png', 185.50, 78.50),
(2, 1, 'André Onana', 'Thủ môn', 'Cameroon', 'uploads/flags/cm.png', '1996-04-02', 24, 'uploads/players/1_2.png', 190.00, 85.00),
(3, 1, 'Victor Lindelöf', 'Hậu vệ', 'Thụy Điển', 'uploads/flags/se.png', '1994-07-17', 2, 'uploads/players/1_3.png', 187.00, 82.00),
(4, 1, 'Harry Maguire', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-03-05', 5, 'uploads/players/1_4.png', 194.00, 100.00),
(5, 1, 'Lisandro Martinez', 'Hậu vệ', 'Argentina', 'uploads/flags/ar.png', '1998-01-18', 6, 'uploads/players/1_5.png', 175.00, 75.00),
(6, 1, 'Diogo Dalot', 'Hậu vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1999-03-18', 20, 'uploads/players/1_6.png', 184.00, 76.00),
(7, 1, 'Luke Shaw', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-07-12', 23, 'uploads/players/1_7.png', 185.00, 75.00),
(8, 1, 'Jonny Evans', 'Hậu vệ', 'Bắc Ireland', 'uploads/flags/gb-nir.png', '1988-01-03', 35, 'uploads/players/1_8.png', 188.00, 82.00),
(9, 1, 'Toby Collyer', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2004-01-03', 43, 'uploads/players/1_9.png', 180.00, 70.00),
(10, 1, 'Mason Mount', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-01-10', 7, 'uploads/players/1_10.png', 181.00, 74.00),
(11, 1, 'Bruno Fernandes', 'Tiền vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1994-09-08', 8, 'uploads/players/1_11.png', 179.00, 69.00),
(12, 1, 'Christian Eriksen', 'Tiền vệ', 'Đan Mạch', 'uploads/flags/dk.png', '1992-02-14', 14, 'uploads/players/1_12.png', 182.00, 76.00),
(13, 1, 'Casemiro', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '1992-02-23', 18, 'uploads/players/1_13.png', 185.00, 84.00),
(14, 1, 'Kobbie Mainoo', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-04-19', 37, 'uploads/players/1_14.png', 176.00, 70.00),
(15, 1, 'Rasmus Højlund', 'Tiền đạo', 'Đan Mạch', 'uploads/flags/dk.png', '2003-02-04', 9, 'uploads/players/1_15.png', 191.00, 85.00),
(16, 1, 'Amad Diallo', 'Tiền đạo', 'Bờ Biển Ngà', 'uploads/flags/ci.png', '2002-07-11', 16, 'uploads/players/1_16.png', 173.00, 68.00),
(17, 1, 'Alejandro Garnacho', 'Tiền đạo', 'Argentina', 'uploads/flags/ar.png', '2004-07-01', 17, 'uploads/players/1_17.png', 180.00, 70.00),
(18, 1, 'Altay Bayındır', 'Thủ môn', 'Thổ Nhĩ Kỳ', 'uploads/flags/tr.png', '1998-04-14', 1, 'uploads/players/1_18.png', 200.00, 90.00),
(19, 1, 'Harry Amass', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2007-03-16', 41, 'uploads/players/1_19.png', 175.00, 68.00),
(20, 1, 'Habeeb Ogunneye', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-11-12', 66, 'uploads/players/1_20.png', 178.00, 72.00),
(21, 2, 'Alisson Becker', 'Thủ môn', 'Brazil', 'uploads/flags/br.png', '1992-10-02', 1, 'uploads/players/2_1.png', 193.00, 91.00),
(22, 2, 'Caoimhin Kelleher', 'Thủ môn', 'Ai-len', 'uploads/flags/ie.png', '1998-11-23', 62, 'uploads/players/2_2.png', 188.00, 85.00),
(23, 2, 'Joe Gomez', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-05-23', 2, 'uploads/players/2_3.png', 188.00, 77.00),
(24, 2, 'Virgil van Dijk', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '1991-07-08', 4, 'uploads/players/2_4.png', 193.00, 92.00),
(25, 2, 'Ibrahima Konaté', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '1999-05-25', 5, 'uploads/players/2_5.png', 194.00, 93.00),
(26, 2, 'Kostas Tsimikas', 'Hậu vệ', 'Hy Lạp', 'uploads/flags/gr.png', '1996-05-12', 21, 'uploads/players/2_6.png', 179.00, 70.00),
(27, 2, 'Andy Robertson', 'Hậu vệ', 'Scotland', 'uploads/flags/gb-sct.png', '1994-03-11', 26, 'uploads/players/2_7.png', 178.00, 64.00),
(28, 2, 'Trent Alexander-Arnold', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1998-10-07', 66, 'uploads/players/2_8.png', 180.00, 76.00),
(29, 2, 'Dominik Szoboszlai', 'Tiền vệ', 'Hungari', 'uploads/flags/hu.png', '2000-10-25', 8, 'uploads/players/2_9.png', 186.00, 79.00),
(30, 2, 'Alexis Mac Allister', 'Tiền vệ', 'Argentina', 'uploads/flags/ar.png', '1998-12-24', 10, 'uploads/players/2_10.png', 174.00, 73.00),
(31, 2, 'Curtis Jones', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2001-01-30', 17, 'uploads/players/2_11.png', 185.00, 75.00),
(32, 2, 'Harvey Elliott', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2003-04-04', 19, 'uploads/players/2_12.png', 170.00, 68.00),
(33, 2, 'Luis Díaz', 'Tiền đạo', 'Colombia', 'uploads/flags/co.png', '1997-01-13', 7, 'uploads/players/2_13.png', 180.00, 72.00),
(34, 2, 'Darwin Núñez', 'Tiền đạo', 'Uruguay', 'uploads/flags/uy.png', '1999-06-24', 9, 'uploads/players/2_14.png', 187.00, 80.00),
(35, 2, 'Mohamed Salah', 'Tiền đạo', 'Ai Cập', 'uploads/flags/eg.png', '1992-06-15', 11, 'uploads/players/2_15.png', 175.00, 71.00),
(36, 2, 'Cody Gakpo', 'Tiền đạo', 'Hà Lan', 'uploads/flags/nl.png', '1999-05-07', 18, 'uploads/players/2_16.png', 193.00, 85.00),
(37, 2, 'Diogo Jota', 'Tiền đạo', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1996-12-04', 20, 'uploads/players/2_17.png', 178.00, 73.00),
(38, 2, 'Jarell Quansah', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2003-01-29', 78, 'uploads/players/2_18.png', 190.00, 88.00),
(39, 2, 'Conor Bradley', 'Hậu vệ', 'Bắc Ireland', 'uploads/flags/gb-nir.png', '2003-07-09', 84, 'uploads/players/2_19.png', 173.00, 70.00),
(40, 2, 'Wataru Endo', 'Tiền vệ', 'Nhật Bản', 'uploads/flags/jp.png', '1993-02-09', 3, 'uploads/players/2_20.png', 178.00, 70.00),
(41, 3, 'Marcus Bettinelli', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1992-05-24', 13, 'uploads/players/3_1.png', 191.00, 85.00),
(42, 3, 'Robert Sánchez', 'Thủ môn', 'Tây Ban Nha', 'uploads/flags/es.png', '1997-11-18', 1, 'uploads/players/3_2.png', 197.00, 90.00),
(43, 3, 'Lucas Bergström', 'Thủ môn', 'Phần Lan', 'uploads/flags/fi.png', '2002-09-05', 47, 'uploads/players/3_3.png', 190.00, 80.00),
(44, 3, 'Marc Cucurella', 'Hậu vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1998-07-22', 3, 'uploads/players/3_4.png', 169.00, 67.00),
(45, 3, 'Benoît Badiashile', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '2001-03-26', 5, 'uploads/players/3_5.png', 194.00, 85.00),
(46, 3, 'Trevoh Chalobah', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-07-05', 23, 'uploads/players/3_6.png', 190.00, 82.00),
(47, 3, 'Reece James', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-12-08', 24, 'uploads/players/3_7.png', 183.00, 82.00),
(48, 3, 'Levi Colwill', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2003-02-26', 6, 'uploads/players/3_8.png', 187.00, 78.00),
(49, 3, 'Malo Gusto', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '2003-05-19', 27, 'uploads/players/3_9.png', 175.00, 70.00),
(50, 3, 'Wesley Fofana', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '2000-12-17', 29, 'uploads/players/3_10.png', 186.00, 83.00),
(51, 3, 'Enzo Fernández', 'Tiền vệ', 'Argentina', 'uploads/flags/ar.png', '2001-01-17', 8, 'uploads/players/3_11.png', 178.00, 78.00),
(52, 3, 'Mykhailo Mudryk', 'Tiền vệ', 'Ukraine', 'uploads/flags/ua.png', '2001-01-05', 10, 'uploads/players/3_12.png', 175.00, 72.00),
(53, 3, 'Noni Madueke', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2002-03-10', 11, 'uploads/players/3_13.png', 182.00, 74.00),
(54, 3, 'Nicolas Jackson', 'Tiền đạo', 'Senegal', 'uploads/flags/sn.png', '2001-06-20', 15, 'uploads/players/3_14.png', 190.00, 78.00),
(55, 3, 'Christopher Nkunku', 'Tiền đạo', 'Pháp', 'uploads/flags/fr.png', '1997-11-14', 18, 'uploads/players/3_15.png', 175.00, 73.00),
(56, 3, 'Ted Curd', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '2006-02-14', 43, 'uploads/players/3_16.png', 185.00, 75.00),
(57, 3, 'Josh Acheampong', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2006-05-05', 34, 'uploads/players/3_17.png', 180.00, 70.00),
(58, 3, 'Cole Palmer', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-05-06', 20, 'uploads/players/3_18.png', 180.00, 77.00),
(59, 3, 'Moisés Caicedo', 'Tiền vệ', 'Ecuador', 'uploads/flags/ec.png', '2001-11-02', 25, 'uploads/players/3_19.png', 178.00, 78.00),
(60, 3, 'Roméo Lavia', 'Tiền vệ', 'Bỉ', 'uploads/flags/be.png', '2004-01-06', 45, 'uploads/players/3_20.png', 181.00, 75.00),
(61, 4, 'William Saliba', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '2001-03-24', 2, 'uploads/players/4_1.png', 192.00, 87.00),
(62, 4, 'Kieran Tierney', 'Hậu vệ', 'Scotland', 'uploads/flags/gb-sct.png', '1997-06-05', 3, 'uploads/players/4_2.png', 172.00, 70.00),
(63, 4, 'Ben White', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-10-08', 4, 'uploads/players/4_3.png', 180.00, 78.00),
(64, 4, 'Gabriel Magalhães', 'Hậu vệ', 'Brazil', 'uploads/flags/br.png', '1997-12-19', 6, 'uploads/players/4_4.png', 190.00, 85.00),
(65, 4, 'Jurriën Timber', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '2001-06-17', 12, 'uploads/players/4_5.png', 179.00, 75.00),
(66, 4, 'Jakub Kiwior', 'Hậu vệ', 'Ba Lan', 'uploads/flags/pl.png', '2000-02-15', 15, 'uploads/players/4_6.png', 189.00, 80.00),
(67, 4, 'Takehiro Tomiyasu', 'Hậu vệ', 'Nhật Bản', 'uploads/flags/jp.png', '1998-11-05', 18, 'uploads/players/4_7.png', 188.00, 82.00),
(68, 4, 'Oleksandr Zinchenko', 'Hậu vệ', 'Ukraine', 'uploads/flags/ua.png', '1996-12-15', 17, 'uploads/players/4_8.png', 175.00, 70.00),
(69, 4, 'Thomas Partey', 'Tiền vệ', 'Ghana', 'uploads/flags/gh.png', '1993-06-13', 5, 'uploads/players/4_9.png', 185.00, 82.00),
(70, 4, 'Bukayo Saka', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2001-09-05', 7, 'uploads/players/4_10.png', 178.00, 72.00),
(71, 4, 'Martin Ødegaard', 'Tiền vệ', 'Na Uy', 'uploads/flags/no.png', '1998-12-17', 8, 'uploads/players/4_11.png', 176.00, 68.00),
(72, 4, 'Jorginho', 'Tiền vệ', 'Ý', 'uploads/flags/it.png', '1991-12-20', 20, 'uploads/players/4_12.png', 180.00, 75.00),
(73, 4, 'Kai Havertz', 'Tiền đạo', 'Đức', 'uploads/flags/de.png', '1999-06-11', 29, 'uploads/players/4_13.png', 193.00, 83.00),
(74, 4, 'Declan Rice', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-01-14', 41, 'uploads/players/4_14.png', 188.00, 82.00),
(75, 4, 'Gabriel Jesus', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '1997-04-03', 9, 'uploads/players/4_15.png', 175.00, 73.00),
(76, 4, 'Gabriel Martinelli', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '2001-06-18', 11, 'uploads/players/4_16.png', 178.00, 75.00),
(77, 4, 'Leandro Trossard', 'Tiền đạo', 'Bỉ', 'uploads/flags/be.png', '1994-12-04', 19, 'uploads/players/4_17.png', 172.00, 70.00),
(78, 4, 'Myles Lewis-Skelly', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2006-09-26', 49, 'uploads/players/4_18.png', 180.00, 72.00),
(79, 4, 'Ethan Nwaneri', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2007-03-21', 53, 'uploads/players/4_19.png', 175.00, 68.00),
(80, 4, 'David Raya', 'Thủ môn', 'Tây Ban Nha', 'uploads/flags/es.png', '1995-09-15', 22, 'uploads/players/4_20.png', 183.00, 80.00),
(81, 5, 'Stefan Ortega', 'Thủ môn', 'Đức', 'uploads/flags/de.png', '1992-11-06', 18, 'uploads/players/5_1.png', 185.00, 82.00),
(82, 5, 'Ederson', 'Thủ môn', 'Brazil', 'uploads/flags/br.png', '1993-08-17', 31, 'uploads/players/5_2.png', 188.00, 86.00),
(83, 5, 'Scott Carson', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1985-09-03', 33, 'uploads/players/5_3.png', 190.00, 85.00),
(84, 5, 'Rúben Dias', 'Hậu vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1997-05-14', 3, 'uploads/players/5_4.png', 186.00, 82.00),
(85, 5, 'John Stones', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1994-05-28', 5, 'uploads/players/5_5.png', 188.00, 80.00),
(86, 5, 'Nathan Aké', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '1995-02-18', 6, 'uploads/players/5_6.png', 180.00, 75.00),
(87, 5, 'Josko Gvardiol', 'Hậu vệ', 'Croatia', 'uploads/flags/hr.png', '2002-01-23', 24, 'uploads/players/5_7.png', 185.00, 80.00),
(88, 5, 'Manuel Akanji', 'Hậu vệ', 'Thụy Sĩ', 'uploads/flags/ch.png', '1995-07-19', 25, 'uploads/players/5_8.png', 187.00, 85.00),
(89, 5, 'Rico Lewis', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2004-11-21', 82, 'uploads/players/5_9.png', 173.00, 68.00),
(90, 5, 'Mateo Kovacic', 'Tiền vệ', 'Croatia', 'uploads/flags/hr.png', '1994-05-06', 8, 'uploads/players/5_10.png', 181.00, 80.00),
(91, 5, 'Jack Grealish', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-09-10', 10, 'uploads/players/5_11.png', 180.00, 77.00),
(92, 5, 'Rodri', 'Tiền vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1996-06-22', 16, 'uploads/players/5_12.png', 191.00, 82.00),
(93, 5, 'Kevin De Bruyne', 'Tiền vệ', 'Bỉ', 'uploads/flags/be.png', '1991-06-28', 17, 'uploads/players/5_13.png', 181.00, 70.00),
(94, 5, 'Bernardo Silva', 'Tiền vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1994-08-10', 20, 'uploads/players/5_14.png', 173.00, 64.00),
(95, 5, 'Phil Foden', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-05-28', 47, 'uploads/players/5_15.png', 171.00, 70.00),
(96, 5, 'James McAtee', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-10-18', 87, 'uploads/players/5_16.png', 175.00, 68.00),
(97, 5, 'Erling Haaland', 'Tiền đạo', 'Na Uy', 'uploads/flags/no.png', '2000-07-21', 9, 'uploads/players/5_17.png', 194.00, 88.00),
(98, 5, 'Max Alleyne', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-07-21', 68, 'uploads/players/5_18.png', 180.00, 70.00),
(99, 5, 'Matheus Nunes', 'Tiền vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1998-08-27', 27, 'uploads/players/5_19.png', 183.00, 75.00),
(100, 5, 'Oscar Bobb', 'Tiền vệ', 'Na Uy', 'uploads/flags/no.png', '2003-07-12', 52, 'uploads/players/5_20.png', 175.00, 68.00),
(101, 6, 'Milos Kerkez', 'Hậu vệ', 'Hungary', 'uploads/flags/no_flag.png', '2003-11-07', 3, 'uploads/players/6_1.png', 181.00, 70.00),
(102, 6, 'Adam Smith', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1991-04-29', 15, 'uploads/players/6_2.png', 180.00, 75.00),
(103, 6, 'James Hill', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-01-10', 23, 'uploads/players/6_3.png', 185.00, 78.00),
(104, 6, 'Marcos Senesi', 'Hậu vệ', 'Argentina', 'uploads/flags/ar.png', '1997-05-10', 5, 'uploads/players/6_4.png', 185.00, 77.00),
(105, 6, 'Illia Zabarnyi', 'Hậu vệ', 'Ukraine', 'uploads/flags/ua.png', '2002-09-01', 27, 'uploads/players/6_5.png', 190.00, 82.00),
(106, 6, 'Lewis Cook', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-02-03', 4, 'uploads/players/6_6.png', 174.00, 70.00),
(107, 6, 'David Brooks', 'Tiền vệ', 'Xứ Wales', 'uploads/flags/gb-wls.png', '1997-07-08', 7, 'uploads/players/6_7.png', 179.00, 72.00),
(108, 6, 'Ryan Christie', 'Tiền vệ', 'Scotland', 'uploads/flags/gb-sct.png', '1995-02-22', 10, 'uploads/players/6_8.png', 178.00, 73.00),
(109, 6, 'Alex Scott', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2003-08-21', 8, 'uploads/players/6_9.png', 171.00, 68.00),
(110, 6, 'Marcus Tavernier', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-03-22', 16, 'uploads/players/6_10.png', 177.00, 74.00),
(111, 6, 'Dango Ouattara', 'Tiền đạo', 'Burkina Faso', 'uploads/flags/bf.png', '2002-02-11', 11, 'uploads/players/6_11.png', 180.00, 75.00),
(112, 6, 'Justin Kluivert', 'Tiền đạo', 'Hà Lan', 'uploads/flags/nl.png', '1999-05-05', 19, 'uploads/players/6_12.png', 171.00, 70.00),
(113, 6, 'Antoine Semenyo', 'Tiền đạo', 'Ghana', 'uploads/flags/gh.png', '2000-01-07', 24, 'uploads/players/6_13.png', 180.00, 76.00),
(114, 6, 'Will Dennis', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '2000-07-10', 40, 'uploads/players/6_14.png', 185.00, 80.00),
(115, 6, 'Tyler Adams', 'Tiền vệ', 'Hoa Kỳ', 'uploads/flags/us.png', '1999-02-14', 12, 'uploads/players/6_15.png', 175.00, 72.00),
(116, 6, 'Luis Sinisterra', 'Tiền đạo', 'Colombia', 'uploads/flags/co.png', '1999-06-17', 17, 'uploads/players/6_16.png', 172.00, 70.00),
(117, 6, 'Enes Ünal', 'Tiền đạo', 'Thổ Nhĩ Kỳ', 'uploads/flags/tr.png', '1997-05-10', 26, 'uploads/players/6_17.png', 190.00, 82.00),
(118, 6, 'Daniel Jebbison', 'Tiền đạo', 'Canada', 'uploads/flags/ca.png', '2003-07-11', 21, 'uploads/players/6_18.png', 185.00, 78.00),
(119, 6, 'Max Kinsey', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-02-02', 48, 'uploads/players/6_19.png', 180.00, 70.00),
(120, 6, 'Daniel Adu-Adjei', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2005-06-21', 44, 'uploads/players/6_20.png', 175.00, 68.00),
(121, 7, 'Emiliano Martínez', 'Thủ môn', 'Argentina', 'uploads/flags/ar.png', '1992-09-02', 23, 'uploads/players/7_1.png', 195.00, 90.00),
(122, 7, 'Robin Olsen', 'Thủ môn', 'Thụy Điển', 'uploads/flags/se.png', '1990-01-08', 25, 'uploads/players/7_2.png', 196.00, 88.00),
(123, 7, 'Matty Cash', 'Hậu vệ', 'Ba Lan', 'uploads/flags/pl.png', '1997-08-07', 2, 'uploads/players/7_3.png', 183.00, 78.00),
(124, 7, 'Ezri Konsa', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-10-23', 4, 'uploads/players/7_4.png', 183.00, 77.00),
(125, 7, 'Tyrone Mings', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-03-13', 5, 'uploads/players/7_5.png', 195.00, 95.00),
(126, 7, 'Lucas Digne', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '1993-07-20', 12, 'uploads/players/7_6.png', 178.00, 75.00),
(127, 7, 'Pau Torres', 'Hậu vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1997-01-16', 14, 'uploads/players/7_7.png', 191.00, 85.00),
(128, 7, 'Kortney Hause', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-07-16', 30, 'uploads/players/7_8.png', 189.00, 80.00),
(129, 7, 'John McGinn', 'Tiền vệ', 'Scotland', 'uploads/flags/gb-sct.png', '1994-10-18', 7, 'uploads/players/7_9.png', 175.00, 68.00),
(130, 7, 'Youri Tielemans', 'Tiền vệ', 'Bỉ', 'uploads/flags/be.png', '1997-05-07', 8, 'uploads/players/7_10.png', 176.00, 73.00),
(131, 7, 'Jacob Ramsey', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2001-05-28', 41, 'uploads/players/7_11.png', 180.00, 76.00),
(132, 7, 'Boubacar Kamara', 'Tiền vệ', 'Pháp', 'uploads/flags/fr.png', '1999-11-23', 44, 'uploads/players/7_12.png', 184.00, 82.00),
(133, 7, 'Ollie Watkins', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1995-12-30', 11, 'uploads/players/7_13.png', 180.00, 73.00),
(134, 7, 'Leon Bailey', 'Tiền vệ', 'Jamaica', 'uploads/flags/jm.png', '1997-08-09', 31, 'uploads/players/7_14.png', 180.00, 75.00),
(135, 7, 'Sam Proctor', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '2006-12-21', 78, 'uploads/players/7_15.png', 185.00, 78.00),
(136, 7, 'Morgan Rogers', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2002-07-26', 27, 'uploads/players/7_16.png', 182.00, 74.00),
(137, 7, 'Ian Maatsen', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '2002-03-10', 22, 'uploads/players/7_17.png', 180.00, 76.00),
(138, 7, 'Ross Barkley', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-12-05', 6, 'uploads/players/7_18.png', 186.00, 82.00),
(139, 7, 'Amadou Onana', 'Tiền vệ', 'Bỉ', 'uploads/flags/be.png', '2001-08-16', 24, 'uploads/players/7_19.png', 192.00, 88.00),
(140, 7, 'Lamare Bogarde', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '2004-01-05', 26, 'uploads/players/7_20.png', 175.00, 70.00),
(141, 8, 'Mark Flekken', 'Thủ môn', 'Hà Lan', 'uploads/flags/nl.png', '1993-06-13', 1, 'uploads/players/8_1.png', 194.00, 87.00),
(142, 8, 'Nathan Collins', 'Hậu vệ', 'Ireland', 'uploads/flags/is.png', '2001-04-30', 22, 'uploads/players/8_2.png', 194.00, 89.00),
(143, 8, 'Aaron Hickey', 'Hậu vệ', 'Scotland', 'uploads/flags/gb-sct.png', '2002-06-10', 2, 'uploads/players/8_3.png', 180.00, 77.00),
(144, 8, 'Rico Henry', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-07-08', 3, 'uploads/players/8_4.png', 170.00, 70.00),
(145, 8, 'Ethan Pinnock', 'Hậu vệ', 'Jamaica', 'uploads/flags/jm.png', '1993-05-29', 5, 'uploads/players/8_5.png', 194.00, 85.00),
(146, 8, 'Ben Mee', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1989-09-21', 16, 'uploads/players/8_6.png', 180.00, 78.00),
(147, 8, 'Kristoffer Ajer', 'Hậu vệ', 'Na Uy', 'uploads/flags/no.png', '1998-04-17', 20, 'uploads/players/8_7.png', 194.00, 87.00),
(148, 8, 'Yehor Yarmoliuk', 'Tiền vệ', 'Ukraine', 'uploads/flags/ua.png', '2004-03-01', 18, 'uploads/players/8_8.png', 185.00, 75.00),
(149, 8, 'Christian Nørgaard', 'Tiền vệ', 'Đan Mạch', 'uploads/flags/dk.png', '1994-03-10', 6, 'uploads/players/8_9.png', 185.00, 80.00),
(150, 8, 'Mathias Jensen', 'Tiền vệ', 'Đan Mạch', 'uploads/flags/dk.png', '1996-01-01', 8, 'uploads/players/8_10.png', 180.00, 73.00),
(151, 8, 'Kevin Schade', 'Tiền vệ', 'Đức', 'uploads/flags/de.png', '2001-11-27', 7, 'uploads/players/8_11.png', 189.00, 82.00),
(152, 8, 'Josh Dasilva', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1998-10-23', 10, 'uploads/players/8_12.png', 175.00, 70.00),
(153, 8, 'Yoane Wissa', 'Tiền vệ', 'DR Congo', 'uploads/flags/cd.png', '1996-09-03', 11, 'uploads/players/8_13.png', 176.00, 75.00),
(154, 8, 'Bryan Mbeumo', 'Tiền vệ', 'Cameroon', 'uploads/flags/cm.png', '1999-08-07', 19, 'uploads/players/8_14.png', 179.00, 74.00),
(155, 8, 'Mikkel Damsgaard', 'Tiền vệ', 'Đan Mạch', 'uploads/flags/dk.png', '2000-07-03', 24, 'uploads/players/8_15.png', 175.00, 70.00),
(156, 8, 'Vitaly Janelt', 'Tiền vệ', 'Đức', 'uploads/flags/de.png', '1998-05-10', 27, 'uploads/players/8_16.png', 183.00, 78.00),
(157, 8, 'Keane Lewis-Potter', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2001-02-22', 23, 'uploads/players/8_17.png', 175.00, 70.00),
(158, 8, 'Hákon Valdimarsson', 'Thủ môn', 'Iceland', 'uploads/flags/no_flag.png', '2001-10-13', 12, 'uploads/players/8_18.png', 190.00, 85.00),
(159, 8, 'Kim Ji-Soo', 'Hậu vệ', 'Hàn Quốc', 'uploads/flags/kr.png', '2004-12-24', 36, 'uploads/players/8_19.png', 185.00, 75.00),
(160, 8, 'Benjamin Arthur', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-10-09', 43, 'uploads/players/8_20.png', 180.00, 70.00),
(161, 9, 'Remi Matthews', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1994-02-10', 31, 'uploads/players/9_1.png', 191.00, 85.00),
(162, 9, 'Joel Ward', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1989-10-29', 2, 'uploads/players/9_2.png', 188.00, 80.00),
(163, 9, 'Tyrick Mitchell', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-09-01', 3, 'uploads/players/9_3.png', 175.00, 70.00),
(164, 9, 'Marc Guéhi', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-07-13', 6, 'uploads/players/9_4.png', 182.00, 77.00),
(165, 9, 'Nathaniel Clyne', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1991-04-05', 17, 'uploads/players/9_5.png', 175.00, 75.00),
(166, 9, 'Chris Richards', 'Hậu vệ', 'Hoa Kỳ', 'uploads/flags/us.png', '2000-03-28', 26, 'uploads/players/9_6.png', 185.00, 82.00),
(167, 9, 'Jefferson Lerma', 'Tiền vệ', 'Colombia', 'uploads/flags/co.png', '1994-10-25', 8, 'uploads/players/9_7.png', 179.00, 79.00),
(168, 9, 'Eberechi Eze', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1998-06-29', 10, 'uploads/players/9_8.png', 173.00, 71.00),
(169, 9, 'Will Hughes', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-04-17', 19, 'uploads/players/9_9.png', 182.00, 75.00),
(170, 9, 'Malcolm Ebiowei', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2003-09-04', 23, 'uploads/players/9_10.png', 175.00, 68.00),
(171, 9, 'Cheick Doucouré', 'Tiền vệ', 'Mali', 'uploads/flags/ml.png', '2000-01-08', 28, 'uploads/players/9_11.png', 185.00, 80.00),
(172, 9, 'Matheus França', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '2004-04-01', 11, 'uploads/players/9_12.png', 180.00, 75.00),
(173, 9, 'Jean-Philippe Mateta', 'Tiền đạo', 'Pháp', 'uploads/flags/fr.png', '1997-06-28', 14, 'uploads/players/9_13.png', 192.00, 85.00),
(174, 9, 'Dean Henderson', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1997-03-12', 1, 'uploads/players/9_14.png', 190.00, 87.00),
(175, 9, 'Daniel Muñoz', 'Hậu vệ', 'Colombia', 'uploads/flags/co.png', '1996-05-26', 12, 'uploads/players/9_15.png', 181.00, 80.00),
(176, 9, 'Adam Wharton', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2004-02-06', 20, 'uploads/players/9_16.png', 175.00, 70.00),
(177, 9, 'Kaden Rodney', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2004-10-07', 42, 'uploads/players/9_17.png', 180.00, 72.00),
(178, 9, 'Justin Devenny', 'Tiền vệ', 'Bắc Ireland', 'uploads/flags/gb-nir.png', '2003-10-11', 55, 'uploads/players/9_18.png', 175.00, 70.00),
(179, 9, 'Franco Umeh-Chibueze', 'Tiền đạo', 'Ireland', 'uploads/flags/is.png', '2005-02-26', 46, 'uploads/players/9_19.png', 180.00, 68.00),
(180, 9, 'Daichi Kamada', 'Tiền vệ', 'Nhật Bản', 'uploads/flags/jp.png', '1996-08-05', 18, 'uploads/players/9_20.png', 180.00, 75.00),
(181, 10, 'Jordan Pickford', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1994-03-07', 1, 'uploads/players/10_1.png', 185.00, 83.00),
(182, 10, 'João Virgínia', 'Thủ môn', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1999-10-10', 12, 'uploads/players/10_2.png', 190.00, 80.00),
(183, 10, 'Nathan Patterson', 'Hậu vệ', 'Scotland', 'uploads/flags/gb-sct.png', '2001-10-16', 2, 'uploads/players/10_3.png', 175.00, 75.00),
(184, 10, 'Michael Keane', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-01-11', 5, 'uploads/players/10_4.png', 191.00, 85.00),
(185, 10, 'James Tarkowski', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1992-11-19', 6, 'uploads/players/10_5.png', 185.00, 82.00),
(186, 10, 'Ashley Young', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1985-07-09', 18, 'uploads/players/10_6.png', 175.00, 70.00),
(187, 10, 'Vitalii Mykolenko', 'Hậu vệ', 'Ukraine', 'uploads/flags/ua.png', '1999-05-29', 19, 'uploads/players/10_7.png', 180.00, 77.00),
(188, 10, 'Séamus Coleman', 'Hậu vệ', 'Ireland', 'uploads/flags/is.png', '1988-10-11', 23, 'uploads/players/10_8.png', 177.00, 73.00),
(189, 10, 'Jarrad Branthwaite', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-06-27', 32, 'uploads/players/10_9.png', 195.00, 90.00),
(190, 10, 'Abdoulaye Doucouré', 'Tiền vệ', 'Mali', 'uploads/flags/ml.png', '1993-01-01', 16, 'uploads/players/10_10.png', 183.00, 80.00),
(191, 10, 'Idrissa Gana Gueye', 'Tiền vệ', 'Senegal', 'uploads/flags/sn.png', '1989-09-26', 27, 'uploads/players/10_11.png', 174.00, 70.00),
(192, 10, 'James Garner', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2001-03-13', 37, 'uploads/players/10_12.png', 180.00, 75.00),
(193, 10, 'Youssef Chermiti', 'Tiền đạo', 'Bồ Đào Nha', 'uploads/flags/pt.png', '2004-05-24', 17, 'uploads/players/10_13.png', 185.00, 78.00),
(194, 10, 'Dwight McNeil', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1999-11-22', 7, 'uploads/players/10_14.png', 180.00, 75.00),
(195, 10, 'Dominic Calvert-Lewin', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1997-03-16', 9, 'uploads/players/10_15.png', 189.00, 82.00),
(196, 10, 'Jack Harrison', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1996-11-20', 11, 'uploads/players/10_16.png', 175.00, 73.00),
(197, 10, 'Tim Iroegbunam', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2003-06-30', 42, 'uploads/players/10_17.png', 185.00, 78.00),
(198, 10, 'Tyler Onyango', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2003-03-04', 62, 'uploads/players/10_18.png', 180.00, 70.00),
(199, 10, 'Iliman Ndiaye', 'Tiền đạo', 'Senegal', 'uploads/flags/sn.png', '2000-03-06', 10, 'uploads/players/10_19.png', 175.00, 72.00),
(200, 10, 'Jake OBrien', 'Hậu vệ', 'Ireland', 'uploads/flags/is.png', '2001-05-15', 15, 'uploads/players/10_20.png', 190.00, 85.00),
(201, 11, 'Bernd Leno', 'Thủ môn', 'Đức', 'uploads/flags/de.png', '1992-03-04', 17, 'uploads/players/11_1.png', 188.00, 81.00),
(202, 11, 'Kenny Tete', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '1995-10-09', 2, 'uploads/players/11_2.png', 183.00, 77.00),
(203, 11, 'Calvin Bassey', 'Hậu vệ', 'Nigeria', 'uploads/flags/ng.png', '1999-12-31', 12, 'uploads/players/11_3.png', 186.00, 80.00),
(204, 11, 'Issa Diop', 'Hậu vệ', 'Pháp', 'uploads/flags/fr.png', '1997-01-09', 4, 'uploads/players/11_4.png', 191.00, 87.00),
(205, 11, 'Antonee Robinson', 'Hậu vệ', 'Mỹ', 'uploads/flags/us.png', '1997-08-08', 33, 'uploads/players/11_5.png', 178.00, 73.00),
(206, 11, 'Harrison Reed', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-01-27', 6, 'uploads/players/11_6.png', 175.00, 70.00),
(207, 11, 'Harry Wilson', 'Tiền vệ', 'Wales', 'uploads/flags/no_flag.png', '1997-03-22', 8, 'uploads/players/11_7.png', 173.00, 68.00),
(208, 11, 'Tom Cairney', 'Tiền vệ', 'Scotland', 'uploads/flags/gb-sct.png', '1991-01-20', 10, 'uploads/players/11_8.png', 181.00, 75.00),
(209, 11, 'Andreas Pereira', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '1996-01-01', 18, 'uploads/players/11_9.png', 178.00, 72.00),
(210, 11, 'Sasa Lukic', 'Tiền vệ', 'Serbia', 'uploads/flags/rs.png', '1996-08-13', 28, 'uploads/players/11_10.png', 184.00, 78.00),
(211, 11, 'Raúl Jiménez', 'Tiền đạo', 'Mexico', 'uploads/flags/mx.png', '1991-05-05', 9, 'uploads/players/11_11.png', 187.00, 84.00),
(212, 11, 'Rodrigo Muniz', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '2001-06-04', 19, 'uploads/players/11_12.png', 187.00, 83.00),
(213, 11, 'Carlos Vinícius', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '1995-03-22', 30, 'uploads/players/11_13.png', 190.00, 85.00),
(214, 11, 'Steven Benda', 'Thủ môn', 'Đức', 'uploads/flags/de.png', '1998-10-01', 40, 'uploads/players/11_14.png', 191.00, 86.00),
(215, 11, 'Timothy Castagne', 'Hậu vệ', 'Bỉ', 'uploads/flags/be.png', '1995-12-05', 21, 'uploads/players/11_15.png', 180.00, 74.00),
(216, 11, 'Josh King', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1992-01-15', 25, 'uploads/players/11_16.png', 185.00, 80.00),
(217, 11, 'Adama Traoré', 'Tiền đạo', 'Tây Ban Nha', 'uploads/flags/es.png', '1996-01-25', 37, 'uploads/players/11_17.png', 175.00, 70.00),
(218, 11, 'Alex Iwobi', 'Tiền đạo', 'Nigeria', 'uploads/flags/ng.png', '1996-05-03', 17, 'uploads/players/11_18.png', 183.00, 76.00),
(219, 11, 'Ryan Sessegnon', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-05-18', 22, 'uploads/players/11_19.png', 178.00, 72.00),
(220, 11, 'Emile Smith Rowe', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-07-28', 32, 'uploads/players/11_20.png', 175.00, 68.00),
(221, 12, 'Christian Walton', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1995-11-09', 31, 'uploads/players/12_1.png', 188.00, 82.00),
(222, 12, 'Cieran Slicker', 'Thủ môn', 'Scotland', 'uploads/flags/gb-sct.png', '2002-09-15', 1, 'uploads/players/12_2.png', 191.00, 85.00),
(223, 12, 'Leif Davis', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-12-31', 3, 'uploads/players/12_3.png', 175.00, 70.00),
(224, 12, 'Luke Woolfenden', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1998-10-21', 6, 'uploads/players/12_4.png', 185.00, 78.00),
(225, 12, 'Cameron Burgess', 'Hậu vệ', 'Úc', 'uploads/flags/au.png', '1995-10-21', 26, 'uploads/players/12_5.png', 192.00, 88.00),
(226, 12, 'Axel Tuanzebe', 'Hậu vệ', 'CH Congo', 'uploads/flags/cd.png', '1997-11-14', 40, 'uploads/players/12_6.png', 185.00, 80.00),
(227, 12, 'Sam Morsy', 'Tiền vệ', 'Ai Cập', 'uploads/flags/eg.png', '1991-09-10', 5, 'uploads/players/12_7.png', 178.00, 74.00),
(228, 12, 'Jack Taylor', 'Tiền vệ', 'Ireland', 'uploads/flags/is.png', '1998-06-26', 14, 'uploads/players/12_8.png', 183.00, 76.00),
(229, 12, 'Massimo Luongo', 'Tiền vệ', 'Úc', 'uploads/flags/au.png', '1992-09-25', 25, 'uploads/players/12_9.png', 178.00, 72.00),
(230, 12, 'Wes Burns', 'Tiền đạo', 'Wales', 'uploads/flags/no_flag.png', '1994-11-23', 7, 'uploads/players/12_10.png', 180.00, 73.00),
(231, 12, 'Conor Chaplin', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1997-02-16', 10, 'uploads/players/12_11.png', 170.00, 65.00),
(232, 12, 'George Hirst', 'Tiền đạo', 'Scotland', 'uploads/flags/gb-sct.png', '1999-02-15', 9, 'uploads/players/12_12.png', 188.00, 82.00),
(233, 12, 'Nathan Broadhead', 'Tiền đạo', 'Wales', 'uploads/flags/no_flag.png', '1998-04-05', 11, 'uploads/players/12_13.png', 178.00, 72.00),
(234, 12, 'Omari Hutchinson', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2003-10-29', 20, 'uploads/players/12_14.png', 175.00, 68.00),
(235, 12, 'Jacob Greaves', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-09-12', 4, 'uploads/players/12_15.png', 191.00, 85.00),
(236, 12, 'Liam Delap', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2003-02-08', 21, 'uploads/players/12_16.png', 185.00, 80.00),
(237, 12, 'Arijanet Muric', 'Thủ môn', 'Kosovo', 'uploads/flags/xk.png', '1998-11-07', 49, 'uploads/players/12_17.png', 196.00, 90.00),
(238, 12, 'Ben Johnson', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-01-24', 32, 'uploads/players/12_18.png', 180.00, 74.00),
(239, 12, 'Conor Townsend', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-10-04', 3, 'uploads/players/12_19.png', 183.00, 76.00),
(240, 12, 'Kalvin Phillips', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-12-02', 23, 'uploads/players/12_20.png', 178.00, 72.00),
(241, 13, 'Danny Ward', 'Thủ môn', 'Wales', 'uploads/flags/no_flag.png', '1993-06-22', 1, 'uploads/players/13_1.png', 191.00, 85.00),
(242, 13, 'Mads Hermansen', 'Thủ môn', 'Đan Mạch', 'uploads/flags/dk.png', '2000-07-11', 30, 'uploads/players/13_2.png', 189.00, 83.00),
(243, 13, 'Jakub Stolarczyk', 'Thủ môn', 'Ba Lan', 'uploads/flags/pl.png', '2000-12-11', 41, 'uploads/players/13_3.png', 195.00, 90.00),
(244, 13, 'James Justin', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1998-02-23', 2, 'uploads/players/13_4.png', 180.00, 74.00),
(245, 13, 'Wout Faes', 'Hậu vệ', 'Bỉ', 'uploads/flags/be.png', '1998-04-03', 3, 'uploads/players/13_5.png', 188.00, 82.00),
(246, 13, 'Conor Coady', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-02-25', 4, 'uploads/players/13_6.png', 185.00, 80.00),
(247, 13, 'Harry Souttar', 'Hậu vệ', 'Úc', 'uploads/flags/au.png', '1998-10-22', 15, 'uploads/players/13_7.png', 203.00, 95.00),
(248, 13, 'Ricardo Pereira', 'Hậu vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1993-10-06', 21, 'uploads/players/13_8.png', 175.00, 70.00),
(249, 13, 'Jannik Vestergaard', 'Hậu vệ', 'Đan Mạch', 'uploads/flags/dk.png', '1992-08-03', 23, 'uploads/players/13_9.png', 198.00, 92.00),
(250, 13, 'Harry Winks', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1996-02-02', 8, 'uploads/players/13_10.png', 170.00, 65.00),
(251, 13, 'Wilfred Ndidi', 'Tiền vệ', 'Nigeria', 'uploads/flags/ng.png', '1996-12-16', 25, 'uploads/players/13_11.png', 185.00, 80.00),
(252, 13, 'Kasey McAteer', 'Tiền vệ', 'Ireland', 'uploads/flags/is.png', '2001-11-22', 35, 'uploads/players/13_12.png', 178.00, 72.00),
(253, 13, 'Jamie Vardy', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1987-01-11', 9, 'uploads/players/13_13.png', 183.00, 76.00),
(254, 13, 'Stephy Mavididi', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1998-05-31', 10, 'uploads/players/13_14.png', 175.00, 70.00),
(255, 13, 'Patson Daka', 'Tiền đạo', 'Zambia', 'uploads/flags/zm.png', '1998-10-09', 20, 'uploads/players/13_15.png', 183.00, 76.00),
(256, 13, 'Daniel Iversen', 'Thủ môn', 'Đan Mạch', 'uploads/flags/dk.png', '1997-07-19', 31, 'uploads/players/13_16.png', 190.00, 84.00),
(257, 13, 'Luke Thomas', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2001-06-10', 33, 'uploads/players/13_17.png', 178.00, 72.00),
(258, 13, 'Boubakary Soumaré', 'Tiền vệ', 'Pháp', 'uploads/flags/fr.png', '1999-02-07', 42, 'uploads/players/13_18.png', 188.00, 82.00),
(259, 13, 'Michael Golding', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2006-01-11', 43, 'uploads/players/13_19.png', 175.00, 68.00),
(260, 13, 'Bobby De Cordova-Reid', 'Tiền đạo', 'Jamaica', 'uploads/flags/jm.png', '1993-06-02', 14, 'uploads/players/13_20.png', 178.00, 72.00),
(261, 14, 'Bart Verbruggen', 'Thủ môn', 'Hà Lan', 'uploads/flags/nl.png', '2002-08-18', 1, 'uploads/players/14_1.png', 195.00, 88.00),
(262, 14, 'Tariq Lamptey', 'Hậu vệ', 'Ghana', 'uploads/flags/gh.png', '2000-09-30', 2, 'uploads/players/14_2.png', 170.00, 65.00),
(263, 14, 'Igor Julio', 'Hậu vệ', 'Brazil', 'uploads/flags/br.png', '1998-02-07', 3, 'uploads/players/14_3.png', 191.00, 85.00),
(264, 14, 'Adam Webster', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-01-04', 4, 'uploads/players/14_4.png', 188.00, 82.00),
(265, 14, 'Lewis Dunk', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1991-11-21', 5, 'uploads/players/14_5.png', 191.00, 85.00),
(266, 14, 'Jan Paul van Hecke', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '2000-06-08', 29, 'uploads/players/14_6.png', 188.00, 82.00),
(267, 14, 'Pervis Estupiñán', 'Hậu vệ', 'Ecuador', 'uploads/flags/ec.png', '1998-01-21', 30, 'uploads/players/14_7.png', 175.00, 70.00),
(268, 14, 'Joël Veltman', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '1992-01-15', 34, 'uploads/players/14_8.png', 183.00, 76.00),
(269, 14, 'James Milner', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1986-01-04', 6, 'uploads/players/14_9.png', 175.00, 70.00),
(270, 14, 'Solly March', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1994-07-20', 7, 'uploads/players/14_10.png', 178.00, 72.00),
(271, 14, 'Kaoru Mitoma', 'Tiền vệ', 'Nhật Bản', 'uploads/flags/jp.png', '1997-05-20', 22, 'uploads/players/14_11.png', 175.00, 68.00),
(272, 14, 'Yasin Ayari', 'Tiền vệ', 'Thụy Điển', 'uploads/flags/se.png', '2003-10-06', 26, 'uploads/players/14_12.png', 180.00, 74.00),
(273, 14, 'João Pedro', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '2001-09-26', 9, 'uploads/players/14_13.png', 183.00, 76.00),
(274, 14, 'Danny Welbeck', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1990-11-26', 18, 'uploads/players/14_14.png', 185.00, 80.00),
(275, 14, 'Simon Adingra', 'Tiền đạo', 'Bờ Biển Ngà', 'uploads/flags/ci.png', '2002-01-01', 11, 'uploads/players/14_15.png', 175.00, 68.00),
(276, 14, 'Carlos Baleba', 'Tiền vệ', 'Cameroon', 'uploads/flags/cm.png', '2004-01-03', 20, 'uploads/players/14_16.png', 180.00, 74.00),
(277, 14, 'Jack Hinshelwood', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-04-11', 41, 'uploads/players/14_17.png', 178.00, 72.00),
(278, 14, 'Carl Rushworth', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '2001-07-02', 39, 'uploads/players/14_18.png', 191.00, 85.00),
(279, 14, 'Yankuba Minteh', 'Tiền đạo', 'Gambia', 'uploads/flags/gm.png', '2004-07-22', 17, 'uploads/players/14_19.png', 175.00, 68.00),
(280, 14, 'Mats Wieffer', 'Tiền vệ', 'Hà Lan', 'uploads/flags/nl.png', '1999-11-16', 27, 'uploads/players/14_20.png', 188.00, 82.00),
(281, 15, 'Alex McCarthy', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1989-12-03', 1, 'uploads/players/15_1.png', 188.00, 82.00),
(282, 15, 'Joe Lumley', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1995-02-02', 31, 'uploads/players/15_2.png', 191.00, 85.00),
(283, 15, 'Gavin Bazunu', 'Thủ môn', 'Ireland', 'uploads/flags/is.png', '2002-02-20', 25, 'uploads/players/15_3.png', 195.00, 88.00),
(284, 15, 'Kyle Walker-Peters', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-04-13', 2, 'uploads/players/15_4.png', 178.00, 72.00),
(285, 15, 'Ryan Manning', 'Hậu vệ', 'Ireland', 'uploads/flags/is.png', '1996-06-14', 3, 'uploads/players/15_5.png', 178.00, 72.00),
(286, 15, 'James Bree', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-12-11', 14, 'uploads/players/15_6.png', 178.00, 72.00),
(287, 15, 'Juan Larios', 'Hậu vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '2004-01-12', 28, 'uploads/players/15_7.png', 180.00, 74.00),
(288, 15, 'Jan Bednarek', 'Hậu vệ', 'Ba Lan', 'uploads/flags/pl.png', '1996-04-12', 35, 'uploads/players/15_8.png', 191.00, 85.00),
(289, 15, 'Joe Aribo', 'Tiền vệ', 'Nigeria', 'uploads/flags/ng.png', '1996-07-21', 7, 'uploads/players/15_9.png', 183.00, 76.00),
(290, 15, 'Will Smallbone', 'Tiền vệ', 'Ireland', 'uploads/flags/is.png', '2000-02-21', 16, 'uploads/players/15_10.png', 178.00, 72.00),
(291, 15, 'Kamaldeen Sulemana', 'Tiền vệ', 'Ghana', 'uploads/flags/gh.png', '2002-02-15', 20, 'uploads/players/15_11.png', 175.00, 68.00),
(292, 15, 'Tyler Dibling', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-09-12', 47, 'uploads/players/15_12.png', 175.00, 68.00),
(293, 15, 'Ross Stewart', 'Tiền đạo', 'Scotland', 'uploads/flags/gb-sct.png', '1996-07-11', 9, 'uploads/players/15_13.png', 191.00, 85.00),
(294, 15, 'Adam Lallana', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1988-05-10', 10, 'uploads/players/15_14.png', 178.00, 72.00),
(295, 15, 'Armel Bella-Kotchap', 'Hậu vệ', 'Đức', 'uploads/flags/de.png', '2001-12-11', 37, 'uploads/players/15_15.png', 191.00, 85.00),
(296, 15, 'Paul Onuachu', 'Tiền đạo', 'Nigeria', 'uploads/flags/ng.png', '1994-05-28', 30, 'uploads/players/15_16.png', 203.00, 95.00),
(297, 15, 'Taylor Harwood-Bellis', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-01-30', 6, 'uploads/players/15_17.png', 191.00, 85.00),
(298, 15, 'Charlie Taylor', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-09-28', 21, 'uploads/players/15_18.png', 183.00, 76.00),
(299, 15, 'Nathan Wood', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-05-22', 39, 'uploads/players/15_19.png', 188.00, 82.00),
(300, 15, 'Yukinari Sugawara', 'Hậu vệ', 'Nhật Bản', 'uploads/flags/jp.png', '2000-06-28', 41, 'uploads/players/15_20.png', 175.00, 68.00),
(301, 16, 'Guglielmo Vicario', 'Thủ môn', 'Ý', 'uploads/flags/it.png', '1996-10-07', 1, 'uploads/players/16_1.png', 191.00, 85.00),
(302, 16, 'Fraser Forster', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1988-03-17', 20, 'uploads/players/16_2.png', 198.00, 92.00),
(303, 16, 'Brandon Austin', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1999-01-08', 40, 'uploads/players/16_3.png', 191.00, 85.00),
(304, 16, 'Alfie Whiteman', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1998-10-02', 41, 'uploads/players/16_4.png', 191.00, 85.00),
(305, 16, 'Sergio Reguilón', 'Hậu vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1996-12-16', 3, 'uploads/players/16_5.png', 180.00, 74.00),
(306, 16, 'Cristian Romero', 'Hậu vệ', 'Argentina', 'uploads/flags/ar.png', '1998-04-27', 17, 'uploads/players/16_6.png', 188.00, 82.00),
(307, 16, 'Pedro Porro', 'Hậu vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1999-09-13', 23, 'uploads/players/16_7.png', 175.00, 70.00),
(308, 16, 'Djed Spence', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-08-09', 24, 'uploads/players/16_8.png', 178.00, 72.00),
(309, 16, 'Ben Davies', 'Hậu vệ', 'Xứ Wales', 'uploads/flags/gb-wls.png', '1993-04-24', 33, 'uploads/players/16_9.png', 183.00, 76.00),
(310, 16, 'Micky van de Ven', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '2001-04-19', 37, 'uploads/players/16_10.png', 191.00, 85.00),
(311, 16, 'Destiny Udogie', 'Hậu vệ', 'Ý', 'uploads/flags/it.png', '2002-11-28', 13, 'uploads/players/16_11.png', 178.00, 72.00),
(312, 16, 'Yves Bissouma', 'Tiền vệ', 'Mali', 'uploads/flags/ml.png', '1996-08-30', 8, 'uploads/players/16_12.png', 183.00, 76.00),
(313, 16, 'Dejan Kulusevski', 'Tiền vệ', 'Thụy Điển', 'uploads/flags/se.png', '2000-04-25', 21, 'uploads/players/16_13.png', 183.00, 76.00),
(314, 16, 'Pape Sarr', 'Tiền vệ', 'Senegal', 'uploads/flags/sn.png', '2002-09-14', 29, 'uploads/players/16_14.png', 178.00, 72.00),
(315, 16, 'Rodrigo Bentancur', 'Tiền vệ', 'Uruguay', 'uploads/flags/uy.png', '1997-06-25', 30, 'uploads/players/16_15.png', 183.00, 76.00),
(316, 16, 'James Maddison', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1996-11-23', 10, 'uploads/players/16_16.png', 175.00, 70.00),
(317, 16, 'Son Heung-Min', 'Tiền đạo', 'Hàn Quốc', 'uploads/flags/kr.png', '1992-07-08', 7, 'uploads/players/16_17.png', 183.00, 76.00),
(318, 16, 'Richarlison', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '1997-05-10', 9, 'uploads/players/16_18.png', 183.00, 76.00),
(319, 16, 'Radu Drăgușin', 'Hậu vệ', 'Romania', 'uploads/flags/ro.png', '2002-02-03', 6, 'uploads/players/16_19.png', 191.00, 85.00),
(320, 16, 'Timo Werner', 'Tiền đạo', 'Đức', 'uploads/flags/de.png', '1996-03-06', 16, 'uploads/players/16_20.png', 183.00, 76.00),
(321, 17, 'Lukasz Fabianski', 'Thủ môn', 'Ba Lan', 'uploads/flags/pl.png', '1985-04-18', 1, 'uploads/players/17_1.png', 191.00, 85.00),
(322, 17, 'Alphonse Areola', 'Thủ môn', 'Pháp', 'uploads/flags/fr.png', '1993-02-27', 23, 'uploads/players/17_2.png', 191.00, 85.00),
(323, 17, 'Aaron Cresswell', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1989-12-15', 3, 'uploads/players/17_3.png', 183.00, 76.00),
(324, 17, 'Vladimír Coufal', 'Hậu vệ', 'Cộng hòa Séc', 'uploads/flags/cz.png', '1992-08-22', 5, 'uploads/players/17_4.png', 178.00, 72.00),
(325, 17, 'Emerson', 'Hậu vệ', 'Ý', 'uploads/flags/it.png', '1994-08-03', 33, 'uploads/players/17_5.png', 175.00, 70.00),
(326, 17, 'Lucas Paquetá', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '1997-08-27', 10, 'uploads/players/17_6.png', 183.00, 76.00),
(327, 17, 'Edson Álvarez', 'Tiền vệ', 'Mexico', 'uploads/flags/mx.png', '1997-10-24', 19, 'uploads/players/17_7.png', 185.00, 80.00),
(328, 17, 'Tomáš Souček', 'Tiền vệ', 'Cộng hòa Séc', 'uploads/flags/cz.png', '1995-02-27', 28, 'uploads/players/17_8.png', 191.00, 85.00),
(329, 17, 'Michail Antonio', 'Tiền đạo', 'Jamaica', 'uploads/flags/jm.png', '1990-03-28', 9, 'uploads/players/17_9.png', 185.00, 80.00),
(330, 17, 'Danny Ings', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1992-07-23', 18, 'uploads/players/17_10.png', 178.00, 72.00),
(331, 17, 'Jarrod Bowen', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1996-12-20', 20, 'uploads/players/17_11.png', 178.00, 72.00),
(332, 17, 'Konstantinos Mavropanos', 'Hậu vệ', 'Hy Lạp', 'uploads/flags/gr.png', '1997-12-11', 15, 'uploads/players/17_12.png', 191.00, 85.00),
(333, 17, 'Kaelan Casey', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2004-10-28', 42, 'uploads/players/17_13.png', 188.00, 82.00),
(334, 17, 'Ollie Scarles', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2005-12-12', 57, 'uploads/players/17_14.png', 175.00, 68.00),
(335, 17, 'James Ward-Prowse', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1994-11-01', 8, 'uploads/players/17_15.png', 178.00, 72.00),
(336, 17, 'Mohammed Kudus', 'Tiền vệ', 'Ghana', 'uploads/flags/gh.png', '2000-08-02', 14, 'uploads/players/17_16.png', 175.00, 68.00),
(337, 17, 'Lewis Orford', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2006-02-18', 61, 'uploads/players/17_17.png', 175.00, 68.00),
(338, 17, 'Luis Guilherme', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '2006-02-09', 17, 'uploads/players/17_18.png', 175.00, 68.00),
(339, 17, 'Wes Foderingham', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1991-01-14', 21, 'uploads/players/17_19.png', 191.00, 85.00),
(340, 17, 'Maximilian Kilman', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-05-23', 26, 'uploads/players/17_20.png', 191.00, 85.00),
(341, 18, 'José Sá', 'Thủ môn', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1993-01-17', 1, 'uploads/players/18_1.png', 191.00, 85.00),
(342, 18, 'Dan Bentley', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1993-07-13', 25, 'uploads/players/18_2.png', 191.00, 85.00),
(343, 18, 'Tom King', 'Thủ môn', 'Xứ Wales', 'uploads/flags/gb-wls.png', '1995-03-09', 40, 'uploads/players/18_3.png', 195.00, 88.00),
(344, 18, 'Matt Doherty', 'Hậu vệ', 'Ireland', 'uploads/flags/is.png', '1992-01-16', 2, 'uploads/players/18_4.png', 183.00, 76.00),
(345, 18, 'Rayan Aït-Nouri', 'Hậu vệ', 'Algeria', 'uploads/flags/dz.png', '2001-06-06', 3, 'uploads/players/18_5.png', 178.00, 72.00),
(346, 18, 'Craig Dawson', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1990-05-06', 15, 'uploads/players/18_6.png', 191.00, 85.00),
(347, 18, 'Nélson Semedo', 'Hậu vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1993-11-16', 22, 'uploads/players/18_7.png', 175.00, 70.00),
(348, 18, 'Toti Gomes', 'Hậu vệ', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1999-01-16', 24, 'uploads/players/18_8.png', 191.00, 85.00),
(349, 18, 'Boubacar Traoré', 'Tiền vệ', 'Mali', 'uploads/flags/ml.png', '2001-08-20', 6, 'uploads/players/18_9.png', 183.00, 76.00),
(350, 18, 'Pablo Sarabia', 'Tiền vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1992-05-11', 21, 'uploads/players/18_10.png', 175.00, 70.00),
(351, 18, 'João Gomes', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '2001-02-12', 8, 'uploads/players/18_11.png', 178.00, 72.00),
(352, 18, 'Hwang Hee-Chan', 'Tiền đạo', 'Hàn Quốc', 'uploads/flags/kr.png', '1996-01-26', 11, 'uploads/players/18_12.png', 183.00, 76.00),
(353, 18, 'Matheus Cunha', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '1999-05-27', 10, 'uploads/players/18_13.png', 183.00, 76.00),
(354, 18, 'Gonçalo Guedes', 'Tiền đạo', 'Bồ Đào Nha', 'uploads/flags/pt.png', '1996-11-29', 29, 'uploads/players/18_14.png', 178.00, 72.00),
(355, 18, 'Sasa Kalajdzic', 'Tiền đạo', 'Áo', 'uploads/flags/at.png', '1997-07-07', 18, 'uploads/players/18_15.png', 198.00, 92.00),
(356, 18, 'Santiago Bueno', 'Hậu vệ', 'Uruguay', 'uploads/flags/uy.png', '1998-11-09', 4, 'uploads/players/18_16.png', 191.00, 85.00),
(357, 18, 'Wes Okoduwa', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2008-05-12', 61, 'uploads/players/18_17.png', 175.00, 68.00),
(358, 18, 'Tommy Doyle', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2001-10-17', 20, 'uploads/players/18_18.png', 178.00, 72.00),
(359, 18, 'Jean-Ricner Bellegarde', 'Tiền vệ', 'Pháp', 'uploads/flags/fr.png', '1998-06-27', 27, 'uploads/players/18_19.png', 175.00, 68.00),
(360, 18, 'Enso González', 'Tiền đạo', 'Paraguay', 'uploads/flags/py.png', '2005-01-20', 30, 'uploads/players/18_20.png', 175.00, 68.00),
(361, 19, 'Martin Dúbravka', 'Thủ môn', 'Slovakia', 'uploads/flags/sk.png', '1989-01-15', 1, 'uploads/players/19_1.png', 191.00, 85.00),
(362, 19, 'Nick Pope', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1992-04-19', 22, 'uploads/players/19_2.png', 191.00, 85.00),
(363, 19, 'Mark Gillespie', 'Thủ môn', 'Anh', 'uploads/flags/gb-eng.png', '1992-03-27', 29, 'uploads/players/19_3.png', 191.00, 85.00),
(364, 19, 'Kieran Trippier', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1990-09-19', 2, 'uploads/players/19_4.png', 175.00, 70.00),
(365, 19, 'Sven Botman', 'Hậu vệ', 'Hà Lan', 'uploads/flags/nl.png', '2000-01-12', 4, 'uploads/players/19_5.png', 191.00, 85.00),
(366, 19, 'Fabian Schär', 'Hậu vệ', 'Thụy Sĩ', 'uploads/flags/ch.png', '1991-12-20', 5, 'uploads/players/19_6.png', 188.00, 82.00),
(367, 19, 'Jamaal Lascelles', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1993-11-11', 6, 'uploads/players/19_7.png', 191.00, 85.00),
(368, 19, 'Matt Targett', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-09-18', 13, 'uploads/players/19_8.png', 183.00, 76.00),
(369, 19, 'Emil Krafth', 'Hậu vệ', 'Thụy Điển', 'uploads/flags/se.png', '1994-08-02', 17, 'uploads/players/19_9.png', 183.00, 76.00),
(370, 19, 'Tino Livramento', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-11-12', 21, 'uploads/players/19_10.png', 178.00, 72.00),
(371, 19, 'Dan Burn', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1992-05-09', 33, 'uploads/players/19_11.png', 198.00, 92.00),
(372, 19, 'Sandro Tonali', 'Tiền vệ', 'Ý', 'uploads/flags/it.png', '2000-05-08', 8, 'uploads/players/19_12.png', 183.00, 76.00),
(373, 19, 'Harvey Barnes', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-12-09', 11, 'uploads/players/19_13.png', 178.00, 72.00),
(374, 19, 'Jacob Murphy', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-02-24', 23, 'uploads/players/19_14.png', 178.00, 72.00),
(375, 19, 'Joe Willock', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1999-08-20', 28, 'uploads/players/19_15.png', 183.00, 76.00),
(376, 19, 'Sean Longstaff', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-10-30', 36, 'uploads/players/19_16.png', 183.00, 76.00),
(377, 19, 'Bruno Guimarães', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '1997-11-16', 39, 'uploads/players/19_17.png', 183.00, 76.00);
INSERT INTO `players` (`player_id`, `team_id`, `name`, `position`, `nationality`, `nationality_flag_url`, `birth_date`, `jersey_number`, `photo_url`, `height`, `weight`) VALUES
(378, 19, 'Lewis Miley', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2006-05-01', 67, 'uploads/players/19_18.png', 175.00, 68.00),
(379, 19, 'Joelinton', 'Tiền đạo', 'Brazil', 'uploads/flags/br.png', '1996-08-14', 7, 'uploads/players/19_19.png', 191.00, 85.00),
(380, 19, 'Callum Wilson', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '1992-02-27', 9, 'uploads/players/19_20.png', 183.00, 76.00),
(381, 20, 'Neco Williams', 'Hậu vệ', 'Xứ Wales', 'uploads/flags/gb-wls.png', '2001-04-13', 7, 'uploads/players/20_1.png', 175.00, 70.00),
(382, 20, 'Harry Toffolo', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '1995-08-19', 15, 'uploads/players/20_2.png', 183.00, 76.00),
(383, 20, 'Willy-Arnaud Boly', 'Hậu vệ', 'Bờ Biển Ngà', 'uploads/flags/ci.png', '1991-02-03', 30, 'uploads/players/20_3.png', 191.00, 85.00),
(384, 20, 'Ola Aina', 'Hậu vệ', 'Nigeria', 'uploads/flags/ng.png', '1996-10-08', 34, 'uploads/players/20_4.png', 178.00, 72.00),
(385, 20, 'Morgan Gibbs-White', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2000-01-27', 10, 'uploads/players/20_5.png', 178.00, 72.00),
(386, 20, 'Ryan Yates', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '1997-11-21', 22, 'uploads/players/20_6.png', 183.00, 76.00),
(387, 20, 'Danilo', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '1991-07-15', 13, 'uploads/players/20_7.png', 178.00, 72.00),
(388, 20, 'Taiwo Awoniyi', 'Tiền đạo', 'Nigeria', 'uploads/flags/ng.png', '1997-08-12', 9, 'uploads/players/20_8.png', 188.00, 82.00),
(389, 20, 'Chris Wood', 'Tiền đạo', 'New Zealand', 'uploads/flags/nz.png', '1991-12-07', 11, 'uploads/players/20_9.png', 191.00, 85.00),
(390, 20, 'Anthony Elanga', 'Tiền đạo', 'Thụy Điển', 'uploads/flags/se.png', '2002-04-27', 21, 'uploads/players/20_10.png', 175.00, 68.00),
(391, 20, 'Matz Sels', 'Thủ môn', 'Bỉ', 'uploads/flags/be.png', '1992-02-26', 26, 'uploads/players/20_11.png', 191.00, 85.00),
(392, 20, 'Murillo', 'Hậu vệ', 'Brazil', 'uploads/flags/br.png', '2002-07-04', 5, 'uploads/players/20_12.png', 191.00, 85.00),
(393, 20, 'Zach Abbott', 'Hậu vệ', 'Anh', 'uploads/flags/gb-eng.png', '2006-05-13', 44, 'uploads/players/20_13.png', 175.00, 68.00),
(394, 20, 'Ibrahim Sangaré', 'Tiền vệ', 'Bờ Biển Ngà', 'uploads/flags/ci.png', '1997-12-02', 6, 'uploads/players/20_14.png', 191.00, 85.00),
(395, 20, 'Nicolas Dominguez', 'Tiền vệ', 'Argentina', 'uploads/flags/ar.png', '1998-06-28', 16, 'uploads/players/20_15.png', 178.00, 72.00),
(396, 20, 'Callum Hudson-Odoi', 'Tiền đạo', 'Anh', 'uploads/flags/gb-eng.png', '2000-11-07', 14, 'uploads/players/20_16.png', 178.00, 72.00),
(397, 20, 'Elliot Anderson', 'Tiền vệ', 'Anh', 'uploads/flags/gb-eng.png', '2002-11-06', 8, 'uploads/players/20_17.png', 178.00, 72.00),
(398, 20, 'Eric da Silva Moreira', 'Tiền đạo', 'Đức', 'uploads/flags/de.png', '2006-05-03', 17, 'uploads/players/20_18.png', 175.00, 68.00),
(399, 20, 'Nikola Milenkovic', 'Hậu vệ', 'Serbia', 'uploads/flags/rs.png', '1997-10-12', 31, 'uploads/players/20_19.png', 198.00, 92.00),
(400, 20, 'Carlos Miguel', 'Thủ môn', 'Brazil', 'uploads/flags/br.png', '1998-10-09', 33, 'uploads/players/20_20.png', 198.00, 92.00),
(401, 4, 'Mikel Merino', 'Tiền vệ', 'Tây Ban Nha', 'uploads/flags/es.png', '1996-06-22', 8, 'uploads/players/4_21.png', NULL, NULL),
(407, 13, 'Facundo Buonanotte', 'Tiền vệ', 'Argentina', 'uploads/flags/ar.png', '2004-12-23', 40, 'uploads/players/13_30.png', NULL, NULL),
(408, 20, 'Morato', 'Tiền vệ', 'Brazil', 'uploads/flags/br.png', '2001-06-30', 5, 'uploads/players/20_31.png', NULL, NULL),
(409, 1, 'Nguyễn Văn B', 'Tiền vệ', 'Việt Nam', NULL, '2025-05-05', 14, '0', 182.00, 65.00),
(411, 1, 'Nguyễn Thu Hường', 'Tiền đạo', 'Việt Nam', NULL, '2025-05-06', 19, 'uploads/players/1748521136_dora.png', 165.00, 50.00),
(412, 12, 'Trần Thúy Nga', 'Hậu vệ', 'Việt Nam', NULL, '2025-05-05', 22, '0', 160.00, 45.00),
(413, 4, 'Trịnh Thùy Linh', 'Hậu vệ', 'Pháp', NULL, '2025-05-01', 12, '0', 165.00, 49.00),
(414, 6, 'Vũ Minh Hòa', 'Tiền vệ', 'barzil', NULL, '2025-05-06', 14, '0', 187.00, 66.00),
(416, 16, 'Trần văn Linh', 'Hậu vệ', 'Việt nam', NULL, '2025-05-06', 13, '0', 185.00, 78.00),
(417, 1, 'Nguyễn văn C', 'Tiền vệ', 'Việt nam', NULL, '2025-05-06', 14, 'uploads/players/1748535122_ar.png', 188.00, 78.00);

-- --------------------------------------------------------

--
-- Table structure for table `playerstats`
--

CREATE TABLE `playerstats` (
  `stat_id` int NOT NULL,
  `player_id` int NOT NULL,
  `season_id` int DEFAULT NULL,
  `matches_played` int DEFAULT '0',
  `goals` int DEFAULT '0',
  `assists` int DEFAULT '0',
  `yellow_cards` int DEFAULT '0',
  `red_cards` int DEFAULT '0',
  `minutes_played` int DEFAULT '0',
  `clean_sheets` int DEFAULT '0',
  `penalties_scored` int DEFAULT '0',
  `penalties_missed` int DEFAULT '0',
  `saves` int DEFAULT '0',
  `passes` int DEFAULT '0',
  `key_passes` int DEFAULT '0',
  `total_goals` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `playerstats`
--

INSERT INTO `playerstats` (`stat_id`, `player_id`, `season_id`, `matches_played`, `goals`, `assists`, `yellow_cards`, `red_cards`, `minutes_played`, `clean_sheets`, `penalties_scored`, `penalties_missed`, `saves`, `passes`, `key_passes`, `total_goals`) VALUES
(3411, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3412, 2, 1, 2, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 0),
(3413, 3, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3414, 4, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3415, 5, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3416, 6, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3417, 7, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3418, 8, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3419, 9, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3420, 10, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3421, 11, 1, 4, 2, 4, 0, 0, 0, 0, 1, 0, 0, 0, 0, 3),
(3422, 12, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3423, 13, 1, 8, 2, 2, 7, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3424, 14, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3425, 15, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3426, 16, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3427, 17, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3428, 18, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3429, 19, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3430, 20, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3431, 409, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3432, 411, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3433, 417, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3434, 21, 1, 2, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0),
(3435, 22, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3436, 23, 1, 3, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3437, 24, 1, 6, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3438, 25, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3439, 26, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3440, 27, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3441, 28, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3442, 29, 1, 2, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3443, 30, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3444, 31, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3445, 32, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3446, 33, 1, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3447, 34, 1, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4),
(3448, 35, 1, 9, 7, 2, 0, 0, 0, 0, 4, 0, 0, 0, 0, 11),
(3449, 36, 1, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3450, 37, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3451, 38, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3452, 39, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3453, 40, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3454, 41, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3455, 42, 1, 1, 1, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 2),
(3456, 43, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3457, 44, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3458, 45, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1),
(3459, 46, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3460, 47, 1, 4, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 2),
(3461, 48, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3462, 49, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3463, 50, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3464, 51, 1, 8, 5, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5),
(3465, 52, 1, 2, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3466, 53, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3467, 54, 1, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0),
(3468, 55, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3469, 56, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3470, 57, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3471, 58, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3472, 59, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3473, 60, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3474, 61, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3475, 62, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3476, 63, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3477, 64, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3478, 65, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3479, 66, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3480, 67, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3481, 68, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3482, 69, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3483, 70, 1, 7, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7),
(3484, 71, 1, 3, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3485, 72, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3486, 73, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3487, 74, 1, 4, 1, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3488, 75, 1, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3489, 76, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3490, 77, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3491, 78, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3492, 79, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3493, 80, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3494, 401, 1, 1, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1),
(3495, 413, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3496, 81, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3497, 82, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3498, 83, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3499, 84, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3500, 85, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3501, 86, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3502, 87, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3503, 88, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3504, 89, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3505, 90, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3506, 91, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3507, 92, 1, 6, 1, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3508, 93, 1, 6, 4, 3, 0, 0, 0, 0, 0, 1, 0, 0, 0, 4),
(3509, 94, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3510, 95, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3511, 96, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3512, 97, 1, 8, 7, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 9),
(3513, 98, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3514, 99, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3515, 100, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3516, 101, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(3517, 102, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3518, 103, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3519, 104, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3520, 105, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3521, 106, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3522, 107, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3523, 108, 1, 6, 1, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3524, 109, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3525, 110, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3526, 111, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3527, 112, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3528, 113, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3529, 114, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3530, 115, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3531, 116, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3532, 117, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3533, 118, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3534, 119, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3535, 120, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3536, 414, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3537, 121, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3538, 122, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3539, 123, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3540, 124, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3541, 125, 1, 6, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3542, 126, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3543, 127, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3544, 128, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3545, 129, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3546, 130, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3547, 131, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3548, 132, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3549, 133, 1, 6, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6),
(3550, 134, 1, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3551, 135, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3552, 136, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3553, 137, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3554, 138, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3555, 139, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3556, 140, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3557, 141, 1, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3558, 142, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3559, 143, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3560, 144, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3561, 145, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3562, 146, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3563, 147, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3564, 148, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3565, 149, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3566, 150, 1, 6, 1, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3567, 151, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3568, 152, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3569, 153, 1, 3, 2, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3570, 154, 1, 7, 4, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 4),
(3571, 155, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3572, 156, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3573, 157, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3574, 158, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3575, 159, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3576, 160, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3577, 161, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3578, 162, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3579, 163, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3580, 164, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3581, 165, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3582, 166, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3583, 167, 1, 7, 1, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3584, 168, 1, 8, 6, 3, 1, 0, 0, 0, 0, 1, 0, 0, 0, 6),
(3585, 169, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3586, 170, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3587, 171, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3588, 172, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3589, 173, 1, 2, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 2),
(3590, 174, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3591, 175, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3592, 176, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3593, 177, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3594, 178, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3595, 179, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3596, 180, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3597, 181, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3598, 182, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3599, 183, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3600, 184, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3601, 185, 1, 4, 1, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3602, 186, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3603, 187, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3604, 188, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3605, 189, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3606, 190, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3607, 191, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3608, 192, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3609, 193, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3610, 194, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3611, 195, 1, 5, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5),
(3612, 196, 1, 2, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3613, 197, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3614, 198, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3615, 199, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3616, 200, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3618, 201, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3619, 202, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3620, 203, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3621, 204, 1, 2, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3622, 205, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3623, 206, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3624, 207, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3625, 208, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3626, 209, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3627, 210, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3628, 211, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3629, 212, 1, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3630, 213, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3631, 214, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3632, 215, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3633, 216, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3634, 217, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3635, 218, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3636, 219, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3637, 220, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3638, 221, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3639, 222, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3640, 223, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3641, 224, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3642, 225, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3643, 226, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3644, 227, 1, 3, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3645, 228, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3646, 229, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3647, 230, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3648, 231, 1, 4, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4),
(3649, 232, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3650, 233, 1, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3651, 234, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3652, 235, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3653, 236, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3654, 237, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3655, 238, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3656, 239, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3657, 240, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3658, 412, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3659, 241, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3660, 242, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3661, 243, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3662, 244, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3663, 245, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3664, 246, 1, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3665, 247, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3666, 248, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3667, 249, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3668, 250, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3669, 251, 1, 3, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3670, 252, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1),
(3671, 253, 1, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3672, 254, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3673, 255, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3674, 256, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3675, 257, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3676, 258, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3677, 259, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3678, 260, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3679, 407, 1, 2, 2, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3680, 261, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3681, 262, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3682, 263, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3683, 264, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3684, 265, 1, 4, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3685, 266, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3686, 267, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3687, 268, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3688, 269, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3689, 270, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3690, 271, 1, 8, 8, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8),
(3691, 272, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3692, 273, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3693, 274, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3694, 275, 1, 2, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3695, 276, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3696, 277, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3697, 278, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3698, 279, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3699, 280, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3700, 281, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3701, 282, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3702, 283, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3703, 284, 1, 3, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3704, 285, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3705, 286, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3706, 287, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3707, 288, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3708, 289, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3709, 290, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3710, 291, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3711, 292, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3712, 293, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3713, 294, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3714, 295, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3715, 296, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3716, 297, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3717, 298, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3718, 299, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3719, 300, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3720, 301, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3721, 302, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3722, 303, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3723, 304, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3724, 305, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3725, 306, 1, 6, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3726, 307, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3727, 308, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3728, 309, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3729, 310, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3730, 311, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3731, 312, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3732, 313, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3733, 314, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3734, 315, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3735, 316, 1, 3, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3736, 317, 1, 5, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5),
(3737, 318, 1, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3738, 319, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3739, 320, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3740, 416, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3741, 321, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3742, 322, 1, 2, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0),
(3743, 323, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3744, 324, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3745, 325, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3746, 326, 1, 7, 2, 2, 5, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3747, 327, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3748, 328, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3749, 329, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3750, 330, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3751, 331, 1, 8, 7, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7),
(3752, 332, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3753, 333, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3754, 334, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3755, 335, 1, 6, 5, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 5),
(3756, 336, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3757, 337, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3758, 338, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3759, 339, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3760, 340, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3761, 341, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3762, 342, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3763, 343, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3764, 344, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3765, 345, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3766, 346, 1, 6, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3767, 347, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3768, 348, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3769, 349, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3770, 350, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3771, 351, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3772, 352, 1, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3773, 353, 1, 4, 3, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3774, 354, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3775, 355, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3776, 356, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3777, 357, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3778, 358, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3779, 359, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3780, 360, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3781, 361, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3782, 362, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3783, 363, 1, 1, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0),
(3784, 364, 1, 3, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3785, 365, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3786, 366, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3787, 367, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3788, 368, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3789, 369, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3790, 370, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3791, 371, 1, 4, 1, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3792, 372, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3793, 373, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3794, 374, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3795, 375, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3796, 376, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3797, 377, 1, 6, 4, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 4),
(3798, 378, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3799, 379, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3800, 380, 1, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3),
(3801, 381, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3802, 382, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3803, 383, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3804, 384, 1, 3, 1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3805, 385, 1, 5, 4, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4),
(3806, 386, 1, 2, 1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3807, 387, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3808, 388, 1, 4, 3, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 4),
(3809, 389, 1, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2),
(3810, 390, 1, 2, 1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3811, 391, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0),
(3812, 392, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3813, 393, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3814, 394, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3815, 395, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3816, 396, 1, 2, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1),
(3817, 397, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3818, 398, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3819, 399, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3820, 400, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3821, 408, 1, 2, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `seasons`
--

CREATE TABLE `seasons` (
  `season_id` int NOT NULL,
  `name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `seasons`
--

INSERT INTO `seasons` (`season_id`, `name`, `start_date`, `end_date`) VALUES
(1, '2024/2025', '2024-08-10', '2025-05-25');

-- --------------------------------------------------------

--
-- Table structure for table `stadiums`
--

CREATE TABLE `stadiums` (
  `stadium_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `capacity` int DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `city` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `built_year` int DEFAULT NULL,
  `photo_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `stadiums`
--

INSERT INTO `stadiums` (`stadium_id`, `name`, `capacity`, `address`, `city`, `built_year`, `photo_url`) VALUES
(1, 'Old Trafford', 74879, 'Sir Matt Busby Way', 'Manchester', 1910, 'uploads/stadiums/old_trafford.png'),
(2, 'Anfield', 54074, 'Anfield Road', 'Liverpool', 1884, 'uploads/stadiums/anfield.png'),
(3, 'Stamford Bridge', 41837, 'Fulham Road', 'London', 1877, 'uploads/stadiums/stamford_bridge.png'),
(4, 'Emirates Stadium', 60704, 'Highbury House, 75 Drayton Park', 'London', 2006, 'uploads/stadiums/emirates.png'),
(5, 'Etihad Stadium', 53400, 'Ashton New Road', 'Manchester', 2003, 'uploads/stadiums/etihad.png'),
(21, 'Vitality Stadium', 11700, 'Kings Park, Bournemouth BH7 7AF, UK', 'Bournemouth', 1957, 'uploads/stadiums/Vitality_Stadium.png'),
(22, 'Villa Park', 42749, 'Trinity Rd, Birmingham B6 6HE, UK', 'Birmingham', 1897, 'uploads/stadiums/Villa_Park.png'),
(23, 'Brentford Community Stadium', 17250, '166 Lionel Rd N, Brentford TW8 9QT, UK', 'London', 2020, 'uploads/stadiums/Brentford_Community_Stadium.png'),
(24, 'Selhurst Park', 25486, 'Whitehorse Ln, London SE25 6PU, UK', 'London', 1924, 'uploads/stadiums/Selhurst_Park.png'),
(25, 'Goodison Park', 39414, 'Goodison Rd, Liverpool L4 4EL, UK', 'Liverpool', 1892, 'uploads/stadiums/Goodison_Park.png'),
(26, 'Craven Cottage', 25400, 'Stevenage Rd, London SW6 6HH, UK', 'London', 1896, 'uploads/stadiums/Craven_Cottage.png'),
(27, 'Portman Road', 29820, 'Portman Rd, Ipswich IP1 2DA, UK', 'Ipswich', 1884, 'uploads/stadiums/Portman_Road.png'),
(28, 'King Power Stadium', 32312, 'Filbert Way, Leicester LE2 7FL, UK', 'Leicester', 2002, 'uploads/stadiums/King_Power_Stadium.png'),
(29, 'St Mary’s Stadium', 32600, 'Britannia Rd, Southampton SO14 5FP, UK', 'Southampton', 2001, 'uploads/stadiums/St_Marys_Stadium.png'),
(30, 'Tottenham Hotspur Stadium', 62850, '782 High Rd, London N17 0AP, UK', 'London', 2019, 'uploads/stadiums/Tottenham_Hotspur_Stadium.png'),
(31, 'Olympic Stadium', 60000, 'London E20 2ST, UK', 'London', 2012, 'uploads/stadiums/Olympic_Stadium.png'),
(32, 'Molineux', 32050, 'Waterloo Rd, Wolverhampton WV1 4QR, UK', 'Wolverhampton', 1889, 'uploads/stadiums/Molineux.png'),
(33, 'St James\' Park', 52350, 'St James\' St, Newcastle upon Tyne NE1 4ST, UK', 'Newcastle upon Tyne', 1892, 'uploads/stadiums/St_James_Park.png'),
(34, 'American Express Stadium', 31876, 'Village Way, Falmer, Brighton BN1 9BL, UK', 'Brighton', 2011, 'uploads/stadiums/Amex_Stadium.png'),
(35, 'The City Ground', 30245, 'Nottingham NG2 5FJ, UK', 'Nottingham', 1898, 'uploads/stadiums/The_City_Ground.png'),
(36, 'dư', 665, 'hh', 'nn', 1845, 'uploads/stadiums/683d72413c2cb.jpg'),
(37, 'hyh', 12589, 'hjk', 'ghj', 1924, 'uploads/stadiums/683d72ec448d0.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `standings`
--

CREATE TABLE `standings` (
  `standing_id` int NOT NULL,
  `season_id` int DEFAULT NULL,
  `team_id` int DEFAULT NULL,
  `matches_played` int DEFAULT '0',
  `wins` int DEFAULT '0',
  `draws` int DEFAULT '0',
  `losses` int DEFAULT '0',
  `goals_for` int DEFAULT '0',
  `goals_against` int DEFAULT '0',
  `points` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `standings`
--

INSERT INTO `standings` (`standing_id`, `season_id`, `team_id`, `matches_played`, `wins`, `draws`, `losses`, `goals_for`, `goals_against`, `points`) VALUES
(266, 1, 2, 11, 9, 0, 2, 24, 11, 27),
(267, 1, 5, 12, 8, 3, 1, 19, 7, 27),
(268, 1, 3, 13, 8, 2, 3, 19, 11, 26),
(269, 1, 19, 13, 8, 1, 4, 23, 11, 25),
(270, 1, 7, 11, 8, 0, 3, 19, 10, 24),
(271, 1, 8, 11, 6, 3, 2, 18, 12, 21),
(272, 1, 18, 11, 6, 1, 4, 15, 12, 19),
(273, 1, 20, 14, 6, 4, 4, 17, 16, 22),
(274, 1, 14, 11, 5, 3, 3, 20, 18, 18),
(275, 1, 4, 12, 4, 5, 3, 17, 12, 17),
(276, 1, 9, 12, 4, 4, 4, 16, 20, 16),
(277, 1, 1, 12, 4, 3, 5, 14, 16, 15),
(278, 1, 6, 12, 3, 5, 4, 14, 15, 14),
(279, 1, 10, 12, 3, 5, 4, 11, 14, 14),
(280, 1, 11, 12, 4, 0, 8, 14, 18, 12),
(281, 1, 17, 11, 2, 4, 5, 13, 14, 10),
(282, 1, 16, 11, 1, 3, 7, 11, 22, 6),
(283, 1, 15, 12, 1, 3, 8, 7, 22, 6),
(284, 1, 13, 13, 1, 3, 9, 6, 22, 6),
(285, 1, 12, 11, 1, 2, 8, 11, 25, 5);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `short_name` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `logo_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `stadium_id` int DEFAULT NULL,
  `city` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `founded_year` int DEFAULT NULL,
  `facebook` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `twitter` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `instagram` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `youtube` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_vietnamese_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_vietnamese_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `name`, `short_name`, `logo_url`, `stadium_id`, `city`, `founded_year`, `facebook`, `twitter`, `instagram`, `youtube`, `website`) VALUES
(1, 'Manchester United ', 'MU', 'uploads/teams/mu_logo.png', 1, 'Manchester', 1878, 'https://www.facebook.com/manchesterunited', 'https://twitter.com/ManUtd', 'https://www.instagram.com/manchesterunited/', 'https://www.youtube.com/manutd', 'https://www.manutd.com'),
(2, 'Liverpool FC', 'LFC', 'uploads/teams/lfc_logo.png', 2, 'Liverpool', 1892, 'https://www.facebook.com/LiverpoolFC', 'https://twitter.com/LFC', 'https://www.instagram.com/liverpoolfc/', 'https://www.youtube.com/LiverpoolFC', 'https://www.liverpoolfc.com'),
(3, 'Chelsea FC', 'CFC', 'uploads/teams/cfc_logo.png', 3, 'London', 1905, 'https://www.facebook.com/ChelseaFC', 'https://twitter.com/Chelseafc', 'https://www.instagram.com/chelseafc/', 'https://www.youtube.com/chelseafc', 'https://www.chelseafc.com'),
(4, 'Arsenal FC', 'AFC', 'uploads/teams/afc_logo.png', 4, 'London', 1886, 'https://www.facebook.com/Arsenal', 'https://twitter.com/Arsenal', 'https://www.instagram.com/arsenal/', 'https://www.youtube.com/user/ArsenalTour', 'https://www.arsenal.com'),
(5, 'Manchester City', 'MCFC', 'uploads/teams/mcfc_logo.png', 5, 'Manchester', 1880, 'https://www.facebook.com/mancity', 'https://twitter.com/ManCity', 'https://www.instagram.com/mancity/', 'https://www.youtube.com/mcfcofficial', 'https://www.mancity.com'),
(6, 'AFC Bournemouth', 'AFCB', 'uploads/teams/afcb_logo.png', 21, 'Bournemouth', 1899, 'https://www.facebook.com/officialafcb', 'https://twitter.com/afcbournemouth', 'https://www.instagram.com/afcb/', 'https://www.youtube.com/officialafcb', 'https://www.afcb.co.uk'),
(7, 'Aston Villa', 'AVFC', 'uploads/teams/avfc_logo.png', 22, 'Birmingham', 1874, 'https://www.facebook.com/avfcofficial', 'https://twitter.com/AVFCOfficial', 'https://www.instagram.com/avfcofficial/', 'https://www.youtube.com/user/avfcofficial', 'https://www.avfc.co.uk'),
(8, 'Brentford', 'BRE', 'uploads/teams/brentford_logo.png', 23, 'London', 1889, 'https://www.facebook.com/brentfordfootballclub', 'https://twitter.com/BrentfordFC', 'https://www.instagram.com/brentfordfc/', 'https://www.youtube.com/brentfordfc', 'https://www.brentfordfc.com'),
(9, 'Crystal Palace', 'CPFC', 'uploads/teams/cpfc_logo.png', 24, 'London', 1905, 'https://www.facebook.com/officialcpfc', 'https://twitter.com/CPFC', 'https://www.instagram.com/officialcpfc/', 'https://www.youtube.com/user/officialcpfc', 'https://www.cpfc.co.uk'),
(10, 'Everton', 'EFC', 'uploads/teams/efc_logo.png', 25, 'Liverpool', 1878, 'https://www.facebook.com/Everton', 'https://twitter.com/Everton', 'https://www.instagram.com/everton/', 'https://www.youtube.com/everton', 'https://www.evertonfc.com'),
(11, 'Fulham', 'FUL', 'uploads/teams/fulham_logo.png', 26, 'London', 1879, 'https://www.facebook.com/FulhamFC', 'https://twitter.com/FulhamFC', 'https://www.instagram.com/fulhamfc/', 'https://www.youtube.com/fulhamfc', 'https://www.fulhamfc.com'),
(12, 'Ipswich Town', 'ITFC', 'uploads/teams/itfc_logo.png', 27, 'Ipswich', 1878, 'https://www.facebook.com/officialitfc', 'https://twitter.com/ipswichtown', 'https://www.instagram.com/ipswichtown/', 'https://www.youtube.com/ipswichtown', 'https://www.itfc.co.uk'),
(13, 'Leicester City', 'LCFC', 'uploads/teams/lcfc_logo.png', 28, 'Leicester', 1884, 'https://www.facebook.com/LeicesterCityFC', 'https://twitter.com/LCFC', 'https://www.instagram.com/lcfc/', 'https://www.youtube.com/LCFC', 'https://www.lcfc.com'),
(14, 'Brighton & Hove Albion FC', 'BHAFC', 'uploads/teams/bhafc_logo.png', 34, 'Brighton', 1901, 'https://www.facebook.com/OfficialBHAFC', 'https://twitter.com/OfficialBHAFC', 'https://www.instagram.com/officialbhafc/', 'https://www.youtube.com/OfficialBHAFC', 'https://www.brightonandhovealbion.com'),
(15, 'Southampton', 'SOU', 'uploads/teams/sou_logo.png', 29, 'Southampton', 1885, 'https://www.facebook.com/SouthamptonFC', 'https://twitter.com/SouthamptonFC', 'https://www.instagram.com/southamptonfc/', 'https://www.youtube.com/southamptonfc', 'https://www.southamptonfc.com'),
(16, 'Tottenham Hotspur', 'THFC', 'uploads/teams/thfc_logo.png', 30, 'London', 1882, 'https://www.facebook.com/TottenhamHotspur', 'https://twitter.com/SpursOfficial', 'https://www.instagram.com/spursofficial/', 'https://www.youtube.com/spursofficial', 'https://www.tottenhamhotspur.com'),
(17, 'West Ham United', 'WHU', 'uploads/teams/whu_logo.png', 31, 'London', 1895, 'https://www.facebook.com/westhamunited', 'https://twitter.com/WestHam', 'https://www.instagram.com/westham/', 'https://www.youtube.com/WestHamUnitedFC', 'https://www.whufc.com'),
(18, 'Wolverhampton Wanderers', 'WOL', 'uploads/teams/wol_logo.png', 32, 'Wolverhampton', 1877, 'https://www.facebook.com/WWFCOfficial', 'https://twitter.com/Wolves', 'https://www.instagram.com/wolves/', 'https://www.youtube.com/officialwolves', 'https://www.wolves.co.uk'),
(19, 'Newcastle United', 'NUFC', 'uploads/teams/nufc_logo.png', 33, 'Newcastle upon Tyne', 1892, 'https://www.facebook.com/newcastleunited', 'https://twitter.com/NUFC', 'https://www.instagram.com/nufc/', 'https://www.youtube.com/officialNUFC', 'https://www.nufc.co.uk'),
(20, 'Nottingham Forest', 'NFFC', 'uploads/teams/nffc_logo.png', 35, 'Nottingham', 1865, 'https://www.facebook.com/officialnffc', 'https://twitter.com/NFFC', 'https://www.instagram.com/officialnffc/', 'https://www.youtube.com/officialnffc', 'https://www.nottinghamforest.co.uk');

-- --------------------------------------------------------

--
-- Table structure for table `teamstats`
--

CREATE TABLE `teamstats` (
  `stat_id` int NOT NULL,
  `team_id` int NOT NULL,
  `season_id` int DEFAULT NULL,
  `matches_played` int DEFAULT '0',
  `wins` int DEFAULT '0',
  `draws` int DEFAULT '0',
  `losses` int DEFAULT '0',
  `goals_for` int DEFAULT '0',
  `goals_against` int DEFAULT '0',
  `clean_sheets` int DEFAULT '0',
  `points` int DEFAULT '0',
  `position` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teamstats`
--

INSERT INTO `teamstats` (`stat_id`, `team_id`, `season_id`, `matches_played`, `wins`, `draws`, `losses`, `goals_for`, `goals_against`, `clean_sheets`, `points`, `position`) VALUES
(52, 1, 1, 12, 4, 3, 5, 14, 16, 4, 15, NULL),
(53, 2, 1, 11, 9, 0, 2, 24, 11, 5, 27, NULL),
(54, 3, 1, 13, 8, 2, 3, 19, 11, 6, 26, NULL),
(55, 4, 1, 12, 4, 5, 3, 17, 12, 4, 17, NULL),
(56, 5, 1, 12, 8, 3, 1, 19, 7, 7, 27, NULL),
(57, 6, 1, 12, 3, 5, 4, 14, 15, 2, 14, NULL),
(58, 7, 1, 11, 8, 0, 3, 19, 10, 5, 24, NULL),
(59, 8, 1, 11, 6, 3, 2, 18, 12, 3, 21, NULL),
(60, 9, 1, 12, 4, 4, 4, 16, 20, 3, 16, NULL),
(61, 10, 1, 12, 3, 5, 4, 11, 14, 2, 14, NULL),
(62, 11, 1, 12, 4, 0, 8, 14, 18, 1, 12, NULL),
(63, 12, 1, 11, 1, 2, 8, 11, 25, 0, 5, NULL),
(64, 13, 1, 12, 1, 3, 8, 6, 21, 1, 6, NULL),
(65, 15, 1, 12, 1, 3, 8, 7, 22, 2, 6, NULL),
(66, 16, 1, 11, 1, 3, 7, 11, 22, 0, 6, NULL),
(67, 17, 1, 11, 2, 4, 5, 13, 14, 2, 10, NULL),
(68, 18, 1, 11, 6, 1, 4, 15, 12, 3, 19, NULL),
(69, 19, 1, 13, 8, 1, 4, 23, 11, 6, 25, NULL),
(70, 14, 1, 11, 5, 3, 3, 20, 18, 2, 18, NULL),
(71, 20, 1, 13, 5, 4, 4, 16, 16, 4, 19, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `avatar_url` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` enum('Nam','Nữ','Không xác định') DEFAULT 'Không xác định',
  `birthday` date DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `favorite_teams` varchar(255) DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `is_locked` tinyint(1) DEFAULT '0',
  `lock_until` datetime DEFAULT NULL,
  `lock_reason` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `avatar_url`, `birth_date`, `sex`, `birthday`, `country`, `phone_number`, `favorite_teams`, `login_attempts`, `is_locked`, `lock_until`, `lock_reason`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$I2dClYF5YWnPxtkPWDM92uE6kC9w18fcZAg1.des7EMtS5tO2VYT.', 'admin', 'uploads/users/admin.png', '2004-01-18', 'Không xác định', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL),
(2, 'fan1', 'fan1@example.com', '$2y$10$wgyAzpc4XVJ/6jvV2zxR6eT0AkNwJIuPu69wm8peCva665iUk.gpS', 'user', 'uploads/users/fan1.png', NULL, 'Không xác định', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL),
(3, 'Thuynga', 'Tnga2756@gmail.com', '$2y$10$BlnJFKCMxwQ7fdPpGsP2ae2WqWZYTkm6QTyP1YpPplzghYGruvqcG', 'user', NULL, NULL, 'Nữ', '2004-02-19', 'AR', '0258998454', '14,3', 3, 0, NULL, NULL),
(5, 'huong1234', 'huong@gmail.com', '$2y$10$kvY1hT0ZV2azk5EuvS1/OexjN.ytD/zKbXB0Ck5f5wyqVLRmPO/Rq', 'user', NULL, NULL, 'Nữ', '1996-06-04', 'AR', '0123456789', '1,19', 0, 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `headtohead`
--
ALTER TABLE `headtohead`
  ADD PRIMARY KEY (`h2h_id`),
  ADD UNIQUE KEY `unique_h2h` (`team1_id`,`team2_id`,`season_id`),
  ADD KEY `team2_id` (`team2_id`),
  ADD KEY `season_id` (`season_id`),
  ADD KEY `idx_h2h_teams` (`team1_id`,`team2_id`);

--
-- Indexes for table `managers`
--
ALTER TABLE `managers`
  ADD PRIMARY KEY (`manager_id`),
  ADD UNIQUE KEY `unique_team_id` (`team_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `idx_manager_name` (`name`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `home_team_id` (`home_team_id`),
  ADD KEY `away_team_id` (`away_team_id`),
  ADD KEY `season_id` (`season_id`),
  ADD KEY `stadium_id` (`stadium_id`),
  ADD KEY `idx_match_date` (`match_date`);

--
-- Indexes for table `matchevents`
--
ALTER TABLE `matchevents`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `idx_match_events` (`match_id`,`team_id`,`player_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `idx_news_date` (`publish_date`),
  ADD KEY `fk_news_category` (`category_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `idx_player_name` (`name`);

--
-- Indexes for table `playerstats`
--
ALTER TABLE `playerstats`
  ADD PRIMARY KEY (`stat_id`),
  ADD UNIQUE KEY `unique_player_season` (`player_id`,`season_id`),
  ADD KEY `season_id` (`season_id`),
  ADD KEY `idx_player_season` (`player_id`,`season_id`);

--
-- Indexes for table `seasons`
--
ALTER TABLE `seasons`
  ADD PRIMARY KEY (`season_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `stadiums`
--
ALTER TABLE `stadiums`
  ADD PRIMARY KEY (`stadium_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_stadium_name` (`name`);

--
-- Indexes for table `standings`
--
ALTER TABLE `standings`
  ADD PRIMARY KEY (`standing_id`),
  ADD UNIQUE KEY `season_id` (`season_id`,`team_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `unique_stadium_id` (`stadium_id`),
  ADD KEY `stadium_id` (`stadium_id`),
  ADD KEY `idx_team_name` (`name`);

--
-- Indexes for table `teamstats`
--
ALTER TABLE `teamstats`
  ADD PRIMARY KEY (`stat_id`),
  ADD UNIQUE KEY `unique_team_season` (`team_id`,`season_id`),
  ADD KEY `season_id` (`season_id`),
  ADD KEY `idx_team_season` (`team_id`,`season_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `goal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `headtohead`
--
ALTER TABLE `headtohead`
  MODIFY `h2h_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `managers`
--
ALTER TABLE `managers`
  MODIFY `manager_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=388;

--
-- AUTO_INCREMENT for table `matchevents`
--
ALTER TABLE `matchevents`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=526;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=421;

--
-- AUTO_INCREMENT for table `playerstats`
--
ALTER TABLE `playerstats`
  MODIFY `stat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6283;

--
-- AUTO_INCREMENT for table `seasons`
--
ALTER TABLE `seasons`
  MODIFY `season_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stadiums`
--
ALTER TABLE `stadiums`
  MODIFY `stadium_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `standings`
--
ALTER TABLE `standings`
  MODIFY `standing_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=299;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `teamstats`
--
ALTER TABLE `teamstats`
  MODIFY `stat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goals_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `goals_ibfk_3` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE SET NULL;

--
-- Constraints for table `headtohead`
--
ALTER TABLE `headtohead`
  ADD CONSTRAINT `headtohead_ibfk_1` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `headtohead_ibfk_2` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `headtohead_ibfk_3` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`) ON DELETE SET NULL;

--
-- Constraints for table `managers`
--
ALTER TABLE `managers`
  ADD CONSTRAINT `managers_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE SET NULL;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`team_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`team_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`stadium_id`) REFERENCES `stadiums` (`stadium_id`) ON DELETE SET NULL;

--
-- Constraints for table `matchevents`
--
ALTER TABLE `matchevents`
  ADD CONSTRAINT `matchevents_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matchevents_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `matchevents_ibfk_3` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE SET NULL;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE SET NULL;

--
-- Constraints for table `playerstats`
--
ALTER TABLE `playerstats`
  ADD CONSTRAINT `playerstats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playerstats_ibfk_2` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`) ON DELETE CASCADE;

--
-- Constraints for table `standings`
--
ALTER TABLE `standings`
  ADD CONSTRAINT `standings_ibfk_1` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `standings_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`stadium_id`) REFERENCES `stadiums` (`stadium_id`) ON DELETE SET NULL;

--
-- Constraints for table `teamstats`
--
ALTER TABLE `teamstats`
  ADD CONSTRAINT `teamstats_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teamstats_ibfk_2` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
