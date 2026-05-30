CREATE DATABASE IF NOT EXISTS somaj_romania;

USE somaj_romania;

CREATE TABLE IF NOT EXISTS statistici (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judet VARCHAR(50) NOT NULL,
    anul INT NOT NULL,
    luna INT NOT NULL,
    grupa_varsta VARCHAR(50),
    nivel_educatie VARCHAR(100),
    mediu VARCHAR(20),
    numar_someri INT NOT NULL
);