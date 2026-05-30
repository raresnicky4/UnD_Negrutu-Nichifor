package org.example;

import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

@Entity
@Table(name = "statistici_somaj")
public class Statistici {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    private String judet;

    private Integer anul;
    private Integer luna;

    private String grupaVarsta;
    private String nivelEducatie;
    private String mediu;

    private Integer numarSomeri;

    public Long getId() {
        return id;
    }

    public void setId(Long id) {
        this.id = id;
    }

    public String getJudet() {
        return judet;
    }

    public void setJudet(String judet) {
        this.judet = judet;
    }

    public Integer getAnul() {
        return anul;
    }

    public void setAnul(Integer anul) {
        this.anul = anul;
    }

    public Integer getLuna() {
        return luna;
    }

    public void setLuna(Integer luna) {
        this.luna = luna;
    }

    public String getGrupaVarsta() {
        return grupaVarsta;
    }

    public void setGrupaVarsta(String grupaVarsta) {
        this.grupaVarsta = grupaVarsta;
    }

    public String getNivelEducatie() {
        return nivelEducatie;
    }

    public void setNivelEducatie(String nivelEducatie) {
        this.nivelEducatie = nivelEducatie;
    }

    public String getMediu() {
        return mediu;
    }

    public void setMediu(String mediu) {
        this.mediu = mediu;
    }

    public Integer getNumarSomeri() {
        return numarSomeri;
    }

    public void setNumarSomeri(Integer numarSomeri) {
        this.numarSomeri = numarSomeri;
    }
}