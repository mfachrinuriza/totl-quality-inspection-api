-- Alternatif migrate: Import lewat phpMyAdmin (tab Import) ke database QualityInspectionDb.
-- Jika memakai file ini, TIDAK perlu `php artisan migrate`.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `Th_QualityInspection` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `TransactionId` CHAR(36) NOT NULL,
  `ClientId` CHAR(36) NOT NULL,
  `Tower` VARCHAR(100) NULL,
  `Lantai` VARCHAR(100) NULL,
  `Zona` VARCHAR(100) NULL,
  `DrawingUrl` VARCHAR(1000) NULL,
  `PICTotal` VARCHAR(100) NULL,
  `MK` VARCHAR(100) NULL,
  `Vendor` VARCHAR(100) NULL,
  `Ketidaksesuaian` VARCHAR(1000) NULL,
  `DescTemuan` TEXT NULL,
  `Status` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UX_ThQI_ClientId` (`ClientId`),
  KEY `IX_ThQI_TransactionId` (`TransactionId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Td_QualityInspection_Picture` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ThQualityInspectionID` INT UNSIGNED NOT NULL,
  `PictureUrl` VARCHAR(1000) NULL,
  `Keterangan` VARCHAR(2000) NULL,
  `Status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY (`ThQualityInspectionID`),
  CONSTRAINT `FK_Pic_Th` FOREIGN KEY (`ThQualityInspectionID`) REFERENCES `Th_QualityInspection`(`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Td_QualityInspection_Pinpoint` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ThQualityInspectionID` INT UNSIGNED NOT NULL,
  `DrawingUrl` VARCHAR(1000) NULL,
  `Keterangan` VARCHAR(5000) NULL,
  `PinpointX` DECIMAL(18,12) NOT NULL,
  `PinpointY` DECIMAL(18,12) NOT NULL,
  `PageNumber` INT NOT NULL,
  `Status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY (`ThQualityInspectionID`),
  CONSTRAINT `FK_Pin_Th` FOREIGN KEY (`ThQualityInspectionID`) REFERENCES `Th_QualityInspection`(`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
