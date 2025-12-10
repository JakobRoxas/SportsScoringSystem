DROP DATABASE IF EXISTS `sports_scoring_system`;
CREATE DATABASE `sports_scoring_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sports_scoring_system`;

CREATE TABLE `Tournament` (
  `TournamentID` CHAR(5) NOT NULL,
  `TournamentName` VARCHAR(100) NOT NULL,
  `TournamentYear` INT NOT NULL,
  PRIMARY KEY (`TournamentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Team` (
  `TeamID` CHAR(5) NOT NULL,
  `TeamName` VARCHAR(50) NOT NULL,
  `TeamCity` VARCHAR(50) NOT NULL,
  `TeamConf` ENUM('EAST','WEST') NOT NULL,
  PRIMARY KEY (`TeamID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Coach` (
  `CoachID` CHAR(5) NOT NULL,
  `CoachFname` VARCHAR(50) NOT NULL,
  `CoachLname` VARCHAR(50) NOT NULL,
  `TeamID` CHAR(5) NOT NULL,
  PRIMARY KEY (`CoachID`),
  INDEX (`TeamID`),
  CONSTRAINT `fk_coach_team` FOREIGN KEY (`TeamID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Player` (
  `PlayerID` CHAR(5) NOT NULL,
  `PlayerFname` VARCHAR(50) NOT NULL,
  `PlayerLname` VARCHAR(50) NOT NULL,
  `TeamID` CHAR(5) NOT NULL,
  PRIMARY KEY (`PlayerID`),
  INDEX (`TeamID`),
  CONSTRAINT `fk_player_team` FOREIGN KEY (`TeamID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `TournamentTeam` (
  `TournamentID` CHAR(5) NOT NULL,
  `TeamID` CHAR(5) NOT NULL,
  `Seed` INT NOT NULL,
  PRIMARY KEY (`TournamentID`,`TeamID`),
  INDEX (`TournamentID`),
  INDEX (`TeamID`),
  CONSTRAINT `fk_tournamentteam_tournament` FOREIGN KEY (`TournamentID`) REFERENCES `Tournament` (`TournamentID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_tournamentteam_team` FOREIGN KEY (`TeamID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Round` (
  `RoundID` CHAR(5) NOT NULL,
  `RoundType` ENUM('PRELIMINARIES','SEMI-FINALS','FINALS') NOT NULL,
  `RoundNumber` INT NOT NULL,
  `TournamentID` CHAR(5) NOT NULL,
  PRIMARY KEY (`RoundID`),
  INDEX (`TournamentID`),
  CONSTRAINT `fk_round_tournament` FOREIGN KEY (`TournamentID`) REFERENCES `Tournament` (`TournamentID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Series` (
  `SeriesID` CHAR(5) NOT NULL,
  `RoundID` CHAR(5) NOT NULL,
  `Team1ID` CHAR(5) NOT NULL,
  `Team2ID` CHAR(5) NOT NULL,
  `WinnerTeamID` CHAR(5) DEFAULT NULL,
  PRIMARY KEY (`SeriesID`),
  INDEX (`RoundID`),
  INDEX (`Team1ID`),
  INDEX (`Team2ID`),
  INDEX (`WinnerTeamID`),
  CONSTRAINT `fk_series_round` FOREIGN KEY (`RoundID`) REFERENCES `Round` (`RoundID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_series_team1` FOREIGN KEY (`Team1ID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_series_team2` FOREIGN KEY (`Team2ID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_series_winner` FOREIGN KEY (`WinnerTeamID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Game` (
  `GameID` CHAR(5) NOT NULL,
  `SeriesID` CHAR(5) NOT NULL,
  `GameNumber` INT NOT NULL,
  `GameDate` DATE NOT NULL,
  `Venue` VARCHAR(100) NOT NULL,
  `WinnerTeamID` CHAR(5) DEFAULT NULL,
  PRIMARY KEY (`GameID`),
  INDEX (`SeriesID`),
  INDEX (`WinnerTeamID`),
  CONSTRAINT `fk_game_series` FOREIGN KEY (`SeriesID`) REFERENCES `Series` (`SeriesID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_game_winner` FOREIGN KEY (`WinnerTeamID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `Score` (
  `GameID` CHAR(5) NOT NULL,
  `TeamID` CHAR(5) NOT NULL,
  `PointsScored` INT NOT NULL,
  PRIMARY KEY (`GameID`,`TeamID`),
  INDEX (`GameID`),
  INDEX (`TeamID`),
  CONSTRAINT `fk_score_game` FOREIGN KEY (`GameID`) REFERENCES `Game` (`GameID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_score_team` FOREIGN KEY (`TeamID`) REFERENCES `Team` (`TeamID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `PlayerStat` (
  `GameID` CHAR(5) NOT NULL,
  `PlayerID` CHAR(5) NOT NULL,
  `Points` INT NOT NULL,
  `Rebounds` INT NOT NULL,
  `Assists` INT NOT NULL,
  PRIMARY KEY (`GameID`,`PlayerID`),
  INDEX (`GameID`),
  INDEX (`PlayerID`),
  CONSTRAINT `fk_playerstat_game` FOREIGN KEY (`GameID`) REFERENCES `Game` (`GameID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_playerstat_player` FOREIGN KEY (`PlayerID`) REFERENCES `Player` (`PlayerID`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;