package org.example;

import org.springframework.cache.annotation.Cacheable;
import org.springframework.data.domain.Example;
import org.springframework.web.bind.annotation.*;
import java.util.List;

@RestController
@RequestMapping("/api/statistici")
@CrossOrigin(origins = "*")
public class StatisticiController {

    private final StatisticiRepository repository;
    private final IncarcatorDate incarcatorDate;

    public StatisticiController(StatisticiRepository repository, IncarcatorDate incarcatorDate) {
        this.repository = repository;
        this.incarcatorDate = incarcatorDate;
    }

    @Cacheable("statisticiCache")
    @GetMapping("/filtreaza")
    public List<Statistici> getStatisticiFiltrare(
            @RequestParam(required = false) String judet,
            @RequestParam(required = false) Integer an,
            @RequestParam(required = false) String grupaVarsta,
            @RequestParam(required = false) String educatie,
            @RequestParam(required = false) String mediu) {

        if (an != null) {
            incarcatorDate.descarcaLaCerere(an);
        }

        Statistici modelCautare = new Statistici();
        if (judet != null && !judet.isEmpty()) modelCautare.setJudet(judet);
        if (grupaVarsta != null && !grupaVarsta.isEmpty()) modelCautare.setGrupaVarsta(grupaVarsta);
        if (educatie != null && !educatie.isEmpty()) modelCautare.setNivelEducatie(educatie);
        if (mediu != null && !mediu.isEmpty()) modelCautare.setMediu(mediu);

        List<Statistici> toateDatele = repository.findAll(Example.of(modelCautare));

        if (an != null) {
            toateDatele.removeIf(stat -> stat.getAnul() != an && stat.getAnul() != (an - 1));
        }

        return toateDatele;
    }
}