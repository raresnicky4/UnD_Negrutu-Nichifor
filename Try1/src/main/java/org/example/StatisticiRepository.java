package org.example;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface StatisticiRepository extends JpaRepository<Statistici, Long> {

    boolean existsByAnul(Integer an);
}