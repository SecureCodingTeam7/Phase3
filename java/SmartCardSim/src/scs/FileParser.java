package scs;

import java.io.File;
import java.io.IOException;
import java.nio.charset.Charset;
import java.nio.file.Files;
import java.util.List;

/**
 * Created by mep on 26.11.14.
 */
public class FileParser {

    private File file;
    private String destination;

    private String amount;

    public FileParser(File file) {
        this.file = file;
    }

    public void parse() throws IOException {
        List<String> lines = Files.readAllLines(file.toPath(), Charset.defaultCharset());

        for(String line : lines) {
            if(line.startsWith("destination:")) {
                destination = line.substring(12);
            }

            if(line.startsWith("amount:")) {
                amount = line.substring(7);
                // we are ready now !
                return;
            }
        }
    }

    public String getDestination() {
        return destination;
    }

    public String getAmount() {
        return amount;
    }
}
