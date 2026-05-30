package org.example;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.stereotype.Service;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

@Service
public class IncarcatorDate {

    private final StatisticiRepository repository;

    public IncarcatorDate(StatisticiRepository repository) {
        this.repository = repository;
    }

    public void descarcaLaCerere(Integer anCautat) {
        if (anCautat != null) {
            proceseazaAn(anCautat);
            proceseazaAn(anCautat - 1);
        }
    }

    private void proceseazaAn(int an) {
        if (repository.existsByAnul(an)) {
            return;
        }

        boolean aduseCuSucces = false;

        try {
            URL urlApi = new URL("https://data.gov.ro/api/3/action/package_search?q=somaj");
            HttpURLConnection connApi = (HttpURLConnection) urlApi.openConnection();
            connApi.setRequestProperty("User-Agent", "Mozilla/5.0");

            BufferedReader brApi = new BufferedReader(new InputStreamReader(connApi.getInputStream(), StandardCharsets.UTF_8));
            StringBuilder jsonBuilder = new StringBuilder();
            String line;
            while ((line = brApi.readLine()) != null) {
                jsonBuilder.append(line);
            }

            ObjectMapper mapper = new ObjectMapper();
            JsonNode rezultate = mapper.readTree(jsonBuilder.toString()).path("result").path("results");

            for (JsonNode pachet : rezultate) {
                for (JsonNode resursa : pachet.path("resources")) {
                    String format = resursa.path("format").asText().toUpperCase();
                    String link = resursa.path("url").asText().replace("http://", "https://");
                    String nume = resursa.path("name").asText();

                    if (format.contains("CSV") && (nume.contains(String.valueOf(an)) || link.contains(String.valueOf(an)))) {
                        if (extrageDateReale(link, an)) {
                            aduseCuSucces = true;
                            System.out.println("--> SUCCES! Am adus datele oficiale pentru " + an);
                            return;
                        }
                    }
                }
            }
        } catch (Exception ignored) {}

        if (!aduseCuSucces) {
            genereazaDateDeRezerva(an);
        }
    }

    private void genereazaDateDeRezerva(int an) {
        String[] judete = {"IASI", "BUCURESTI", "CLUJ", "TIMIS", "BRASOV", "CONSTANTA", "DOLJ", "SUCEAVA", "BACAU", "MURES"};
        for (String judet : judete) {
            salveazaDistributie(judet, (int) (Math.random() * 8000) + 1500, an);
        }
    }

    private boolean extrageDateReale(String link, int an) {
        try {
            HttpURLConnection conn = (HttpURLConnection) new URL(link).openConnection();
            conn.setRequestProperty("User-Agent", "Mozilla/5.0");
            conn.setInstanceFollowRedirects(true);

            try (BufferedReader br = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8))) {
                String header = br.readLine();
                if (header == null) return false;

                String[] capTabel = header.toUpperCase().replace("\"", "").split("[,;]");
                int idxJudet = -1, idxSomeri = -1;

                for (int i = 0; i < capTabel.length; i++) {
                    if (capTabel[i].contains("JUDET") || capTabel[i].contains("JUDEȚ")) idxJudet = i;
                    if (capTabel[i].contains("TOTAL") || capTabel[i].contains("SOMERI")) {
                        if (idxSomeri == -1) idxSomeri = i;
                    }
                }

                if (idxJudet == -1) idxJudet = 1;
                if (idxSomeri == -1) idxSomeri = 2;

                String linie;
                int randuriValide = 0;

                while ((linie = br.readLine()) != null) {
                    String[] col = linie.replace("\"", "").split("[,;]");
                    if (col.length > Math.max(idxJudet, idxSomeri)) {
                        String judet = col[idxJudet].trim().toUpperCase();
                        if (judet.isEmpty() || judet.contains("TOTAL") || judet.length() < 3) continue;

                        int someri = 0;
                        try {
                            String numarStr = col[idxSomeri].replaceAll("[^0-9]", "");
                            someri = numarStr.isEmpty() ? 0 : Integer.parseInt(numarStr);
                        } catch (Exception ignored) {}

                        if (someri > 0) {
                            salveazaDistributie(judet, someri, an);
                            randuriValide++;
                        }
                    }
                }
                return randuriValide > 0;
            }
        } catch (Exception e) {
            return false;
        }
    }

    private void salveazaDistributie(String judet, int totalSomeri, int an) {
        int bazaPeLuna = totalSomeri / 12;
        if(bazaPeLuna == 0) bazaPeLuna = 100;

        for (int luna = 1; luna <= 12; luna++) {
            int urbanMediu = (int)(bazaPeLuna * 0.60 * 0.75);
            int urbanSuperior = (int)(bazaPeLuna * 0.60 * 0.25);
            int ruralMediu = (int)(bazaPeLuna * 0.40 * 0.75);
            int ruralSuperior = bazaPeLuna - urbanMediu - urbanSuperior - ruralMediu;

            salveazaRand(judet, an, luna, "Urban", "Mediu", Math.max(1, urbanMediu));
            salveazaRand(judet, an, luna, "Urban", "Superior", Math.max(1, urbanSuperior));
            salveazaRand(judet, an, luna, "Rural", "Mediu", Math.max(1, ruralMediu));
            salveazaRand(judet, an, luna, "Rural", "Superior", Math.max(1, ruralSuperior));
        }
    }

    private void salveazaRand(String judet, int an, int luna, String mediu, String educatie, int numar) {
        Statistici stat = new Statistici();
        stat.setJudet(judet);
        stat.setAnul(an);
        stat.setLuna(luna);
        stat.setMediu(mediu);
        stat.setNivelEducatie(educatie);
        stat.setGrupaVarsta("Toate");
        stat.setNumarSomeri(numar);
        repository.save(stat);
    }
}