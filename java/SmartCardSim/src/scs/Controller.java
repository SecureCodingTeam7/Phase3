package scs;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.TabPane;
import javafx.scene.control.TextField;
import javafx.scene.input.Clipboard;
import javafx.scene.input.ClipboardContent;
import javafx.scene.paint.Color;
import javafx.stage.FileChooser;

import java.io.File;
import java.io.IOException;
import java.net.URL;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.ResourceBundle;

public class Controller implements Initializable {

    @FXML
    private Label statusLabel;
    @FXML
    private TextField destinationTextField;
    @FXML
    private TextField pinTextField;
    @FXML
    private TextField amountTextField;
    @FXML
    private Label fileLabel;
    @FXML
    private Button copyTANButton;
    @FXML
    private TabPane tabPane;

    private String tan;
    private File file;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        statusLabel.setVisible(false);
        copyTANButton.setVisible(false);
        tabPane.getStyleClass().add("floating");
    }

    @FXML
    private void copyTANClicked(ActionEvent event) {
        final Clipboard clipboard = Clipboard.getSystemClipboard();
        final ClipboardContent content = new ClipboardContent();
        content.putString(tan);
        clipboard.setContent(content);
    }

    @FXML
    private void searchClicked(ActionEvent event) {
        FileChooser chooser = new FileChooser();
        file = chooser.showOpenDialog(statusLabel.getScene().getWindow());

        fileLabel.setText("File: " + file.getName());
    }

    @FXML
    private void generateTANClicked(ActionEvent event) {
        statusLabel.setVisible(false);
        copyTANButton.setVisible(false);

        if(!validateInputs()) {
            return;
        }

        String destination = null;
        String amount = null;

        if(tabPane.getSelectionModel().getSelectedIndex() == 0) {
            destination = destinationTextField.getText();
            amount = amountTextField.getText();
        } else {
            FileParser parser = new FileParser(file);
            try {
                parser.parse();
                destination = parser.getDestination();
                amount = parser.getAmount();

                if(destination == null || amount == null) {
                    setNegativeStatus("Destination or amount missing in file!");
                    return;
                }

                if(!validateInputs(destination, amount)) {
                    return;
                }

            } catch (IOException e) {
                e.printStackTrace();
                setNegativeStatus("Cannot read from file!");
                return;
            }
        }

        generateTAN(destination, amount);
    }

    private void generateTAN(String destination, String amount) {
        String pin = pinTextField.getText();

        long time;
        try {
            time = NetworkTime.getTime();
        } catch (IOException ex) {
            ex.printStackTrace();
            setNegativeStatus("Could not reach time server!");
            return;
        }

        long seed = time - time % (1 * 60);

        MessageDigest md5Digest;

        try {
            md5Digest = MessageDigest.getInstance("MD5");
        } catch (NoSuchAlgorithmException e) {
            // this should normally not happen!
            e.printStackTrace();
            setNegativeStatus("Could not get md5 digest!");
            return;
        }

        byte[] md5 = md5Digest.digest((seed + pin + destination + amount + seed).getBytes());

        StringBuffer stringBuffer = new StringBuffer();

        for(byte b : md5) {
            stringBuffer.append(Math.abs(b));
        }

        tan = stringBuffer.toString().substring(0, 15);

        setPositiveStatus("Your TAN: " + tan);
    }

    private boolean validateInputs() {

        String pin = pinTextField.getText();
        String destination = destinationTextField.getText();
        String amount = amountTextField.getText();

        if(pin.length() != 6) {
            setNegativeStatus("PIN must have exactly six digits!");
            return false;
        }

        if(!pin.matches("[0-9]+")) {
            setNegativeStatus("PIN must contain only digits!");
            return false;
        }

        // If we are using a batch file just test the file
        if(tabPane.getSelectionModel().getSelectedIndex() == 1) {
            setNegativeStatus("Please specify a file!");
            return file != null;
        }

        return validateInputs(destination, amount);
    }

    private boolean validateInputs(String destination, String amount) {

        if(destination.isEmpty() || amount.isEmpty()) {
            setNegativeStatus("Please fill in all fields!");
            return false;
        }

        if(destination.length() != 10) {
            setNegativeStatus("Destination must have exactly ten digits!");
            return false;
        }

        if(!destination.matches("[0-9]+")) {
            setNegativeStatus("Destination must contain only digits!");
            return false;
        }

        if(!amount.matches("[0-9]+(\\.\\d{1,2})?")) {
            setNegativeStatus("Amount must be a valid amount!");
            return false;
        }

        return true;
    }

    private void setNegativeStatus(String errorMessage) {
        statusLabel.setTextFill(Color.FIREBRICK);
        statusLabel.setText(errorMessage);
        statusLabel.setVisible(true);
        copyTANButton.setVisible(false);
    }

    private void setPositiveStatus(String message) {
        statusLabel.setTextFill(Color.GREEN);
        statusLabel.setText(message);
        statusLabel.setVisible(true);
        copyTANButton.setVisible(true);
    }
}
